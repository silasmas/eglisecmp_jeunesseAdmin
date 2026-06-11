<?php

namespace App\Filament\Resources\RegistrationFormConfigs\Pages;

use App\Enums\RegistrationFormColumnSpan;
use App\Filament\Resources\RegistrationFormConfigs\RegistrationFormConfigResource;
use App\Models\RegistrationFormConfigSet;
use App\Models\RegistrationFormFieldItem;
use App\Services\RegistrationFormConfigService;
use App\Support\RegistrationFieldRegistry;
use App\Support\RegistrationFormFieldAccess;
use App\Support\RegistrationFormUiSettings;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Page d'édition d'un jeu de configuration du formulaire d'inscription.
 */
class EditRegistrationFormConfig extends EditRecord
{
    protected static string $resource = RegistrationFormConfigResource::class;

    /**
     * Actions disponibles dans l'en-tête.
     *
     * @return array<int, mixed>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('publish')
                ->label('Appliquer au formulaire')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Appliquer cette configuration')
                ->modalDescription('Cette configuration sera publiée et impactera immédiatement le formulaire d\'inscription public (pour l\'événement concerné ou le modèle par défaut).')
                ->action(function (): void {
                    $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
                    app(RegistrationFormConfigService::class)->publish($this->record);

                    Notification::make()
                        ->title('Configuration appliquée')
                        ->body('Le formulaire public utilise désormais cette configuration.')
                        ->success()
                        ->send();

                    $this->refreshFormData([
                        'is_published',
                        'published_at',
                        'items',
                        'field_order',
                        'ui_settings',
                        'payment_modes_order',
                        'mobile_money_providers_order',
                    ]);
                }),
            DeleteAction::make()
                ->visible(fn (RegistrationFormConfigSet $record): bool => ! $record->isDefaultTemplate()),
        ];
    }

    /**
     * Hydrate le formulaire avec les lignes de champs existantes.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var RegistrationFormConfigSet $record */
        $record = $this->record;
        $record->loadMissing('fieldItems');

        $itemsByKey = $record->fieldItems->keyBy(
            fn (RegistrationFormFieldItem $item): string => $item->field_key->value
        );

        $data['preview_step'] = 0;
        $mergedUi = RegistrationFormUiSettings::merge($record->ui_settings);
        $data['ui_settings'] = $mergedUi;
        $data['payment_modes_order'] = RegistrationFormUiSettings::paymentModesOrderState($mergedUi);
        $data['mobile_money_providers_order'] = RegistrationFormUiSettings::mobileProvidersOrderState($mergedUi);
        $data['field_order'] = app(RegistrationFormConfigService::class)->buildFieldOrderState($itemsByKey);
        $data['items'] = [];

        foreach (RegistrationFieldRegistry::all() as $definition) {
            $item = $itemsByKey->get($definition->key->value);
            $isConfigurable = app(RegistrationFormConfigService::class)->isFieldConfigurable($definition, $item);

            $data['items'][$definition->key->value] = [
                'is_admin_unlocked' => (bool) ($item?->is_admin_unlocked ?? false),
                'is_visible' => $isConfigurable
                    ? ($item?->is_visible ?? $definition->defaultVisible)
                    : true,
                'is_required' => $isConfigurable
                    ? ($item?->is_required ?? $definition->defaultRequired)
                    : true,
                'column_span' => ($item?->column_span ?? $definition->defaultColumnSpan)->value,
                'label_override' => $item?->label_override,
                'helper_text_override' => $item?->helper_text_override,
            ];
        }

        return $data;
    }

    /**
     * Nettoie les données avant persistance du modèle parent.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset(
            $data['items'],
            $data['is_published'],
            $data['published_at'],
            $data['preview_step'],
            $data['field_order'],
            $data['payment_modes_order'],
            $data['mobile_money_providers_order'],
        );

        return $data;
    }

    /**
     * Persiste les métadonnées et les lignes de champs.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var RegistrationFormConfigSet $record */
        $state = $this->form->getState();
        $items = $state['items'] ?? [];
        $fieldOrder = $state['field_order'] ?? [];
        $sortOrders = app(RegistrationFormConfigService::class)->sortOrdersFromFieldOrder($fieldOrder);
        $canUnlock = RegistrationFormFieldAccess::canUnlockLockedFields(Auth::user());

        $existingItems = $record->fieldItems()->get()->keyBy(
            fn (RegistrationFormFieldItem $item): string => $item->field_key->value
        );

        foreach (RegistrationFieldRegistry::all() as $definition) {
            $payload = $items[$definition->key->value] ?? [];
            $existingItem = $existingItems->get($definition->key->value);

            $isAdminUnlocked = false;
            if ($definition->isLocked) {
                $isAdminUnlocked = $canUnlock
                    ? (bool) ($payload['is_admin_unlocked'] ?? false)
                    : (bool) ($existingItem?->is_admin_unlocked ?? false);
            }

            $isConfigurable = ! $definition->isLocked || $isAdminUnlocked;

            if ($definition->isLocked && ! $isAdminUnlocked) {
                $isVisible = true;
                $isRequired = true;
                $columnSpan = $definition->defaultColumnSpan;
            } else {
                $isVisible = (bool) ($payload['is_visible'] ?? $definition->defaultVisible);
                $isRequired = (bool) ($payload['is_required'] ?? $definition->defaultRequired);

                if (! $isVisible) {
                    $isRequired = false;
                }

                $columnSpanValue = (string) ($payload['column_span'] ?? $definition->defaultColumnSpan->value);
                $columnSpan = RegistrationFormColumnSpan::tryFrom($columnSpanValue) ?? $definition->defaultColumnSpan;
            }

            $labelOverride = isset($payload['label_override']) ? trim((string) $payload['label_override']) : null;
            $labelOverride = $labelOverride !== '' ? Str::limit($labelOverride, 200, '') : null;

            $helperOverride = isset($payload['helper_text_override']) ? trim((string) $payload['helper_text_override']) : null;
            $helperOverride = $helperOverride !== '' ? Str::limit($helperOverride, 500, '') : null;

            RegistrationFormFieldItem::query()->updateOrCreate(
                [
                    'reg_form_config_set_id' => $record->id,
                    'field_key' => $definition->key->value,
                ],
                [
                    'is_visible' => $isVisible,
                    'is_required' => $isRequired,
                    'column_span' => $columnSpan,
                    'is_admin_unlocked' => $isAdminUnlocked,
                    'label_override' => $labelOverride,
                    'helper_text_override' => $helperOverride,
                    'sort_order' => $sortOrders[$definition->key->value] ?? $definition->defaultSortOrder,
                ]
            );
        }

        $uiPayload = $state['ui_settings'] ?? [];
        $uiPayload['payment_modes_order'] = RegistrationFormUiSettings::paymentModesOrderFromRepeater(
            $state['payment_modes_order'] ?? []
        );
        $uiPayload['mobile_money_providers_order'] = RegistrationFormUiSettings::mobileProvidersOrderFromRepeater(
            $state['mobile_money_providers_order'] ?? []
        );
        $uiSettings = RegistrationFormUiSettings::merge($uiPayload);

        if (! RegistrationFormUiSettings::hasVisiblePaymentMode($uiSettings)) {
            Notification::make()
                ->title('Moyen de paiement requis')
                ->body('Au moins un moyen de paiement doit rester visible sur le formulaire public.')
                ->danger()
                ->send();

            $this->halt();
        }

        if (
            RegistrationFormUiSettings::isPaymentModeVisible($uiSettings, 'mobile_money')
            && ! RegistrationFormUiSettings::hasVisibleMobileProvider($uiSettings)
        ) {
            Notification::make()
                ->title('Opérateur Mobile Money requis')
                ->body('Le mode Mobile money est affiché : au moins un opérateur (M-Pesa, Orange, Airtel, Afri…) doit rester visible.')
                ->danger()
                ->send();

            $this->halt();
        }

        if (! RegistrationFormUiSettings::hasVisibleContactField($uiSettings)) {
            Notification::make()
                ->title('Contact requis')
                ->body('Au moins le téléphone ou l’e-mail doit rester visible à l’étape identité.')
                ->danger()
                ->send();

            $this->halt();
        }

        $record->update([
            'name' => $data['name'] ?? $record->name,
            'ui_settings' => $uiSettings,
        ]);

        return $record->fresh(['fieldItems', 'event']);
    }

    /**
     * Notification après enregistrement : rappel d’appliquer la configuration.
     */
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Brouillon enregistré')
            ->body('Les modifications sont sauvegardées. Cliquez sur « Appliquer au formulaire » en haut de page pour les rendre effectives sur le formulaire public.')
            ->success()
            ->duration(10000);
    }
}
