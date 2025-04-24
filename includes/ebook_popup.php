<!-- Pop-up pour téléchargement d'ebook -->
<div id="ebook-popup" class="fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-50 hidden">
    <div class="bg-[#111111] rounded-lg max-w-4xl w-full overflow-hidden">
        <div class="flex flex-col md:flex-row">
            <!-- Image de l'ebook -->
            <div class="md:w-2/5 bg-gradient-to-br from-[#0ed0ff] to-[#00b5e2] flex items-center justify-center p-8">
                <img src="assets/img/ebook-cover.jpg" alt="Ebook Épaul - Guide de mobilité" class="max-w-full h-auto rounded-lg shadow-lg transform hover:scale-105 transition-transform">
            </div>

            <!-- Formulaire -->
            <div class="md:w-3/5 p-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-white">Guide gratuit : <span class="text-[#0ed0ff]">Mobilité des épaules</span></h2>
                    <button id="close-ebook-popup" class="text-gray-400 hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <p class="text-white mb-6">
                    Téléchargez notre guide gratuit pour améliorer la mobilité de vos épaules, prévenir les blessures
                    et optimiser vos performances en CrossFit, Haltérophilie et Hyrox.
                </p>

                <div class="mb-6">
                    <div class="flex items-start mb-2">
                        <span class="text-[#0ed0ff] text-xl mr-2 flex-shrink-0">✓</span>
                        <p class="text-gray-300">Un mini bilan avec scoring pour suivre tes progrès </p>
                    </div>
                    <div class="flex items-start mb-2">
                        <span class="text-[#0ed0ff] text-xl mr-2 flex-shrink-0">✓</span>
                        <p class="text-gray-300">Les exercices de mobilité incontournables </p>
                    </div>
                    <div class="flex items-start">
                        <span class="text-[#0ed0ff] text-xl mr-2 flex-shrink-0">✓</span>
                        <p class="text-gray-300">Une routine simple à intégrer en 5 min d’échauffement</p>
                    </div>
                </div>

                <form id="ebook-form" action="process_ebook.php" method="POST" class="space-y-4">
                    <!-- Token CSRF -->
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

                    <div>
                        <label for="ebook-name" class="sr-only">Nom</label>
                        <input type="text" id="ebook-name" name="name" placeholder="Votre nom" required
                               class="w-full p-3 bg-[#222222] border border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#0ed0ff] text-white transition duration-200">
                        <div class="error-message hidden text-red-500 text-sm mt-1"></div>
                    </div>

                    <div>
                        <label for="ebook-email" class="sr-only">Email</label>
                        <input type="email" id="ebook-email" name="email" placeholder="Votre email" required
                               class="w-full p-3 bg-[#222222] border border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#0ed0ff] text-white transition duration-200">
                        <div class="error-message hidden text-red-500 text-sm mt-1"></div>
                    </div>

                    <div class="flex items-start">
                        <input type="checkbox" id="ebook-consent" name="consent" required class="mt-1 mr-2">
                        <label for="ebook-consent" class="text-sm text-gray-400">
                            J'accepte de recevoir l'ebook et des informations de la part d'Enorehab. Consultez notre
                            <a href="privacy.php" class="text-[#0ed0ff] underline" target="_blank">politique de confidentialité</a>.
                        </label>
                    </div>

                    <button type="submit" class="w-full bg-[#0ed0ff] hover:bg-[#00b5e2] text-black font-bold text-lg py-3 px-6 rounded-lg transition-all duration-300 transform hover:scale-105">
                        Recevoir mon guide gratuit
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Script pour gérer le popup -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const popup = document.getElementById('ebook-popup');
        const openButtons = document.querySelectorAll('.open-ebook-popup');
        const closeButton = document.getElementById('close-ebook-popup');
        const form = document.getElementById('ebook-form');

        // Fonction pour ouvrir le popup
        function openPopup() {
            popup.classList.remove('hidden');
            // Animation d'entrée
            const modalContent = popup.querySelector('div');
            modalContent.style.opacity = 0;
            modalContent.style.transform = 'scale(0.9)';
            setTimeout(() => {
                modalContent.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                modalContent.style.opacity = 1;
                modalContent.style.transform = 'scale(1)';
            }, 10);
        }

        // Fonction pour fermer le popup
        function closePopup() {
            const modalContent = popup.querySelector('div');
            modalContent.style.opacity = 0;
            modalContent.style.transform = 'scale(0.9)';
            setTimeout(() => {
                popup.classList.add('hidden');
            }, 300);
        }

        // Ouvrir le popup quand on clique sur les boutons
        openButtons.forEach(button => {
            button.addEventListener('click', openPopup);
        });

        // Fermer le popup quand on clique sur le bouton de fermeture
        closeButton.addEventListener('click', closePopup);

        // Fermer le popup quand on clique en dehors
        popup.addEventListener('click', function(e) {
            if (e.target === popup) {
                closePopup();
            }
        });

        // Afficher automatiquement après X secondes (seulement pour les nouveaux visiteurs)
        if (!sessionStorage.getItem('popupShown')) {
            setTimeout(function() {
                openPopup();
                sessionStorage.setItem('popupShown', 'true');
            }, 30000); // 30 secondes
        }

        // Validation du formulaire
        if (form) {
            form.addEventListener('submit', function(e) {
                let valid = true;
                const nameInput = document.getElementById('ebook-name');
                const emailInput = document.getElementById('ebook-email');

                // Validation du nom
                if (!nameInput.value.trim()) {
                    showError(nameInput, 'Le nom est requis');
                    valid = false;
                } else if (nameInput.value.trim().length < 2) {
                    showError(nameInput, 'Le nom doit contenir au moins 2 caractères');
                    valid = false;
                } else {
                    hideError(nameInput);
                }

                // Validation de l'email
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailInput.value.trim()) {
                    showError(emailInput, 'L\'email est requis');
                    valid = false;
                } else if (!emailPattern.test(emailInput.value.trim())) {
                    showError(emailInput, 'Adresse email invalide');
                    valid = false;
                } else {
                    hideError(emailInput);
                }

                // Empêcher la soumission si le formulaire n'est pas valide
                if (!valid) {
                    e.preventDefault();
                }
            });
        }

        // Fonction pour afficher une erreur
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
    });
</script>