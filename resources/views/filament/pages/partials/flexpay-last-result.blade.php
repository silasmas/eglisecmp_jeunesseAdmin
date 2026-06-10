@php
    $ok = (bool) ($lastResult['success'] ?? false);
    $flexpayOk = (bool) ($lastResult['flexpay_accepted'] ?? false);
    $badgeColor = $flexpayOk ? 'success' : ($ok ? 'warning' : 'danger');
    $badgeLabel = $flexpayOk ? 'FlexPay OK (code 0)' : ($ok ? 'HTTP reçu' : 'Échec');
@endphp

<div class="mb-4 flex flex-wrap items-center gap-2">
    <x-filament::badge :color="$badgeColor">{{ $badgeLabel }}</x-filament::badge>
    @if (isset($lastResult['http_status']))
        <x-filament::badge color="gray">HTTP {{ $lastResult['http_status'] }}</x-filament::badge>
    @endif
    @if (isset($lastResult['duration_ms']))
        <x-filament::badge color="gray">{{ $lastResult['duration_ms'] }} ms</x-filament::badge>
    @endif
</div>

@if (! empty($lastResult['summary']))
    <p class="mb-4 text-sm font-medium">{{ $lastResult['summary'] }}</p>
@endif

@if (! empty($lastResult['error']))
    <div class="mb-4 rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-900 dark:border-red-800 dark:bg-red-950/30 dark:text-red-100">
        <strong>Erreur :</strong> {{ $lastResult['error'] }}
        @if (! empty($lastResult['exception_class']))
            <div class="mt-1 font-mono text-xs opacity-80">{{ $lastResult['exception_class'] }}</div>
        @endif
    </div>
@endif

@if (! empty($lastResult['redirect_url']))
    <div class="mb-4">
        <a href="{{ $lastResult['redirect_url'] }}" target="_blank" rel="noopener" class="text-sm font-medium text-primary-600 hover:underline">
            Ouvrir la page de paiement carte FlexPay
        </a>
    </div>
@endif

@if (! empty($lastResult['response']['message']) || isset($lastResult['response']['code']))
    <div class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm dark:border-gray-700 dark:bg-gray-900/40">
        <div><strong>Message FlexPay :</strong> {{ $lastResult['response']['message'] ?? '—' }}</div>
        <div><strong>Code FlexPay :</strong> {{ $lastResult['response']['code'] ?? '—' }}</div>
    </div>
@endif

@if (! empty($lastResult['probes']))
    <div class="space-y-3">
        @foreach ($lastResult['probes'] as $probe)
            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                <div class="mb-2 text-sm font-medium">{{ $probe['request']['url'] ?? 'URL' }}</div>
                <pre class="max-h-64 overflow-auto rounded bg-gray-950 p-3 text-xs text-green-200">{{ json_encode($probe, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        @endforeach
    </div>
@else
    <pre class="max-h-[32rem] overflow-auto rounded-lg bg-gray-950 p-4 text-xs leading-relaxed text-green-200">{{ json_encode($lastResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
@endif
