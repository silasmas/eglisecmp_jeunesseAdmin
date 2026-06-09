<div
    class="reg-form-preview__ui-block reg-form-preview__field--full"
    x-data="{ showPanel: false }"
>
    <span class="reg-form-preview__ui-tag">Bloc parent / tuteur</span>
    <div class="reg-form-preview__info">
        Vous êtes parent/tuteur et vous inscrivez plusieurs enfants ? Cochez la case pour activer la vérification OTP.
    </div>
    <label class="reg-form-preview__checkbox">
        <span class="reg-form-preview__checkbox-box" @click="showPanel = !showPanel"></span>
        <span>Je suis parent/tuteur et j'enregistre plusieurs enfants (même famille).</span>
    </label>
    <div x-show="showPanel" x-cloak class="mt-2" style="display:flex;flex-direction:column;gap:0.45rem;">
        <div class="reg-form-preview__grid">
            <div class="reg-form-preview__field">
                <div class="reg-form-preview__label">E-mail parent/tuteur <span class="reg-form-preview__req">*</span></div>
                <div class="reg-form-preview__input">parent@domaine.com</div>
            </div>
            <div class="reg-form-preview__field">
                <div class="reg-form-preview__label">OTP reçu par e-mail</div>
                <div class="reg-form-preview__input">6 chiffres</div>
            </div>
        </div>
        <div class="reg-form-preview__hint">Cliquez la case dans l'aperçu pour simuler l'ouverture du panneau OTP.</div>
    </div>
</div>
