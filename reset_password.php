<?php
require_once 'config.php';

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$error = null;
$success = null;

if (empty($token)) {
    redirect('index.php');
}

// Vérifier la validité du token
$stmt = $pdo->prepare("SELECT id, reset_expires_at FROM users WHERE reset_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    $error = "Ce lien de réinitialisation est invalide ou n'existe plus.";
} elseif (strtotime($user['reset_expires_at']) < time()) {
    $error = "Ce lien a expiré. Veuillez refaire une demande.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (strlen($password) < 6) {
        $error = "Le mot de passe doit faire au moins 6 caractères.";
    } elseif ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires_at = NULL WHERE id = ?")->execute([$hash, $user['id']]);
        
        $success = "Votre mot de passe a été réinitialisé avec succès ! Vous pouvez maintenant vous connecter.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau mot de passe - Campus Connect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Outfit', 'sans-serif'] }, colors: { brand: { 500: '#0ea5e9', 600: '#0284c7' } } } } }
    </script>
</head>
<body class="bg-slate-900 font-sans text-white min-h-screen flex items-center justify-center relative overflow-hidden">
    <!-- Blobs -->
    <div class="absolute top-10 right-10 w-72 h-72 bg-brand-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20"></div>

    <div class="relative max-w-md w-full mx-4">
        <?php if ($error): ?>
            <div class="mb-4 bg-red-500/20 text-red-200 px-4 py-3 rounded-xl shadow-lg border border-red-500/50 text-center font-medium">
                <?= htmlspecialchars($error) ?>
            </div>
            <div class="text-center mt-4">
                <a href="forgot_password.php" class="text-brand-400 hover:underline">Refaire une demande</a>
            </div>
        <?php elseif ($success): ?>
            <div class="mb-4 bg-emerald-500/20 text-emerald-200 px-4 py-3 rounded-xl shadow-lg border border-emerald-500/50 text-center font-medium">
                <?= htmlspecialchars($success) ?>
            </div>
            <div class="text-center mt-6">
                <a href="index.php" class="inline-block px-8 py-3 bg-brand-500 hover:bg-brand-600 text-white font-bold rounded-xl shadow-lg transition-all">Se connecter</a>
            </div>
        <?php else: ?>
            <div class="bg-white/10 backdrop-blur-xl rounded-3xl p-8 shadow-2xl border border-white/20">
                <h2 class="text-2xl font-bold text-center mb-2">Nouveau mot de passe</h2>
                <p class="text-center text-slate-400 text-sm mb-8">Créez votre nouveau mot de passe sécurisé ci-dessous.</p>

                <form action="reset_password.php" method="POST" class="space-y-6">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-300 mb-2">Nouveau mot de passe</label>
                        <input type="password" name="password" required minlength="6" class="w-full bg-slate-800/50 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:ring-2 focus:ring-brand-500 outline-none transition-all">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-300 mb-2">Confirmer le mot de passe</label>
                        <input type="password" name="confirm_password" required minlength="6" class="w-full bg-slate-800/50 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:ring-2 focus:ring-brand-500 outline-none transition-all">
                    </div>

                    <button type="submit" class="w-full py-3 bg-brand-500 text-white font-bold rounded-xl shadow-lg hover:bg-brand-600 transition-all">
                        Enregistrer le mot de passe
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
