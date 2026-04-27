<?php
require_once 'config.php';

$error = null;
$success = null;
$debug_link = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    $stmt = $pdo->prepare("SELECT id, firstname FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires_at = ? WHERE id = ?")->execute([$token, $expires, $user['id']]);
        
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
        
        $to = $email;
        $subject = "Campus Connect - Réinitialisation du mot de passe";
        $message = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <h2>Réinitialisation de votre mot de passe</h2>
            <p>Bonjour " . htmlspecialchars($user['firstname']) . ",</p>
            <p>Vous avez demandé à réinitialiser votre mot de passe. Cliquez sur le lien ci-dessous pour en créer un nouveau :</p>
            <p><a href='$reset_link' style='display: inline-block; padding: 10px 20px; background-color: #0ea5e9; color: white; text-decoration: none; border-radius: 5px;'>Réinitialiser mon mot de passe</a></p>
            <p>Si le bouton ne fonctionne pas, copiez-collez ce lien dans votre navigateur :</p>
            <p><small>$reset_link</small></p>
            <p>Ce lien est valide pendant 1 heure.</p>
        </body>
        </html>
        ";
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: no-reply@campus-connect.fr" . "\r\n";

        $mail_sent = @mail($to, $subject, $message, $headers);
        
        $success = "Un lien de réinitialisation vous a été envoyé par email. (Pensez à vérifier vos spams).";
        
        if (!$mail_sent) {
            $debug_link = $reset_link; // Fallback pour dev local
        }
    } else {
        // Pour des raisons de sécurité, on affiche le même message succès même si l'email n'existe pas.
        $success = "Un lien de réinitialisation vous a été envoyé par email. (Pensez à vérifier vos spams).";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - Campus Connect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Outfit', 'sans-serif'] }, colors: { brand: { 500: '#0ea5e9', 600: '#0284c7' } } } } }
    </script>
</head>
<body class="bg-slate-900 font-sans text-white min-h-screen flex items-center justify-center relative overflow-hidden">
    <!-- Blobs -->
    <div class="absolute top-10 left-10 w-72 h-72 bg-brand-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20"></div>
    <div class="absolute bottom-10 right-10 w-72 h-72 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20"></div>

    <div class="relative max-w-md w-full mx-4">
        <?php if ($debug_link): ?>
            <div class="mb-4 bg-emerald-100 border border-emerald-300 text-emerald-800 px-4 py-3 rounded-xl shadow-sm">
                <p class="font-bold text-sm">Mode Débug (Localhost) :</p>
                <p class="text-sm">L'envoi de mail a échoué. <a href="<?= htmlspecialchars($debug_link) ?>" class="underline font-bold">Cliquez ici pour aller au lien de reset généré</a>.</p>
            </div>
        <?php endif; ?>

        <?php if ($success && !$debug_link): ?>
            <div class="mb-4 bg-emerald-500 text-white px-4 py-3 rounded-xl shadow-lg border border-emerald-400 text-center font-medium">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <div class="bg-white/10 backdrop-blur-xl rounded-3xl p-8 shadow-2xl border border-white/20">
            <h2 class="text-2xl font-bold text-center mb-2">Mot de passe oublié</h2>
            <p class="text-center text-slate-400 text-sm mb-8">Entrez votre email universitaire. Nous vous enverrons un lien pour créer un nouveau mot de passe.</p>

            <form action="forgot_password.php" method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-semibold text-slate-300 mb-2">Adresse Email</label>
                    <input type="email" name="email" required placeholder="etudiant@campus.edu" class="w-full bg-slate-800/50 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:ring-2 focus:ring-brand-500 outline-none transition-all">
                </div>

                <button type="submit" class="w-full py-3 bg-brand-500 text-white font-bold rounded-xl shadow-lg hover:bg-brand-600 transition-all">
                    Envoyer le lien
                </button>
            </form>
            
            <div class="mt-6 text-center text-sm">
                <a href="index.php" class="text-brand-400 hover:text-brand-300 hover:underline flex items-center justify-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Retour à l'accueil
                </a>
            </div>
        </div>
    </div>
</body>
</html>
