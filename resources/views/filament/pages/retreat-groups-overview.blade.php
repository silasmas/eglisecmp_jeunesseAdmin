@php
    $stats = $this->stats();
    $chambresHommes = $this->chambresBySexe('homme');
    $chambresFemmes = $this->chambresBySexe('femme');
    $ateliersByTranche = $this->ateliersByTranche();
    $chambreResource = \App\Filament\Resources\RetreatChambres\RetreatChambreResource::class;
    $atelierResource = \App\Filament\Resources\RetreatAteliers\RetreatAtelierResource::class;
    $participantResource = \App\Filament\Resources\RetreatParticipants\RetreatParticipantResource::class;
@endphp

<x-filament-panels::page>
    <div
        class="cmp-groups-page"
        x-data="{
            tab: 'ateliers',
            search: '',
            draggingId: null,
            dragOverAtelierId: null,
            matchesSearch(name) {
                if (!this.search) {
                    return true;
                }
                return String(name || '').toLowerCase().includes(this.search.toLowerCase());
            },
            startDrag(event, participantId) {
                this.draggingId = participantId;
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', String(participantId));
            },
            endDrag() {
                this.draggingId = null;
                this.dragOverAtelierId = null;
            },
            allowDrop(event, atelierId) {
                event.preventDefault();
                this.dragOverAtelierId = atelierId;
            },
            dropOnAtelier(event, atelierId) {
                event.preventDefault();
                const rawId = event.dataTransfer.getData('text/plain') || this.draggingId;
                const participantId = Number(rawId);
                this.endDrag();
                if (!participantId) {
                    return;
                }
                $wire.moveParticipantToAtelier(participantId, atelierId);
            },
        }"
    >
        <section class="cmp-groups-header">
            <div>
                <p class="cmp-groups-eyebrow">Organisation</p>
                <h1 class="cmp-groups-title">Répartition des participants</h1>
                <p class="cmp-groups-hint">
                    Onglet Ateliers : glissez un membre vers un autre atelier pour le réaffecter.
                    Les hors tranche d’âge sont signalés « Possibilité de réaffecter ».
                </p>
            </div>
            <div class="cmp-groups-actions">
                <a class="cmp-link-btn" href="{{ $participantResource::getUrl('index') }}">Participants</a>
                <a class="cmp-link-btn" href="{{ $chambreResource::getUrl('index') }}">Chambres</a>
                <a class="cmp-link-btn" href="{{ $atelierResource::getUrl('index') }}">Ateliers</a>
            </div>
        </section>

        <section class="cmp-stats-grid">
            <article class="cmp-stat-card">
                <p class="cmp-stat-label">Total participants</p>
                <p class="cmp-stat-value">{{ $stats['total_participants'] }}</p>
            </article>
            <article class="cmp-stat-card">
                <p class="cmp-stat-label">Chambres hommes</p>
                <p class="cmp-stat-value">{{ $stats['hommes_current'] }} / {{ $stats['hommes_capacity'] }}</p>
            </article>
            <article class="cmp-stat-card">
                <p class="cmp-stat-label">Chambres femmes</p>
                <p class="cmp-stat-value">{{ $stats['femmes_current'] }} / {{ $stats['femmes_capacity'] }}</p>
            </article>
            <article class="cmp-stat-card {{ ($stats['mismatch_count'] ?? 0) > 0 ? 'cmp-stat-card--alert' : '' }}">
                <p class="cmp-stat-label">Mauvaises affectations</p>
                <p class="cmp-stat-value">{{ $stats['mismatch_count'] ?? 0 }}</p>
            </article>
        </section>

        <section class="cmp-search-wrap">
            <input
                class="cmp-search-input"
                type="search"
                placeholder="Rechercher un participant..."
                x-model.debounce.200ms="search"
            />
        </section>

        <section class="cmp-tabs">
            <button type="button" class="cmp-tab-btn" :class="{ 'is-active': tab === 'hommes' }" @click="tab = 'hommes'">
                Chambres (Hommes)
            </button>
            <button type="button" class="cmp-tab-btn" :class="{ 'is-active': tab === 'femmes' }" @click="tab = 'femmes'">
                Chambres (Femmes)
            </button>
            <button type="button" class="cmp-tab-btn" :class="{ 'is-active': tab === 'ateliers' }" @click="tab = 'ateliers'">
                Ateliers
                @if(($stats['mismatch_count'] ?? 0) > 0)
                    <span class="cmp-tab-badge">{{ $stats['mismatch_count'] }}</span>
                @endif
            </button>
        </section>

        <section x-show="tab === 'hommes'" x-cloak>
            <div class="cmp-section-head">
                <h2>Chambres Hommes</h2>
                <span>{{ count($chambresHommes) }} chambre(s)</span>
            </div>
            <div class="cmp-card-grid">
                @forelse($chambresHommes as $chambre)
                    <article class="cmp-group-card" wire:key="chambre-h-{{ $chambre['id'] }}">
                        <header class="cmp-group-card__header">
                            <div>
                                <h3>Chambre {{ $chambre['nom'] }}</h3>
                                <p>Responsable: {{ $chambre['responsable_name'] ?? 'Non défini' }}</p>
                            </div>
                            <span class="cmp-card-pill">
                                {{ $chambre['participants_count'] }} / {{ $chambre['capacite'] > 0 ? $chambre['capacite'] : '—' }}
                            </span>
                        </header>
                        <div class="cmp-list">
                            @forelse($chambre['participants'] as $participant)
                                <div
                                    class="cmp-list-item {{ !empty($participant['age_mismatch']) ? 'is-mismatch' : '' }}"
                                    wire:key="chambre-h-p-{{ $participant['id'] }}"
                                    x-show="matchesSearch(@js($participant['name']))"
                                    x-transition
                                >
                                    <div class="cmp-list-item__main">
                                        <a href="{{ $participantResource::getUrl('view', ['record' => $participant['id']]) }}">
                                            {{ $participant['name'] }}
                                        </a>
                                        @if(!empty($participant['age_mismatch']))
                                            <span class="cmp-mismatch-badge">Possibilité de réaffecter</span>
                                        @endif
                                    </div>
                                    <span class="cmp-list-meta">
                                        @if($participant['atelier_number'])
                                            At. {{ $participant['atelier_number'] }}
                                        @else
                                            —
                                        @endif
                                    </span>
                                </div>
                            @empty
                                <p class="cmp-empty">Aucun participant assigné.</p>
                            @endforelse
                        </div>
                    </article>
                @empty
                    <p class="cmp-empty">Aucune chambre hommes.</p>
                @endforelse
            </div>
        </section>

        <section x-show="tab === 'femmes'" x-cloak>
            <div class="cmp-section-head">
                <h2>Chambres Femmes</h2>
                <span>{{ count($chambresFemmes) }} chambre(s)</span>
            </div>
            <div class="cmp-card-grid">
                @forelse($chambresFemmes as $chambre)
                    <article class="cmp-group-card" wire:key="chambre-f-{{ $chambre['id'] }}">
                        <header class="cmp-group-card__header">
                            <div>
                                <h3>Chambre {{ $chambre['nom'] }}</h3>
                                <p>Responsable: {{ $chambre['responsable_name'] ?? 'Non défini' }}</p>
                            </div>
                            <span class="cmp-card-pill">
                                {{ $chambre['participants_count'] }} / {{ $chambre['capacite'] > 0 ? $chambre['capacite'] : '—' }}
                            </span>
                        </header>
                        <div class="cmp-list">
                            @forelse($chambre['participants'] as $participant)
                                <div
                                    class="cmp-list-item {{ !empty($participant['age_mismatch']) ? 'is-mismatch' : '' }}"
                                    wire:key="chambre-f-p-{{ $participant['id'] }}"
                                    x-show="matchesSearch(@js($participant['name']))"
                                    x-transition
                                >
                                    <div class="cmp-list-item__main">
                                        <a href="{{ $participantResource::getUrl('view', ['record' => $participant['id']]) }}">
                                            {{ $participant['name'] }}
                                        </a>
                                        @if(!empty($participant['age_mismatch']))
                                            <span class="cmp-mismatch-badge">Possibilité de réaffecter</span>
                                        @endif
                                    </div>
                                    <span class="cmp-list-meta">
                                        @if($participant['atelier_number'])
                                            At. {{ $participant['atelier_number'] }}
                                        @else
                                            —
                                        @endif
                                    </span>
                                </div>
                            @empty
                                <p class="cmp-empty">Aucun participant assigné.</p>
                            @endforelse
                        </div>
                    </article>
                @empty
                    <p class="cmp-empty">Aucune chambre femmes.</p>
                @endforelse
            </div>
        </section>

        <section x-show="tab === 'ateliers'" x-cloak>
            <div class="cmp-section-head">
                <h2>Ateliers par tranche d'âge</h2>
                <span>Glisser-déposer pour réaffecter</span>
            </div>

            @forelse($ateliersByTranche as $tranche => $ateliers)
                <div class="cmp-tranche-title">{{ $tranche }}</div>
                <div class="cmp-card-grid">
                    @foreach($ateliers as $atelier)
                        <article
                            class="cmp-group-card {{ ($atelier['mismatch_count'] ?? 0) > 0 ? 'has-mismatch' : '' }}"
                            wire:key="atelier-{{ $atelier['id'] }}"
                            :class="{ 'is-drop-target': dragOverAtelierId === {{ (int) $atelier['id'] }} }"
                            @dragover="allowDrop($event, {{ (int) $atelier['id'] }})"
                            @dragleave="if (dragOverAtelierId === {{ (int) $atelier['id'] }}) { dragOverAtelierId = null }"
                            @drop="dropOnAtelier($event, {{ (int) $atelier['id'] }})"
                        >
                            <header class="cmp-group-card__header">
                                <div>
                                    <h3>Atelier {{ $atelier['numero'] }}</h3>
                                    <p>{{ $atelier['tranche_label'] }}</p>
                                    <p>Resp.: {{ $atelier['responsable_name'] ?? 'Non défini' }}</p>
                                    @if($atelier['adjoint_name'])
                                        <p>Adjoint: {{ $atelier['adjoint_name'] }}</p>
                                    @endif
                                </div>
                                <div class="cmp-card-pills">
                                    <span class="cmp-card-pill">{{ $atelier['participants_count'] }}</span>
                                    @if(($atelier['mismatch_count'] ?? 0) > 0)
                                        <span class="cmp-card-pill cmp-card-pill--danger">
                                            {{ $atelier['mismatch_count'] }} hors tranche
                                        </span>
                                    @endif
                                </div>
                            </header>
                            <div class="cmp-list cmp-drop-zone">
                                @forelse($atelier['participants'] as $participant)
                                    <div
                                        class="cmp-list-item cmp-list-item--draggable {{ !empty($participant['age_mismatch']) ? 'is-mismatch' : '' }}"
                                        wire:key="atelier-p-{{ $atelier['id'] }}-{{ $participant['id'] }}"
                                        draggable="true"
                                        @dragstart="startDrag($event, {{ (int) $participant['id'] }})"
                                        @dragend="endDrag()"
                                        x-show="matchesSearch(@js($participant['name']))"
                                        x-transition
                                    >
                                        <div class="cmp-list-item__main">
                                            <span class="cmp-drag-handle" title="Glisser pour réaffecter" aria-hidden="true">⠿</span>
                                            <a href="{{ $participantResource::getUrl('view', ['record' => $participant['id']]) }}">
                                                {{ $participant['name'] }}
                                            </a>
                                            @if($participant['age'] !== null)
                                                <span class="cmp-age-chip">{{ $participant['age'] }} ans</span>
                                            @endif
                                            @if(!empty($participant['age_mismatch']))
                                                <span class="cmp-mismatch-badge">Possibilité de réaffecter</span>
                                            @endif
                                        </div>
                                        <span class="cmp-list-meta">
                                            @if($participant['chambre_nom'])
                                                Ch. {{ $participant['chambre_nom'] }}
                                            @else
                                                —
                                            @endif
                                        </span>
                                    </div>
                                @empty
                                    <p class="cmp-empty cmp-drop-empty">Déposez un participant ici</p>
                                @endforelse
                            </div>
                        </article>
                    @endforeach
                </div>
            @empty
                <p class="cmp-empty">Aucun atelier disponible.</p>
            @endforelse
        </section>
    </div>
</x-filament-panels::page>
