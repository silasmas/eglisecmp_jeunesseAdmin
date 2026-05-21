{{--
    Formulaire de don avec paiement FlexPay
    Inclure : SweetAlert2, Toastr (optionnel)
--}}

<div class="donation-form-wrapper">
    {{-- Étape 1 : Montant et infos --}}
    <form id="formDon" class="checkout-form">
        @csrf
        <div class="form-grp">
            <label for="montant">Montant du don (USD) <span>*</span></label>
            <input type="number" name="montant" id="montant" min="1" step="1" required placeholder="Ex: 10">
        </div>
        <div class="form-grp">
            <label for="nom">Votre nom</label>
            <input type="text" name="nom" id="nom" placeholder="Optionnel">
        </div>
        <div class="form-grp">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" placeholder="Optionnel">
        </div>
        <div class="form-grp">
            <label for="message">Message</label>
            <textarea name="message" id="message" rows="3" placeholder="Optionnel"></textarea>
        </div>
        <button type="submit" id="btnInitDon">Continuer vers le paiement</button>
    </form>

    {{-- Étape 2 : Choix du moyen de paiement (masqué au départ) --}}
    <div id="paiement-section" style="display: none; opacity: 0.5; pointer-events: none;">
        <p>Total à payer : <strong id="totalAff">0 USD</strong></p>
        <form id="formPaie" action="{{ route('process.payment') }}" method="POST" onsubmit="submitPaymentForm(event)">
            @csrf
            <input type="hidden" name="reference" id="referenceCreate">
            <input type="hidden" name="total" id="total">
            <input type="hidden" name="currency" id="currency">

            <div class="form-grp">
                <label>Moyen de paiement <span>*</span></label>
                <select name="channel" id="channel" required>
                    <option value="">Sélectionnez</option>
                    <option value="mobile_money">Mobile Money</option>
                    <option value="card">Carte bancaire</option>
                </select>
            </div>
            <div class="form-grp" id="phoneContainer" style="display: none;">
                <label>Numéro de téléphone</label>
                <input type="text" name="phone" id="phone" placeholder="24382XXXXX">
            </div>
            <div class="form-grp">
                <label>
                    <input type="checkbox" id="customCheck7" required>
                    J'accepte les conditions générales
                </label>
            </div>
            <button type="submit" id="btnPaiement">Payer</button>
        </form>
    </div>
</div>

{{-- Inclure le script : @include('don.scripts.paiement') ou intégrer paiement.blade.php --}}
