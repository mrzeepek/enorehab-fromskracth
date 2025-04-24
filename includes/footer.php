</main>
    
    <!-- Footer -->
    <footer class="bg-[#111111] py-16 text-white">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-8 md:mb-0 text-center md:text-left">
                    <a href="#" class="text-white text-2xl font-bold">
                        <span class="text-[#0ed0ff]">Eno</span>rehab
                    </a>
                    <p class="mt-2 text-gray-400">Accompagnement à distance pour sportifs</p>
                </div>
                <div class="flex flex-col md:flex-row items-center space-y-4 md:space-y-0 md:space-x-8">
    <a href="mailto:enora.lenez@enorehab.fr" class="text-gray-400 hover:text-[#0ed0ff] transition-colors">
        enora.lenez@enorehab.fr
    </a>
        <div class="flex space-x-6">
                <a href="https://instagram.com/enorehab" target="_blank" rel="noopener noreferrer" aria-label="Instagram" class="text-gray-400 hover:text-[#0ed0ff] transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-instagram">
                        <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                        <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                        <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                    </svg>
                </a>
            </div>
        </div>
            </div>
            
            <hr class="border-gray-800 my-8">
            
            <div class="flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-500 text-sm">&copy; <?php echo date('Y'); ?> Enorehab. Tous droits réservés.</p>
                <div class="mt-4 md:mt-0">
                    <a href="#" class="text-gray-500 text-sm hover:text-[#0ed0ff] transition-colors mr-6">Mentions légales</a>
                    <a href="#" class="text-gray-500 text-sm hover:text-[#0ed0ff] transition-colors">Politique de confidentialité</a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- AOS Animation Library -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <!-- JavaScript -->
    <script>
        // Initialisation AOS Animation Library
        document.addEventListener('DOMContentLoaded', function() {
            // Détection de connexion lente ou appareil bas de gamme
            const connectionType = navigator.connection ? navigator.connection.effectiveType : 'unknown';
            const isSlowConnection = connectionType === '2g' || connectionType === 'slow-2g';
            const isReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            // Adaptation des animations selon les conditions
            if (isSlowConnection || isReducedMotion) {
                document.body.classList.add('reduce-animation');

                // Configuration AOS simplifiée
                AOS.init({
                    duration: 300,
                    once: true,
                    disable: 'mobile',
                    offset: 50
                });
            } else {
                // Configuration AOS standard
                AOS.init({
                    duration: 800,
                    once: true,
                    offset: 100,
                });
            }
        });

        // Mobile Menu Toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });

        // Smooth Scroll pour les liens d'ancrage
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();

                const targetId = this.getAttribute('href');
                if (targetId === '#') return;

                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    const headerOffset = 80; // Hauteur du header fixe
                    const elementPosition = targetElement.getBoundingClientRect().top;
                    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });

                    // Fermer le menu mobile si ouvert
                    const mobileMenu = document.getElementById('mobile-menu');
                    if (!mobileMenu.classList.contains('hidden')) {
                        mobileMenu.classList.add('hidden');
                    }
                }
            });
        });

        // Animation spéciale pour les boutons CTA
        const ctaButtons = document.querySelectorAll('.cta-button');

        ctaButtons.forEach(button => {
            // Animation de pulse pour attirer l'attention
            setInterval(() => {
                button.classList.add('pulse-animation');

                // Supprime la classe après l'animation
                setTimeout(() => {
                    button.classList.remove('pulse-animation');
                }, 1000);
            }, 5000); // Répète toutes les 5 secondes
        });

        // Gestion du formulaire avec validation côté client
        const contactForm = document.querySelector('#contact-form form');

        if (contactForm) {
            contactForm.addEventListener('submit', function(e) {
                let valid = true;
                const nameInput = document.getElementById('name');
                const emailInput = document.getElementById('email');

                // Validation basique
                if (!nameInput.value.trim()) {
                    valid = false;
                    nameInput.classList.add('border-red-500');
                } else {
                    nameInput.classList.remove('border-red-500');
                }

                if (!emailInput.value.trim() || !emailInput.value.includes('@')) {
                    valid = false;
                    emailInput.classList.add('border-red-500');
                } else {
                    emailInput.classList.remove('border-red-500');
                }

                if (!valid) {
                    e.preventDefault();
                } else {
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
        }

        // Effet de compteur pour le prix barré (animation visuelle)
        function animateCounter(element, start, end, duration) {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                const currentValue = Math.floor(progress * (end - start) + start);
                element.innerHTML = `${currentValue}€`;
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        }

        // Observer pour déclencher l'animation du compteur
        const pricingSection = document.querySelector('#pricing');
        if (pricingSection) {
            const priceObserver = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting) {
                    const originalPrice = document.querySelector('#pricing .line-through');
                    if (originalPrice) {
                        animateCounter(originalPrice, 89, 149, 1500);
                    }
                    priceObserver.unobserve(pricingSection);
                }
            }, { threshold: 0.5 });

            priceObserver.observe(pricingSection);
        }
    </script>
    
    <!-- Main.js pour animations supplémentaires -->
    <script src="assets/js/main.js"></script>
</body>
</html>