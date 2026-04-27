<?php
require_once 'config.php';

if (!is_admin()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $redirect_to = $_POST['redirect'] ?? 'admin-dashboard.php';

    if ($action === 'validate_user') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?")->execute([$user_id]);
        $_SESSION['admin_success'] = "Compte validé avec succès.";
        $redirect_to = 'admin-users.php';
        
    } elseif ($action === 'refuse_user') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $pdo->prepare("DELETE FROM users WHERE id = ? AND status = 'pending'")->execute([$user_id]);
        $_SESSION['admin_success'] = "Demande refusée et compte supprimé.";
        $redirect_to = 'admin-users.php';
        
    } elseif ($action === 'freeze_user') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $duration = $_POST['duration'] ?? 'indefinite';
        
        $frozen_until = null;
        if ($duration === '1h') {
            $frozen_until = date('Y-m-d H:i:s', strtotime('+1 hour'));
        } elseif ($duration === '1d') {
            $frozen_until = date('Y-m-d H:i:s', strtotime('+1 day'));
        } elseif ($duration === '1w') {
            $frozen_until = date('Y-m-d H:i:s', strtotime('+1 week'));
        }
        
        $stmt = $pdo->prepare("UPDATE users SET status = 'frozen', frozen_until = ? WHERE id = ?");
        $stmt->execute([$frozen_until, $user_id]);
        $_SESSION['admin_success'] = "L'utilisateur a été sanctionné.";
        if ($redirect_to === 'feed') $redirect_to = 'user-dashboard.php';
        else $redirect_to = 'admin-users.php';
        
    } elseif ($action === 'unfreeze_user') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $pdo->prepare("UPDATE users SET status = 'active', frozen_until = NULL WHERE id = ?")->execute([$user_id]);
        $_SESSION['admin_success'] = "Le gel du compte a été levé.";
        $redirect_to = 'admin-users.php';
        
    } elseif ($action === 'delete_user') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $pdo->prepare("DELETE FROM posts WHERE user_id = ?")->execute([$user_id]);
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
        $_SESSION['admin_success'] = "Utilisateur supprimé définitivement.";
        $redirect_to = 'admin-users.php';
        
    } elseif ($action === 'create_user') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $_SESSION['admin_error'] = "Cet email existe déjà.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (email, password, role, status) VALUES (?, ?, ?, 'active')");
            $stmt->execute([$email, $hash, $role]);
            $_SESSION['admin_success'] = "Profil créé et activé avec succès.";
        }
        $redirect_to = 'admin-users.php';
        
    } elseif ($action === 'change_role') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $role = $_POST['role'] ?? 'user';
        // On évite qu'il ne se rétrograde lui-même par erreur
        if ($user_id != $_SESSION['user_id']) {
            $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$role, $user_id]);
            $_SESSION['admin_success'] = "Rôle de l'utilisateur mis à jour.";
        }
        $redirect_to = 'admin-users.php';
        
    } elseif ($action === 'change_password') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $new_password = $_POST['new_password'] ?? '';
        if (!empty($new_password)) {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $user_id]);
            $_SESSION['admin_success'] = "Mot de passe modifié avec succès.";
        } else {
            $_SESSION['admin_error'] = "Le mot de passe ne peut être vide.";
        }
        $redirect_to = 'admin-users.php';
        
    } elseif ($action === 'freeze_post') {
        $post_id = intval($_POST['post_id'] ?? 0);
        $pdo->prepare("UPDATE posts SET status = 'frozen' WHERE id = ?")->execute([$post_id]);
        $_SESSION['admin_success'] = "Annonce suspendue (cachée du feed).";
        if ($redirect_to === 'feed') $redirect_to = 'user-dashboard.php';
        
    } elseif ($action === 'unfreeze_post') {
        $post_id = intval($_POST['post_id'] ?? 0);
        $pdo->prepare("UPDATE posts SET status = 'active' WHERE id = ?")->execute([$post_id]);
        $_SESSION['admin_success'] = "Annonce rétablie.";
        
    } elseif ($action === 'add_word') {
        $word = trim(strtolower($_POST['word'] ?? ''));
        if (!empty($word)) {
            try {
                $pdo->prepare("INSERT INTO blocked_words (word) VALUES (?)")->execute([$word]);
                $_SESSION['admin_success'] = "Mot ajouté à la liste noire.";
            } catch (PDOException $e) {
                $_SESSION['admin_error'] = "Ce mot existe déjà.";
            }
        }
        $redirect_to = 'admin-settings.php';
        
    } elseif ($action === 'delete_word') {
        $word_id = intval($_POST['word_id'] ?? 0);
        $pdo->prepare("DELETE FROM blocked_words WHERE id = ?")->execute([$word_id]);
        $_SESSION['admin_success'] = "Mot supprimé de la liste noire.";
        $redirect_to = 'admin-settings.php';
        
    } elseif ($action === 'delete_post') {
        $post_id = intval($_POST['post_id'] ?? 0);
        $pdo->prepare("DELETE FROM posts WHERE id = ?")->execute([$post_id]);
        $_SESSION['admin_success'] = "L'annonce a été supprimée.";
        if ($redirect_to === 'feed') $redirect_to = 'user-dashboard.php';
    }

    redirect($redirect_to);
}
