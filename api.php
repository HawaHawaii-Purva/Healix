<?php
/* Healix JSON API – every dashboard/AJAX action goes through here (PDO prepared statements only). */
require_once __DIR__ . '/config.php';
if (empty($_SESSION['uid'])) json_out(['ok' => false, 'error' => 'Session expired. Please sign in again.'], 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) json_out(['ok' => false, 'error' => 'Invalid request token. Refresh the page.'], 403);
$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) json_out(['ok' => false, 'error' => 'Bad request.'], 400);
$u = current_user();
if (!$u) json_out(['ok' => false, 'error' => 'Session expired.'], 401);
$uid = (int)$u['id']; $pdo = db(); $now = date('Y-m-d H:i:s'); $today = date('Y-m-d');

function fail(string $m, int $c = 422): void { json_out(['ok' => false, 'error' => $m], $c); }
function num($v, $min, $max, string $label) { if (!is_numeric($v) || $v < $min || $v > $max) fail("$label must be between $min and $max."); return $v + 0; }
function stats_payload(int $uid): array { $s = refresh_stats($uid); return ['stats' => $s, 'score' => (int)$s['health_score']]; }

try {
    switch ($in['action'] ?? '') {

    case 'add_water':
        $ml = (int)num($in['ml'] ?? 0, 50, 1500, 'Water amount');
        $s = today_stats($uid);
        if ($s['water_ml'] + $ml > 10000) fail('That is above a safe daily intake. Please check the amount.');
        $pdo->prepare('UPDATE user_stats SET water_ml = water_ml + ? WHERE id = ?')->execute([$ml, $s['id']]);
        json_out(['ok' => true] + stats_payload($uid));

    case 'add_steps':
        $n = (int)num($in['steps'] ?? 0, 1, 30000, 'Steps');
        $s = today_stats($uid);
        if ($s['steps'] + $n > 100000) fail('Daily step total looks too high.');
        $pdo->prepare('UPDATE user_stats SET steps = steps + ? WHERE id = ?')->execute([$n, $s['id']]);
        json_out(['ok' => true] + stats_payload($uid));

    case 'save_workout':
        $type = trim((string)($in['workout_type'] ?? ''));
        if ($type === '' || mb_strlen($type) > 80) fail('Invalid workout name.');
        $dur = (int)num($in['duration_mins'] ?? 0, 1, 600, 'Duration');
        $cal = (int)num($in['calories'] ?? 0, 0, 5000, 'Calories');
        $int = in_array($in['intensity'] ?? '', ['Low', 'Moderate', 'High'], true) ? $in['intensity'] : 'Moderate';
        $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO workouts (user_id, workout_type, duration_mins, calories, intensity, created_at) VALUES (?,?,?,?,?,?)')->execute([$uid, $type, $dur, $cal, $int, $now]);
        $s = today_stats($uid);
        $pdo->prepare('UPDATE user_stats SET active_minutes = active_minutes + ?, calories_burned = calories_burned + ? WHERE id = ?')->execute([$dur, $cal, $s['id']]);
        $pdo->commit();
        json_out(['ok' => true, 'message' => "Workout saved: $type · $dur min · $cal kcal"] + stats_payload($uid));

    case 'log_meditation':
        $title = trim((string)($in['title'] ?? 'Mindful Breathing')); $track = trim((string)($in['track'] ?? 'None'));
        if ($title === '' || mb_strlen($title) > 100 || mb_strlen($track) > 60) fail('Invalid session details.');
        $dur = (int)num($in['duration_mins'] ?? 0, 1, 240, 'Duration');
        $pdo->prepare('INSERT INTO meditation_logs (user_id, session_title, sound_track, duration_mins, created_at) VALUES (?,?,?,?,?)')->execute([$uid, $title, $track, $dur, $now]);
        json_out(['ok' => true, 'streak' => mindful_streak($uid), 'entry' => ['title' => $title, 'track' => $track, 'mins' => $dur]]);

    case 'update_profile':
        $name = trim((string)($in['name'] ?? ''));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) fail('Please enter a valid name.');
        $h = (int)num($in['height_cm'] ?? '', 50, 260, 'Height (cm)');
        $w = round((float)num($in['weight_kg'] ?? '', 20, 400, 'Weight (kg)'), 1);
        $a = (int)num($in['age'] ?? '', 10, 100, 'Age');
        $tw = round((float)num($in['target_weight_kg'] ?? '', 20, 400, 'Target weight (kg)'), 1);
        $goal = in_array($in['primary_goal'] ?? '', GOALS_LIST, true) ? $in['primary_goal'] : 'General Wellness';
        $pdo->prepare('UPDATE users SET name=?, height_cm=?, weight_kg=?, age=?, target_weight_kg=?, primary_goal=? WHERE id=?')->execute([$name, $h, $w, $a, $tw, $goal, $uid]);
        json_out(['ok' => true, 'message' => 'Profile updated.']);

    case 'create_post':
        if (tier_rank($u['tier_id']) < 1) fail('Posting is available on Silver and above. Upgrade to join in!', 403);
        $text = trim((string)($in['content'] ?? ''));
        if (mb_strlen($text) < 3 || mb_strlen($text) > 500) fail('Posts must be 3–500 characters.');
        $type = in_array($in['post_type'] ?? '', ['Workout', 'MentalHealth', 'Nutrition'], true) ? $in['post_type'] : 'Workout';
        $pdo->prepare('INSERT INTO posts (user_id, content, post_type, created_at) VALUES (?,?,?,?)')->execute([$uid, $text, $type, $now]);
        json_out(['ok' => true, 'post' => ['id' => (int)$pdo->lastInsertId(), 'content' => $text, 'post_type' => $type, 'likes_count' => 0, 'liked' => 0, 'name' => $u['name'], 'tier_id' => $u['tier_id'], 'ago' => 'Just now']]);

    case 'like_post':
        if (tier_rank($u['tier_id']) < 1) fail('Liking is available on Silver and above.', 403);
        $pid = (int)($in['id'] ?? 0);
        $st = $pdo->prepare('SELECT id FROM posts WHERE id = ?'); $st->execute([$pid]);
        if (!$st->fetch()) fail('Post not found.', 404);
        $pdo->beginTransaction();
        $st = $pdo->prepare('SELECT 1 FROM post_likes WHERE user_id = ? AND post_id = ?'); $st->execute([$uid, $pid]);
        if ($st->fetchColumn()) {
            $pdo->prepare('DELETE FROM post_likes WHERE user_id = ? AND post_id = ?')->execute([$uid, $pid]);
            $pdo->prepare('UPDATE posts SET likes_count = GREATEST(likes_count - 1, 0) WHERE id = ?')->execute([$pid]); $liked = false;
        } else {
            $pdo->prepare('INSERT INTO post_likes (user_id, post_id) VALUES (?,?)')->execute([$uid, $pid]);
            $pdo->prepare('UPDATE posts SET likes_count = likes_count + 1 WHERE id = ?')->execute([$pid]); $liked = true;
        }
        $st = $pdo->prepare('SELECT likes_count FROM posts WHERE id = ?'); $st->execute([$pid]); $cnt = (int)$st->fetchColumn();
        $pdo->commit();
        json_out(['ok' => true, 'liked' => $liked, 'likes' => $cnt]);

    case 'book_appointment':
        $did = (int)($in['doctor_id'] ?? 0); $date = (string)($in['date'] ?? ''); $slot = (string)($in['time_slot'] ?? ''); $type = (string)($in['type'] ?? '');
        $st = $pdo->prepare('SELECT * FROM doctors WHERE id = ?'); $st->execute([$did]); $doc = $st->fetch();
        if (!$doc) fail('Doctor not found.', 404);
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if (!$d || $d->format('Y-m-d') !== $date) fail('Please pick a valid date.');
        if ($date < $today || $date > date('Y-m-d', strtotime('+60 days'))) fail('Please choose a date within the next 60 days.');
        if (!in_array($d->format('D'), array_map('trim', explode(',', $doc['available_days'])), true)) fail($doc['name'] . ' is not available on that day.');
        if (!in_array($slot, SLOTS, true)) fail('Please choose a time slot.');
        if (!in_array($type, ['video', 'chat'], true)) fail('Choose video call or chat.');
        $st = $pdo->prepare('SELECT 1 FROM appointments WHERE doctor_id = ? AND date = ? AND time_slot = ?'); $st->execute([$did, $date, $slot]);
        if ($st->fetchColumn()) fail('That slot was just taken. Please pick another one.', 409);
        $free = consult_is_free($u, $type, $date);
        $code = 'HLX-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        $pdo->prepare('INSERT INTO appointments (user_id, doctor_id, date, time_slot, type, status, meeting_code) VALUES (?,?,?,?,?,?,?)')->execute([$uid, $did, $date, $slot, $type, 'upcoming', $code]);
        $fee = $free ? 'included in your ' . $u['tier_id'] . ' plan' : inr((int)$doc['fee_inr']) . ' (demo charge)';
        json_out(['ok' => true, 'message' => 'Booked with ' . $doc['name'] . ' · ' . date('D, d M', strtotime($date)) . ' ' . $slot . ' · Fee ' . $fee . ' · Code ' . $code]);

    case 'complete_appointment':
        $pdo->prepare("UPDATE appointments SET status = 'completed' WHERE id = ? AND user_id = ?")->execute([(int)($in['id'] ?? 0), $uid]);
        json_out(['ok' => true]);

    case 'upgrade':
        $tier = (string)($in['tier'] ?? ''); $cycle = (int)($in['cycle'] ?? 0); $method = (string)($in['method'] ?? '');
        if (!isset(PLANS[$tier]) || !isset(PLANS[$tier]['prices'][$cycle])) fail('Unknown plan selected.');
        if ($method === 'upi' && !preg_match('/^[\w.\-]{2,}@[A-Za-z]{2,}$/', (string)($in['upi'] ?? ''))) fail('Enter a valid UPI ID (e.g. name@okaxis).');
        if (!in_array($method, ['upi', 'card'], true)) fail('Choose a payment method.');
        $amount = PLANS[$tier]['prices'][$cycle];
        $base = ($u['tier_id'] === $tier && $u['sub_expiry'] && $u['sub_expiry'] >= $today) ? $u['sub_expiry'] : $today; // renewing the same plan extends it
        $end = date('Y-m-d', strtotime("$base +$cycle months"));
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE subscriptions SET status = 'replaced' WHERE user_id = ? AND status = 'active'")->execute([$uid]);
        $pdo->prepare("INSERT INTO subscriptions (user_id, plan_tier, billing_cycle, amount_inr, status, end_date) VALUES (?,?,?,?, 'active', ?)")->execute([$uid, $tier, $cycle . ' Months', $amount, $end]);
        $pdo->prepare('UPDATE users SET tier_id = ?, sub_expiry = ? WHERE id = ?')->execute([$tier, $end, $uid]);
        $pdo->commit();
        json_out(['ok' => true, 'message' => "Payment confirmed! You're now a $tier member until " . date('d M Y', strtotime($end)) . '.']);

    default:
        fail('Unknown action.', 400);
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Healix API error: ' . $e->getMessage());
    json_out(['ok' => false, 'error' => 'Server error. Please try again.'], 500);
}
