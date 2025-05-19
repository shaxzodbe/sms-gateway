<?php

namespace App\Filament\Resources\BatchRecipientResource\Pages;

use App\Filament\Resources\BatchRecipientResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBatchRecipient extends EditRecord
{
    protected static string $resource = BatchRecipientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
