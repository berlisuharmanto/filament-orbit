<?php

namespace ProjectMoon\FilamentDomainManager\Resources\DomainResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use ProjectMoon\FilamentDomainManager\Models\Domain;
use ProjectMoon\FilamentDomainManager\Resources\DomainResource;

class CreateDomain extends CreateRecord
{
    protected static string $resource = DomainResource::class;

    protected function afterCreate(): void
    {
        /** @var Domain $record */
        $record = $this->record;

        if ($record->connection_mode === 'auto') {
            $result = $record->provisionWithProvider();

            if ($result->success) {
                Notification::make()
                    ->title('Domain Auto-Provisioned!')
                    ->body("DNS records were successfully provisioned via {$record->provider}.")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Provider Auto-Provisioning Error')
                    ->body($result->errorMessage ?: 'Failed to configure provider records.')
                    ->danger()
                    ->send();
            }
        }
    }
}
