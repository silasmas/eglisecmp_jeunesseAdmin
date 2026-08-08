<?php

namespace App\Filament\Support;

use App\Jobs\SendSmsCampaignJob;
use App\Models\RetreatParticipant;
use App\Models\SmsTemplate;
use App\Services\KeccelSmsService;
use App\Services\Sms\SmsTemplateRenderer;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Actions Filament pour envoyer un SMS depuis la ressource Participants (unitaire / bulk).
 */
class RetreatParticipantSmsFilamentAction
{
    /**
     * Action ligne : envoyer un SMS à un participant.
     *
     * @param  string  $name  Identifiant Filament
     */
    public static function make(string $name = 'envoyer_sms'): Action
    {
        return Action::make($name)
            ->label('Envoyer SMS')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->color('primary')
            ->visible(fn (RetreatParticipant $record): bool => filled($record->telephone))
            ->modalHeading('Envoyer un SMS au participant')
            ->modalDescription('Utilise Keccel. Préférez un message court (1 segment) avec {{lien_billet}} seul.')
            ->fillForm(fn (RetreatParticipant $record): array => [
                'use_free_body' => false,
                'sms_template_id' => SmsTemplate::query()->active()->orderBy('name')->value('id'),
                'body' => (string) (SmsTemplate::query()->active()->orderBy('name')->value('body') ?? ''),
            ])
            ->form(fn (RetreatParticipant $record): array => self::formSchema($record))
            ->action(function (RetreatParticipant $record, array $data): void {
                self::sendToParticipants(collect([$record]), (string) ($data['body'] ?? ''));
            });
    }

    /**
     * Action bulk : campagne sur la sélection.
     *
     * @param  string  $name  Identifiant Filament
     */
    public static function makeBulk(string $name = 'envoyer_sms_bulk'): BulkAction
    {
        return BulkAction::make($name)
            ->label('Envoyer SMS')
            ->icon('heroicon-o-paper-airplane')
            ->modalHeading('Envoyer un SMS à la sélection')
            ->modalDescription('Les participants sans téléphone seront ignorés. Message mis en file d’attente.')
            ->fillForm(fn (): array => [
                'use_free_body' => false,
                'sms_template_id' => SmsTemplate::query()->active()->orderBy('name')->value('id'),
                'body' => (string) (SmsTemplate::query()->active()->orderBy('name')->value('body') ?? ''),
            ])
            ->form(fn (): array => self::formSchema(null))
            ->action(function (Collection $records, array $data): void {
                self::sendToParticipants($records, (string) ($data['body'] ?? ''));
            })
            ->deselectRecordsAfterCompletion();
    }

    /**
     * @param  RetreatParticipant|null  $previewParticipant  Participant pour l’aperçu (null en bulk)
     * @return array<int, mixed>
     */
    protected static function formSchema(?RetreatParticipant $previewParticipant): array
    {
        $renderer = app(SmsTemplateRenderer::class);

        return [
            Toggle::make('use_free_body')
                ->label('Corps libre (ignorer le modèle)')
                ->helperText('Cochez pour rédiger un message ponctuel sans choisir de modèle enregistré.')
                ->live(),
            Select::make('sms_template_id')
                ->label('Modèle SMS')
                ->helperText('Choisissez un modèle actif (Notifications → Modèles SMS). Le corps se charge automatiquement.')
                ->options(fn (): array => SmsTemplate::query()->active()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->live()
                ->visible(fn (callable $get): bool => ! (bool) $get('use_free_body'))
                ->afterStateUpdated(function (?int $state, callable $set): void {
                    if (! $state) {
                        return;
                    }
                    $template = SmsTemplate::query()->find($state);
                    if ($template) {
                        $set('body', (string) $template->body);
                    }
                }),
            Textarea::make('body')
                ->label('Corps du SMS')
                ->rows(4)
                ->required()
                ->live(debounce: 400)
                ->helperText('Variables : {{prenom}}, {{evenement}}, {{lien_billet}} (/b/…), {{lien_acces}} (/a/… = contrôle entrée), {{lien_inscription}} (/i). Un seul lien par SMS de préférence.'),
            Placeholder::make('preview')
                ->label('Aperçu')
                ->content(function (callable $get) use ($renderer, $previewParticipant): string {
                    $body = (string) ($get('body') ?? '');
                    $preview = $renderer->preview($body, $previewParticipant);
                    $lines = [
                        $preview['text'] !== '' ? $preview['text'] : '(vide)',
                        '',
                        $preview['character_count'].' car. — '.$preview['segments'].' segment(s) — '
                            .($preview['encoding'] === 'gsm' ? 'GSM-7' : 'Unicode'),
                    ];
                    foreach ($preview['warnings'] as $warning) {
                        $lines[] = '⚠ '.$warning;
                    }

                    return implode("\n", $lines);
                }),
        ];
    }

    /**
     * @param  iterable<int, mixed>  $participants  Participants ciblés
     * @param  string  $body  Corps avec variables
     */
    protected static function sendToParticipants(iterable $participants, string $body): void
    {
        $body = trim($body);
        if ($body === '') {
            Notification::make()->title('Corps SMS vide')->danger()->send();

            return;
        }

        $sms = app(KeccelSmsService::class);
        $recipients = [];

        foreach ($participants as $participant) {
            if (! $participant instanceof RetreatParticipant) {
                continue;
            }
            $phone = $sms->normalizePhone((string) ($participant->telephone ?? ''));
            if ($phone === '') {
                continue;
            }
            $recipients[$phone] = [
                'type' => 'participant',
                'phone' => $phone,
                'participant_id' => (int) $participant->id,
            ];
        }

        $list = array_values($recipients);
        if ($list === []) {
            Notification::make()
                ->title('Aucun numéro valide')
                ->body('Les participants sélectionnés n’ont pas de téléphone exploitable.')
                ->warning()
                ->send();

            return;
        }

        $userId = (int) Auth::id();

        try {
            if (count($list) === 1) {
                $only = $list[0];
                $participant = RetreatParticipant::query()->find($only['participant_id']);
                $message = app(SmsTemplateRenderer::class)->render($body, $participant);
                $sms->send($only['phone'], $message, 'participant_sms');

                Notification::make()
                    ->title('SMS envoyé')
                    ->body('Message transmis à Keccel pour '.$only['phone'].'.')
                    ->success()
                    ->send();

                return;
            }

            SendSmsCampaignJob::dispatch($userId, $body, $list);

            Notification::make()
                ->title('Campagne SMS mise en file')
                ->body(count($list).' destinataire(s) — notification à la fin de l’envoi.')
                ->success()
                ->send();
        } catch (Throwable $e) {
            report($e);

            Notification::make()
                ->title('Échec d’envoi SMS')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
