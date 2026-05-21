<div class="space-y-4 text-sm">
    <div>
        <div class="font-semibold">Message</div>
        <div class="mt-1 whitespace-pre-wrap rounded-lg bg-gray-50 p-3 dark:bg-gray-900">{{ $record->message }}</div>
    </div>

    <div>
        <div class="font-semibold">Retour Keccel</div>
        <div class="mt-1 whitespace-pre-wrap break-words rounded-lg bg-gray-50 p-3 dark:bg-gray-900">{{ $record->provider_response ?: '—' }}</div>
    </div>

    <div>
        <div class="font-semibold">Retour livraison</div>
        <div class="mt-1 whitespace-pre-wrap break-words rounded-lg bg-gray-50 p-3 dark:bg-gray-900">{{ $record->delivery_response ?: '—' }}</div>
    </div>

    @if ($record->error_message)
        <div>
            <div class="font-semibold text-danger-600">Erreur</div>
            <div class="mt-1 whitespace-pre-wrap rounded-lg bg-danger-50 p-3 text-danger-700 dark:bg-danger-950">{{ $record->error_message }}</div>
        </div>
    @endif
</div>
