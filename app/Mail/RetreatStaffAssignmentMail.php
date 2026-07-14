<?php

namespace App\Mail;

use App\Models\RetreatAtelier;
use App\Models\RetreatChambre;
use App\Models\User;
use App\Support\CmpMailEnvelope;
use App\Support\RetreatMailUrl;
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
   */
  public function __construct(
    public User $user,
    public string $assignmentType,
    public string $roleLabel,
    public RetreatAtelier|RetreatChambre $assignment,
  ) {}

  public function envelope(): Envelope
  {
    $label = $this->assignmentType === 'atelier'
      ? __('retraite.mail_staff_assignment_atelier', ['numero' => $this->assignment->numero])
      : __('retraite.mail_staff_assignment_chambre', ['nom' => $this->assignment->nom]);

    return CmpMailEnvelope::make(
      __('retraite.mail_staff_assignment_subject', [
        'role' => $this->roleLabelForMail(),
        'target' => $label,
      ])
    );
  }

  public function content(): Content
  {
    return new Content(
      markdown: 'emails.retreat-staff-assignment',
      with: [
        'user' => $this->user,
        'assignmentType' => $this->assignmentType,
        'roleLabel' => $this->roleLabelForMail(),
        'assignment' => $this->assignment,
        'adminUrl' => RetreatMailUrl::admin(),
      ],
    );
  }

  /**
   * Libellé lisible du rôle pour l'e-mail.
   */
  protected function roleLabelForMail(): string
  {
    return match ($this->roleLabel) {
      'adjoint' => 'Adjoint',
      default => 'Responsable',
    };
  }
}
