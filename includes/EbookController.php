<?php
/**
 * Classe EbookController - Gestion des téléchargements d'ebooks
 *
 * Gère les téléchargements d'ebooks, l'enregistrement des utilisateurs
 * et l'envoi des emails avec l'ebook et les notifications admin.
 */

require_once __DIR__ . '/EmailManager.php';
require_once __DIR__ . '/db_config.php';

class EbookController {
    private $pdo;
    private $emailManager;
    private $errors = [];

    /**
     * Constructeur avec injection de dépendances optionnelle
     *
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @param EmailManager|null $emailManager Instance EmailManager (optionnelle)
     */
    public function __construct($pdo = null, $emailManager = null) {
        // Connexion à la base de données
        try {
            $this->pdo = $pdo ?: getDbConnection();

            // Créer la table si elle n'existe pas
            $this->initDatabase();
        } catch (PDOException $e) {
            $this->errors[] = "Erreur de connexion à la base de données: " . $e->getMessage();
        }

        // Gestionnaire d'emails
        $this->emailManager = $emailManager ?: new EmailManager();
    }

    /**
     * Initialise la structure de la base de données
     */
    private function initDatabase() {
        if (!$this->pdo) {
            return;
        }

        // Créer la table des abonnés ebook si elle n'existe pas
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS enorehab_ebook_subscribers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            download_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45),
            consent BOOLEAN DEFAULT 1,
            mail_list BOOLEAN DEFAULT 1
        )");
    }

    /**
     * Traite une demande de téléchargement d'ebook
     *
     * @param array $data Données du formulaire
     * @return array Résultat du traitement avec statuts
     */
    public function processEbookRequest($data) {
        $result = [
            'success' => false,
            'db_success' => false,
            'email_success' => false,
            'admin_email_success' => false,
            'errors' => [],
            'message' => ''
        ];

        // 1. Validation des données
        $this->validateEbookData($data);

        if (!empty($this->errors)) {
            $result['errors'] = $this->errors;
            $result['message'] = 'Erreurs de validation';
            return $result;
        }

        // 2. Enregistrement en base de données
        $dbResult = $this->saveEbookSubscriber($data);
        $result['db_success'] = $dbResult['success'];

        if (!$dbResult['success']) {
            $this->errors[] = $dbResult['message'];
        }

        // 3. Envoi de l'ebook par email
        $emailSent = $this->emailManager->sendEbookToClient(
            $data['email'],
            $data['name']
        );

        $result['email_success'] = $emailSent;

        if (!$emailSent) {
            $this->errors[] = "L'envoi de l'email a échoué.";
        }

        // 4. Envoi de la notification à l'administrateur
        $stats = $this->getEbookStats();
        $adminEmailSent = $this->emailManager->sendEbookAdminNotification(
            $data,
            $dbResult['success'],
            $stats
        );

        $result['admin_email_success'] = $adminEmailSent;

        // 5. Résultat global
        $result['success'] = $emailSent; // Considéré réussi si l'email a été envoyé
        $result['errors'] = $this->errors;
        $result['stats'] = $stats;
        $result['message'] = $emailSent
            ? 'Ebook envoyé avec succès'
            : 'Échec de l\'envoi de l\'ebook';

        return $result;
    }

    /**
     * Valide les données du formulaire
     *
     * @param array $data Données à valider
     * @return bool Validité des données
     */
    private function validateEbookData($data) {
        $this->errors = [];

        // Vérifier le nom
        if (empty($data['name'])) {
            $this->errors[] = "Le nom est requis";
        } elseif (strlen($data['name']) < 2) {
            $this->errors[] = "Le nom doit contenir au moins 2 caractères";
        }

        // Vérifier l'email
        if (empty($data['email'])) {
            $this->errors[] = "L'email est requis";
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "L'email n'est pas valide";
        }

        // Vérifier le consentement
        if (!isset($data['consent']) || !$data['consent']) {
            $this->errors[] = "Vous devez accepter les conditions";
        }

        return empty($this->errors);
    }

    /**
     * Enregistre un abonné à l'ebook en base de données
     *
     * @param array $data Données de l'abonné
     * @return array Résultat de l'opération
     */
    private function saveEbookSubscriber($data) {
        $result = [
            'success' => false,
            'message' => '',
            'id' => null
        ];

        if (!$this->pdo) {
            $result['message'] = "Pas de connexion à la base de données";
            return $result;
        }

        try {
            // Vérifier si l'email existe déjà
            $stmt = $this->pdo->prepare("SELECT id FROM enorehab_ebook_subscribers WHERE email = :email");
            $stmt->execute([':email' => $data['email']]);
            $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$existingUser) {
                // Insertion d'un nouvel abonné
                $stmt = $this->pdo->prepare("INSERT INTO enorehab_ebook_subscribers 
                    (name, email, ip_address, consent, mail_list) 
                    VALUES (:name, :email, :ip, :consent, :mail_list)");

                $stmt->execute([
                    ':name' => $data['name'],
                    ':email' => $data['email'],
                    ':ip' => $_SERVER['REMOTE_ADDR'],
                    ':consent' => isset($data['consent']) && $data['consent'] ? 1 : 0,
                    ':mail_list' => isset($data['consent']) && $data['consent'] ? 1 : 0
                ]);

                $result['id'] = $this->pdo->lastInsertId();
                $result['success'] = true;
                $result['message'] = "Abonné enregistré avec l'ID: " . $result['id'];

            } else {
                // Mise à jour d'un abonné existant
                $stmt = $this->pdo->prepare("UPDATE enorehab_ebook_subscribers 
                    SET download_date = NOW(), 
                        name = :name,
                        ip_address = :ip
                    WHERE email = :email");

                $stmt->execute([
                    ':name' => $data['name'],
                    ':ip' => $_SERVER['REMOTE_ADDR'],
                    ':email' => $data['email']
                ]);

                $result['id'] = $existingUser['id'];
                $result['success'] = true;
                $result['message'] = "Abonné existant mis à jour";
                $result['is_update'] = true;
            }

        } catch (PDOException $e) {
            $result['message'] = "Erreur de base de données: " . $e->getMessage();
        }

        return $result;
    }

    /**
     * Récupère les statistiques sur les ebooks
     *
     * @return array Statistiques
     */
    private function getEbookStats() {
        $stats = [
            'total' => 0,
            'today' => 0,
            'mail_list' => 0
        ];

        if (!$this->pdo) {
            return $stats;
        }

        try {
            // Total des téléchargements
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM enorehab_ebook_subscribers");
            $stats['total'] = $stmt->fetchColumn();

            // Téléchargements aujourd'hui
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM enorehab_ebook_subscribers 
                                        WHERE DATE(download_date) = CURDATE()");
            $stats['today'] = $stmt->fetchColumn();

            // Inscrits à la newsletter
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM enorehab_ebook_subscribers 
                                        WHERE mail_list = 1");
            $stats['mail_list'] = $stmt->fetchColumn();

        } catch (PDOException $e) {
            // Silencieux en cas d'erreur
        }

        return $stats;
    }

    /**
     * Récupère les erreurs de validation
     *
     * @return array Erreurs
     */
    public function getErrors() {
        return $this->errors;
    }
}