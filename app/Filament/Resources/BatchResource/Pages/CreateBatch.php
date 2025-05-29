<?php

namespace App\Filament\Resources\BatchResource\Pages;

use App\Contracts\PhoneValidatorInterface;
use App\Filament\Resources\BatchResource;
use App\Models\BatchRecipient;
use App\Models\Provider;
use App\Services\RabbitMQService;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CreateBatch extends CreateRecord
{
    protected static string $resource = BatchResource::class;

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->submit(null)
            ->requiresConfirmation()
            ->action(function () {
                $this->closeActionModal();
                $this->create();
            });
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        if (! $record->file_name) {
            return;
        }

        $path = Storage::disk('public')->path($record->file_name);
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        $phones = [];

        if (in_array($extension, ['csv'])) {
            $rows = array_slice(array_map('str_getcsv', file($path)), 1);
            foreach ($rows as $row) {
                $phone = trim($row[0] ?? '');
                if ($phone) {
                    $phones[] = $phone;
                }
            }
        } elseif (in_array($extension, ['xls', 'xlsx'])) {
            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getActiveSheet();

            foreach ($sheet->getRowIterator() as $row) {
                $cell = $row->getCellIterator('A', 'A')->current();
                $phone = trim((string) $cell->getValue());

                if ($phone) {
                    $phones[] = $phone;
                }
            }
        }

        $now = now();
        $batchData = [];

        $validator = app(PhoneValidatorInterface::class);
        foreach ($phones as $phone) {
            $batchData[] = [
                'phone' => $phone,
                'batch_id' => $record->id,
                'is_valid' => $validator->isValidPhone($phone),
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if (count($batchData) >= 1000) {
                BatchRecipient::insert($batchData);
                $batchData = [];
            }
        }

        if (count($batchData) > 0) {
            BatchRecipient::insert($batchData);
        }
        $this->sendToRabbitMQ($record, $phones);
    }

    protected function sendToRabbitMQ($record, $phones): void
    {
        $provider = Provider::find($record->provider_id);

        if (! $provider) {
            Log::error("Provider with ID {$record->provider_id} not found.");

            return;
        }

        $baseOtpMessage = [
            'metadata' => [
                'priority' => 'high',
                'service_name' => 'texnomart',
                'type' => 'otp',
                'batch_id' => $record->id,
                'provider_id' => $record->provider_id,
                'timestamp' => time(),
            ],
        ];

        $messages = array_map(function ($phone) use ($record) {
            return ['phone' => $phone, 'message' => $record->message];
        }, $phones);

        $messageChunks = array_chunk($messages, $provider->batch_size);

        foreach ($messageChunks as $chunk) {
            $otpMessage = $baseOtpMessage;
            $otpMessage['messages'] = $chunk;

            try {
                $rabbitMQService = RabbitMQService::getInstance();
                $rabbitMQService->publish('', $otpMessage, 1);
                Log::info('Sent batch to RabbitMQ with '.count($chunk)." messages. Batch ID: {$record->id}");
            } catch (\Exception $e) {
                Log::error("Failed to send batch {$record->id} to RabbitMQ: ".$e->getMessage());
            }
        }
    }
}
