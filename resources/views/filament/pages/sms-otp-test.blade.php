<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Test d'envoi OTP SMS
        </x-slot>

        <p class="text-sm text-gray-600 dark:text-gray-300">
            Utilisez le bouton ci-dessus pour envoyer un OTP de test via Keccel avec la configuration SMS actuelle.
            Ce test permet de vérifier la passerelle avant d'activer un événement avec canal OTP SMS.
            Chaque tentative est enregistrée dans l'historique SMS avec le statut HTTP et le retour Keccel.
        </p>
    </x-filament::section>
</x-filament-panels::page>
