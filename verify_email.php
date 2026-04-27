<?php
require_once 'config.php';

// Redirection si l'utilisateur n'est pas passé par l'inscription ou s'il est déjà connecté
if (!isset($_SESSION['verify_email'])) {
    redirect('index.php');
}

$email = $_SESSION['verify_email'];
$error = null;
$success = null;

// Notification de débug si l'envoi d'email a échoué (serveur local)
$debug_code = $_SESSION['debug_code'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['resend'])) {
        $code = sprintf("%06d", mt_rand(1, 999999));
        $pdo->prepare("UPDATE users SET verification_code = ? WHERE email = ?")->execute([$code, $email]);
        
        $to = $email;
        $subject = "Campus Connect - Nouveau code de vérification";
        $message = "<html><body><h2>Nouveau code</h2><h1 style='color: #0ea5e9; font-size: 32px;'>$code</h1></body></html>";
        $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: no-reply@campus-connect.fr\r\n";
        
        if (!@mail($to, $subject, $message, $headers)) {
            $_SESSION['debug_code'] = $code;
        }
        $success = "Un nouveau code a été envoyé !";
        $debug_code = $_SESSION['debug_code'] ?? null;
    } else {
        $code_input = trim($_POST['code'] ?? '');
        
        $stmt = $pdo->prepare("SELECT id, verification_code FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && $user['verification_code'] === $code_input) {
            $pdo->prepare("UPDATE users SET is_email_verified = 1, verification_code = NULL WHERE id = ?")->execute([$user['id']]);
            
            unset($_SESSION['verify_email']);
            unset($_SESSION['debug_code']);
            
            $_SESSION['success'] = "Email vérifié ! Votre compte est en attente de validation par l'administration.";
            redirect('index.php');
        } else {
            $error = "Le code de vérification est incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification de l'Email - Campus Connect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Outfit', 'sans-serif'] }, colors: { brand: { 50: '#f0f9ff', 100: '#e0f2fe', 500: '#0ea5e9', 600: '#0284c7', 900: '#0c4a6e' } } } } }
    </script>
</head>
<body class="bg-slate-50 font-sans text-slate-900 min-h-screen flex items-center justify-center bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-sky-100 via-slate-50 to-purple-100">

    <div class="max-w-md w-full mx-4">
        <!-- Debug Notice for Local Development -->
        <?php if ($debug_code): ?>
            <div class="mb-4 bg-emerald-100 border border-emerald-300 text-emerald-800 px-4 py-3 rounded-xl shadow-sm">
                <p class="font-bold text-sm">Mode Débug (Localhost) :</p>
                <p class="text-sm">L'envoi de mail a échoué (SMTP non configuré). Voici le code reçu : <span class="font-bold text-lg"><?= $debug_code ?></span></p>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-4 bg-emerald-100 text-emerald-700 px-4 py-3 rounded-xl shadow-sm text-sm font-medium border border-emerald-200">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-4 bg-red-100 text-red-700 px-4 py-3 rounded-xl shadow-sm text-sm font-medium border border-red-200">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="bg-white/80 backdrop-blur-xl rounded-3xl p-8 shadow-2xl border border-white">
            <div class="w-16 h-16 bg-gradient-to-br from-brand-500 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg shadow-brand-500/30">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
            </div>
            
            <h2 class="text-2xl font-bold text-center text-slate-800 mb-2">Vérifiez votre Email</h2>
            <p class="text-center text-slate-500 text-sm mb-8">Nous avons envoyé un code à 6 chiffres à <br><span class="font-semibold text-slate-700"><?= htmlspecialchars($email) ?></span></p>

            <form action="verify_email.php" method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2 text-center">Code de vérification</label>
                    <input type="text" name="code" required maxlength="6" pattern="\d{6}" placeholder="000000" class="w-full text-center text-3xl tracking-[0.5em] font-bold bg-slate-50 border border-slate-200 rounded-xl px-4 py-4 text-slate-900 focus:ring-2 focus:ring-brand-500 transition-all outline-none">
                </div>

                <button type="submit" class="w-full py-3 bg-gradient-to-r from-brand-500 to-brand-600 text-white font-bold rounded-xl shadow-lg shadow-brand-500/30 hover:shadow-brand-500/50 hover:from-brand-600 hover:to-brand-700 transform transition-all hover:-translate-y-0.5">
                    Valider mon compte
                </button>
            </form>
            
            <div class="mt-8 text-center text-sm">
                <p class="text-slate-500">Vous n'avez pas reçu le code ?</p>
                <form action="verify_email.php" method="POST" class="inline">
                    <button type="submit" name="resend" value="1" class="font-bold text-brand-600 hover:text-brand-700 bg-transparent border-none cursor-pointer p-0 hover:underline">Renvoyer le code</button>
                </form>
            </div>
        </div>
    </div>

</body>
</html>
