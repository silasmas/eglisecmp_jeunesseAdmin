<?php

namespace App\Filament\Resources\SmsTemplates\Schemas;

use App\Models\RetreatParticipant;
use App\Services\Sms\SmsTemplateRenderer;
use App\Support\RetreatActiveEventScope;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;

/**
 * Formulaire modèle SMS : variables cliquables, aperçu live, compteur segments.
 */
class SmsTemplateForm
{
    /**
     * @param  Schema  $schema  Schéma Filament
     */
    public static function configure(Schema $schema): Schema
    {
        $renderer = app(SmsTemplateRenderer::class);
        $variableActions = [];

        foreach ($renderer->availableVariables() as $key => $label) {
            $variableActions[] = Action::make('insert_'.$key)
                ->label('{{'.$key.'}}')
                ->color('gray')
                ->size(Size::ExtraSmall)
                ->action(function (Set $set, Get $get) use ($key): void {
                    $body = (string) ($get('body') ?? '');
                    $set('body', $body.'{{'.$key.'}}');
                });
        }

        return $schema->components([
            Section::make('Modèle')
                ->schema([
                    TextInput::make('name')
                        ->label('Nom')
                        ->required()
                        ->maxLength(120)
                        ->live(onBlur: true),
                    TextInput::make('slug')
                        ->label('Slug')
                        ->helperText('Laissé vide : généré depuis le nom.')
                        ->maxLength(120)
                        ->unique(ignoreRecord: true),
                    TextInput::make('description')
                        ->label('Description')
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Toggle::make('is_active')
                        ->label('Actif')
                        ->default(true),
                    Textarea::make('body')
                        ->label('Corps du SMS')
                        ->required()
                        ->rows(5)
                        ->live(debounce: 400)
                        ->columnSpanFull()
                        ->helperText('Cliquez une variable ci-dessous pour l’insérer.'),
                    Actions::make($variableActions)
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('Aperçu en direct')
                ->schema([
                    Select::make('preview_participant_id')
                        ->label('Participant d’exemple')
                        ->searchable()
                        ->preload()
                        ->dehydrated(false)
                        ->live()
                        ->options(function (): array {
                            return RetreatActiveEventScope::applyToParticipants(
                                RetreatParticipant::query()
                            )
                                ->orderByDesc('id')
                                ->limit(80)
                                ->get()
                                ->mapWithKeys(fn (RetreatParticipant $p): array => [
                                    $p->id => trim(($p->prenom ?? '').' '.($p->nom ?? '')).' — '.($p->telephone ?? '—'),
                                ])
                                ->all();
                        })
                        ->placeholder('Données fictives si vide'),
                    Placeholder::make('sms_preview')
                        ->label('Message rendu')
                        ->content(function (Get $get) use ($renderer): string {
                            $preview = self::buildPreview($get, $renderer);
                            $text = $preview['text'] !== '' ? $preview['text'] : '(vide)';

                            return $text;
                        })
                        ->columnSpanFull(),
                    Placeholder::make('sms_stats')
                        ->label('Compteur')
                        ->content(function (Get $get) use ($renderer): string {
                            $preview = self::buildPreview($get, $renderer);
                            $encoding = $preview['encoding'] === 'gsm' ? 'GSM-7' : 'Unicode (UCS-2)';
                            $lines = [
                                $preview['character_count'].' caractère(s)',
                                $preview['segments'].' segment(s)',
                                'Encodage : '.$encoding,
                            ];

                            foreach ($preview['warnings'] as $warning) {
                                $lines[] = '⚠ '.$warning;
                            }

                            return implode("\n", $lines);
                        })
                        ->columnSpanFull(),
                ]),
        ]);
    }

    /**
     * @param  Get  $get  Accès champs formulaire
     * @param  SmsTemplateRenderer  $renderer  Moteur de rendu
     * @return array{text: string, encoding: string, character_count: int, segments: int, billet_unavailable: bool, warnings: list<string>}
     */
    protected static function buildPreview(Get $get, SmsTemplateRenderer $renderer): array
    {
        $body = (string) ($get('body') ?? '');
        $participantId = $get('preview_participant_id');
        $participant = null;

        if (filled($participantId)) {
            $participant = RetreatParticipant::query()->find((int) $participantId);
        }

        if ($participant === null) {
            return $renderer->preview($body, null, [
                'prenom' => 'Jean',
                'nom' => 'Mbala',
                'postnom' => 'K.',
                'nom_complet' => 'Jean Mbala K.',
                'telephone' => '243890000000',
                'email' => 'exemple@eglisecmp.com',
                'atelier' => 'Atelier n°1',
                'chambre' => 'Chambre A',
                'evenement' => 'Grande Retraite des Jeunes',
                'lien_billet' => 'https://eglisecmp.com/exemple-billet',
                'lien_justificatif' => 'https://eglisecmp.com/exemple-justificatif',
                'lien_acces' => 'https://eglisecmp.com/exemple-acces',
            ]);
        }

        return $renderer->preview($body, $participant);
    }
}
