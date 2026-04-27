<?php
require_once 'config.php';

if (!is_logged_in()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = intval($_POST['post_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if (empty($message)) {
        $_SESSION['contact_error'] = "Le message ne peut pas être vide.";
        redirect('user-dashboard.php');
    }

    // Récupérer l'auteur du post
    $stmt = $pdo->prepare("SELECT users.email, users.id, posts.title FROM posts JOIN users ON posts.user_id = users.id WHERE posts.id = ?");
    $stmt->execute([$post_id]);
    $author = $stmt->fetch();

    if ($author) {
        $to = $author['email'];
        $subject = "[Campus Connect] Quelqu'un est intéressé par votre annonce : " . $author['title'];
        
        $my_email = $_SESSION['email'];
        
        $body = "Bonjour,\n\nVous avez reçu un message concernant votre annonce '{$author['title']}' sur Campus Connect.\n\n";
        $body .= "Message de $my_email :\n------------------\n";
        $body .= $message . "\n------------------\n\nVous pouvez répondre directement en écrivant à : $my_email";
        
        // Headers pour l'email
        $headers = "From: no-reply@campus-connect.fr\r\n";
        $headers .= "Reply-To: $my_email\r\n";

        // IMPORTANT : SUR UN SERVEUR LOCAL (XAMPP/WAMP sans config SMTP), la fonction mail() retournera false.
        $mail_sent = @mail($to, $subject, $body, $headers); // le @ permet de faire taire les warnings en local.

        // Dans tous les cas, on ajoute une "notification" dans la base de données
        $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $notif_msg = "Un utilisateur ($my_email) vous a envoyé un message pour: {$author['title']}";
        $notif_stmt->execute([$author['id'], $notif_msg]);

        if ($mail_sent) {
            $_SESSION['post_success'] = "Votre email a bien été envoyé à l'auteur, et il a reçu une notification.";
        } else {
            $_SESSION['post_success'] = "Email potentiellement non parti (configuration serveur local) mais l'auteur a reçu une notification interne !";
        }
    } else {
        $_SESSION['post_error'] = "Annonce ou auteur introuvable.";
    }

    redirect('user-dashboard.php');
}
