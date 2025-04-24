<?php
/**
 * Fonctions d'envoi d'emails HTML pour Enorehab
 * 
 * Ce fichier contient les fonctions pour envoyer des emails au format HTML
 * avec le template personnalisé Enorehab.
 */



/**
* Envoie un email de notification à l'administrateur pour une nouvelle demande de bilan
 *
 * @param array $data Données du formulaire
 * @param bool $db_success Succès de l'enregistrement en base
* @param string $db_error_message Message d'erreur BD (si échec)
 * @return bool Succès ou échec
 */
function send_admin_bilan_notification($data, $db_success = true, $db_error_message = '') {
    global $emailManager;

    // Vérifier si le manager est initialisé
    if (!isset($emailManager)) {
        require_once 'includes/email_system.php';
    }

    // Préparation des variables pour le template
    $variables = [
        'NAME' => htmlspecialchars($data['name']),
        'EMAIL' => htmlspecialchars($data['email']),
        'PHONE' => htmlspecialchars($data['phone'] ?? 'Non renseigné'),
        'INSTAGRAM' => htmlspecialchars($data['instagram'] ?? 'Non renseigné'),
        'DATE' => date('d/m/Y H:i:s'),
        'IP' => $_SERVER['REMOTE_ADDR'],
        'DB_STATUS' => $db_success ? 'Enregistré avec succès en base de données' : 'Échec de l\'enregistrement en base',
        'DB_BG_COLOR' => $db_success ? '#1a3a1a' : '#3a1a1a',  // Vert foncé ou rouge foncé
        'DB_ERROR_MESSAGE' => $db_error_message ? '<br><span style="color: #ff6b6b;">' . htmlspecialchars($db_error_message) . '</span>' : '',
        'YEAR' => date('Y')
    ];

    // Options pour l'envoi
    $options = [
        'reply_to' => $data['email'],
        'reply_to_name' => $data['name']
    ];

    // Envoyer l'email avec le template admin
    return $emailManager->sendTemplateEmail(
        'enora.lenez@enorehab.fr',
        "Nouvelle demande de bilan kiné - " . $data['name'],
        "admin_bilan_notification.html",  // Nom du template HTML
        $variables,
        $options
    );
}
/**
 * Envoie un email de confirmation au client
 *
 * @param string $to Email du destinataire
 * @param string $name Nom du client
 * @param string $phone Téléphone (optionnel)
 * @param string $instagram Instagram (optionnel)
 * @return bool Succès ou échec
 */
function send_client_bilan_confirmation($to, $name, $phone = '', $instagram = '') {
    global $emailManager;

    // Vérifier si le manager est initialisé
    if (!isset($emailManager)) {
        require_once 'includes/email_system.php';
    }

    // Données pour le template
    $variables = [
        'NAME' => htmlspecialchars($name),
        'EMAIL' => htmlspecialchars($to),
        'PHONE' => htmlspecialchars($phone ?: 'Non renseigné'),
        'INSTAGRAM' => htmlspecialchars($instagram ?: 'Non renseigné'),
        'YEAR' => date('Y')
    ];

    // Options pour l'envoi (expéditeur, etc.)
    $options = [
        'from' => [
            'email' => 'enora.lenez@enorehab.fr',
            'name' => 'Enora Lenez - Enorehab'
        ],
        'reply_to' => 'enora.lenez@enorehab.fr',
        'reply_to_name' => 'Enora Lenez'
    ];

    // Envoyer l'email avec le template
    return $emailManager->sendTemplateEmail(
        $to,
        "Confirmation de votre bilan kiné personnalisé",
        "client_bilan_confirmation.html",  // Nom du template HTML
        $variables,
        $options
    );
}

/**
 * Envoie un email de confirmation au client avec le template HTML
 * 
 * @param string $to Email du destinataire
 * @param string $name Nom du client
 * @param string $phone Téléphone du client (optionnel)
 * @param string $instagram Instagram du client (optionnel)
 * @return bool Succès ou échec de l'envoi
 */
function send_client_confirmation_email($to, $name, $phone = '', $instagram = '') {
    // Récupérer le template HTML
    $template = file_get_contents(__DIR__ . '/templates/template_email.html');

    if (!$template) {
        // Fallback si le template n'est pas trouvé
        return send_plain_client_email($to, $name, $phone, $instagram);
    }

    // Remplacer les variables dans le template
    $template = str_replace('{{NAME}}', htmlspecialchars($name), $template);
    $template = str_replace('{{EMAIL}}', htmlspecialchars($to), $template);
    $template = str_replace('{{PHONE}}', htmlspecialchars($phone ?: 'Non renseigné'), $template);

    // Remplacer l'année correctement avec un placeholder
    $template = str_replace('<?php echo date(\'Y\'); ?>', date('Y'), $template);
    $template = str_replace('{{YEAR}}', date('Y'), $template);

    // CORRECTION: En-têtes pour l'email HTML (modifié pour assurer la compatibilité)
    $subject = "Confirmation de ta demande de bilan Enorehab";
    $headers = "From: Enorehab <noreply@enorehab.fr>\r\n";
    $headers .= "Reply-To: enora.lenez@enorehab.fr\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n"; // Ajout d'un header pour la compatibilité

    // IMPORTANT: Création d'un message multi-part pour meilleure compatibilité
    $boundary = md5(time());
    $headers = "From: Enorehab <noreply@enorehab.fr>\r\n";
    $headers .= "Reply-To: enora.lenez@enorehab.fr\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    // Création d'un message texte comme fallback
    $plain_message = "Bonjour $name,\n\n";
    $plain_message .= "Nous avons bien reçu ta demande de bilan kiné personnalisé.\n\n";
    $plain_message .= "Je te contacterai très prochainement pour organiser notre rendez-vous en visio ou par téléphone.\n\n";
    $plain_message .= "Voici un récapitulatif de tes informations :\n";
    $plain_message .= "- Nom : $name\n";
    $plain_message .= "- Email : $to\n";
    $plain_message .= "- Téléphone : " . ($phone ?: "Non renseigné") . "\n";
    $plain_message .= "- Instagram : " . ($instagram ?: "Non renseigné") . "\n\n";
    $plain_message .= "Offre : Bilan kiné personnalisé à 119€\n\n";
    $plain_message .= "Ce que comprend ton accompagnement :\n";
    $plain_message .= "✓ Bilan en visio ou par téléphone (30 min)\n";
    $plain_message .= "✓ Tests vidéo\n";
    $plain_message .= "✓ Analyse vidéo personnalisée\n";
    $plain_message .= "✓ Programme kiné sur-mesure (via TrueCoach)\n";
    $plain_message .= "✓ Suivi via messagerie pendant 4 semaines\n\n";
    $plain_message .= "À très vite pour ton bilan !\n\n";
    $plain_message .= "Enora\n";
    $plain_message .= "Enorehab - Accompagnement à distance pour sportifs\n";
    $plain_message .= "enora.lenez@enorehab.fr\n";

    // Contenu complet de l'email (multipart)
    $message = "--$boundary\r\n";
    $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $message .= $plain_message . "\r\n\r\n";

    // Partie HTML
    $message .= "--$boundary\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $message .= $template . "\r\n\r\n";

    $message .= "--$boundary--";

    // Envoi de l'email avec le message multipart
    return mail($to, $subject, $message, $headers);
}
/**
 * Version texte brut de l'email de confirmation (fallback)
 */
function send_plain_client_email($to, $name, $phone = '', $instagram = '') {
    $subject = "Confirmation de ta demande de bilan Enorehab";
    
    $message = "Bonjour " . $name . ",\n\n";
    $message .= "Nous avons bien reçu ta demande de bilan kiné personnalisé.\n\n";
    $message .= "Je te contacterai très prochainement pour organiser notre rendez-vous en visio ou par téléphone.\n\n";
    $message .= "Voici un récapitulatif de tes informations :\n";
    $message .= "- Nom : " . $name . "\n";
    $message .= "- Email : " . $to . "\n";
    
    if (!empty($phone)) {
        $message .= "- Téléphone : " . $phone . "\n";
    }
    
    if (!empty($instagram)) {
        $message .= "- Instagram : " . $instagram . "\n";
    }
    
    $message .= "\nOffre : Bilan kiné personnalisé à 119€\n\n";
    
    $message .= "Ce que comprend ton accompagnement :\n";
    $message .= "✓ Bilan en visio ou par téléphone (30 min)\n";
    $message .= "✓ Tests vidéo\n";
    $message .= "✓ Analyse vidéo personnalisée\n";
    $message .= "✓ Programme kiné sur-mesure (via TrueCoach)\n";
    $message .= "✓ Suivi via messagerie pendant 4 semaines\n\n";
    
    $message .= "À très vite pour ton bilan !\n\n";
    $message .= "Enora\n";
    $message .= "Enorehab - Accompagnement à distance pour sportifs\n";
    $message .= "enora.lenez@enorehab.fr\n";
    
    $headers = "From: Enorehab <noreply@enorehab.fr>\r\n";
    $headers .= "Reply-To: enora.lenez@enorehab.fr\r\n";
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Envoie un email de notification à l'administrateur
 * 
 * @param array $data Données du formulaire
 * @param bool $db_success Statut de l'enregistrement en base de données
 * @param string $db_error_message Message d'erreur de la base de données (si applicable)
 * @return bool Succès ou échec de l'envoi
 */
function send_admin_notification_email($data, $db_success = true, $db_error_message = '') {
    $to = "enora.lenez@enorehab.fr";
    $subject = "Nouvelle demande de bilan Enorehab";
    
    // Créer un message HTML pour l'administrateur (plus simple mais toujours joli)
    $message = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Nouvelle demande</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            h1 { color: #0ed0ff; }
            .info { background-color: #f5f5f5; padding: 15px; border-left: 4px solid #0ed0ff; margin-bottom: 20px; }
            .label { font-weight: bold; }
            .success { color: green; }
            .error { color: red; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Nouvelle demande de bilan</h1>
            <div class="info">
                <p><span class="label">Nom :</span> ' . htmlspecialchars($data['name']) . '</p>
                <p><span class="label">Email :</span> ' . htmlspecialchars($data['email']) . '</p>';
    
    if (!empty($data['phone'])) {
        $message .= '<p><span class="label">Téléphone :</span> ' . htmlspecialchars($data['phone']) . '</p>';
    }
    
    if (!empty($data['instagram'])) {
        $message .= '<p><span class="label">Instagram :</span> ' . htmlspecialchars($data['instagram']) . '</p>';
    }
    
    $message .= '
                <p><span class="label">Offre :</span> Bilan kiné personnalisé à 119€</p>
                <p><span class="label">Date :</span> ' . date("d/m/Y H:i:s") . '</p>
                <p><span class="label">IP :</span> ' . $_SERVER['REMOTE_ADDR'] . '</p>
                <p><span class="label">Enregistré en BDD :</span> ' . 
                ($db_success ? '<span class="success">Oui</span>' : '<span class="error">Non</span> - ' . htmlspecialchars($db_error_message)) . '</p>
            </div>
            <p>Tu peux contacter cette personne pour organiser son bilan.</p>
        </div>
    </body>
    </html>';
    
    // En-têtes pour l'email HTML
    $headers = "From: Enorehab <noreply@enorehab.fr>\r\n";
    $headers .= "Reply-To: " . $data['email'] . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Fonction adaptative pour envoyer des emails en local ou en production
 * Cette fonction remplace la version dans config_local.php pour ajouter le support HTML
 */
function send_email_local_or_production($to, $subject, $message, $headers, $is_html = false) {
    // Détection automatique de l'environnement
    $is_local = ($_SERVER['SERVER_NAME'] === 'localhost' || 
                 $_SERVER['SERVER_NAME'] === '127.0.0.1' || 
                 $_SERVER['REMOTE_ADDR'] === '127.0.0.1' || 
                 $_SERVER['REMOTE_ADDR'] === '::1');
    
    if ($is_local) {
        // En local : simuler l'envoi et sauvegarder dans un fichier
        $email_dir = __DIR__ . '/logs/emails';
        if (!is_dir($email_dir)) {
            mkdir($email_dir, 0755, true);
        }
        
        $email_file = $email_dir . '/' . time() . '_' . md5($to . $subject) . '.html';
        
        // Construire le contenu du fichier avec les métadonnées
        $content = "Date: " . date('Y-m-d H:i:s') . "\n";
        $content .= "To: $to\n";
        $content .= "Subject: $subject\n";
        $content .= "Headers: $headers\n";
        $content .= "Is HTML: " . ($is_html ? 'Yes' : 'No') . "\n";
        $content .= "-------------------------\n";
        $content .= $message;
        
        // Écrire dans le fichier
        file_put_contents($email_file, $content);
        
        return true; // Simuler un succès
    } else {
        // En production : utiliser la fonction mail() normale
        return mail($to, $subject, $message, $headers);
    }
}