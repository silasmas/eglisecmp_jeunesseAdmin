@extends('layouts.retraite-inscription')

@section('title', 'Don volontaire — Retraite CMP')
@section('meta_description', 'Faire un don en nature ou en espèces pour soutenir la Grande Retraite de la jeunesse CMP.')

@push('styles')
  <link rel="stylesheet" href="{{ asset('retraite-don/css/don.css') }}?v=5">
@endpush

@section('content')
  <div class="don-page-wrap">
  <header class="hero hero--compact">
    <div class="hero-content">
      <a href="{{ url('/') }}" class="don-back-link"><i class="bi bi-arrow-left"></i> Portail retraite</a>
      <h1 class="hero-title">Don volontaire pour la retraite</h1>
      <p class="hero-subtitle">Soutenez la retraite par un don en nature ou en espèces.</p>
      <p id="donCapacityBanner" class="hero-places-muted hidden" aria-live="polite"></p>
    </div>
    <div class="hero-wave" aria-hidden="true">
      <svg viewBox="0 0 1440 70" preserveAspectRatio="none"><path fill="#FAF8F6" d="M0,40 C360,80 720,0 1080,40 C1260,60 1380,50 1440,45 L1440,70 L0,70 Z"/></svg>
    </div>
  </header>

  <div class="app-layout don-layout-single">
    <main class="content-area">
      <section class="step active" id="donStep">
        <div class="step-header">
          <div class="step-icon"><i class="bi bi-heart"></i></div>
          <h2 class="step-title">Votre don</h2>
          <p class="step-description">Choisissez le type de don et renseignez vos coordonnées.</p>
        </div>

        <div class="don-kind-tabs" role="tablist">
          <button type="button" class="don-tab is-active" data-kind="in_kind">Don en nature</button>
          <button type="button" class="don-tab" data-kind="cash">Don en espèces</button>
        </div>

        <form id="donForm" class="don-form" novalidate>
          <div class="fields-grid">
            <div class="field full">
              <label class="field-label" for="donorName">Nom complet <span class="required">*</span></label>
              <input type="text" id="donorName" class="field-input" required autocomplete="name">
            </div>

            <div class="field full">
              <label class="field-label" for="donorPhone">Téléphone</label>
              <input type="tel" id="donorPhone" class="field-input" autocomplete="tel">
            </div>

            <div class="field full">
              <label class="field-label" for="donorEmail">E-mail <span class="required">*</span></label>
              <input type="email" id="donorEmail" class="field-input" required autocomplete="email">
              <p class="field-hint">Vous recevrez une confirmation à cette adresse.</p>
            </div>

            <div id="inKindPanel" class="field full">
              <label class="field-label" for="inKindDescription">Que souhaitez-vous donner ? <span class="required">*</span></label>
              <textarea id="inKindDescription" class="field-input" rows="4" placeholder="Ex. matelas, vivres, matériel son…"></textarea>
            </div>

            <div id="cashPanel" class="hidden full">
              <div class="field full">
                <span class="field-label">Destination du don <span class="required">*</span></span>
                <div class="participation-inline-options mt-2 don-purpose-options">
                  <label class="participation-inline-option">
                    <input type="radio" name="cashPurpose" value="general" checked>
                    <span>Bon fonctionnement</span>
                  </label>
                  <label class="participation-inline-option" id="sponsorYouthOption">
                    <input type="radio" name="cashPurpose" value="sponsor_youth" id="cashPurposeSponsorYouth">
                    <span>Prise en charge jeunes</span>
                  </label>
                </div>
                <p id="sponsorClosedHint" class="field-hint sponsor-closed-hint hidden" role="note"></p>
              </div>

              <div class="field full" id="generalAmountField">
                <label class="field-label" for="cashAmount">Montant (<span id="donCurrency">USD</span>) <span class="required">*</span></label>
                <input type="number" id="cashAmount" class="field-input" min="1" step="0.01" placeholder="Ex. 50">
              </div>

              <div class="field full hidden" id="youthSlotsField">
                <label class="field-label" for="youthSlots">Nombre de jeunes à sponsoriser <span class="required">*</span></label>
                <input type="number" id="youthSlots" class="field-input" min="1" max="50" value="1">
                <p class="field-hint">Prix par place : <strong id="unitPriceLabel">—</strong> — Total : <strong id="youthTotalLabel">—</strong></p>
                <p id="sponsorCapacityHint" class="field-hint"></p>
              </div>
            </div>

            <div class="field full">
              <label class="field-label" for="donorMessage">Message <span class="optional">(optionnel)</span></label>
              <textarea id="donorMessage" class="field-input" rows="3" placeholder="Un mot pour l'équipe d'organisation…"></textarea>
            </div>
          </div>

          <div id="cashPaymentBlock" class="hidden mt-4">
            <div class="field full">
              <div class="field-label">Mode de paiement <span class="required">*</span></div>
              <div class="participation-inline-options mt-2" id="donPaymentModesGroup" role="radiogroup">
                <label class="participation-inline-option" for="donPayModeMm">
                  <input type="radio" id="donPayModeMm" name="donPaymentMode" value="mobile_money">
                  <span>Mobile money</span>
                </label>
                <label class="participation-inline-option" for="donPayModeCard">
                  <input type="radio" id="donPayModeCard" name="donPaymentMode" value="card">
                  <span>Carte bancaire</span>
                </label>
                <label class="participation-inline-option" for="donPayModeCash">
                  <input type="radio" id="donPayModeCash" name="donPaymentMode" value="cash">
                  <span>Espèces (cash)</span>
                </label>
              </div>
            </div>

            <div id="donMobileMoneyBlock" class="hidden mt-3">
              <p class="field-label"><i class="bi bi-phone"></i> Opérateur Mobile Money</p>
              <div id="donFlexpayProvidersMount" class="payment-methods mb-3"></div>
              <div class="field full">
                <label class="field-label" for="donFlexpayPhone">Numéro Mobile Money (12 chiffres, commence par 243) <span class="required">*</span></label>
                <input type="tel" id="donFlexpayPhone" class="field-input" placeholder="2438XX XXX XXX" inputmode="numeric" autocomplete="tel">
                <p id="donFlexpayPhoneFormatHint" class="field-hint" role="note">Sans le signe « + ». Exemple : <strong>243</strong> puis 9 chiffres (équivalent à un numéro national commençant par 0).</p>
              </div>
              <button type="button" id="btnDonMobilePay" class="btn btn-next mt-2">
                Déclencher le paiement <i class="bi bi-phone-vibrate"></i>
              </button>
              <p class="field-hint mt-2" id="donMobilePayHint">Une demande sera envoyée sur votre téléphone. Laissez cette page ouverte.</p>
            </div>

            <div id="donPaymentProgressPanel" class="payment-progress-panel hidden" aria-live="polite">
              <div class="payment-progress-title"><i class="bi bi-activity"></i> Suivi du paiement</div>
              <ol class="payment-progress-steps">
                <li class="payment-progress-step" data-step="1">
                  <span class="payment-progress-dot">1</span>
                  <span class="payment-progress-label">Envoi de la demande à votre opérateur</span>
                </li>
                <li class="payment-progress-step" data-step="2">
                  <span class="payment-progress-dot">2</span>
                  <span class="payment-progress-label">Confirmation sur votre téléphone</span>
                </li>
                <li class="payment-progress-step" data-step="3">
                  <span class="payment-progress-dot">3</span>
                  <span class="payment-progress-label">Validation de l’encaissement</span>
                </li>
              </ol>
              <p id="donPaymentProgressDetail" class="payment-progress-detail"></p>
              <div id="donPaymentPollRelaunchWrap" class="payment-poll-relaunch hidden">
                <p id="donPaymentPollRelaunchHint" class="payment-progress-rehint"></p>
                <button type="button" id="btnRelaunchDonPaymentPoll" class="btn btn-submit payment-poll-relaunch-btn">
                  <i class="bi bi-arrow-clockwise"></i> Relancer la vérification du statut
                </button>
              </div>
            </div>

            <div id="donCardBlock" class="hidden mt-3">
              <div class="info-box info">
                <i class="bi bi-bank2"></i>
                <span id="donCardExplainerText">Vous allez être redirigé vers le portail sécurisé de paiement par carte.</span>
              </div>
              <button type="button" id="btnDonCardPay" class="btn btn-next mt-2">
                Continuer vers le paiement carte <i class="bi bi-box-arrow-up-right"></i>
              </button>
            </div>

            <div id="donCashBlock" class="hidden mt-3">
              <div class="info-box warning mb-3">
                <i class="bi bi-cash-stack"></i>
                <span>Après dépôt en espèces, téléchargez votre preuve. L'équipe validera avant confirmation et génération des codes parrainage.</span>
              </div>
              <div class="field full">
                <label class="field-label">Preuve de paiement <span class="required">*</span></label>
                <div class="file-drop-zone" id="donProofDropZone">
                  <div class="file-drop-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                  <div class="file-drop-text">Glissez votre fichier ici ou <strong>parcourir</strong></div>
                  <div class="file-drop-hint">JPG, PNG, PDF · Max 5 Mo</div>
                </div>
                <input type="file" id="donProofInput" accept="image/*,.pdf" style="display:none;">
                <div class="file-preview hidden" id="donProofPreview">
                  <div class="file-preview-name" id="donProofFileName">
                    <i class="bi bi-file-earmark"></i>
                    <span></span>
                    <button type="button" class="photo-remove-btn" id="donProofRemoveBtn" style="margin-left:auto;"><i class="bi bi-x"></i></button>
                  </div>
                  <img id="donProofImage" src="" alt="Aperçu" style="display:none;margin-top:0.5rem;">
                </div>
              </div>
              <button type="button" id="btnDonSubmitCashProof" class="btn btn-submit mt-2">
                <i class="bi bi-upload"></i> Envoyer la preuve
              </button>
            </div>

            <div id="donPaymentStatusBanner" class="info-box mt-3 hidden"></div>

            <p id="donPaymentStatus" class="field-hint mt-3" aria-live="polite"></p>
          </div>

          <div class="nav-buttons mt-4">
            <span class="btn-spacer"></span>
            <button type="submit" id="btnSubmitDon" class="btn btn-submit">
              <i class="bi bi-heart"></i> Envoyer ma proposition de don
            </button>
          </div>
        </form>
      </section>
    </main>
  </div>
  </div>
@endsection

@push('scripts')
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="retraite-don-api-base" content="{{ url('/api/v1/retreat/donations') }}">
  <script src="{{ asset('retraite-don/js/don-form.js') }}?v=7"></script>
@endpush
