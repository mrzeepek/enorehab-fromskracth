<?php
/**
 * Traitement du formulaire de téléchargement d'ebook Enorehab
 *
 * Ce script utilise les classes améliorées pour gérer les téléchargements d'ebook,
 * l'enregistrement en base de données et l'envoi d'emails.
 */

// Initialisation
error_reporting(E_ALL);
ini_set('display_errors', 0); // Désactivé en production
set_time_limit(30);

// Démarrer la session pour le CSRF
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure les dépendances
require_once 'includes/logger.php';
require_once 'includes/EbookController.php';

// Journaliser l'accès à cette page
log_page_access();

// Vérifier que la demande est faite via POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    log_warning('Tentative d\'accès direct à process_ebook.php', ['ip' => $_SERVER['REMOTE_ADDR']]);
    header("Location: index.php");
    exit;
}

// Initialiser le contrôleur d'ebook
$controller = new EbookController();

try {
    // Vérifier le token CSRF
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception("Erreur de sécurité: token CSRF invalide");
    }

    // Nettoyer et valider les données du formulaire
    $formData = [
        'name' => filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
        'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
        'consent' => filter_input(INPUT_POST, 'consent', FILTER_VALIDATE_BOOLEAN)
    ];

    // Traiter la demande d'ebook
    $result = $controller->processEbookRequest($formData);

    // Journaliser le résultat
    log_info('Résultat du traitement de téléchargement d\'ebook', [
        'email' => $formData['email'],
        'success' => $result['success'],
        'db_success' => $result['db_success'],
        'email_success' => $result['email_success']
    ]);

    // Redirection en fonction du résultat
    if ($result['success']) {
        // Si l'email a été envoyé avec succès
        header("Location: index.php?ebook_success=true");
        exit;
    } else {
        // Échec de l'envoi - construire un message d'erreur
        $errorParam = urlencode(implode('|', $result['errors']));
        header("Location: index.php?ebook_error=" . $errorParam);
        exit;
    }

} catch (Exception $e) {
    // Journaliser l'exception
    log_exception($e, 'Erreur dans process_ebook.php');

    // Redirection avec message d'erreur générique
    header("Location: index.php?ebook_error=Une+erreur+inattendue+est+survenue");
    exit;
}