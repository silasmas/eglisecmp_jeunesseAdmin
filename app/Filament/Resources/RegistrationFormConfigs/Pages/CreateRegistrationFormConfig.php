<?php

namespace App\Filament\Resources\RegistrationFormConfigs\Pages;

use App\Filament\Resources\RegistrationFormConfigs\RegistrationFormConfigResource;
use App\Models\ChurchEvent;
use App\Models\RegistrationFormConfigSet;
use App\Services\RegistrationFormConfigService;
use App\Support\RegistrationFormUiSettings;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * Page de création d'un jeu de configuration du formulaire.
 */
class CreateRegistrationFormConfig extends CreateRecord
{
    protected static string $resource = RegistrationFormConfigResource::class;

    /**
     * Valeurs par défaut du formulaire (blocs, ordre paiement).
     */
    public function mount(): void
    {
        parent::mount();

        $this->form->fill(array_merge($this->form->getState(), [
            'ui_settings' => RegistrationFormUiSettings::merge(null),
            'payment_modes_order' => RegistrationFormUiSettings::paymentModesOrderState(null),
            'mobile_money_providers_order' => RegistrationFormUiSettings::mobileProvidersOrderState(null),
        ]));
    }

    /**
     * Prépare les données avant création du jeu de configuration.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $eventId = $data['church_event_id'] ?? null;

        if ($eventId === null) {
            $data['name'] = $data['name'] ?? 'Modèle par défaut';
        } else {
            $event = ChurchEvent::query()->find($eventId);
            $data['name'] = $data['name'] ?? ($event ? "Formulaire — {$event->name}" : 'Configuration événement');
        }

        $data['is_published'] = false;

        return $data;
    }

    /**
     * Crée le jeu puis initialise les lignes de champs.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        if (isset($data['church_event_id']) && $data['church_event_id'] !== null) {
            $exists = RegistrationFormConfigSet::query()
                ->where('church_event_id', $data['church_event_id'])
                ->exists();

            if ($exists) {
                Notification::make()
                    ->title('Configuration déjà existante')
                    ->body('Un jeu de configuration existe déjà pour cet événement. Modifiez-le plutôt qu’en créer un nouveau.')
                    ->danger()
                    ->send();

                $this->halt();
            }
        }

        if (! isset($data['church_event_id']) || $data['church_event_id'] === null) {
            $defaultExists = RegistrationFormConfigSet::query()
                ->whereNull('church_event_id')
                ->exists();

            if ($defaultExists) {
                Notification::make()
                    ->title('Modèle par défaut déjà présent')
                    ->body('Le modèle par défaut existe déjà. Éditez-le ou créez une configuration liée à un événement.')
                    ->danger()
                    ->send();

                $this->halt();
            }
        }

        $state = $this->form->getState();
        $sourceSetId = isset($state['source_config_set_id']) ? (int) $state['source_config_set_id'] : null;
        $configService = app(RegistrationFormConfigService::class);
        $sourceSet = $configService->resolveSourceConfigSetForCreate($sourceSetId ?: null);

        /** @var RegistrationFormConfigSet $record */
        $record = RegistrationFormConfigSet::query()->create([
            'church_event_id' => $data['church_event_id'] ?? null,
            'name' => $data['name'],
            'is_published' => false,
            'ui_settings' => RegistrationFormUiSettings::merge($sourceSet?->ui_settings),
        ]);

        $configService->seedFieldItems($record, $sourceSet);

        return $record;
    }

    /**
     * Notification après création : rappel d’appliquer la configuration.
     */
    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Configuration créée')
            ->body('Le brouillon est prêt. Modifiez si besoin, enregistrez, puis cliquez sur « Appliquer au formulaire » pour l’activer sur le formulaire public.')
            ->success()
            ->duration(10000);
    }

    /**
     * Redirige vers l'édition après création.
     */
    protected function getRedirectUrl(): string
    {
        return RegistrationFormConfigResource::getUrl('edit', ['record' => $this->record]);
    }
}
