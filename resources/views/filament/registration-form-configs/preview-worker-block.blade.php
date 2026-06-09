<div
    class="reg-form-preview__ui-block reg-form-preview__field--full"
    x-data="{ showLookup: false }"
>
    <span class="reg-form-preview__ui-tag">Bloc ouvrier</span>
    <label class="reg-form-preview__checkbox">
        <span class="reg-form-preview__checkbox-box" @click="showLookup = !showLookup"></span>
        <span>Je suis ouvrier de la jeunesse CMP et je veux préremplir mes informations depuis mon compte.</span>
    </label>
    <div
        x-show="showLookup"
        x-cloak
        class="mt-2"
        style="display:flex;flex-direction:column;gap:0.35rem;"
    >
        <div class="reg-form-preview__label">E-mail ou téléphone de l'ouvrier</div>
        <div class="reg-form-preview__input">exemple@domaine.com ou téléphone</div>
        <div class="reg-form-preview__hint">Cliquez la case ci-dessus dans l'aperçu pour simuler l'ouverture du champ de recherche.</div>
    </div>
</div>
