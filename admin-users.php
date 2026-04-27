<?php
require_once 'config.php';
if (!is_admin()) redirect('index.php');

$admin_success = $_SESSION['admin_success'] ?? null;
$admin_error = $_SESSION['admin_error'] ?? null;
unset($_SESSION['admin_success'], $_SESSION['admin_error']);

$pending_users_list = $pdo->query("SELECT * FROM users WHERE status = 'pending' ORDER BY created_at DESC")->fetchAll();
$other_users = $pdo->query("SELECT * FROM users WHERE status != 'pending' ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Connect - Gestion Utilisateurs</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Outfit', 'sans-serif'] }, colors: { brand: { 50: '#f0f9ff', 100: '#e0f2fe', 500: '#0ea5e9', 600: '#0284c7', 900: '#0c4a6e' } } } } }
    </script>
</head>
<body class="bg-slate-100 font-sans text-slate-900 h-screen flex overflow-hidden">
    
    <!-- Flash Messages -->
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
            <a href="admin-users.php" class="flex items-center px-4 py-3 bg-brand-600/20 text-brand-400 hover:text-brand-300 rounded-xl transition-all">
                <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                Utilisateurs
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

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto bg-slate-50 relative">
        <header class="bg-white/80 backdrop-blur-md sticky top-0 z-10 border-b border-slate-200 h-16 flex items-center px-8 shadow-sm">
            <h1 class="text-xl font-bold bg-gradient-to-r from-slate-800 to-slate-500 text-transparent bg-clip-text">Gestion des Utilisateurs</h1>
        </header>

        <div class="p-8 max-w-7xl mx-auto space-y-8">
            
            <!-- Pending Users -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                    <h2 class="font-bold text-slate-800 flex items-center gap-2">
                        <?php if(count($pending_users_list) > 0): ?><span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span><?php endif; ?>
                        Demandes d'inscription en attente (<?= count($pending_users_list) ?>)
                    </h2>
                </div>
                <div class="divide-y divide-slate-100">
                    <?php if (count($pending_users_list) == 0): ?>
                        <p class="p-6 text-sm text-slate-500 italic text-center">Aucune demande en attente.</p>
                    <?php else: ?>
                        <?php foreach ($pending_users_list as $u): ?>
                            <div class="p-5 flex items-center justify-between hover:bg-slate-50 transition-colors">
                                <div>
                                    <p class="font-bold text-slate-800"><?= htmlspecialchars($u['email']) ?></p>
                                    <p class="text-xs text-slate-500 mt-1">Demande reçue le <?= date('d/m/Y', strtotime($u['created_at'])) ?></p>
                                </div>
                                <div class="flex gap-2">
                                    <form action="admin_action.php" method="POST">
                                        <input type="hidden" name="action" value="validate_user">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="px-3 py-1.5 bg-emerald-100 text-emerald-700 hover:bg-emerald-200 rounded-lg text-sm font-bold transition-colors">Valider</button>
                                    </form>
                                    <form action="admin_action.php" method="POST" onsubmit="return confirm('Refuser et supprimer ?');">
                                        <input type="hidden" name="action" value="refuse_user">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="px-3 py-1.5 bg-rose-100 text-rose-700 hover:bg-rose-200 rounded-lg text-sm font-bold transition-colors">Refuser</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Create Form -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sticky top-24">
                        <h2 class="font-bold text-slate-800 mb-4">Création de profil manuel</h2>
                        <form action="admin_action.php" method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="create_user">
                            <div>
                                <label class="text-xs font-semibold text-slate-500">Email</label>
                                <input type="email" name="email" required class="mt-1 w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 outline-none">
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-500">Mot de passe provisoire</label>
                                <input type="password" name="password" required class="mt-1 w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 outline-none">
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-500">Rôle initial</label>
                                <select name="role" class="mt-1 w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none">
                                    <option value="user">Étudiant</option>
                                    <option value="admin">Administrateur</option>
                                </select>
                            </div>
                            <button type="submit" class="w-full py-2.5 bg-slate-800 text-white font-semibold rounded-lg hover:bg-slate-900 transition-colors shadow-sm">Créer & Activer</button>
                        </form>
                    </div>
                </div>

                <!-- Manage Existing Users -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <h2 class="font-bold text-slate-800 mb-6 border-b border-slate-100 pb-2">Annuaire des Membres Actifs & Gelés (<?= count($other_users) ?>)</h2>
                    <div class="space-y-4">
                        <?php foreach ($other_users as $u): ?>
                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 transition-all hover:shadow-sm">
                                <div class="flex flex-wrap items-center justify-between gap-4 mb-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center text-slate-600 font-bold">
                                            <?= strtoupper(substr($u['email'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <p class="font-bold text-sm text-slate-800"><?= htmlspecialchars($u['email']) ?></p>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="text-xs text-slate-500">Inscrit le <?= date('d/m/Y', strtotime($u['created_at'])) ?></span>
                                                <?php if ($u['status'] === 'frozen'): ?>
                                                    <span class="text-[10px] font-bold bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded">GELÉ</span>
                                                <?php else: ?>
                                                    <span class="text-[10px] font-bold bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded">ACTIF</span>
                                                <?php endif; ?>
                                                <?php if ($u['role'] === 'admin'): ?>
                                                    <span class="text-[10px] font-bold bg-brand-100 text-brand-700 px-1.5 py-0.5 rounded">ADMIN</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                        <!-- Actions rapides de sanction -->
                                        <div class="flex items-center gap-2">
                                            <?php if ($u['status'] === 'frozen'): ?>
                                                <form action="admin_action.php" method="POST">
                                                    <input type="hidden" name="action" value="unfreeze_user">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <button type="submit" class="text-xs font-medium bg-white border border-slate-300 px-3 py-1.5 rounded hover:bg-slate-100">Dégeler</button>
                                                </form>
                                            <?php else: ?>
                                                <form action="admin_action.php" method="POST" class="flex items-center gap-1">
                                                    <input type="hidden" name="action" value="freeze_user">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <select name="duration" class="text-xs border border-slate-200 rounded outline-none px-2 py-1.5">
                                                        <option value="indefinite">Gel Définitif</option>
                                                        <option value="1h">1 Heure</option>
                                                        <option value="1d">1 Jour</option>
                                                        <option value="1w">1 Semaine</option>
                                                    </select>
                                                    <button type="submit" class="text-xs font-bold bg-purple-100 text-purple-700 px-3 py-1.5 rounded hover:bg-purple-200">Geler</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <!-- Panel d'administration des données utilisateur -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4 pt-4 border-t border-slate-200/60">
                                    <!-- Rôle -->
                                    <form action="admin_action.php" method="POST" class="flex gap-2 items-center">
                                        <input type="hidden" name="action" value="change_role">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <select name="role" class="text-xs border border-slate-200 rounded px-2 py-1.5 outline-none flex-1">
                                            <option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>Rôle : Étudiant</option>
                                            <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Rôle : Administrateur</option>
                                        </select>
                                        <button type="submit" class="text-[10px] font-bold text-white bg-slate-600 px-2 py-1.5 rounded hover:bg-slate-700">Appliquer</button>
                                    </form>
                                    
                                    <!-- Mot de passe -->
                                    <form action="admin_action.php" method="POST" class="flex gap-2 items-center">
                                        <input type="hidden" name="action" value="change_password">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <input type="text" name="new_password" placeholder="Nouveau mdp..." required class="text-xs border border-slate-200 rounded px-2 py-1.5 outline-none flex-1">
                                        <button type="submit" class="text-[10px] font-bold text-white bg-slate-600 px-2 py-1.5 rounded hover:bg-slate-700">Changer mdp</button>
                                    </form>
                                </div>
                                <div class="flex justify-end mt-3">
                                    <form action="admin_action.php" method="POST" onsubmit="return confirm('La suppression est IRRÉVERSIBLE. Continuer ?');">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="text-xs font-bold text-red-600 hover:underline">Supprimer le profil définitivement</button>
                                    </form>
                                </div>
                                <?php else: ?>
                                    <p class="text-xs text-center text-slate-400 italic mt-2">C'est votre session actuelle. Vous ne pouvez pas modifier votre propre statut ici.</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        setTimeout(() => {
            const toasts = document.querySelectorAll('#toast-container > div');
            toasts.forEach(toast => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.5s ease-out';
                setTimeout(() => toast.remove(), 500);
            });
        }, 4000);
    </script>
</body>
</html>
