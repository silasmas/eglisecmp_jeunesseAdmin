<?php

namespace App\Services;

use App\Models\RetreatNotification;
use App\Models\RetreatParticipant;
use App\Models\User;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Database\Eloquent\Model;

/**
 * Alimente la cloche Filament (table `notifications`) et la table métier `retreat_notification`.
 */
class PanelNotificationDispatcher
{
    /**
     * @param  iterable<int, User|null>  $recipients
     */
    public function notify(
        iterable $recipients,
        string $title,
        string $message,
        ?string $link = null,
        string $category = 'info',
        ?Model $subject = null,
    ): void {
        foreach ($recipients as $user) {
            if (! $user instanceof User) {
                continue;
            }

            $filament = FilamentNotification::make()
                ->title($title)
                ->body($message)
                ->icon(match ($category) {
                    'payment' => 'heroicon-o-banknotes',
                    'participant' => 'heroicon-o-user-group',
                    'warning' => 'heroicon-o-exclamation-triangle',
                    'success' => 'heroicon-o-check-circle',
                    default => 'heroicon-o-bell',
                })
                ->iconColor(match ($category) {
                    'warning' => 'warning',
                    'success' => 'success',
                    'payment' => 'primary',
                    default => 'gray',
                });

            // La classe Filament\DatabaseNotification implémente ShouldQueue : notifyNow évite
            // d'attendre un worker pour persister dans `notifications`.
            $user->notifyNow($filament->toDatabase());

            $laravel = $user->notifications()->latest()->first();

            RetreatNotification::query()->create([
                'title' => $title,
                'message' => $message,
                'link' => $link,
                'is_read' => false,
                'user_id' => $user->id,
                'is_active' => true,
                'category' => $category,
                'subject_type' => $subject ? $subject->getMorphClass() : null,
                'subject_id' => $subject?->getKey(),
                'laravel_notification_id' => $laravel?->getKey(),
            ]);
        }
    }

    /**
     * Utilisateurs à prévenir pour le suivi d’un participant (ouvriers liés).
     *
     * @return array<int, User|null>
     */
    public function participantStakeholders(RetreatParticipant $participant): array
    {
        return array_filter([
            $participant->owner_id ? User::query()->find($participant->owner_id) : null,
            $participant->user_id ? User::query()->find($participant->user_id) : null,
        ]);
    }
}
