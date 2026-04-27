<?php
require_once 'config.php';
if (!is_admin()) redirect('index.php');

$admin_success = $_SESSION['admin_success'] ?? null;
$admin_error = $_SESSION['admin_error'] ?? null;
unset($_SESSION['admin_success'], $_SESSION['admin_error']);

// Statistiques Globales
$stats['active_posts'] = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'active'")->fetchColumn();
$stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$stats['pending_users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();

// Annonces récentes pour modération
$recent_posts = $pdo->query("
    SELECT p.*, u.email 
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.status = 'active'
    ORDER BY p.created_at DESC LIMIT 10
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Connect - Vue d'ensemble Admin</title>
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
            <a href="admin-dashboard.php" class="flex items-center px-4 py-3 bg-brand-600/20 text-brand-400 hover:text-brand-300 rounded-xl transition-all">
                <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                Tableau de bord
            </a>
            <a href="admin-users.php" class="flex items-center px-4 py-3 text-slate-400 hover:bg-slate-800/50 hover:text-white rounded-xl transition-all">
                <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                Utilisateurs
                <?php if ($stats['pending_users'] > 0): ?>
                    <span class="ml-auto bg-red-500 text-white rounded-full px-2 py-0.5 text-xs font-bold"><?= $stats['pending_users'] ?></span>
                <?php endif; ?>
            </a>
            <a href="admin-settings.php" class="flex items-center px-4 py-3 text-slate-400 hover:bg-slate-800/50 hover:text-white rounded-xl transition-all">
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
            <h1 class="text-xl font-bold bg-gradient-to-r from-slate-800 to-slate-500 text-transparent bg-clip-text">Aperçu Général</h1>
        </header>

        <div class="p-8 max-w-7xl mx-auto space-y-12">
            
            <!-- KPI -->
            <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition-transform"><svg class="w-16 h-16 text-brand-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29-3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/></svg></div>
                    <p class="text-slate-500 text-sm font-bold uppercase tracking-wider">Annonces Actives</p>
                    <p class="text-4xl font-black text-slate-800 mt-2"><?= $stats['active_posts'] ?></p>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition-transform"><svg class="w-16 h-16 text-purple-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg></div>
                    <p class="text-slate-500 text-sm font-bold uppercase tracking-wider">Total Utilisateurs</p>
                    <p class="text-4xl font-black text-slate-800 mt-2"><?= $stats['total_users'] ?></p>
                </div>
                <a href="admin-users.php" class="bg-gradient-to-br from-red-500 to-rose-600 p-6 rounded-2xl shadow-xl text-white relative overflow-hidden group hover:shadow-2xl transition-all cursor-pointer">
                    <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:scale-110 transition-transform"><svg class="w-16 h-16" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg></div>
                    <div class="relative z-10">
                        <p class="text-red-100 text-sm font-bold uppercase tracking-wider drop-shadow-sm">En attente de validation</p>
                        <p class="text-4xl font-black mt-2 drop-shadow-md"><?= $stats['pending_users'] ?></p>
                        <p class="text-sm font-medium mt-1 opacity-90">Cliquez pour gérer les inscriptions</p>
                    </div>
                </a>
            </section>

            <!-- Recents -->
            <section class="grid grid-cols-1 gap-8">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 overflow-y-auto">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="font-bold text-slate-800 text-lg">Dernières Annonces Publiées</h2>
                        <span class="text-sm text-slate-500 border border-slate-200 px-3 py-1 rounded-full shadow-sm bg-slate-50">Surveillez l'activité</span>
                    </div>
                    <div class="space-y-4">
                        <?php foreach ($recent_posts as $p): ?>
                            <div class="p-5 bg-slate-50 border border-slate-200 rounded-xl flex justify-between items-start hover:shadow-md transition-shadow">
                                <div>
                                    <h3 class="font-bold text-base text-slate-900 mb-1"><?= htmlspecialchars($p['title']) ?></h3>
                                    <p class="text-sm text-slate-600 line-clamp-2 max-w-2xl mb-2"><?= htmlspecialchars($p['description']) ?></p>
                                    <div class="flex gap-2 text-xs font-medium">
                                        <span class="text-slate-500">Par <?= htmlspecialchars($p['email']) ?> le <?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></span>
                                        <span class="text-brand-600 bg-brand-50 px-2 rounded"><?= htmlspecialchars($p['category']) ?></span>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <form action="admin_action.php" method="POST" onsubmit="return confirm('Suspendre cette annonce ?');">
                                        <input type="hidden" name="action" value="freeze_post">
                                        <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="redirect" value="admin-dashboard.php">
                                        <button type="submit" class="w-full text-xs font-medium text-orange-600 bg-orange-50 border border-orange-100 hover:bg-orange-100 px-3 py-1.5 rounded-lg transition-colors">Cacher Annonce</button>
                                    </form>
                                    <form action="admin_action.php" method="POST" onsubmit="return confirm('Supprimer définitivement l\'annonce ?');">
                                        <input type="hidden" name="action" value="delete_post">
                                        <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="redirect" value="admin-dashboard.php">
                                        <button type="submit" class="w-full text-xs font-bold text-white bg-red-500 hover:bg-red-600 px-3 py-1.5 rounded-lg transition-colors">Supprimer</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (count($recent_posts) == 0): ?>
                            <p class="text-sm text-slate-500 italic text-center py-8">Aucune annonce trouvée.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
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
