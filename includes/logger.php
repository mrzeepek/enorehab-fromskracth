<?php
/**
 * Système de journalisation pour Enorehab
 *
 * Fournit des fonctions de journalisation simples pour enregistrer
 * les informations, avertissements et erreurs dans des fichiers de log.
 */

// Définir le dossier de logs
define('LOG_DIR', __DIR__ . '/../logs');

// Créer le dossier de logs s'il n'existe pas
if (!is_dir(LOG_DIR)) {
    mkdir(LOG_DIR, 0755, true);
}

// Sous-dossiers de logs par type
$logSubdirs = ['info', 'warning', 'error', 'debug'];
foreach ($logSubdirs as $subdir) {
    $path = LOG_DIR . '/' . $subdir;
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

/**
 * Obtient le nom de fichier de log du jour
 *
 * @param string $type Type de log
 * @return string Chemin du fichier
 */
function getLogFilename($type) {
    $date = date('Y-m-d');
    return LOG_DIR . '/' . $type . '/' . $date . '.log';
}

/**
 * Écrit un message dans le fichier de log
 *
 * @param string $type Type de log
 * @param string $message Message à journaliser
 * @param array $context Contexte supplémentaire
 * @return bool Succès ou échec
 */
function writeLog($type, $message, $context = []) {
    $filename = getLogFilename($type);

    // Formater le message
    $timestamp = date('Y-m-d H:i:s');
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'CLI';

    $logMessage = "[$timestamp] [$ip] $message";

    // Ajouter le contexte s'il est présent
    if (!empty($context)) {
        $logMessage .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    $logMessage .= PHP_EOL;

    // Écrire dans le fichier
    return file_put_contents($filename, $logMessage, FILE_APPEND | LOCK_EX) !== false;
}

/**
 * Journalise un message d'information
 *
 * @param string $message Message à journaliser
 * @param array $context Contexte supplémentaire
 * @return bool Succès ou échec
 */
function log_info($message, $context = []) {
    return writeLog('info', $message, $context);
}

/**
 * Journalise un avertissement
 *
 * @param string $message Message à journaliser
 * @param array $context Contexte supplémentaire
 * @return bool Succès ou échec
 */
function log_warning($message, $context = []) {
    return writeLog('warning', $message, $context);
}

/**
 * Journalise une erreur
 *
 * @param string $message Message à journaliser
 * @param array $context Contexte supplémentaire
 * @return bool Succès ou échec
 */
function log_error($message, $context = []) {
    return writeLog('error', $message, $context);
}

/**
 * Journalise un message de débogage (uniquement en mode développement)
 *
 * @param string $message Message à journaliser
 * @param array $context Contexte supplémentaire
 * @return bool Succès ou échec
 */
function log_debug($message, $context = []) {
    // Détection automatique de l'environnement de développement
    $isDev = (
        defined('DEBUG_MODE') && DEBUG_MODE === true ||
        $_SERVER['SERVER_NAME'] === 'localhost' ||
        $_SERVER['SERVER_NAME'] === '127.0.0.1' ||
        strpos($_SERVER['SERVER_NAME'], '.test') !== false ||
        strpos($_SERVER['SERVER_NAME'], '.local') !== false
    );

    if ($isDev) {
        return writeLog('debug', $message, $context);
    }

    return true;
}

/**
 * Nettoie les anciens fichiers de log
 *
 * @param int $daysToKeep Nombre de jours à conserver
 * @return int Nombre de fichiers supprimés
 */
function cleanupLogs($daysToKeep = 30) {
    $deletedCount = 0;
    $cutoffTime = time() - ($daysToKeep * 86400);

    foreach (['info', 'warning', 'error', 'debug'] as $type) {
        $dir = LOG_DIR . '/' . $type;

        if (!is_dir($dir)) {
            continue;
        }

        $files = glob($dir . '/*.log');

        foreach ($files as $file) {
            $fileTime = filemtime($file);

            if ($fileTime < $cutoffTime) {
                if (unlink($file)) {
                    $deletedCount++;
                }
            }
        }
    }

    return $deletedCount;
}

/**
 * Enregistre une exception dans les logs
 *
 * @param Exception|Throwable $exception L'exception à journaliser
 * @param string $context Information contextuelle supplémentaire
 * @return bool Succès ou échec
 */
function log_exception($exception, $context = 'Uncaught Exception') {
    $message = "$context: " . get_class($exception) . ': ' . $exception->getMessage();

    $data = [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => array_slice(explode("\n", $exception->getTraceAsString()), 0, 5)
    ];

    return log_error($message, $data);
}

// Définir un gestionnaire d'exceptions global
set_exception_handler(function($exception) {
    log_exception($exception);
});

// Définir un gestionnaire d'erreurs global
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $errorTypes = [
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated'
    ];

    $type = isset($errorTypes[$errno]) ? $errorTypes[$errno] : "Error #$errno";
    $message = "$type: $errstr";

    $data = [
        'file' => $errfile,
        'line' => $errline
    ];

    if ($errno == E_ERROR || $errno == E_USER_ERROR) {
        log_error($message, $data);
    } elseif ($errno == E_WARNING || $errno == E_USER_WARNING) {
        log_warning($message, $data);
    } else {
        log_debug($message, $data);
    }

    // Ne pas interrompre l'exécution du script
    return false;
});

/**
 * Enregistre un accès à une page dans le journal
 */
function log_page_access() {
    $url = $_SERVER['REQUEST_URI'] ?? '/';
    $referer = $_SERVER['HTTP_REFERER'] ?? 'direct';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    log_info('Page access', [
        'url' => $url,
        'referer' => $referer,
        'user_agent' => $userAgent
    ]);
}