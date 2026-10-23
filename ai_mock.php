<?php
/* Healix AI – simulated, rule-based assistant. Personalised with today's stats, profile and subscription tier. */
require_once __DIR__ . '/config.php';
if (empty($_SESSION['uid'])) json_out(['ok' => false, 'error' => 'Please sign in.'], 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) json_out(['ok' => false, 'error' => 'Invalid request token.'], 403);
$in = json_decode(file_get_contents('php://input'), true) ?: [];
$prompt = trim((string)($in['prompt'] ?? ''));
if ($prompt === '' || mb_strlen($prompt) > 500) json_out(['ok' => false, 'error' => 'Please type a question (max 500 characters).'], 422);
$u = current_user(); if (!$u) json_out(['ok' => false, 'error' => 'Session expired.'], 401);

$rank = tier_rank($u['tier_id']); $tier = $u['tier_id']; $full = $rank >= 2;
$cap = [5, 5, 40, PHP_INT_MAX][$rank];
if (($_SESSION['ai_day'] ?? '') !== date('Y-m-d')) { $_SESSION['ai_day'] = date('Y-m-d'); $_SESSION['ai_n'] = 0; }
if ($_SESSION['ai_n'] >= $cap) {
    json_out(['ok' => true, 'reply' => "You've used today's **$cap** coach messages on the $tier plan.\n- Gold: AI Health & Workout Coach with full personalised plans\n- Platinum: unlimited AI coaching + advanced bio-analytics\nTap **Profile → Upgrade** to unlock more."]);
}
$_SESSION['ai_n']++;

$p = mb_strtolower($prompt);
$first = explode(' ', trim($u['name']))[0];
$s = today_stats((int)$u['id']); $score = calc_score($s);
$kg = $u['weight_kg'] ? (float)$u['weight_kg'] : null;
$has = function (array $w) use ($p) { foreach ($w as $x) if (strpos($p, $x) !== false) return true; return false; };
$upsell = $full ? '' : "\nUpgrade to **Gold** for fully personalised plans.";

function workout_plan(int $mins, string $kind, ?float $kg, bool $full): string {
    $lib = [
        'low' => ['Marching in place', 'Standing knee-to-elbow', 'Wall push-ups', 'Chair squats', 'Standing side leg raises', 'Seated torso twists', 'Glute bridges'],
        'hiit' => ['Jumping jacks', 'High knees', 'Squat jumps', 'Mountain climbers', 'Step-back burpees', 'Skater hops'],
        'strength' => ['Bodyweight squats', 'Push-ups (knees ok)', 'Reverse lunges', 'Plank hold', 'Glute bridges', 'Superman hold'],
        'yoga' => ['Cat-cow', 'Downward dog', 'Warrior II', 'Low lunge', 'Seated forward fold', "Child's pose"],
        'cardio' => ['Brisk walking in place', 'Step-touch', 'Boxer shuffle', 'Fast feet', 'Grapevine steps', 'Shadow boxing'],
    ];
    $rate = ['low' => 4, 'hiit' => 10, 'strength' => 6, 'yoga' => 3.5, 'cardio' => 7][$kind] * ($kg ? $kg / 70 : 1);
    $main = max(1, $mins - 4); $moves = array_slice($lib[$kind], 0, min(count($lib[$kind]), $main));
    $per = round($main / count($moves), 1);
    $label = ['low' => 'Low-impact', 'hiit' => 'HIIT', 'strength' => 'Strength', 'yoga' => 'Yoga & mobility', 'cardio' => 'Cardio'][$kind];
    $out = "**$mins-minute $label workout** (≈ " . round($rate * $mins) . " kcal)\n";
    if (!$full) return $out . '- Do ' . implode(', ', array_slice($moves, 0, 3)) . " for a few minutes each, with easy breathing.\n";
    $out .= "**Warm-up (2 min)**\n- Shoulder rolls, neck circles, hip circles\n**Main set ($main min, ~$per min each)**\n";
    foreach ($moves as $m) $out .= "- $m\n";
    return $out . "**Cool-down (2 min)**\n- Slow breathing + hamstring and chest stretch\nStay hydrated and stop if anything feels sharp or dizzy.";
}

if ($has(['back pain', 'lower back', 'warm-up stretch', 'warm up stretch', 'stretch'])) {
    $r = "**Gentle lower-back warm-up (≈ 5 min)**\n- Pelvic tilts — 10 slow reps\n- Cat-cow — 8 slow rounds\n- Knee-to-chest — 30 sec each side\n- Glute bridge — 2 × 10\n- Child's pose — 45 sec\nMove within comfortable range. Stop if you feel sharp pain, numbness or pain travelling down a leg, and consider a **Physiotherapist** on the Doctors page.";
} elseif ($has(['protein'])) {
    if ($kg) {
        $lo = round($kg * 0.3); $hi = round($kg * 0.4); $dl = round($kg * 1.2); $dh = round($kg * 1.8);
        $r = "At **" . round($kg) . " kg**, aim for roughly **$lo–$hi g of protein** within 1–2 hours after training, and about **$dl–$dh g across the day**.\n- Vegetarian: paneer, dal + rice, curd, sprouts, soy chunks\n- Others: eggs, chicken, fish, whey if needed\nIf you have kidney or liver conditions, check targets with a doctor first.";
    } else {
        $r = "A good rule is **20–40 g of protein** after a workout and **1.2–1.8 g per kg body weight** per day.\n- Try paneer, dal, curd, eggs, sprouts or whey\nAdd your weight in **Profile** and I'll calculate it for you.";
    }
} elseif ($has(['workout', 'exercise', 'routine', 'plan me', 'hiit', 'yoga', 'cardio', 'strength', 'min'])) {
    preg_match('/(\d{1,2})\s*-?\s*(?:min|minute)/', $p, $m); $mins = isset($m[1]) ? max(5, min(60, (int)$m[1])) : 15;
    $kind = $has(['low-impact', 'low impact', 'gentle', 'beginner', 'knee', 'joint']) ? 'low' : ($has(['hiit', 'interval', 'intense']) ? 'hiit' : ($has(['strength', 'muscle', 'weights']) ? 'strength' : ($has(['yoga', 'flex', 'stretch']) ? 'yoga' : ($has(['cardio', 'stamina']) ? 'cardio' : 'low'))));
    $r = workout_plan($mins, $kind, $kg, $full) . $upsell;
} elseif ($has(['water', 'hydrat', 'thirst'])) {
    $left = max(0, WATER_GOAL - (int)$s['water_ml']);
    $r = $left > 0 ? "You've logged **" . $s['water_ml'] . " ml** today — **$left ml** to reach " . WATER_GOAL . " ml.\n- Sip a glass (250 ml) every 1–2 hours\n- Tap the **+250 ml** button on your dashboard after each glass" : "Great job, $first! You've hit your **" . WATER_GOAL . " ml** water goal today 💧";
} elseif ($has(['step', 'walk'])) {
    $left = max(0, STEP_GOAL - (int)$s['steps']);
    $r = $left > 0 ? "You're at **" . number_format($s['steps']) . " steps**. About **" . number_format($left) . "** to go (≈ " . round($left / 100) . " min of brisk walking).\n- Take a 10-minute walk after your next meal\n- Use stairs where you can" : "You've crushed your step goal today — **" . number_format($s['steps']) . " steps** 🎉";
} elseif ($has(['score', 'health'])) {
    $r = "Your health score today is **$score / 100**.\n- Water: " . round(min(1, $s['water_ml'] / WATER_GOAL) * 30) . " / 30\n- Steps: " . round(min(1, $s['steps'] / STEP_GOAL) * 40) . " / 40\n- Activity: " . round(min(1, $s['active_minutes'] / ACTIVE_GOAL) * 30) . " / 30";
    if ($rank >= 3 && $u['height_cm'] && $kg) { $bmi = round($kg / pow($u['height_cm'] / 100, 2), 1); $r .= "\n**Bio-analytics (Platinum):** BMI **$bmi**" . ($u['target_weight_kg'] ? ", target weight **" . $u['target_weight_kg'] . " kg**" : '') . "."; }
} elseif ($has(['stress', 'anx', 'calm', 'relax', 'sleep', 'meditat', 'breath'])) {
    $r = "Try the **4-7-8 breath** (about 2 minutes):\n- Inhale through the nose for 4\n- Hold for 7\n- Exhale slowly for 8\n- Repeat 4 cycles\nOpen the **Meditate** page for the guided visual circle and rain or ocean sounds. If low mood or anxiety persists, a Mental Health Therapist is just a booking away.";
} elseif ($has(['plan', 'tier', 'upgrade', 'subscription', 'silver', 'gold', 'platinum', 'include'])) {
    $r = "You're on the **$tier** plan" . ($u['sub_expiry'] ? " (valid till " . date('d M Y', strtotime($u['sub_expiry'])) . ")" : '') . ".\n- Silver: workout tracker, meditation library, community\n- Gold: AI coach, full soundscapes, 1 free doctor chat/month\n- Platinum: unlimited AI, 2 free video consults/month, bio-analytics";
} elseif ($has(['diet', 'eat', 'food', 'meal', 'calorie', 'weight', 'lose'])) {
    $r = "For your goal (**" . $u['primary_goal'] . "**), build each plate with:\n- ½ vegetables or salad\n- ¼ protein (dal, paneer, eggs, fish)\n- ¼ whole grains (roti, millets, brown rice)\nEat slowly, keep sugary drinks occasional, and sleep 7–8 hours." . ($full ? "\nFor a personalised diet chart, book a **Nutritionist** consult." : $upsell);
} elseif ($has(['hello', 'hi ', 'hey', 'namaste']) || $p === 'hi') {
    $r = "Hello $first! 🌿 Today you're at **$score / 100** with " . number_format($s['steps']) . " steps and " . $s['water_ml'] . " ml of water. What would you like to work on?";
} else {
    $r = "Here's your snapshot, $first:\n- Health score: **$score / 100**\n- Steps: " . number_format($s['steps']) . " / " . number_format(STEP_GOAL) . "\n- Water: " . $s['water_ml'] . " / " . WATER_GOAL . " ml\nTry asking for a **15-minute low-impact workout**, a **post-workout protein target**, or **stress relief tips**.";
}
json_out(['ok' => true, 'reply' => $r]);
