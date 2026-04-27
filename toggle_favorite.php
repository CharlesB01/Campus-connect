<?php
require_once 'config.php';

if (!is_logged_in()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = intval($_POST['post_id'] ?? 0);
    $user_id = $_SESSION['user_id'];

    if ($post_id > 0) {
        // Vérifier si déjà favori
        $stmt = $pdo->prepare("SELECT * FROM favorites WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$user_id, $post_id]);
        
        if ($stmt->fetch()) {
            // Retire des favoris
            $del = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND post_id = ?");
            $del->execute([$user_id, $post_id]);
        } else {
            // Ajoute aux favoris
            $add = $pdo->prepare("INSERT INTO favorites (user_id, post_id) VALUES (?, ?)");
            if ($add->execute([$user_id, $post_id])) {
                // Notifier l'auteur de l'annonce si l'action n'est pas sur sa propre annonce
                $stmtPost = $pdo->prepare("SELECT user_id, title FROM posts WHERE id = ?");
                $stmtPost->execute([$post_id]);
                $post = $stmtPost->fetch();
                
                if ($post && $post['user_id'] != $user_id) {
                    $my_email = $_SESSION['email'];
                    $msg = "Quelqu'un ($my_email) a ajouté votre annonce '{$post['title']}' en favori ! ❤️";
                    $notif = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                    $notif->execute([$post['user_id'], $msg]);
                }
            }
        }
    }
}

// Redirect back where we came from, usually user-dashboard.php
redirect('user-dashboard.php');
