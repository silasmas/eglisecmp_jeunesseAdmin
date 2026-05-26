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
    <div class="cmp-groups-page" x-data="{ tab: 'hommes', search: '' }">
        <section class="cmp-groups-header">
            <div>
                <p class="cmp-groups-eyebrow">Organisation</p>
                <h1 class="cmp-groups-title">Répartition des participants</h1>
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
            <article class="cmp-stat-card">
                <p class="cmp-stat-label">Ateliers actifs</p>
                <p class="cmp-stat-value">{{ $stats['ateliers_count'] }}</p>
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
            </button>
        </section>

        <section x-show="tab === 'hommes'" x-cloak>
            <div class="cmp-section-head">
                <h2>Chambres Hommes</h2>
                <span>{{ count($chambresHommes) }} chambre(s)</span>
            </div>
            <div class="cmp-card-grid">
                @forelse($chambresHommes as $chambre)
                    <article class="cmp-group-card">
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
                                <a
                                    class="cmp-list-item"
                                    href="{{ $participantResource::getUrl('view', ['record' => $participant['id']]) }}"
                                    x-show="search === '' || @js(strtolower($participant['name'])).includes(search.toLowerCase())"
                                    x-transition
                                >
                                    <span>{{ $participant['name'] }}</span>
                                    <span class="cmp-list-meta">
                                        @if($participant['atelier_number'])
                                            At. {{ $participant['atelier_number'] }}
                                        @else
                                            —
                                        @endif
                                    </span>
                                </a>
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
                    <article class="cmp-group-card">
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
                                <a
                                    class="cmp-list-item"
                                    href="{{ $participantResource::getUrl('view', ['record' => $participant['id']]) }}"
                                    x-show="search === '' || @js(strtolower($participant['name'])).includes(search.toLowerCase())"
                                    x-transition
                                >
                                    <span>{{ $participant['name'] }}</span>
                                    <span class="cmp-list-meta">
                                        @if($participant['atelier_number'])
                                            At. {{ $participant['atelier_number'] }}
                                        @else
                                            —
                                        @endif
                                    </span>
                                </a>
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
            </div>

            @forelse($ateliersByTranche as $tranche => $ateliers)
                <div class="cmp-tranche-title">{{ $tranche }}</div>
                <div class="cmp-card-grid">
                    @foreach($ateliers as $atelier)
                        <article class="cmp-group-card">
                            <header class="cmp-group-card__header">
                                <div>
                                    <h3>Atelier {{ $atelier['numero'] }}</h3>
                                    <p>Resp.: {{ $atelier['responsable_name'] ?? 'Non défini' }}</p>
                                    @if($atelier['adjoint_name'])
                                        <p>Adjoint: {{ $atelier['adjoint_name'] }}</p>
                                    @endif
                                </div>
                                <span class="cmp-card-pill">{{ $atelier['participants_count'] }}</span>
                            </header>
                            <div class="cmp-list">
                                @forelse($atelier['participants'] as $participant)
                                    <a
                                        class="cmp-list-item"
                                        href="{{ $participantResource::getUrl('view', ['record' => $participant['id']]) }}"
                                        x-show="search === '' || @js(strtolower($participant['name'])).includes(search.toLowerCase())"
                                        x-transition
                                    >
                                        <span>{{ $participant['name'] }}</span>
                                        <span class="cmp-list-meta">
                                            @if($participant['chambre_nom'])
                                                Ch. {{ $participant['chambre_nom'] }}
                                            @else
                                                —
                                            @endif
                                        </span>
                                    </a>
                                @empty
                                    <p class="cmp-empty">Aucun participant assigné.</p>
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

