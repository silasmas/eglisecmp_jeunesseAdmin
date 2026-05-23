<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Données de base et Shield
        </x-slot>

        <p class="text-sm text-gray-600 dark:text-gray-300">
            Utilisez le bouton ci-dessus pour mettre à jour les permissions Filament Shield, les rôles Spatie,
            le compte super admin, l’opérateur SMS et l’événement retraite minimal — sans recharger les données de démo.
        </p>

        <ul class="mt-4 list-disc space-y-1 pl-5 text-sm text-gray-600 dark:text-gray-300">
            <li>Permissions Shield (~183 entrées)</li>
            <li>Rôles : super_admin, panel_user, ouvrier</li>
            <li>Utilisateur admin (variables SEED_ADMIN_* du .env)</li>
            <li>Opérateur Keccel (KECCEL_*)</li>
            <li>Données retraite essentielles</li>
        </ul>
    </x-filament::section>

    @php
        $httpUrl = $this->getHttpSyncUrl();
    @endphp

    @if ($httpUrl)
        <x-filament::section class="mt-6">
            <x-slot name="heading">
                Lien HTTP (scripts / déploiement)
            </x-slot>

            <p class="mb-2 text-sm text-gray-600 dark:text-gray-300">
                Appel GET (JSON) — protégé par <code class="text-xs">PRODUCTION_BASE_SYNC_TOKEN</code> dans le .env.
                Limite : 5 requêtes par minute.
            </p>

            <div class="rounded-lg bg-gray-100 p-3 font-mono text-xs break-all dark:bg-gray-800">
                {{ $httpUrl }}
            </div>
        </x-filament::section>
    @endif

    <x-filament::section class="mt-6">
        <x-slot name="heading">
            Migrations + synchronisation
        </x-slot>

        <p class="text-sm text-gray-600 dark:text-gray-300">
            Le bouton <strong>Migrations + sync</strong> exécute d’abord
            <code class="text-xs">php artisan migrate --force</code> (nouvelles tables/colonnes),
            puis la synchronisation des données de base (Shield, rôles, admin, SMS, retraite).
        </p>
        <div class="mt-2 rounded-lg bg-gray-100 p-3 font-mono text-xs dark:bg-gray-800">
            php artisan cmp:migrate-and-sync
        </div>
    </x-filament::section>

    <x-filament::section class="mt-6">
        <x-slot name="heading">
            Lien symbolique storage
        </x-slot>

        <p class="text-sm text-gray-600 dark:text-gray-300">
            Le bouton « Lien storage » en haut de page exécute
            <code class="text-xs">php artisan storage:link</code> (public/storage → storage/app/public).
            Utile après un déploiement si les médias ne s’affichent pas.
        </p>
    </x-filament::section>

    <x-filament::section class="mt-6">
        <x-slot name="heading">
            Ligne de commande
        </x-slot>

        <p class="text-sm text-gray-600 dark:text-gray-300">
            Équivalent CLI :
        </p>
        <div class="mt-2 rounded-lg bg-gray-100 p-3 font-mono text-xs dark:bg-gray-800">
            php artisan cmp:sync-production-base
        </div>
    </x-filament::section>
</x-filament-panels::page>
