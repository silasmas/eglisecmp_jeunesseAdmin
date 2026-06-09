<?php

namespace App\Filament\Resources\RegistrationFormConfigs\Schemas;

use App\Enums\RegistrationFormColumnSpan;
use App\Models\ChurchEvent;
use App\Models\RegistrationFormConfigSet;
use App\Support\RegistrationFieldRegistry;
use App\Support\RegistrationFormFieldAccess;
use App\Support\RegistrationFormPreviewBuilder;
use App\Support\RegistrationFormUiSettings;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

/**
 * Schéma Filament pour éditer un jeu de configuration du formulaire d'inscription.
 */
class RegistrationFormConfigForm
{
    /**
     * Construit le schéma du formulaire Filament.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(array_merge(
                self::headerComponents(),
                [self::uiBlocksSection()],
                [self::previewSection()],
                [self::fieldOrderSection()],
                self::fieldSections(),
            ));
    }

    /**
     * En-tête : métadonnées du jeu de configuration.
     *
     * @return array<int, mixed>
     */
    protected static function headerComponents(): array
    {
        $canUnlock = RegistrationFormFieldAccess::canUnlockLockedFields(Auth::user());

        return [
            Section::make('Paramètres généraux')
                ->schema([
                    TextInput::make('name')
                        ->label('Nom')
                        ->required()
                        ->maxLength(160),
                    Select::make('church_event_id')
                        ->label('Événement')
                        ->options(fn ($record): array => ChurchEvent::query()
                            ->where('type', 'retraite')
                            ->when($record === null, fn ($query) => $query->whereDoesntHave('registrationFormConfigSet'))
                            ->orderByDesc('start_at')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->nullable()
                        ->disabled(fn ($record): bool => $record !== null)
                        ->helperText(fn ($record): string => $record !== null
                            ? 'Une fois créé, l’événement n’est plus modifiable.'
                            : 'Laissez vide pour le modèle par défaut, ou choisissez une retraite sans configuration existante.'),
                    Select::make('source_config_set_id')
                        ->label('Reconduire depuis')
                        ->options(fn (): array => self::sourceConfigSetOptions())
                        ->searchable()
                        ->nullable()
                        ->visible(fn ($record): bool => $record === null)
                        ->helperText('Copie champs, ordre, libellés et blocs/paiement d’une configuration existante. Vous pourrez modifier avant d’appliquer au formulaire public.'),
                    Toggle::make('is_published')
                        ->label('Publié')
                        ->helperText('Seules les configurations publiées impactent le formulaire public.')
                        ->disabled(),
                ])
                ->description($canUnlock
                    ? 'Les champs critiques (identité) peuvent être déverrouillés ci-dessous pour les configurer comme les autres.'
                    : 'Les champs critiques (identité) sont verrouillés. Seul un administrateur peut les déverrouiller.')
                ->columns(2),
        ];
    }

    /**
     * Blocs hors registre : ouvrier, parent multi-enfants, moyens de paiement.
     */
    protected static function uiBlocksSection(): Section
    {
        return Section::make('Blocs et paiement')
            ->description('Activez ou masquez les blocs spéciaux et les moyens de paiement proposés à l’étape 5.')
            ->schema([
                Grid::make(2)
                    ->schema([
                        Toggle::make('ui_settings.worker_prefill.is_visible')
                            ->label('Bloc « Je suis ouvrier » (préremplissage)')
                            ->default(true)
                            ->live(),
                        Select::make('ui_settings.worker_prefill.position')
                            ->label('Position — ouvrier (étape identité)')
                            ->options(RegistrationFormUiSettings::blockPositionOptions())
                            ->default(RegistrationFormUiSettings::POSITION_BEFORE_FIELDS)
                            ->live(),
                        Toggle::make('ui_settings.parent_multi_child.is_visible')
                            ->label('Bloc parent/tuteur multi-enfants (OTP)')
                            ->default(true)
                            ->live(),
                        Select::make('ui_settings.parent_multi_child.position')
                            ->label('Position — parent (étape coordonnées)')
                            ->options(RegistrationFormUiSettings::blockPositionOptions())
                            ->default(RegistrationFormUiSettings::POSITION_BEFORE_FIELDS)
                            ->live(),
                    ]),
                Section::make('Moyens de paiement')
                    ->description('Visibilité et ordre d’affichage à l’étape paiement (aperçu et formulaire public).')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Toggle::make('ui_settings.payment_modes.mobile_money.is_visible')
                                    ->label('Mobile money')
                                    ->default(true)
                                    ->live(),
                                Toggle::make('ui_settings.payment_modes.card.is_visible')
                                    ->label('Carte bancaire')
                                    ->default(true)
                                    ->live(),
                                Toggle::make('ui_settings.payment_modes.cash.is_visible')
                                    ->label('Espèces (cash)')
                                    ->default(true)
                                    ->live(),
                            ]),
                        Repeater::make('payment_modes_order')
                            ->label('Ordre des moyens de paiement')
                            ->schema([
                                Hidden::make('mode'),
                            ])
                            ->itemLabel(fn (?array $state): ?string => isset($state['mode'])
                                ? (RegistrationFormUiSettings::PAYMENT_MODE_LABELS[$state['mode']] ?? $state['mode'])
                                : null)
                            ->reorderableWithDragAndDrop()
                            ->addable(false)
                            ->deletable(false)
                            ->live(),
                    ])
                    ->columnSpanFull(),
            ])
            ->columns(1)
            ->collapsible();
    }

    /**
     * Options pour reconduire une configuration depuis un jeu existant.
     *
     * @return array<int, string>
     */
    protected static function sourceConfigSetOptions(): array
    {
        return RegistrationFormConfigSet::query()
            ->with('event')
            ->orderByDesc('updated_at')
            ->get()
            ->mapWithKeys(function (RegistrationFormConfigSet $set): array {
                $label = $set->isDefaultTemplate()
                    ? "Modèle par défaut — {$set->name}"
                    : ($set->event?->name
                        ? "Événement : {$set->event->name}"
                        : $set->name);

                if ($set->is_published) {
                    $label .= ' · publié';
                }

                return [$set->id => $label];
            })
            ->all();
    }

    /**
     * Aperçu temps réel du formulaire public (selon le brouillon non enregistré).
     */
    protected static function previewSection(): Section
    {
        $canUnlock = RegistrationFormFieldAccess::canUnlockLockedFields(Auth::user());

        return Section::make('Aperçu en temps réel')
            ->description('Simule l’étape sélectionnée (identité, coordonnées, participation ou paiement), y compris les blocs ouvrier/parent et l’ordre des moyens de paiement. Mise à jour en direct sans enregistrer.')
            ->schema([
                Select::make('preview_step')
                    ->label('Étape à prévisualiser')
                    ->options(RegistrationFormPreviewBuilder::stepOptions())
                    ->default(0)
                    ->live(),
                Html::make(function (callable $get) use ($canUnlock): HtmlString {
                    $step = (int) ($get('preview_step') ?? 0);
                    $items = $get('items') ?? [];
                    $fieldOrder = $get('field_order') ?? [];
                    $uiSettings = RegistrationFormUiSettings::merge([
                        'worker_prefill' => $get('ui_settings.worker_prefill') ?? [],
                        'parent_multi_child' => $get('ui_settings.parent_multi_child') ?? [],
                        'payment_modes' => $get('ui_settings.payment_modes') ?? [],
                        'payment_modes_order' => RegistrationFormUiSettings::paymentModesOrderFromRepeater(
                            $get('payment_modes_order') ?? []
                        ),
                    ]);
                    $fields = in_array($step, [0, 1, 2], true)
                        ? RegistrationFormPreviewBuilder::fieldsForStep($items, $step, $canUnlock, $fieldOrder)
                        : [];
                    $uiBlocks = RegistrationFormPreviewBuilder::uiBlocksForStep($step, $uiSettings);

                    $previewHtml = view('filament.registration-form-configs.preview', [
                        'fields' => $fields,
                        'step' => $step,
                        'uiBlocks' => $uiBlocks,
                    ])->render();

                    $publicUrl = route('retraite.inscription');
                    $linkHtml = '<p class="mt-3 text-sm opacity-80">'
                        .'<a href="'.e($publicUrl).'" target="_blank" rel="noopener" class="text-primary-600 hover:underline font-medium">'
                        .'Ouvrir le formulaire public dans un nouvel onglet</a>'
                        .' · les changements nécessitent « Appliquer au formulaire ».</p>';

                    return new HtmlString($previewHtml.$linkHtml);
                }),
            ])
            ->columnSpanFull()
            ->collapsible();
    }

    /**
     * Réordonnancement par glisser-déposer, par étape du formulaire public.
     */
    protected static function fieldOrderSection(): Section
    {
        $repeaters = [];

        foreach (RegistrationFieldRegistry::groupedByStep() as $step => $group) {
            $stepIndex = (int) $step;

            $repeaters[] = Repeater::make("field_order.{$stepIndex}")
                ->label($group['label'])
                ->schema([
                    Hidden::make('field_key'),
                ])
                ->itemLabel(function (?array $state): ?string {
                    $fieldKey = $state['field_key'] ?? null;

                    if (! is_string($fieldKey) || $fieldKey === '') {
                        return null;
                    }

                    return RegistrationFieldRegistry::find($fieldKey)?->label() ?? $fieldKey;
                })
                ->reorderableWithDragAndDrop()
                ->addable(false)
                ->deletable(false)
                ->live();
        }

        return Section::make('Ordre des champs')
            ->description('Glissez-déposez pour changer l’ordre d’affichage sur le formulaire public et le récapitulatif. L’aperçu ci-dessus se met à jour en direct.')
            ->schema($repeaters)
            ->columnSpanFull()
            ->collapsible();
    }

    /**
     * Sections par étape du formulaire public.
     *
     * @return array<int, Section>
     */
    protected static function fieldSections(): array
    {
        $canUnlock = RegistrationFormFieldAccess::canUnlockLockedFields(Auth::user());
        $sections = [];

        foreach (RegistrationFieldRegistry::groupedByStep() as $group) {
            $rows = [];

            foreach ($group['fields'] as $definition) {
                $fieldKey = $definition->key->value;
                $isRegistryLocked = $definition->isLocked;

                $rows[] = Section::make($definition->label())
                    ->description($isRegistryLocked
                        ? 'Champ critique · '.$definition->type->label()
                        : $definition->type->label())
                    ->schema([
                        Grid::make(5)
                            ->schema(array_filter([
                                $isRegistryLocked && $canUnlock
                                    ? Toggle::make("items.{$fieldKey}.is_admin_unlocked")
                                        ->label('Déverrouiller')
                                        ->helperText('Réservé admin : rend ce champ configurable.')
                                        ->live()
                                    : null,
                                Toggle::make("items.{$fieldKey}.is_visible")
                                    ->label('Visible')
                                    ->disabled(fn (callable $get): bool => $isRegistryLocked
                                        && (! $canUnlock || ! (bool) $get("items.{$fieldKey}.is_admin_unlocked")))
                                    ->live(),
                                Toggle::make("items.{$fieldKey}.is_required")
                                    ->label('Obligatoire')
                                    ->disabled(fn (callable $get): bool => $isRegistryLocked
                                        && (! $canUnlock || ! (bool) $get("items.{$fieldKey}.is_admin_unlocked")))
                                    ->hidden(fn (callable $get): bool => ! (bool) $get("items.{$fieldKey}.is_visible")),
                                Select::make("items.{$fieldKey}.column_span")
                                    ->label('Largeur')
                                    ->options(collect(RegistrationFormColumnSpan::cases())
                                        ->mapWithKeys(fn (RegistrationFormColumnSpan $span): array => [
                                            $span->value => $span->label(),
                                        ])
                                        ->all())
                                    ->disabled(fn (callable $get): bool => $isRegistryLocked
                                        && (! $canUnlock || ! (bool) $get("items.{$fieldKey}.is_admin_unlocked")))
                                    ->hidden(fn (callable $get): bool => ! (bool) $get("items.{$fieldKey}.is_visible"))
                                    ->live(),
                            ])),
                        Grid::make(2)
                            ->schema([
                                TextInput::make("items.{$fieldKey}.label_override")
                                    ->label('Libellé personnalisé')
                                    ->placeholder($definition->label())
                                    ->maxLength(200)
                                    ->live(debounce: 400)
                                    ->helperText('Laissez vide pour conserver le libellé par défaut.'),
                                Textarea::make("items.{$fieldKey}.helper_text_override")
                                    ->label('Texte d\'aide sous le champ')
                                    ->rows(2)
                                    ->maxLength(500)
                                    ->live(debounce: 400)
                                    ->helperText('Affiché sous le champ sur le formulaire public (si renseigné).'),
                            ]),
                    ])
                    ->compact()
                    ->columnSpanFull();
            }

            $sections[] = Section::make($group['label'])
                ->description('Visibilité, obligation, largeur, libellés et textes d\'aide.')
                ->schema($rows)
                ->columnSpanFull()
                ->collapsed();
        }

        return $sections;
    }
}
