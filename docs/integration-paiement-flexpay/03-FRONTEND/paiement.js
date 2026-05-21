/**
 * Logique de paiement FlexPay - Dons
 *
 * Dépendances : SweetAlert2 (swal), jQuery (pour $.ajax) ou Fetch API
 * À inclure dans votre layout ou page
 */

document.addEventListener('DOMContentLoaded', function() {

    // ========== ÉTAPE 1 : Init du don ==========
    document.getElementById('formDon')?.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const btn = document.getElementById('btnInitDon');
        btn.disabled = true;
        btn.textContent = 'Chargement...';

        fetch("{{ route('init.don') }}", {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value,
                'Accept': 'application/json',
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('total').value = data.total;
                document.getElementById('currency').value = data.currency;
                document.getElementById('referenceCreate').value = data.reference;
                document.getElementById('totalAff').textContent = data.total + ' ' + data.currency;

                const section = document.getElementById('paiement-section');
                section.style.display = 'block';
                section.style.opacity = 1;
                section.style.pointerEvents = 'auto';

                initPaiement(); // Gestion Mobile Money / Carte
            } else {
                alert(data.message || 'Erreur');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Erreur réseau');
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = 'Continuer vers le paiement';
        });
    });
});

/**
 * Affiche/masque le champ téléphone selon le moyen de paiement
 */
function initPaiement() {
    const select = document.getElementById('channel');
    const phoneContainer = document.getElementById('phoneContainer');
    const phoneInput = document.getElementById('phone');
    const checkbox = document.getElementById('customCheck7');
    const btn = document.querySelector('#formPaie button[type="submit"]');

    function updateState() {
        if (select.value === 'mobile_money') {
            phoneContainer.style.display = 'block';
            phoneInput.required = true;
        } else {
            phoneContainer.style.display = 'none';
            phoneInput.required = false;
            phoneInput.value = '';
        }
        btn.disabled = !checkbox?.checked;
    }
    updateState();
    select?.addEventListener('change', updateState);
    checkbox?.addEventListener('change', updateState);
}

/**
 * Soumission du formulaire de paiement (AJAX)
 */
function submitPaymentForm(event) {
    event.preventDefault();

    const form = event.target;
    const btn = form.querySelector('button[type="submit"]');
    const checkbox = document.getElementById('customCheck7');

    if (!checkbox?.checked) {
        alert('Veuillez accepter les conditions générales.');
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Traitement...';

    const formData = new FormData(form);

    fetch(form.action, {
        method: form.method,
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value,
            'Accept': 'application/json',
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.reponse) {
            if (data.type === 'mobile') {
                // Mobile Money : polling
                if (typeof swal !== 'undefined') {
                    swal({ title: data.message, icon: 'success' });
                } else {
                    alert(data.message);
                }
                checkTransactionStatus(data.orderNumber);
            } else {
                // Carte bancaire : redirection
                if (typeof swal !== 'undefined') {
                    swal({
                        title: 'Redirection vers le paiement...',
                        icon: 'info'
                    }).then(() => {
                        window.location.href = data.redirect_url;
                    });
                } else {
                    window.location.href = data.redirect_url;
                }
            }
        } else {
            alert(data.message || 'Erreur de paiement');
            btn.disabled = false;
            btn.textContent = 'Payer';
        }
    })
    .catch(err => {
        console.error(err);
        alert('Erreur réseau');
        btn.disabled = false;
        btn.textContent = 'Payer';
    });
}

/**
 * Polling pour vérifier le statut Mobile Money
 */
function checkTransactionStatus(reference) {
    let attempts = 0;
    const maxAttempts = 14; // ~70 secondes
    let isStopped = false;

    const interval = setInterval(() => {
        if (isStopped) return;
        attempts++;

        fetch('/checkTransactionStatus?reference=' + encodeURIComponent(reference), {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(response => {
            if (response.reponse && response.status == 0) {
                isStopped = true;
                clearInterval(interval);
                if (typeof swal !== 'undefined') {
                    swal({ title: 'Merci ! Paiement effectué.', icon: 'success' });
                } else {
                    alert('Paiement effectué !');
                }
                setTimeout(() => location.reload(), 2000);
            }
            if (response.reponse === false && response.status == 1) {
                isStopped = true;
                clearInterval(interval);
                if (typeof swal !== 'undefined') {
                    swal({ title: response.message || 'Paiement refusé', icon: 'error' });
                } else {
                    alert(response.message || 'Paiement refusé');
                }
            }
            if (attempts >= maxAttempts) {
                isStopped = true;
                clearInterval(interval);
                if (typeof swal !== 'undefined') {
                    swal({ title: 'Délai dépassé. Réessayez.', icon: 'warning' });
                } else {
                    alert('Délai dépassé.');
                }
            }
        })
        .catch(() => {
            if (attempts >= maxAttempts) {
                isStopped = true;
                clearInterval(interval);
            }
        });
    }, 5000);
}
