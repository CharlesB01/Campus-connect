<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation basique
    if (empty($email) || empty($password)) {
        $_SESSION['error_login'] = "Tous les champs sont obligatoires.";
        redirect('index.php');
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Vérification de l'email
        if ($user['is_email_verified'] == 0) {
            $_SESSION['verify_email'] = $user['email'];
            redirect('verify_email.php');
        }

        // Vérification du statut
        if ($user['status'] === 'pending') {
            $_SESSION['error_login'] = "Votre compte est en attente de validation par un administrateur.";
            redirect('index.php');
        }

        if ($user['status'] === 'frozen') {
            // Vérifier si la date de fin de freeze est passée
            if ($user['frozen_until'] && new DateTime() > new DateTime($user['frozen_until'])) {
                // Dé-freeze automatique
                $pdo->prepare("UPDATE users SET status = 'active', frozen_until = NULL WHERE id = ?")->execute([$user['id']]);
                $user['status'] = 'active';
            } else {
                $freeze_date = $user['frozen_until'] ? date('d/m/Y H:i', strtotime($user['frozen_until'])) : "indéfiniment";
                $_SESSION['error_login'] = "Votre compte a été suspendu jusqu'au $freeze_date.";
                redirect('index.php');
            }
        }

        if ($user['status'] === 'active') {
            // Connexion réussie
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            
            redirect('user-dashboard.php');
        }
    } else {
        $_SESSION['error_login'] = "Identifiants incorrects.";
        redirect('index.php');
    }
}
