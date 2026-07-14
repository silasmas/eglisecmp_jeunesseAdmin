<?php

namespace App\Mail;

use App\Models\User;
use App\Support\CmpMailEnvelope;
use App\Support\RetreatMailUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail contenant les identifiants d'accès au tableau de bord pour un encadreur.
 */
class RetreatStaffAccessCredentialsMail extends Mailable
{
  use Queueable, SerializesModels;

  /**
   * @param User $user Utilisateur concerné
   * @param string $plainPassword Mot de passe en clair (envoi unique)
   * @param string $dashboardRole Nom du rôle Spatie assigné
   * @param string|null $assignmentSummary Résumé de l'affectation chambre/atelier
   * @param string|null $metierRoleLabel Rôle métier sur l'affectation
   */
  public function __construct(
    public User $user,
    public string $plainPassword,
    public string $dashboardRole,
    public ?string $assignmentSummary = null,
    public ?string $metierRoleLabel = null,
  ) {}

  /**
   * @return Envelope Enveloppe de l'e-mail
   */
  public function envelope(): Envelope
  {
    return CmpMailEnvelope::make(__('retraite.mail_staff_access_subject'));
  }

  /**
   * @return Content Contenu Markdown de l'e-mail
   */
  public function content(): Content
  {
    return new Content(
      markdown: 'emails.retreat-staff-access-credentials',
      with: [
        'user' => $this->user,
        'plainPassword' => $this->plainPassword,
        'dashboardRoleLabel' => $this->dashboardRoleLabel(),
        'assignmentSummary' => $this->assignmentSummary,
        'metierRoleLabel' => $this->metierRoleLabelForMail(),
        'adminUrl' => RetreatMailUrl::admin(),
        'loginEmail' => $this->user->email,
      ],
    );
  }

  /**
   * Libellé lisible du rôle dashboard.
   *
   * @return string
   */
  protected function dashboardRoleLabel(): string
  {
    return match ($this->dashboardRole) {
      'super_admin' => __('retraite.mail_staff_access_role_super_admin'),
      'panel_user' => __('retraite.mail_staff_access_role_panel_user'),
      'ouvrier' => __('retraite.mail_staff_access_role_ouvrier'),
      default => $this->dashboardRole,
    };
  }

  /**
   * Libellé lisible du rôle métier sur l'affectation.
   *
   * @return string|null
   */
  protected function metierRoleLabelForMail(): ?string
  {
    if ($this->metierRoleLabel === null) {
      return null;
    }

    return match ($this->metierRoleLabel) {
      'adjoint' => 'Adjoint',
      'assistant' => 'Assistant',
      default => 'Responsable',
    };
  }
}
