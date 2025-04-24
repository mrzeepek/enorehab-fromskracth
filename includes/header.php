<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    
    <!-- SEO Meta Tags -->
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="<?php echo $pageDescription; ?>">
    <meta name="keywords" content="<?php echo $pageKeywords; ?>">
    <meta name="author" content="Enorehab">
    
    <!-- Open Graph / Facebook Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://enorehab.com/">
    <meta property="og:title" content="<?php echo $pageTitle; ?>">
    <meta property="og:description" content="<?php echo $pageDescription; ?>">
    <meta property="og:image" content="https://enorehab.com/assets/img/og-image.jpg">
    
    <!-- Twitter Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $pageTitle; ?>">
    <meta name="twitter:description" content="<?php echo $pageDescription; ?>">
    <meta name="twitter:image" content="https://enorehab.com/assets/img/twitter-image.jpg">
    
    <!-- Favicon -->
    <link rel="icon" href="assets/img/logo-er.png">
    <link rel="apple-touch-icon" href="assets/img/logo-er.png">
    <link rel="shortcut icon" type="image/png" href="assets/img/logo-er.png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&family=Montserrat:wght@800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS - JIT via CDN pour la démo -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        // Configuration Tailwind avec les couleurs personnalisées
                        'primary': '#000000',
                        'secondary': '#111111',
                        'accent': '#0ed0ff',
                        'accent-dark': '#00b5e2',
                    },
                    fontFamily: {
                        'sans': ['Inter', 'sans-serif'],
                        'display': ['Montserrat', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/custom.css" rel="stylesheet">
    
    <!-- Analytics -->
    <?php include_once("includes/analytics.php"); ?>
</head>
<body class="bg-black text-white">
    <!-- Header / Navigation -->
<!-- Remplacez la ligne contenant le texte "Enorehab" dans le header.php par ce bloc: -->

<header class="fixed top-0 left-0 w-full z-50 bg-black bg-opacity-90 py-4">
    <div class="container mx-auto px-4 flex justify-between items-center">
        <a href="#" class="text-white font-bold flex items-center">
            <img src="assets/img/logo-er.png" alt="Enorehab Logo" class="h-12 mr-3">
            <span class="text-2xl"><span class="text-[#0ed0ff]">ENO</span>REHAB</span>
        </a>
        <nav class="hidden md:block">
            <ul class="flex space-x-8">
                <li><a href="#hero" class="text-white hover:text-[#0ed0ff] transition-colors">Accueil</a></li>
                <li><a href="#target" class="text-white hover:text-[#0ed0ff] transition-colors">Pour qui</a></li>
                <li><a href="#offer" class="text-white hover:text-[#0ed0ff] transition-colors">Offre</a></li>
                <li><a href="#pricing" class="text-white hover:text-[#0ed0ff] transition-colors">Prix</a></li>
                <li><a href="#faq" class="text-white hover:text-[#0ed0ff] transition-colors">FAQ</a></li>
            </ul>
        </nav>
        <a href="#booking" class="hidden md:inline-block bg-[#0ed0ff] hover:bg-[#00b5e2] text-white font-bold py-2 px-6 rounded-lg transition-all duration-300">
            Réserver
        </a>
        <button id="mobile-menu-button" class="md:hidden text-white" aria-label="Menu mobile">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>
    
    <!-- Mobile Menu -->
    <div id="mobile-menu" class="md:hidden hidden bg-[#111111] py-6">
        <div class="container mx-auto px-4">
            <ul class="space-y-4">
                <li><a href="#hero" class="block text-white hover:text-[#0ed0ff] transition-colors py-2">Accueil</a></li>
                <li><a href="#target" class="block text-white hover:text-[#0ed0ff] transition-colors py-2">Pour qui</a></li>
                <li><a href="#offer" class="block text-white hover:text-[#0ed0ff] transition-colors py-2">Offre</a></li>
                <li><a href="#pricing" class="block text-white hover:text-[#0ed0ff] transition-colors py-2">Prix</a></li>
                <li><a href="#faq" class="block text-white hover:text-[#0ed0ff] transition-colors py-2">FAQ</a></li>
                <li><a href="#booking" class="block bg-[#0ed0ff] hover:bg-[#00b5e2] text-white font-bold py-3 px-6 rounded-lg transition-all duration-300 text-center mt-4">Réserver</a></li>
            </ul>
        </div>
    </div>
</header>
    
    <!-- Main Content -->
    <main class="pt-16"> <!-- Padding-top pour compenser la hauteur du header fixe -->

    