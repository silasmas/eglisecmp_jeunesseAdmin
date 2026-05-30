<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Met à jour le libellé du point 5 du règlement public (données collectées).
 */
return new class extends Migration
{
  public function up(): void
  {
    $replacements = [
      'Les données collectées sont utilisées pour la gestion de l’événement, l’édition du badge et les communications officielles (e-mail ou messagerie) liées à votre inscription.' =>
        'Les données collectées sont uniquement pour la gestion de l’événement, l’édition du badge et les communications officielles (E-mail ou message) liées à votre inscription.',
      "Les données collectées sont utilisées pour la gestion de l'événement, l'édition du badge et les communications officielles (e-mail ou messagerie) liées à votre inscription." =>
        'Les données collectées sont uniquement pour la gestion de l’événement, l’édition du badge et les communications officielles (E-mail ou message) liées à votre inscription.',
    ];

    DB::table('retreat_policies')->orderBy('id')->each(function (object $row) use ($replacements): void {
      $content = (string) $row->content;
      $updated = $content;

      foreach ($replacements as $search => $replace) {
        $updated = str_replace($search, $replace, $updated);
      }

      if ($updated !== $content) {
        DB::table('retreat_policies')->where('id', $row->id)->update([
          'content' => $updated,
          'updated_at' => now(),
        ]);
      }
    });
  }

  public function down(): void
  {
    $replacements = [
      'Les données collectées sont uniquement pour la gestion de l’événement, l’édition du badge et les communications officielles (E-mail ou message) liées à votre inscription.' =>
        'Les données collectées sont utilisées pour la gestion de l’événement, l’édition du badge et les communications officielles (e-mail ou messagerie) liées à votre inscription.',
    ];

    DB::table('retreat_policies')->orderBy('id')->each(function (object $row) use ($replacements): void {
      $content = (string) $row->content;
      $updated = $content;

      foreach ($replacements as $search => $replace) {
        $updated = str_replace($search, $replace, $updated);
      }

      if ($updated !== $content) {
        DB::table('retreat_policies')->where('id', $row->id)->update([
          'content' => $updated,
          'updated_at' => now(),
        ]);
      }
    });
  }
};
