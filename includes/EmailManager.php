<?php
/**
 * Classe EmailManager - Système de gestion des emails pour Enorehab
 *
 * Gère l'envoi des emails avec PHPMailer, la mise en forme des templates,
 * et les configurations SMTP. Supporte le mode de développement local.
 */

// Inclusion des dépendances PHPMailer
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailManager {
    private $mailer;
    private $isLocal;
    private $templateDir;
    private $defaultSender = [
        'email' => 'enora.lenez@enorehab.fr',
        'name' => 'Enorehab'
    ];
    private $smtpSettings = [
        'host' => 'smtp.ionos.fr',
        'username' => 'enora.lenez@enorehab.fr',
        'password' => 'Despouille1134!', // À sécuriser dans un fichier .env
        'port' => 465,
        'encryption' => PHPMailer::ENCRYPTION_SMTPS
    ];

    /**
     * Constructeur avec configuration optionnelle
     *
     * @param array $config Configuration optionnelle
     */
    public function __construct($config = []) {
        // Détection de l'environnement local
        $this->isLocal = ($_SERVER['SERVER_NAME'] === 'localhost' ||
            $_SERVER['SERVER_NAME'] === '127.0.0.1' ||
            $_SERVER['REMOTE_ADDR'] === '127.0.0.1' ||
            $_SERVER['REMOTE_ADDR'] === '::1');

        // Chemins des templates
        $this->templateDir = isset($config['templateDir'])
            ? $config['templateDir']
            : __DIR__ . '/../templates/emails/';

        // Créer le dossier de templates s'il n'existe pas
        if (!is_dir($this->templateDir)) {
            mkdir($this->templateDir, 0755, true);
        }

        // Configuration de l'expéditeur par défaut
        if (isset($config['defaultSender'])) {
            $this->defaultSender = $config['defaultSender'];
        }

        // Configuration SMTP si fournie
        if (isset($config['smtp'])) {
            $this->smtpSettings = array_merge($this->smtpSettings, $config['smtp']);
        }

        // Initialiser PHPMailer
        $this->initMailer();
    }

    /**
     * Initialise l'objet PHPMailer avec les configurations
     */
    private function initMailer() {
        $this->mailer = new PHPMailer(true);

        // Configuration de base
        $this->mailer->CharSet = 'UTF-8';

        // Configuration SMTP (non utilisée en environnement local)
        if (!$this->isLocal) {
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->smtpSettings['host'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->smtpSettings['username'];
            $this->mailer->Password = $this->smtpSettings['password'];
            $this->mailer->SMTPSecure = $this->smtpSettings['encryption'];
            $this->mailer->Port = $this->smtpSettings['port'];

            // Options SSL pour éviter les erreurs de certificat
            $this->mailer->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
        }
    }

    /**
     * Charge et traite un template avec les variables
     *
     * @param string $templateName Nom du fichier de template
     * @param array $variables Variables à remplacer
     * @return string Template HTML avec variables remplacées
     * @throws Exception Si le template n'est pas trouvé
     */
    public function loadTemplate($templateName, $variables = []) {
        // Recherche du template dans plusieurs emplacements possibles
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
            throw new Exception("Template non trouvé: $templateName");
        }

        $template = file_get_contents($templatePath);

        // Remplacer les variables
        foreach ($variables as $key => $value) {
            $template = str_replace('{{' . $key . '}}', htmlspecialchars($value), $template);
        }

        // Ajouter l'année courante si non spécifiée
        if (!isset($variables['YEAR'])) {
            $template = str_replace('{{YEAR}}', date('Y'), $template);
        }

        return $template;
    }

    /**
     * Prépare le mailer avec les paramètres de base pour un envoi d'email
     *
     * @param string $to Adresse email du destinataire
     * @param string $toName Nom du destinataire (optionnel)
     * @param array $options Options supplémentaires
     */
    private function prepareMailer($to, $toName = '', $options = []) {
        // Réinitialiser les destinataires et pièces jointes
        $this->mailer->clearAddresses();
        $this->mailer->clearAttachments();
        $this->mailer->clearCCs();
        $this->mailer->clearBCCs();
        $this->mailer->clearReplyTos();

        // Destinataire principal
        $this->mailer->addAddress($to, $toName);

        // Expéditeur (from)
        $from = isset($options['from']) ? $options['from'] : $this->defaultSender;
        $this->mailer->setFrom($from['email'], $from['name']);

        // Répondre à (reply-to)
        if (isset($options['replyTo'])) {
            $this->mailer->addReplyTo(
                $options['replyTo']['email'],
                $options['replyTo']['name'] ?? ''
            );
        }

        // Copies (CC)
        if (isset($options['cc'])) {
            foreach ((array)$options['cc'] as $cc) {
                $this->mailer->addCC($cc['email'], $cc['name'] ?? '');
            }
        }

        // Copies cachées (BCC)
        if (isset($options['bcc'])) {
            foreach ((array)$options['bcc'] as $bcc) {
                $this->mailer->addBCC($bcc['email'], $bcc['name'] ?? '');
            }
        }

        // Pièces jointes
        if (isset($options['attachments'])) {
            foreach ((array)$options['attachments'] as $attachment) {
                $this->mailer->addAttachment(
                    $attachment['path'],
                    $attachment['name'] ?? basename($attachment['path']),
                    $attachment['encoding'] ?? 'base64',
                    $attachment['type'] ?? '',
                    $attachment['disposition'] ?? 'attachment'
                );
            }
        }

        // Format HTML par défaut
        $this->mailer->isHTML(true);
    }

    /**
     * Envoie un email
     *
     * @param string $to Adresse email du destinataire
     * @param string $subject Sujet de l'email
     * @param string $body Contenu HTML de l'email
     * @param string $altBody Contenu texte alternatif (optionnel)
     * @param array $options Options supplémentaires
     * @return bool Succès ou échec de l'envoi
     */
    public function sendEmail($to, $subject, $body, $altBody = '', $options = []) {
        try {
            // En mode local, on simule l'envoi et on sauvegarde dans un fichier
            if ($this->isLocal && !isset($options['forceSmtp'])) {
                return $this->saveLocalEmail($to, $subject, $body, $altBody, $options);
            }

            // Préparer le mailer
            $this->prepareMailer($to, $options['toName'] ?? '', $options);

            // Sujet et corps du message
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;

            // Corps alternatif en texte brut
            if (empty($altBody)) {
                // Générer automatiquement une version texte
                $altBody = strip_tags(str_replace(
                    ['<br>', '<br/>', '<br />', '</p>', '</h1>', '</h2>', '</h3>', '</h4>'],
                    "\n",
                    $body
                ));
            }
            $this->mailer->AltBody = $altBody;

            // Envoyer l'email
            return $this->mailer->send();

        } catch (Exception $e) {
            // Journaliser l'erreur si la fonction existe
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
     * Envoie un email en utilisant un template
     *
     * @param string $to Adresse email du destinataire
     * @param string $subject Sujet de l'email
     * @param string $templateName Nom du fichier de template
     * @param array $variables Variables à remplacer dans le template
     * @param array $options Options supplémentaires
     * @return bool Succès ou échec de l'envoi
     */
    public function sendTemplateEmail($to, $subject, $templateName, $variables = [], $options = []) {
        try {
            // Charger et traiter le template
            $body = $this->loadTemplate($templateName, $variables);

            // Générer une version texte si nécessaire
            $altBody = isset($options['altBody']) ? $options['altBody'] : '';

            // Envoyer l'email
            return $this->sendEmail($to, $subject, $body, $altBody, $options);

        } catch (Exception $e) {
            // Journaliser l'erreur si la fonction existe
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
     * Simule l'envoi d'email en mode local en sauvegardant dans un fichier
     *
     * @param string $to Adresse email du destinataire
     * @param string $subject Sujet de l'email
     * @param string $body Corps de l'email
     * @param string $altBody Corps alternatif
     * @param array $options Options supplémentaires
     * @return bool Succès (toujours true en mode local)
     */
    private function saveLocalEmail($to, $subject, $body, $altBody, $options) {
        // Créer le dossier de logs si nécessaire
        $logDir = __DIR__ . '/../logs/emails';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // Nom du fichier basé sur la date et le destinataire
        $filename = date('Y-m-d_H-i-s') . '_' . substr(md5($to . $subject), 0, 8) . '.html';
        $logFile = $logDir . '/' . $filename;

        // Préparer le contenu du log
        $from = isset($options['from']) ? $options['from'] : $this->defaultSender;
        $logContent = "Date: " . date('Y-m-d H:i:s') . "\n";
        $logContent .= "To: " . $to . "\n";
        $logContent .= "Subject: " . $subject . "\n";
        $logContent .= "From: " . $from['name'] . " <" . $from['email'] . ">\n\n";

        if (!empty($options['attachments'])) {
            $logContent .= "Attachments:\n";
            foreach ($options['attachments'] as $attachment) {
                $logContent .= "- " . ($attachment['name'] ?? basename($attachment['path'])) . "\n";
            }
            $logContent .= "\n";
        }

        $logContent .= "=== HTML CONTENT ===\n";
        $logContent .= $body . "\n\n";

        if (!empty($altBody)) {
            $logContent .= "=== TEXT CONTENT ===\n";
            $logContent .= $altBody . "\n";
        }

        // Écrire dans le fichier
        file_put_contents($logFile, $logContent);

        return true; // Toujours succès en mode local
    }

    /**
     * Envoie l'ebook au client avec le template approprié
     *
     * @param string $email Email du client
     * @param string $name Nom du client
     * @return bool Succès ou échec de l'envoi
     */
    public function sendEbookToClient($email, $name) {
        // Variables pour le template
        $variables = [
            'NAME' => $name,
            'EMAIL' => $email,
            'YEAR' => date('Y')
        ];

        // Pièce jointe (ebook)
        $options = [
            'attachments' => [
                [
                    'path' => __DIR__ . '/../assets/ebooks/epaul-mobilite.pdf',
                    'name' => 'Epaul - Guide de mobilité.pdf'
                ]
            ],
            'from' => [
                'email' => 'enora.lenez@enorehab.fr',
                'name' => 'Enora Lenez - Enorehab'
            ],
            'replyTo' => [
                'email' => 'enora.lenez@enorehab.fr',
                'name' => 'Enora Lenez'
            ]
        ];

        return $this->sendTemplateEmail(
            $email,
            'Votre ebook gratuit : Épaul - Guide de mobilité',
            'ebook_template.html',
            $variables,
            $options
        );
    }

    /**
     * Envoie une notification admin pour un téléchargement d'ebook
     *
     * @param array $data Données du téléchargement
     * @param bool $dbSuccess Succès de l'enregistrement en base
     * @param array $stats Statistiques (optionnel)
     * @return bool Succès ou échec de l'envoi
     */
    public function sendEbookAdminNotification($data, $dbSuccess = true, $stats = []) {
        // Variables pour le template
        $variables = [
            'NAME' => $data['name'],
            'EMAIL' => $data['email'],
            'DATE' => date('d/m/Y H:i:s'),
            'IP' => $_SERVER['REMOTE_ADDR'],
            'CONSENT' => isset($data['consent']) && $data['consent'] ? 'Oui' : 'Non',
            'DB_SUCCESS' => $dbSuccess ? 'Oui' : 'Non',
            'DB_COLOR' => $dbSuccess ? '#0ed0ff' : '#ff6b6b',
            'TOTAL_DOWNLOADS' => $stats['total'] ?? '?',
            'TODAY_DOWNLOADS' => $stats['today'] ?? '?',
            'MAIL_LIST_COUNT' => $stats['mail_list'] ?? '?'
        ];

        // Options
        $options = [
            'replyTo' => [
                'email' => $data['email'],
                'name' => $data['name']
            ]
        ];

        return $this->sendTemplateEmail(
            'enora.lenez@enorehab.fr',
            'Nouveau téléchargement d\'ebook - ' . $data['name'],
            'admin_ebook_notification.html',
            $variables,
            $options
        );
    }

    /**
     * Envoie une confirmation au client pour sa demande de bilan
     *
     * @param string $email Email du client
     * @param string $name Nom du client
     * @param string $phone Téléphone (optionnel)
     * @param string $instagram Instagram (optionnel)
     * @return bool Succès ou échec de l'envoi
     */
    public function sendBilanClientConfirmation($email, $name, $phone = '', $instagram = '') {
        // Variables pour le template
        $variables = [
            'NAME' => $name,
            'EMAIL' => $email,
            'PHONE' => $phone ?: 'Non renseigné',
            'INSTAGRAM' => $instagram ?: 'Non renseigné'
        ];

        // Options
        $options = [
            'from' => [
                'email' => 'enora.lenez@enorehab.fr',
                'name' => 'Enora Lenez - Enorehab'
            ],
            'replyTo' => [
                'email' => 'enora.lenez@enorehab.fr',
                'name' => 'Enora Lenez'
            ]
        ];

        return $this->sendTemplateEmail(
            $email,
            'Confirmation de votre bilan kiné personnalisé',
            'client_bilan_confirmation.html',
            $variables,
            $options
        );
    }

    /**
     * Envoie une notification admin pour une demande de bilan
     *
     * @param array $data Données de la demande
     * @param bool $dbSuccess Succès de l'enregistrement en base
     * @param string $dbErrorMessage Message d'erreur (optionnel)
     * @return bool Succès ou échec de l'envoi
     */
    public function sendBilanAdminNotification($data, $dbSuccess = true, $dbErrorMessage = '') {
        // Variables pour le template
        $variables = [
            'NAME' => $data['name'],
            'EMAIL' => $data['email'],
            'PHONE' => $data['phone'] ?? 'Non renseigné',
            'INSTAGRAM' => $data['instagram'] ?? 'Non renseigné',
            'DATE' => date('d/m/Y H:i:s'),
            'IP' => $_SERVER['REMOTE_ADDR'],
            'DB_STATUS' => $dbSuccess ? 'Enregistré avec succès en base de données' : 'Échec de l\'enregistrement en base',
            'DB_BG_COLOR' => $dbSuccess ? '#1a3a1a' : '#3a1a1a',
            'DB_ERROR_MESSAGE' => $dbErrorMessage ? '<br><span style="color: #ff6b6b;">' . $dbErrorMessage . '</span>' : ''
        ];

        // Options
        $options = [
            'replyTo' => [
                'email' => $data['email'],
                'name' => $data['name']
            ]
        ];

        return $this->sendTemplateEmail(
            'enora.lenez@enorehab.fr',
            'Nouvelle demande de bilan kiné - ' . $data['name'],
            'admin_bilan_notification.html',
            $variables,
            $options
        );
    }
}