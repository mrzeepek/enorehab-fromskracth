<?php
/**
 * Traitement du formulaire de demande de bilan Enorehab
 *
 * Ce script utilise les classes améliorées pour gérer les demandes de bilan,
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
require_once 'includes/BilanController.php';

// Journaliser l'accès à cette page
log_page_access();

// Vérifier que la demande est faite via POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    log_warning('Tentative d\'accès direct à process_form.php', ['ip' => $_SERVER['REMOTE_ADDR']]);
    header("Location: index.php#booking");
    exit;
}

// Initialiser le contrôleur de bilan
$controller = new BilanController();

try {
    // 1. VÉRIFICATION DU HONEYPOT (protection anti-bot)
    if (!empty($_POST['website'])) {
        // C'est probablement un bot, rejeter silencieusement
        log_warning('Honeypot rempli - probable bot', ['ip' => $_SERVER['REMOTE_ADDR']]);

        // Simuler un succès pour ne pas alerter le bot
        header("Location: index.php?success=true#booking");
        exit;
    }

    // 2. VÉRIFICATION DU TOKEN CSRF
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception("Erreur de sécurité: token CSRF invalide");
    }

    // 3. NETTOYER ET VALIDER LES DONNÉES DU FORMULAIRE
    $formData = [
        'name' => filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
        'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
        'phone' => filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: null,
        'instagram' => filter_input(INPUT_POST, 'instagram', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: null
    ];

    // 4. TRAITER LA DEMANDE DE BILAN
    $result = $controller->processBilanRequest($formData);

    // 5. JOURNALISER LE RÉSULTAT
    log_info('Résultat du traitement de demande de bilan', [
        'email' => $formData['email'],
        'success' => $result['success'],
        'db_success' => $result['db_success'],
        'client_email_success' => $result['client_email_success'],
        'admin_email_success' => $result['admin_email_success']
    ]);

    // 6. REDIRECTION EN FONCTION DU RÉSULTAT
    if ($result['success']) {
        // Si au moins une opération a réussi
        header("Location: index.php?success=true#booking");
        exit;
    } else {
        // Échec de l'envoi - construire un message d'erreur
        $errorParam = urlencode(implode('|', $result['errors']));
        header("Location: index.php?error=" . $errorParam . "#booking");
        exit;
    }

} catch (Exception $e) {
    // Journaliser l'exception
    log_exception($e, 'Erreur dans process_form.php');

    // Redirection avec message d'erreur générique
    header("Location: index.php?error=Une+erreur+inattendue+est+survenue#booking");
    exit;
}