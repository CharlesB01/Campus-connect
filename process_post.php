<?php
require_once 'config.php';

if (!is_logged_in()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? 'demand'; // 'offer' or 'demand'
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $price_unit = $_POST['price_unit'] ?? 'Total';

    if (empty($title) || empty($description) || empty($category)) {
        $_SESSION['post_error'] = "Veuillez remplir les champs obligatoires (Titre, Description, Catégorie).";
        redirect('user-dashboard.php');
    }

    // Vérification des mots bloqués
    $stmt = $pdo->query("SELECT word FROM blocked_words");
    $blocked_words = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $content_to_check = strtolower($title . ' ' . $description);
    foreach ($blocked_words as $word) {
        $word_lower = strtolower($word);
        // Utilisation de regex pour trouver les mots complets
        if (preg_match('/\b' . preg_quote($word_lower, '/') . '\b/i', $content_to_check)) {
            $_SESSION['post_error'] = "Votre publication a été bloquée car elle contient un mot inapproprié ou interdit.";
            redirect('user-dashboard.php');
        }
    }

    // Si tout est ok, on insère
    $stmt = $pdo->prepare("INSERT INTO posts (user_id, type, title, description, category, price, price_unit) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$_SESSION['user_id'], $type, $title, $description, $category, $price, $price_unit])) {
        $_SESSION['post_success'] = "Annonce publiée avec succès !";
    } else {
        $_SESSION['post_error'] = "Erreur lors de la publication de l'annonce.";
    }

    redirect('user-dashboard.php');
}
