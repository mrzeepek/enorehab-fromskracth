<?php
/**
 * Diagnostic de connexion à la base de données
 *
 * Ce script teste la connexion à la base de données et affiche des informations de diagnostic
 * IMPORTANT: À utiliser uniquement en développement et à supprimer en production!
 */

// Activer l'affichage des erreurs pour le diagnostic
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Diagnostic de connexion à la base de données</h1>";

// 1. Vérifier le fichier .env
$env_file = __DIR__ . '/.env';
echo "<h2>1. Vérification du fichier .env</h2>";
if (file_exists($env_file)) {
    echo "<p style='color: green;'>✓ Le fichier .env existe</p>";

    // Afficher les variables d'environnement (sans le mot de passe)
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    echo "<p>Contenu du fichier (partiellement masqué) :</p>";
    echo "<pre>";
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Masquer les mots de passe pour la sécurité
        if (strpos($line, 'PASS') !== false || strpos($line, 'PASSWORD') !== false) {
            list($name, $value) = explode('=', $line, 2);
            echo htmlspecialchars($name) . "=********\n";
        } else {
            echo htmlspecialchars($line) . "\n";
        }
    }
    echo "</pre>";

    // Charger les variables d'environnement
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!empty($name)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
        }
    }
} else {
    echo "<p style='color: red;'>✕ Le fichier .env n'existe pas!</p>";
    echo "<p>Créez un fichier .env à la racine du site avec les informations suivantes :</p>";
    echo "<pre>
DB_HOST=votre_serveur_db
DB_NAME=votre_nom_de_db
DB_USER=votre_utilisateur
DB_PASS=votre_mot_de_passe
</pre>";
}

// 2. Vérifier les variables d'environnement
echo "<h2>2. Vérification des variables d'environnement</h2>";

$required_vars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
$all_vars_present = true;

foreach ($required_vars as $var) {
    if (getenv($var)) {
        $value = $var === 'DB_PASS' ? '********' : getenv($var);
        echo "<p style='color: green;'>✓ $var est défini ($value)</p>";
    } else {
        echo "<p style='color: red;'>✕ $var n'est pas défini!</p>";
        $all_vars_present = false;
    }
}

if (!$all_vars_present) {
    echo "<p style='color: red;'>Toutes les variables d'environnement requises ne sont pas définies.</p>";
}

// 3. Tester la connexion à la base de données
echo "<h2>3. Test de connexion à la base de données</h2>";

if ($all_vars_present) {
    try {
        $dsn = "mysql:host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_NAME') . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5
        ];

        echo "<p>Tentative de connexion à : " . getenv('DB_HOST') . "...</p>";

        $pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'), $options);
        echo "<p style='color: green;'>✓ Connexion à la base de données réussie!</p>";

        // Vérifier si la table existe
        $tableExists = false;
        try {
            $result = $pdo->query("SHOW TABLES LIKE 'enorehab_ebook_subscribers'");
            $tableExists = $result && $result->rowCount() > 0;

            if ($tableExists) {
                echo "<p style='color: green;'>✓ La table 'enorehab_ebook_subscribers' existe</p>";

                // Compter les entrées
                $count = $pdo->query("SELECT COUNT(*) FROM enorehab_ebook_subscribers")->fetchColumn();
                echo "<p>Nombre d'abonnés dans la base : $count</p>";

                // Afficher quelques entrées récentes (sans afficher les emails complets)
                if ($count > 0) {
                    echo "<p>Entrées récentes :</p>";
                    $stmt = $pdo->query("SELECT id, name, email, download_date FROM enorehab_ebook_subscribers ORDER BY download_date DESC LIMIT 5");
                    echo "<table border='1' cellpadding='5' cellspacing='0'>";
                    echo "<tr><th>ID</th><th>Nom</th><th>Email (partiellement masqué)</th><th>Date de téléchargement</th></tr>";
                    while ($row = $stmt->fetch()) {
                        // Masquer partiellement l'email
                        $email_parts = explode('@', $row['email']);
                        $masked_email = substr($email_parts[0], 0, 3) . '***@' . $email_parts[1];

                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                        echo "<td>" . htmlspecialchars($masked_email) . "</td>";
                        echo "<td>" . htmlspecialchars($row['download_date']) . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
            } else {
                echo "<p style='color: orange;'>⚠ La table 'enorehab_ebook_subscribers' n'existe pas encore</p>";
                echo "<p>Elle sera créée automatiquement lors de la première soumission du formulaire d'ebook.</p>";
            }
        } catch (PDOException $e) {
            echo "<p style='color: red;'>Erreur lors de la vérification de la table : " . $e->getMessage() . "</p>";
        }

    } catch (PDOException $e) {
        echo "<p style='color: red;'>✕ Erreur de connexion : " . $e->getMessage() . "</p>";

        // Afficher des informations de diagnostic supplémentaires
        echo "<h3>Informations supplémentaires :</h3>";
        echo "<ul>";
        echo "<li>Vérifiez que le serveur MySQL est bien en cours d'exécution</li>";
        echo "<li>Vérifiez que l'utilisateur a les permissions nécessaires</li>";
        echo "<li>Vérifiez que la base de données existe</li>";
        echo "<li>Si votre hébergeur requiert une IP spécifique pour l'accès à la base de données, assurez-vous que l'IP de votre serveur est autorisée</li>";
        echo "</ul>";
    }
} else {
    echo "<p style='color: red;'>Le test de connexion a été ignoré car les variables d'environnement sont manquantes.</p>";
}

// Avertissement de sécurité
echo "<div style='margin-top: 30px; padding: 10px; background-color: #fff3cd; border: 1px solid #ffeeba; border-radius: 5px;'>";
echo "<strong>⚠️ Avertissement de sécurité :</strong> Ce script affiche des informations sensibles sur votre configuration de base de données. Assurez-vous de <strong>le supprimer ou de le protéger</strong> une fois le diagnostic terminé.";
echo "</div>";