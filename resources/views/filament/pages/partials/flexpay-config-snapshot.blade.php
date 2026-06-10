<dl class="grid gap-3 text-sm md:grid-cols-2">
    <div>
        <dt class="font-medium text-gray-500 dark:text-gray-400">Marchand</dt>
        <dd class="font-mono text-xs break-all">{{ $configSnapshot['merchant'] ?? '—' }}</dd>
    </div>
    <div>
        <dt class="font-medium text-gray-500 dark:text-gray-400">Token API</dt>
        <dd class="font-mono text-xs break-all">{{ $configSnapshot['token_preview'] ?? '—' }}</dd>
    </div>
    <div class="md:col-span-2">
        <dt class="font-medium text-gray-500 dark:text-gray-400">Mobile</dt>
        <dd class="font-mono text-xs break-all">{{ $configSnapshot['gateway_mobile'] ?? '—' }}</dd>
    </div>
    <div class="md:col-span-2">
        <dt class="font-medium text-gray-500 dark:text-gray-400">Carte</dt>
        <dd class="font-mono text-xs break-all">{{ $configSnapshot['gateway_card'] ?? '—' }}</dd>
    </div>
    <div class="md:col-span-2">
        <dt class="font-medium text-gray-500 dark:text-gray-400">Vérification (check)</dt>
        <dd class="font-mono text-xs break-all">{{ $configSnapshot['gateway_check'] ?? '—' }}</dd>
    </div>
    <div class="md:col-span-2">
        <dt class="font-medium text-gray-500 dark:text-gray-400">Callback webhook</dt>
        <dd class="font-mono text-xs break-all">{{ $configSnapshot['callback_url'] ?? '—' }}</dd>
    </div>
</dl>

@if (! ($configSnapshot['merchant_configured'] ?? false) || ! ($configSnapshot['token_configured'] ?? false))
    <div class="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-100">
        Vérifiez <code class="text-xs">FLEXPAY_MARCHAND</code>, <code class="text-xs">FLEXPAY_API_TOKEN</code>
        et les URLs <code class="text-xs">FLEXPAY_GATEWAY_*</code> dans le .env.
    </div>
@endif
