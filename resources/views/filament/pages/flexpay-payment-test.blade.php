<x-filament-panels::page>
    <div class="grid gap-6">
        <x-filament::section>
            <x-slot name="heading">
                Configuration FlexPay active
            </x-slot>

            <div class="flex flex-wrap gap-2 mb-4">
                <x-filament::button wire:click="refreshConfig" color="gray" size="sm" icon="heroicon-o-arrow-path">
                    Recharger la config
                </x-filament::button>
            </div>

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
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Lancer un test
            </x-slot>

            <form wire:submit="runTest" class="grid gap-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Type de test</label>
                        <select wire:model.live="operation" class="fi-input block w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900">
                            <option value="probe">Sondage connectivité (GET sur les URLs)</option>
                            <option value="mobile">Paiement Mobile Money</option>
                            <option value="card">Paiement carte bancaire</option>
                            <option value="check">Vérifier une transaction (check)</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Timeout (secondes)</label>
                        <input type="number" min="5" max="120" wire:model="timeoutSeconds" class="fi-input block w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900">
                    </div>
                </div>

                @if ($operation !== 'probe')
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium">Référence</label>
                            <div class="flex gap-2">
                                <input type="text" wire:model="reference" class="fi-input block w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900">
                                <x-filament::button type="button" wire:click="regenerateReference" color="gray" size="sm">
                                    Nouveau
                                </x-filament::button>
                            </div>
                        </div>

                        @if (in_array($operation, ['mobile', 'card'], true))
                            <div>
                                <label class="mb-1 block text-sm font-medium">Montant</label>
                                <input type="text" wire:model="amount" class="fi-input block w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium">Devise</label>
                                <input type="text" wire:model="currency" class="fi-input block w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900">
                            </div>
                        @endif
                    </div>
                @endif

                @if ($operation === 'mobile')
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium">Opérateur (type FlexPay)</label>
                            <select wire:model="flexpayType" class="fi-input block w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900">
                                @foreach ($this->mobileProviderOptions() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Téléphone (12 chiffres, 243…)</label>
                            <input type="text" wire:model="phone" placeholder="243891234567" class="fi-input block w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900">
                        </div>
                    </div>
                @endif

                @if ($operation === 'card')
                    <div>
                        <label class="mb-1 block text-sm font-medium">Description</label>
                        <input type="text" wire:model="description" class="fi-input block w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900">
                    </div>
                @endif

                <div class="flex flex-wrap gap-2">
                    <x-filament::button type="submit" icon="heroicon-o-play">
                        Exécuter le test
                    </x-filament::button>
                </div>
            </form>

            <p class="mt-4 text-sm text-gray-600 dark:text-gray-300">
                Les tests appellent directement FlexPay depuis ce serveur. Utilisez un petit montant et une référence
                préfixée <code class="text-xs">TEST-</code>. Les retours bruts (HTTP, JSON, erreurs cURL) s’affichent ci-dessous.
            </p>
        </x-filament::section>

        @if ($lastResult)
            <x-filament::section>
                <x-slot name="heading">
                    Dernier retour FlexPay
                </x-slot>

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
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
