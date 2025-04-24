<?php
/**
 * Configuration de la base de données avec support .env
 *
 * Ce fichier fournit une connexion à la base de données en utilisant
 * les variables d'environnement ou un fichier .env
 */

/**
 * Charge les variables d'environnement à partir du fichier .env
 *
 * @param string $envFile Chemin vers le fichier .env
 * @return void
 */
function loadEnvFile($envFile = __DIR__ . '/../.env') {
    if (!file_exists($envFile)) {
        return;
    }

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Ignorer les commentaires
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Diviser en clé/valeur
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Supprimer les guillemets autour de la valeur
            if (strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) {
                $value = substr($value, 1, -1);
            } elseif (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1) {
                $value = substr($value, 1, -1);
            }

            // Définir la variable d'environnement
            if (!isset($_ENV[$key])) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}


/**
 * Obtient les paramètres de connexion à la base de données
 *
 * @return array Paramètres de connexion
 */
function getDbParams() {
    // Charger les variables d'environnement
    loadEnvFile();

    // Paramètres de production par défaut (en cas d'échec du chargement .env)
    $params = [
        'host' => 'db5017331779.hosting-data.io',
        'dbname' => 'dbs13898318',
        'user' => 'dbu2274689',
        'password' => '17221722Df@@',
        'charset' => 'utf8mb4'
    ];

    // Vérifier d'abord explicitement DB_PASSWORD et DB_PASS
    $dbPassword = getenv('DB_PASSWORD');
    $dbPass = getenv('DB_PASS');

    // Utiliser celui qui est défini
    if ($dbPassword !== false && !empty($dbPassword)) {
        $params['password'] = $dbPassword;
    } elseif ($dbPass !== false && !empty($dbPass)) {
        $params['password'] = $dbPass;
    }

    // Ensuite, vérifier les autres variables
    $dbHost = getenv('DB_HOST');
    if ($dbHost !== false && !empty($dbHost)) {
        $params['host'] = $dbHost;
    }

    $dbName = getenv('DB_NAME');
    if ($dbName !== false && !empty($dbName)) {
        $params['dbname'] = $dbName;
    }

    $dbUser = getenv('DB_USER');
    if ($dbUser !== false && !empty($dbUser)) {
        $params['user'] = $dbUser;
    }

    $dbCharset = getenv('DB_CHARSET');
    if ($dbCharset !== false && !empty($dbCharset)) {
        $params['charset'] = $dbCharset;
    }

    // Ajouter une journalisation en cas de problème
    if (function_exists('log_debug')) {
        log_debug('Paramètres de connexion DB', [
            'host' => $params['host'],
            'dbname' => $params['dbname'],
            'user' => $params['user'],
            'password_defined' => !empty($params['password']),
            'charset' => $params['charset'],
            'env_DB_PASSWORD_defined' => ($dbPassword !== false),
            'env_DB_PASS_defined' => ($dbPass !== false)
        ]);
    }

    return $params;
}

/**
 * Établit une connexion à la base de données
 *
 * @param array $params Paramètres de connexion (optionnel)
 * @return PDO Instance de PDO
 * @throws PDOException Si la connexion échoue
 */
function getDbConnection($params = null) {
    static $pdo = null;

    // Retourner la connexion existante si elle est déjà établie
    if ($pdo !== null) {
        return $pdo;
    }

    // Utiliser les paramètres fournis ou les obtenir depuis l'environnement
    $params = $params ?: getDbParams();

    // Construire le DSN
    $dsn = "mysql:host={$params['host']};dbname={$params['dbname']};charset={$params['charset']}";

    // Options PDO
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];

    try {
        // Créer l'instance PDO
        $pdo = new PDO($dsn, $params['user'], $params['password'], $options);
        return $pdo;
    } catch (PDOException $e) {
        // Journaliser l'erreur si la fonction existe
        if (function_exists('log_error')) {
            log_error('Erreur de connexion à la base de données', [
                'message' => $e->getMessage(),
                'dsn' => $dsn
            ]);
        }

        // Propager l'exception
        throw $e;
    }
}

/**
 * Exécute une requête SELECT et retourne tous les résultats
 *
 * @param string $query Requête SQL
 * @param array $params Paramètres (optionnel)
 * @return array Résultats
 */
function dbSelect($query, $params = []) {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        if (function_exists('log_error')) {
            log_error('Erreur de requête SQL', [
                'query' => $query,
                'params' => $params,
                'message' => $e->getMessage()
            ]);
        }
        return [];
    }
}

/**
 * Exécute une requête INSERT et retourne l'ID inséré
 *
 * @param string $table Nom de la table
 * @param array $data Données à insérer
 * @return int|false ID inséré ou false en cas d'échec
 */
function dbInsert($table, $data) {
    try {
        $pdo = getDbConnection();

        // Construire la requête
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $query = "INSERT INTO $table ($columns) VALUES ($placeholders)";

        $stmt = $pdo->prepare($query);
        $stmt->execute(array_values($data));

        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        if (function_exists('log_error')) {
            log_error('Erreur d\'insertion SQL', [
                'table' => $table,
                'data' => $data,
                'message' => $e->getMessage()
            ]);
        }
        return false;
    }
}

/**
 * Exécute une requête UPDATE
 *
 * @param string $table Nom de la table
 * @param array $data Données à mettre à jour
 * @param array $where Conditions WHERE
 * @return int Nombre de lignes affectées
 */
function dbUpdate($table, $data, $where) {
    try {
        $pdo = getDbConnection();

        // Construire la partie SET
        $setParts = [];
        $params = [];

        foreach ($data as $column => $value) {
            $setParts[] = "$column = ?";
            $params[] = $value;
        }

        // Construire la partie WHERE
        $whereParts = [];

        foreach ($where as $column => $value) {
            $whereParts[] = "$column = ?";
            $params[] = $value;
        }

        $query = "UPDATE $table SET " . implode(', ', $setParts) . " WHERE " . implode(' AND ', $whereParts);

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        return $stmt->rowCount();
    } catch (PDOException $e) {
        if (function_exists('log_error')) {
            log_error('Erreur de mise à jour SQL', [
                'table' => $table,
                'data' => $data,
                'where' => $where,
                'message' => $e->getMessage()
            ]);
        }
        return 0;
    }
}

/**
 * Exécute une requête DELETE
 *
 * @param string $table Nom de la table
 * @param array $where Conditions WHERE
 * @return int Nombre de lignes affectées
 */
function dbDelete($table, $where) {
    try {
        $pdo = getDbConnection();

        // Construire la partie WHERE
        $whereParts = [];
        $params = [];

        foreach ($where as $column => $value) {
            $whereParts[] = "$column = ?";
            $params[] = $value;
        }

        $query = "DELETE FROM $table WHERE " . implode(' AND ', $whereParts);

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        return $stmt->rowCount();
    } catch (PDOException $e) {
        if (function_exists('log_error')) {
            log_error('Erreur de suppression SQL', [
                'table' => $table,
                'where' => $where,
                'message' => $e->getMessage()
            ]);
        }
        return 0;
    }
}