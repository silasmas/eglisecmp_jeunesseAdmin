<x-filament-panels::page>
    @include('filament.partials.cmp-atelier-ui-styles')

    <div class="mb-6">
        <label class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-200">Événement</label>
        <select
            wire:model.live="eventId"
            wire:loading.attr="disabled"
            class="fi-input block w-full max-w-2xl rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900"
        >
            <option value="">— Choisir un événement —</option>
            @foreach ($this->eventOptions as $id => $label)
                <option value="{{ $id }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    @if ($isLoadingAteliers)
        <div class="cmp-atelier-loader" aria-live="polite">
            <span class="cmp-atelier-spinner"></span>
            <span>Chargement des ateliers et participants…</span>
        </div>
    @elseif (! $eventId)
        <x-filament::section>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                Sélectionnez un événement pour gérer les mouvements des participants regroupés par atelier.
                Seuls le responsable et l'adjoint peuvent autoriser une sortie ou un retour.
            </p>
        </x-filament::section>
    @elseif (count($atelierBlocks) === 0)
        <x-filament::section>
            <p class="text-sm text-gray-600 dark:text-gray-300">Aucun participant dans vos ateliers pour cet événement.</p>
        </x-filament::section>
    @else
        @foreach ($atelierBlocks as $block)
            @php
                $atelier = $block['atelier'];
                $canManage = (bool) ($block['can_manage'] ?? false);
                $participants = $block['participants'];
                $movements = $block['movements'];
                $typeLabels = ['exit' => 'Sortie', 'return' => 'Retour'];
                $typeColors = ['exit' => '#ea580c', 'return' => '#22c55e'];
            @endphp

            <x-filament::section
                collapsible
                :collapsed="$loop->index !== 0"
                class="cmp-atelier-section"
                wire:key="movement-atelier-{{ $atelier->id }}-{{ $eventId }}"
            >
                <x-slot name="heading">
                    Atelier {{ $atelier->numero }} · {{ $participants->count() }} participant(s)
                </x-slot>
                <x-slot name="description">
                    Responsable : {{ $atelier->responsable?->name ?? '—' }}
                    @if ($atelier->adjoint) · Adjoint : {{ $atelier->adjoint->name }} @endif
                </x-slot>

                <div class="cmp-pointage-wrap">
                    <table class="cmp-pointage-table">
                        <thead>
                            <tr>
                                <th class="cmp-th-num">N°</th>
                                <th class="cmp-th-name">Membre</th>
                                <th class="cmp-th-name">Motif</th>
                                <th class="cmp-th-name">Observation</th>
                                <th class="cmp-th-exit">Sortie</th>
                                <th class="cmp-th-return">Retour</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($participants as $participant)
                                @php
                                    $participantMovements = $movements->get($participant->id, collect());
                                    $lastMovement = $participantMovements->first();
                                @endphp
                                <tr class="cmp-pointage-row" wire:key="movement-row-{{ $participant->id }}-{{ $eventId }}">
                                    <td style="text-align:center">
                                        <span class="cmp-pointage-num">{{ $loop->iteration }}</span>
                                    </td>
                                    <td>
                                        <div class="cmp-pointage-name">{{ $participant->full_name }}</div>
                                        @if ($lastMovement)
                                            <div class="cmp-movement-history" title="{{ $typeLabels[$lastMovement->movement_type] ?? $lastMovement->movement_type }} · {{ $lastMovement->moved_at?->format('d/m/Y H:i') }}">
                                                Dernier : {{ $typeLabels[$lastMovement->movement_type] ?? $lastMovement->movement_type }}
                                                · {{ $lastMovement->moved_at?->format('d/m H:i') }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($canManage)
                                            <input
                                                type="text"
                                                class="cmp-movement-reason-input"
                                                wire:model.defer="participantReasons.{{ $participant->id }}"
                                                placeholder="Ex. courses, soins"
                                            >
                                        @else
                                            <span class="text-xs text-gray-500">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($canManage)
                                            <input
                                                type="text"
                                                class="cmp-movement-reason-input"
                                                wire:model.defer="participantNotes.{{ $participant->id }}"
                                                placeholder="Observation"
                                            >
                                        @else
                                            <span class="text-xs text-gray-500">—</span>
                                        @endif
                                    </td>
                                    @foreach (['exit' => 'Sortie', 'return' => 'Retour'] as $typeKey => $typeLabel)
                                        <td class="cmp-status-cell">
                                            @if ($canManage)
                                                <button
                                                    type="button"
                                                    class="cmp-status-check"
                                                    style="--status-color: {{ $typeColors[$typeKey] }}"
                                                    wire:click="recordMovement({{ $participant->id }}, '{{ $typeKey }}')"
                                                    wire:loading.class="is-loading"
                                                    wire:target="recordMovement({{ $participant->id }}, '{{ $typeKey }}')"
                                                    title="{{ $typeLabel }}"
                                                >
                                                    <span class="cmp-check-box">→</span>
                                                    <span wire:loading.remove wire:target="recordMovement({{ $participant->id }}, '{{ $typeKey }}')">
                                                        {{ $typeLabel }}
                                                    </span>
                                                    <span wire:loading wire:target="recordMovement({{ $participant->id }}, '{{ $typeKey }}')">…</span>
                                                </button>
                                            @else
                                                <span
                                                    class="cmp-status-check is-readonly"
                                                    style="--status-color: {{ $typeColors[$typeKey] }}; opacity: .35"
                                                >
                                                    {{ $typeLabel }}
                                                </span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                                @if ($participantMovements->isNotEmpty())
                                    <tr class="cmp-excuse-row cmp-pointage-row" wire:key="movement-history-{{ $participant->id }}">
                                        <td colspan="6">
                                            <div class="text-xs text-gray-600 dark:text-gray-400">
                                                <strong>Historique :</strong>
                                                @foreach ($participantMovements->take(5) as $movement)
                                                    {{ $typeLabels[$movement->movement_type] ?? $movement->movement_type }}
                                                    @if ($movement->authorizedBy) ({{ $movement->authorizedBy->name }}) @endif
                                                    · {{ $movement->moved_at?->format('d/m/Y H:i') }}
                                                    @if ($movement->reason) — {{ $movement->reason }} @endif
                                                    @if ($movement->note) [{{ $movement->note }}] @endif
                                                    @if (! $loop->last) · @endif
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endforeach
    @endif
</x-filament-panels::page>
