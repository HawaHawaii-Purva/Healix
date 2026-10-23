<?php
$pageTitle = 'Dashboard'; $active = 'home';
require __DIR__ . '/header.php';
$uid = (int)$user['id'];
$stats = refresh_stats($uid);
$score = (int)$stats['health_score'];
$streak = mindful_streak($uid);
$first = explode(' ', trim($user['name']))[0];
$daysLeft = $user['sub_expiry'] ? max(0, (int)ceil((strtotime($user['sub_expiry']) - strtotime(date('Y-m-d'))) / 86400)) : 0;
?>
<!-- Greeting -->
<section class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
  <div>
    <h1 class="text-2xl font-extrabold text-deepteal sm:text-3xl"><span id="greet">Good Morning</span>, <?= e($first) ?> 🌿</h1>
    <p class="text-sm text-slate-500">Small, steady steps build a healthier you.</p>
  </div>
  <div class="glass flex items-center gap-3 self-start px-4 py-2.5 sm:self-auto">
    <span class="relative flex h-2.5 w-2.5"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-sage"></span></span>
    <div class="leading-tight"><p id="clock" class="text-sm font-bold text-deepteal">--:--:--</p><p id="dateLbl" class="text-xs text-slate-500">&nbsp;</p></div>
  </div>
</section>

<!-- Tier banner -->
<section class="mt-5 flex flex-col gap-3 rounded-3xl bg-gradient-to-r from-deepteal to-sage p-5 text-white shadow-lg sm:flex-row sm:items-center sm:justify-between">
  <div class="flex items-center gap-3">
    <span class="grid h-11 w-11 place-items-center rounded-2xl bg-white/15 text-xl"><i class="fa-solid fa-crown"></i></span>
    <div>
      <?php if ($user['tier_id'] === 'Free'): ?>
        <p class="font-bold">You're on the Free plan</p><p class="text-sm text-emerald-100">Unlock the AI coach, soundscapes and doctor consults.</p>
      <?php else: ?>
        <p class="font-bold"><?= e($user['tier_id']) ?> Member · expires <?= date('d M Y', strtotime($user['sub_expiry'])) ?></p>
        <p class="text-sm text-emerald-100"><?= $daysLeft ?> day<?= $daysLeft === 1 ? '' : 's' ?> remaining<?= $daysLeft <= 14 ? ' — renew soon to keep your perks' : '' ?></p>
      <?php endif; ?>
    </div>
  </div>
  <a href="profile.php#plans" class="btn bg-white text-deepteal hover:bg-mint"><?= $user['tier_id'] === 'Free' ? 'Upgrade Tier' : ($user['tier_id'] === 'Platinum' ? 'Manage / Renew' : 'Upgrade Tier') ?> <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i></a>
</section>

<div class="mt-5 grid gap-5 lg:grid-cols-3">
  <!-- Health score -->
  <section class="glass lift p-6 lg:row-span-1">
    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500">Overall Health Score</h2>
    <div class="relative mx-auto mt-4 h-44 w-44">
      <svg viewBox="0 0 160 160" class="h-full w-full -rotate-90">
        <circle cx="80" cy="80" r="70" fill="none" stroke="#E8F1EE" stroke-width="12"/>
        <circle id="gauge" cx="80" cy="80" r="70" fill="none" stroke="#2E6B56" stroke-width="12" stroke-linecap="round" stroke-dasharray="439.82" stroke-dashoffset="439.82" class="transition-all duration-1000 ease-out"/>
      </svg>
      <div class="absolute inset-0 flex flex-col items-center justify-center"><span id="scoreNum" class="text-4xl font-extrabold text-deepteal">0</span><span class="text-xs text-slate-400">out of 100</span></div>
    </div>
    <p id="scoreLbl" class="mt-3 text-center text-sm font-semibold text-sage">&nbsp;</p>
    <div class="mt-4 space-y-2 text-xs text-slate-500">
      <div class="flex justify-between"><span>💧 Water</span><span id="ptW">0 / 30</span></div>
      <div class="flex justify-between"><span>👟 Steps</span><span id="ptS">0 / 40</span></div>
      <div class="flex justify-between"><span>🏃 Activity</span><span id="ptA">0 / 30</span></div>
    </div>
  </section>

  <div class="grid gap-5 lg:col-span-2 sm:grid-cols-2">
    <!-- Steps -->
    <section class="glass lift p-6">
      <div class="flex items-center justify-between"><h2 class="font-bold text-deepteal"><i class="fa-solid fa-shoe-prints mr-2 text-sage"></i>Steps</h2><span class="text-xs text-slate-400">Goal <?= number_format(STEP_GOAL) ?></span></div>
      <p class="mt-3 text-3xl font-extrabold text-deepteal"><span id="stepsVal">0</span></p>
      <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-mint"><div id="stepsBar" class="h-full w-0 rounded-full bg-sage transition-all duration-700"></div></div>
      <div class="mt-4 flex flex-wrap gap-2">
        <button class="chip" onclick="addSteps(500)">+500</button><button class="chip" onclick="addSteps(1000)">+1,000</button>
        <form class="flex flex-1 gap-2" onsubmit="event.preventDefault();addSteps(+document.getElementById('stepIn').value);document.getElementById('stepIn').value=''">
          <input id="stepIn" type="number" min="1" max="30000" placeholder="Custom" class="input !py-1.5 !text-xs"><button class="btn btn-soft !px-3 !py-1.5 text-xs">Add</button>
        </form>
      </div>
    </section>
    <!-- Water -->
    <section class="glass lift p-6">
      <div class="flex items-center justify-between"><h2 class="font-bold text-deepteal"><i class="fa-solid fa-droplet mr-2 text-sky-500"></i>Water</h2><span class="text-xs text-slate-400">Goal <?= number_format(WATER_GOAL) ?> ml</span></div>
      <p class="mt-3 text-3xl font-extrabold text-deepteal"><span id="waterVal">0</span> <span class="text-base font-semibold text-slate-400">ml</span></p>
      <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-sky-100"><div id="waterBar" class="h-full w-0 rounded-full bg-sky-400 transition-all duration-700"></div></div>
      <div class="mt-4 flex gap-2"><button class="btn btn-soft flex-1" onclick="addWater(250)">+250 ml</button><button class="btn btn-primary flex-1" onclick="addWater(500)">+500 ml</button></div>
    </section>
    <!-- Streak -->
    <section class="glass lift p-6">
      <h2 class="font-bold text-deepteal"><i class="fa-solid fa-spa mr-2 text-coral"></i>Mindfulness</h2>
      <p class="mt-3 text-2xl font-extrabold text-coral"><span class="inline-block animate-pulse">🔥</span> <?= $streak ?> Day Mindfulness Streak</p>
      <p class="mt-1 text-xs text-slate-500"><?= $streak ? 'Keep it alive — one short session today.' : 'Start your streak with a 2-minute breathing session.' ?></p>
      <a href="meditation.php" class="btn btn-soft mt-4 w-full">Open Meditation <i class="fa-solid fa-arrow-right text-xs"></i></a>
    </section>
    <!-- Activity -->
    <section class="glass lift p-6">
      <h2 class="font-bold text-deepteal"><i class="fa-solid fa-fire-flame-curved mr-2 text-coral"></i>Today's Activity</h2>
      <div class="mt-3 grid grid-cols-2 gap-3 text-center">
        <div class="rounded-2xl bg-mint/70 p-3"><p class="text-2xl font-extrabold text-deepteal" id="actVal">0</p><p class="text-xs text-slate-500">active min</p></div>
        <div class="rounded-2xl bg-orange-50 p-3"><p class="text-2xl font-extrabold text-coral" id="calVal">0</p><p class="text-xs text-slate-500">kcal burned</p></div>
      </div>
      <a href="exercise.php" class="btn btn-primary mt-4 w-full"><i class="fa-solid fa-dumbbell"></i> Start a Workout</a>
    </section>
  </div>
</div>

<script>
const GOAL = { steps: <?= STEP_GOAL ?>, water: <?= WATER_GOAL ?>, active: <?= ACTIVE_GOAL ?> };
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
  $('ptW').textContent = Math.round(Math.min(1, w / GOAL.water) * 30) + ' / 30';
  $('ptS').textContent = Math.round(Math.min(1, st / GOAL.steps) * 40) + ' / 40';
  $('ptA').textContent = Math.round(Math.min(1, am / GOAL.active) * 30) + ' / 30';
  $('scoreLbl').textContent = score >= 80 ? 'Thriving — beautiful work ✨' : score >= 60 ? 'Balanced and steady 🌿' : score >= 35 ? 'Warming up — keep going' : "Let's begin with a glass of water";
}
async function addWater(ml) { const j = await api('add_water', { ml }); if (j) { render(j.stats, j.score); toast('+' + ml + ' ml logged 💧'); } }
async function addSteps(n) { if (!n) return; const j = await api('add_steps', { steps: n }); if (j) { render(j.stats, j.score); toast('+' + n.toLocaleString('en-IN') + ' steps added 👟'); } }
requestAnimationFrame(() => render(<?= json_encode($stats) ?>, <?= $score ?>));
</script>
<?php include __DIR__ . '/footer.php'; ?>
