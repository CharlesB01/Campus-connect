<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');

    if (empty($email) || empty($password)) {
        $_SESSION['auth_error'] = "Veuillez remplir tous les champs.";
        redirect('index.php');
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $_SESSION['auth_error'] = "Cet email est déjà utilisé.";
        redirect('index.php');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $code = sprintf("%06d", mt_rand(1, 999999));
    
    // On l'insère en base avec is_email_verified à 0
    $stmt = $pdo->prepare("INSERT INTO users (email, password, firstname, lastname, verification_code, is_email_verified, status) VALUES (?, ?, ?, ?, ?, 0, 'pending')");
    
    if ($stmt->execute([$email, $hash, $firstname, $lastname, $code])) {
        // Envoi de l'email
        $to = $email;
        $subject = "Campus Connect - Validation de votre compte";
        $message = "
        <html>
        <head><title>Vérification de compte</title></head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <h2>Bienvenue sur Campus Connect !</h2>
            <p>Bonjour " . htmlspecialchars($firstname) . ",</p>
            <p>Pour finaliser votre inscription, veuillez entrer le code de vérification suivant :</p>
            <div style='background: #f0f9ff; padding: 20px; text-align: center; border-radius: 8px; margin: 20px 0;'>
                <h1 style='color: #0ea5e9; font-size: 32px; letter-spacing: 5px; margin: 0;'>$code</h1>
            </div>
            <p>Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet email.</p>
            <br>
            <p>L'équipe Campus Connect</p>
        </body>
        </html>
        ";
        
        // Headers requis pour l'HTML
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: no-reply@campus-connect.fr" . "\r\n";

        $mail_sent = @mail($to, $subject, $message, $headers);
        
        // On mémorise l'email dans la session pour la page de vérification
        $_SESSION['verify_email'] = $email;
        
        // DEBUG LOCAL UNIQUEMENT (S'affiche si la fonction mail échoue à cause de l'absence de SMTP en local)
        if (!$mail_sent) {
            $_SESSION['debug_code'] = $code;
        }

        redirect('verify_email.php');
    } else {
        $_SESSION['auth_error'] = "Erreur lors de l'inscription.";
        redirect('index.php');
    }
}
