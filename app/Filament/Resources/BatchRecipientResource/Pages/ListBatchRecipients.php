<?php

namespace App\Filament\Resources\BatchRecipientResource\Pages;

use App\Filament\Resources\BatchRecipientResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBatchRecipients extends ListRecords
{
    protected static string $resource = BatchRecipientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
