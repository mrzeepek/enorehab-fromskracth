/**
 * Enorehab - Validation de formulaire améliorée
 *
 * Script à inclure dans footer.php juste avant la fermeture </body>
 */
document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.querySelector('#contact-form form');

    if (!contactForm) return;

    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const submitButton = contactForm.querySelector('button[type="submit"]');

    // Fonction pour montrer une erreur
    function showError(input, message) {
        const errorElement = input.nextElementSibling;
        if (errorElement && errorElement.classList.contains('error-message')) {
            errorElement.textContent = message;
            errorElement.classList.remove('hidden');
            input.classList.add('border-red-500');
        }
    }

    // Fonction pour cacher une erreur
    function hideError(input) {
        const errorElement = input.nextElementSibling;
        if (errorElement && errorElement.classList.contains('error-message')) {
            errorElement.classList.add('hidden');
            input.classList.remove('border-red-500');
        }
    }

    // Validation du nom
    nameInput.addEventListener('blur', function() {
        const name = this.value.trim();
        if (!name) {
            showError(this, 'Le nom est requis');
        } else if (name.length < 2) {
            showError(this, 'Le nom doit contenir au moins 2 caractères');
        } else {
            hideError(this);
        }
    });

    // Validation de l'email
    emailInput.addEventListener('blur', function() {
        const email = this.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!email) {
            showError(this, 'L\'email est requis');
        } else if (!emailRegex.test(email)) {
            showError(this, 'Adresse email invalide');
        } else {
            hideError(this);
        }
    });

    // Désactivation de l'auto-complétion pour le honeypot
    if (document.getElementById('website')) {
        document.getElementById('website').setAttribute('autocomplete', 'off');
    }

    // Validation du formulaire à la soumission
    contactForm.addEventListener('submit', function(e) {
        let isValid = true;

        // Valider le nom
        const name = nameInput.value.trim();
        if (!name) {
            showError(nameInput, 'Le nom est requis');
            isValid = false;
        } else if (name.length < 2) {
            showError(nameInput, 'Le nom doit contenir au moins 2 caractères');
            isValid = false;
        }

        // Valider l'email
        const email = emailInput.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!email) {
            showError(emailInput, 'L\'email est requis');
            isValid = false;
        } else if (!emailRegex.test(email)) {
            showError(emailInput, 'Adresse email invalide');
            isValid = false;
        }

        // Empêcher la soumission si le formulaire n'est pas valide
        if (!isValid) {
            e.preventDefault();
        } else {
            // Désactiver le bouton pour éviter les soumissions multiples
            submitButton.disabled = true;
            submitButton.classList.add('opacity-75');
            submitButton.innerHTML = 'Envoi en cours...';

            // Tracking d'événement pour les analytics
            if (typeof fbq === 'function') {
                fbq('track', 'Lead');
            }

            if (typeof gtag === 'function') {
                gtag('event', 'form_submission', {
                    'event_category': 'engagement',
                    'event_label': 'contact_form'
                });
            }
        }
    });
});