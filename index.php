<?php
/**
 * Enorehab - Landing Page
 *
 * Landing page pour service de bilan kiné online pour athlètes CrossFit/Hyrox/haltérophiles
 *
 * @version 1.0
 * @author Enorehab
 */

// Activer l'affichage des erreurs pour débogage (à commenter en production)
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// Démarrer la session pour le token CSRF
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Générer un token CSRF s'il n'existe pas
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Définition des méta-données pour le SEO
$pageTitle = "Enorehab | Accompagnement en ligne pour athlètes CrossFit, Hyrox et haltérophiles";
$pageDescription = "Diagnostic kiné expert 100% personnalisé pour continuer à performer malgré la douleur. Pour CrossFitters, athlètes Hyrox et haltérophiles.";
$pageKeywords = "kiné, crossfit, hyrox, haltérophilie, blessure, douleur, bilan, online, visio";


// Inclure l'en-tête
include_once("includes/header.php");

// ===== DÉBUT DES MODIFICATIONS =====
// Inclure le système de gestion des cookies
include_once("includes/cookie_consent.php");

// Inclure le popup pour l'ebook
include_once("includes/ebook_popup.php");
// Messages de succès/erreur pour l'ebook
if (isset($_GET['ebook_success']) && $_GET['ebook_success'] == 'true'): ?>
    <div id="ebook-success-message" class="fixed top-20 left-1/2 transform -translate-x-1/2 max-w-md w-full bg-green-900 bg-opacity-90 border border-green-500 rounded-lg p-4 text-white shadow-lg z-50">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium">
                    Votre ebook a été envoyé à votre adresse email avec succès !
                </p>
            </div>
            <div class="ml-auto pl-3">
                <div class="-mx-1.5 -my-1.5">
                    <button onclick="this.parentElement.parentElement.parentElement.parentElement.remove()" class="inline-flex text-green-400 hover:text-green-500">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script>
        setTimeout(function() {
            const message = document.getElementById('ebook-success-message');
            if (message) {
                message.classList.add('opacity-0', 'transition-opacity', 'duration-500');
                setTimeout(() => message.remove(), 500);
            }
        }, 5000);
    </script>
<?php elseif (isset($_GET['error_ebook'])): ?>
    <div id="ebook-error-message" class="fixed top-20 left-1/2 transform -translate-x-1/2 max-w-md w-full bg-red-900 bg-opacity-90 border border-red-500 rounded-lg p-4 text-white shadow-lg z-50">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium">
                    <?php echo htmlspecialchars(urldecode($_GET['error_ebook'])); ?>
                </p>
            </div>
            <div class="ml-auto pl-3">
                <div class="-mx-1.5 -my-1.5">
                    <button onclick="this.parentElement.parentElement.parentElement.parentElement.remove()" class="inline-flex text-red-400 hover:text-red-500">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script>
        setTimeout(function() {
            const message = document.getElementById('ebook-error-message');
            if (message) {
                message.classList.add('opacity-0', 'transition-opacity', 'duration-500');
                setTimeout(() => message.remove(), 500);
            }
        }, 5000);
    </script>
<?php endif;
// ===== FIN DES MODIFICATIONS =====

?>

    <!-- 1. HERO SECTION - 100% viewport -->
    <section id="hero" class="min-h-screen flex items-center justify-center bg-black text-white px-4">
        <div class="container mx-auto text-center">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold mb-6 text-[#0ed0ff] animate-fade-in">
                Continue à performer.<br class="hidden md:block">Malgré la douleur.
            </h1>
            <p class="text-xl md:text-2xl mb-10 max-w-3xl mx-auto">
                Le diagnostic kiné expert 100% personnalisé pour les athlètes qui refusent de s'arrêter.
            </p>
            <a href="#booking" class="cta-button inline-block bg-[#0ed0ff] hover:bg-[#00b5e2] text-white font-bold uppercase tracking-wide text-lg py-4 px-8 rounded-lg transition-all duration-300 transform hover:scale-105 shadow-lg">
                Réserve ton bilan maintenant
            </a>
        </div>
    </section>

    <!-- 2. POUR QUI C'EST - Fond alterné -->
    <section id="target" class="py-20 bg-[#111111] text-white px-4">
        <div class="container mx-auto">
            <h2 class="text-3xl md:text-4xl font-extrabold mb-16 text-center">Te reconnais tu ?</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- CrossFit -->
                <div class="bg-black p-8 rounded-lg text-center target-block" data-aos="fade-up" data-aos-delay="100">
                    <img src="assets/img/icon-crossfit.svg" alt="CrossFit Icon" class="w-20 h-20 mx-auto mb-6 text-[#0ed0ff]" loading="lazy">
                    <h3 class="text-xl md:text-2xl font-bold mb-4">Crossfit</h3>
                    <p class="text-lg">Tu dois adapter tous tes WODs, voire même éliminer certains mouvements, et tu es frustré de ne plus pouvoir t'entraîner comme avant.</p>
                </div>

                <!-- Hyrox -->
                <div class="bg-black p-8 rounded-lg text-center target-block" data-aos="fade-up" data-aos-delay="200">
                    <img src="assets/img/icon-hyrox.svg" alt="Hyrox Icon" class="w-20 h-20 mx-auto mb-6 text-[#0ed0ff]" loading="lazy">
                    <h3 class="text-xl md:text-2xl font-bold mb-4">Hyrox</h3>
                    <p class="text-lg">Tu t'es inscrit à une compétition qui approche à grands pas, mais une douleur persistante freine ta progression, et tu refuses de devoir annuler ta course.</p>
                </div>

                <!-- Haltéro -->
                <div class="bg-black p-8 rounded-lg text-center target-block" data-aos="fade-up" data-aos-delay="300">
                    <img src="assets/img/icon-weightlifting.svg" alt="Weightlifting Icon" class="w-20 h-20 mx-auto mb-6 text-[#0ed0ff]" loading="lazy">
                    <h3 class="text-xl md:text-2xl font-bold mb-4">Haltéro</h3>
                    <p class="text-lg">Tu n'arrives plus à charger lourd en arraché ou épaulé-jeté à cause d'une douleur persistante que tu t'efforces d'ignorer.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 3. TON HISTOIRE -->
    <section id="story" class="py-20 bg-black text-white px-4">
        <div class="container mx-auto">
            <div class="flex flex-col md:flex-row items-center gap-12">
                <div class="md:w-1/2" data-aos="fade-right">
                    <h2 class="text-3xl md:text-4xl font-extrabold mb-8">Je suis kinésithérapeute</h2>
                    <p class="text-lg mb-6"> et également pratiquante de crossfit et d'haltérophilie en compétitions depuis bientôt 4 ans. Chaque jour, je vois des athlètes ignorer leur douleur par peur de devoir interrompre leur entraînement ou de régresser, préférant pousser leur corps sans l'écouter...</p>
                    <p class="text-lg mb-6">Pourtant, ils ont tort de croire qu'une blessure signifie forcément arrêter de s'entraîner.</p>
                    <p class="text-lg mb-8"> C'est tout le contraire ! C'est pourquoi j'ai choisi d'unir mes compétences de kiné à mon expérience en crossfit pour vous proposer un accompagnement 100% personnalisé, pour vous permettre de dire adieu à la douleur – sans dire adieu à vos trainings.</p>
                </div>
                <div class="md:w-1/2" data-aos="fade-left">
                    <img src="assets/img/profile-photo.jpg" alt="Enora - Kiné et athlète" class="rounded-lg shadow-2xl w-full h-auto" loading="lazy">
                </div>
            </div>
        </div>
    </section>

    <!-- NOUVELLE SECTION : EBOOK -->
    <section id="ebook-promo" class="py-20 bg-black text-white px-4">
        <div class="container mx-auto">
            <div class="max-w-5xl mx-auto bg-gradient-to-r from-[#0ed0ff]/10 to-transparent p-10 rounded-lg border border-[#0ed0ff]/30">
                <div class="flex flex-col md:flex-row items-center gap-8">
                    <div class="md:w-1/3 flex justify-center">
                        <img src="assets/img/ebook-cover.jpg" alt="Ebook Épaul - Guide de mobilité" class="w-full max-w-[250px] h-auto rounded-lg shadow-lg transform hover:scale-105 transition-transform" loading="lazy">
                    </div>
                    <div class="md:w-2/3">
                        <h2 class="text-3xl md:text-4xl font-extrabold mb-4">Guide gratuit : <span class="text-[#0ed0ff]">Mobilité des épaules</span></h2>
                        <p class="text-lg mb-6">Améliorez la mobilité de vos épaules et prévenez les blessures avec notre guide gratuit. Idéal pour les athlètes de CrossFit, Haltérophilie et Hyrox.</p>

                        <div class="mb-6">
                            <div class="flex items-start mb-2">
                                <span class="text-[#0ed0ff] text-xl mr-2 flex-shrink-0">✓</span>
                                <p class="text-white">Exercices de mobilité essentiels</p>
                            </div>
                            <div class="flex items-start mb-2">
                                <span class="text-[#0ed0ff] text-xl mr-2 flex-shrink-0">✓</span>
                                <p class="text-white">Programme progressif sur 4 semaines</p>
                            </div>
                            <div class="flex items-start">
                                <span class="text-[#0ed0ff] text-xl mr-2 flex-shrink-0">✓</span>
                                <p class="text-white">Routine d'échauffement en 5 minutes</p>
                            </div>
                        </div>

                        <button class="open-ebook-popup inline-block bg-[#0ed0ff] hover:bg-[#00b5e2] text-black font-bold uppercase tracking-wide text-lg py-4 px-8 rounded-lg transition-all duration-300 transform hover:scale-105 shadow-lg">
                            Télécharger gratuitement
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- 4. OFFRE / VALEUR - Fond alterné -->
    <section id="offer" class="py-20 bg-[#111111] text-white px-4">
        <div class="container mx-auto">
            <h2 class="text-3xl md:text-4xl font-extrabold mb-16 text-center">Voici ce que comprend mon accompagnement :</h2>

            <div class="max-w-3xl mx-auto bg-black p-10 rounded-lg shadow-lg" data-aos="zoom-in">
                <ul class="space-y-6">
                    <li class="flex items-start">
                        <span class="text-[#0ed0ff] text-2xl mr-4 flex-shrink-0">✅</span>
                        <div>
                            <h3 class="text-xl font-bold mb-2">Bilan en visio ou par téléphone (30 min)</h3>
                            <p class="text-lg">Une consultation approfondie pour comprendre ta blessure et définir tes objectifs</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <span class="text-[#0ed0ff] text-2xl mr-4 flex-shrink-0">✅</span>
                        <div>
                            <h3 class="text-xl font-bold mb-2">Tests vidéo</h3>
                            <p class="text-lg">Un protocole précis pour identifier les causes de ta douleur à l'aide de retours vidéos.</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <span class="text-[#0ed0ff] text-2xl mr-4 flex-shrink-0">✅</span>
                        <div>
                            <h3 class="text-xl font-bold mb-2">Analyse vidéo personnalisée</h3>
                            <p class="text-lg">Une analyse détaillée de tes mouvements pour corriger les schémas problématiques, repérer d'éventuelles faiblesses ou déficits de mobilité, et les corriger efficacement.</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <span class="text-[#0ed0ff] text-2xl mr-4 flex-shrink-0">✅</span>
                        <div>
                            <h3 class="text-xl font-bold mb-2">Programme kiné sur-mesure (via TrueCoach)</h3>
                            <p class="text-lg">Des exercices adaptés à ta blessure, intégrés à ton emploi du temps et à ton entraînement.</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <span class="text-[#0ed0ff] text-2xl mr-4 flex-shrink-0">✅</span>
                        <div>
                            <h3 class="text-xl font-bold mb-2">Suivi via messagerie</h3>
                            <p class="text-lg">Je reste disponible à tout moment pour répondre à tes questions, écouter tes ressentis et ajuster ta rééducation selon l'évolution de tes symptômes.</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </section>

    <!-- 5. OFFRE SPÉCIALE / PRIX -->
    <section id="pricing" class="py-20 bg-black text-white px-4">
        <div class="container mx-auto text-center">
            <h2 class="text-3xl md:text-4xl font-extrabold mb-10">💥 Offre de lancement limitée</h2>

            <div class="max-w-lg mx-auto border-2 border-[#0ed0ff] rounded-lg bg-[#111111] p-10 mb-12" data-aos="flip-up">
                <div class="flex justify-center items-center gap-6 mb-6">
                    <!--<span class="line-through text-gray-400 text-2xl">149€</span>-->
                    <span class="text-4xl md:text-5xl font-bold text-white">119€ TTC</span>
                </div>
                <ul class="text-left mb-10 space-y-3">
                    <li class="flex items-center">
                        <span class="text-[#0ed0ff] mr-2">✓</span>
                        <span>Bilan complet personnalisé</span>
                    </li>
                    <li class="flex items-center">
                        <span class="text-[#0ed0ff] mr-2">✓</span>
                        <span>Programme sur-mesure</span>
                    </li>
                    <li class="flex items-center">
                        <span class="text-[#0ed0ff] mr-2">✓</span>
                        <span>Suivi 4 semaines</span>
                    </li>
                </ul>
                <a href="#booking" class="cta-button inline-block bg-[#0ed0ff] hover:bg-[#00b5e2] text-white font-bold uppercase tracking-wide text-lg py-4 px-8 rounded-lg transition-all duration-300 w-full">
                    Réserve ton bilan maintenant
                </a>
            </div>

            <div class="mt-16 max-w-4xl mx-auto">
                <!-- Section témoignages commentée -->
                <!--
                <h3 class="text-2xl font-bold mb-8 text-center">Ils m'ont fait confiance</h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-[#111111] p-6 rounded-lg">
                        <div class="flex items-center mb-4">
                            <div class="text-[#0ed0ff]">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                </svg>
                                [... autres étoiles ...]
                            </div>
                        </div>
                        <p class="text-gray-300 mb-4">"Après des mois de douleur au genou, je pouvais à peine faire un squat. Enora a identifié le problème et m'a donné un programme adapté à mes WODs. En 3 semaines, j'ai pu reprendre l'entraînement complet!"</p>
                        <p class="font-medium">Thomas D. - CrossFit Athlete</p>
                    </div>

                    [... autres témoignages ...]
                </div>
                -->
            </div>
        </div>
    </section>

    <!-- 6. FAQ -->
    <section id="faq" class="py-20 bg-[#111111] text-white px-4">
        <div class="container mx-auto">
            <h2 class="text-3xl md:text-4xl font-extrabold mb-16 text-center">Questions fréquentes</h2>

            <div class="max-w-3xl mx-auto space-y-10">
                <div class="bg-black p-8 rounded-lg" data-aos="fade-up" data-aos-delay="100">
                    <h3 class="text-2xl font-bold mb-4">Est-ce que la sécu ou ma mutuelle rembourse cette prestation ?</h3>
                    <p class="text-lg">Non, cette prestation n'est pas remboursée car elle est hors nomenclature (le tarif n'étant pas fixé par la sécurité sociale). Toutefois, certaines mutuelles peuvent couvrir une partie des frais, dans la limite d'un certain montant.</p>
                </div>

                <div class="bg-black p-8 rounded-lg" data-aos="fade-up" data-aos-delay="200">
                    <h3 class="text-2xl font-bold mb-4">Dois-je arrêter de m'entraîner ?</h3>
                    <p class="text-lg">Pas du tout ! L'objectif est justement de te permettre de continuer à t'entraîner, tout en traitant ta blessure. On adaptera certains mouvements et l'intensité si besoin.</p>
                </div>

                <div class="bg-black p-8 rounded-lg" data-aos="fade-up" data-aos-delay="300">
                    <h3 class="text-2xl font-bold mb-4">Et si la douleur persiste ?</h3>
                    <p class="text-lg">Un suivi hebdomadaire est prévu pour ajuster ton programme en fonction de ta progression. Si les résultats ne sont pas au rendez-vous, on réévaluera ensemble les approches. Tu restes libre d'interrompre le suivi à tout moment.</p>
                </div>

                <div class="bg-black p-8 rounded-lg" data-aos="fade-up" data-aos-delay="400">
                    <h3 class="text-2xl font-bold mb-4">Combien de temps faut-il pour se rétablir ?</h3>
                    <p class="text-lg">Chaque pathologie est différente, et chaque personne réagit différemment à une même pathologie. Il est donc difficile de donner une durée précise, mais tu pourras généralement constater une amélioration de tes symptômes dès les premières semaines de suivi.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Messages de succès/erreur -->
<?php if (isset($_GET['success']) && $_GET['success'] == 'true'): ?>
    <div id="success-message" class="max-w-md mx-auto mb-10 bg-green-900 bg-opacity-50 border border-green-500 rounded-lg p-6 text-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-green-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <h3 class="text-xl font-bold text-white mb-2">Demande envoyée avec succès !</h3>
        <p class="text-green-200 mb-4">Merci pour ta demande ! Je te contacterai très rapidement pour organiser ton bilan personnalisé.</p>
        <p class="text-white">Un email de confirmation t'a été envoyé.</p>
    </div>
<?php elseif (isset($_GET['error'])): ?>
    <div id="error-message" class="max-w-md mx-auto mb-10 bg-red-900 bg-opacity-50 border border-red-500 rounded-lg p-6">
        <h3 class="text-xl font-bold text-white mb-2">Une erreur est survenue</h3>
        <p class="text-red-200 mb-2">Merci de vérifier les informations saisies :</p>
        <ul class="list-disc pl-5 text-white">
            <?php
            $errors = explode('|', urldecode($_GET['error']));
            foreach ($errors as $error) {
                echo "<li>" . htmlspecialchars($error) . "</li>";
            }
            ?>
        </ul>
    </div>
<?php endif; ?>

    <!-- 7. CTA FINAL -->
    <section id="booking" class="py-20 bg-black text-white px-4">
        <div class="container mx-auto text-center">
            <h2 class="text-3xl md:text-4xl font-extrabold mb-10">Tu t'entraînes dur. Ton corps mérite un plan à son niveau.</h2>

            <!-- Formulaire de contact -->
            <div id="contact-form" class="max-w-md mx-auto mb-10" data-aos="fade-up">
                <form action="process_form.php" method="POST" class="space-y-4" novalidate>
                    <!-- Champ honeypot caché -->
                    <div style="display:none;" aria-hidden="true">
                        <label for="website">Site web (ne pas remplir)</label>
                        <input type="text" id="website" name="website" autocomplete="off" tabindex="-1">
                    </div>

                    <!-- Token CSRF -->
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <div>
                        <label for="name" class="sr-only">Nom</label>
                        <input type="text" id="name" name="name" placeholder="Ton nom" required
                               class="w-full p-4 bg-[#111111] border border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#0ed0ff] text-white transition duration-200">
                        <div class="error-message hidden text-red-500 text-sm mt-1"></div>
                    </div>
                    <div>
                        <label for="email" class="sr-only">Email</label>
                        <input type="email" id="email" name="email" placeholder="Ton email" required
                               class="w-full p-4 bg-[#111111] border border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#0ed0ff] text-white transition duration-200">
                        <div class="error-message hidden text-red-500 text-sm mt-1"></div>
                    </div>
                    <div>
                        <label for="phone" class="sr-only">Téléphone</label>
                        <input type="tel" id="phone" name="phone" placeholder="Ton téléphone"
                               class="w-full p-4 bg-[#111111] border border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#0ed0ff] text-white transition duration-200">
                    </div>
                    <div>
                        <label for="instagram" class="sr-only">Instagram</label>
                        <input type="text" id="instagram" name="instagram" placeholder="Ton instagram"
                               class="w-full p-4 bg-[#111111] border border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#0ed0ff] text-white transition duration-200">
                    </div>

                    <!-- reCAPTCHA conditionnel -->
                    <?php if (!isset($DISABLE_RECAPTCHA) || $DISABLE_RECAPTCHA !== true): ?>
                        <div class="flex justify-center">
                            <div class="g-recaptcha" data-sitekey="VOTRE_CLE_SITE_RECAPTCHA"></div>
                        </div>
                    <?php else: ?>
                        <div class="bg-yellow-900 bg-opacity-30 border border-yellow-500 rounded p-3 text-yellow-200 text-sm text-center">
                            <p>reCAPTCHA désactivé pour les tests locaux</p>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="cta-button bg-[#0ed0ff] hover:bg-[#00b5e2] text-white font-bold uppercase tracking-wide text-lg py-4 px-8 rounded-lg transition-all duration-300 w-full">
                        Réserve ton bilan maintenant
                    </button>
                </form>
            </div>

            <!-- Calendly (commenté) -->
            <!--
            <div id="calendly-widget" class="max-w-3xl mx-auto mb-10 hidden">
                <div class="calendly-inline-widget" data-url="https://calendly.com/enorehab/bilan" style="min-width:320px;height:630px;"></div>
                <script type="text/javascript" src="https://assets.calendly.com/assets/external/widget.js" async></script>
            </div>
            -->

            <p class="text-lg font-bold text-[#0ed0ff]">Places limitées</p>
        </div>
    </section>

    <!-- Bouton flottant pour l'ebook -->
    <div class="fixed bottom-8 left-8 z-40 md:block hidden">
        <button class="open-ebook-popup flex items-center space-x-2 bg-[#0ed0ff] hover:bg-[#00b5e2] text-black font-bold py-3 px-5 rounded-full shadow-lg transition-all duration-300 transform hover:scale-105">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z" />
            </svg>
            <span>Ebook gratuit</span>
        </button>
    </div>

<?php
// Inclure le pied de page
include_once("includes/footer.php");
?>