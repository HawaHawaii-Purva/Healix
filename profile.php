<?php
$pageTitle = 'Profile'; $active = 'profile';
require __DIR__ . '/header.php';
$uid = (int)$user['id']; $pdo = db(); $rank = tier_rank($tier);
$one = function (string $sql, array $p = []) use ($pdo) { $st = $pdo->prepare($sql); $st->execute($p); return $st->fetch(PDO::FETCH_NUM) ?: []; };
[$wCount, $wMins, $wCal] = $one('SELECT COUNT(*), COALESCE(SUM(duration_mins),0), COALESCE(SUM(calories),0) FROM workouts WHERE user_id = ?', [$uid]);
[$mCount, $mMins] = $one('SELECT COUNT(*), COALESCE(SUM(duration_mins),0) FROM meditation_logs WHERE user_id = ?', [$uid]);
[$postCount] = $one('SELECT COUNT(*) FROM posts WHERE user_id = ?', [$uid]);
[$followers] = $one('SELECT COUNT(*) FROM follows WHERE following_id = ?', [$uid]);
[$following] = $one('SELECT COUNT(*) FROM follows WHERE follower_id = ?', [$uid]);
[$apptCount] = $one('SELECT COUNT(*) FROM appointments WHERE user_id = ?', [$uid]);
[$bestSteps, $bestWater] = $one('SELECT COALESCE(MAX(steps),0), COALESCE(MAX(water_ml),0) FROM user_stats WHERE user_id = ?', [$uid]);
$streak = mindful_streak($uid);
$goals = user_goals($uid);

// last 7 days
$st = $pdo->prepare('SELECT date, steps FROM user_stats WHERE user_id = ? AND date >= ?'); $st->execute([$uid, date('Y-m-d', strtotime('-6 days'))]);
$by = array_column($st->fetchAll(), 'steps', 'date'); $week = [];
for ($i = 6; $i >= 0; $i--) { $d = date('Y-m-d', strtotime("-$i days")); $week[] = ['d' => date('D', strtotime($d)), 'v' => (int)($by[$d] ?? 0)]; }
$weekMax = max($goals['steps'], max(array_column($week, 'v')));

// health insights
$h = $user['height_cm'] ? (float)$user['height_cm'] : null; $w = $user['weight_kg'] ? (float)$user['weight_kg'] : null; $age = $user['age'] ? (int)$user['age'] : null;
$bmi = ($h && $w) ? round($w / (($h / 100) ** 2), 1) : null;
$bmiLbl = $bmi === null ? '—' : ($bmi < 18.5 ? 'Underweight' : ($bmi < 25 ? 'Healthy' : ($bmi < 30 ? 'Overweight' : 'Obese')));
$bmiColor = $bmi === null ? '#94a3b8' : ($bmi < 18.5 ? '#38bdf8' : ($bmi < 25 ? '#10b981' : ($bmi < 30 ? '#f59e0b' : '#f43f5e')));
$ideal = $h ? [round(18.5 * ($h / 100) ** 2, 1), round(24.9 * ($h / 100) ** 2, 1)] : null;
$bmr = ($h && $w && $age) ? round(10 * $w + 6.25 * $h - 5 * $age + ($user['gender'] === 'Male' ? 5 : ($user['gender'] === 'Female' ? -161 : -78))) : null;
$mult = ['Sedentary' => 1.2, 'Light' => 1.375, 'Moderate' => 1.55, 'Active' => 1.725, 'Very Active' => 1.9][$user['activity_level']] ?? 1.55;
$tdee = $bmr ? round($bmr * $mult) : null;
$target = $tdee ? ($user['primary_goal'] === 'Lose Weight' ? max(1200, $tdee - 400) : ($user['primary_goal'] === 'Build Muscle' ? $tdee + 250 : $tdee)) : null;
$toTarget = ($w && $user['target_weight_kg']) ? round($w - (float)$user['target_weight_kg'], 1) : null;

// profile completeness
$fields = ['height_cm', 'weight_kg', 'age', 'target_weight_kg', 'gender', 'city', 'bio', 'blood_group'];
$done = count(array_filter($fields, fn($f) => $user[$f] !== null && $user[$f] !== ''));
$complete = (int)round($done / count($fields) * 100);

// achievements
$ach = [
  ['First Workout', 'fa-dumbbell', 'from-orange-400 to-rose-500', $wCount >= 1], ['5 Workouts', 'fa-fire', 'from-rose-400 to-pink-500', $wCount >= 5],
  ['Step Master', 'fa-shoe-prints', 'from-amber-400 to-orange-500', $bestSteps >= 10000], ['Hydration Hero', 'fa-droplet', 'from-sky-400 to-indigo-500', $bestWater >= 2500],
  ['Zen Starter', 'fa-spa', 'from-violet-400 to-fuchsia-500', $mCount >= 1], ['7-Day Streak', 'fa-bolt', 'from-yellow-400 to-orange-500', $streak >= 7],
  ['Social Butterfly', 'fa-heart', 'from-pink-400 to-rose-500', $postCount >= 3], ['Connected', 'fa-user-group', 'from-teal-400 to-emerald-500', $following >= 3],
  ['Doctor Visit', 'fa-user-doctor', 'from-cyan-400 to-blue-500', $apptCount >= 1], ['Profile Pro', 'fa-id-badge', 'from-emerald-400 to-lime-500', $complete === 100],
];
$earned = count(array_filter($ach, fn($a) => $a[3]));

// recent activity timeline
$tl = [];
$st = $pdo->prepare('SELECT workout_type, duration_mins, calories, created_at FROM workouts WHERE user_id = ? ORDER BY created_at DESC LIMIT 5'); $st->execute([$uid]);
foreach ($st->fetchAll() as $r) $tl[] = ['🏋️', $r['workout_type'], $r['duration_mins'] . ' min · ' . $r['calories'] . ' kcal', $r['created_at']];
$st = $pdo->prepare('SELECT session_title, duration_mins, created_at FROM meditation_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 5'); $st->execute([$uid]);
foreach ($st->fetchAll() as $r) $tl[] = ['🧘', $r['session_title'], $r['duration_mins'] . ' min of mindfulness', $r['created_at']];
$st = $pdo->prepare('SELECT d.name, a.date, a.type, a.created FROM (SELECT doctor_id, date, type, date AS created FROM appointments WHERE user_id = ? ORDER BY id DESC LIMIT 3) a JOIN doctors d ON d.id = a.doctor_id'); $st->execute([$uid]);
foreach ($st->fetchAll() as $r) $tl[] = ['🩺', $r['name'], ucfirst($r['type']) . ' consultation', $r['date'] . ' 00:00:00'];
usort($tl, fn($a, $b) => strcmp($b[3], $a[3])); $tl = array_slice($tl, 0, 7);

$st = $pdo->prepare('SELECT * FROM subscriptions WHERE user_id = ? ORDER BY id DESC LIMIT 6'); $st->execute([$uid]); $subs = $st->fetchAll();
$tierStyle = ['Free' => 'bg-white/25 text-white', 'Silver' => 'bg-slate-200 text-slate-700', 'Gold' => 'bg-amber-300 text-amber-900', 'Platinum' => 'bg-gradient-to-r from-cyan-300 to-fuchsia-300 text-slate-900'][$tier];
$sel = fn($a, $b) => $a === $b ? 'selected' : '';
?>
<!-- Hero -->
<section class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-violet-600 via-fuchsia-500 to-orange-400 p-6 text-white shadow-xl shadow-fuchsia-500/30 sm:p-8">
  <div class="pointer-events-none absolute -right-8 -top-8 h-44 w-44 rounded-full bg-white/20 blur-2xl"></div>
  <div class="relative flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
    <div class="flex items-center gap-4">
      <span class="rounded-full bg-white/40 p-1"><?= avatar($user['name'], $uid, 'h-20 w-20 sm:h-24 sm:w-24', 'text-3xl') ?></span>
      <div class="min-w-0">
        <h1 class="truncate text-2xl font-extrabold sm:text-3xl"><?= e($user['name']) ?></h1>
        <p class="truncate text-sm text-white/85"><i class="fa-regular fa-envelope mr-1"></i><?= e($user['email']) ?></p>
        <div class="mt-2 flex flex-wrap gap-1.5 text-xs font-semibold">
          <span class="rounded-full bg-white/25 px-2.5 py-1"><i class="fa-solid fa-bullseye mr-1"></i><?= e($user['primary_goal']) ?></span>
          <?php if ($user['city']): ?><span class="rounded-full bg-white/25 px-2.5 py-1"><i class="fa-solid fa-location-dot mr-1"></i><?= e($user['city']) ?></span><?php endif; ?>
          <span class="rounded-full bg-white/25 px-2.5 py-1"><i class="fa-regular fa-calendar mr-1"></i>Joined <?= date('M Y', strtotime($user['created_at'])) ?></span>
        </div>
        <?php if ($user['bio']): ?><p class="mt-2 text-sm text-white/90"><?= e($user['bio']) ?></p><?php endif; ?>
      </div>
    </div>
    <div class="rounded-2xl bg-white/20 p-4 backdrop-blur md:w-64">
      <p class="text-xs font-semibold uppercase tracking-wider text-white/75">Membership</p>
      <p class="mt-1"><span class="rounded-full px-3 py-1 text-xs font-extrabold tracking-wider <?= $tierStyle ?>"><?= strtoupper($tier) ?><?= $tier === 'Free' ? ' PLAN' : ' MEMBER' ?></span></p>
      <p class="mt-2 text-xs text-white/85"><?= $user['sub_expiry'] ? 'Valid till ' . date('d M Y', strtotime($user['sub_expiry'])) : 'Upgrade any time to unlock extras.' ?></p>
      <a href="#plans" class="hx-btn mt-3 w-full bg-white !py-2 text-violet-700 hover:bg-violet-50">View plans <i class="fa-solid fa-arrow-down text-xs"></i></a>
    </div>
  </div>
</section>

<!-- Stats -->
<section class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
  <?php foreach ([
    ['fa-dumbbell', 'from-orange-400 to-rose-500', number_format((int)$wCount), 'Workouts'],
    ['fa-fire', 'from-rose-400 to-pink-500', number_format((int)$wCal), 'kcal burned'],
    ['fa-stopwatch', 'from-amber-400 to-orange-500', number_format((int)$wMins), 'active min'],
    ['fa-spa', 'from-violet-400 to-fuchsia-500', number_format((int)$mMins), 'mindful min'],
    ['fa-bolt', 'from-yellow-400 to-orange-500', $streak, 'day streak'],
    ['fa-shoe-prints', 'from-emerald-400 to-teal-500', number_format((int)$bestSteps), 'best steps day'],
  ] as $s): ?>
    <div class="glass lift p-4 text-center"><span class="tile mx-auto h-10 w-10 bg-gradient-to-br <?= $s[1] ?>"><i class="fa-solid <?= $s[0] ?>"></i></span>
      <p class="mt-2 text-xl font-extrabold text-slate-800"><?= $s[2] ?></p><p class="text-[11px] font-semibold text-slate-500"><?= $s[3] ?></p></div>
  <?php endforeach; ?>
</section>

<div class="mt-5 grid gap-5 lg:grid-cols-3">
  <!-- Health insights -->
  <section class="glass p-6 lg:col-span-2">
    <div class="flex flex-wrap items-center justify-between gap-2"><h2 class="font-bold text-slate-800"><i class="fa-solid fa-heart-pulse mr-2 text-rose-500"></i>Health insights</h2>
      <span class="badge rounded-pill text-bg-light">Profile <?= $complete ?>% complete</span></div>
    <div class="progress mt-3" style="height:8px"><div class="progress-bar" style="width:<?= $complete ?>%;background:linear-gradient(90deg,#10b981,#0ea5e9,#d946ef)"></div></div>
    <?php if ($bmi !== null): ?>
      <div class="mt-5 grid gap-4 sm:grid-cols-2">
        <div class="rounded-2xl p-4" style="background:<?= $bmiColor ?>1a">
          <p class="text-xs font-semibold uppercase text-slate-500">Body Mass Index</p>
          <p class="mt-1 text-3xl font-extrabold" style="color:<?= $bmiColor ?>"><?= $bmi ?> <span class="text-sm font-bold"><?= $bmiLbl ?></span></p>
          <div class="relative mt-3 h-2.5 rounded-full" style="background:linear-gradient(90deg,#38bdf8 0 18%,#10b981 18% 50%,#f59e0b 50% 72%,#f43f5e 72%)"><span class="absolute top-1/2 h-4 w-4 -translate-y-1/2 -translate-x-1/2 rounded-full border-2 border-white bg-slate-800 shadow" style="left:<?= max(2, min(98, ($bmi - 14) / 26 * 100)) ?>%"></span></div>
          <?php if ($ideal): ?><p class="mt-3 text-xs text-slate-500">Healthy weight range for your height: <strong><?= $ideal[0] ?>–<?= $ideal[1] ?> kg</strong></p><?php endif; ?>
        </div>
        <div class="space-y-2 text-sm">
          <?php foreach (array_filter([
            $tdee ? ['fa-fire-flame-curved', 'text-orange-500', 'Daily calorie target', number_format($target) . ' kcal'] : null,
            $bmr ? ['fa-bed', 'text-indigo-500', 'Resting metabolism (BMR)', number_format($bmr) . ' kcal'] : null,
            ['fa-droplet', 'text-sky-500', 'Suggested water', number_format(round($w * 35 / 50) * 50) . ' ml'],
            ['fa-egg', 'text-amber-500', 'Protein range / day', round($w * 1.2) . '–' . round($w * 1.8) . ' g'],
            $toTarget !== null ? ['fa-flag-checkered', 'text-emerald-500', 'To target weight', ($toTarget > 0 ? $toTarget . ' kg to lose' : ($toTarget < 0 ? abs($toTarget) . ' kg to gain' : 'On target 🎉'))] : null,
          ]) as $r): ?>
            <div class="flex items-center justify-between rounded-xl bg-white px-3 py-2 shadow-sm"><span class="flex items-center gap-2 text-slate-600"><i class="fa-solid <?= $r[0] ?> <?= $r[1] ?>"></i><?= $r[2] ?></span><strong class="text-slate-800"><?= $r[3] ?></strong></div>
          <?php endforeach; ?>
        </div>
      </div>
      <p class="mt-3 text-[11px] text-slate-400">Estimates use standard formulas and are not medical advice. Add age and gender for a more accurate calorie target.</p>
    <?php else: ?>
      <div class="mt-5 rounded-2xl bg-amber-50 p-4 text-sm text-amber-800"><i class="fa-solid fa-circle-info mr-1"></i>Add your height and weight below to unlock BMI, calorie, water and protein insights.</div>
    <?php endif; ?>
  </section>

  <!-- Weekly chart -->
  <section class="glass p-6">
    <h2 class="font-bold text-slate-800"><i class="fa-solid fa-chart-column mr-2 text-sky-500"></i>Steps · last 7 days</h2>
    <div class="mt-5 flex h-40 items-end gap-2">
      <?php foreach ($week as $x): ?>
        <div class="flex flex-1 flex-col items-center gap-1"><div class="w-full rounded-t-lg bg-gradient-to-t from-sky-500 via-emerald-400 to-lime-300" style="height:<?= max(4, round($x['v'] / $weekMax * 100)) ?>%" title="<?= number_format($x['v']) ?>"></div><span class="text-[10px] font-semibold text-slate-500"><?= $x['d'] ?></span></div>
      <?php endforeach; ?>
    </div>
    <p class="mt-3 text-xs text-slate-500">Daily goal: <strong><?= number_format($goals['steps']) ?></strong> steps · <strong><?= number_format($goals['water']) ?></strong> ml water</p>
  </section>
</div>

<!-- Achievements + timeline -->
<div class="mt-5 grid gap-5 lg:grid-cols-3">
  <section class="glass p-6 lg:col-span-2">
    <div class="flex items-center justify-between"><h2 class="font-bold text-slate-800"><i class="fa-solid fa-trophy mr-2 text-amber-500"></i>Achievements</h2><span class="badge rounded-pill text-bg-warning"><?= $earned ?>/<?= count($ach) ?> earned</span></div>
    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-5">
      <?php foreach ($ach as $a): ?>
        <div class="rounded-2xl p-3 text-center <?= $a[3] ? 'bg-white shadow-md' : 'bg-slate-100/70 opacity-60' ?>">
          <span class="tile mx-auto h-11 w-11 <?= $a[3] ? 'bg-gradient-to-br ' . $a[2] : 'bg-slate-300' ?>"><i class="fa-solid <?= $a[1] ?>"></i></span>
          <p class="mt-2 text-[11px] font-bold leading-tight text-slate-700"><?= $a[0] ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
  <section class="glass p-6">
    <h2 class="font-bold text-slate-800"><i class="fa-solid fa-clock-rotate-left mr-2 text-violet-500"></i>Recent activity</h2>
    <ul class="mt-3 space-y-3">
      <?php foreach ($tl as $t): ?><li class="flex items-start gap-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-violet-100 to-sky-100 text-lg"><?= $t[0] ?></span>
        <div class="min-w-0"><p class="truncate text-sm font-semibold text-slate-700"><?= e($t[1]) ?></p><p class="text-xs text-slate-400"><?= e($t[2]) ?> · <?= e(time_ago($t[3])) ?></p></div></li><?php endforeach; ?>
      <?php if (!$tl): ?><li class="text-sm text-slate-400">Nothing yet — log a workout or a breathing session to see it here.</li><?php endif; ?>
    </ul>
  </section>
</div>

<!-- Edit profile (Bootstrap accordion) -->
<section class="mt-5">
  <h2 class="mb-3 text-xl font-extrabold text-slate-800">Edit profile & settings</h2>
  <form id="profileForm">
    <div class="accordion shadow-sm" id="acc" style="--bs-accordion-border-radius:1.5rem;--bs-accordion-inner-border-radius:1.4rem">
      <div class="accordion-item overflow-hidden">
        <h2 class="accordion-header"><button class="accordion-button font-bold" type="button" data-bs-toggle="collapse" data-bs-target="#c1"><i class="fa-solid fa-id-card mr-2 text-fuchsia-500"></i>Personal & body metrics</button></h2>
        <div id="c1" class="accordion-collapse collapse show" data-bs-parent="#acc"><div class="accordion-body grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <div class="lg:col-span-2"><label class="mb-1 block text-xs font-semibold text-slate-500">Full name</label><input class="input" name="name" required maxlength="100" value="<?= e($user['name']) ?>"></div>
          <div><label class="mb-1 block text-xs font-semibold text-slate-500">City</label><input class="input" name="city" maxlength="80" placeholder="e.g. Pune" value="<?= e($user['city']) ?>"></div>
          <div><label class="mb-1 block text-xs font-semibold text-slate-500">Age</label><input class="input" type="number" name="age" min="10" max="100" value="<?= e($user['age']) ?>"></div>
          <div><label class="mb-1 block text-xs font-semibold text-slate-500">Gender</label><select class="input" name="gender"><option value="">Prefer not to say</option><?php foreach (['Male', 'Female', 'Other'] as $g): ?><option <?= $sel($user['gender'], $g) ?>><?= $g ?></option><?php endforeach; ?></select></div>
          <div><label class="mb-1 block text-xs font-semibold text-slate-500">Primary goal</label><select class="input" name="primary_goal"><?php foreach (GOALS_LIST as $g): ?><option <?= $sel($user['primary_goal'], $g) ?>><?= e($g) ?></option><?php endforeach; ?></select></div>
          <div><label class="mb-1 block text-xs font-semibold text-slate-500">Height (cm)</label><input class="input" type="number" name="height_cm" min="50" max="260" value="<?= e($user['height_cm']) ?>"></div>
          <div><label class="mb-1 block text-xs font-semibold text-slate-500">Weight (kg)</label><input class="input" type="number" step="0.1" name="weight_kg" min="20" max="400" value="<?= e($user['weight_kg']) ?>"></div>
          <div><label class="mb-1 block text-xs font-semibold text-slate-500">Target weight (kg)</label><input class="input" type="number" step="0.1" name="target_weight_kg" min="20" max="400" value="<?= e($user['target_weight_kg']) ?>"></div>
          <div class="sm:col-span-2 lg:col-span-3"><label class="mb-1 block text-xs font-semibold text-slate-500">Bio <span class="font-normal text-slate-400">(shown on your community profile)</span></label><input class="input" name="bio" maxlength="200" placeholder="Tell the community about your journey" value="<?= e($user['bio']) ?>"></div>
        </div></div>
      </div>
      <div class="accordion-item overflow-hidden">
        <h2 class="accordion-header"><button class="accordion-button collapsed font-bold" type="button" data-bs-toggle="collapse" data-bs-target="#c2"><i class="fa-solid fa-notes-medical mr-2 text-rose-500"></i>Health & lifestyle</button></h2>
        <div id="c2" class="accordion-collapse collapse" data-bs-parent="#acc"><div class="accordion-body grid gap-4 sm:grid-cols-2">
          <div><label class="mb-1 block text-xs font-semibold text-slate-500">Activity level</label><select class="input" name="activity_level"><?php foreach (['Sedentary', 'Light', 'Moderate', 'Active', 'Very Active'] as $g): ?><option <?= $sel($user['activity_level'], $g) ?>><?= $g ?></option><?php endforeach; ?></select></div>
          <div><label class="mb-1 block text-xs font-semibold text-slate-500">Blood group</label><select class="input" name="blood_group"><option value="">Not set</option><?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $g): ?><option <?= $sel($user['blood_group'], $g) ?>><?= $g ?></option><?php endforeach; ?></select></div>
          <div class="sm:col-span-2"><label class="mb-1 block text-xs font-semibold text-slate-500">Allergies / conditions / notes for your doctor</label><textarea class="input" name="medical_notes" rows="2" maxlength="300" placeholder="e.g. Peanut allergy, mild asthma"><?= e($user['medical_notes']) ?></textarea></div>
        </div></div>
      </div>
      <div class="accordion-item overflow-hidden">
        <h2 class="accordion-header"><button class="accordion-button collapsed font-bold" type="button" data-bs-toggle="collapse" data-bs-target="#c3"><i class="fa-solid fa-bullseye mr-2 text-emerald-500"></i>Daily goals</button></h2>
        <div id="c3" class="accordion-collapse collapse" data-bs-parent="#acc"><div class="accordion-body grid gap-4 sm:grid-cols-2">
          <div><label class="mb-1 block text-xs font-semibold text-slate-500">Daily step goal</label><input class="input" type="number" name="step_goal" min="1000" max="50000" step="500" value="<?= $goals['steps'] ?>"></div>
          <div><label class="mb-1 block text-xs font-semibold text-slate-500">Daily water goal (ml)</label><input class="input" type="number" name="water_goal_ml" min="500" max="8000" step="250" value="<?= $goals['water'] ?>"></div>
        </div></div>
      </div>
    </div>
    <button class="hx-btn hx-primary mt-4"><i class="fa-solid fa-floppy-disk"></i> Save changes</button>
  </form>

  <div class="accordion mt-4 shadow-sm" id="acc2" style="--bs-accordion-border-radius:1.5rem;--bs-accordion-inner-border-radius:1.4rem">
    <div class="accordion-item overflow-hidden">
      <h2 class="accordion-header"><button class="accordion-button collapsed font-bold" type="button" data-bs-toggle="collapse" data-bs-target="#c4"><i class="fa-solid fa-shield-halved mr-2 text-indigo-500"></i>Security — change password</button></h2>
      <div id="c4" class="accordion-collapse collapse" data-bs-parent="#acc2"><div class="accordion-body">
        <form id="pwForm" class="grid gap-4 sm:grid-cols-3">
          <input class="input" type="password" name="current" placeholder="Current password" required autocomplete="current-password">
          <input class="input" type="password" name="new" placeholder="New password (8+ chars)" required minlength="8" autocomplete="new-password">
          <button class="hx-btn hx-sky"><i class="fa-solid fa-key"></i> Update password</button>
        </form>
      </div></div>
    </div>
  </div>
</section>

<!-- Plans (only visible here) -->
<section id="plans" class="mt-10 scroll-mt-6">
  <div class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-end">
    <div><h2 class="text-xl font-extrabold text-slate-800">Membership plans</h2>
      <p class="text-sm text-slate-500">Optional extras — every Healix feature is open to you on any plan.</p></div>
    <div class="grid grid-cols-3 rounded-2xl bg-white p-1 text-sm font-semibold shadow-sm" id="cycleSwitch">
      <?php foreach ([3, 6, 12] as $c): ?><button type="button" data-cycle="<?= $c ?>" class="rounded-xl px-4 py-2 transition"><?= $c ?> Months</button><?php endforeach; ?>
    </div>
  </div>
  <div class="mt-6 grid gap-5 lg:grid-cols-3">
    <?php $pl = ['Silver' => ['from-slate-400 to-slate-600', 'hx-sky'], 'Gold' => ['from-amber-400 to-orange-500', 'hx-coral'], 'Platinum' => ['from-cyan-400 via-violet-500 to-fuchsia-500', 'hx-violet']];
    foreach (PLANS as $name => $p): $pop = $name === 'Gold'; $cur = $tier === $name; ?>
      <article class="glass lift relative flex flex-col overflow-hidden p-0 <?= $pop ? 'ring-2 ring-orange-400 lg:-translate-y-2' : '' ?>">
        <div class="bg-gradient-to-r <?= $pl[$name][0] ?> p-5 text-white">
          <?php if ($pop): ?><span class="absolute right-3 top-3 rounded-full bg-white px-2.5 py-1 text-[10px] font-extrabold tracking-wider text-orange-600">MOST POPULAR</span><?php endif; ?>
          <p class="text-3xl"><?= $p['icon'] ?></p><h3 class="text-xl font-extrabold"><?= $name ?> Tier</h3>
          <p class="mt-2"><span class="price text-4xl font-extrabold" data-tier="<?= $name ?>">₹0</span></p>
          <p class="mt-1 text-xs text-white/85"><span class="perMonth" data-tier="<?= $name ?>"></span> <span class="save ml-1 rounded-full bg-white/25 px-2 py-0.5 font-bold" data-tier="<?= $name ?>"></span></p>
        </div>
        <div class="flex flex-1 flex-col p-5">
          <ul class="mb-5 flex-1 space-y-2 text-sm"><?php foreach ($p['features'] as $f): ?><li class="flex gap-2"><i class="fa-solid fa-circle-check mt-0.5 text-emerald-500"></i><span><?= e($f) ?></span></li><?php endforeach; ?></ul>
          <button class="hx-btn <?= $pl[$name][1] ?> w-full" onclick="openUpgrade('<?= $name ?>')"><?= $cur ? 'Renew / Extend' : (TIERS[$name] < $rank ? 'Switch to ' . $name : 'Choose ' . $name) ?></button>
          <?php if ($cur): ?><p class="mt-2 text-center text-xs font-bold text-emerald-600">✓ Your current plan</p><?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
  <?php if ($subs): ?>
  <div class="glass mt-6 p-6">
    <h3 class="mb-3 font-bold text-slate-800"><i class="fa-solid fa-receipt mr-2 text-emerald-500"></i>Billing history</h3>
    <div class="overflow-x-auto"><table class="w-full min-w-[440px] text-sm">
      <thead><tr class="text-left text-xs uppercase text-slate-400"><th class="py-2">Plan</th><th>Cycle</th><th>Amount</th><th>Valid till</th><th>Status</th></tr></thead>
      <tbody><?php foreach ($subs as $s): ?><tr class="border-t border-slate-100"><td class="py-2 font-semibold"><?= e($s['plan_tier']) ?></td><td><?= e($s['billing_cycle']) ?></td><td><?= inr((int)$s['amount_inr']) ?></td><td><?= date('d M Y', strtotime($s['end_date'])) ?></td>
        <td><span class="badge rounded-pill <?= $s['status'] === 'active' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= e(ucfirst($s['status'])) ?></span></td></tr><?php endforeach; ?></tbody></table></div>
  </div>
  <?php endif; ?>
</section>

<!-- Payment modal: Bootstrap, centered, with close button -->
<div class="modal fade" id="payModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:460px">
    <div class="modal-content overflow-hidden border-0" style="border-radius:1.75rem">
      <div class="flex items-center justify-between bg-gradient-to-r from-violet-600 via-fuchsia-500 to-orange-400 px-5 py-4 text-white">
        <div><p class="text-xs text-white/80">Razorpay Secure · Demo checkout</p><p class="text-lg font-extrabold" id="payTitle">Upgrade</p></div>
        <button type="button" class="xclose" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="modal-body space-y-4 p-5">
        <div class="rounded-2xl bg-gradient-to-br from-violet-50 to-sky-50 p-4 text-sm">
          <div class="flex justify-between"><span class="text-slate-500">Plan</span><strong id="paySum">—</strong></div>
          <div class="mt-1 flex justify-between"><span class="text-slate-500">Valid until</span><strong id="payUntil">—</strong></div>
          <div class="mt-2 flex justify-between border-t border-white pt-2 text-base"><span class="font-semibold">Total</span><strong id="payAmt" class="text-violet-700">₹0</strong></div>
        </div>
        <div class="grid grid-cols-2 rounded-2xl bg-slate-100 p-1 text-sm font-semibold">
          <button type="button" id="m-upi" onclick="setMethod('upi')" class="rounded-xl py-2"><i class="fa-solid fa-mobile-screen mr-1"></i>UPI</button>
          <button type="button" id="m-card" onclick="setMethod('card')" class="rounded-xl py-2"><i class="fa-solid fa-credit-card mr-1"></i>Card</button>
        </div>
        <div id="f-upi"><label class="mb-1 block text-xs font-semibold text-slate-500">UPI ID</label><input id="upiId" class="input" placeholder="yourname@okaxis" autocomplete="off"></div>
        <div id="f-card" class="hidden space-y-3"><input class="input" inputmode="numeric" maxlength="19" placeholder="Card number (demo only)" id="cardNo" autocomplete="off">
          <div class="grid grid-cols-2 gap-3"><input class="input" placeholder="MM / YY" maxlength="7" autocomplete="off"><input class="input" placeholder="CVV" maxlength="4" type="password" autocomplete="off"></div></div>
        <button id="payBtn" class="hx-btn hx-violet w-full" onclick="confirmPayment()"><i class="fa-solid fa-lock"></i> Confirm Payment</button>
        <p class="text-center text-[11px] text-slate-400">Demo only — no real payment is processed and card details never leave your browser.</p>
      </div>
    </div>
  </div>
</div>

<script>
const PLANS = <?= json_encode(PLANS, JSON_HEX_TAG | JSON_HEX_AMP) ?>, CURRENT = <?= json_encode($tier) ?>;
let cycle = 6, selTier = null, method = 'upi';
const inr = n => '₹' + n.toLocaleString('en-IN');
function paint() {
  document.querySelectorAll('#cycleSwitch button').forEach(b => b.className = 'rounded-xl px-4 py-2 transition ' + (+b.dataset.cycle === cycle ? 'bg-gradient-to-r from-violet-500 to-fuchsia-500 text-white shadow' : 'text-slate-500'));
  for (const [t, p] of Object.entries(PLANS)) {
    const pr = p.prices[cycle], base = p.prices[3] * cycle / 3, off = Math.round((1 - pr / base) * 100);
    document.querySelector('.price[data-tier=' + t + ']').textContent = inr(pr);
    document.querySelector('.perMonth[data-tier=' + t + ']').textContent = 'for ' + cycle + ' months · ' + inr(Math.round(pr / cycle)) + '/mo';
    document.querySelector('.save[data-tier=' + t + ']').textContent = off > 0 ? 'Save ' + off + '%' : '';
  }
}
document.querySelectorAll('#cycleSwitch button').forEach(b => b.onclick = () => { cycle = +b.dataset.cycle; paint(); }); paint();
function openUpgrade(t) {
  selTier = t; const pr = PLANS[t].prices[cycle], d = new Date(); d.setMonth(d.getMonth() + cycle);
  document.getElementById('payTitle').textContent = PLANS[t].icon + ' ' + t + ' Tier';
  document.getElementById('paySum').textContent = t + ' · ' + cycle + ' months';
  document.getElementById('payUntil').textContent = d.toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }) + (CURRENT === t ? ' (extends current plan)' : '');
  document.getElementById('payAmt').textContent = inr(pr); setMethod('upi'); openModal('payModal');
}
function setMethod(m) {
  method = m;
  ['upi', 'card'].forEach(k => { document.getElementById('f-' + k).classList.toggle('hidden', k !== m); document.getElementById('m-' + k).className = 'rounded-xl py-2 ' + (k === m ? 'bg-white text-violet-700 shadow-sm' : 'text-slate-500'); });
}
async function confirmPayment() {
  if (method === 'upi' && !/^[\w.\-]{2,}@[A-Za-z]{2,}$/.test(document.getElementById('upiId').value.trim())) return toast('Enter a valid UPI ID, e.g. name@okaxis', 'error');
  if (method === 'card' && document.getElementById('cardNo').value.replace(/\D/g, '').length < 13) return toast('Enter a valid card number (demo).', 'error');
  const btn = document.getElementById('payBtn'); btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing…';
  await sleep(1400);
  const j = await api('upgrade', { tier: selTier, cycle, method, upi: document.getElementById('upiId').value.trim() });
  if (j) { toast(j.message); closeModal('payModal'); await sleep(1200); location.reload(); }
  else { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-lock"></i> Confirm Payment'; }
}
document.getElementById('profileForm').addEventListener('submit', async e => {
  e.preventDefault(); const j = await api('update_profile', Object.fromEntries(new FormData(e.target)));
  if (j) { toast(j.message); setTimeout(() => location.reload(), 900); }
});
document.getElementById('pwForm').addEventListener('submit', async e => {
  e.preventDefault(); const f = e.target, j = await api('change_password', { current: f.current.value, new: f.new.value });
  if (j) { toast(j.message); f.reset(); }
});
</script>
<?php include __DIR__ . '/footer.php'; ?>
