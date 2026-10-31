<?php
require_once __DIR__ . '/config.php';
if (!empty($_SESSION['uid'])) { header('Location: index.php'); exit; }
$pageTitle = 'Create Account'; $tab = 'register'; $error = ''; $old = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass = $_POST['password'] ?? '';
    $goal = in_array($_POST['primary_goal'] ?? '', GOALS_LIST, true) ? $_POST['primary_goal'] : 'General Wellness';
    $old = ['name' => $name, 'email' => $email, 'primary_goal' => $goal];
    if (!csrf_check($_POST['csrf'] ?? '')) $error = 'Session expired. Please try again.';
    elseif (mb_strlen($name) < 2 || mb_strlen($name) > 100) $error = 'Please enter your name (2–100 characters).';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) $error = 'Please enter a valid email address.';
    elseif (strlen($pass) < 8 || !preg_match('/[A-Za-z]/', $pass) || !preg_match('/\d/', $pass)) $error = 'Password needs 8+ characters with letters and numbers.';
    elseif ($pass !== ($_POST['confirm'] ?? '')) $error = 'Passwords do not match.';
    else {
        $pdo = db();
        $st = $pdo->prepare('SELECT 1 FROM users WHERE email = ?'); $st->execute([$email]);
        if ($st->fetchColumn()) {
            $error = 'An account with this email already exists.';
        } else {
            $pdo->prepare('INSERT INTO users (name, email, password_hash, primary_goal) VALUES (?, ?, ?, ?)')
                ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $goal]);
            session_regenerate_id(true);
            $_SESSION['uid'] = (int)$pdo->lastInsertId();
            header('Location: index.php'); exit;
        }
    }
}
include __DIR__ . '/head.php';
include __DIR__ . '/auth_card.php';
