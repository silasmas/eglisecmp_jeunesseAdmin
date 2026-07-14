<?php

namespace App\Console\Commands;

use App\Models\RetreatParticipant;
use App\Support\RetreatBilletPageBuilder;
use Illuminate\Console\Command;

/**
 * Affiche l'URL publique du billet pour un participant payé (debug / production).
 */
class RetreatBilletUrlCommand extends Command
{
  protected $signature = 'retreat:billet-url
                          {--id= : ID du participant}
                          {--email= : E-mail du participant}
                          {--latest : Dernier participant payé}';

  protected $description = 'Affiche le lien public du billet participant (base de données courante).';

  /**
   * @return int Code de sortie
   */
  public function handle(): int
  {
    $participant = $this->resolveParticipant();

    if ($participant === null) {
      $this->error('Aucun participant payé trouvé avec ces critères.');

      return self::FAILURE;
    }

    $url = RetreatBilletPageBuilder::publicUrl($participant);

    if ($url === null) {
      $this->error('Participant sans token billet ou paiement non validé (ID '.$participant->id.').');

      return self::FAILURE;
    }

    $this->info('Participant : '.$participant->full_name.' (ID '.$participant->id.')');
    $this->line('Token : '.$participant->download_token);
    $this->line('URL billet : '.$url);

    return self::SUCCESS;
  }

  /**
   * @return RetreatParticipant|null
   */
  protected function resolveParticipant(): ?RetreatParticipant
  {
    if ($this->option('id')) {
      return RetreatParticipant::query()
        ->whereKey((int) $this->option('id'))
        ->where('paiement_valide', true)
        ->first();
    }

    if ($this->option('email')) {
      return RetreatParticipant::query()
        ->where('email', (string) $this->option('email'))
        ->where('paiement_valide', true)
        ->latest('id')
        ->first();
    }

    return RetreatParticipant::query()
      ->where('paiement_valide', true)
      ->whereNotNull('download_token')
      ->where('is_active', true)
      ->latest('id')
      ->first();
  }
}
