<?php

namespace App\Filament\Support;

use App\Models\RetreatAtelier;
use App\Models\RetreatChambre;
use App\Models\User;
use App\Services\UserDashboardAccessProvisioner;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Spatie\Permission\Models\Role;

/**
 * Action Filament : renvoyer les identifiants dashboard à un responsable d'affectation.
 */
final class ResendStaffAccessCredentialsFilamentAction
{
  /**
   * @param string $name Identifiant de l'action Filament
   * @return Action Action configurée
   */
  public static function make(string $name = 'resend_staff_access'): Action
  {
    return Action::make($name)
      ->label('Renvoyer accès dashboard')
      ->icon('heroicon-o-envelope')
      ->color('info')
      ->visible(fn (mixed $record): bool => self::hasStaffWithEmail($record))
      ->modalHeading('Renvoyer les identifiants d\'accès')
      ->modalDescription(fn (mixed $record): string => self::modalDescription($record))
      ->modalSubmitActionLabel('Envoyer les identifiants')
      ->form(fn (mixed $record): array => self::formSchema($record))
      ->action(function (mixed $record, array $data): void {
        $staffTarget = (string) ($data['staff_target'] ?? 'responsable');
        $user = self::resolveStaffUser($record, $staffTarget);

        if ($user === null) {
          Notification::make()
            ->title('Responsable introuvable')
            ->danger()
            ->send();

          return;
        }

        $result = app(UserDashboardAccessProvisioner::class)->provisionAndNotify(
          user: $user,
          dashboardRole: (string) ($data['dashboard_role'] ?? 'ouvrier'),
          generatePassword: (bool) ($data['generate_password'] ?? true),
          manualPassword: filled($data['password'] ?? null) ? (string) $data['password'] : null,
          assignmentSummary: self::assignmentSummary($record),
          metierRoleLabel: self::metierRoleLabel($record, $staffTarget),
        );

        $notification = Notification::make()
          ->title($result['success'] ? 'Identifiants envoyés' : 'Échec envoi identifiants')
          ->body($result['message']);

        if ($result['success']) {
          $notification->success()->send();
        } else {
          $notification->danger()->send();
        }
      });
  }

  /**
   * @param mixed $record Enregistrement chambre ou atelier
   * @return array<int, \Filament\Forms\Components\Component>
   */
  protected static function formSchema(mixed $record): array
  {
    $schema = [];

    if ($record instanceof RetreatAtelier && filled($record->adjoint_user_id)) {
      $schema[] = Select::make('staff_target')
        ->label('Destinataire')
        ->options([
          'responsable' => self::staffOptionLabel('Responsable', $record->responsable),
          'adjoint' => self::staffOptionLabel('Adjoint', $record->adjoint),
        ])
        ->default('responsable')
        ->required()
        ->live();
    }

    $schema[] = Toggle::make('generate_password')
      ->label('Générer un mot de passe automatiquement')
      ->default(true)
      ->live();

    $schema[] = TextInput::make('password')
      ->label('Mot de passe')
      ->password()
      ->revealable()
      ->visible(fn (Get $get): bool => ! (bool) $get('generate_password'))
      ->required(fn (Get $get): bool => ! (bool) $get('generate_password'))
      ->minLength(8)
      ->helperText('Minimum 8 caractères. Il sera communiqué une seule fois par e-mail.');

    $schema[] = Select::make('dashboard_role')
      ->label('Rôle dans le tableau de bord')
      ->options(fn (): array => Role::query()
        ->where('guard_name', 'web')
        ->orderBy('name')
        ->pluck('name', 'name')
        ->all())
      ->default(fn (mixed $record): string => self::defaultDashboardRole($record))
      ->required()
      ->helperText('Contrôle les accès Filament et les permissions Shield.');

    return $schema;
  }

  /**
   * @param mixed $record Enregistrement chambre ou atelier
   * @return string Description modale
   */
  protected static function modalDescription(mixed $record): string
  {
    $user = self::resolveStaffUser($record);

    if ($user === null) {
      return 'Aucun responsable n\'est affecté à cet enregistrement.';
    }

    return sprintf(
      'Un e-mail contenant l\'adresse de connexion et le mot de passe sera envoyé à %s (%s).',
      $user->name,
      $user->email,
    );
  }

  /**
   * @param mixed $record Enregistrement chambre ou atelier
   * @return bool True si au moins un encadreur avec e-mail est affecté
   */
  public static function hasStaffWithEmail(mixed $record): bool
  {
    if ($record instanceof RetreatAtelier) {
      $record->loadMissing(['responsable', 'adjoint']);

      return filled($record->responsable?->email) || filled($record->adjoint?->email);
    }

    $user = self::resolveStaffUser($record);

    return filled($user?->email);
  }

  /**
   * @param mixed $record Enregistrement chambre ou atelier
   * @param string $staffTarget Cible responsable ou adjoint
   * @return User|null Utilisateur encadreur
   */
  public static function resolveStaffUser(mixed $record, string $staffTarget = 'responsable'): ?User
  {
    if ($record instanceof RetreatChambre) {
      $record->loadMissing('responsable');

      return $record->responsable;
    }

    if ($record instanceof RetreatAtelier) {
      $record->loadMissing(['responsable', 'adjoint']);

      if ($staffTarget === 'adjoint' && $record->adjoint !== null) {
        return $record->adjoint;
      }

      return $record->responsable;
    }

    return null;
  }

  /**
   * @param mixed $record Enregistrement chambre ou atelier
   * @return string Rôle dashboard par défaut
   */
  protected static function defaultDashboardRole(mixed $record): string
  {
    $user = self::resolveStaffUser($record);
    $currentRole = $user?->roles()->where('guard_name', 'web')->value('name');

    if (filled($currentRole)) {
      return (string) $currentRole;
    }

    return 'ouvrier';
  }

  /**
   * @param mixed $record Enregistrement chambre ou atelier
   * @return string|null Résumé lisible de l'affectation
   */
  protected static function assignmentSummary(mixed $record): ?string
  {
    if ($record instanceof RetreatChambre) {
      return __('retraite.mail_staff_assignment_chambre', ['nom' => $record->nom]);
    }

    if ($record instanceof RetreatAtelier) {
      return __('retraite.mail_staff_assignment_atelier', ['numero' => $record->numero]);
    }

    return null;
  }

  /**
   * @param mixed $record Enregistrement chambre ou atelier
   * @param string $staffTarget Cible responsable ou adjoint
   * @return string|null Rôle métier sur l'affectation
   */
  protected static function metierRoleLabel(mixed $record, string $staffTarget): ?string
  {
    if ($record instanceof RetreatChambre) {
      return (string) ($record->role_on_chambre ?: 'responsable');
    }

    if ($record instanceof RetreatAtelier) {
      if ($staffTarget === 'adjoint') {
        return 'adjoint';
      }

      return (string) ($record->role_on_atelier ?: 'responsable');
    }

    return null;
  }

  /**
   * @param string $label Libellé du rôle
   * @param User|null $user Utilisateur
   * @return string Option select
   */
  protected static function staffOptionLabel(string $label, ?User $user): string
  {
    if ($user === null) {
      return $label.' — non défini';
    }

    return sprintf('%s — %s (%s)', $label, $user->name, $user->email);
  }
}
