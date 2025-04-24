<?php
/**
 * Système de gestion des emails avec PHPMailer
 *
 * Ce fichier remplace les fonctions d'email existantes
 * avec une solution plus robuste utilisant PHPMailer
 */

// Autoload de Composer (si vous utilisez Composer)
// require 'vendor/autoload.php';

// Si vous n'utilisez pas Composer, incluez manuellement les fichiers PHPMailer
require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Classe de gestion des templates d'emails
 */
class EmailTemplateManager {
    private $templateDir;

    /**
     * Constructeur
     *
     * @param string $templateDir Dossier contenant les templates
     */
    public function __construct($templateDir = null) {
        $this->templateDir = $templateDir ?: __DIR__ . '/../templates/emails/';

        // Créer le répertoire s'il n'existe pas
        if (!is_dir($this->templateDir)) {
            mkdir($this->templateDir, 0755, true);
        }
    }

    /**
     * Récupère un template et remplace les variables
     *
     * @param string $templateName Nom du fichier de template
     * @param array $variables Variables à remplacer
     * @return string HTML du template avec variables remplacées
     */
    public function getRenderedTemplate($templateName, $variables = []) {
        // Vérifier plusieurs chemins possibles pour plus de fiabilité
        $possiblePaths = [
            $this->templateDir . $templateName,
            $this->templateDir . '/' . $templateName,
            __DIR__ . '/../templates/emails/' . $templateName,
            __DIR__ . '/../templates/' . $templateName
        ];

        $templatePath = null;
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $templatePath = $path;
                break;
            }
        }

        if (!$templatePath) {
            // Journaliser tous les chemins vérifiés pour faciliter le débogage
            if (function_exists('log_error')) {
                log_error("Template introuvable", [
                    'template' => $templateName,
                    'checked_paths' => $possiblePaths
                ]);
            }
            throw new Exception("Le template $templateName n'existe pas");
        }

        $template = file_get_contents($templatePath);

        // Remplacer les variables
        foreach ($variables as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }

        // Remplacer l'année actuelle
        $template = str_replace('{{YEAR}}', date('Y'), $template);

        return $template;
    }

    /**
     * Ajoute un nouveau template
     *
     * @param string $templateName Nom du fichier de template
     * @param string $content Contenu HTML du template
     * @return bool Succès ou échec
     */
    public function addTemplate($templateName, $content) {
        $templatePath = $this->templateDir . $templateName;
        return file_put_contents($templatePath, $content) !== false;
    }
}

/**
 * Classe de gestion des emails
 */
class EmailManager {
    private $mailer;
    private $templateManager;
    private $defaultFrom = ['email' => 'noreply@enorehab.fr', 'name' => 'Enorehab'];
    private $isLocal;

    /**
     * Constructeur
     *
     * @param array $smtpConfig Configuration SMTP (optionnel)
     */
    public function __construct($smtpConfig = null) {
        $this->mailer = new PHPMailer(true);
        $this->templateManager = new EmailTemplateManager();

        // Détection automatique de l'environnement
        $this->isLocal = ($_SERVER['SERVER_NAME'] === 'localhost' ||
            $_SERVER['SERVER_NAME'] === '127.0.0.1' ||
            $_SERVER['REMOTE_ADDR'] === '127.0.0.1' ||
            $_SERVER['REMOTE_ADDR'] === '::1');

        // Configuration par défaut
        $this->mailer->CharSet = 'UTF-8';

        // Configuration SMTP si fournie
        if ($smtpConfig) {
            $this->setupSMTP($smtpConfig);
        }
    }

    /**
     * Configure SMTP
     *
     * @param array $config Configuration SMTP
     */
    public function setupSMTP($config) {
        $this->mailer->isSMTP();
        $this->mailer->Host = $config['host'];
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $config['username'];
        $this->mailer->Password = $config['password'];
        $this->mailer->SMTPSecure = $config['secure'] ?? PHPMailer::ENCRYPTION_SMTPS; // SSL
        $this->mailer->Port = $config['port'] ?? 465;                                // Port 465

        // Ajouter ces options pour plus de fiabilité
        $this->mailer->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
    }
    /**
     * Définit l'expéditeur par défaut
     *
     * @param string $email Email de l'expéditeur
     * @param string $name Nom de l'expéditeur
     */
    public function setDefaultFrom($email, $name = '') {
        $this->defaultFrom = ['email' => $email, 'name' => $name];
    }

    /**
     * Envoie un email
     *
     * @param string $to Email du destinataire
     * @param string $subject Sujet de l'email
     * @param string $body Corps de l'email (HTML)
     * @param array $options Options supplémentaires
     * @return bool Succès ou échec
     */
    public function sendEmail($to, $subject, $body, $options = []) {
        try {
            // En local, simuler l'envoi
            if ($this->isLocal && !isset($options['force_send'])) {
                return $this->saveLocalEmail($to, $subject, $body, $options);
            }

            // Réinitialiser pour un nouvel envoi
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            // Destinataire
            $this->mailer->addAddress($to, $options['to_name'] ?? '');

            // Expéditeur
            $from = $options['from'] ?? $this->defaultFrom;
            $this->mailer->setFrom($from['email'], $from['name']);

            // Répondre à
            if (isset($options['reply_to'])) {
                $this->mailer->addReplyTo($options['reply_to'], $options['reply_to_name'] ?? '');
            }

            // CC et BCC
            if (isset($options['cc'])) {
                foreach ((array)$options['cc'] as $cc) {
                    $this->mailer->addCC($cc);
                }
            }

            if (isset($options['bcc'])) {
                foreach ((array)$options['bcc'] as $bcc) {
                    $this->mailer->addBCC($bcc);
                }
            }

            // Pièces jointes
            if (isset($options['attachments'])) {
                foreach ($options['attachments'] as $attachment) {
                    $this->mailer->addAttachment(
                        $attachment['path'],
                        $attachment['name'] ?? '',
                        $attachment['encoding'] ?? 'base64',
                        $attachment['type'] ?? '',
                        $attachment['disposition'] ?? 'attachment'
                    );
                }
            }

            // Contenu
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;

            // Version texte
            if (isset($options['alt_body'])) {
                $this->mailer->AltBody = $options['alt_body'];
            } else {
                // Créer une version texte automatiquement
                $this->mailer->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $body));
            }

            // Envoyer l'email
            return $this->mailer->send();

        } catch (Exception $e) {
            if (function_exists('log_error')) {
                log_error('Erreur d\'envoi d\'email', [
                    'message' => $e->getMessage(),
                    'to' => $to,
                    'subject' => $subject
                ]);
            }

            return false;
        }
    }

    /**
     * Envoie un email à partir d'un template
     *
     * @param string $to Email du destinataire
     * @param string $subject Sujet de l'email
     * @param string $templateName Nom du fichier de template
     * @param array $variables Variables à remplacer dans le template
     * @param array $options Options supplémentaires
     * @return bool Succès ou échec
     */
    public function sendTemplateEmail($to, $subject, $templateName, $variables = [], $options = []) {
        try {
            $body = $this->templateManager->getRenderedTemplate($templateName, $variables);
            return $this->sendEmail($to, $subject, $body, $options);
        } catch (Exception $e) {
            if (function_exists('log_error')) {
                log_error('Erreur de template email', [
                    'message' => $e->getMessage(),
                    'template' => $templateName
                ]);
            }

            return false;
        }
    }

    /**
     * Simule l'envoi d'email en local et sauvegarde dans un fichier
     */
    private function saveLocalEmail($to, $subject, $body, $options) {
        $email_dir = __DIR__ . '/logs/emails';
        if (!is_dir($email_dir)) {
            mkdir($email_dir, 0755, true);
        }

        $email_file = $email_dir . '/' . time() . '_' . md5($to . $subject) . '.html';

        // Construire le contenu du fichier avec les métadonnées
        $content = "Date: " . date('Y-m-d H:i:s') . "\n";
        $content .= "To: $to\n";
        $content .= "Subject: $subject\n";

        // Options
        $from = $options['from'] ?? $this->defaultFrom;
        $content .= "From: {$from['email']} ({$from['name']})\n";

        if (isset($options['reply_to'])) {
            $content .= "Reply-To: {$options['reply_to']}\n";
        }

        $content .= "-------------------------\n";
        $content .= $body;

        // Écrire dans le fichier
        file_put_contents($email_file, $content);

        return true;
    }
}

/**
 * Fonction pour envoyer un email de confirmation au client
 *
 * @param string $to Email du destinataire
 * @param string $name Nom du client
 * @param string $phone Téléphone du client (optionnel)
 * @param string $instagram Instagram du client (optionnel)
 * @return bool Succès ou échec de l'envoi
 */
function send_client_confirmation_email($to, $name, $phone = '', $instagram = '') {
    global $emailManager;

    // Vérifier si le manager est initialisé
    if (!isset($emailManager)) {
        $emailManager = new EmailManager();
    }

    $variables = [
        'NAME' => htmlspecialchars($name),
        'EMAIL' => htmlspecialchars($to),
        'PHONE' => htmlspecialchars($phone ?: 'Non renseigné'),
        'INSTAGRAM' => htmlspecialchars($instagram ?: 'Non renseigné')
    ];

    return $emailManager->sendTemplateEmail(
        $to,
        "Confirmation de ta demande de bilan Enorehab",
        "confirmation_client.html",
        $variables
    );
}

/**
 * Fonction pour envoyer un email d'ebook
 *
 * @param string $to Email du destinataire
 * @param string $name Nom du client
 * @return bool Succès ou échec de l'envoi
 */
function send_ebook_email($to, $name) {
    global $emailManager;

    // Vérifier si le manager est initialisé
    if (!isset($emailManager)) {
        $emailManager = new EmailManager();
    }

    $variables = [
        'NAME' => htmlspecialchars($name)
    ];

    // Options avec pièce jointe (ebook)
    $options = [
        'attachments' => [
            [
                'path' => __DIR__ . '/../assets/ebooks/epaul-mobilite.pdf',
                'name' => 'Epaul - Guide de mobilité.pdf'
            ]
        ]
    ];

    return $emailManager->sendTemplateEmail(
        $to,
        "Votre ebook gratuit : Epaul - Guide de mobilité",
        "ebook_template.html",  // Changez "ebook_delivery.html" en "ebook_template.html"
        $variables,
        $options
    );
}

/**
 * Fonction pour envoyer un email de notification à l'administrateur
 *
 * @param array $data Données du formulaire
 * @param bool $db_success Statut de l'enregistrement en base de données
 * @param string $db_error_message Message d'erreur de la base de données (si applicable)
 * @return bool Succès ou échec de l'envoi
 */
function send_admin_notification_email($data, $db_success = true, $db_error_message = '') {
    global $emailManager;

    // Vérifier si le manager est initialisé
    if (!isset($emailManager)) {
        $emailManager = new EmailManager();
    }

    $variables = [
        'NAME' => htmlspecialchars($data['name']),
        'EMAIL' => htmlspecialchars($data['email']),
        'PHONE' => htmlspecialchars($data['phone'] ?? 'Non renseigné'),
        'INSTAGRAM' => htmlspecialchars($data['instagram'] ?? 'Non renseigné'),
        'DATE' => date("d/m/Y H:i:s"),
        'IP' => $_SERVER['REMOTE_ADDR'],
        'DB_SUCCESS' => $db_success ? 'Oui' : 'Non',
        'DB_ERROR' => $db_error_message
    ];

    $options = [
        'reply_to' => $data['email'],
        'reply_to_name' => $data['name']
    ];

    return $emailManager->sendTemplateEmail(
        "enora.lenez@enorehab.fr",
        "Nouvelle demande de bilan Enorehab",
        "admin_notification.html",
        $variables,
        $options
    );
}

// Initialisation globale
$emailManager = new EmailManager();

// SMTP en production (à décommenter et configurer)

if (!($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1')) {
    $smtpConfig = [
        'host' => 'smtp.ionos.fr',
        'username' => 'enora.lenez@enorehab.fr', // Remplacez par votre adresse email Ionos
        'password' => 'Despouille1134!',       // Remplacez par votre mot de passe
        'secure' => PHPMailer::ENCRYPTION_SMTPS,        // Utilise SSL (important)
        'port' => 465                                   // Port pour SSL
    ];
    $emailManager->setupSMTP($smtpConfig);
}
