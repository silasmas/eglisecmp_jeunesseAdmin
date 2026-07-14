<?php

namespace App\Services;

use App\Mail\RetreatStaffAssignmentMail;
use App\Models\RetreatAtelier;
use App\Models\RetreatChambre;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Notifie par e-mail les ouvriers affectés responsable ou adjoint d'atelier/chambre.
 */
class RetreatStaffAssignmentNotifier
{
  public function __construct(
    protected UserDashboardAccessProvisioner $accessProvisioner,
  ) {}

  /**
   * Envoie la notification après affectation atelier (responsable ou adjoint).
   *
   * @param User $user Utilisateur concerné
   * @param 'responsable'|'adjoint' $role Rôle attribué
   * @param RetreatAtelier $atelier Atelier concerné
   */
  public function notifyAtelier(User $user, string $role, RetreatAtelier $atelier): void
  {
    $this->send($user, 'atelier', $role, $atelier);
  }

  /**
   * Envoie la notification après affectation chambre.
   *
   * @param User $user Utilisateur concerné
   * @param 'responsable'|'adjoint' $role Rôle attribué
   * @param RetreatChambre $chambre Chambre concernée
   */
  public function notifyChambre(User $user, string $role, RetreatChambre $chambre): void
  {
    $this->send($user, 'chambre', $role, $chambre);
  }

  /**
   * @param 'atelier'|'chambre' $type
   * @param 'responsable'|'adjoint' $role
   * @param RetreatAtelier|RetreatChambre $assignment
   */
  protected function send(User $user, string $type, string $role, RetreatAtelier|RetreatChambre $assignment): void
  {
    if (! filled($user->email)) {
      return;
    }

    if (! in_array($role, ['responsable', 'adjoint'], true)) {
      return;
    }

    try {
      $credentials = $this->accessProvisioner->provisionForStaffAssignment($user);

      Mail::to($user->email)->send(new RetreatStaffAssignmentMail(
        user: $user,
        assignmentType: $type,
        roleLabel: $role,
        assignment: $assignment,
        plainPassword: $credentials['plainPassword'],
        dashboardRole: $credentials['dashboardRole'],
      ));
    } catch (\Throwable $e) {
      Log::warning('E-mail affectation retraite non envoyé', [
        'user_id' => $user->id,
        'type' => $type,
        'role' => $role,
        'assignment_id' => $assignment->getKey(),
        'error' => $e->getMessage(),
      ]);
      report($e);
    }
  }
}
