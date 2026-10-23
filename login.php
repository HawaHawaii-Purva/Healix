<?php
require_once __DIR__ . '/config.php';
if (!empty($_SESSION['uid'])) { header('Location: index.php'); exit; }
$pageTitle = 'Sign In'; $tab = 'login'; $error = ''; $old = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['email'] = trim($_POST['email'] ?? '');
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $error = 'Session expired. Please try again.';
    } elseif (($_SESSION['lock_until'] ?? 0) > time()) {
        $error = 'Too many attempts. Please wait a minute and try again.';
    } else {
        $st = db()->prepare('SELECT id, password_hash FROM users WHERE email = ?');
        $st->execute([strtolower($old['email'])]);
        $row = $st->fetch();
        if ($row && password_verify($_POST['password'] ?? '', $row['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['uid'] = (int)$row['id'];
            unset($_SESSION['fails'], $_SESSION['lock_until']);
            header('Location: index.php'); exit;
        }
        $_SESSION['fails'] = ($_SESSION['fails'] ?? 0) + 1;
        if ($_SESSION['fails'] >= 5) { $_SESSION['lock_until'] = time() + 60; $_SESSION['fails'] = 0; }
        $error = 'Incorrect email or password.';
    }
}
include __DIR__ . '/head.php';
include __DIR__ . '/auth_card.php';
