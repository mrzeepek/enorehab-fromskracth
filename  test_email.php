<?php
// Chemins vers les fichiers PHPMailer
require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Créer une instance de PHPMailer
$mail = new PHPMailer(true);

try {
    // Configuration serveur
    $mail->SMTPDebug = 2;                      // Active le mode debug (à supprimer en production)
    $mail->isSMTP();                           // Utiliser SMTP
    $mail->Host       = 'smtp.ionos.fr';       // Serveur SMTP
    $mail->SMTPAuth   = true;                  // Activer l'authentification SMTP
    $mail->Username   = 'enora.lenez@enorehab.fr';     // Votre adresse email Ionos
    $mail->Password   = 'Despouille1134!';    // Votre mot de passe
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
    $mail->Port       = 465;                   // Port SSL
    $mail->CharSet    = 'UTF-8';               // Jeu de caractères

    // Options SSL supplémentaires pour éviter les erreurs de certificat
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];

    // Destinataires
    $mail->setFrom('enora.lenez@enorehab.fr', 'Enorehab');
    $mail->addAddress('marko34ii@gmail.com'); // Adresse où envoyer le test

    // Contenu
    $mail->isHTML(true);
    $mail->Subject = 'Test email Ionos avec PHPMailer';
    $mail->Body    = 'Si vous voyez ce message, <b>la configuration fonctionne</b>!';
    $mail->AltBody = 'Si vous voyez ce message, la configuration fonctionne!';

    $mail->send();
    echo 'Message envoyé avec succès';
} catch (Exception $e) {
    echo "Échec de l'envoi du message. Erreur: {$mail->ErrorInfo}";
}