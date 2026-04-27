<?php
require_once 'config.php';

echo "Initialisation de la base de données...<br>";

// Table Users
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    firstname TEXT,
    lastname TEXT,
    role TEXT DEFAULT 'user', -- 'user' or 'admin'
    status TEXT DEFAULT 'pending', -- 'pending', 'active', 'frozen'
    is_email_verified INTEGER DEFAULT 0,
    verification_code TEXT,
    reset_token TEXT,
    reset_expires_at DATETIME,
    frozen_until DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Table Posts
$pdo->exec("CREATE TABLE IF NOT EXISTS posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    type TEXT NOT NULL, -- 'offer' or 'demand'
    title TEXT NOT NULL,
    description TEXT NOT NULL,
    category TEXT NOT NULL,
    price REAL,
    price_unit TEXT, -- 'Total', 'Heure', 'Jour', 'Séance', 'Gratuit'
    status TEXT DEFAULT 'active', -- 'active', 'deleted', 'reported'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id)
)");

// Table Favorites
$pdo->exec("CREATE TABLE IF NOT EXISTS favorites (
    user_id INTEGER,
    post_id INTEGER,
    PRIMARY KEY(user_id, post_id),
    FOREIGN KEY(user_id) REFERENCES users(id),
    FOREIGN KEY(post_id) REFERENCES posts(id)
)");

// Table Blocked Words
$pdo->exec("CREATE TABLE IF NOT EXISTS blocked_words (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    word TEXT UNIQUE NOT NULL
)");

// Table Notifications
$pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    message TEXT NOT NULL,
    is_read INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id)
)");

// Insert default block words if empty
$stmt = $pdo->query("SELECT COUNT(*) FROM blocked_words");
if ($stmt->fetchColumn() == 0) {
    $default_words = ['merde', 'putain', 'connard', 'salope', 'sexe', 'porno', 'drogue'];
    $insert_stmt = $pdo->prepare("INSERT INTO blocked_words (word) VALUES (?)");
    foreach ($default_words as $word) {
        $insert_stmt->execute([$word]);
    }
}

// Insert an admin user if it doesn't exist
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE email = 'charlesbellot181@gmail.com'");
if ($stmt->fetchColumn() == 0) {
    $password_hash = password_hash('7591.7591', PASSWORD_DEFAULT);
    $pdo->exec("INSERT INTO users (email, password, firstname, lastname, role, status, is_email_verified) VALUES ('charlesbellot181@gmail.com', '$password_hash', 'Charles', 'Admin', 'admin', 'active', 1)");
}

echo "Base de données initialisée avec succès ! L'utilisateur admin (charlesbellot181@gmail.com) a été créé.";
