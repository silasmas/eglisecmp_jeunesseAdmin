<?php

namespace App\Observers;

use App\Models\RetreatAtelier;
use App\Models\User;
use App\Services\RetreatStaffAssignmentNotifier;

/**
 * Envoie un e-mail lorsqu'un ouvrier est nommé responsable ou adjoint d'atelier.
 */
class RetreatAtelierObserver
{
  public function __construct(
    protected RetreatStaffAssignmentNotifier $notifier,
  ) {}

  public function saved(RetreatAtelier $atelier): void
  {
    if ($atelier->wasChanged('responsable_user_id') && $atelier->responsable_user_id) {
      $user = User::query()->find($atelier->responsable_user_id);
      if ($user) {
        $this->notifier->notifyAtelier($user, 'responsable', $atelier);
      }
    }

    if ($atelier->wasChanged('adjoint_user_id') && $atelier->adjoint_user_id) {
      $user = User::query()->find($atelier->adjoint_user_id);
      if ($user) {
        $this->notifier->notifyAtelier($user, 'adjoint', $atelier);
      }
    }
  }
}
