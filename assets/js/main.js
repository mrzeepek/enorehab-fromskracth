/**
 * Enorehab - Main JavaScript
 * 
 * Gère les animations, interactions et effets avancés
 * de la landing page Enorehab
 *
 * @version 1.0
 */

// Exécuter le code lorsque le DOM est entièrement chargé
document.addEventListener('DOMContentLoaded', () => {
    
    // ========== ANIMATIONS DU HEADER AU SCROLL ==========
    const header = document.querySelector('header');
    const heroSection = document.getElementById('hero');
    
    // Fonction pour modifier le header au scroll
    function updateHeaderOnScroll() {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    }
    
    // Détecter le scroll pour modifier le header
    window.addEventListener('scroll', updateHeaderOnScroll);
    
    // Appel initial pour s'assurer que l'état est correct au chargement
    updateHeaderOnScroll();
    
    // ========== ANIMATIONS DES SECTIONS AU SCROLL ==========
    
    // Observer qui détecte l'entrée dans le viewport
    const fadeObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            // Si l'élément est visible
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                
                // Si c'est un bloc target, ajouter un délai progressif
                if (entry.target.classList.contains('target-block')) {
                    const index = Array.from(document.querySelectorAll('.target-block')).indexOf(entry.target);
                    entry.target.style.transitionDelay = `${index * 0.2}s`;
                }
                
                // Arrêter d'observer une fois que l'animation est déclenchée
                fadeObserver.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1, // 10% de visibilité déclenche l'animation
        rootMargin: '-20px' // Déclenche un peu avant que l'élément soit visible
    });
    
    // Sélectionner tous les éléments à animer
    const fadeElements = document.querySelectorAll('.target-block, #story img, #pricing > div > div, #faq > div > div');
    
    // Observer chaque élément
    fadeElements.forEach(el => {
        fadeObserver.observe(el);
    });
    
    // ========== COMPTEUR POUR LES PLACES RESTANTES ==========
    
    // Simuler un nombre de places limité qui diminue
    function setupRemainingSpots() {
        const storageKey = 'enorehab_remaining_spots';
        let remainingSpots;

        // Vérifier si une valeur existe déjà en stockage local
        if (localStorage.getItem(storageKey)) {
            remainingSpots = parseInt(localStorage.getItem(storageKey));
        } else {
            // Valeur initiale
            remainingSpots = 10;
            localStorage.setItem(storageKey, remainingSpots);
        }

        // Réduire uniquement au début de la session, pas à chaque reload de page
        const visitorKey = 'enorehab_visitor_' + new Date().toDateString();
        if (!sessionStorage.getItem(visitorKey)) {
            // Marquer comme visité avec date
            sessionStorage.setItem(visitorKey, 'true');

            // 30% de chance de réduire le nombre de places (probabilité réduite)
            if (Math.random() > 0.7 && remainingSpots > 3) {
                remainingSpots -= 1;
                localStorage.setItem(storageKey, remainingSpots);
            }
        }

        // Afficher le nombre de places restantes
        const priceText = document.querySelector('#pricing .text-[#0ed0ff]');
        if (priceText) {
            priceText.textContent = `Offre valable pour les ${remainingSpots} prochains bilans uniquement.`;
        }

        // Ajouter une notification d'urgence si peu de places restantes
        if (remainingSpots <= 3) {
            const pricingSection = document.getElementById('pricing');
            if (pricingSection) {
                // Vérifier si l'alerte n'existe pas déjà
                if (!document.querySelector('.urgency-alert')) {
                    const urgencyAlert = document.createElement('div');
                    urgencyAlert.className = 'mt-4 text-red-500 font-bold text-center animate-pulse urgency-alert';
                    urgencyAlert.textContent = `⚠️ Attention : Seulement ${remainingSpots} place${remainingSpots > 1 ? 's' : ''} restante${remainingSpots > 1 ? 's' : ''} !`;

                    const priceBox = pricingSection.querySelector('.border-[#0ed0ff]');
                    if (priceBox && priceBox.parentNode) {
                        priceBox.parentNode.insertBefore(urgencyAlert, priceBox.nextSibling);
                    }
                }
            }
        }
    }
    
    // Exécuter la fonction de compteur
    setupRemainingSpots();
    
    // ========== GESTION DES FORMULAIRES ==========
    
    // Afficher les messages d'erreur ou de succès depuis l'URL
    function handleFormMessages() {
        const urlParams = new URLSearchParams(window.location.search);
        const formContainer = document.getElementById('contact-form');
        
        if (!formContainer) return;
        
        // Vérifier si un message de succès est présent
        if (urlParams.has('success')) {
            const successMessage = document.createElement('div');
            successMessage.className = 'success-message';
            successMessage.innerHTML = `
                <p class="font-bold">Merci pour ta demande !</p>
                <p>Nous t'avons envoyé un email de confirmation. Tu seras contacté(e) très rapidement.</p>
            `;
            
            // Insérer avant le formulaire
            const form = formContainer.querySelector('form');
            formContainer.insertBefore(successMessage, form);
            
            // Masquer le formulaire après soumission réussie
            if (form) form.style.display = 'none';
        }
        
        // Vérifier si un message d'erreur est présent
        if (urlParams.has('error')) {
            const errorParam = urlParams.get('error');
            let errorMessage;
            
            // Différents messages selon le type d'erreur
            if (errorParam === 'sending') {
                errorMessage = "Une erreur s'est produite lors de l'envoi du formulaire. Merci de réessayer ou de nous contacter directement par email.";
            } else {
                // Gérer les erreurs de validation multiples
                const errors = decodeURIComponent(errorParam).split('|');
                errorMessage = `<p class="font-bold">Merci de corriger les erreurs suivantes :</p><ul class="list-disc pl-5 mt-2">`;
                errors.forEach(err => {
                    errorMessage += `<li>${err}</li>`;
                });
                errorMessage += `</ul>`;
            }
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.innerHTML = errorMessage;
            
            // Insérer avant le formulaire
            const form = formContainer.querySelector('form');
            if (form) formContainer.insertBefore(errorDiv, form);
        }
    }
    
    // Exécuter la vérification des messages de formulaire
    handleFormMessages();
    
    // ========== ANIMATIONS SPÉCIALES POUR LES CTA ==========
    
    // Animation "tremblante" pour attirer l'attention sur les CTA
    function setupCTAAttention() {
        const ctaButtons = document.querySelectorAll('.cta-button');

        // Fonction pour faire trembler légèrement un bouton avec une meilleure performance
        function shakeCTA(button) {
            // Vérifier si l'animation est déjà en cours
            if (button.hasAttribute('data-animating')) return;

            button.setAttribute('data-animating', 'true');
            button.animate([
                { transform: 'translateX(0)' },
                { transform: 'translateX(-3px)' },
                { transform: 'translateX(3px)' },
                { transform: 'translateX(-2px)' },
                { transform: 'translateX(2px)' },
                { transform: 'translateX(0)' }
            ], {
                duration: 500,
                iterations: 1
            }).onfinish = function() {
                button.removeAttribute('data-animating');
            };
        }

        // Vérifier la visibilité de la page
        let pageVisible = !document.hidden;
        document.addEventListener('visibilitychange', () => {
            pageVisible = !document.hidden;
        });

        // Animation initiale et récurrente optimisée
        ctaButtons.forEach((button, index) => {
            setTimeout(() => {
                if (pageVisible) shakeCTA(button);

                // Réduit la fréquence des animations pour améliorer les performances
                setInterval(() => {
                    if (pageVisible && Math.random() > 0.7) {
                        shakeCTA(button);
                    }
                }, 20000); // Animation toutes les 20 secondes au lieu de 12

            }, 3000 + (index * 500));
        });
    }
    
    // Lancer les animations de CTA
    setupCTAAttention();
    
    // ========== TEST DE VALIDATION DE FORMULAIRE AMÉLIORÉ ==========
    
    // Validation en temps réel des champs
    function setupLiveValidation() {
        const form = document.querySelector('#contact-form form');
        if (!form) return;
        
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        
        // Validation de l'email en temps réel
        if (emailInput) {
            emailInput.addEventListener('input', () => {
                const email = emailInput.value.trim();
                const validEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
                
                if (email && !validEmail) {
                    emailInput.classList.add('border-red-500');
                    
                    // Montrer un message d'erreur si pas déjà présent
                    if (!emailInput.nextElementSibling || !emailInput.nextElementSibling.classList.contains('validation-error')) {
                        const errorMsg = document.createElement('p');
                        errorMsg.className = 'text-red-500 text-sm mt-1 validation-error';
                        errorMsg.textContent = 'Adresse email invalide';
                        emailInput.parentNode.insertBefore(errorMsg, emailInput.nextSibling);
                    }
                } else {
                    emailInput.classList.remove('border-red-500');
                    
                    // Supprimer le message d'erreur s'il existe
                    if (emailInput.nextElementSibling && emailInput.nextElementSibling.classList.contains('validation-error')) {
                        emailInput.nextElementSibling.remove();
                    }
                }
            });
        }
        
        // Validation du nom (minimum 2 caractères)
        if (nameInput) {
            nameInput.addEventListener('input', () => {
                const name = nameInput.value.trim();
                
                if (name && name.length < 2) {
                    nameInput.classList.add('border-red-500');
                    
                    // Montrer un message d'erreur si pas déjà présent
                    if (!nameInput.nextElementSibling || !nameInput.nextElementSibling.classList.contains('validation-error')) {
                        const errorMsg = document.createElement('p');
                        errorMsg.className = 'text-red-500 text-sm mt-1 validation-error';
                        errorMsg.textContent = 'Le nom doit contenir au moins 2 caractères';
                        nameInput.parentNode.insertBefore(errorMsg, nameInput.nextSibling);
                    }
                } else {
                    nameInput.classList.remove('border-red-500');
                    
                    // Supprimer le message d'erreur s'il existe
                    if (nameInput.nextElementSibling && nameInput.nextElementSibling.classList.contains('validation-error')) {
                        nameInput.nextElementSibling.remove();
                    }
                }
            });
        }
    }
    
    // Activer la validation en temps réel
    setupLiveValidation();
    
    // ========== EXPÉRIENCE UTILISATEUR AMÉLIORÉE ==========
    
    // Bouton "Retour en haut" qui apparaît au scroll
    function setupScrollToTop() {
        // Créer le bouton
        const scrollButton = document.createElement('button');
        scrollButton.innerHTML = '&uarr;';
        scrollButton.className = 'fixed bottom-8 right-8 bg-[#0ed0ff] hover:bg-[#00b5e2] text-white w-12 h-12 rounded-full flex items-center justify-center shadow-lg transition-all duration-300 transform scale-0 opacity-0 z-50';
        scrollButton.setAttribute('aria-label', 'Retour en haut');
        
        // Ajouter au body
        document.body.appendChild(scrollButton);
        
        // Fonction pour afficher/masquer le bouton
        function toggleScrollButton() {
            if (window.scrollY > 500) {
                scrollButton.classList.remove('scale-0', 'opacity-0');
                scrollButton.classList.add('scale-100', 'opacity-100');
            } else {
                scrollButton.classList.remove('scale-100', 'opacity-100');
                scrollButton.classList.add('scale-0', 'opacity-0');
            }
        }
        
        // Événement de scroll
        window.addEventListener('scroll', toggleScrollButton);
        
        // Fonction de retour en haut avec animation
        scrollButton.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
    
    // Activer le bouton de retour en haut
    setupScrollToTop();
});

// ========== EFFET PARALLAXE POUR LE HERO ==========

// Effet de parallaxe léger sur le hero
window.addEventListener('scroll', () => {
    const hero = document.getElementById('hero');
    if (!hero) return;
    
    const scrollPosition = window.scrollY;
    
    // Effet de parallaxe avec une vitesse réduite
    if (scrollPosition < window.innerHeight) {
        hero.style.backgroundPositionY = `${scrollPosition * 0.2}px`;
    }
});

// ========== COUNTDOWN TIMER POUR L'OFFRE LIMITÉE ==========

// Fonction pour configurer un décompte
function setupCountdown() {
    // Date de fin (7 jours à partir de maintenant pour chaque visiteur)
    const storageKey = 'enorehab_countdown_end';
    let endDate;
    
    // Vérifier si une date existe déjà en stockage
    if (localStorage.getItem(storageKey)) {
        endDate = new Date(localStorage.getItem(storageKey));
    } else {
        // Créer une nouvelle date de fin (dans 7 jours)
        endDate = new Date();
        endDate.setDate(endDate.getDate() + 7);
        localStorage.setItem(storageKey, endDate.toISOString());
    }
    
    // Trouver l'élément où afficher le countdown
    const pricingSection = document.getElementById('pricing');
    if (!pricingSection) return;
    
    // Créer l'élément de décompte s'il n'existe pas
    let countdownEl = document.getElementById('countdown-timer');
    if (!countdownEl) {
        countdownEl = document.createElement('div');
        countdownEl.id = 'countdown-timer';
        countdownEl.className = 'mt-6 text-center';
        
        // Trouver où insérer le décompte
        const priceBox = pricingSection.querySelector('.border-[#0ed0ff]');
        if (priceBox && priceBox.parentNode) {
            priceBox.parentNode.insertBefore(countdownEl, priceBox.nextSibling);
        }
    }
    
    // Fonction pour mettre à jour le décompte
    function updateCountdown() {
        const now = new Date();
        const timeLeft = endDate - now;
        
        if (timeLeft <= 0) {
            countdownEl.innerHTML = "<p class='text-red-500 font-bold'>L'offre a expiré !</p>";
            return;
        }
        
        // Calculer jours, heures, minutes, secondes
        const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
        const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
        
        // Mettre à jour l'affichage
        countdownEl.innerHTML = `
            <p class="text-white mb-2 font-medium">Cette offre expire dans :</p>
            <div class="flex justify-center space-x-4">
                <div class="bg-[#111111] px-3 py-2 rounded">
                    <span class="text-[#0ed0ff] text-xl font-bold">${days}</span>
                    <span class="text-xs block">Jours</span>
                </div>
                <div class="bg-[#111111] px-3 py-2 rounded">
                    <span class="text-[#0ed0ff] text-xl font-bold">${hours}</span>
                    <span class="text-xs block">Heures</span>
                </div>
                <div class="bg-[#111111] px-3 py-2 rounded">
                    <span class="text-[#0ed0ff] text-xl font-bold">${minutes}</span>
                    <span class="text-xs block">Minutes</span>
                </div>
                <div class="bg-[#111111] px-3 py-2 rounded">
                    <span class="text-[#0ed0ff] text-xl font-bold">${seconds}</span>
                    <span class="text-xs block">Secondes</span>
                </div>
            </div>
        `;
    }
    
    // Mettre à jour immédiatement puis chaque seconde
    updateCountdown();
    setInterval(updateCountdown, 1000);
}

// Lancer le décompte au chargement complet de la page
window.addEventListener('load', setupCountdown);