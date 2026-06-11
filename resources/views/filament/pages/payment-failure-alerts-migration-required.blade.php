<div class="rounded-xl border border-amber-300 bg-amber-50 p-6 text-sm text-amber-950 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-100">
    <h2 class="mb-2 text-base font-semibold">Migration base de données requise</h2>
    <p class="mb-4">
        La table <code class="text-xs">retreat_payment_failure_alerts</code> n'existe pas encore sur ce serveur.
        Les échecs de paiement ne peuvent pas être listés tant que la migration n'a pas été exécutée.
    </p>
    <p class="mb-2 font-medium">Sur le serveur (SSH ou terminal hébergeur), exécutez&nbsp;:</p>
    <pre class="mb-4 overflow-x-auto rounded-lg bg-gray-950 p-4 text-xs text-green-200">php artisan migrate --path=database/migrations/2026_06_09_130000_create_retreat_payment_failure_alerts_table.php --force</pre>
    <p class="text-xs opacity-80">
        Après la migration, rechargez cette page pour afficher la liste des échecs de paiement.
    </p>
</div>
