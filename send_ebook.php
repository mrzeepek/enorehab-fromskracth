<?php
/**
 * Enorehab - Envoi d'ebook aux inscrits
 *
 * Ce script envoie l'ebook de mobilité "Épaul" aux utilisateurs
 * avec un template HTML qui respecte la charte graphique du site.
 * Il enregistre également les informations utilisateur en base de données
 * et envoie une notification à l'administrateur.
 */

// Activer l'affichage des erreurs (à désactiver en production)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Démarrer la session (nécessaire pour CSRF)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Générer un token CSRF s'il n'existe pas
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Inclure les dépendances
require_once 'includes/logger.php';
require_once 'includes/email_system.php';
require_once 'includes/db_config.php';

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Erreur de sécurité");
    }

    // Récupération et validation des données
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $consent = isset($_POST['consent']) ? true : false;

    // Validation des champs obligatoires
    $errors = [];

    if (empty($name)) {
        $errors[] = "Le nom est requis";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Une adresse email valide est requise";
    }

    if (!$consent) {
        $errors[] = "Vous devez accepter les conditions pour recevoir l'ebook";
    }

    if (empty($errors)) {
        // Variables pour suivre les résultats des opérations
        $db_success = false;
        $email_success = false;
        $admin_email_success = false;

        // 1. Enregistrement en base de données
        try {
            $pdo = getDbConnection();

            if (!$pdo) {
                throw new Exception("Impossible d'établir une connexion à la base de données");
            }

            // Vérifier si l'email existe déjà pour éviter les doublons
            $stmt = $pdo->prepare("SELECT id FROM enorehab_ebook_subscribers WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$existingUser) {
                // Créer la table si elle n'existe pas
                $pdo->exec("CREATE TABLE IF NOT EXISTS enorehab_ebook_subscribers (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    email VARCHAR(100) NOT NULL UNIQUE,
                    download_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                    ip_address VARCHAR(45),
                    consent BOOLEAN DEFAULT 1,
                    mail_list BOOLEAN DEFAULT 1
                )");

                // Insertion des données
                $stmt = $pdo->prepare("INSERT INTO enorehab_ebook_subscribers (name, email, ip_address, consent, mail_list) 
                                   VALUES (:name, :email, :ip, :consent, :mail_list)");

                $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':ip' => $_SERVER['REMOTE_ADDR'],
                    ':consent' => $consent ? 1 : 0,
                    ':mail_list' => $consent ? 1 : 0
                ]);

                $db_success = true;
                log_info('Nouvel abonné ebook enregistré en DB', [
                    'email' => $email,
                    'id' => $pdo->lastInsertId()
                ]);
            } else {
                // Mettre à jour la date de téléchargement
                $stmt = $pdo->prepare("UPDATE enorehab_ebook_subscribers SET download_date = NOW() WHERE email = :email");
                $stmt->execute([':email' => $email]);

                $db_success = true;
                log_info('Abonné ebook existant, mise à jour', [
                    'email' => $email
                ]);
            }

            // Obtenir les statistiques
            $totalDownloads = $pdo->query("SELECT COUNT(*) FROM enorehab_ebook_subscribers")->fetchColumn();
            $todayDownloads = $pdo->query("SELECT COUNT(*) FROM enorehab_ebook_subscribers WHERE DATE(download_date) = CURDATE()")->fetchColumn();
            $mailListCount = $pdo->query("SELECT COUNT(*) FROM enorehab_ebook_subscribers WHERE mail_list = 1")->fetchColumn();

        } catch (PDOException $e) {
            $db_error_message = $e->getMessage();
            log_error('Erreur DB lors de l\'enregistrement d\'un abonné ebook', [
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        }

        // 2. Envoi de l'email avec l'ebook au client
        try {
            // Créer une instance de PHPMailer
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            // Configuration serveur
            $mail->SMTPDebug = 0;                       // 0 = pas de debug, 2 = debug complet
            $mail->isSMTP();                            // Utiliser SMTP
            $mail->Host       = 'smtp.ionos.fr';        // Serveur SMTP
            $mail->SMTPAuth   = true;                   // Activer l'authentification SMTP
            $mail->Username   = 'enora.lenez@enorehab.fr'; // Votre adresse email
            $mail->Password   = 'Despouille1134!';   // Votre mot de passe (à remplacer)
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; // SSL
            $mail->Port       = 465;                    // Port SSL
            $mail->CharSet    = 'UTF-8';                // Jeu de caractères

            // Options SSL pour éviter les erreurs de certificat
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            // Destinataires
            $mail->setFrom('enora.lenez@enorehab.fr', 'Enorehab');
            $mail->addAddress($email, $name);
            $mail->addReplyTo('enora.lenez@enorehab.fr', 'Enorehab');

            // Ajouter la pièce jointe (l'ebook)
            $ebookPath = 'assets/ebooks/epaul-mobilite.pdf';
            if (file_exists($ebookPath)) {
                $mail->addAttachment($ebookPath, 'Epaul - Guide de mobilité.pdf');
            } else {
                throw new Exception("L'ebook n'a pas été trouvé sur le serveur.");
            }

            // Préparer le template HTML
            $htmlTemplate = file_get_contents('templates/emails/ebook_template.html');

            // Remplacer les variables dans le template
            $htmlTemplate = str_replace('{{NAME}}', htmlspecialchars($name), $htmlTemplate);
            $htmlTemplate = str_replace('{{EMAIL}}', htmlspecialchars($email), $htmlTemplate);
            $htmlTemplate = str_replace('{{YEAR}}', date('Y'), $htmlTemplate);

            // Contenu du message
            $mail->isHTML(true);
            $mail->Subject = 'Votre ebook gratuit : Épaul - Guide de mobilité';
            $mail->Body    = $htmlTemplate;
            $mail->AltBody = "Bonjour $name,\n\nMerci de votre intérêt pour notre guide de mobilité \"Épaul\". Vous trouverez votre ebook en pièce jointe à cet email.\n\nCe guide vous aidera à améliorer la mobilité de vos épaules, réduire les douleurs et optimiser vos performances sportives.\n\nCordialement,\nEnora\nEnorehab";

            // Envoyer l'email
            $mail->send();
            $email_success = true;

        } catch (Exception $e) {
            $errorMessage = "Une erreur est survenue lors de l'envoi de l'email : " . $e->getMessage();
            log_error('Erreur d\'envoi d\'email au client', [
                'message' => $e->getMessage(),
                'email' => $email
            ]);
        }

        // 3. Envoi d'un email de notification à l'administrateur
        try {
            $adminMail = new PHPMailer\PHPMailer\PHPMailer(true);

            // Configuration serveur
            $adminMail->SMTPDebug = 0;
            $adminMail->isSMTP();
            $adminMail->Host       = 'smtp.ionos.fr';
            $adminMail->SMTPAuth   = true;
            $adminMail->Username   = 'enora.lenez@enorehab.fr';
            $adminMail->Password   = 'Despouille1134!';
            $adminMail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $adminMail->Port       = 465;
            $adminMail->CharSet    = 'UTF-8';

            // Options SSL
            $adminMail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            // Destinataires
            $adminMail->setFrom('noreply@enorehab.fr', 'Système Enorehab');
            $adminMail->addAddress('enora.lenez@enorehab.fr', 'Enora Lenez');
            $adminMail->addReplyTo($email, $name);

            // Préparer le template HTML pour l'admin
            $adminTemplate = file_get_contents('templates/emails/admin_ebook_notification.html');

            // Remplacer les variables dans le template
            $adminTemplate = str_replace('{{NAME}}', htmlspecialchars($name), $adminTemplate);
            $adminTemplate = str_replace('{{EMAIL}}', htmlspecialchars($email), $adminTemplate);
            $adminTemplate = str_replace('{{DATE}}', date('d/m/Y H:i:s'), $adminTemplate);
            $adminTemplate = str_replace('{{IP}}', $_SERVER['REMOTE_ADDR'], $adminTemplate);
            $adminTemplate = str_replace('{{CONSENT}}', $consent ? 'Oui' : 'Non', $adminTemplate);
            $adminTemplate = str_replace('{{DB_SUCCESS}}', $db_success ? 'Oui' : 'Non', $adminTemplate);
            $adminTemplate = str_replace('{{DB_COLOR}}', $db_success ? '#0ed0ff' : '#ff6b6b', $adminTemplate);
            $adminTemplate = str_replace('{{YEAR}}', date('Y'), $adminTemplate);

            // Statistiques (si disponibles)
            $adminTemplate = str_replace('{{TOTAL_DOWNLOADS}}', isset($totalDownloads) ? $totalDownloads : '?', $adminTemplate);
            $adminTemplate = str_replace('{{TODAY_DOWNLOADS}}', isset($todayDownloads) ? $todayDownloads : '?', $adminTemplate);
            $adminTemplate = str_replace('{{MAIL_LIST_COUNT}}', isset($mailListCount) ? $mailListCount : '?', $adminTemplate);

            // Contenu du message
            $adminMail->isHTML(true);
            $adminMail->Subject = 'Nouveau téléchargement d\'ebook - ' . $name;
            $adminMail->Body    = $adminTemplate;
            $adminMail->AltBody = "Nouveau téléchargement d'ebook\n\nNom: $name\nEmail: $email\nDate: " . date('d/m/Y H:i:s') . "\nEnregistré en BD: " . ($db_success ? 'Oui' : 'Non');

            // Envoyer l'email
            $adminMail->send();
            $admin_email_success = true;

        } catch (Exception $e) {
            log_error('Erreur d\'envoi d\'email à l\'admin', [
                'message' => $e->getMessage()
            ]);
        }

        // 4. Redirection en fonction des résultats
        if ($email_success) {
            // Redirection avec succès (même si DB échoue, l'utilisateur a reçu son ebook)
            header("Location: index.php?ebook_success=true");
            exit;
        } else {
            // Problème avec l'envoi de l'email
            header("Location: index.php?ebook_error=email_failed");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>Télécharger l'ebook | Enorehab</title>
    <meta name="description" content="Téléchargez gratuitement notre guide de mobilité pour les épaules destiné aux athlètes CrossFit, Hyrox et haltérophiles.">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&family=Montserrat:wght@800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS - CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#000000',
                        'secondary': '#111111',
                        'accent': '#0ed0ff',
                        'accent-dark': '#00b5e2',
                    }
                }
            }
        }
    </script>

    <!-- Custom CSS -->
    <link href="assets/css/custom.css" rel="stylesheet">
</head>
<body class="bg-black text-white">
<div class="min-h-screen flex items-center justify-center px-4 py-12">
    <div class="max-w-md w-full space-y-8 bg-[#111111] p-8 rounded-lg shadow-lg">

        <?php if (isset($errorMessage)): ?>
            <div class="bg-red-900 bg-opacity-50 border border-red-500 rounded-lg p-4 mb-6">
                <p class="text-white"><?php echo $errorMessage; ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-900 bg-opacity-50 border border-red-500 rounded-lg p-4 mb-6">
                <ul class="list-disc pl-5 text-white">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="text-center mb-6">
            <a href="index.php" class="inline-block">
                <img src="assets/img/logo-er.png" alt="Enorehab Logo" class="h-16 mx-auto mb-2">
            </a>
            <h2 class="text-2xl font-bold text-white">Guide gratuit : <span class="text-[#0ed0ff]">Mobilité des épaules</span></h2>
            <p class="text-gray-300 mt-2">Remplissez ce formulaire pour recevoir notre ebook gratuitement</p>
        </div>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="space-y-6">
            <!-- Token CSRF -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div>
                <label for="name" class="sr-only">Nom</label>
                <input id="name" name="name" type="text" required
                       class="w-full px-4 py-3 border border-gray-700 bg-[#222222] rounded-lg focus:ring-[#0ed0ff] focus:border-[#0ed0ff] text-white"
                       placeholder="Votre nom"
                       value="">
            </div>
            <div>
                <label for="email" class="sr-only">Email</label>
                <input id="email" name="email" type="email" required
                       class="w-full px-4 py-3 border border-gray-700 bg-[#222222] rounded-lg focus:ring-[#0ed0ff] focus:border-[#0ed0ff] text-white"
                       placeholder="Votre email"
                       value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
            </div>

            <div class="flex items-start">
                <input id="consent" name="consent" type="checkbox" required
                       class="h-4 w-4 mt-1 text-[#0ed0ff] border-gray-700 rounded bg-[#222222] focus:ring-[#0ed0ff]"
                    <?php echo isset($consent) && $consent ? 'checked' : ''; ?>>
                <label for="consent" class="ml-2 block text-sm text-gray-300">
                    J'accepte de recevoir l'ebook et des informations de la part d'Enorehab. Consultez notre
                    <a href="privacy.php" class="text-[#0ed0ff] underline" target="_blank">politique de confidentialité</a>.
                </label>
            </div>

            <div>
                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-lg font-medium text-black bg-[#0ed0ff] hover:bg-[#00b5e2] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#0ed0ff] transition-all duration-300">
                    Recevoir mon guide gratuit
                </button>
            </div>
        </form>

        <div class="mt-6 text-center">
            <a href="index.php" class="text-[#0ed0ff] hover:underline">Retour à l'accueil</a>
        </div>
    </div>
</div>
</body>
</html>