<?php

namespace App\Filament\Pages;

use App\Jobs\SendSmsCampaignJob;
use App\Models\RetreatAtelier;
use App\Models\RetreatChambre;
use App\Models\RetreatParticipant;
use App\Models\SmsTemplate;
use App\Services\KeccelSmsService;
use App\Services\Sms\SmsTemplateRenderer;
use App\Support\RetreatActiveEventScope;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Page admin : préparer et lancer une campagne SMS (participants filtrés + numéros manuels).
 */
class SendSmsCampaign extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-paper-airplane';

    protected static ?string $navigationLabel = 'Envoyer SMS';

    protected static ?string $title = 'Envoyer une campagne SMS';

    protected static string|UnitEnum|null $navigationGroup = 'Notifications';

    protected static ?int $navigationSort = 13;

    protected static ?string $slug = 'envoyer-sms';

    public ?int $sms_template_id = null;

    public string $body = '';

    public bool $use_free_body = false;

    public ?string $search = null;

    public ?int $atelier_id = null;

    public ?int $chambre_id = null;

    public ?string $paiement_filter = null;

    /** @var list<string> */
    public array $selected_participant_ids = [];

    public bool $select_all_filtered = false;

    public string $manual_phones = '';

    /**
     * Charge le corps depuis le premier modèle actif si disponible.
     */
    public function mount(): void
    {
        $first = SmsTemplate::query()->active()->orderBy('name')->first();
        if ($first) {
            $this->sms_template_id = $first->id;
            $this->body = (string) $first->body;
        }
    }

    /**
     * @param  Schema  $schema  Schéma Filament
     */
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Message')
                ->schema([
                    Toggle::make('use_free_body')
                        ->label('Corps libre (ignorer le modèle)')
                        ->live(),
                    Select::make('sms_template_id')
                        ->label('Modèle SMS')
                        ->options(fn (): array => SmsTemplate::query()
                            ->active()
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->live()
                        ->visible(fn (): bool => ! $this->use_free_body)
                        ->afterStateUpdated(function (?int $state): void {
                            if (! $state) {
                                return;
                            }
                            $template = SmsTemplate::query()->find($state);
                            if ($template) {
                                $this->body = (string) $template->body;
                            }
                        }),
                    Textarea::make('body')
                        ->label('Corps du SMS')
                        ->rows(4)
                        ->required()
                        ->live(debounce: 400)
                        ->helperText('Variables : {{prenom}}, {{nom}}, {{lien_billet}}, {{evenement}}, etc.'),
                ]),
            Section::make('Filtres participants')
                ->description('Événements opérationnels uniquement.')
                ->schema([
                    TextInput::make('search')
                        ->label('Recherche')
                        ->placeholder('Prénom, nom ou téléphone…')
                        ->live(debounce: 400),
                    Select::make('atelier_id')
                        ->label('Atelier')
                        ->options(fn (): array => RetreatActiveEventScope::applyToAteliers(RetreatAtelier::query())
                            ->orderBy('numero')
                            ->get()
                            ->mapWithKeys(fn (RetreatAtelier $a): array => [
                                $a->id => 'Atelier n°'.$a->numero,
                            ])
                            ->all())
                        ->searchable()
                        ->live()
                        ->nullable(),
                    Select::make('chambre_id')
                        ->label('Chambre')
                        ->options(fn (): array => RetreatActiveEventScope::applyToChambres(RetreatChambre::query())
                            ->orderBy('nom')
                            ->pluck('nom', 'id')
                            ->all())
                        ->searchable()
                        ->live()
                        ->nullable(),
                    Select::make('paiement_filter')
                        ->label('Paiement')
                        ->options([
                            'valide' => 'Paiement validé',
                            'non_valide' => 'Paiement non validé',
                        ])
                        ->live()
                        ->nullable(),
                    Toggle::make('select_all_filtered')
                        ->label('Tous les participants filtrés')
                        ->helperText(fn (): string => $this->filteredParticipantsQuery()->count().' participant(s) correspondent aux filtres.')
                        ->live(),
                    CheckboxList::make('selected_participant_ids')
                        ->label('Sélection manuelle')
                        ->options(fn (): array => $this->filteredParticipantsQuery()
                            ->orderBy('prenom')
                            ->limit(150)
                            ->get()
                            ->mapWithKeys(fn (RetreatParticipant $p): array => [
                                (string) $p->id => trim(($p->prenom ?? '').' '.($p->nom ?? '')).' — '.($p->telephone ?? 'sans tél.'),
                            ])
                            ->all())
                        ->columns(1)
                        ->bulkToggleable()
                        ->searchable()
                        ->visible(fn (): bool => ! $this->select_all_filtered)
                        ->live(),
                ])
                ->columns(2),
            Section::make('Numéros manuels')
                ->schema([
                    Textarea::make('manual_phones')
                        ->label('Numéros (un par ligne ou séparés par virgule)')
                        ->rows(4)
                        ->live(debounce: 400)
                        ->helperText('Normalisés en 243… — pour non-inscrits : {{prenom}}/{{lien_billet}} vides, {{lien_inscription}} et {{evenement}} restent disponibles.'),
                ]),
            Section::make('Aperçu & confirmation')
                ->schema([
                    Placeholder::make('preview')
                        ->label('Aperçu (1er destinataire)')
                        ->content(fn (): string => $this->previewText()),
                    Placeholder::make('stats')
                        ->label('Compteur & volume')
                        ->content(fn (): string => $this->previewStats()),
                ])
                ->footerActions([
                    Action::make('dispatchCampaign')
                        ->label('Confirmer et envoyer')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalHeading('Confirmer l’envoi de la campagne')
                        ->modalDescription(fn (): string => $this->confirmationSummary())
                        ->action('dispatchCampaign'),
                ]),
        ]);
    }

    /**
     * Construit et met en file la campagne.
     */
    public function dispatchCampaign(): void
    {
        $body = trim($this->body);
        if ($body === '') {
            Notification::make()
                ->title('Corps SMS vide')
                ->danger()
                ->send();

            return;
        }

        $recipients = $this->buildRecipients();
        if ($recipients === []) {
            Notification::make()
                ->title('Aucun destinataire')
                ->body('Sélectionnez des participants ou saisissez des numéros.')
                ->warning()
                ->send();

            return;
        }

        $userId = (int) Auth::id();
        SendSmsCampaignJob::dispatch($userId, $body, $recipients);

        Notification::make()
            ->title('Campagne mise en file')
            ->body(count($recipients).' destinataire(s) — vous serez notifié à la fin.')
            ->success()
            ->send();
    }

    /**
     * @return Builder<RetreatParticipant>
     */
    protected function filteredParticipantsQuery(): Builder
    {
        $query = RetreatActiveEventScope::applyToParticipants(
            RetreatParticipant::query()->whereNotNull('telephone')->where('telephone', '!=', '')
        );

        if (filled($this->atelier_id)) {
            $query->where('atelier_id', $this->atelier_id);
        }

        if (filled($this->chambre_id)) {
            $query->where('chambre_id', $this->chambre_id);
        }

        if ($this->paiement_filter === 'valide') {
            $query->where('paiement_valide', true);
        } elseif ($this->paiement_filter === 'non_valide') {
            $query->where(function (Builder $q): void {
                $q->where('paiement_valide', false)->orWhereNull('paiement_valide');
            });
        }

        if (filled($this->search)) {
            $term = '%'.trim((string) $this->search).'%';
            $query->where(function (Builder $q) use ($term): void {
                $q->where('prenom', 'like', $term)
                    ->orWhere('nom', 'like', $term)
                    ->orWhere('postnom', 'like', $term)
                    ->orWhere('telephone', 'like', $term);
            });
        }

        return $query;
    }

    /**
     * @return list<array{type: string, phone: string, participant_id?: int|null}>
     */
    protected function buildRecipients(): array
    {
        $sms = app(KeccelSmsService::class);
        $byPhone = [];

        $participantIds = $this->select_all_filtered
            ? $this->filteredParticipantsQuery()->pluck('id')->all()
            : array_map('intval', $this->selected_participant_ids);

        if ($participantIds !== []) {
            RetreatParticipant::query()
                ->whereIn('id', $participantIds)
                ->get(['id', 'telephone'])
                ->each(function (RetreatParticipant $p) use ($sms, &$byPhone): void {
                    $phone = $sms->normalizePhone((string) $p->telephone);
                    if ($phone === '') {
                        return;
                    }
                    $byPhone[$phone] = [
                        'type' => 'participant',
                        'phone' => $phone,
                        'participant_id' => (int) $p->id,
                    ];
                });
        }

        foreach ($this->parseManualPhones() as $raw) {
            $phone = $sms->normalizePhone($raw);
            if ($phone === '' || isset($byPhone[$phone])) {
                continue;
            }
            $byPhone[$phone] = [
                'type' => 'manual',
                'phone' => $phone,
                'participant_id' => null,
            ];
        }

        return array_values($byPhone);
    }

    /**
     * @return list<string>
     */
    protected function parseManualPhones(): array
    {
        $chunks = preg_split('/[\s,;]+/', $this->manual_phones) ?: [];

        return array_values(array_filter(array_map('trim', $chunks)));
    }

    /**
     * Premier destinataire pour l’aperçu (participant prioritaire).
     *
     * @return array{0: ?RetreatParticipant, 1: ?string}
     */
    protected function firstPreviewTarget(): array
    {
        $recipients = $this->buildRecipients();
        if ($recipients === []) {
            return [null, null];
        }

        $first = $recipients[0];
        $participant = filled($first['participant_id'] ?? null)
            ? RetreatParticipant::query()->find((int) $first['participant_id'])
            : null;

        return [$participant, $first['phone'] ?? null];
    }

    protected function previewText(): string
    {
        [$participant] = $this->firstPreviewTarget();
        $preview = app(SmsTemplateRenderer::class)->preview($this->body, $participant);

        return $preview['text'] !== '' ? $preview['text'] : '(aperçu vide)';
    }

    protected function previewStats(): string
    {
        [$participant] = $this->firstPreviewTarget();
        $preview = app(SmsTemplateRenderer::class)->preview($this->body, $participant);
        $count = count($this->buildRecipients());
        $encoding = $preview['encoding'] === 'gsm' ? 'GSM-7' : 'Unicode';
        $estimated = $count * max(1, $preview['segments']);

        $lines = [
            "Destinataires : {$count}",
            "Caractères (aperçu) : {$preview['character_count']}",
            "Segments / SMS : {$preview['segments']} ({$encoding})",
            "Estimation totale segments : {$estimated}",
        ];

        foreach ($preview['warnings'] as $warning) {
            $lines[] = '⚠ '.$warning;
        }

        return implode("\n", $lines);
    }

    protected function confirmationSummary(): string
    {
        $count = count($this->buildRecipients());
        [$participant] = $this->firstPreviewTarget();
        $preview = app(SmsTemplateRenderer::class)->preview($this->body, $participant);
        $estimated = $count * max(1, $preview['segments']);

        return "Vous allez envoyer à {$count} destinataire(s) (~{$estimated} segment(s) SMS). Continuer ?";
    }
}
