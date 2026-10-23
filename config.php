<?php
/**
 * Healix – core configuration & helpers (PHP 7.4+).
 * 1) Fill in DB_* from your InfinityFree control panel (MySQL Databases).
 */
if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
date_default_timezone_set('Asia/Kolkata');

const DB_HOST = 'sqlXXX.infinityfree.com';
const DB_NAME = 'if0_XXXXXXXX_healix';
const DB_USER = 'if0_XXXXXXXX';
const DB_PASS = 'YOUR_DB_PASSWORD';

const STEP_GOAL = 10000;
const WATER_GOAL = 2500;
const ACTIVE_GOAL = 30;
const GOALS_LIST = ['General Wellness', 'Lose Weight', 'Build Muscle', 'Improve Stamina', 'Reduce Stress', 'Better Sleep', 'Eat Healthier'];
const SLOTS = ['09:00 AM', '10:30 AM', '12:00 PM', '03:00 PM', '05:00 PM', '07:00 PM'];
const TIERS = ['Free' => 0, 'Silver' => 1, 'Gold' => 2, 'Platinum' => 3];
const PLANS = [
    'Silver' => ['icon' => '🥉', 'prices' => [3 => 499, 6 => 899, 12 => 1499],
        'features' => ['Basic Workout Tracker', 'Standard Meditation Library', 'Community Feed access']],
    'Gold' => ['icon' => '🥈', 'prices' => [3 => 999, 6 => 1799, 12 => 2999],
        'features' => ['Everything in Silver', 'AI Health & Workout Coach', 'Full Audio Soundscapes', '1 Free Doctor Chat / month']],
    'Platinum' => ['icon' => '🥇', 'prices' => [3 => 1999, 6 => 3499, 12 => 5999],
        'features' => ['Everything in Gold', 'Unlimited AI Coach', 'Priority Video Call Consultations', '2 Free Doctor Video Consultations / month', 'Advanced Bio-analytics']],
];

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $pdo->exec("SET NAMES utf8mb4");
        } catch (PDOException $e) {
            error_log('Healix DB error: ' . $e->getMessage());
            http_response_code(500);
            die('Healix could not connect to the database. Please check config.php.');
        }
    }
    return $pdo;
}

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_check($t): bool { return is_string($t) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t); }
function json_out(array $d, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function tier_rank(string $t): int { return TIERS[$t] ?? 0; }
function inr(int $n): string { return '₹' . number_format($n); }

function current_user(): ?array {
    if (empty($_SESSION['uid'])) return null;
    $st = db()->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([$_SESSION['uid']]);
    $u = $st->fetch();
    if (!$u) { unset($_SESSION['uid']); return null; }
    if ($u['tier_id'] !== 'Free' && $u['sub_expiry'] && $u['sub_expiry'] < date('Y-m-d')) {
        db()->prepare("UPDATE users SET tier_id = 'Free', sub_expiry = NULL WHERE id = ?")->execute([$u['id']]);
        $u['tier_id'] = 'Free'; $u['sub_expiry'] = null;
    }
    return $u;
}
function require_login(): array {
    $u = current_user();
    if (!$u) { header('Location: login.php'); exit; }
    return $u;
}

function today_stats(int $uid): array {
    $pdo = db(); $d = date('Y-m-d');
    $pdo->prepare('INSERT IGNORE INTO user_stats (user_id, date) VALUES (?, ?)')->execute([$uid, $d]);
    $st = $pdo->prepare('SELECT * FROM user_stats WHERE user_id = ? AND date = ?');
    $st->execute([$uid, $d]);
    return $st->fetch();
}
function calc_score(array $s): int {
    $w = min(1, $s['water_ml'] / WATER_GOAL) * 30;
    $p = min(1, $s['steps'] / STEP_GOAL) * 40;
    $a = min(1, $s['active_minutes'] / ACTIVE_GOAL) * 30;
    return (int)round($w + $p + $a);
}
function refresh_stats(int $uid): array {
    $s = today_stats($uid);
    $score = calc_score($s);
    if ((int)$s['health_score'] !== $score) {
        db()->prepare('UPDATE user_stats SET health_score = ? WHERE id = ?')->execute([$score, $s['id']]);
        $s['health_score'] = $score;
    }
    return $s;
}
function mindful_streak(int $uid): int {
    $st = db()->prepare('SELECT DISTINCT DATE(created_at) AS d FROM meditation_logs WHERE user_id = ? ORDER BY d DESC LIMIT 400');
    $st->execute([$uid]);
    $days = array_column($st->fetchAll(), 'd');
    if (!$days) return 0;
    $cur = date('Y-m-d');
    if ($days[0] !== $cur) {
        $cur = date('Y-m-d', strtotime('-1 day'));
        if ($days[0] !== $cur) return 0;
    }
    $n = 0;
    foreach ($days as $d) {
        if ($d === $cur) { $n++; $cur = date('Y-m-d', strtotime($cur . ' -1 day')); } else break;
    }
    return $n;
}
function time_ago(string $ts): string {
    $d = max(0, time() - strtotime($ts));
    if ($d < 60) return 'Just now';
    if ($d < 3600) return floor($d / 60) . 'm ago';
    if ($d < 86400) return floor($d / 3600) . 'h ago';
    if ($d < 604800) return floor($d / 86400) . 'd ago';
    return date('d M Y', strtotime($ts));
}
/** Is a consultation of $type on $date covered by the user's plan perks? (Gold/Platinum: 1 chat/mo; Platinum: 2 video/mo) */
function consult_is_free(array $u, string $type, string $date): bool {
    $rank = tier_rank($u['tier_id']);
    $limit = ($type === 'chat' && $rank >= 2) ? 1 : (($type === 'video' && $rank >= 3) ? 2 : 0);
    if (!$limit) return false;
    $st = db()->prepare("SELECT COUNT(*) FROM appointments WHERE user_id = ? AND type = ? AND DATE_FORMAT(date, '%Y-%m') = ?");
    $st->execute([$u['id'], $type, substr($date, 0, 7)]);
    return (int)$st->fetchColumn() < $limit;
}
function next_available(string $days): array {
    $allowed = array_map('trim', explode(',', $days));
    for ($i = 0; $i < 14; $i++) {
        if ($i === 0 && (int)date('H') >= 17) continue;
        $t = strtotime("+$i day");
        if (in_array(date('D', $t), $allowed, true)) {
            return ['date' => date('Y-m-d', $t), 'label' => $i === 0 ? 'Today' : ($i === 1 ? 'Tomorrow' : date('D, d M', $t))];
        }
    }
    return ['date' => date('Y-m-d'), 'label' => 'Soon'];
}
