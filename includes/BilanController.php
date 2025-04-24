<?php
/**
 * Classe BilanController - Gestion des demandes de bilan kiné
 *
 * Gère les demandes de bilan kiné, l'enregistrement des clients
 * et l'envoi des emails de confirmation et notifications admin.
 */

require_once __DIR__ . '/EmailManager.php';
require_once __DIR__ . '/db_config.php';

class BilanController {
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

        // Créer la table des demandes de bilan si elle n'existe pas
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS enorehab_contacts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20),
            instagram VARCHAR(100),
            submission_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45),
            status VARCHAR(20) DEFAULT 'pending',
            notes TEXT
        )");
    }

    /**
     * Traite une demande de bilan kiné
     *
     * @param array $data Données du formulaire
     * @return array Résultat du traitement avec statuts
     */
    public function processBilanRequest($data) {
        $result = [
            'success' => false,
            'db_success' => false,
            'client_email_success' => false,
            'admin_email_success' => false,
            'errors' => [],
            'message' => ''
        ];

        // 1. Validation des données
        $this->validateBilanData($data);

        if (!empty($this->errors)) {
            $result['errors'] = $this->errors;
            $result['message'] = 'Erreurs de validation';
            return $result;
        }

        // 2. Enregistrement en base de données
        $dbResult = $this->saveBilanRequest($data);
        $result['db_success'] = $dbResult['success'];

        if (!$dbResult['success']) {
            $this->errors[] = $dbResult['message'];
        }

        // 3. Envoi de l'email de confirmation au client
        $clientEmailSent = $this->emailManager->sendBilanClientConfirmation(
            $data['email'],
            $data['name'],
            $data['phone'] ?? '',
            $data['instagram'] ?? ''
        );

        $result['client_email_success'] = $clientEmailSent;

        if (!$clientEmailSent) {
            $this->errors[] = "L'envoi de l'email de confirmation a échoué.";
        }

        // 4. Envoi de la notification à l'administrateur
        $adminEmailSent = $this->emailManager->sendBilanAdminNotification(
            $data,
            $dbResult['success'],
            $dbResult['success'] ? '' : $dbResult['message']
        );

        $result['admin_email_success'] = $adminEmailSent;

        // 5. Résultat global
        $result['success'] = $clientEmailSent || $adminEmailSent || $dbResult['success']; // Au moins un succès
        $result['errors'] = $this->errors;
        $result['message'] = $result['success']
            ? 'Demande de bilan traitée avec succès'
            : 'Échec du traitement de la demande de bilan';

        return $result;
    }

    /**
     * Valide les données du formulaire
     *
     * @param array $data Données à valider
     * @return bool Validité des données
     */
    private function validateBilanData($data) {
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

        // Vérifier le téléphone (si fourni)
        if (!empty($data['phone'])) {
            // Vérifier format du téléphone (optionnel)
            $phonePattern = '/^[0-9+\s()-]{8,20}$/';
            if (!preg_match($phonePattern, $data['phone'])) {
                $this->errors[] = "Le format du numéro de téléphone n'est pas valide";
            }
        }

        return empty($this->errors);
    }

    /**
     * Enregistre une demande de bilan en base de données
     *
     * @param array $data Données de la demande
     * @return array Résultat de l'opération
     */
    private function saveBilanRequest($data) {
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
            // Insertion d'une nouvelle demande
            $stmt = $this->pdo->prepare("INSERT INTO enorehab_contacts 
                (name, email, phone, instagram, ip_address) 
                VALUES (:name, :email, :phone, :instagram, :ip)");

            $stmt->execute([
                ':name' => $data['name'],
                ':email' => $data['email'],
                ':phone' => $data['phone'] ?? null,
                ':instagram' => $data['instagram'] ?? null,
                ':ip' => $_SERVER['REMOTE_ADDR']
            ]);

            $result['id'] = $this->pdo->lastInsertId();
            $result['success'] = true;
            $result['message'] = "Demande enregistrée avec l'ID: " . $result['id'];

        } catch (PDOException $e) {
            $result['message'] = "Erreur de base de données: " . $e->getMessage();
        }

        return $result;
    }

    /**
     * Récupère les erreurs de validation
     *
     * @return array Erreurs
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Récupère toutes les demandes de bilan
     *
     * @param string $status Filtrer par statut (optionnel)
     * @param int $limit Limite de résultats (optionnel)
     * @return array Liste des demandes
     */
    public function getAllBilanRequests($status = null, $limit = 100) {
        $requests = [];

        if (!$this->pdo) {
            return $requests;
        }

        try {
            $query = "SELECT * FROM enorehab_contacts";
            $params = [];

            if ($status) {
                $query .= " WHERE status = :status";
                $params[':status'] = $status;
            }

            $query .= " ORDER BY submission_date DESC LIMIT :limit";
            $params[':limit'] = $limit;

            $stmt = $this->pdo->prepare($query);

            // Bind le paramètre limit avec le type correct (PDO::PARAM_INT)
            foreach ($params as $key => $value) {
                if ($key === ':limit') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }

            $stmt->execute();
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            $this->errors[] = "Erreur lors de la récupération des demandes: " . $e->getMessage();
        }

        return $requests;
    }

    /**
     * Met à jour le statut d'une demande de bilan
     *
     * @param int $id ID de la demande
     * @param string $status Nouveau statut
     * @param string $notes Notes (optionnel)
     * @return bool Succès ou échec
     */
    public function updateBilanStatus($id, $status, $notes = null) {
        if (!$this->pdo) {
            return false;
        }

        try {
            $query = "UPDATE enorehab_contacts SET status = :status";

            if ($notes !== null) {
                $query .= ", notes = :notes";
            }

            $query .= " WHERE id = :id";

            $stmt = $this->pdo->prepare($query);
            $params = [
                ':id' => $id,
                ':status' => $status
            ];

            if ($notes !== null) {
                $params[':notes'] = $notes;
            }

            $stmt->execute($params);
            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            $this->errors[] = "Erreur lors de la mise à jour du statut: " . $e->getMessage();
            return false;
        }
    }
}