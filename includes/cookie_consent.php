<!-- Bannière de consentement pour les cookies -->
<div id="cookie-consent-banner" class="fixed bottom-0 left-0 w-full bg-black bg-opacity-95 text-white py-4 px-6 flex flex-col md:flex-row justify-between items-center z-50 shadow-lg transform transition-transform duration-500" style="display: none; border-top: 2px solid #0ed0ff;">
    <div class="text-sm md:text-base mb-4 md:mb-0 md:mr-6">
        <p class="mb-2">
            Ce site utilise des cookies pour améliorer votre expérience et analyser le trafic.
            Vous pouvez choisir d'accepter ou de refuser l'utilisation des cookies.
        </p>
        <a href="privacy.php" class="text-[#0ed0ff] underline">En savoir plus sur notre politique de confidentialité</a>
    </div>
    <div class="flex flex-col sm:flex-row gap-3">
        <button id="cookie-accept-all" class="bg-[#0ed0ff] hover:bg-[#00b5e2] text-black font-medium py-2 px-4 rounded-lg transition-all">
            Tout accepter
        </button>
        <button id="cookie-accept-necessary" class="bg-[#333333] hover:bg-[#444444] text-white font-medium py-2 px-4 rounded-lg transition-all">
            Seulement nécessaires
        </button>
        <button id="cookie-customize" class="bg-transparent hover:bg-[#222222] text-white border border-white font-medium py-2 px-4 rounded-lg transition-all">
            Personnaliser
        </button>
    </div>
</div>

<!-- Modal de personnalisation des cookies -->
<div id="cookie-settings-modal" class="fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-50 hidden">
    <div class="bg-[#111111] rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-[#333333]">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold text-white">Paramètres des cookies</h2>
                <button id="close-cookie-settings" class="text-gray-400 hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
        <div class="p-6">
            <div class="space-y-6">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-lg font-semibold text-white">Cookies strictement nécessaires</h3>
                        <div class="relative">
                            <input type="checkbox" id="necessary-cookies" class="sr-only" checked disabled>
                            <label for="necessary-cookies" class="block w-12 h-6 rounded-full bg-[#0ed0ff] opacity-75 cursor-not-allowed"></label>
                            <span class="absolute right-1 top-1 bg-white w-4 h-4 rounded-full"></span>
                        </div>
                    </div>
                    <p class="text-sm text-gray-400">Ces cookies sont essentiels au fonctionnement du site et ne peuvent pas être désactivés.</p>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-lg font-semibold text-white">Cookies analytiques</h3>
                        <div class="relative">
                            <input type="checkbox" id="analytics-cookies" class="sr-only">
                            <label for="analytics-cookies" class="block w-12 h-6 rounded-full bg-gray-700 cursor-pointer"></label>
                            <span id="analytics-toggle" class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-all"></span>
                        </div>
                    </div>
                    <p class="text-sm text-gray-400">Ces cookies nous permettent d'analyser l'utilisation du site pour mesurer et améliorer les performances.</p>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-lg font-semibold text-white">Cookies marketing</h3>
                        <div class="relative">
                            <input type="checkbox" id="marketing-cookies" class="sr-only">
                            <label for="marketing-cookies" class="block w-12 h-6 rounded-full bg-gray-700 cursor-pointer"></label>
                            <span id="marketing-toggle" class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-all"></span>
                        </div>
                    </div>
                    <p class="text-sm text-gray-400">Ces cookies sont utilisés pour vous proposer des publicités pertinentes et des communications marketing.</p>
                </div>
            </div>
        </div>
        <div class="p-6 border-t border-[#333333] flex justify-end">
            <button id="save-cookie-settings" class="bg-[#0ed0ff] hover:bg-[#00b5e2] text-black font-medium py-2 px-6 rounded-lg transition-all">
                Enregistrer les préférences
            </button>
        </div>
    </div>
</div>

<!-- Script de gestion des cookies -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Fonctions de gestion des cookies
        function setCookie(name, value, days) {
            let expires = '';
            if (days) {
                const date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = '; expires=' + date.toUTCString();
            }
            document.cookie = name + '=' + (value || '') + expires + '; path=/; SameSite=Lax';
        }

        function getCookie(name) {
            const nameEQ = name + '=';
            const ca = document.cookie.split(';');
            for(let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
            }
            return null;
        }

        // Vérifier si le consentement a déjà été donné
        const consentGiven = getCookie('cookie_consent');
        const banner = document.getElementById('cookie-consent-banner');

        if (!consentGiven) {
            // Afficher la bannière si aucun consentement n'a été donné
            banner.style.display = 'flex';
        } else {
            // Appliquer les paramètres de cookies sauvegardés
            applyStoredCookieSettings();
        }

        // Gestionnaire pour accepter tous les cookies
        document.getElementById('cookie-accept-all').addEventListener('click', function() {
            setCookie('cookie_consent', 'all', 365);
            setCookie('cookie_analytics', 'true', 365);
            setCookie('cookie_marketing', 'true', 365);
            banner.style.display = 'none';
            applyStoredCookieSettings();
        });

        // Gestionnaire pour accepter uniquement les cookies nécessaires
        document.getElementById('cookie-accept-necessary').addEventListener('click', function() {
            setCookie('cookie_consent', 'necessary', 365);
            setCookie('cookie_analytics', 'false', 365);
            setCookie('cookie_marketing', 'false', 365);
            banner.style.display = 'none';
            applyStoredCookieSettings();
        });

        // Gestionnaire pour ouvrir les paramètres personnalisés
        document.getElementById('cookie-customize').addEventListener('click', function() {
            document.getElementById('cookie-settings-modal').classList.remove('hidden');

            // Définir l'état initial des toggles
            const analyticsEnabled = getCookie('cookie_analytics') === 'true';
            const marketingEnabled = getCookie('cookie_marketing') === 'true';

            document.getElementById('analytics-cookies').checked = analyticsEnabled;
            document.getElementById('marketing-cookies').checked = marketingEnabled;

            updateTogglePosition('analytics-toggle', analyticsEnabled);
            updateTogglePosition('marketing-toggle', marketingEnabled);
        });

        // Fermer la modal
        document.getElementById('close-cookie-settings').addEventListener('click', function() {
            document.getElementById('cookie-settings-modal').classList.add('hidden');
        });

        // Fonctionnalité des toggles
        document.getElementById('analytics-cookies').addEventListener('change', function() {
            updateTogglePosition('analytics-toggle', this.checked);
        });

        document.getElementById('marketing-cookies').addEventListener('change', function() {
            updateTogglePosition('marketing-toggle', this.checked);
        });

        // Enregistrer les paramètres
        document.getElementById('save-cookie-settings').addEventListener('click', function() {
            const analyticsEnabled = document.getElementById('analytics-cookies').checked;
            const marketingEnabled = document.getElementById('marketing-cookies').checked;

            setCookie('cookie_consent', 'custom', 365);
            setCookie('cookie_analytics', analyticsEnabled.toString(), 365);
            setCookie('cookie_marketing', marketingEnabled.toString(), 365);

            document.getElementById('cookie-settings-modal').classList.add('hidden');
            banner.style.display = 'none';

            applyStoredCookieSettings();
        });

        function updateTogglePosition(toggleId, isChecked) {
            const toggle = document.getElementById(toggleId);
            if (isChecked) {
                toggle.style.left = '7px';
                toggle.parentElement.previousElementSibling.classList.add('bg-[#0ed0ff]');
                toggle.parentElement.previousElementSibling.classList.remove('bg-gray-700');
            } else {
                toggle.style.left = '1px';
                toggle.parentElement.previousElementSibling.classList.remove('bg-[#0ed0ff]');
                toggle.parentElement.previousElementSibling.classList.add('bg-gray-700');
            }
        }

        function applyStoredCookieSettings() {
            const analyticsEnabled = getCookie('cookie_analytics') === 'true';
            const marketingEnabled = getCookie('cookie_marketing') === 'true';

            // Activer/désactiver les scripts analytiques
            if (analyticsEnabled) {
                enableAnalytics();
            } else {
                disableAnalytics();
            }

            // Activer/désactiver les scripts marketing
            if (marketingEnabled) {
                enableMarketing();
            } else {
                disableMarketing();
            }
        }

        // Fonctions pour activer/désactiver les scripts
        function enableAnalytics() {
            // Activez Google Analytics ici
            if (typeof gtag === 'function') {
                window['ga-disable-G-XXXXXXXXXX'] = false;
            }
        }

        function disableAnalytics() {
            // Désactivez Google Analytics ici
            window['ga-disable-G-XXXXXXXXXX'] = true;
        }

        function enableMarketing() {
            // Activez Facebook Pixel ici
            if (typeof fbq === 'function') {
                fbq('consent', 'grant');
            }
        }

        function disableMarketing() {
            // Désactivez Facebook Pixel ici
            if (typeof fbq === 'function') {
                fbq('consent', 'revoke');
            }
        }
    });
</script>