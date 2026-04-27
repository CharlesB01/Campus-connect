<?php
require_once 'config.php';

// Logique pour auto-initialiser la base de données si elle est vide
$stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
if (!$stmt->fetch()) {
    require_once 'init_db.php';
}

$is_admin = is_admin();
$is_logged_in = is_logged_in();

$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;
$error_login = $_SESSION['error_login'] ?? null;

unset($_SESSION['error'], $_SESSION['success'], $_SESSION['error_login']);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Connect - L'entraide étudiante</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            900: '#0c4a6e',
                        }
                    }
                }
            },
            plugins: [
                function ({ addUtilities }) {
                    addUtilities({
                        '.glass': {
                            'background-color': 'rgba(255, 255, 255, 0.1)',
                            'backdrop-filter': 'blur(16px)',
                            'border': '1px solid rgba(255, 255, 255, 0.2)',
                        }
                    })
                }
            ]
        }
    </script>
</head>

<body
    class="bg-slate-900 text-white font-sans antialiased overflow-x-hidden selection:bg-brand-500 selection:text-white">

    <!-- Flash Messages (Toasts) -->
    <div id="toast-container" class="fixed top-20 right-4 z-50 flex flex-col gap-2">
        <?php if ($error): ?>
            <div class="bg-red-500 text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-2 animate-bounce">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if ($error_login): ?>
            <div class="bg-red-500 text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-2"
                style="animation: bounce 1s">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <?= htmlspecialchars($error_login) ?>
            </div>
            <!-- Auto open login modal if there's a login error -->
            <script>document.addEventListener("DOMContentLoaded", () => toggleModal('login'));</script>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="bg-green-500 text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Navigation -->
    <nav class="fixed w-full z-50 glass border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center gap-2 cursor-pointer"
                    onclick="window.location.href='index.php'">
                    <div
                        class="w-8 h-8 rounded-lg bg-gradient-to-br from-brand-500 to-purple-600 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <span class="font-bold text-xl tracking-tight">Campus Connect</span>
                </div>

                <!-- Auth Buttons -->
                <div class="hidden md:flex items-center space-x-4">
                    <?php if ($is_logged_in): ?>
                        <span class="text-sm font-medium text-slate-300">Bonjour,
                            <?= htmlspecialchars($_SESSION['email']) ?></span>
                        <a href="user-dashboard.php"
                            class="text-sm font-medium text-brand-400 hover:text-brand-300 transition-colors">Mon Feed</a>
                        <?php if ($is_admin): ?>
                            <a href="admin-dashboard.php"
                                class="text-sm font-medium text-brand-400 hover:text-brand-300 transition-colors">Panel
                                Admin</a>
                        <?php endif; ?>
                        <a href="logout.php"
                            class="bg-red-500/20 hover:bg-red-500 text-red-100 hover:text-white px-4 py-2 rounded-full text-sm font-medium transition-all">Déconnexion</a>
                    <?php else: ?>
                        <button class="text-sm font-medium text-slate-300 hover:text-white transition-colors"
                            onclick="toggleModal('login')">Se connecter</button>
                        <button
                            class="bg-brand-500 hover:bg-brand-600 text-white px-5 py-2 rounded-full text-sm font-medium transition-all shadow-lg shadow-brand-500/20"
                            onclick="toggleModal('register')">
                            Rejoindre le campus
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="relative min-h-screen flex items-center justify-center pt-16">
        <!-- Background Blobs -->
        <div
            class="absolute top-0 -left-4 w-72 h-72 bg-purple-500 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob">
        </div>
        <div
            class="absolute top-0 -right-4 w-72 h-72 bg-brand-500 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob animation-delay-2000">
        </div>
        <div
            class="absolute -bottom-8 left-20 w-72 h-72 bg-pink-500 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob animation-delay-4000">
        </div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col items-center text-center">
            <div
                class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/5 border border-white/10 text-brand-400 text-xs font-semibold uppercase tracking-wide mb-6">
                <span class="w-2 h-2 rounded-full bg-brand-500 animate-pulse"></span>
                Nouveau sur le campus
            </div>

            <h1
                class="text-5xl md:text-7xl font-bold tracking-tight mb-6 bg-gradient-to-r from-white via-slate-200 to-slate-400 text-transparent bg-clip-text">
                Connectez vos talents <br />
                <span class="text-white">avec votre campus</span>
            </h1>

            <p class="mt-4 text-xl text-slate-400 max-w-2xl mx-auto mb-10">
                La plateforme dynamique dédiée aux étudiants. Échangez des compétences, trouvez de l'aide, et
                boostez votre vie étudiante.
            </p>

            <div class="flex flex-col sm:flex-row gap-4">
                <?php if ($is_logged_in): ?>
                    <a href="user-dashboard.php"
                        class="px-8 py-3.5 bg-brand-500 hover:bg-brand-600 text-white font-semibold rounded-full shadow-lg shadow-brand-500/25 transition-all transform hover:-translate-y-1">
                        Accéder au Feed
                    </a>
                <?php else: ?>
                    <button onclick="toggleModal('login')"
                        class="px-8 py-3.5 bg-brand-500 hover:bg-brand-600 text-white font-semibold rounded-full shadow-lg shadow-brand-500/25 transition-all transform hover:-translate-y-1">
                        Se connecter
                    </button>
                    <button onclick="toggleModal('register')"
                        class="px-8 py-3.5 bg-slate-800 hover:bg-slate-700 text-white font-semibold rounded-full border border-slate-700 transition-all">
                        S'inscrire
                    </button>
                <?php endif; ?>
            </div>

            <!-- Stats -->
            <div class="mt-20 grid grid-cols-2 gap-8 md:grid-cols-4 border-t border-white/10 pt-10 w-full max-w-4xl">
                <div class="flex flex-col">
                    <dt class="order-2 mt-2 text-lg leading-6 font-medium text-slate-400">Services rendus</dt>
                    <dd class="order-1 text-4xl font-extrabold text-white">2.5k+</dd>
                </div>
                <div class="flex flex-col">
                    <dt class="order-2 mt-2 text-lg leading-6 font-medium text-slate-400">Étudiants</dt>
                    <dd class="order-1 text-4xl font-extrabold text-white">500+</dd>
                </div>
                <div class="flex flex-col">
                    <dt class="order-2 mt-2 text-lg leading-6 font-medium text-slate-400">Prix moyen</dt>
                    <dd class="order-1 text-4xl font-extrabold text-white">15€</dd>
                </div>
                <div class="flex flex-col">
                    <dt class="order-2 mt-2 text-lg leading-6 font-medium text-slate-400">Satisfaction</dt>
                    <dd class="order-1 text-4xl font-extrabold text-white">4.9/5</dd>
                </div>
            </div>
        </div>
    </div>

    <!-- Login Modal -->
    <div id="login-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/80 transition-opacity backdrop-blur-sm"
                onclick="toggleModal('login')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div
                class="relative inline-block align-bottom glass rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full p-8 border border-white/10">
                <h3 class="text-2xl font-bold mb-4 text-white">Bon retour parmi nous</h3>
                <form action="login.php" method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1">Email universitaire</label>
                        <input type="email" name="email" required
                            class="w-full px-4 py-2 bg-slate-800/50 border border-slate-700 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent text-white placeholder-slate-500"
                            placeholder="etudiant@campus.edu">
                    </div>
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <label class="block text-sm font-medium text-slate-400">Mot de passe</label>
                            <a href="forgot_password.php" class="text-xs text-brand-400 hover:text-brand-300 hover:underline">Mot de passe oublié ?</a>
                        </div>
                        <input type="password" name="password" required
                            class="w-full px-4 py-2 bg-slate-800/50 border border-slate-700 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent text-white">
                    </div>
                    <button type="submit"
                        class="w-full py-3 bg-brand-500 hover:bg-brand-600 rounded-lg font-semibold text-white transition-colors">Se
                        connecter</button>
                    <p class="text-center text-sm text-slate-400">Pas encore de compte ? <a href="#"
                            onclick="toggleModal('login'); toggleModal('register')"
                            class="text-brand-400 hover:underline">S'inscrire</a></p>
                </form>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div id="register-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title"
        role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/80 transition-opacity backdrop-blur-sm"
                onclick="toggleModal('register')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div
                class="relative inline-block align-bottom glass rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full p-8 border border-white/10">
                <h3 class="text-2xl font-bold mb-4 text-white">Créer un compte</h3>
                <p class="text-sm text-slate-400 mb-4">Votre compte devra être validé par un administrateur avant de
                    pouvoir accéder au réseau.</p>
                <form action="register.php" method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1">Email universitaire</label>
                        <input type="email" name="email" required
                            class="w-full px-4 py-2 bg-slate-800/50 border border-slate-700 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent text-white placeholder-slate-500"
                            placeholder="etudiant@campus.edu">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1">Créer un mot de passe</label>
                        <input type="password" name="password" required
                            class="w-full px-4 py-2 bg-slate-800/50 border border-slate-700 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent text-white">
                    </div>
                    <button type="submit"
                        class="w-full py-3 bg-brand-500 hover:bg-brand-600 rounded-lg font-semibold text-white transition-colors">Demander
                        la création du compte</button>
                    <p class="text-center text-sm text-slate-400">Déjà un compte ? <a href="#"
                            onclick="toggleModal('register'); toggleModal('login')"
                            class="text-brand-400 hover:underline">Se connecter</a></p>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        function toggleModal(modalId) {
            const modal = document.getElementById(modalId + '-modal');
            if (modal) {
                modal.classList.toggle('hidden');
            }
        }

        // Supprimer les toasts après quelques secondes
        setTimeout(() => {
            const toasts = document.querySelectorAll('#toast-container > div');
            toasts.forEach(toast => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.5s ease-out';
                setTimeout(() => toast.remove(), 500);
            });
        }, 5000);
    </script>
</body>

</html>