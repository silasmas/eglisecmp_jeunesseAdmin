<?php

namespace App\Observers;

use App\Models\RetreatChambre;
use App\Models\User;
use App\Services\RetreatStaffAssignmentNotifier;

/**
 * Envoie un e-mail lorsqu'un ouvrier est nommé responsable ou adjoint de chambre.
 */
class RetreatChambreObserver
{
  public function __construct(
    protected RetreatStaffAssignmentNotifier $notifier,
  ) {}

  public function saved(RetreatChambre $chambre): void
  {
    $userChanged = $chambre->wasChanged('responsable_user_id') && $chambre->responsable_user_id;
    $roleChanged = $chambre->wasChanged('role_on_chambre') && $chambre->responsable_user_id;

    if (! $userChanged && ! $roleChanged) {
      return;
    }

    $role = $chambre->role_on_chambre === 'adjoint' ? 'adjoint' : 'responsable';

    if ($chambre->role_on_chambre === 'assistant') {
      return;
    }

    $user = User::query()->find($chambre->responsable_user_id);
    if ($user) {
      $this->notifier->notifyChambre($user, $role, $chambre);
    }
  }
}
