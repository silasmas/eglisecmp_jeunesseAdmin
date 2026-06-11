      <!-- ═══ ÉTAPE 1 : IDENTITÉ ═══ -->
      <section class="step active" id="step-0">
        <div class="step-header">
          <div class="step-icon"><i class="bi bi-person"></i></div>
          <h2 class="step-title">Identité du participant</h2>
          <p class="step-description">Renseignez vos informations personnelles pour créer votre profil.</p>
        </div>

        <div class="field full worker-prefill-panel" id="workerPrefillPanel">
          <label class="field-checkbox-label" for="isWorkerCheck">
            <input type="checkbox" id="isWorkerCheck" class="field-checkbox">
            <span>Je suis ouvrier de la jeunesse CMP et je veux préremplir mes informations depuis mon compte.</span>
          </label>
          <div id="workerPrefillLookup" class="worker-prefill-lookup hidden">
            <label class="field-label" for="workerIdentifier">E-mail ou téléphone de l’ouvrier</label>
            <div class="phone-row">
              <input type="text" id="workerIdentifier" class="field-input" placeholder="exemple@domaine.com ou téléphone">
              <button type="button" class="btn btn-next" id="workerPrefillBtn">
                <i class="bi bi-search"></i> Préremplir
              </button>
            </div>
            <p id="workerPrefillFeedback" class="field-hint" aria-live="polite"></p>
          </div>
        </div>
        <input type="hidden" id="role" value="Participant">

        <div class="fields-grid">
          <div class="field" data-reg-field="nom">
            <label class="field-label" for="nom">Nom <span class="required">*</span></label>
            <input type="text" id="nom" class="field-input" placeholder="Ex: Kabongo" data-required autocomplete="family-name">
            <span class="field-error"><i class="bi bi-exclamation-circle"></i> Ce champ est requis</span>
            <p id="nomLiveFeedback" class="phone-live-feedback field-hint hidden" aria-live="polite"></p>
          </div>
          <div class="field" data-reg-field="prenom">
            <label class="field-label" for="prenom">Prénom <span class="required">*</span></label>
            <input type="text" id="prenom" class="field-input" placeholder="Ex: Jean-Marc" data-required autocomplete="given-name">
            <span class="field-error"><i class="bi bi-exclamation-circle"></i> Ce champ est requis</span>
            <p id="prenomLiveFeedback" class="phone-live-feedback field-hint hidden" aria-live="polite"></p>
          </div>
          <div class="field" data-reg-field="sexe">
            <label class="field-label" for="sexe">Sexe <span class="required">*</span></label>
            <select id="sexe" class="field-input" data-required>
              <option value="">Sélectionnez...</option>
              <option value="M">Masculin</option>
              <option value="F">Féminin</option>
            </select>
            <span class="field-error"><i class="bi bi-exclamation-circle"></i> Veuillez sélectionner</span>
          </div>
          <div class="field" data-reg-field="date_naissance">
            <label class="field-label" for="dateNaissance">Date de naissance <span class="required">*</span></label>
            <input type="text" id="dateNaissance" class="field-input" data-required placeholder="JJ/MM/AAAA ou sélectionnez" autocomplete="bday">
            <span class="field-error"><i class="bi bi-exclamation-circle"></i> Ce champ est requis</span>
            <span class="field-hint" id="ageDisplay"></span>
          </div>
          <div class="field" data-reg-field="telephone">
            <label class="field-label" for="telephone">Téléphone principal (WhatsApp) <span class="required">*</span></label>
            <div class="phone-row">
              <select id="indicatif" class="field-input">
                <option value="+243">🇨🇩 +243</option>
                <option value="+33">🇫🇷 +33</option>
                <option value="+32">🇧🇪 +32</option>
                <option value="+1">🇺🇸 +1</option>
                <option value="+44">🇬🇧 +44</option>
                <option value="+242">🇨🇬 +242</option>
              </select>
              <input type="tel" id="telephone" class="field-input" placeholder="Ex: 891 234 567" data-required>
            </div>
            <span class="field-error"><i class="bi bi-exclamation-circle"></i> Un numéro de téléphone valide est requis</span>
            <span class="field-hint"><i class="bi bi-whatsapp"></i> Votre billet sera envoyé via WhatsApp</span>
            <p id="telephoneLiveFeedback" class="phone-live-feedback field-hint hidden" aria-live="polite"></p>
          </div>
          <div class="field" data-reg-field="email">
            <label class="field-label" for="email">Email <span class="required">*</span></label>
            <input type="email" id="email" class="field-input" placeholder="exemple@domaine.com" data-required>
            <span class="field-error"><i class="bi bi-exclamation-circle"></i> Adresse email invalide</span>
            <p id="emailLiveFeedback" class="phone-live-feedback field-hint hidden" aria-live="polite"></p>
          </div>
          <div class="field full" data-reg-field="photo">
            <label class="field-label">Photo de profil <span class="required">*</span></label>
            <div class="photo-upload-zone" id="photoZone">
              <div class="photo-preview-circle" id="photoCircle">
                <i class="bi bi-camera" id="photoPlaceholder"></i>
                <img id="photoPreview" src="" alt="Aperçu photo" style="display:none;">
              </div>
              <div class="photo-upload-text">
                <strong>Ajouter une photo</strong>
                <span>JPG, PNG, WEBP · Recadrage carré + compression automatique</span>
              </div>
              <button type="button" class="photo-remove-btn hidden" id="photoRemoveBtn">
                <i class="bi bi-x-circle"></i> Retirer
              </button>
            </div>
            <input type="file" id="photoInput" accept="image/jpeg,image/png,image/webp" style="display:none;">
            <div id="photoCompressStatus"></div>
            <span class="field-error" id="photoRequiredError"><i class="bi bi-exclamation-circle"></i> La photo est obligatoire</span>
            <span class="field-hint">Votre photo apparaît sur le badge et aide l’accueil à identifier rapidement chaque participant.</span>
          </div>
        </div>

        <div class="nav-buttons">
          <div class="btn-spacer"></div>
          <button type="button" class="btn btn-next" onclick="nextStep()">
            Continuer <i class="bi bi-arrow-right"></i>
          </button>
        </div>
      </section>

      <!-- ═══ ÉTAPE 2 : COORDONNÉES ═══ -->
      <section class="step" id="step-1">
        <div class="step-header">
          <div class="step-icon"><i class="bi bi-telephone"></i></div>
          <h2 class="step-title">Vos coordonnées</h2>
          <p class="step-description">Vos coordonnées nous serviront à la communication quant au suivi de votre inscription ainsi qu'à l'envoi des confirmations.</p>
        </div>

        <div class="field full" id="parentMultiChildBlock">
          <div class="info-box warning mb-2">
            <i class="bi bi-people"></i>
            <span><strong>Vous êtes parent/tuteur et vous inscrivez plusieurs enfants ?</strong> Cochez la case ci-dessous pour activer la vérification OTP, puis réutiliser les mêmes contacts pour les prochains enfants.</span>
          </div>
          <label class="field-checkbox-label" for="familyMultiChildCheck">
            <input type="checkbox" id="familyMultiChildCheck" class="field-checkbox">
            <span>Je suis parent/tuteur et j’enregistre plusieurs enfants (même famille).</span>
          </label>
          <div id="familyMultiChildPanel" class="hidden" style="margin-top:10px;">
            <div class="info-box info mb-2">
              <i class="bi bi-shield-check"></i>
              <span id="parentOtpChannelHint">Anti-robot: l’OTP sera envoyé selon la configuration de l’événement. Ensuite, vous pourrez réutiliser les mêmes contacts pour les prochains enfants.</span>
            </div>
            <div class="fields-grid">
              <div class="field" id="parentContactEmailField">
                <label class="field-label" for="parentContactEmail">E-mail parent/tuteur <span class="required">*</span></label>
                <input type="email" id="parentContactEmail" class="field-input" placeholder="parent@domaine.com">
              </div>
              <div class="field hidden" id="parentContactPhoneField">
                <label class="field-label" for="parentContactPhone">Téléphone parent/tuteur (12 chiffres, 243...) <span class="required">*</span></label>
                <input type="tel" id="parentContactPhone" class="field-input" placeholder="2438XXXXXXXX">
              </div>
              <div class="hidden" id="parentOtpFieldsWrap">
                <div class="field" id="parentEmailOtpField">
                  <label class="field-label" for="parentEmailOtp">OTP reçu par e-mail</label>
                  <input type="text" id="parentEmailOtp" class="field-input" inputmode="numeric" maxlength="6" placeholder="6 chiffres">
                </div>
                <div class="field hidden" id="parentSmsOtpField">
                  <label class="field-label" for="parentSmsOtp">OTP reçu par SMS</label>
                  <input type="text" id="parentSmsOtp" class="field-input" inputmode="numeric" maxlength="6" placeholder="6 chiffres">
                </div>
              </div>
              <div class="field full hidden" id="parentFullNameField">
                <label class="field-label" for="parentFullName">Nom complet du parent/tuteur vérifié <span class="required">*</span></label>
                <input type="text" id="parentFullName" class="field-input" placeholder="Ex.: Marie Kabongo Tshimanga">
                <span class="field-error"><i class="bi bi-exclamation-circle"></i> Le nom complet du parent/tuteur est requis</span>
                <span class="field-hint">Ce nom sera utilisé comme libellé du regroupement familial.</span>
              </div>
            </div>
            <button type="button" class="btn btn-next mt-2" id="btnSendParentOtp">
              <i class="bi bi-shield-lock"></i> Envoyer le code OTP
            </button>
            <button type="button" class="btn btn-submit mt-2 hidden" id="btnVerifyParentOtp">
              <i class="bi bi-check2-circle"></i> Vérifier le code OTP
            </button>
            <p id="parentOtpStatus" class="field-hint mt-2"></p>
          </div>
        </div>

        <div class="fields-grid">
          <div class="field full" data-reg-field="tel_urgence">
            <label class="field-label" for="telUrgence">Téléphone d'urgence <span class="optional">(facultatif)</span></label>
            <input type="tel" id="telUrgence" class="field-input" placeholder="Numéro alternatif (+243… ou sans indicatif comme le champ principal)">
            <p id="telUrgenceLiveFeedback" class="phone-live-feedback field-hint hidden" aria-live="polite"></p>
          </div>
          <div class="field" id="guardianNameField" data-reg-field="guardian_name">
            <label class="field-label" for="guardianName">Nom du parent ou tuteur <span class="optional">(facultatif)</span></label>
            <input type="text" id="guardianName" class="field-input" placeholder="Ex.: Marie Kabongo">
            <p id="guardianNameLiveFeedback" class="phone-live-feedback field-hint hidden" aria-live="polite"></p>
          </div>
          <div class="field" id="guardianPhoneField" data-reg-field="guardian_phone">
            <label class="field-label" for="guardianPhone">Téléphone du parent ou tuteur <span class="optional">(facultatif)</span></label>
            <input type="tel" id="guardianPhone" class="field-input" placeholder="Même convention que téléphone d’urgence (+ ou indicatif commun)">
            <p id="guardianPhoneLiveFeedback" class="phone-live-feedback field-hint hidden" aria-live="polite"></p>
          </div>
          <div class="field full hidden tutor-same-family-ack-wrap" id="tutorSameFamilyField">
            <div class="tutor-same-family-panel">
              <p class="tutor-same-family-panel-title"><i class="bi bi-people-fill"></i> Identification pour le regroupement des fiches (optionnel)</p>
              <div id="tutorSameFamilyExplain" class="tutor-same-family-explain"></div>
              <label class="field-checkbox-label tutor-same-family-label" for="tutorSameFamilyCheck">
                <input type="checkbox" id="tutorSameFamilyCheck" class="field-checkbox">
                <span>J’accepte de <strong>m’identifier</strong> comme appartenant au <strong>même foyer / famille</strong> que le ou les dossier(s) déjà présents ci-dessus, et je <strong>consens au regroupement</strong> de nos inscriptions pour l’organisation. Si cette situation ne vous concerne pas, laissez la case décochée — vous pouvez poursuivre sans bloquer.</span>
              </label>
            </div>
          </div>
          <div class="field full" data-reg-field="adresse">
            <label class="field-label" for="adresse">Adresse <span class="required">*</span></label>
            <input type="text" id="adresse" class="field-input" placeholder="Numéro et rue" data-required>
            <span class="field-error"><i class="bi bi-exclamation-circle"></i> Ce champ est requis</span>
          </div>
          <div class="field" data-reg-field="commune">
            <label class="field-label" for="commune">Commune <span class="required">*</span></label>
            <input type="text" id="commune" class="field-input" placeholder="Ex: Gombe" data-required>
            <span class="field-error"><i class="bi bi-exclamation-circle"></i> Ce champ est requis</span>
          </div>
          <div class="field" data-reg-field="ville">
            <label class="field-label" for="ville">Ville <span class="required">*</span></label>
            <input type="text" id="ville" class="field-input" placeholder="Ex: Kinshasa" data-required>
            <span class="field-error"><i class="bi bi-exclamation-circle"></i> Ce champ est requis</span>
          </div>
        </div>

        <div class="nav-buttons">
          <button type="button" class="btn btn-prev" onclick="prevStep()">
            <i class="bi bi-arrow-left"></i> Précédent
          </button>
          <button type="button" class="btn btn-next" onclick="nextStep()">
            Continuer <i class="bi bi-arrow-right"></i>
          </button>
        </div>
      </section>

      <!-- ═══ ÉTAPE 3 : PARTICIPATION ═══ -->
      <section class="step" id="step-2">
        <div class="step-header">
          <div class="step-icon"><i class="bi bi-building"></i></div>
          <h2 class="step-title">Informations de participation</h2>
          <p class="step-description">Aidez-nous à mieux organiser votre séjour lors de la retraite.</p>
        </div>

        <div class="fields-grid participation-grid">
          <div class="field" data-reg-field="eglise">
            <label class="field-label" for="eglise">Église / Assemblée <span class="required">*</span></label>
            <input type="text" id="eglise" class="field-input" placeholder="Ex: CMP Gombe" data-required>
            <span class="field-error"><i class="bi bi-exclamation-circle"></i> Ce champ est requis</span>
          </div>
          <div class="field" data-reg-field="departement">
            <label class="field-label" for="departement">Département<span class="optional">(facultatif)</span></label>
            <input type="text" id="departement" class="field-input" placeholder="Ex: Cellule Amour">
            <label class="field-checkbox-label participation-checkbox-label" for="noDepartement" id="noDepartementWrap">
              <input type="checkbox" id="noDepartement" class="field-checkbox">
              <span>Je ne fais partie d'aucun département</span>
            </label>
          </div>
          <div class="field participation-hebergement-field" data-reg-field="hebergement">
            <label class="field-label">Type d’hébergement <span class="optional">(facultatif)</span></label>
            <div class="participation-inline-options" role="radiogroup" aria-label="Type d’hébergement">
              <label class="participation-inline-option" for="hebergementInterne">
                <input type="radio" id="hebergementInterne" name="hebergement" value="interne">
                <span>Interne</span>
              </label>
              <label class="participation-inline-option" for="hebergementExterne">
                <input type="radio" id="hebergementExterne" name="hebergement" value="externe">
                <span>Externe</span>
              </label>
            </div>
            <span class="field-hint">Interne = vous dormez sur le site de retraite ; Externe = vous rentrez/logez hors site.</span>
          </div>
          <div class="field full" data-reg-field="observations">
            <label class="field-label" data-reg-label>Observations / Besoins particuliers <span class="optional">(facultatif)</span></label>
            <div class="participation-inline-options mt-2" data-reg-yesno role="radiogroup" aria-label="Observations ou besoins particuliers">
              <label class="participation-inline-option" for="hasObservationsYes">
                <input type="radio" id="hasObservationsYes" name="hasObservations" value="yes">
                <span>Oui</span>
              </label>
              <label class="participation-inline-option" for="hasObservationsNo">
                <input type="radio" id="hasObservationsNo" name="hasObservations" value="no" checked>
                <span>Non</span>
              </label>
            </div>
            <div id="observationsDetailWrap" class="hidden mt-2">
              <label class="field-label" for="observations">Précisez vos observations</label>
              <textarea id="observations" class="field-input participation-observations" rows="3" placeholder="Allergies alimentaires, besoins médicaux, ou toute information utile..."></textarea>
            </div>
          </div>
        </div>

        <div class="info-box info mt-2">
          <i class="bi bi-info-circle"></i>
          <span>Les détails d'hébergement pourront être modifiés ultérieurement selon les disponibilités.</span>
        </div>

        <div class="divider"></div>

        <div class="nav-buttons">
          <button type="button" class="btn btn-prev" onclick="prevStep()">
            <i class="bi bi-arrow-left"></i> Précédent
          </button>
          <button type="button" class="btn btn-next" onclick="nextStep()">
            Continuer <i class="bi bi-arrow-right"></i>
          </button>
        </div>
      </section>

      <!-- ═══ ÉTAPE 4 : RÉCAPITULATIF (avant paiement) ═══ -->
      <section class="step" id="step-3">
        <div class="step-header">
          <div class="step-icon"><i class="bi bi-clipboard-check"></i></div>
          <h2 class="step-title">Récapitulatif de votre inscription</h2>
          <p class="step-description">Vérifiez toutes vos informations avant de procéder au paiement sécurisé.</p>
        </div>

        <div id="recapContent"></div>

        <div id="policiesBlock" class="recap-policies recap-policies-compact mt-4 hidden">
          <div class="info-box warning policies-open-box">
            <p class="mb-2"><strong>Règlement obligatoire</strong> · Ouvrez la fenêtre pour lire puis accepter les conditions légales et d’organisation. Sans cette acceptation validée, le paiement sera bloqué.</p>
            <button type="button" class="btn btn-next mt-2" id="btnOpenPoliciesModal">
              <i class="bi bi-folder2-open"></i> Voir et accepter le règlement
            </button>
            <p id="policiesAcceptedBadge" class="hidden policies-accepted-msg mt-2">
              <i class="bi bi-check-circle-fill text-success-policies"></i> Règlement accepté pour cette inscription.
            </p>
          </div>
        </div>

        <div class="recap-confirm-box" id="confirmBox">
          <label>
            <input type="checkbox" id="confirmCheck">
            <span>Je confirme que les informations ci-dessus sont exactes avant de passer à l’étape de paiement.</span>
          </label>
        </div>

        <div class="nav-buttons">
          <button type="button" class="btn btn-prev" onclick="prevStep()">
            <i class="bi bi-arrow-left"></i> Précédent
          </button>
          <button type="button" class="btn btn-submit" id="submitBtn" disabled onclick="confirmRecapAndProceed()">
            <i class="bi bi-arrow-right-circle"></i> Continuer vers le paiement
          </button>
        </div>
      </section>

      <!-- ═══ ÉTAPE 5 : PAIEMENT ═══ -->
      <section class="step" id="step-4">
        <div class="step-header">
          <div class="step-icon"><i class="bi bi-credit-card"></i></div>
          <h2 class="step-title">Paiement sécurisé</h2>
          <p class="step-description" id="paymentStepSubtitle">Montant défini depuis l’événement actif pour finaliser votre place.</p>
        </div>

        <div class="text-center mb-3">
          <div class="payment-amount" id="dynamicPaymentAmount">
            <i class="bi bi-tag"></i>
            <span id="paymentAmountLabel">Chargement du montant…</span>
          </div>
        </div>

        <div class="field full">
          <div class="field-label">Mode de paiement <span class="required">*</span></div>
          <div class="participation-inline-options mt-2" id="paymentModesGroup" role="radiogroup" aria-label="Mode de paiement">
            <label class="participation-inline-option" for="payModeMm" data-payment-mode="mobile_money">
              <input type="radio" id="payModeMm" name="paymentMode" value="mobile_money">
              <span>Mobile money</span>
            </label>
            <label class="participation-inline-option" for="payModeCard" data-payment-mode="card">
              <input type="radio" id="payModeCard" name="paymentMode" value="card">
              <span>Carte bancaire</span>
            </label>
            <label class="participation-inline-option" for="payModeCash" data-payment-mode="cash">
              <input type="radio" id="payModeCash" name="paymentMode" value="cash">
              <span>Espèces (cash)</span>
            </label>
          </div>
        </div>

        <div id="mobileMoneyBlock" class="hidden mt-3">
          <p class="field-label"><i class="bi bi-phone"></i> Opérateur Mobile Money</p>
          <div id="flexpayProvidersMount" class="payment-methods mb-3"></div>
          <div class="field">
            <label class="field-label" for="flexpayPhoneInput">Numéro Mobile Money (12 chiffres, commence par 243)<span class="required">*</span></label>
            <input type="tel" id="flexpayPhoneInput" inputmode="numeric" class="field-input" placeholder="2438XX XXX XXX" autocomplete="tel">
            <p id="flexpayPhoneFormatHint" class="field-hint" role="note">Sans le signe « + ». Exemple : <strong>243</strong> puis 9 chiffres (équivalent à un numéro national commençant par 0).</p>
          </div>
          <button type="button" class="btn btn-next mt-2" id="btnTriggerMobilePay">
            Déclencher le paiement <i class="bi bi-phone-vibrate"></i>
          </button>
          <p class="field-hint mt-2" id="mobilePayHint">Une demande sera envoyée sur votre téléphone. Laissez cette page ouverte.</p>
        </div>

        <div id="paymentProgressPanel" class="payment-progress-panel hidden" aria-live="polite">
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
          <p id="paymentProgressDetail" class="payment-progress-detail"></p>
          <div id="paymentPollRelaunchWrap" class="payment-poll-relaunch hidden">
            <p id="paymentPollRelaunchHint" class="payment-progress-rehint"></p>
            <button type="button" id="btnRelaunchPaymentPoll" class="btn btn-submit payment-poll-relaunch-btn">
              <i class="bi bi-arrow-clockwise"></i> Relancer la vérification du statut
            </button>
          </div>
        </div>

        <div id="cardBlock" class="hidden mt-3">
          <div class="info-box info">
            <i class="bi bi-bank2"></i>
            <span id="cardExplainerText">Vous allez être redirigé vers le portail sécurisé de paiement par carte (via l’intermédiaire technique de l’église).</span>
          </div>
          <button type="button" class="btn btn-next mt-2" id="btnTriggerCardPay">
            Continuer vers le paiement carte <i class="bi bi-box-arrow-up-right"></i>
          </button>
        </div>

        <div id="cashBlock" class="hidden mt-3">
          <div class="info-box warning mb-3">
            <i class="bi bi-cash-stack"></i>
            <span>Après dépôt en espèces, téléchargez votre preuve ici pour que l’équipe valide manuellement votre inscription.</span>
          </div>
          <div class="field full">
            <label class="field-label">Preuve de paiement <span class="required">*</span></label>
            <div class="file-drop-zone" id="proofDropZone">
              <div class="file-drop-icon"><i class="bi bi-cloud-arrow-up"></i></div>
              <div class="file-drop-text">
                Glissez votre fichier ici ou <strong>parcourir</strong>
              </div>
              <div class="file-drop-hint">Reçu, photo du dépôt · JPG, PNG, PDF · Max 5 Mo</div>
            </div>
            <input type="file" id="proofInput" accept="image/*,.pdf" style="display:none;">
            <span class="field-error" id="proofError"><i class="bi bi-exclamation-circle"></i> La preuve de paiement est requise</span>
            <div class="file-preview" id="proofPreview">
              <div class="file-preview-name" id="proofFileName">
                <i class="bi bi-file-earmark"></i>
                <span></span>
                <button type="button" class="photo-remove-btn" id="proofRemoveBtn" style="margin-left:auto;">
                  <i class="bi bi-x"></i>
                </button>
              </div>
              <img id="proofImage" src="" alt="Aperçu preuve" style="display:none; margin-top:0.5rem;">
            </div>
          </div>
          <button type="button" class="btn btn-submit mt-2" id="btnSubmitCashProof">
            <i class="bi bi-upload"></i> Envoyer la preuve et finaliser
          </button>
        </div>

        <div id="paymentStatusBanner" class="info-box mt-3 hidden"></div>

        <div class="nav-buttons mt-4">
          <button type="button" class="btn btn-prev" onclick="prevStep()">
            <i class="bi bi-arrow-left"></i> Précédent
          </button>
        </div>
      </section>

      <!-- ═══ ÉTAPE 6 : BILLET ═══ -->
      <section class="step" id="step-5">
        <div id="billetCreationLoader" class="billet-creation-loader hidden" aria-live="polite" aria-busy="false">
          <div class="billet-creation-loader-inner">
            <div class="retraite-gate-spinner" aria-hidden="true"></div>
            <p id="billetCreationLoaderText">Génération de votre billet en cours…</p>
          </div>
        </div>
        <div class="step-header text-center">
          <div class="success-checkmark">
            <i class="bi bi-check-lg"></i>
          </div>
          <h2 class="step-title" id="badgeMainTitle">Inscription finalisée</h2>
          <p class="step-description" id="badgeMainSubtitle">Votre dossier a été traité avec succès.</p>
        </div>

        <div class="badge-container">
          <p class="badge-section-kicker badge-kicker-intro">
            Synthèse officielle pour impression ou partage · QR relié à votre dossier en ligne.
          </p>

          <div id="badgeExportComposite" class="badge-export-sheet">
            <header class="badge-export-header">
              <div class="badge-export-org">Jeunesse CMP</div>
              <div class="badge-export-event-title" id="badgeExportEventTitle">Grande Retraite de la jeunesse</div>
              <div class="badge-export-meta text-muted small" id="badgeExportMeta"></div>
            </header>

            <div id="badgeRecapMirrored" class="badge-recap-mirror"></div>

            <div class="badge-qr-strip">
              <div class="badge-qr-strip-inner">
                <div id="badgeQrMount" class="badge-qr-mount" aria-hidden="true"></div>
                <div class="badge-qr-copy">
                  <strong>Contrôle d'accès</strong>
                  <p class="badge-qr-link-line" id="badgeQrLinkLine"></p>
                  <p class="badge-qr-hint">À présenter à l'accueil : ce code ouvre la page de vérification pour autoriser l'accès.</p>
                  <p class="badge-qr-hint" id="badgeBilletLinkWrap" style="display:none;margin-top:8px;">
                    <a id="badgeBilletLink" href="#" target="_blank" rel="noopener">Ouvrir votre billet participant</a>
                  </p>
                </div>
              </div>
            </div>
          </div><!-- /badgeExportComposite -->

          <div class="badge-actions">
            <button type="button" class="btn btn-download" id="downloadBadgeBtn" onclick="downloadBadgePngComposite()">
              <i class="bi bi-image"></i> Télécharger PNG
            </button>
            <button type="button" class="btn btn-download btn-download-secondary" id="downloadBadgePdfBtn" onclick="downloadBadgePdfComposite()">
              <i class="bi bi-file-earmark-pdf"></i> Télécharger PDF
            </button>
            <button type="button" class="btn btn-outline" id="badgeNewRegistrationBtn">
              <i class="bi bi-arrow-counterclockwise"></i> Nouvelle inscription
            </button>
          </div>

          <div id="badgeElectronicBanner" class="info-box success mt-3 hidden" style="max-width:560px;">
            <i class="bi bi-envelope-check"></i>
            <span><strong>Paiement électronique validé.</strong> Récapitulatif et confirmations vous seront transmis à l’adresse e-mail indiquée. Conservez la référence de transaction.</span>
          </div>
          <div id="badgeCashPendingBanner" class="info-box warning mt-3 hidden" style="max-width:560px;">
            <i class="bi bi-hourglass-split"></i>
            <span><strong>Paiement en espèces soumis.</strong> Vous recevrez un message par e-mail dès confirmation manuelle par l’équipe financière.</span>
          </div>
          <div id="badgeGenericBanner" class="info-box mt-3" style="max-width:560px;">
            <i class="bi bi-info-circle"></i>
            <span id="badgeGenericBannerText">Conservez un exemplaire PNG ou PDF de cette synthèse (QR vérifiable) jusqu’à l’accueil sur place.</span>
          </div>
          <div id="badgePortalNotifications" class="info-box portal-muted mt-3 hidden" style="max-width:560px; text-align:left;"></div>
        </div>
      </section>
