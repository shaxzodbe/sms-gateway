<?php

namespace App\Filament\Resources\BatchResource\Pages;

use App\Filament\Resources\BatchResource;
use App\Models\BatchRecipient;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CreateBatch extends CreateRecord
{
    protected static string $resource = BatchResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record; // созданный Batch

        if (! $record->file_name) {
            return;
        }

        $path = Storage::disk('public')->path($record->file_name);
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        $phones = [];

        if (in_array($extension, ['csv'])) {
            $rows = array_map('str_getcsv', file($path));
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
                $cell = $row->getCellIterator()->current();
                $phone = trim((string) $cell->getValue());

                if ($phone) {
                    $phones[] = $phone;
                }
            }
        }

        $validator = app(\App\Contracts\PhoneValidatorInterface::class);
        foreach ($phones as $phone) {
            BatchRecipient::create([
                'phone' => $phone,
                'batch_id' => $record->id,
                'is_valid' => $validator->isValidPhone($phone),
            ]);
        }
    }
}
