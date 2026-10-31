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
function opt_num($v, $min, $max, string $label) { return ($v === '' || $v === null) ? null : num($v, $min, $max, $label); }
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
        $h = opt_num($in['height_cm'] ?? '', 50, 260, 'Height (cm)');
        $w = opt_num($in['weight_kg'] ?? '', 20, 400, 'Weight (kg)');
        $a = opt_num($in['age'] ?? '', 10, 100, 'Age');
        $tw = opt_num($in['target_weight_kg'] ?? '', 20, 400, 'Target weight (kg)');
        $sg = (int)num($in['step_goal'] ?? 10000, 1000, 50000, 'Step goal');
        $wg = (int)num($in['water_goal_ml'] ?? 2500, 500, 8000, 'Water goal');
        $goal = in_array($in['primary_goal'] ?? '', GOALS_LIST, true) ? $in['primary_goal'] : 'General Wellness';
        $gender = in_array($in['gender'] ?? '', ['Male', 'Female', 'Other'], true) ? $in['gender'] : null;
        $act = in_array($in['activity_level'] ?? '', ['Sedentary', 'Light', 'Moderate', 'Active', 'Very Active'], true) ? $in['activity_level'] : 'Moderate';
        $bg = in_array($in['blood_group'] ?? '', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'], true) ? $in['blood_group'] : null;
        $city = mb_substr(trim((string)($in['city'] ?? '')), 0, 80) ?: null;
        $bio = mb_substr(trim((string)($in['bio'] ?? '')), 0, 200) ?: null;
        $notes = mb_substr(trim((string)($in['medical_notes'] ?? '')), 0, 300) ?: null;
        $pdo->prepare('UPDATE users SET name=?, height_cm=?, weight_kg=?, age=?, target_weight_kg=?, primary_goal=?, gender=?, activity_level=?, blood_group=?, city=?, bio=?, medical_notes=?, step_goal=?, water_goal_ml=? WHERE id=?')
            ->execute([$name, $h, $w, $a, $tw, $goal, $gender, $act, $bg, $city, $bio, $notes, $sg, $wg, $uid]);
        json_out(['ok' => true, 'message' => 'Profile updated.']);

    case 'change_password':
        $cur = (string)($in['current'] ?? ''); $new = (string)($in['new'] ?? '');
        $st = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?'); $st->execute([$uid]);
        if (!password_verify($cur, (string)$st->fetchColumn())) fail('Your current password is incorrect.');
        if (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) fail('New password needs 8+ characters with letters and numbers.');
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
        json_out(['ok' => true, 'message' => 'Password changed successfully.']);

    /* ---------- Community ---------- */
    case 'like_post':
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

    case 'save_post':
        $pid = (int)($in['id'] ?? 0);
        $st = $pdo->prepare('SELECT 1 FROM post_saves WHERE user_id = ? AND post_id = ?'); $st->execute([$uid, $pid]);
        if ($st->fetchColumn()) { $pdo->prepare('DELETE FROM post_saves WHERE user_id = ? AND post_id = ?')->execute([$uid, $pid]); $saved = false; }
        else { $pdo->prepare('INSERT IGNORE INTO post_saves (user_id, post_id) VALUES (?,?)')->execute([$uid, $pid]); $saved = true; }
        json_out(['ok' => true, 'saved' => $saved]);

    case 'follow_toggle':
        $tid = (int)($in['id'] ?? 0);
        if ($tid === $uid) fail('You cannot follow yourself.');
        $st = $pdo->prepare('SELECT 1 FROM users WHERE id = ?'); $st->execute([$tid]);
        if (!$st->fetchColumn()) fail('Member not found.', 404);
        $st = $pdo->prepare('SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?'); $st->execute([$uid, $tid]);
        if ($st->fetchColumn()) { $pdo->prepare('DELETE FROM follows WHERE follower_id = ? AND following_id = ?')->execute([$uid, $tid]); $following = false; }
        else { $pdo->prepare('INSERT INTO follows (follower_id, following_id) VALUES (?,?)')->execute([$uid, $tid]); $following = true; }
        $st = $pdo->prepare('SELECT COUNT(*) FROM follows WHERE following_id = ?'); $st->execute([$tid]);
        json_out(['ok' => true, 'following' => $following, 'followers' => (int)$st->fetchColumn()]);

    case 'get_comments':
        $st = $pdo->prepare('SELECT c.id, c.user_id, c.content, c.created_at, u.name FROM comments c JOIN users u ON u.id = c.user_id WHERE c.post_id = ? ORDER BY c.created_at ASC LIMIT 100');
        $st->execute([(int)($in['post_id'] ?? 0)]);
        $rows = array_map(function ($r) { $r['ago'] = time_ago($r['created_at']); unset($r['created_at']); return $r; }, $st->fetchAll());
        json_out(['ok' => true, 'comments' => $rows]);

    case 'add_comment':
        $pid = (int)($in['post_id'] ?? 0); $text = trim((string)($in['content'] ?? ''));
        if (mb_strlen($text) < 1 || mb_strlen($text) > 300) fail('Comments must be 1–300 characters.');
        $st = $pdo->prepare('SELECT 1 FROM posts WHERE id = ?'); $st->execute([$pid]);
        if (!$st->fetchColumn()) fail('Post not found.', 404);
        $pdo->prepare('INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?,?,?,?)')->execute([$pid, $uid, $text, $now]);
        $cid = (int)$pdo->lastInsertId();
        $st = $pdo->prepare('SELECT COUNT(*) FROM comments WHERE post_id = ?'); $st->execute([$pid]);
        json_out(['ok' => true, 'count' => (int)$st->fetchColumn(), 'comment' => ['id' => $cid, 'user_id' => $uid, 'name' => $u['name'], 'content' => $text, 'ago' => 'Just now']]);

    case 'delete_post':
        $pid = (int)($in['id'] ?? 0);
        $st = $pdo->prepare('SELECT image_path FROM posts WHERE id = ? AND user_id = ?'); $st->execute([$pid, $uid]);
        $row = $st->fetch();
        if (!$row) fail('You can only delete your own posts.', 403);
        $pdo->prepare('DELETE FROM posts WHERE id = ? AND user_id = ?')->execute([$pid, $uid]);
        if ($row['image_path'] && strpos($row['image_path'], 'uploads/') === 0) @unlink(__DIR__ . '/' . basename(dirname($row['image_path'])) . '/' . basename($row['image_path']));
        json_out(['ok' => true]);

    /* ---------- Doctors ---------- */
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
        $free = consult_is_free($u, $type, $date);   // plan perks are applied silently
        $code = 'HLX-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        $pdo->prepare('INSERT INTO appointments (user_id, doctor_id, date, time_slot, type, status, meeting_code) VALUES (?,?,?,?,?,?,?)')->execute([$uid, $did, $date, $slot, $type, 'upcoming', $code]);
        $fee = $free ? 'Free' : inr((int)$doc['fee_inr']) . ' (demo)';
        json_out(['ok' => true, 'message' => 'Booked with ' . $doc['name'] . ' · ' . date('D, d M', strtotime($date)) . ' ' . $slot . ' · Fee ' . $fee . ' · Code ' . $code]);

    case 'complete_appointment':
        $pdo->prepare("UPDATE appointments SET status = 'completed' WHERE id = ? AND user_id = ?")->execute([(int)($in['id'] ?? 0), $uid]);
        json_out(['ok' => true]);

    /* ---------- Subscription (shown only on the Profile page) ---------- */
    case 'upgrade':
        $tier = (string)($in['tier'] ?? ''); $cycle = (int)($in['cycle'] ?? 0); $method = (string)($in['method'] ?? '');
        if (!isset(PLANS[$tier]) || !isset(PLANS[$tier]['prices'][$cycle])) fail('Unknown plan selected.');
        if ($method === 'upi' && !preg_match('/^[\w.\-]{2,}@[A-Za-z]{2,}$/', (string)($in['upi'] ?? ''))) fail('Enter a valid UPI ID (e.g. name@okaxis).');
        if (!in_array($method, ['upi', 'card'], true)) fail('Choose a payment method.');
        $amount = PLANS[$tier]['prices'][$cycle];
        $base = ($u['tier_id'] === $tier && $u['sub_expiry'] && $u['sub_expiry'] >= $today) ? $u['sub_expiry'] : $today;
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
