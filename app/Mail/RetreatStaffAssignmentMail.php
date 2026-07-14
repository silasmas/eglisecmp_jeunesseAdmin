<?php

namespace App\Mail;

use App\Models\RetreatAtelier;
use App\Models\RetreatChambre;
use App\Models\User;
use App\Support\CmpMailEnvelope;
use App\Support\RetreatMailUrl;
use App\Support\RetreatStaffAssignmentPresentation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail envoyé à un ouvrier lorsqu'il est nommé responsable ou adjoint d'atelier/chambre.
 */
class RetreatStaffAssignmentMail extends Mailable
{
  use Queueable, SerializesModels;

  /**
   * @param 'atelier'|'chambre' $assignmentType Type d'affectation
   * @param 'responsable'|'adjoint' $roleLabel Rôle attribué
   * @param RetreatAtelier|RetreatChambre $assignment Cible (atelier ou chambre)
   * @param string $plainPassword Mot de passe dashboard en clair
   * @param string $dashboardRole Nom du rôle Spatie dans le tableau de bord
   */
  public function __construct(
    public User $user,
    public string $assignmentType,
    public string $roleLabel,
    public RetreatAtelier|RetreatChambre $assignment,
    public string $plainPassword,
    public string $dashboardRole,
  ) {}

  /**
   * @return Envelope Enveloppe de l'e-mail
   */
  public function envelope(): Envelope
  {
    $label = $this->assignmentType === 'atelier'
      ? __('retraite.mail_staff_assignment_atelier', ['numero' => $this->assignment->numero])
      : __('retraite.mail_staff_assignment_chambre', ['nom' => $this->assignment->nom]);

    return CmpMailEnvelope::make(
      __('retraite.mail_staff_assignment_subject', [
        'role' => $this->roleLabelForMail(),
        'target' => $label,
        'year' => RetreatStaffAssignmentPresentation::retreatYear($this->assignment),
      ])
    );
  }

  /**
   * @return Content Contenu Markdown de l'e-mail
   */
  public function content(): Content
  {
    return new Content(
      markdown: 'emails.retreat-staff-assignment',
      with: [
        'user' => $this->user,
        'assignmentType' => $this->assignmentType,
        'roleLabel' => $this->roleLabelForMail(),
        'assignment' => $this->assignment,
        'retreatTitle' => RetreatStaffAssignmentPresentation::retreatTitle($this->assignment),
        'retreatYear' => RetreatStaffAssignmentPresentation::retreatYear($this->assignment),
        'dashboardRoleLabel' => RetreatStaffAssignmentPresentation::dashboardRoleLabel($this->dashboardRole),
        'loginEmail' => $this->user->email,
        'plainPassword' => $this->plainPassword,
        'adminUrl' => RetreatMailUrl::admin(),
      ],
    );
  }

  /**
   * Libellé lisible du rôle pour l'e-mail.
   *
   * @return string
   */
  protected function roleLabelForMail(): string
  {
    return match ($this->roleLabel) {
      'adjoint' => 'Adjoint',
      default => 'Responsable',
    };
  }
}
