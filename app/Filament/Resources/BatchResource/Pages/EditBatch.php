<?php

namespace App\Filament\Resources\BatchResource\Pages;

use App\Contracts\PhoneValidatorInterface;
use App\Filament\Resources\BatchResource;
use App\Models\BatchRecipient;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class EditBatch extends EditRecord
{
    protected static string $resource = BatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function afterSave(): void
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
    }
}
