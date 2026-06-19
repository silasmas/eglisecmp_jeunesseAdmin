<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="retraite-api-base" content="{{ url('/api/v1/retreat/inscription') }}">

    <title>CMP Jeunesse - Portail retraite</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('retraite-inscription/css/tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('retraite-inscription/css/utilities.css') }}">
    <link rel="stylesheet" href="{{ asset('retraite-inscription/css/splash.css') }}">
    <link rel="stylesheet" href="{{ asset('cmp-portail/css/hero-shared.css') }}">
    <link rel="stylesheet" href="{{ asset('cmp-portail/css/cmp-layout.css') }}">
    <link rel="stylesheet" href="{{ asset('cmp-portail/css/cmp-footer.css') }}">
    <link rel="stylesheet" href="{{ asset('cmp-portail/css/welcome.css') }}">

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
        }

        a { color: inherit; text-decoration: none; }
        button, input, select { font: inherit; }

        .page {
            min-height: 100vh;
            display: grid;
            grid-template-rows: auto 1fr auto;
            width: 100%;
            max-width: 100%;
        }

        .topbar,
        .headline,
        .content {
            max-width: 1180px;
            margin-inline: auto;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .brand img {
            width: 54px;
            height: 54px;
            object-fit: contain;
            background: rgba(255, 255, 255, .92);
            border-radius: 8px;
            padding: 6px;
        }

        .brand strong,
        .brand span {
            display: block;
        }

        .brand strong {
            font-size: 1rem;
            line-height: 1.2;
        }

        .brand span span {
            margin-top: 3px;
            color: rgba(255, 255, 255, .78);
            font-size: .86rem;
        }

        .admin-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 0 16px;
            border: 1px solid rgba(255, 255, 255, .38);
            border-radius: 8px;
            color: var(--white);
            background: rgba(255, 255, 255, .12);
            backdrop-filter: blur(8px);
        }

        .content {
            width: min(1180px, calc(100% - 36px));
            margin-inline: auto;
            margin-top: -30px;
            margin-bottom: 44px;
            position: relative;
            z-index: 2;
        }

        .options {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .option {
            min-height: 210px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 22px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--white);
            box-shadow: 0 16px 35px rgba(54, 22, 36, .10);
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }

        .option:hover {
            transform: translateY(-3px);
            border-color: rgba(123, 29, 62, .36);
            box-shadow: 0 20px 42px rgba(54, 22, 36, .14);
        }

        .option.option--disabled {
            opacity: .55;
            pointer-events: none;
            cursor: not-allowed;
            transform: none;
            box-shadow: 0 8px 20px rgba(54, 22, 36, .06);
        }

        .option.option--disabled .option-action {
            color: var(--muted);
        }

        .options.options--focus-mode .option.portal-option-hidden {
            display: none !important;
        }

        .portal-options-toolbar {
            margin: 0 0 14px;
        }

        #participantLookupModal .modal {
            max-height: min(92vh, 720px);
            display: flex;
            flex-direction: column;
            width: min(560px, 100%);
        }

        #participantLookupModal .modal-body {
            overflow-y: auto;
            max-height: min(62vh, 520px);
            -webkit-overflow-scrolling: touch;
        }

        #participantLookupModal .result-list {
            max-height: none;
        }

        .option-index {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 8px;
            color: var(--brand);
            background: #f6e8ee;
            font-weight: 800;
        }

        .option h2 {
            margin: 24px 0 8px;
            font-size: clamp(1.3rem, 2vw, 1.7rem);
            line-height: 1.12;
            letter-spacing: 0;
        }

        .option p,
        .panel p {
            margin: 0;
            color: var(--muted);
            line-height: 1.5;
        }

        .option-action {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-top: 22px;
            color: var(--brand);
            font-weight: 700;
        }

        .panel {
            margin-top: 16px;
            padding: 22px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--white);
            box-shadow: 0 16px 35px rgba(54, 22, 36, .08);
        }

        .panel-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 16px;
        }

        .panel h2 {
            margin: 0 0 8px;
            font-size: 1.25rem;
            letter-spacing: 0;
        }

        .hidden { display: none !important; }

        .modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 18px;
            background: rgba(25, 12, 19, .58);
        }

        .modal {
            width: min(520px, 100%);
            border-radius: 8px;
            border: 1px solid #eadde3;
            background: var(--white);
            box-shadow: 0 28px 70px rgba(32, 14, 24, .28);
        }

        .modal-header,
        .modal-body {
            padding: 20px;
        }

        .modal-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            border-bottom: 1px solid #efe4e9;
        }

        .modal-header h2 {
            margin: 0 0 6px;
            font-size: 1.2rem;
        }

        .modal-close {
            width: 38px;
            height: 38px;
            border: 0;
            border-radius: 8px;
            color: #514650;
            background: #f5eff2;
            cursor: pointer;
            font-weight: 900;
        }

        .modal.modal-wide {
            width: min(640px, 100%);
            max-height: min(92vh, 900px);
            display: flex;
            flex-direction: column;
        }

        #workerParticipantModal .modal-body {
            overflow-y: auto;
            flex: 1 1 auto;
            -webkit-overflow-scrolling: touch;
        }

        #workerParticipantModal .modal-footer {
            flex-shrink: 0;
        }

        #workerParticipantModal #workerParticipantModalStatus:not(.hidden) {
            margin: 0 20px 12px;
        }

        .worker-modal-status {
            margin: 0 0 12px;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 0.92rem;
            line-height: 1.45;
        }

        .worker-modal-status.info {
            background: #eef4ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        .worker-modal-status.success {
            background: #ecfdf3;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .worker-modal-status.warning {
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .worker-modal-status.error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .worker-modal-note {
            margin: 0 0 10px;
            font-size: 0.88rem;
            color: var(--muted);
        }

        .modal-footer {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
            padding: 16px 20px 20px;
            border-top: 1px solid #efe4e9;
        }

        .worker-modal-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-top: 14px;
        }

        .worker-modal-meta span {
            display: block;
            padding: 10px 12px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fbf7f9;
            font-size: 0.92rem;
        }

        .worker-modal-meta strong {
            display: block;
            margin-bottom: 4px;
            font-size: 0.72rem;
            text-transform: uppercase;
            color: var(--muted);
        }

        #qrReader {
            width: min(360px, 100%);
            min-height: 280px;
            border-radius: 10px;
            margin-top: 10px;
            overflow: hidden;
            background: #111;
        }

        #qrReader.hidden {
            display: none;
        }

        #qrReader video {
            border-radius: 10px;
        }

        .registration-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .registration-badges .badge {
            font-size: 0.78rem;
        }

        .otp-boxes {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 8px;
            margin: 18px 0 10px;
        }

        .otp-digit {
            height: 54px;
            padding: 0;
            text-align: center;
            font-size: 1.35rem;
            font-weight: 900;
        }

        .button.loading {
            pointer-events: none;
            opacity: .82;
        }

        .button.loading::before {
            content: "";
            width: 16px;
            height: 16px;
            border: 2px solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: spin .8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 10px;
            align-items: end;
        }

        .search-grid {
            display: grid;
            grid-template-columns: 180px 1fr auto;
            gap: 10px;
            align-items: end;
        }

        label {
            display: block;
            margin-bottom: 6px;
            color: #463d47;
            font-size: .9rem;
            font-weight: 700;
        }

        input,
        select {
            width: 100%;
            min-height: 48px;
            padding: 0 14px;
            border: 1px solid #d8cbd2;
            border-radius: 8px;
            color: var(--ink);
            background: var(--white);
            outline: none;
        }

        input:focus,
        select:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(123, 29, 62, .12);
        }

        .button {
            min-height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 18px;
            border: 0;
            border-radius: 8px;
            color: var(--white);
            background: var(--brand);
            cursor: pointer;
            font-weight: 800;
            white-space: nowrap;
        }

        .button.secondary {
            color: var(--brand);
            background: #f6e8ee;
        }

        .button.ghost {
            color: #514650;
            background: #f5eff2;
        }

        .status-line {
            margin-top: 12px;
            color: var(--muted);
            line-height: 1.5;
        }

        .status-line.info {
            color: var(--brand);
        }

        .status-line.success {
            color: var(--success);
            font-weight: 800;
        }

        .status-line.error {
            color: #b42318;
            font-weight: 800;
        }

        .status-line.warning {
            color: var(--warning);
            font-weight: 800;
        }

        .result-list {
            display: grid;
            gap: 10px;
            margin-top: 16px;
        }

        .participant-card {
            position: relative;
            border: 1px solid #e8dce2;
            border-radius: 8px;
            padding: 16px;
            background: #fffafd;
        }

        .participant-card.worker-result {
            padding-bottom: 16px;
        }

        .participant-card-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(148px, 1fr));
            gap: 8px;
            margin-top: 14px;
        }

        .participant-card-actions .mini-action {
            width: 100%;
            white-space: normal;
            text-align: center;
            line-height: 1.25;
            padding: 8px 10px;
            min-height: 40px;
        }

        .worker-actions {
            display: contents;
        }

        .participant-identity {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .mini-action {
            min-height: 36px;
            padding: 0 10px;
            border: 0;
            border-radius: 8px;
            color: var(--white);
            background: var(--brand);
            cursor: pointer;
            font-weight: 800;
        }

        .mini-action.warning {
            background: #9b5a09;
        }

        .mini-action.danger {
            background: #b42318;
        }

        .participant-avatar {
            width: 58px;
            height: 58px;
            flex: 0 0 auto;
            border-radius: 8px;
            border: 1px solid #eadde3;
            object-fit: cover;
            background: #f5eff2;
        }

        .participant-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 12px;
        }

        .participant-top h3 {
            margin: 0 0 4px;
            font-size: 1.08rem;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            padding: 0 10px;
            border-radius: 999px;
            color: var(--warning);
            background: #fff3d8;
            font-weight: 800;
            font-size: .82rem;
            white-space: nowrap;
        }

        .badge.ok {
            color: var(--success);
            background: #e7f8ef;
        }

        .badge.pending {
            color: var(--warning);
            background: #fff3d8;
        }

        .badge.danger {
            color: #b42318;
            background: #fee4e2;
        }

        .verify-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 18px;
            border-bottom: 1px solid #eadde3;
            padding-bottom: 12px;
        }

        .verify-tab {
            min-height: 38px;
            padding: 0 14px;
            border: 1px solid #eadde3;
            border-radius: 999px;
            background: #fff;
            color: #514650;
            font-weight: 800;
            cursor: pointer;
        }

        .verify-tab.is-active {
            color: #fff;
            background: var(--brand);
            border-color: var(--brand);
        }

        .verify-tab-panel.hidden { display: none; }

        .attendance-atelier-card {
            margin-bottom: 2rem;
            border: 1px solid #e8dce2;
            border-radius: 12px;
            padding: 16px;
            background: #fff;
        }

        .attendance-atelier-head {
            margin-bottom: 12px;
        }

        .attendance-atelier-head h3 {
            margin: 0 0 4px;
            font-size: 1rem;
        }

        .attendance-atelier-head p {
            margin: 0;
            color: var(--muted);
            font-size: .85rem;
        }

        .mini-action:disabled {
            color: #81747e;
            background: #eee5e9;
            cursor: not-allowed;
        }

        .countdown-note {
            position: absolute;
            left: 16px;
            bottom: 20px;
            max-width: 45%;
            color: var(--warning);
            font-weight: 800;
            font-size: .86rem;
        }

        .meta {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            color: #514650;
            font-size: .92rem;
            line-height: 1.45;
        }

        .meta strong {
            display: block;
            color: var(--ink);
            font-size: .78rem;
            text-transform: uppercase;
        }

        .qr-area {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 260px;
            gap: 16px;
            align-items: start;
            margin-top: 14px;
        }

        video {
            width: 100%;
            min-height: 260px;
            max-height: 360px;
            object-fit: cover;
            border-radius: 8px;
            background: #170d13;
        }

        .chat-window {
            display: grid;
            gap: 10px;
            min-height: 260px;
            max-height: 420px;
            overflow: auto;
            padding: 14px;
            border: 1px solid #e8dce2;
            border-radius: 8px;
            background: #fffafd;
        }

        .message {
            max-width: 84%;
            padding: 12px 14px;
            border-radius: 8px;
            line-height: 1.5;
            background: #f2eaee;
        }

        .message.user {
            justify-self: end;
            color: var(--white);
            background: var(--brand);
        }

        .suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 12px 0;
        }

        .suggestions button {
            min-height: 36px;
            padding: 0 12px;
            border: 1px solid #e1d4da;
            border-radius: 999px;
            color: var(--brand);
            background: var(--white);
            cursor: pointer;
            font-weight: 700;
        }

        @media (max-width: 980px) {
            .options,
            .meta {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .qr-area {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 680px) {
            .topbar,
            .panel-header,
            .participant-top {
                align-items: flex-start;
            }

            .content {
                width: calc(100% - 24px);
                margin-top: -24px;
            }

            .options,
            .form-grid,
            .search-grid,
            .meta {
                grid-template-columns: 1fr;
            }

            .option { min-height: 176px; }
            .message { max-width: 100%; }
        }
    </style>
    @include('filament.partials.cmp-atelier-ui-styles')
</head>
<body>
    @include('retraite-inscription.partials.splash-loader')

    <div class="page portail-page portail-page--booting cmp-page-shell">
        @include('partials.cmp-portail.hero-welcome')

        <main class="content">
            <div class="portal-options-toolbar hidden" id="portalOptionsToolbar">
                <button type="button" class="button ghost" id="portalOptionsReset">← Afficher toutes les options</button>
            </div>

            <section class="options" id="portalOptions" aria-label="Options du portail">
                @if($portalRetreatEvent)
                    <a class="option" href="{{ route('retraite.inscription') }}">
                        <span class="option-index">1</span>
                        <span>
                            <h2>Inscription</h2>
                            <p>Remplir le formulaire public et lancer la validation de participation.</p>
                        </span>
                        <span class="option-action">Commencer <span aria-hidden="true">→</span></span>
                    </a>
                @else
                    <div class="option option--disabled" role="group" aria-disabled="true">
                        <span class="option-index">1</span>
                        <span>
                            <h2>Inscription</h2>
                            <p>
                                @if($portalPublicClosed ?? null)
                                    « {{ $portalPublicClosed->name }} » est clôturée — les inscriptions en ligne ne sont plus disponibles.
                                @elseif($portalInactiveEvent ?? null)
                                    « {{ $portalInactiveEvent->name }} » — événement désactivé, les inscriptions sont suspendues.
                                @else
                                    Aucune retraite ouverte aux inscriptions pour le moment.
                                @endif
                            </p>
                        </span>
                        <span class="option-action">Fermé <span aria-hidden="true">—</span></span>
                    </div>
                @endif

                @if($portalRetreatEvent)
                    <a class="option portal-option-anchor" href="#verification-inscription">
                        <span class="option-index">2</span>
                        <span>
                            <h2>Vérifier une inscription</h2>
                            <p>Accès réservé aux ouvriers avec code OTP envoyé par e-mail.</p>
                        </span>
                        <span class="option-action">Accéder <span aria-hidden="true">→</span></span>
                    </a>
                @else
                    <div class="option option--disabled" role="group" aria-disabled="true">
                        <span class="option-index">2</span>
                        <span>
                            <h2>Vérifier une inscription</h2>
                            <p>
                                @if($portalPublicClosed ?? null)
                                    « {{ $portalPublicClosed->name }} » est clôturée — la vérification ouvrier n'est plus disponible sur le portail public.
                                @elseif($portalInactiveEvent ?? null)
                                    « {{ $portalInactiveEvent->name }} » — événement désactivé, la vérification ouvrier n'est plus disponible.
                                @else
                                    Aucune retraite active pour la vérification ouvrier.
                                @endif
                            </p>
                        </span>
                        <span class="option-action">Fermé <span aria-hidden="true">—</span></span>
                    </div>
                @endif

                @if($portalProgrammeLocked)
                    <div class="option option--disabled" role="group" aria-disabled="true"
                         title="{{ $portalRetreatEvent?->start_at ? 'Disponible à partir du '.$portalRetreatEvent->start_at->format('d/m/Y') : ($portalPublicClosed ? 'Retraite clôturée' : 'Bientôt disponible') }}">
                        <span class="option-index">3</span>
                        <span>
                            <h2>Programme & consignes</h2>
                            <p>
                                @if($portalPublicClosed ?? null)
                                    « {{ $portalPublicClosed->name }} » est clôturée — le programme n'est plus accessible en ligne.
                                @elseif($portalInactiveEvent ?? null)
                                    « {{ $portalInactiveEvent->name }} » — événement désactivé, le programme n'est plus accessible.
                                @elseif($portalRetreatEvent?->start_at)
                                    Ouverture le {{ $portalRetreatEvent->start_at->format('d/m/Y') }} (heure locale).
                                @else
                                    Les informations seront publiées prochainement.
                                @endif
                            </p>
                        </span>
                        <span class="option-action">{{ ($portalPublicClosed ?? null) ? 'Fermé' : 'Bientôt' }} <span aria-hidden="true">—</span></span>
                    </div>
                @else
                    <a class="option portal-option-anchor" href="#programme-consignes">
                        <span class="option-index">3</span>
                        <span>
                            <h2>Programme & consignes</h2>
                            <p>Retrouver les informations importantes de la retraite active.</p>
                        </span>
                        <span class="option-action">Consulter <span aria-hidden="true">→</span></span>
                    </a>
                @endif

                @if($portalDonEvent ?? null)
                    <a class="option" href="{{ route('retraite.don') }}">
                        <span class="option-index">4</span>
                        <span>
                            <h2>Faire un don</h2>
                            <p>
                                @if($portalPublicClosed ?? null)
                                    Soutenir « {{ $portalPublicClosed->name }} » par un don en nature ou en espèces (bon fonctionnement).
                                @elseif($portalDonEvent && ! $portalDonEvent->is_active)
                                    Soutenir « {{ $portalDonEvent->name }} » — inscriptions suspendues, les dons restent possibles.
                                @else
                                    Soutenir la retraite par un don en nature ou en espèces (sponsoriser des jeunes).
                                @endif
                            </p>
                        </span>
                        <span class="option-action">Donner <span aria-hidden="true">→</span></span>
                    </a>
                @else
                    <div class="option option--disabled" role="group" aria-disabled="true">
                        <span class="option-index">4</span>
                        <span>
                            <h2>Faire un don</h2>
                            <p>Les dons en ligne ne sont pas disponibles tant qu'aucune retraite active n'est configurée.</p>
                        </span>
                        <span class="option-action">Fermé <span aria-hidden="true">—</span></span>
                    </div>
                @endif

                @if($portalRetreatEvent)
                    <a class="option portal-option-anchor" href="#assistant-retraite">
                        <span class="option-index">5</span>
                        <span>
                            <h2>Assistant Retraite</h2>
                            <p>Poser une question rapide sur l'inscription, le paiement ou les consignes.</p>
                        </span>
                        <span class="option-action">Discuter <span aria-hidden="true">→</span></span>
                    </a>
                @else
                    <div class="option option--disabled" role="group" aria-disabled="true">
                        <span class="option-index">5</span>
                        <span>
                            <h2>Assistant Retraite</h2>
                            <p>
                                @if($portalPublicClosed ?? null)
                                    « {{ $portalPublicClosed->name }} » est clôturée — l'assistant n'est plus disponible.
                                @elseif($portalInactiveEvent ?? null)
                                    « {{ $portalInactiveEvent->name }} » — événement désactivé, l'assistant n'est plus disponible.
                                @else
                                    L'assistant n'est pas disponible tant qu'aucune retraite active n'est configurée.
                                @endif
                            </p>
                        </span>
                        <span class="option-action">Fermé <span aria-hidden="true">—</span></span>
                    </div>
                @endif
            </section>

            <section id="verification-inscription" class="panel hidden portal-panel" aria-labelledby="verify-title">
                <div class="panel-header">
                    <div>
                        <h2 id="verify-title">Vérification ouvrier</h2>
                        <p>Connectez-vous avec votre e-mail d'ouvrier. Le formulaire de recherche s'affiche après validation du code OTP.</p>
                    </div>
                    <button id="logoutVerifier" class="button ghost hidden" type="button">Déconnexion</button>
                </div>

                <div id="otpStepEmail">
                    <form class="form-grid" id="otpRequestForm">
                        <div>
                            <label for="workerEmail">E-mail ouvrier</label>
                            <input id="workerEmail" name="email" type="email" autocomplete="email" placeholder="nom@exemple.com" required>
                        </div>
                        <button id="otpRequestButton" class="button" type="submit">Recevoir le code</button>
                    </form>
                </div>

                <div id="otpStepCode" class="hidden"></div>

                <div id="verificationWorkspace" class="hidden">
                    <div class="verify-tabs hidden" id="verifyTabs">
                        <button type="button" class="verify-tab is-active" data-verify-tab="search">Recherche inscriptions</button>
                        <button type="button" class="verify-tab hidden" id="verifyTabAttendanceBtn" data-verify-tab="attendance">Pointage atelier</button>
                    </div>

                    <div id="verifyPanelSearch" class="verify-tab-panel" data-verify-panel="search">
                    <form class="search-grid" id="searchForm">
                        <div>
                            <label for="searchMode">Recherche</label>
                            <select id="searchMode" name="mode">
                                <option value="auto">Automatique</option>
                                <option value="reference">Référence</option>
                                <option value="phone">Téléphone</option>
                                <option value="email">Adresse e-mail</option>
                                <option value="name">Nom complet</option>
                            </select>
                        </div>
                        <div>
                            <label for="searchQuery">Critère</label>
                            <input id="searchQuery" name="query" type="search" autocomplete="off" placeholder="Référence, téléphone, e-mail ou nom complet">
                        </div>
                        <button class="button" type="submit" id="searchSubmitBtn">Rechercher</button>
                    </form>

                    <div class="qr-area">
                        <div>
                            <p class="status-line">Scannez le QR du billet ou du justificatif : la caméra s'ouvre puis une fiche participant s'affiche.</p>
                            <div id="qrReader" class="hidden"></div>
                        </div>
                        <div>
                            <button id="startQr" class="button secondary" type="button">Scanner un QR code</button>
                            <button id="stopQr" class="button ghost hidden" type="button">Arrêter le scan</button>
                            <p id="qrStatus" class="status-line">Le scan utilise la caméra uniquement pendant la vérification.</p>
                        </div>
                    </div>

                    <div id="searchStatus" class="status-line"></div>
                    <div id="searchResults" class="result-list"></div>
                    </div>

                    <div id="verifyPanelAttendance" class="verify-tab-panel hidden" data-verify-panel="attendance">
                        <p id="attendanceReadOnlyBanner" class="status-line hidden" style="margin-bottom:12px;padding:10px 12px;border-radius:8px;background:#fff7ed;color:#9a3412;font-weight:600;">
                            Consultation seule — seuls le responsable et l'adjoint de chaque atelier peuvent enregistrer les présences.
                        </p>
                        <div class="mb-6">
                            <label class="mb-1 block text-sm font-semibold" for="attendanceActivitySelect">Activité</label>
                            <select id="attendanceActivitySelect" class="fi-input block w-full max-w-2xl rounded-lg border-gray-300 text-sm">
                                <option value="">— Choisir une activité —</option>
                            </select>
                        </div>
                        <div id="attendanceLoader" class="cmp-atelier-loader hidden" aria-live="polite">
                            <span class="cmp-atelier-spinner"></span>
                            <span>Chargement de votre atelier…</span>
                        </div>
                        <div id="attendanceStatus" class="status-line"></div>
                        <div id="attendanceBlocks"></div>
                    </div>
                </div>
            </section>

            <section id="programme-consignes" class="panel hidden portal-panel" aria-labelledby="program-title">
                <h2 id="program-title">Programme & consignes</h2>
                <p id="programSummary">Chargement des informations de la retraite...</p>
                @if($portalProgrammeLocked && $portalRetreatEvent?->start_at)
                    <p class="status-line warning" style="margin-top:12px;font-weight:700;color:#9b5a09;">
                        Consultation du programme détaillé à partir du {{ $portalRetreatEvent->start_at->format('d/m/Y à H:i') }}.
                    </p>
                @endif
            </section>

            <section id="assistant-retraite" class="panel hidden portal-panel" aria-labelledby="chat-title">
                <h2 id="chat-title">Assistant Retraite</h2>
                <p>Première version encadrée : l'assistant répond à partir des informations publiques configurées pour la retraite.</p>
                <div class="suggestions" id="chatSuggestions">
                    <button type="button" data-question="Quel est le prix de l'inscription ?">Prix</button>
                    <button type="button" data-question="Où se passe la retraite ?">Lieu</button>
                    <button type="button" data-question="Comment fonctionne le paiement ?">Paiement</button>
                    <button type="button" data-question="Quelles sont les consignes importantes ?">Consignes</button>
                    <button type="button" data-question="Je veux vérifier mon inscription">Vérifier mon inscription</button>
                </div>
                <div id="chatWindow" class="chat-window"></div>
                <form class="form-grid" id="chatForm" style="margin-top: 12px;">
                    <input id="chatInput" type="text" autocomplete="off" placeholder="Votre question sur la retraite">
                    <button id="chatSubmitBtn" class="button" type="submit">Envoyer</button>
                </form>
            </section>
        </main>

        @include('partials.cmp-portail.footer')
    </div>

    <div id="otpModal" class="modal-backdrop hidden" role="dialog" aria-modal="true" aria-labelledby="otpModalTitle">
        <div class="modal">
            <div class="modal-header">
                <div>
                    <h2 id="otpModalTitle">Code de vérification</h2>
                    <p>Entrez les 6 chiffres envoyés par e-mail. Le code expire après 5 minutes et la validation se lance automatiquement.</p>
                </div>
                <button id="closeOtpModal" class="modal-close" type="button" aria-label="Fermer">×</button>
            </div>
            <div class="modal-body">
                <div class="otp-boxes" id="otpBoxes" aria-label="Code OTP">
                    <input class="otp-digit" inputmode="numeric" maxlength="1" autocomplete="one-time-code">
                    <input class="otp-digit" inputmode="numeric" maxlength="1">
                    <input class="otp-digit" inputmode="numeric" maxlength="1">
                    <input class="otp-digit" inputmode="numeric" maxlength="1">
                    <input class="otp-digit" inputmode="numeric" maxlength="1">
                    <input class="otp-digit" inputmode="numeric" maxlength="1">
                </div>
                <p id="otpModalStatus" class="status-line">En attente du code.</p>
            </div>
        </div>
    </div>

    <div id="participantLookupModal" class="modal-backdrop hidden" role="dialog" aria-modal="true" aria-labelledby="participantLookupTitle">
        <div class="modal">
            <div class="modal-header">
                <div>
                    <h2 id="participantLookupTitle">Suivre mon inscription</h2>
                    <p>Recherchez votre dossier avec une référence, un téléphone, une adresse e-mail ou votre nom complet.</p>
                </div>
                <button id="closeParticipantLookup" class="modal-close" type="button" aria-label="Fermer">×</button>
            </div>
            <div class="modal-body">
                <form id="participantLookupForm" class="form-grid">
                    <div>
                        <label for="participantLookupQuery">Votre critère</label>
                        <input id="participantLookupQuery" type="search" placeholder="Référence, téléphone, e-mail ou nom complet" autocomplete="off" required>
                    </div>
                    <button id="participantLookupButton" class="button" type="submit">Vérifier</button>
                </form>
                <p id="participantLookupStatus" class="status-line"></p>
                <div id="participantLookupResults" class="result-list"></div>
            </div>
        </div>
    </div>

    <div id="workerParticipantModal" class="modal-backdrop hidden" role="dialog" aria-modal="true" aria-labelledby="workerParticipantModalTitle">
        <div class="modal modal-wide">
            <div class="modal-header">
                <div>
                    <h2 id="workerParticipantModalTitle">Participant scanné</h2>
                    <p id="workerParticipantModalSubtitle">Vérifiez le dossier puis validez l'accès ou la remise du badge.</p>
                </div>
                <button id="closeWorkerParticipantModal" class="modal-close" type="button" aria-label="Fermer">×</button>
            </div>
            <p id="workerParticipantModalStatus" class="worker-modal-status hidden" role="status" aria-live="polite"></p>
            <div class="modal-body" id="workerParticipantModalBody"></div>
            <div class="modal-footer worker-modal-actions" id="workerParticipantModalActions"></div>
        </div>
    </div>

    <script src="{{ asset('cmp-portail/js/portail-splash.js') }}"></script>
    <script src="{{ asset('cmp-portail/js/portail-hero.js') }}"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        const endpoints = {
            status: @json(route('retraite.verification.status')),
            requestOtp: @json(route('retraite.verification.otp.request')),
            verifyOtp: @json(route('retraite.verification.otp.verify')),
            logout: @json(route('retraite.verification.logout')),
            search: @json(route('retraite.verification.search')),
            workerActionTemplate: @json(route('retraite.verification.participants.action', ['participant' => '__ID__'])),
            publicLookup: @json(route('retraite.verification.public.lookup')),
            chatbotContext: @json(route('retraite.verification.chatbot.context')),
            attendanceContext: @json(route('retraite.verification.attendance.context')),
            attendanceBlocks: @json(route('retraite.verification.attendance.blocks')),
            attendanceSet: @json(route('retraite.verification.attendance.set')),
            attendanceExcuse: @json(route('retraite.verification.attendance.excuse')),
            attendanceReportSubmit: @json(route('retraite.verification.attendance.report.submit')),
        };

        let attendanceBlocksCache = [];

        let canViewAtelierAttendance = false;
        let canManageAtelierAttendance = false;
        let attendancePortalReadOnly = false;
        let canManageRegistrations = false;
        let activeVerifyTab = 'search';
        let attendanceActivityId = '';
        let html5QrCode = null;
        let activeWorkerParticipant = null;

        const portalProgrammeLocked = @json($portalProgrammeLocked ?? false);

        const otpStepEmail = document.getElementById('otpStepEmail');
        const otpStepCode = document.getElementById('otpStepCode');
        const workspace = document.getElementById('verificationWorkspace');
        const logoutButton = document.getElementById('logoutVerifier');
        const searchStatus = document.getElementById('searchStatus');
        const searchResults = document.getElementById('searchResults');
        const otpRequestButton = document.getElementById('otpRequestButton');
        const otpModal = document.getElementById('otpModal');
        const otpModalStatus = document.getElementById('otpModalStatus');
        const otpDigits = Array.from(document.querySelectorAll('.otp-digit'));
        let otpEmail = '';
        let otpVerifying = false;
        let chatbotContext = null;

        async function getJson(url) {
            const response = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(payload.message || 'Action impossible pour le moment.');
            }
            return payload;
        }

        async function postJson(url, body = {}) {
            const response = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(body),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(payload.message || 'Action impossible pour le moment.');
            }
            return payload;
        }

        function setVerifierAuthenticated(user, options = {}) {
            canViewAtelierAttendance = !!(options.canViewAtelierAttendance ?? options.canMarkAtelierAttendance);
            canManageAtelierAttendance = !!options.canManageAtelierAttendance;
            attendancePortalReadOnly = canViewAtelierAttendance && !canManageAtelierAttendance;
            canManageRegistrations = !!options.canManageRegistrations;
            otpStepEmail.classList.add('hidden');
            otpStepCode.classList.add('hidden');
            workspace.classList.remove('hidden');
            logoutButton.classList.remove('hidden');
            document.getElementById('verifyTabs')?.classList.remove('hidden');
            document.getElementById('verifyTabAttendanceBtn')?.classList.toggle('hidden', !canViewAtelierAttendance);
            document.getElementById('attendanceReadOnlyBanner')?.classList.toggle('hidden', !attendancePortalReadOnly);
            searchStatus.textContent = user ? `Connecté : ${user.name || user.email}` : 'Connecté.';
            if (canViewAtelierAttendance) {
                loadAttendanceContext();
            }
        }

        function setVerifierGuest(message = '') {
            canViewAtelierAttendance = false;
            canManageAtelierAttendance = false;
            attendancePortalReadOnly = false;
            canManageRegistrations = false;
            activeVerifyTab = 'search';
            otpStepEmail.classList.remove('hidden');
            otpStepCode.classList.add('hidden');
            workspace.classList.add('hidden');
            logoutButton.classList.add('hidden');
            document.getElementById('verifyTabs')?.classList.add('hidden');
            document.getElementById('verifyTabAttendanceBtn')?.classList.add('hidden');
            document.getElementById('attendanceReadOnlyBanner')?.classList.add('hidden');
            document.getElementById('attendanceBlocks').innerHTML = '';
            searchStatus.textContent = message;
            searchResults.innerHTML = '';
            stopQrScanner();
        }

        function switchVerifyTab(tabName) {
            activeVerifyTab = tabName;
            document.querySelectorAll('.verify-tab').forEach((button) => {
                button.classList.toggle('is-active', button.dataset.verifyTab === tabName);
            });
            document.querySelectorAll('[data-verify-panel]').forEach((panel) => {
                panel.classList.toggle('hidden', panel.dataset.verifyPanel !== tabName);
            });
            if (tabName === 'attendance' && attendanceActivityId) {
                loadAttendanceBlocks();
            }
        }

        document.querySelectorAll('.verify-tab').forEach((button) => {
            button.addEventListener('click', () => switchVerifyTab(button.dataset.verifyTab));
        });

        const attendanceStatusLabels = {
            present: 'Présent',
            absent: 'Absent',
            late: 'En retard',
            excused: 'Excusé',
        };
        const attendanceStatusColors = {
            present: '#22c55e',
            absent: '#ef4444',
            late: '#f59e0b',
            excused: '#3b82f6',
        };

        async function loadAttendanceContext() {
            const select = document.getElementById('attendanceActivitySelect');
            if (!select || !canViewAtelierAttendance) return;
            try {
                const payload = await getJson(endpoints.attendanceContext);
                attendancePortalReadOnly = !!payload.read_only;
                document.getElementById('attendanceReadOnlyBanner')?.classList.toggle('hidden', !attendancePortalReadOnly);
                select.innerHTML = '<option value="">— Choisir une activité —</option>';
                (payload.activities || []).forEach((activity) => {
                    const option = document.createElement('option');
                    option.value = activity.id;
                    option.textContent = activity.label;
                    select.appendChild(option);
                });
            } catch (error) {
                setStatusMessage(document.getElementById('attendanceStatus'), error.message, 'error');
                if (String(error.message).includes('Connexion ouvrier')) {
                    setVerifierGuest('Session expirée. Reconnectez-vous pour le pointage atelier.');
                }
            }
        }

        document.getElementById('attendanceActivitySelect')?.addEventListener('change', (event) => {
            attendanceActivityId = event.target.value;
            if (attendanceActivityId) {
                loadAttendanceBlocks();
            } else {
                document.getElementById('attendanceBlocks').innerHTML = '';
            }
        });

        async function loadAttendanceBlocks() {
            const loader = document.getElementById('attendanceLoader');
            const container = document.getElementById('attendanceBlocks');
            const statusEl = document.getElementById('attendanceStatus');
            if (!attendanceActivityId) return;
            loader?.classList.remove('hidden');
            container.innerHTML = '';
            setStatusMessage(statusEl, '', 'info');
            try {
                const payload = await getJson(`${endpoints.attendanceBlocks}?activity_plan_id=${encodeURIComponent(attendanceActivityId)}`);
                renderAttendanceBlocks(payload.data || []);
            } catch (error) {
                setStatusMessage(statusEl, error.message, 'error');
                if (String(error.message).includes('Connexion ouvrier')) {
                    setVerifierGuest('Session expirée. Reconnectez-vous pour le pointage atelier.');
                }
            } finally {
                loader?.classList.add('hidden');
            }
        }

        function renderAttendanceBlocks(blocks) {
            const container = document.getElementById('attendanceBlocks');
            const statusEl = document.getElementById('attendanceStatus');
            attendanceBlocksCache = blocks || [];
            if (!blocks.length) {
                container.innerHTML = '';
                const emptyMessage = attendancePortalReadOnly
                    ? 'Aucun participant enregistré pour cette activité.'
                    : 'Aucun participant dans votre atelier pour cette activité.';
                setStatusMessage(statusEl, emptyMessage, 'warning');
                return;
            }
            setStatusMessage(statusEl, '', 'info');
            container.innerHTML = blocks.map((block) => renderAttendanceBlock(block)).join('');
            bindAttendanceActions(container);
            bindReportActions(container);
        }

        function renderParticipantStatusCells(participant, block) {
            return Object.entries(attendanceStatusLabels).map(([key, label]) => {
                const active = participant.status === key ? 'is-active' : '';
                const disabled = block.can_manage && canManageAtelierAttendance ? '' : 'disabled';
                return `
                    <td class="cmp-status-cell">
                        <button type="button" class="cmp-status-check ${active}" style="--status-color: ${attendanceStatusColors[key]}"
                            data-attendance-set="${participant.id}" data-status="${key}" ${disabled}>
                            <span class="cmp-check-box">${participant.status === key ? '✓' : ''}</span>
                            ${label}
                        </button>
                    </td>`;
            }).join('');
        }

        function renderParticipantExcuseRow(participant, block) {
            if (participant.status !== 'excused') {
                return '';
            }

            return `
                <tr class="cmp-excuse-row cmp-pointage-row" data-excuse-row="${participant.id}">
                    <td colspan="6">
                        <div class="cmp-excuse-field">
                            <label class="cmp-excuse-label">Motif de l'excuse</label>
                            <input type="text" class="cmp-excuse-input" data-excuse-note="${participant.id}"
                                value="${escapeHtml(participant.excuse_note || '')}" placeholder="Indiquez la raison de l'absence excusée"
                                ${block.can_manage && canManageAtelierAttendance ? '' : 'readonly'}>
                        </div>
                    </td>
                </tr>`;
        }

        function renderParticipantRow(participant, block) {
            const meta = participant.recorded_by
                ? `<div class="cmp-pointage-meta">Par ${escapeHtml(participant.recorded_by)}${participant.recorded_at ? ' · ' + escapeHtml(participant.recorded_at) : ''}</div>`
                : '';

            return `
                <tr class="cmp-pointage-row" data-participant-row="${participant.id}">
                    <td style="text-align:center"><span class="cmp-pointage-num">${participant.number}</span></td>
                    <td>
                        <div class="cmp-pointage-name">${escapeHtml(participant.full_name)}</div>
                        ${meta}
                    </td>
                    ${renderParticipantStatusCells(participant, block)}
                </tr>
                ${renderParticipantExcuseRow(participant, block)}`;
        }

        function renderMultiSelectOptions(options, selectedValues) {
            const selected = new Set((selectedValues || []).map(String));
            return (options || []).map((option) => {
                const value = String(option.id ?? option.key);
                const label = option.name ?? option.label;
                const isSelected = selected.has(value) ? 'selected' : '';
                return `<option value="${escapeHtml(value)}" ${isSelected}>${escapeHtml(label)}</option>`;
            }).join('');
        }

        function renderAttendanceReportSection(block) {
            const report = block.report || {};
            const canEditReport = block.can_manage && canManageAtelierAttendance && !report.locked;

            if (!block.can_manage && !report.sujet && !report.locked) {
                return '';
            }

            const lockedBanner = report.locked ? `
                <div class="cmp-report-locked">
                    <span>🔒</span>
                    <span>Compte-rendu soumis${report.submitted_at ? ' le ' + escapeHtml(report.submitted_at) : ''}${report.submitted_by ? ' par ' + escapeHtml(report.submitted_by) : ''} — modification impossible.</span>
                </div>` : '';

            const field = (id, label, color, content) => `
                <div class="cmp-report-field cmp-report-field--full" style="--field-color: ${color}">
                    <label class="cmp-report-label" for="${id}">${label}</label>
                    ${content}
                </div>`;

            const textInput = (name, value, placeholder, rows = null) => {
                if (!canEditReport) {
                    return `<div class="cmp-report-input" readonly>${escapeHtml(value || '—')}</div>`;
                }
                if (rows) {
                    return `<textarea class="cmp-report-input" data-report-field="${name}" rows="${rows}" placeholder="${escapeHtml(placeholder)}">${escapeHtml(value || '')}</textarea>`;
                }
                return `<input type="text" class="cmp-report-input" data-report-field="${name}" value="${escapeHtml(value || '')}" placeholder="${escapeHtml(placeholder)}">`;
            };

            const multiSelect = (name, options, selected, minHeight) => {
                if (!canEditReport) {
                    const labels = (options || [])
                        .filter((option) => (selected || []).map(String).includes(String(option.id ?? option.key)))
                        .map((option) => option.name ?? option.label)
                        .join(', ');
                    return `<div class="cmp-report-input" readonly>${escapeHtml(labels || '—')}</div>`;
                }
                return `<select class="cmp-report-input" data-report-field="${name}" multiple style="min-height: ${minHeight}">${renderMultiSelectOptions(options, selected)}</select>`;
            };

            return `
                <div class="cmp-report-section" data-report-section="${block.atelier_id}">
                    <h4 class="mb-3 text-sm font-bold" style="margin:0 0 .75rem">Compte-rendu de l'activité</h4>
                    ${lockedBanner}
                    <div class="cmp-report-grid">
                        ${field(`report-sujet-${block.atelier_id}`, 'Sujet', '#7b1d3e', textInput('sujet', report.sujet, 'Sujet de l\'activité dans cet atelier'))}
                        ${field(`report-bib-${block.atelier_id}`, 'Texte biblique', '#2563eb', textInput('texte_biblique', report.texte_biblique, 'Références et passages', 2))}
                        <div class="cmp-report-field" style="--field-color: #7c3aed">
                            <label class="cmp-report-label">Conducteur(s) — ouvriers</label>
                            ${multiSelect('conducteur_user_ids', block.worker_options, report.conducteur_user_ids, '5.5rem')}
                        </div>
                        <div class="cmp-report-field" style="--field-color: #ea580c">
                            <label class="cmp-report-label">Conducteur(s) — participants</label>
                            ${multiSelect('conducteur_participant_ids', block.participant_options, report.conducteur_participant_ids, '5.5rem')}
                        </div>
                        ${field(`report-debat-${block.atelier_id}`, 'Conducteur(s) du débat', '#0891b2', multiSelect('conducteur_debat_keys', block.debat_options, report.conducteur_debat_keys, '5.5rem'))}
                        ${field(`report-resume-${block.atelier_id}`, 'Résumé', '#16a34a', textInput('resume', report.resume, 'Résumé de l\'activité de l\'atelier', 3))}
                    </div>
                    ${canEditReport ? `
                        <div class="cmp-report-actions">
                            <button type="button" class="button" data-report-submit="${block.atelier_id}">Soumettre le compte-rendu</button>
                        </div>` : ''}
                </div>`;
        }

        function renderAttendanceBlock(block) {
            const rows = (block.participants || []).map((participant) => renderParticipantRow(participant, block)).join('');

            return `
                <article class="attendance-atelier-card" data-atelier-id="${block.atelier_id}">
                    <div class="attendance-atelier-head">
                        <h3 data-atelier-head-count="${block.atelier_id}">Atelier ${escapeHtml(String(block.atelier_numero))} · ${block.participants_count} membre(s) · ${block.present_count} présent(s)/retard</h3>
                        <p>Responsable : ${escapeHtml(block.responsable || '—')}${block.adjoint ? ' · Adjoint : ' + escapeHtml(block.adjoint) : ''}${!block.can_manage ? ' · <strong>Lecture seule</strong>' : ''}</p>
                    </div>
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
                            <tbody>${rows}</tbody>
                        </table>
                    </div>
                    ${renderAttendanceReportSection(block)}
                </article>`;
        }

        function updateAtelierPresentCount(atelierId, presentCount) {
            const head = document.querySelector(`[data-atelier-head-count="${atelierId}"]`);
            if (!head || presentCount === undefined) {
                return;
            }
            head.textContent = head.textContent.replace(/· \d+ présent\(s\)\/retard/, `· ${presentCount} présent(s)/retard`);
        }

        function refreshReportSection(atelierId) {
            const block = attendanceBlocksCache.find((item) => Number(item.atelier_id) === Number(atelierId));
            const card = document.querySelector(`[data-atelier-id="${atelierId}"]`);
            const section = card?.querySelector(`[data-report-section="${atelierId}"]`);
            if (!block || !card) {
                return;
            }
            const html = renderAttendanceReportSection(block);
            if (section) {
                section.outerHTML = html;
            } else if (html.trim()) {
                card.insertAdjacentHTML('beforeend', html);
            }
            bindReportActions(card);
        }

        function applyAttendanceStatusUpdate(data) {
            if (!data?.participant_id) {
                return;
            }

            const card = document.querySelector(`[data-atelier-id="${data.atelier_id}"]`);
            if (!card) {
                return;
            }

            updateAtelierPresentCount(data.atelier_id, data.present_count);

            const tbody = card.querySelector('tbody');
            const mainRow = card.querySelector(`[data-participant-row="${data.participant_id}"]`);
            if (!mainRow || !tbody) {
                return;
            }

            const block = attendanceBlocksCache.find((item) => Number(item.atelier_id) === Number(data.atelier_id));
            const participant = block?.participants?.find((item) => Number(item.id) === Number(data.participant_id));
            if (!participant) {
                return;
            }

            participant.status = data.status;
            participant.excuse_note = data.excuse_note ?? participant.excuse_note;
            participant.recorded_by = data.recorded_by ?? participant.recorded_by;
            participant.recorded_at = data.recorded_at ?? participant.recorded_at;

            mainRow.querySelectorAll('[data-attendance-set]').forEach((button) => {
                const isActive = button.dataset.status === data.status;
                button.classList.toggle('is-active', isActive);
                const box = button.querySelector('.cmp-check-box');
                if (box) {
                    box.textContent = isActive ? '✓' : '';
                }
            });

            const metaHost = mainRow.querySelector('td:nth-child(2)');
            const nameEl = metaHost?.querySelector('.cmp-pointage-name');
            if (metaHost && nameEl) {
                metaHost.innerHTML = `<div class="cmp-pointage-name">${escapeHtml(participant.full_name)}</div>`;
                if (participant.recorded_by) {
                    metaHost.insertAdjacentHTML('beforeend', `<div class="cmp-pointage-meta">Par ${escapeHtml(participant.recorded_by)}${participant.recorded_at ? ' · ' + escapeHtml(participant.recorded_at) : ''}</div>`);
                }
            }

            let excuseRow = card.querySelector(`[data-excuse-row="${data.participant_id}"]`);
            if (data.status === 'excused') {
                if (!excuseRow) {
                    mainRow.insertAdjacentHTML('afterend', renderParticipantExcuseRow(participant, block || { can_manage: true }));
                    excuseRow = card.querySelector(`[data-excuse-row="${data.participant_id}"]`);
                    const excuseInput = excuseRow?.querySelector('[data-excuse-note]');
                    if (excuseInput) {
                        bindExcuseInput(excuseInput);
                    }
                }
            } else if (excuseRow) {
                excuseRow.remove();
            }
        }

        function bindExcuseInput(input) {
            if (input.dataset.boundExcuse === '1') {
                return;
            }
            input.dataset.boundExcuse = '1';
            input.addEventListener('blur', async () => {
                if (!attendanceActivityId || !canManageAtelierAttendance) {
                    return;
                }
                const participantId = Number(input.dataset.excuseNote);
                try {
                    const payload = await postJson(endpoints.attendanceExcuse, {
                        activity_plan_id: Number(attendanceActivityId),
                        participant_id: participantId,
                        note: input.value,
                    });
                    if (payload.data) {
                        applyAttendanceStatusUpdate(payload.data);
                    }
                } catch (error) {
                    setStatusMessage(document.getElementById('attendanceStatus'), error.message, 'error');
                }
            });
        }

        function collectReportFormData(section) {
            const data = {
                sujet: '',
                texte_biblique: '',
                resume: '',
                conducteur_user_ids: [],
                conducteur_participant_ids: [],
                conducteur_debat_keys: [],
            };

            section.querySelectorAll('[data-report-field]').forEach((field) => {
                const name = field.dataset.reportField;
                if (!name) {
                    return;
                }
                if (field.tagName === 'SELECT' && field.multiple) {
                    data[name] = Array.from(field.selectedOptions).map((option) => {
                        return name === 'conducteur_debat_keys' ? option.value : Number(option.value);
                    });
                    return;
                }
                data[name] = field.value;
            });

            return data;
        }

        function bindReportActions(container) {
            container.querySelectorAll('[data-report-submit]').forEach((button) => {
                if (button.dataset.boundReport === '1') {
                    return;
                }
                button.dataset.boundReport = '1';
                button.addEventListener('click', async () => {
                    if (!attendanceActivityId || !canManageAtelierAttendance) {
                        return;
                    }
                    const atelierId = Number(button.dataset.reportSubmit);
                    const section = container.querySelector(`[data-report-section="${atelierId}"]`);
                    if (!section) {
                        return;
                    }
                    const formData = collectReportFormData(section);
                    if (!formData.sujet?.trim()) {
                        setStatusMessage(document.getElementById('attendanceStatus'), 'Le sujet est obligatoire avant la soumission.', 'warning');
                        return;
                    }
                    if (!window.confirm('Soumettre définitivement ce compte-rendu ? Vous ne pourrez plus le modifier.')) {
                        return;
                    }
                    button.disabled = true;
                    try {
                        const payload = await postJson(endpoints.attendanceReportSubmit, {
                            activity_plan_id: Number(attendanceActivityId),
                            atelier_id: atelierId,
                            ...formData,
                        });
                        const block = attendanceBlocksCache.find((item) => Number(item.atelier_id) === atelierId);
                        if (block && payload.report) {
                            block.report = payload.report;
                            refreshReportSection(atelierId);
                        }
                        setStatusMessage(document.getElementById('attendanceStatus'), payload.message, 'success');
                    } catch (error) {
                        setStatusMessage(document.getElementById('attendanceStatus'), error.message, 'error');
                        button.disabled = false;
                    }
                });
            });
        }

        function bindAttendanceActions(container) {
            if (!canManageAtelierAttendance) {
                return;
            }
            container.querySelectorAll('[data-attendance-set]').forEach((button) => {
                if (button.dataset.boundAttendance === '1') {
                    return;
                }
                button.dataset.boundAttendance = '1';
                button.addEventListener('click', async () => {
                    if (!attendanceActivityId || button.disabled) {
                        return;
                    }
                    const participantId = Number(button.dataset.attendanceSet);
                    const status = button.dataset.status;
                    const card = button.closest('[data-atelier-id]');
                    const excuseInput = card?.querySelector(`[data-excuse-note="${participantId}"]`);
                    button.disabled = true;
                    try {
                        const payload = await postJson(endpoints.attendanceSet, {
                            activity_plan_id: Number(attendanceActivityId),
                            participant_id: participantId,
                            status,
                            excuse_note: excuseInput?.value || null,
                        });
                        if (payload.data) {
                            applyAttendanceStatusUpdate(payload.data);
                        }
                    } catch (error) {
                        setStatusMessage(document.getElementById('attendanceStatus'), error.message, 'error');
                    } finally {
                        button.disabled = false;
                    }
                });
            });

            container.querySelectorAll('[data-excuse-note]').forEach((input) => bindExcuseInput(input));
        }

        function setButtonLoading(button, loading, labelWhenLoading = 'Traitement...') {
            if (!button) return;
            if (loading) {
                button.dataset.originalText = button.textContent;
                button.textContent = labelWhenLoading;
                button.classList.add('loading');
                button.disabled = true;
                return;
            }

            button.textContent = button.dataset.originalText || button.textContent;
            button.classList.remove('loading');
            button.disabled = false;
        }

        function setStatusMessage(element, message, type = 'info') {
            if (!element) return;
            element.textContent = message;
            element.classList.remove('info', 'success', 'error', 'warning');
            element.classList.add(type);
        }

        function openOtpModal() {
            otpModal.classList.remove('hidden');
            setStatusMessage(otpModalStatus, 'Entrez le code reçu par e-mail (6 chiffres, expire dans 5 minutes).', 'info');
            otpDigits.forEach((input) => input.value = '');
            otpDigits[0]?.focus();
        }

        function closeOtpModal() {
            otpModal.classList.add('hidden');
        }

        document.getElementById('closeOtpModal')?.addEventListener('click', closeOtpModal);

        otpDigits.forEach((input, index) => {
            input.addEventListener('input', () => {
                input.value = input.value.replace(/\D+/g, '').slice(0, 1);
                if (input.value && otpDigits[index + 1]) {
                    otpDigits[index + 1].focus();
                }
                maybeVerifyOtpFromBoxes();
            });

            input.addEventListener('keydown', (event) => {
                if (event.key === 'Backspace' && !input.value && otpDigits[index - 1]) {
                    otpDigits[index - 1].focus();
                }
            });

            input.addEventListener('paste', (event) => {
                event.preventDefault();
                const pasted = event.clipboardData.getData('text').replace(/\D+/g, '').slice(0, 6);
                pasted.split('').forEach((digit, digitIndex) => {
                    if (otpDigits[digitIndex]) otpDigits[digitIndex].value = digit;
                });
                otpDigits[Math.min(pasted.length, 5)]?.focus();
                maybeVerifyOtpFromBoxes();
            });
        });

        async function maybeVerifyOtpFromBoxes() {
            const otp = otpDigits.map((input) => input.value).join('');
            if (otp.length < 6 || otpVerifying) return;

            otpVerifying = true;
            otpDigits.forEach((input) => input.disabled = true);
            setStatusMessage(otpModalStatus, 'Vérification du code...', 'info');
            try {
                const payload = await postJson(endpoints.verifyOtp, { email: otpEmail, otp });
                setStatusMessage(otpModalStatus, 'Code validé.', 'success');
                closeOtpModal();
                setVerifierAuthenticated(payload.user, {
                    canViewAtelierAttendance: !!(payload.can_view_atelier_attendance ?? payload.can_mark_atelier_attendance),
                    canManageAtelierAttendance: !!payload.can_manage_atelier_attendance,
                    canManageRegistrations: !!payload.can_manage_registrations,
                });
            } catch (error) {
                setStatusMessage(otpModalStatus, error.message, 'error');
                otpDigits.forEach((input) => input.value = '');
                otpDigits[0]?.focus();
            } finally {
                otpVerifying = false;
                otpDigits.forEach((input) => input.disabled = false);
            }
        }

        document.getElementById('otpRequestForm')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            otpEmail = document.getElementById('workerEmail').value.trim();
            searchStatus.textContent = 'Envoi du code...';
            setButtonLoading(otpRequestButton, true, 'Envoi...');
            openOtpModal();
            setStatusMessage(otpModalStatus, 'Envoi du code par e-mail...', 'info');
            otpDigits.forEach((input) => input.disabled = true);
            try {
                await postJson(endpoints.requestOtp, { email: otpEmail });
                searchStatus.textContent = 'Code envoyé. Vérifiez votre boîte e-mail.';
                setStatusMessage(otpModalStatus, 'Code envoyé. Entrez les 6 chiffres reçus (validité : 5 minutes).', 'success');
                otpDigits.forEach((input) => input.disabled = false);
                otpDigits[0]?.focus();
            } catch (error) {
                searchStatus.textContent = error.message;
                setStatusMessage(otpModalStatus, error.message, 'error');
            } finally {
                setButtonLoading(otpRequestButton, false);
            }
        });

        logoutButton?.addEventListener('click', async () => {
            setButtonLoading(logoutButton, true, 'Déconnexion…');
            try {
                await postJson(endpoints.logout);
            } finally {
                setButtonLoading(logoutButton, false);
                setVerifierGuest('Déconnecté.');
            }
        });

        document.getElementById('searchForm')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const query = document.getElementById('searchQuery').value.trim();
            const mode = document.getElementById('searchMode').value;
            if (!query) return;
            await runSearch(query, mode);
        });

        async function runSearch(query, mode = 'auto', options = {}) {
            const searchSubmitBtn = document.getElementById('searchSubmitBtn');
            searchStatus.textContent = 'Recherche en cours...';
            if (!options.keepResults) {
                searchResults.innerHTML = '';
            }
            setButtonLoading(searchSubmitBtn, true, 'Recherche…');
            try {
                const payload = await postJson(endpoints.search, { query, mode });
                const participants = payload.data || [];
                if (options.openModalOnSingle && participants.length === 1) {
                    searchStatus.textContent = 'Participant identifié via QR code.';
                    openWorkerParticipantModal(participants[0]);
                    return participants;
                }
                renderParticipants(participants);
                return participants;
            } catch (error) {
                searchResults.innerHTML = '';
                searchStatus.textContent = error.message || 'Recherche impossible.';
                searchStatus.classList.add('error');
                if (mode === 'qr') {
                    document.getElementById('qrStatus').textContent = error.message || 'QR code refusé.';
                }
                if (String(error.message).includes('Connexion ouvrier')) {
                    setVerifierGuest('Session expirée. Reconnectez-vous avec votre e-mail ouvrier.');
                }
                return [];
            } finally {
                setButtonLoading(searchSubmitBtn, false);
            }
        }

        function renderRegistrationBadges(participant) {
            const regClass = participant.registration_validated ? 'ok' : 'pending';
            const regLabel = participant.registration_validated ? 'Inscription validée' : 'Inscription à valider';
            const billetClass = participant.billet_envoye ? 'ok' : 'pending';
            const billetLabel = participant.billet_envoye ? 'Billet envoyé' : 'Billet non envoyé';
            const badgeClass = participant.badge_received ? 'ok' : 'pending';
            const badgeLabel = participant.badge_received ? 'Badge remis' : 'Badge non remis';

            return `
                <div class="registration-badges">
                    <span class="badge ${regClass}">${regLabel}</span>
                    <span class="badge ${billetClass}">${billetLabel}</span>
                    <span class="badge ${badgeClass}">${badgeLabel}</span>
                </div>
            `;
        }

        function renderParticipantActionButtons(participant) {
            const paid = participant.paiement_valide;
            const enabled = !!participant.actions_enabled;
            const canAdmin = canManageRegistrations || participant.can_manage_registrations;
            const buttons = [];

            if (canAdmin) {
                const needsValidation = !participant.registration_validated;
                const canSendBillet = !!participant.paiement_valide;
                const badgePending = participant.paiement_valide && !participant.badge_received;
                const badgeDisabled = badgePending ? '' : (participant.badge_received ? 'disabled' : '');
                buttons.push(`<button class="mini-action" data-worker-action="validate_registration" data-participant-id="${participant.id}" ${needsValidation ? '' : 'disabled'}>Valider inscription</button>`);
                buttons.push(`<button class="mini-action" data-worker-action="send_billet" data-participant-id="${participant.id}" ${canSendBillet ? '' : 'disabled'}>${participant.billet_envoye ? 'Renvoyer billet' : 'Envoyer billet'}</button>`);
                buttons.push(`<button class="mini-action" data-worker-action="mark_badge_received" data-participant-id="${participant.id}" ${badgeDisabled}>${participant.badge_received ? 'Badge remis' : 'Marquer badge'}</button>`);
            }

            buttons.push(`<button class="mini-action" data-worker-action="retreat_access" data-participant-id="${participant.id}" ${enabled ? '' : 'disabled'}>Accès retraite</button>`);
            buttons.push(`<button class="mini-action" data-worker-action="activity_access" data-participant-id="${participant.id}" ${enabled ? '' : 'disabled'}>Accès activité</button>`);
            buttons.push(`<button class="mini-action warning" data-worker-action="exit_permission" data-participant-id="${participant.id}" ${enabled ? '' : 'disabled'}>Sortie</button>`);
            if (!canAdmin) {
                buttons.push(`<button class="mini-action" data-worker-action="mark_badge_received" data-participant-id="${participant.id}" ${paid && !participant.badge_received ? '' : 'disabled'}>Badge remis</button>`);
            }
            buttons.push(`<button class="mini-action danger" data-worker-action="exclude_retreat" data-participant-id="${participant.id}" ${enabled ? '' : 'disabled'}>Exclure</button>`);

            if (!buttons.length) {
                return '';
            }

            return `<div class="participant-card-actions">${buttons.join('')}</div>`;
        }

        function renderAdminRegistrationActions(participant) {
            return '';
        }

        function renderWorkerParticipantModalContent(participant) {
            const payment = participant.payment || {};
            const status = getWorkerStatus(participant);
            const badgeLabel = participant.badge_received
                ? 'Badge remis'
                : (participant.paiement_valide ? 'Badge en attente' : 'Paiement requis');
            return `
                <div class="participant-top">
                    <div class="participant-identity">
                        <img class="participant-avatar" src="${escapeHtml(participant.photo_url || '')}" alt="">
                        <div>
                            <h3>${escapeHtml(participant.full_name || 'Participant')}</h3>
                            <p>${escapeHtml(participant.event?.name || 'Retraite')}</p>
                        </div>
                    </div>
                    <span class="badge ${status.className}">${escapeHtml(status.label)}</span>
                </div>
                <div class="worker-modal-meta">
                    <span><strong>Téléphone</strong>${escapeHtml(participant.telephone || '—')}</span>
                    <span><strong>E-mail</strong>${escapeHtml(participant.email || '—')}</span>
                    <span><strong>Référence</strong>${escapeHtml(payment.reference || '—')}</span>
                    <span><strong>Paiement</strong>${participant.paiement_valide ? 'Validé' : 'En attente'}</span>
                    <span><strong>Chambre</strong>${escapeHtml(participant.chambre || '—')}</span>
                    <span><strong>Atelier</strong>${escapeHtml(String(participant.atelier || '—'))}</span>
                    <span><strong>Badge</strong>${escapeHtml(badgeLabel)}</span>
                    <span><strong>Présence</strong>${participant.present ? 'Oui' : 'Non'}</span>
                </div>
                ${renderRegistrationBadges(participant)}
            `;
        }

        function setWorkerModalStatus(message = '', type = 'info') {
            const el = document.getElementById('workerParticipantModalStatus');
            if (!el) {
                return;
            }
            if (!message) {
                el.textContent = '';
                el.className = 'worker-modal-status hidden';
                return;
            }
            el.textContent = message;
            el.className = `worker-modal-status ${type}`;
        }

        function renderWorkerModalActions(participant) {
            const canAdmin = canManageRegistrations || participant.can_manage_registrations;
            const eventStarted = !!participant.actions_enabled || !!participant.event?.has_started;
            const paid = !!participant.paiement_valide;
            const hasAccess = !!participant.present;
            const hasBadge = !!participant.badge_received;
            const fullyDone = hasAccess && hasBadge;

            if (canAdmin) {
                const buttons = [];

                if (!participant.registration_validated) {
                    buttons.push(`<button type="button" class="button" data-worker-action="validate_registration" data-participant-id="${participant.id}">Valider inscription</button>`);
                }

                if (paid) {
                    buttons.push(`<button type="button" class="button secondary" data-worker-action="send_billet" data-participant-id="${participant.id}">${participant.billet_envoye ? 'Renvoyer billet' : 'Envoyer billet'}</button>`);
                }

                if (eventStarted && paid && !hasAccess) {
                    buttons.push(`<button type="button" class="button" data-worker-action="retreat_access" data-participant-id="${participant.id}">Donner accès retraite</button>`);
                }

                if (eventStarted && hasAccess && !hasBadge && paid) {
                    buttons.push(`<button type="button" class="button secondary" data-worker-action="mark_badge_received" data-participant-id="${participant.id}">Marquer badge remis</button>`);
                }

                if (fullyDone) {
                    return '<p class="worker-modal-note">Accès et badge déjà traités pour ce participant.</p>';
                }

                if (!eventStarted && !buttons.length) {
                    return '<p class="worker-modal-note">Aucune action admin disponible pour le moment.</p>';
                }

                if (!eventStarted && buttons.length) {
                    return buttons.join('') + '<p class="worker-modal-note">Remise du badge disponible au début de la retraite, après l\'accès.</p>';
                }

                return buttons.join('') || '<p class="worker-modal-note">Toutes les étapes admin sont complètes.</p>';
            }

            if (fullyDone) {
                return '<p class="worker-modal-note">Participant déjà entré et badge déjà remis.</p>';
            }

            if (!eventStarted) {
                return '<p class="worker-modal-note">Les actions d\'accès et de badge seront disponibles lorsque la retraite aura commencé.</p>';
            }

            if (!paid) {
                return '<p class="worker-modal-note">Paiement non validé — accès impossible.</p>';
            }

            if (!hasAccess) {
                return `<button type="button" class="button" data-worker-action="retreat_access" data-participant-id="${participant.id}">Donner accès retraite</button>`;
            }

            if (!hasBadge) {
                return `
                    <p class="worker-modal-note">Accès retraite déjà accordé.</p>
                    <button type="button" class="button secondary" data-worker-action="mark_badge_received" data-participant-id="${participant.id}">Marquer badge remis</button>
                `;
            }

            return '<p class="worker-modal-note">Badge déjà remis.</p>';
        }

        function openWorkerParticipantModal(participant, statusMessage = '', statusType = 'info') {
            activeWorkerParticipant = participant;
            document.getElementById('workerParticipantModalBody').innerHTML = renderWorkerParticipantModalContent(participant);
            document.getElementById('workerParticipantModalActions').innerHTML = renderWorkerModalActions(participant);
            setWorkerModalStatus(statusMessage, statusType);
            document.getElementById('workerParticipantModal').classList.remove('hidden');
        }

        function closeWorkerParticipantModal() {
            document.getElementById('workerParticipantModal').classList.add('hidden');
            setWorkerModalStatus('');
            activeWorkerParticipant = null;
        }

        document.getElementById('closeWorkerParticipantModal')?.addEventListener('click', closeWorkerParticipantModal);

        document.getElementById('workerParticipantModalActions')?.addEventListener('click', async (event) => {
            const button = event.target.closest('[data-worker-action]');
            if (!activeWorkerParticipant || !button || button.disabled) {
                return;
            }

            const action = button.dataset.workerAction;
            const original = button.textContent;
            button.disabled = true;
            button.textContent = '...';

            try {
                const url = endpoints.workerActionTemplate.replace('__ID__', encodeURIComponent(activeWorkerParticipant.id));
                const payload = await postJson(url, { action });
                const message = payload.message || 'Action effectuée.';
                searchStatus.textContent = message;
                if (payload.data) {
                    openWorkerParticipantModal(payload.data, message, 'success');
                } else {
                    setWorkerModalStatus(message, 'success');
                }
            } catch (error) {
                const message = error.message || 'Action impossible.';
                searchStatus.textContent = message;
                setWorkerModalStatus(message, 'error');
                button.disabled = false;
                button.textContent = original;
            }
        });

        function renderParticipants(participants) {
            if (!participants.length) {
                searchStatus.textContent = 'Aucune inscription trouvée.';
                return;
            }
            searchStatus.textContent = `${participants.length} résultat(s) trouvé(s).`;
            searchResults.innerHTML = participants.map((participant) => {
                const paid = participant.paiement_valide;
                const payment = participant.payment || {};
                const enabled = !!participant.actions_enabled;
                const status = getWorkerStatus(participant);
                const countdown = enabled ? '' : getCountdownText(participant.actions_available_at);
                return `
                    <article class="participant-card worker-result" data-participant-id="${participant.id}">
                        <div class="participant-top">
                            <div class="participant-identity">
                                <img class="participant-avatar" src="${escapeHtml(participant.photo_url || '')}" alt="">
                                <div>
                                    <h3>${escapeHtml(participant.full_name || 'Participant')}</h3>
                                    <p>${escapeHtml(participant.event?.name || 'Retraite')} ${participant.event?.start_at ? '- ' + new Date(participant.event.start_at).toLocaleDateString('fr-FR') : ''}</p>
                                </div>
                            </div>
                            <span class="badge ${status.className}">${escapeHtml(status.label)}</span>
                        </div>
                        <div class="meta">
                            <span><strong>Téléphone</strong>${escapeHtml(participant.telephone || '-')}</span>
                            <span><strong>E-mail</strong>${escapeHtml(participant.email || '-')}</span>
                            <span><strong>Référence</strong>${escapeHtml(payment.reference || '-')}</span>
                            <span><strong>Statut</strong>${escapeHtml(participant.registration_status || '-')}</span>
                            <span><strong>Chambre</strong>${escapeHtml(participant.chambre || '-')}</span>
                            <span><strong>Atelier</strong>${escapeHtml(String(participant.atelier || '-'))}</span>
                        </div>
                        ${renderRegistrationBadges(participant)}
                        ${participant.justificatif_url ? `<p class="status-line"><a href="${participant.justificatif_url}" target="_blank" rel="noopener">Ouvrir le justificatif</a></p>` : ''}
                        ${!enabled && countdown ? `<p class="countdown-note" data-countdown-to="${escapeHtml(participant.actions_available_at)}">${escapeHtml(countdown)}</p>` : ''}
                        ${renderParticipantActionButtons(participant)}
                    </article>
                `;
            }).join('');
        }

        function getWorkerStatus(participant) {
            if (!participant.paiement_valide) return { label: 'Paiement à vérifier', className: 'pending' };
            if (participant.present) return { label: 'Présent', className: 'ok' };
            if (participant.registration_status === 'completed') return { label: 'Inscription validée', className: 'ok' };
            if (participant.registration_status === 'rejected') return { label: 'Refusée', className: 'danger' };
            return { label: participant.registration_status || 'Enregistrée', className: 'pending' };
        }

        function getCountdownText(dateValue) {
            if (!dateValue) return 'Actions disponibles au début de la retraite.';
            const diff = new Date(dateValue).getTime() - Date.now();
            if (diff <= 0) return 'Actions disponibles après actualisation.';
            const days = Math.floor(diff / 86400000);
            const hours = Math.floor((diff % 86400000) / 3600000);
            const minutes = Math.floor((diff % 3600000) / 60000);
            return `Actions dans ${days}j ${hours}h ${minutes}min`;
        }

        searchResults?.addEventListener('click', async (event) => {
            const button = event.target.closest('[data-worker-action]');
            if (!button || button.disabled) return;

            const participantId = button.dataset.participantId;
            const action = button.dataset.workerAction;
            const original = button.textContent;
            button.disabled = true;
            button.textContent = '...';

            try {
                const url = endpoints.workerActionTemplate.replace('__ID__', encodeURIComponent(participantId));
                const payload = await postJson(url, { action });
                searchStatus.textContent = payload.message || 'Action effectuée.';
                const currentQuery = document.getElementById('searchQuery').value.trim();
                const currentMode = document.getElementById('searchMode').value;
                if (currentQuery) await runSearch(currentQuery, currentMode);
            } catch (error) {
                searchStatus.textContent = error.message;
                button.disabled = false;
                button.textContent = original;
            }
        });

        document.getElementById('startQr')?.addEventListener('click', startQrScanner);
        document.getElementById('stopQr')?.addEventListener('click', stopQrScanner);

        async function startQrScanner() {
            const readerEl = document.getElementById('qrReader');
            const status = document.getElementById('qrStatus');
            const startBtn = document.getElementById('startQr');

            if (typeof Html5Qrcode !== 'function') {
                status.textContent = 'Le module de lecture QR est en cours de chargement. Réessayez.';
                return;
            }

            setButtonLoading(startBtn, true, 'Ouverture caméra…');
            try {
                readerEl.classList.remove('hidden');
                document.getElementById('startQr').classList.add('hidden');
                document.getElementById('stopQr').classList.remove('hidden');

                html5QrCode = new Html5Qrcode('qrReader');
                await html5QrCode.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: { width: 250, height: 250 } },
                    (decodedText) => {
                        status.textContent = 'QR code détecté, recherche…';
                        stopQrScanner();
                        runSearch(decodedText, 'qr', { openModalOnSingle: true });
                    },
                    () => {}
                );
                status.textContent = 'Cadrez le QR code dans la caméra…';
            } catch (error) {
                status.textContent = 'Impossible d\'ouvrir la caméra. Autorisez l\'accès dans les paramètres du navigateur.';
                readerEl.classList.add('hidden');
            } finally {
                setButtonLoading(startBtn, false);
            }
        }

        async function stopQrScanner() {
            const readerEl = document.getElementById('qrReader');
            if (html5QrCode) {
                try {
                    await html5QrCode.stop();
                } catch (error) {
                    // ignore stop errors when scanner already stopped
                }
                try {
                    html5QrCode.clear();
                } catch (error) {
                    // ignore clear errors
                }
                html5QrCode = null;
            }
            readerEl?.classList.add('hidden');
            document.getElementById('startQr')?.classList.remove('hidden');
            document.getElementById('stopQr')?.classList.add('hidden');
            const startBtn = document.getElementById('startQr');
            if (startBtn) {
                setButtonLoading(startBtn, false);
            }
        }

        async function bootVerifier() {
            try {
                const response = await fetch(endpoints.status, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const payload = await response.json();
                payload.authenticated
                    ? setVerifierAuthenticated(payload.user, {
                        canViewAtelierAttendance: !!(payload.can_view_atelier_attendance ?? payload.can_mark_atelier_attendance),
                        canManageAtelierAttendance: !!payload.can_manage_atelier_attendance,
                        canManageRegistrations: !!payload.can_manage_registrations,
                    })
                    : setVerifierGuest();
            } catch (error) {
                setVerifierGuest();
            }
        }

        function openParticipantLookupModal(prefill = '') {
            document.getElementById('participantLookupModal').classList.remove('hidden');
            const input = document.getElementById('participantLookupQuery');
            input.value = prefill;
            setStatusMessage(document.getElementById('participantLookupStatus'), '', 'info');
            document.getElementById('participantLookupResults').innerHTML = '';
            input.focus();
        }

        function closeParticipantLookupModal() {
            document.getElementById('participantLookupModal').classList.add('hidden');
        }

        document.getElementById('closeParticipantLookup')?.addEventListener('click', closeParticipantLookupModal);

        document.getElementById('participantLookupForm')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const query = document.getElementById('participantLookupQuery').value.trim();
            const button = document.getElementById('participantLookupButton');
            const status = document.getElementById('participantLookupStatus');
            const results = document.getElementById('participantLookupResults');
            if (!query) return;

            setButtonLoading(button, true, 'Recherche...');
            setStatusMessage(status, 'Recherche de votre inscription...', 'info');
            results.innerHTML = '';
            try {
                const payload = await postJson(endpoints.publicLookup, { query, mode: 'auto' });
                renderPublicLookupResults(payload.data || []);
            } catch (error) {
                setStatusMessage(status, error.message, 'error');
            } finally {
                setButtonLoading(button, false);
            }
        });

        function renderPublicLookupResults(items) {
            const status = document.getElementById('participantLookupStatus');
            const results = document.getElementById('participantLookupResults');
            if (!items.length) {
                setStatusMessage(status, 'Aucune inscription trouvée avec ces informations.', 'warning');
                return;
            }

            setStatusMessage(status, `${items.length} dossier(s) trouvé(s).`, 'success');
            results.innerHTML = items.map((item) => {
                const completed = item.status === 'completed';
                const badgeClass = completed ? 'ok' : (item.status === 'rejected' ? 'danger' : 'pending');
                const eventDate = item.event?.start_at ? new Date(item.event.start_at).toLocaleDateString('fr-FR') : 'date à confirmer';
                const canResumePayment = item.can_resume_payment === true && !!item.resume_payment_url;
                return `
                    <article class="participant-card">
                        <div class="participant-top">
                            <div class="participant-identity">
                                <img class="participant-avatar" src="${escapeHtml(item.photo_url || '')}" alt="">
                                <div>
                                    <h3>${escapeHtml(item.full_name || 'Inscription')}</h3>
                                    <p>${escapeHtml(item.event?.name || 'Retraite')} - ${eventDate}</p>
                                </div>
                            </div>
                            <span class="badge ${badgeClass}">${escapeHtml(item.status_label || 'Enregistrée')}</span>
                        </div>
                        <div class="meta">
                            <span><strong>Référence</strong>${escapeHtml(item.payment?.reference || '-')}</span>
                            <span><strong>Paiement</strong>${escapeHtml(item.payment?.etat || '-')}</span>
                            <span><strong>Montant</strong>${escapeHtml(item.payment ? `${item.payment.amount_expected || '-'} ${item.payment.currency || ''}` : '-')}</span>
                        </div>
                        ${item.justificatif_url ? `<p class="status-line"><a class="button secondary" href="${item.justificatif_url}" target="_blank" rel="noopener">Télécharger mes informations</a></p>` : '<p class="status-line">Le dossier existe, mais le justificatif n’est pas encore disponible.</p>'}
                        ${canResumePayment ? `<p class="status-line"><a class="button" href="${escapeHtml(item.resume_payment_url)}">Reprendre au paiement / changer le moyen</a></p>` : ''}
                    </article>
                `;
            }).join('');
        }

        function escapeHtml(value) {
            return String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        async function loadChatbotContext() {
            try {
                const response = await fetch(endpoints.chatbotContext, { headers: { 'Accept': 'application/json' } });
                const payload = await response.json();
                chatbotContext = payload.data || {};
            } catch (error) {
                chatbotContext = { event: null, policies: [] };
            }

            renderProgramSummary();
            addBotMessage('Bonjour. Je peux vous aider sur l’inscription, le paiement, le lieu, le prix et les consignes de la retraite.');
        }

        function renderProgramSummary() {
            const target = document.getElementById('programSummary');
            if (!target) return;

            if (portalProgrammeLocked) {
                return;
            }

            const event = chatbotContext?.event;
            if (!event) {
                target.textContent = 'Aucune retraite active n’est configurée pour le moment.';
                return;
            }

            const date = event.start_at ? new Date(event.start_at).toLocaleDateString('fr-FR') : 'date à confirmer';
            const price = event.price_to_pay ? `${event.price_to_pay} ${event.currency || ''}` : 'montant à confirmer';
            target.textContent = `${event.name} - ${event.theme || 'thème à confirmer'} - ${event.location || 'lieu à confirmer'} - ${date} - Participation : ${price}.`;
        }

        document.getElementById('chatForm')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const input = document.getElementById('chatInput');
            const chatSubmitBtn = document.getElementById('chatSubmitBtn');
            const question = input.value.trim();
            if (!question) return;
            input.value = '';
            addUserMessage(question);
            setButtonLoading(chatSubmitBtn, true, 'Envoi…');
            try {
                await Promise.resolve();
                handleChatQuestion(question);
            } finally {
                setButtonLoading(chatSubmitBtn, false);
            }
        });

        document.getElementById('chatSuggestions')?.addEventListener('click', (event) => {
            const button = event.target.closest('button[data-question]');
            if (!button) return;
            const question = button.dataset.question;
            addUserMessage(question);
            handleChatQuestion(question);
        });

        function handleChatQuestion(question) {
            let answer;
            try {
                answer = answerRetreatQuestion(question);
            } catch (error) {
                answer = {
                    text: 'Je peux vous aider, mais une information de la retraite n’est pas encore disponible. Essayez une question sur l’inscription, le paiement ou la vérification du dossier.',
                };
            }

            addBotMessage(answer.text || 'Je peux vous aider sur l’inscription, le paiement et la vérification de dossier.');

            if (answer.action === 'registration') {
                addBotAction('Ouvrir le formulaire d’inscription', @json(route('retraite.inscription')));
            }

            if (answer.action === 'lookup') {
                addBotInlineButton('Vérifier mon inscription', () => openParticipantLookupModal());
            }
        }

        function addUserMessage(text) {
            appendMessage(text, 'user');
        }

        function addBotMessage(text) {
            appendMessage(text, 'bot');
        }

        function addBotAction(label, href) {
            const chat = document.getElementById('chatWindow');
            const link = document.createElement('a');
            link.className = 'button secondary';
            link.href = href;
            link.textContent = label;
            link.style.width = 'fit-content';
            chat.appendChild(link);
            chat.scrollTop = chat.scrollHeight;
        }

        function addBotInlineButton(label, handler) {
            const chat = document.getElementById('chatWindow');
            const button = document.createElement('button');
            button.className = 'button secondary';
            button.type = 'button';
            button.textContent = label;
            button.style.width = 'fit-content';
            button.addEventListener('click', handler);
            chat.appendChild(button);
            chat.scrollTop = chat.scrollHeight;
        }

        function appendMessage(text, type) {
            const chat = document.getElementById('chatWindow');
            const node = document.createElement('div');
            node.className = `message ${type === 'user' ? 'user' : ''}`;
            node.textContent = text;
            chat.appendChild(node);
            chat.scrollTop = chat.scrollHeight;
        }

        function answerRetreatQuestion(question) {
            const q = question.toLowerCase();
            const qn = q.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            const event = chatbotContext?.event;
            const policies = chatbotContext?.policies || [];

            if (!event) {
                return { text: 'Je ne trouve pas encore de retraite active configurée. Réessayez plus tard ou contactez l’organisation.' };
            }

            if (
                qn.includes('verifier mon inscription') ||
                qn.includes('verification inscription') ||
                qn.includes('suivre mon inscription') ||
                qn.includes('mon dossier') ||
                (qn.includes('inscription') && (qn.includes('verif') || qn.includes('statut') || qn.includes('passee') || qn.includes('valide')))
            ) {
                return { text: 'Je peux vous aider à retrouver votre dossier. Saisissez votre référence, téléphone, e-mail ou nom complet dans la fenêtre de suivi.', action: 'lookup' };
            }

            if (q.includes('prix') || q.includes('montant') || q.includes('payer')) {
                return { text: `Le montant configuré est ${event.price_to_pay || 'à confirmer'} ${event.currency || ''}. Le paiement se finalise après l’inscription.`, action: 'registration' };
            }

            if (q.includes('lieu') || q.includes('où') || q.includes('adresse')) {
                return { text: `Le lieu configuré est : ${event.location || 'à confirmer par l’organisation'}.` };
            }

            if (q.includes('date') || q.includes('quand') || q.includes('début')) {
                const start = event.start_at ? new Date(event.start_at).toLocaleString('fr-FR') : 'à confirmer';
                const end = event.end_at ? new Date(event.end_at).toLocaleString('fr-FR') : 'à confirmer';
                return { text: `La retraite commence le ${start} et se termine le ${end}.` };
            }

            if (q.includes('paiement') || q.includes('mobile') || q.includes('carte')) {
                return { text: 'Après l’inscription, vous pouvez suivre les moyens de paiement proposés sur le formulaire. Gardez votre référence de paiement pour la vérification.', action: 'registration' };
            }

            if (q.includes('consigne') || q.includes('règle') || q.includes('reglement') || q.includes('règlement')) {
                if (!policies.length) return { text: 'Les consignes ne sont pas encore publiées sur le portail.' };
                return { text: 'Consignes principales : ' + policies.slice(0, 3).map((policy) => policy.title).join(' ; ') + '.' };
            }

            if (q.includes('inscription') || q.includes('inscrire') || q.includes('formulaire')) {
                if (
                    q.includes('pass') ||
                    q.includes('statut') ||
                    q.includes('enregistr') ||
                    q.includes('valid') ||
                    q.includes('suivre') ||
                    q.includes('verif') ||
                    q.includes('vérif') ||
                    q.includes('verification') ||
                    q.includes('vérification')
                ) {
                    return { text: 'Je peux vous aider à retrouver votre dossier sans passer par l’espace ouvrier. Utilisez votre référence, téléphone, e-mail ou nom complet.', action: 'lookup' };
                }

                return { text: 'Pour vous inscrire, ouvrez le formulaire, remplissez vos informations puis terminez l’étape de paiement.', action: 'registration' };
            }

            if (q.includes('référence') || q.includes('reference') || q.includes('statut') || q.includes('dossier') || q.includes('en cours') || q.includes('validé') || q.includes('valide')) {
                return { text: 'Pour connaître l’état de votre inscription, ouvrez la vérification participant et saisissez votre référence, téléphone, e-mail ou nom complet.', action: 'lookup' };
            }

            return { text: 'Je peux répondre sur le prix, le lieu, les dates, le paiement, l’inscription et les consignes. Pour vérifier votre propre dossier, utilisez le bouton de suivi participant.', action: 'lookup' };
        }

        (function wirePortalOptionsNav() {
            const root = document.getElementById('portalOptions');
            const toolbar = document.getElementById('portalOptionsToolbar');
            const resetBtn = document.getElementById('portalOptionsReset');
            if (!root || !toolbar || !resetBtn) return;
            const panels = Array.from(document.querySelectorAll('.portal-panel'));

            const showOnlyPanel = (targetSelector) => {
                panels.forEach((panel) => panel.classList.add('hidden'));
                const target = document.querySelector(targetSelector);
                if (target) target.classList.remove('hidden');
                return target;
            };

            panels.forEach((panel) => panel.classList.add('hidden'));

            root.querySelectorAll('.portal-option-anchor').forEach((el) => {
                el.addEventListener('click', (e) => {
                    const href = el.getAttribute('href');
                    if (!href || !href.startsWith('#')) return;
                    e.preventDefault();
                    root.classList.add('options--focus-mode');
                    root.querySelectorAll('.option').forEach((card) => {
                        card.classList.toggle('portal-option-hidden', card !== el);
                    });
                    toolbar.classList.remove('hidden');
                    const targetPanel = showOnlyPanel(href);
                    targetPanel?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });

            resetBtn.addEventListener('click', () => {
                root.classList.remove('options--focus-mode');
                root.querySelectorAll('.option').forEach((card) => card.classList.remove('portal-option-hidden'));
                toolbar.classList.add('hidden');
                panels.forEach((panel) => panel.classList.add('hidden'));
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        })();

        try {
            bootVerifier();
        } catch (error) {
            setVerifierGuest();
        }

        try {
            loadChatbotContext();
        } catch (error) {
            chatbotContext = { event: null, policies: [] };
            addBotMessage('Bonjour. Je peux vous aider sur l’inscription, le paiement et la vérification de votre dossier.');
        }
    </script>
</body>
</html>
