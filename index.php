<?php
$pageTitle = 'Dashboard'; $active = 'home';
require __DIR__ . '/header.php';
$uid = (int)$user['id']; $pdo = db();
$stats = refresh_stats($uid);
$score = (int)$stats['health_score'];
$streak = mindful_streak($uid);
$goals = user_goals($uid);
$first = explode(' ', trim($user['name']))[0];
// last 7 days of steps for the weekly chart
$st = $pdo->prepare('SELECT date, steps, water_ml FROM user_stats WHERE user_id = ? AND date >= ?'); $st->execute([$uid, date('Y-m-d', strtotime('-6 days'))]);
$byDay = []; foreach ($st->fetchAll() as $r) $byDay[$r['date']] = $r;
$week = []; for ($i = 6; $i >= 0; $i--) { $d = date('Y-m-d', strtotime("-$i days")); $week[] = ['d' => date('D', strtotime($d)), 'steps' => (int)($byDay[$d]['steps'] ?? 0), 'today' => $i === 0]; }
$weekMax = max($goals['steps'], max(array_column($week, 'steps')));
$tips = [
  ['💧', 'Hydration hack', 'Drink a glass of water right after waking up — it kick-starts your metabolism.', 'from-sky-500 to-cyan-400'],
  ['🚶', 'Walk it off', 'A 10-minute walk after meals steadies blood sugar and lifts your mood.', 'from-emerald-500 to-lime-400'],
  ['😴', 'Sleep smarter', 'Keep screens away 45 minutes before bed for deeper, calmer sleep.', 'from-violet-500 to-fuchsia-400'],
  ['🥗', 'Colour your plate', 'Aim for 3 different colours of veggies at lunch — more nutrients, more energy.', 'from-orange-500 to-amber-400'],
];
?>
<!-- Hero -->
<section class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-emerald-500 via-teal-500 to-sky-500 p-6 text-white shadow-xl shadow-teal-500/30 sm:p-8">
  <div class="pointer-events-none absolute -right-10 -top-10 h-48 w-48 rounded-full bg-white/15 blur-2xl"></div>
  <div class="pointer-events-none absolute -bottom-12 left-1/3 h-40 w-40 rounded-full bg-pink-300/30 blur-2xl"></div>
  <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
    <div>
      <h1 class="text-2xl font-extrabold sm:text-4xl"><span id="greet">Good Morning</span>, <?= e($first) ?> 🌿</h1>
      <p class="mt-1 text-sm text-white/85">Small, steady steps build a healthier you. Today's focus: <strong><?= e($user['primary_goal']) ?></strong>.</p>
      <div class="mt-4 flex flex-wrap gap-2">
        <a href="exercise.php" class="hx-btn bg-white text-emerald-700 hover:bg-emerald-50"><i class="fa-solid fa-dumbbell"></i> Start workout</a>
        <a href="meditation.php" class="hx-btn bg-white/20 text-white hover:bg-white/30"><i class="fa-solid fa-spa"></i> Breathe</a>
        <a href="consultation.php" class="hx-btn bg-white/20 text-white hover:bg-white/30"><i class="fa-solid fa-user-doctor"></i> Book doctor</a>
      </div>
    </div>
    <div class="flex items-center gap-3 self-start rounded-2xl bg-white/20 px-5 py-3 backdrop-blur sm:self-auto">
      <span class="relative flex h-2.5 w-2.5"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-white opacity-75"></span><span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-white"></span></span>
      <div class="leading-tight"><p id="clock" class="text-lg font-extrabold tabular-nums">--:--:--</p><p id="dateLbl" class="text-xs text-white/80">&nbsp;</p></div>
    </div>
  </div>
</section>

<div class="mt-5 grid gap-5 lg:grid-cols-3">
  <!-- Health score -->
  <section class="glass lift relative overflow-hidden p-6">
    <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-emerald-400 via-sky-400 to-fuchsia-400"></div>
    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500">Overall Health Score</h2>
    <div class="relative mx-auto mt-4 h-44 w-44">
      <svg viewBox="0 0 160 160" class="h-full w-full -rotate-90">
        <defs><linearGradient id="gg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#10b981"/><stop offset=".55" stop-color="#0ea5e9"/><stop offset="1" stop-color="#d946ef"/></linearGradient></defs>
        <circle cx="80" cy="80" r="70" fill="none" stroke="#E8F1EE" stroke-width="13"/>
        <circle id="gauge" cx="80" cy="80" r="70" fill="none" stroke="url(#gg)" stroke-width="13" stroke-linecap="round" stroke-dasharray="439.82" stroke-dashoffset="439.82" class="transition-all duration-1000 ease-out"/>
      </svg>
      <div class="absolute inset-0 flex flex-col items-center justify-center"><span id="scoreNum" class="text-4xl font-extrabold text-slate-800">0</span><span class="text-xs text-slate-400">out of 100</span></div>
    </div>
    <p id="scoreLbl" class="mt-3 text-center text-sm font-bold text-emerald-600">&nbsp;</p>
    <div class="mt-4 grid grid-cols-3 gap-2 text-center text-xs">
      <div class="rounded-xl bg-sky-50 p-2"><p class="font-bold text-sky-600" id="ptW">0/30</p><p class="text-slate-500">💧 Water</p></div>
      <div class="rounded-xl bg-orange-50 p-2"><p class="font-bold text-orange-600" id="ptS">0/40</p><p class="text-slate-500">👟 Steps</p></div>
      <div class="rounded-xl bg-fuchsia-50 p-2"><p class="font-bold text-fuchsia-600" id="ptA">0/30</p><p class="text-slate-500">🏃 Active</p></div>
    </div>
  </section>

  <div class="grid gap-5 sm:grid-cols-2 lg:col-span-2">
    <!-- Steps -->
    <section class="glass lift p-5">
      <div class="flex items-center gap-3"><span class="tile h-11 w-11 bg-gradient-to-br from-orange-400 to-rose-500"><i class="fa-solid fa-shoe-prints"></i></span>
        <div><h2 class="font-bold text-slate-800">Steps</h2><p class="text-xs text-slate-400">Goal <?= number_format($goals['steps']) ?></p></div></div>
      <p class="mt-3 text-3xl font-extrabold text-slate-800"><span id="stepsVal">0</span></p>
      <div class="progress mt-2" style="height:10px"><div id="stepsBar" class="progress-bar" style="width:0;background:linear-gradient(90deg,#fb923c,#f43f5e)"></div></div>
      <div class="mt-4 flex flex-wrap gap-2">
        <button class="chip" onclick="addSteps(500)">+500</button><button class="chip" onclick="addSteps(1000)">+1,000</button>
        <form class="flex flex-1 gap-2" onsubmit="event.preventDefault();addSteps(+document.getElementById('stepIn').value);document.getElementById('stepIn').value=''">
          <input id="stepIn" type="number" min="1" max="30000" placeholder="Custom" class="input !py-1.5 !text-xs"><button class="hx-btn hx-coral !px-3 !py-1.5 text-xs">Add</button>
        </form>
      </div>
    </section>
    <!-- Water -->
    <section class="glass lift p-5">
      <div class="flex items-center gap-3"><span class="tile h-11 w-11 bg-gradient-to-br from-sky-400 to-indigo-500"><i class="fa-solid fa-droplet"></i></span>
        <div><h2 class="font-bold text-slate-800">Water</h2><p class="text-xs text-slate-400">Goal <?= number_format($goals['water']) ?> ml</p></div></div>
      <p class="mt-3 text-3xl font-extrabold text-slate-800"><span id="waterVal">0</span> <span class="text-base font-semibold text-slate-400">ml</span></p>
      <div class="progress mt-2" style="height:10px"><div id="waterBar" class="progress-bar" style="width:0;background:linear-gradient(90deg,#38bdf8,#6366f1)"></div></div>
      <div class="mt-4 flex gap-2"><button class="hx-btn hx-soft flex-1 !bg-sky-100 !text-sky-700" onclick="addWater(250)">+250 ml</button><button class="hx-btn hx-sky flex-1" onclick="addWater(500)">+500 ml</button></div>
    </section>
    <!-- Streak -->
    <section class="glass lift p-5">
      <div class="flex items-center gap-3"><span class="tile h-11 w-11 bg-gradient-to-br from-violet-400 to-fuchsia-500"><i class="fa-solid fa-spa"></i></span><h2 class="font-bold text-slate-800">Mindfulness</h2></div>
      <p class="mt-3 text-2xl font-extrabold text-orange-500"><span class="inline-block animate-pulse">🔥</span> <?= $streak ?> Day Mindfulness Streak</p>
      <p class="mt-1 text-xs text-slate-500"><?= $streak ? 'Keep it alive — one short session today.' : 'Start your streak with a 2-minute breathing session.' ?></p>
      <a href="meditation.php" class="hx-btn hx-violet mt-4 w-full">Open Meditation <i class="fa-solid fa-arrow-right text-xs"></i></a>
    </section>
    <!-- Activity -->
    <section class="glass lift p-5">
      <div class="flex items-center gap-3"><span class="tile h-11 w-11 bg-gradient-to-br from-emerald-400 to-teal-500"><i class="fa-solid fa-fire-flame-curved"></i></span><h2 class="font-bold text-slate-800">Today's Activity</h2></div>
      <div class="mt-3 grid grid-cols-2 gap-3 text-center">
        <div class="rounded-2xl bg-emerald-50 p-3"><p class="text-2xl font-extrabold text-emerald-600" id="actVal">0</p><p class="text-xs text-slate-500">active min</p></div>
        <div class="rounded-2xl bg-orange-50 p-3"><p class="text-2xl font-extrabold text-orange-500" id="calVal">0</p><p class="text-xs text-slate-500">kcal burned</p></div>
      </div>
      <a href="exercise.php" class="hx-btn hx-primary mt-4 w-full"><i class="fa-solid fa-dumbbell"></i> Start a Workout</a>
    </section>
  </div>
</div>

<div class="mt-5 grid gap-5 lg:grid-cols-3">
  <!-- Weekly chart -->
  <section class="glass p-6 lg:col-span-2">
    <div class="flex items-center justify-between"><h2 class="font-bold text-slate-800"><i class="fa-solid fa-chart-column mr-2 text-fuchsia-500"></i>Steps this week</h2><span class="badge rounded-pill text-bg-light"><?= number_format(array_sum(array_column($week, 'steps'))) ?> total</span></div>
    <div class="mt-5 flex h-40 items-end gap-2 sm:gap-4">
      <?php foreach ($week as $w): $h = max(4, round($w['steps'] / $weekMax * 100)); ?>
        <div class="flex flex-1 flex-col items-center gap-1">
          <span class="text-[10px] font-semibold text-slate-400"><?= $w['steps'] ? number_format($w['steps'] / 1000, 1) . 'k' : '' ?></span>
          <div class="w-full rounded-t-xl <?= $w['today'] ? 'bg-gradient-to-t from-fuchsia-500 to-orange-400' : 'bg-gradient-to-t from-emerald-400 to-sky-400' ?>" style="height:<?= $h ?>%"></div>
          <span class="text-[11px] font-semibold <?= $w['today'] ? 'text-fuchsia-600' : 'text-slate-500' ?>"><?= $w['d'] ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
  <!-- Tips carousel (Bootstrap) -->
  <section>
    <div id="tipCarousel" class="carousel slide carousel-fade h-100" data-bs-ride="carousel" data-bs-interval="4500">
      <div class="carousel-inner h-100 rounded-[1.75rem] shadow-lg">
        <?php foreach ($tips as $i => $t): ?>
          <div class="carousel-item h-100 <?= $i === 0 ? 'active' : '' ?>">
            <div class="flex h-full min-h-[14rem] flex-col justify-center bg-gradient-to-br <?= $t[3] ?> p-6 text-white">
              <span class="text-4xl"><?= $t[0] ?></span><h3 class="mt-2 text-lg font-extrabold"><?= e($t[1]) ?></h3><p class="mt-1 text-sm text-white/90"><?= e($t[2]) ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</div>

<script>
const GOAL = { steps: <?= $goals['steps'] ?>, water: <?= $goals['water'] ?>, active: <?= ACTIVE_GOAL ?> };
const CIRC = 2 * Math.PI * 70, $ = id => document.getElementById(id);
let shownScore = 0;
function greet() {
  const now = new Date(), h = now.getHours();
  $('greet').textContent = h < 12 ? 'Good Morning' : h < 17 ? 'Good Afternoon' : 'Good Evening';
  $('clock').textContent = now.toLocaleTimeString('en-IN');
  $('dateLbl').textContent = now.toLocaleDateString('en-IN', { weekday: 'long', day: 'numeric', month: 'long' });
}
greet(); setInterval(greet, 1000);
function countTo(el, to) {
  const from = shownScore, t0 = performance.now();
  (function step(t) { const k = Math.min(1, (t - t0) / 900); el.textContent = Math.round(from + (to - from) * k); if (k < 1) requestAnimationFrame(step); else shownScore = to; })(t0);
}
function render(s, score) {
  const w = +s.water_ml, st = +s.steps, am = +s.active_minutes;
  $('stepsVal').textContent = st.toLocaleString('en-IN'); $('stepsBar').style.width = Math.min(100, st / GOAL.steps * 100) + '%';
  $('waterVal').textContent = w.toLocaleString('en-IN'); $('waterBar').style.width = Math.min(100, w / GOAL.water * 100) + '%';
  $('actVal').textContent = am; $('calVal').textContent = (+s.calories_burned).toLocaleString('en-IN');
  $('gauge').style.strokeDashoffset = CIRC * (1 - score / 100); countTo($('scoreNum'), score);
  $('ptW').textContent = Math.round(Math.min(1, w / GOAL.water) * 30) + '/30';
  $('ptS').textContent = Math.round(Math.min(1, st / GOAL.steps) * 40) + '/40';
  $('ptA').textContent = Math.round(Math.min(1, am / GOAL.active) * 30) + '/30';
  $('scoreLbl').textContent = score >= 80 ? 'Thriving — beautiful work ✨' : score >= 60 ? 'Balanced and steady 🌿' : score >= 35 ? 'Warming up — keep going' : "Let's begin with a glass of water";
}
async function addWater(ml) { const j = await api('add_water', { ml }); if (j) { render(j.stats, j.score); toast('+' + ml + ' ml logged 💧'); } }
async function addSteps(n) { if (!n) return; const j = await api('add_steps', { steps: n }); if (j) { render(j.stats, j.score); toast('+' + n.toLocaleString('en-IN') + ' steps added 👟'); } }
requestAnimationFrame(() => render(<?= json_encode($stats) ?>, <?= $score ?>));
</script>
<?php include __DIR__ . '/footer.php'; ?>
