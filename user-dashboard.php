<?php
require_once 'config.php';

if (!is_logged_in()) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];
$is_admin = is_admin();

$post_error = $_SESSION['post_error'] ?? null;
$post_success = $_SESSION['post_success'] ?? null;
$contact_error = $_SESSION['contact_error'] ?? null;

unset($_SESSION['post_error'], $_SESSION['post_success'], $_SESSION['contact_error']);

// Filter par catégorie
$filter_cat = $_GET['cat'] ?? '';

// Récupérer les posts
$query = "
    SELECT p.*, u.email, u.firstname, u.lastname,
       (SELECT COUNT(*) FROM favorites WHERE post_id = p.id AND user_id = $user_id) as is_favorited,
       (SELECT COUNT(*) FROM favorites WHERE post_id = p.id) as total_favorites
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.status = 'active'
";
$params = [];

if ($filter_cat) {
    $query .= " AND p.category = ?";
    $params[] = $filter_cat;
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$posts = $stmt->fetchAll();

// Récupérer les notifications non lues
$notif_stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
$notif_stmt->execute([$user_id]);
$notifications = $notif_stmt->fetchAll();

// Mettre à jour les notifications lues si cliqué (simulé ici par la visite pour l'instant - on pourrait l'isoler plus tard)
$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$user_id]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Connect - Mon Feed</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Outfit', 'sans-serif'] },
                    colors: {
                        brand: {
                            50: '#f0f9ff', 100: '#e0f2fe', 500: '#0ea5e9', 600: '#0284c7', 900: '#0c4a6e',
                        }
                    }
                }
            },
            plugins: [
                function ({ addUtilities }) {
                    addUtilities({
                        '.glass': {
                            'background-color': 'rgba(255, 255, 255, 0.85)',
                            'backdrop-filter': 'blur(16px)',
                            'border': '1px solid rgba(255, 255, 255, 0.4)',
                            'box-shadow': '0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03)'
                        },
                        '.glass-dark': {
                            'background-color': 'rgba(15, 23, 42, 0.8)',
                            'backdrop-filter': 'blur(16px)',
                            'border': '1px solid rgba(255, 255, 255, 0.1)',
                        }
                    })
                }
            ]
        }
    </script>
</head>
<body class="bg-slate-50 font-sans text-slate-900 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-sky-50 via-slate-50 to-purple-50 min-h-screen">

    <!-- Flash Messages -->
    <div id="toast-container" class="fixed top-20 right-4 z-50 flex flex-col gap-2">
        <?php if ($post_error || $contact_error): ?>
            <div class="bg-red-500 text-white px-4 py-3 rounded-lg shadow-xl flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <?= htmlspecialchars($post_error ?? $contact_error) ?>
            </div>
        <?php endif; ?>
        <?php if ($post_success): ?>
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white px-4 py-3 rounded-lg shadow-xl flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <?= htmlspecialchars($post_success) ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Top Navigation -->
    <nav class="sticky top-0 z-40 glass border-b border-white/40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center gap-4">
                    <a href="index.php" class="flex items-center gap-2 transform transition-transform hover:scale-105">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-brand-500 to-purple-600 flex items-center justify-center text-white shadow-md">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        </div>
                        <span class="font-bold text-xl tracking-tight text-slate-900 hidden sm:block bg-gradient-to-r from-slate-900 to-slate-600 text-transparent bg-clip-text">Campus Connect</span>
                    </a>
                </div>

                <div class="flex items-center gap-6">
                    <!-- Notifications -->
                    <div class="relative group cursor-pointer">
                        <button class="p-2 text-slate-500 hover:text-brand-600 transition-colors relative">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <?php if (count($notifications) > 0): ?>
                                <span class="absolute top-1 right-1 flex h-3 w-3">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500 border-2 border-white"></span>
                                </span>
                            <?php endif; ?>
                        </button>
                        <!-- Dropdown Notifs (visible en hover pour la démo) -->
                        <div class="absolute right-0 mt-2 w-80 glass rounded-xl shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 transform translate-y-2 group-hover:translate-y-0 p-4 border border-white/50 z-50">
                            <h4 class="font-bold text-slate-800 mb-2 border-b pb-2">Notifications récentes</h4>
                            <?php if (count($notifications) > 0): ?>
                                <ul class="space-y-3">
                                    <?php foreach ($notifications as $n): ?>
                                        <li class="text-sm text-slate-600"><?= htmlspecialchars($n['message']) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-sm text-slate-500 italic">Aucune nouvelle notification.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <div class="hidden sm:block text-right">
                            <p class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($_SESSION['email']) ?></p>
                            <p class="text-xs text-brand-600 font-medium"><?= $is_admin ? 'Administrateur' : 'Étudiant' ?></p>
                        </div>
                        <img class="h-9 w-9 rounded-full ring-2 ring-brand-100 shadow-sm" src="https://ui-avatars.com/api/?name=<?= urlencode(substr($_SESSION['email'], 0, 2)) ?>&background=0ea5e9&color=fff" alt="">
                    </div>
                    <?php if ($is_admin): ?>
                        <a href="admin-dashboard.php" class="p-2 bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition-colors shadow-sm" title="Panel Admin">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        </a>
                    <?php endif; ?>
                    <a href="logout.php" class="text-slate-400 hover:text-red-500 transition-colors" title="Déconnexion">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Sidebar (Desktop) -->
            <div class="hidden lg:block w-64 flex-shrink-0">
                <div class="sticky top-24 glass rounded-2xl p-4 shadow-sm">
                    <div class="space-y-1">
                        <a href="user-dashboard.php" class="flex items-center px-4 py-3 <?= !$filter_cat ? 'bg-brand-50 text-brand-600 font-semibold shadow-sm border border-brand-100' : 'text-slate-600 hover:bg-slate-50' ?> rounded-xl transition-all">
                            <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" /></svg>
                            Fil d'actualité
                        </a>
                    </div>
                    
                    <div class="pt-6 mt-4 border-t border-slate-100">
                        <p class="px-4 text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Catégories</p>
                        <div class="space-y-1">
                            <?php
                            $categories = ['Cours & Soutien', 'Déménagement', 'Réparations', 'Événements', 'Vente'];
                            foreach ($categories as $cat):
                                $isActive = ($filter_cat === $cat);
                            ?>
                                <a href="?cat=<?= urlencode($cat) ?>" class="block px-4 py-2 <?= $isActive ? 'bg-brand-50 text-brand-600 font-medium rounded-lg' : 'text-slate-600 hover:bg-slate-50 rounded-lg' ?> text-sm transition-colors">
                                    <?= htmlspecialchars($cat) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Feed -->
            <main class="flex-1 min-w-0">
                <!-- Create Post Card -->
                <div class="glass rounded-2xl p-6 mb-8 shadow-sm border border-white/60">
                    <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <svg class="w-6 h-6 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        Publier une annonce
                    </h2>
                    <form action="process_post.php" method="POST" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2">
                                <input type="text" name="title" required placeholder="Titre de votre annonce..." class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all shadow-inner">
                            </div>
                            <div>
                                <select name="type" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-700 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all font-medium">
                                    <option value="offer">Je propose (Offre)</option>
                                    <option value="demand">Je cherche (Demande)</option>
                                </select>
                            </div>
                        </div>
                        
                        <textarea name="description" required rows="3" placeholder="Décrivez votre besoin ou votre service en détail..." class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all shadow-inner"></textarea>
                        
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-slate-500 mb-1">Catégorie</label>
                                <select name="category" required class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2 text-slate-700 focus:ring-2 focus:ring-brand-500 transition-all">
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 mb-1">Prix (€)</label>
                                <input type="number" step="0.5" name="price" placeholder="Ex: 15" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2 text-slate-900 focus:ring-2 focus:ring-brand-500 transition-all">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 mb-1">Unité</label>
                                <select name="price_unit" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2 text-slate-700 focus:ring-2 focus:ring-brand-500 transition-all">
                                    <option value="Total">Total</option>
                                    <option value="/ Heure">/ Heure</option>
                                    <option value="/ Jour">/ Jour</option>
                                    <option value="Gratuit">Gratuit</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex justify-end pt-2 border-t border-slate-100 mt-4">
                            <button type="submit" class="px-8 py-2.5 bg-gradient-to-r from-brand-500 to-brand-600 text-white font-semibold rounded-xl shadow-lg shadow-brand-500/30 hover:shadow-brand-500/50 hover:from-brand-600 hover:to-brand-700 transform transition-all hover:-translate-y-0.5">
                                Publier maintenant
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Feed Container -->
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-slate-800">
                        <?= $filter_cat ? "Annonces: " . htmlspecialchars($filter_cat) : "Dernières annonces" ?>
                    </h3>
                    <span class="text-sm text-slate-500 bg-white px-3 py-1 rounded-full border border-slate-200 shadow-sm"><?= count($posts) ?> résultats</span>
                </div>

                <div class="space-y-6">
                    <?php if (count($posts) === 0): ?>
                        <div class="text-center py-12 glass rounded-2xl border border-dashed border-slate-300">
                            <svg class="mx-auto h-12 w-12 text-slate-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" /></svg>
                            <h3 class="text-lg font-medium text-slate-900">Aucune annonce trouvée</h3>
                            <p class="text-slate-500">Soyez le premier à publier dans cette catégorie !</p>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($posts as $post): 
                        $is_offer = $post['type'] === 'offer';
                        $badge_class = $is_offer ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-orange-100 text-orange-700 border-orange-200';
                        $badge_text = $is_offer ? 'Propose' : 'Recherche';
                        $author_name = $post['firstname'] ? htmlspecialchars($post['firstname'] . ' ' . $post['lastname']) : htmlspecialchars(explode('@', $post['email'])[0]);
                        $date_str = date('d/m/Y à H:i', strtotime($post['created_at']));
                    ?>
                        <div class="glass rounded-2xl p-6 hover:shadow-lg transition-all duration-300 border border-white/60 transform hover:-translate-y-1">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex items-center gap-3">
                                    <img class="h-12 w-12 rounded-full shadow-sm" src="https://ui-avatars.com/api/?name=<?= urlencode(substr($author_name, 0, 2)) ?>&background=random&color=fff" alt="">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h4 class="font-bold text-slate-900"><?= $author_name ?></h4>
                                            <span class="px-2 py-0.5 rounded text-xs font-bold border <?= $badge_class ?>"><?= $badge_text ?></span>
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-slate-500 mt-0.5">
                                            <span><?= $date_str ?></span>
                                            <span>•</span>
                                            <span class="font-medium bg-slate-100 px-2 rounded-full"><?= htmlspecialchars($post['category']) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right flex flex-col items-end">
                                    <?php if ($post['price'] > 0 || $post['price_unit'] !== 'Gratuit'): ?>
                                        <span class="block text-2xl font-black <?= $is_offer ? 'text-emerald-600' : 'text-orange-600' ?>">
                                            <?= $post['price'] > 0 ? htmlspecialchars($post['price']) . '€' : '' ?>
                                            <span class="text-sm font-medium text-slate-500"><?= htmlspecialchars($post['price_unit']) ?></span>
                                        </span>
                                    <?php else: ?>
                                        <span class="block text-xl font-black text-brand-600">Gratuit</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <h3 class="text-xl font-bold text-slate-800 mb-3"><?= nl2br(htmlspecialchars($post['title'])) ?></h3>
                            <p class="text-slate-600 leading-relaxed mb-6 bg-slate-50 p-4 rounded-xl border border-slate-100"><?= nl2br(htmlspecialchars($post['description'])) ?></p>

                            <div class="flex items-center justify-between border-t border-slate-100 pt-4">
                                <div class="flex gap-2">
                                    <form action="toggle_favorite.php" method="POST" class="inline">
                                        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                        <button type="submit" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?= $post['is_favorited'] ? 'text-red-500 bg-red-50 hover:bg-red-100' : 'text-slate-500 hover:text-red-500 hover:bg-slate-50' ?>">
                                            <svg class="w-5 h-5 <?= $post['is_favorited'] ? 'fill-current' : '' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                                            <span class="<?= $post['is_favorited'] ? 'font-bold' : '' ?>"><?= $post['total_favorites'] ?></span>
                                        </button>
                                    </form>
                                    <?php if ($post['user_id'] != $user_id): ?>
                                    <button onclick="openContactModal(<?= $post['id'] ?>, '<?= htmlspecialchars(addslashes($post['title'])) ?>')" class="flex items-center gap-1.5 px-4 py-1.5 rounded-lg text-sm font-medium text-brand-600 bg-brand-50 hover:bg-brand-100 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                                        Contacter
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <?php if ($is_admin): ?>
                                    <div class="flex gap-2">
                                        <form action="admin_action.php" method="POST" onsubmit="return confirm('Suspendre cette annonce ?');">
                                            <input type="hidden" name="action" value="freeze_post">
                                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                            <input type="hidden" name="redirect" value="feed">
                                            <button type="submit" class="text-xs font-medium text-orange-600 bg-orange-50 hover:bg-orange-100 px-3 py-1.5 rounded-lg">Cacher Annonce</button>
                                        </form>
                                        <form action="admin_action.php" method="POST" onsubmit="return confirm('GELER l\'auteur de cette annonce indéfiniment ?');">
                                            <input type="hidden" name="action" value="freeze_user">
                                            <input type="hidden" name="user_id" value="<?= $post['user_id'] ?>">
                                            <input type="hidden" name="duration" value="indefinite">
                                            <input type="hidden" name="redirect" value="feed">
                                            <button type="submit" class="text-xs font-bold text-white bg-red-500 hover:bg-red-600 px-3 py-1.5 rounded-lg">Geler Auteur</button>
                                        </form>
                                        <form action="admin_action.php" method="POST" onsubmit="return confirm('Supprimer définitivement l\'annonce ?');">
                                            <input type="hidden" name="action" value="delete_post">
                                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                            <input type="hidden" name="redirect" value="feed">
                                            <button type="submit" class="text-xs font-medium text-white bg-slate-800 hover:bg-slate-900 px-3 py-1.5 rounded-lg">Supprimer</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Contact Modal -->
    <div id="contact-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeContactModal()"></div>
            <div class="relative inline-block align-bottom glass rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full border border-white/20">
                <div class="bg-gradient-to-r from-brand-600 to-brand-500 px-6 py-4">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2" id="modal-title">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                        Envoyer un message
                    </h3>
                </div>
                <form action="send_message.php" method="POST" class="p-6">
                    <input type="hidden" name="post_id" id="contact_post_id">
                    <p class="text-sm border-b border-slate-100 pb-3 mb-4 font-medium text-slate-700">Concerne : <span id="contact_post_title" class="text-brand-600 font-bold"></span></p>
                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Votre message sera envoyé par email :</label>
                        <textarea name="message" required rows="5" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-900 focus:ring-2 focus:ring-brand-500 transition-all" placeholder="Bonjour, je suis intéressé par votre annonce..."></textarea>
                    </div>
                    
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" onclick="closeContactModal()" class="px-5 py-2.5 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 font-semibold rounded-xl transition-all">Annuler</button>
                        <button type="submit" class="px-5 py-2.5 bg-brand-500 hover:bg-brand-600 text-white font-semibold rounded-xl shadow-lg shadow-brand-500/30 transition-all">Envoyer l'email</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openContactModal(postId, postTitle) {
            document.getElementById('contact_post_id').value = postId;
            document.getElementById('contact_post_title').textContent = postTitle;
            document.getElementById('contact-modal').classList.remove('hidden');
        }
        
        function closeContactModal() {
            document.getElementById('contact-modal').classList.add('hidden');
        }

        setTimeout(() => {
            const toasts = document.querySelectorAll('#toast-container > div');
            toasts.forEach(toast => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.5s ease-out';
                setTimeout(() => toast.remove(), 500);
            });
        }, 6000);
    </script>
</body>
</html>
