<x-filament-panels::page>
    @include('filament.partials.cmp-atelier-ui-styles')

    <div class="mb-6">
        <label class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-200">Activité</label>
        <select
            wire:model.live="activityPlanId"
            wire:loading.attr="disabled"
            class="fi-input block w-full max-w-2xl rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900"
        >
            <option value="">— Choisir une activité —</option>
            @foreach ($this->activityOptions as $id => $label)
                <option value="{{ $id }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    @if ($isLoadingAteliers)
        <div class="cmp-atelier-loader" aria-live="polite">
            <span class="cmp-atelier-spinner"></span>
            <span>Chargement des ateliers et participants…</span>
        </div>
    @elseif (! $activityPlanId)
        <x-filament::section>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                Sélectionnez une activité, puis dépliez un atelier pour pointer les présences sur chaque ligne.
            </p>
        </x-filament::section>
    @elseif (count($atelierBlocks) === 0)
        <x-filament::section>
            <p class="text-sm text-gray-600 dark:text-gray-300">Aucun atelier avec participants pour cette activité.</p>
        </x-filament::section>
    @else
        @foreach ($atelierBlocks as $block)
            @php
                $atelier = $block['atelier'];
                $canManage = (bool) ($block['can_manage'] ?? false);
                $participants = $block['participants'];
                $attendances = $block['attendances'];
                $debatOptions = $block['debat_options'] ?? [];
                $report = $block['report'] ?? null;
                $reportLocked = $report?->isSubmitted() ?? false;
                $canEditReport = $canManage && ! $reportLocked;
                $statusLabels = [
                    'present' => 'Présent',
                    'absent' => 'Absent',
                    'late' => 'En retard',
                    'excused' => 'Excusé',
                ];
                $statusColors = [
                    'present' => '#22c55e',
                    'absent' => '#ef4444',
                    'late' => '#f59e0b',
                    'excused' => '#3b82f6',
                ];
                $presentCount = $participants->filter(fn ($p) => in_array($attendances->get($p->id)?->status, ['present', 'late'], true))->count();
            @endphp

            <x-filament::section
                collapsible
                :collapsed="$loop->index !== 0"
                class="cmp-atelier-section"
                wire:key="atelier-block-{{ $atelier->id }}-{{ $activityPlanId }}"
            >
                <x-slot name="heading">
                    Atelier {{ $atelier->numero }}
                    · {{ $participants->count() }} membre(s)
                    · {{ $presentCount }} présent(s)/retard
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
                                <th class="cmp-th-present">Présent</th>
                                <th class="cmp-th-absent">Absent</th>
                                <th class="cmp-th-late">En retard</th>
                                <th class="cmp-th-excused">Excusé</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($participants as $participant)
                                @php
                                    $attendance = $attendances->get($participant->id);
                                    $currentStatus = $attendance?->status ?? 'absent';
                                @endphp
                                <tr class="cmp-pointage-row" wire:key="row-{{ $participant->id }}-{{ $activityPlanId }}">
                                    <td style="text-align:center">
                                        <span class="cmp-pointage-num">{{ $loop->iteration }}</span>
                                    </td>
                                    <td>
                                        <div class="cmp-pointage-name">{{ $participant->full_name }}</div>
                                        @if ($attendance?->recorder)
                                            <div class="cmp-pointage-meta">
                                                Par {{ $attendance->recorder->name }}
                                                @if ($attendance->updated_at)
                                                    · {{ $attendance->updated_at->format('d/m H:i') }}
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    @foreach ($statusLabels as $statusKey => $statusLabel)
                                        <td class="cmp-status-cell">
                                            @if ($canManage)
                                                <button
                                                    type="button"
                                                    class="cmp-status-check {{ $currentStatus === $statusKey ? 'is-active' : '' }}"
                                                    style="--status-color: {{ $statusColors[$statusKey] }}"
                                                    wire:click="setAttendance({{ $participant->id }}, '{{ $statusKey }}')"
                                                    wire:loading.class="is-loading"
                                                    wire:target="setAttendance({{ $participant->id }}, '{{ $statusKey }}')"
                                                    title="{{ $statusLabel }}"
                                                >
                                                    <span class="cmp-check-box">
                                                        @if ($currentStatus === $statusKey) ✓ @endif
                                                    </span>
                                                    <span wire:loading.remove wire:target="setAttendance({{ $participant->id }}, '{{ $statusKey }}')">
                                                        {{ $statusLabel }}
                                                    </span>
                                                    <span wire:loading wire:target="setAttendance({{ $participant->id }}, '{{ $statusKey }}')">…</span>
                                                </button>
                                            @else
                                                <span
                                                    class="cmp-status-check is-readonly {{ $currentStatus === $statusKey ? 'is-active' : '' }}"
                                                    style="--status-color: {{ $statusColors[$statusKey] }}; opacity: {{ $currentStatus === $statusKey ? '1' : '.35' }}"
                                                >
                                                    <span class="cmp-check-box">
                                                        @if ($currentStatus === $statusKey) ✓ @endif
                                                    </span>
                                                    {{ $statusLabel }}
                                                </span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                                @if ($currentStatus === 'excused')
                                    <tr class="cmp-excuse-row cmp-pointage-row" wire:key="excuse-{{ $participant->id }}-{{ $activityPlanId }}">
                                        <td colspan="6">
                                            <div class="cmp-excuse-field">
                                                <label class="cmp-excuse-label" for="excuse-{{ $participant->id }}">Motif de l'excuse</label>
                                                @if ($canManage)
                                                    <input
                                                        id="excuse-{{ $participant->id }}"
                                                        type="text"
                                                        class="cmp-excuse-input"
                                                        wire:model.defer="excuseNotes.{{ $participant->id }}"
                                                        wire:blur="saveExcuseNote({{ $participant->id }})"
                                                        placeholder="Indiquez la raison de l'absence excusée"
                                                    >
                                                @else
                                                    <div class="cmp-excuse-input" readonly>
                                                        {{ $attendance?->note ?? $excuseNotes[$participant->id] ?? '—' }}
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($canManage || $report)
                    <div class="cmp-report-section">
                        <h3 class="mb-3 text-sm font-bold text-gray-900 dark:text-gray-100">
                            Compte-rendu de l'activité
                        </h3>

                        @if ($reportLocked)
                            <div class="cmp-report-locked">
                                <span>🔒</span>
                                <span>
                                    Compte-rendu soumis le {{ $report->submitted_at->format('d/m/Y à H:i') }}
                                    @if ($report->recorder) par {{ $report->recorder->name }} @endif
                                    — modification impossible.
                                </span>
                            </div>
                        @endif

                        <div class="cmp-report-grid">
                            <div class="cmp-report-field cmp-report-field--full" style="--field-color: #7b1d3e">
                                <label class="cmp-report-label" for="sujet-{{ $atelier->id }}">Sujet</label>
                                @if ($canEditReport)
                                    <input
                                        id="sujet-{{ $atelier->id }}"
                                        type="text"
                                        wire:model.defer="reportForms.{{ $atelier->id }}.sujet"
                                        class="cmp-report-input"
                                        placeholder="Sujet de l'activité dans cet atelier"
                                    >
                                @else
                                    <div class="cmp-report-input" readonly>{{ $report?->sujet ?? '—' }}</div>
                                @endif
                            </div>

                            <div class="cmp-report-field cmp-report-field--full" style="--field-color: #2563eb">
                                <label class="cmp-report-label" for="bib-{{ $atelier->id }}">Texte biblique</label>
                                @if ($canEditReport)
                                    <textarea
                                        id="bib-{{ $atelier->id }}"
                                        wire:model.defer="reportForms.{{ $atelier->id }}.texte_biblique"
                                        rows="2"
                                        class="cmp-report-input"
                                        placeholder="Références et passages"
                                    ></textarea>
                                @else
                                    <div class="cmp-report-input" readonly>{{ $report?->texte_biblique ?? '—' }}</div>
                                @endif
                            </div>

                            <div class="cmp-report-field" style="--field-color: #7c3aed">
                                <label class="cmp-report-label" for="cond-u-{{ $atelier->id }}">Conducteur(s) — ouvriers</label>
                                @if ($canEditReport)
                                    <select
                                        id="cond-u-{{ $atelier->id }}"
                                        wire:model.defer="reportForms.{{ $atelier->id }}.conducteur_user_ids"
                                        multiple
                                        class="cmp-report-input"
                                        style="min-height: 5.5rem"
                                    >
                                        @foreach ($this->workerOptions as $worker)
                                            <option value="{{ $worker->id }}">{{ $worker->name }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <div class="cmp-report-input" readonly>
                                        @if ($report && is_array($report->conducteurs))
                                            {{ collect($report->conducteurs)->where('type', 'user')->pluck('label')->join(', ') ?: '—' }}
                                        @else — @endif
                                    </div>
                                @endif
                            </div>

                            <div class="cmp-report-field" style="--field-color: #ea580c">
                                <label class="cmp-report-label" for="cond-p-{{ $atelier->id }}">Conducteur(s) — participants</label>
                                @if ($canEditReport)
                                    <select
                                        id="cond-p-{{ $atelier->id }}"
                                        wire:model.defer="reportForms.{{ $atelier->id }}.conducteur_participant_ids"
                                        multiple
                                        class="cmp-report-input"
                                        style="min-height: 5.5rem"
                                    >
                                        @foreach ($participants as $p)
                                            <option value="{{ $p->id }}">{{ $p->full_name }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <div class="cmp-report-input" readonly>
                                        @if ($report && is_array($report->conducteurs))
                                            {{ collect($report->conducteurs)->where('type', 'participant')->pluck('label')->join(', ') ?: '—' }}
                                        @else — @endif
                                    </div>
                                @endif
                            </div>

                            <div class="cmp-report-field cmp-report-field--full" style="--field-color: #0891b2">
                                <label class="cmp-report-label" for="cond-d-{{ $atelier->id }}">Conducteur(s) du débat</label>
                                @if ($canEditReport)
                                    <select
                                        id="cond-d-{{ $atelier->id }}"
                                        wire:model.defer="reportForms.{{ $atelier->id }}.conducteur_debat_keys"
                                        multiple
                                        class="cmp-report-input"
                                        style="min-height: 5.5rem"
                                    >
                                        @foreach ($debatOptions as $option)
                                            <option value="{{ $option['key'] }}">{{ $option['label'] }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <div class="cmp-report-input" readonly>
                                        @if ($report && is_array($report->conducteurs))
                                            {{ collect($report->conducteurs)->whereIn('type', ['debat_user', 'debat_participant'])->pluck('label')->join(', ') ?: '—' }}
                                        @else — @endif
                                    </div>
                                @endif
                            </div>

                            <div class="cmp-report-field cmp-report-field--full" style="--field-color: #16a34a">
                                <label class="cmp-report-label" for="resume-{{ $atelier->id }}">Résumé</label>
                                @if ($canEditReport)
                                    <textarea
                                        id="resume-{{ $atelier->id }}"
                                        wire:model.defer="reportForms.{{ $atelier->id }}.resume"
                                        rows="3"
                                        class="cmp-report-input"
                                        placeholder="Résumé de l'activité de l'atelier"
                                    ></textarea>
                                @else
                                    <div class="cmp-report-input" readonly>{{ $report?->resume ?? '—' }}</div>
                                @endif
                            </div>
                        </div>

                        @if ($canEditReport)
                            <div class="cmp-report-actions">
                                <x-filament::button
                                    type="button"
                                    wire:click="submitAtelierReport({{ $atelier->id }})"
                                    wire:confirm="Soumettre définitivement ce compte-rendu ? Vous ne pourrez plus le modifier."
                                    wire:loading.attr="disabled"
                                    wire:target="submitAtelierReport({{ $atelier->id }})"
                                >
                                    <span wire:loading.remove wire:target="submitAtelierReport({{ $atelier->id }})">
                                        Soumettre le compte-rendu
                                    </span>
                                    <span wire:loading wire:target="submitAtelierReport({{ $atelier->id }})">
                                        Soumission en cours…
                                    </span>
                                </x-filament::button>
                            </div>
                        @endif
                    </div>
                @endif
            </x-filament::section>
        @endforeach
    @endif
</x-filament-panels::page>
