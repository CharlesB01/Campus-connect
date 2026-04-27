<?php
require_once 'config.php';
if (!is_admin()) redirect('index.php');

$admin_success = $_SESSION['admin_success'] ?? null;
$admin_error = $_SESSION['admin_error'] ?? null;
unset($_SESSION['admin_success'], $_SESSION['admin_error']);

$blocked_words = $pdo->query("SELECT * FROM blocked_words ORDER BY word ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Connect - Sécurité & Censure</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Outfit', 'sans-serif'] }, colors: { brand: { 50: '#f0f9ff', 100: '#e0f2fe', 500: '#0ea5e9', 600: '#0284c7', 900: '#0c4a6e' } } } } }
    </script>
</head>
<body class="bg-slate-100 font-sans text-slate-900 h-screen flex overflow-hidden">
    
    <!-- Flash -->
    <div id="toast-container" class="fixed top-4 right-4 z-50">
        <?php if ($admin_error): ?>
            <div class="bg-red-500 text-white px-4 py-3 rounded-lg shadow-xl mb-2"><?= htmlspecialchars($admin_error) ?></div>
        <?php endif; ?>
        <?php if ($admin_success): ?>
            <div class="bg-green-500 text-white px-4 py-3 rounded-lg shadow-xl mb-2"><?= htmlspecialchars($admin_success) ?></div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <aside class="w-64 bg-slate-900 border-r border-slate-800 flex flex-col flex-shrink-0 z-20">
        <div class="h-16 flex items-center px-6 border-b border-white/10 bg-slate-800/50">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-brand-500 to-purple-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                </div>
                <span class="font-bold text-lg tracking-tight text-white">Admin Panel</span>
            </div>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
            <a href="admin-dashboard.php" class="flex items-center px-4 py-3 text-slate-400 hover:bg-slate-800/50 hover:text-white rounded-xl transition-all">
                <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                Tableau de bord
            </a>
            <a href="admin-users.php" class="flex items-center px-4 py-3 text-slate-400 hover:bg-slate-800/50 hover:text-white rounded-xl transition-all">
                <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                Utilisateurs
            </a>
            <a href="admin-settings.php" class="flex items-center px-4 py-3 bg-brand-600/20 text-brand-400 hover:text-brand-300 rounded-xl transition-all">
                <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                Sécurité & Mots
            </a>
        </nav>

        <div class="p-4 border-t border-slate-800">
            <a href="index.php" class="flex items-center justify-center gap-2 w-full py-2.5 bg-slate-800 hover:bg-slate-700 text-white rounded-xl transition-colors font-medium text-sm">Quitter Admin</a>
        </div>
    </aside>

    <main class="flex-1 overflow-y-auto bg-slate-50 relative">
        <header class="bg-white/80 backdrop-blur-md sticky top-0 z-10 border-b border-slate-200 h-16 flex items-center px-8 shadow-sm">
            <h1 class="text-xl font-bold bg-gradient-to-r from-slate-800 to-slate-500 text-transparent bg-clip-text">Sécurité & Censure</h1>
        </header>

        <div class="p-8 max-w-4xl mx-auto">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="font-bold text-slate-800 text-lg mb-1">Mots Interdits (Liste Noire)</h2>
                        <p class="text-sm text-slate-500">Toute annonce contenant l'un de ces mots sera automatiquement bloquée à la création et ne sera jamais publiée.</p>
                    </div>
                </div>
                
                <form action="admin_action.php" method="POST" class="flex gap-3 mb-8 bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <input type="hidden" name="action" value="add_word">
                    <input type="text" name="word" placeholder="Taper un nouveau mot à interdire..." required class="flex-1 bg-white border border-slate-200 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-brand-500 outline-none">
                    <button type="submit" class="px-6 py-2 bg-slate-800 text-white text-sm font-bold rounded-lg hover:bg-slate-900 shadow-sm transition-colors">Ajouter ce mot</button>
                </form>

                <div class="flex flex-wrap gap-3">
                    <?php if (count($blocked_words) == 0): ?>
                        <p class="text-sm text-slate-400 italic">Aucun mot n'est bloqué actuellement.</p>
                    <?php endif; ?>
                    <?php foreach ($blocked_words as $w): ?>
                        <div class="group inline-flex items-center gap-2 bg-red-50 border border-red-100 text-red-700 px-4 py-2 rounded-full text-sm font-medium hover:bg-red-100 transition-colors">
                            <?= htmlspecialchars($w['word']) ?>
                            <form action="admin_action.php" method="POST" class="inline flex items-center">
                                <input type="hidden" name="action" value="delete_word">
                                <input type="hidden" name="word_id" value="<?= $w['id'] ?>">
                                <button type="submit" class="text-red-400 hover:text-red-700 focus:outline-none transition-colors" title="Retirer ce mot de la liste">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        setTimeout(() => {
            const toasts = document.querySelectorAll('#toast-container > div');
            toasts.forEach(toast => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 500); });
        }, 4000);
    </script>
</body>
</html>
