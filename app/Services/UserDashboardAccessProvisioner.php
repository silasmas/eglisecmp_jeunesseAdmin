<?php

namespace App\Services;

use App\Mail\RetreatStaffAccessCredentialsMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * Provisionne le mot de passe et le rôle dashboard d'un encadreur, puis envoie les identifiants.
 */
class UserDashboardAccessProvisioner
{
  /**
   * Longueur du mot de passe généré automatiquement.
   */
  private const GENERATED_PASSWORD_LENGTH = 16;

  /**
   * Met à jour le compte, assigne le rôle panel et envoie l'e-mail d'accès.
   *
   * @param User $user Utilisateur cible
   * @param string $dashboardRole Nom du rôle Spatie (web)
   * @param bool $generatePassword True pour générer un mot de passe aléatoire
   * @param string|null $manualPassword Mot de passe saisi manuellement
   * @param string|null $assignmentSummary Résumé lisible de l'affectation (chambre/atelier)
   * @param string|null $metierRoleLabel Rôle métier (responsable, adjoint, etc.)
   * @return array{success: bool, message: string}
   */
  public function provisionAndNotify(
    User $user,
    string $dashboardRole,
    bool $generatePassword,
    ?string $manualPassword = null,
    ?string $assignmentSummary = null,
    ?string $metierRoleLabel = null,
  ): array {
    if (! filled($user->email)) {
      return [
        'success' => false,
        'message' => 'Ce responsable n\'a pas d\'adresse e-mail.',
      ];
    }

    $role = Role::query()
      ->where('guard_name', 'web')
      ->where('name', $dashboardRole)
      ->first();

    if ($role === null) {
      return [
        'success' => false,
        'message' => 'Le rôle sélectionné est introuvable.',
      ];
    }

    try {
      $plainPassword = $this->resolvePlainPassword($generatePassword, $manualPassword);
    } catch (ValidationException $exception) {
      return [
        'success' => false,
        'message' => collect($exception->errors())->flatten()->first() ?? 'Mot de passe invalide.',
      ];
    }

    $user->password = $plainPassword;
    $user->is_active = true;
    $user->save();
    $user->syncRoles([$role->name]);

    try {
      Mail::to($user->email)->send(new RetreatStaffAccessCredentialsMail(
        user: $user,
        plainPassword: $plainPassword,
        dashboardRole: $role->name,
        assignmentSummary: $assignmentSummary,
        metierRoleLabel: $metierRoleLabel,
      ));
    } catch (\Throwable $exception) {
      Log::warning('E-mail identifiants encadreur non envoyé', [
        'user_id' => $user->id,
        'error' => $exception->getMessage(),
      ]);
      report($exception);

      return [
        'success' => false,
        'message' => 'Le compte a été mis à jour, mais l\'e-mail n\'a pas pu être envoyé.',
      ];
    }

    return [
      'success' => true,
      'message' => sprintf('Identifiants envoyés à %s.', $user->email),
    ];
  }

  /**
   * Génère un mot de passe aléatoire sécurisé.
   *
   * @return string Mot de passe en clair
   */
  public function generatePassword(): string
  {
    return Str::password(self::GENERATED_PASSWORD_LENGTH, symbols: false);
  }

  /**
   * @param bool $generatePassword True pour générer automatiquement
   * @param string|null $manualPassword Mot de passe saisi
   * @return string Mot de passe en clair
   *
   * @throws ValidationException
   */
  protected function resolvePlainPassword(bool $generatePassword, ?string $manualPassword): string
  {
    if ($generatePassword) {
      return $this->generatePassword();
    }

    $password = trim((string) $manualPassword);

    if ($password === '') {
      throw ValidationException::withMessages([
        'password' => 'Saisissez un mot de passe ou activez la génération automatique.',
      ]);
    }

    if (strlen($password) < 8) {
      throw ValidationException::withMessages([
        'password' => 'Le mot de passe doit contenir au moins 8 caractères.',
      ]);
    }

    return $password;
  }

  /**
   * Génère un mot de passe, active le compte et assigne le rôle dashboard si absent.
   * Ne déclenche pas d'e-mail séparé (identifiants inclus dans l'e-mail d'affectation).
   *
   * @param User $user Utilisateur encadreur
   * @param string $defaultRole Rôle Spatie par défaut si aucun rôle n'est défini
   * @return array{plainPassword: string, dashboardRole: string} Mot de passe en clair et rôle assigné
   */
  public function provisionForStaffAssignment(User $user, string $defaultRole = 'ouvrier'): array
  {
    $plainPassword = $this->generatePassword();
    $dashboardRole = (string) ($user->roles()->where('guard_name', 'web')->value('name') ?? '');

    if ($dashboardRole === '') {
      $dashboardRole = $defaultRole;
      $user->syncRoles([$dashboardRole]);
    }

    $user->password = $plainPassword;
    $user->is_active = true;
    $user->save();

    return [
      'plainPassword' => $plainPassword,
      'dashboardRole' => $dashboardRole,
    ];
  }
}
