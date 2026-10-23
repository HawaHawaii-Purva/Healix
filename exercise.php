<?php
$pageTitle = 'Exercise & Coach'; $active = 'exercise';
require __DIR__ . '/header.php';
$uid = (int)$user['id'];
$rank = tier_rank($tier);
$wf = $user['weight_kg'] ? max(0.6, min(1.8, $user['weight_kg'] / 70)) : 1;
// id, name, icon, suggested mins, kcal/min (70kg), intensity, required tier
$library = [
 'Cardio' => [
  ['brisk-walk', 'Brisk Walk', 'fa-person-walking', 20, 4.5, 'Low', 'Free'],
  ['jump-rope', 'Jump Rope Basics', 'fa-circle-notch', 10, 10, 'Moderate', 'Free'],
  ['steady-jog', 'Steady-State Jog', 'fa-person-running', 30, 9, 'Moderate', 'Gold'],
  ['dance-cardio', 'Dance Cardio Party', 'fa-music', 25, 7, 'Moderate', 'Gold']],
 'HIIT' => [
  ['tabata', 'Tabata Blast', 'fa-bolt', 12, 12, 'High', 'Free'],
  ['lowimpact-hiit', 'Low-Impact HIIT', 'fa-wind', 15, 8, 'Moderate', 'Free'],
  ['burpee-ladder', 'Burpee Ladder', 'fa-fire', 15, 13, 'High', 'Gold'],
  ['sprint-intervals', 'Sprint Intervals', 'fa-stopwatch', 20, 12, 'High', 'Gold']],
 'Yoga & Flexibility' => [
  ['sun-salute', 'Sun Salutation Flow', 'fa-sun', 15, 3.5, 'Low', 'Free'],
  ['morning-mobility', 'Morning Mobility', 'fa-person-praying', 10, 3, 'Low', 'Free'],
  ['power-vinyasa', 'Power Vinyasa', 'fa-leaf', 30, 5.5, 'Moderate', 'Gold'],
  ['yin-stretch', 'Yin Deep Stretch', 'fa-moon', 25, 2.8, 'Low', 'Gold']],
 'Strength' => [
  ['bodyweight', 'Bodyweight Basics', 'fa-child-reaching', 20, 6, 'Moderate', 'Free'],
  ['core', 'Core Crusher', 'fa-circle-dot', 12, 6, 'Moderate', 'Free'],
  ['dumbbell-full', 'Dumbbell Full Body', 'fa-dumbbell', 30, 7, 'Moderate', 'Gold'],
  ['upper-sculpt', 'Upper Body Sculpt', 'fa-hand-fist', 25, 6.5, 'Moderate', 'Gold']],
];
$map = [];
foreach ($library as $cat => $items) foreach ($items as $i) $map[$i[0]] = ['name' => $i[1], 'rate' => $i[4], 'intensity' => $i[5], 'locked' => tier_rank($i[6]) > $rank];
$st = db()->prepare('SELECT * FROM workouts WHERE user_id = ? ORDER BY created_at DESC LIMIT 5'); $st->execute([$uid]);
$recent = $st->fetchAll();
$cats = array_keys($library);
?>
<h1 class="text-2xl font-extrabold text-deepteal sm:text-3xl">Exercise & AI Coach</h1>
<p class="text-sm text-slate-500">Pick a session, time it live, and ask Coach Healix for plans tailored to you.</p>

<div class="mt-5 grid gap-5 lg:grid-cols-3">
  <!-- Library -->
  <section class="lg:col-span-2">
    <div class="flex gap-2 overflow-x-auto pb-1" id="catTabs">
      <?php foreach ($cats as $i => $c): ?><button class="whitespace-nowrap rounded-2xl px-4 py-2 text-sm font-semibold transition" data-cat="<?= $i ?>"><?= e($c) ?></button><?php endforeach; ?>
    </div>
    <?php foreach ($library as $c => $items): $ci = array_search($c, $cats); ?>
      <div class="catPane mt-4 hidden grid-cols-1 gap-4 sm:grid-cols-2" data-cat="<?= $ci ?>">
        <?php foreach ($items as $it): $locked = tier_rank($it[6]) > $rank; ?>
          <article class="glass lift flex flex-col p-5 <?= $locked ? 'opacity-90' : '' ?>">
            <div class="flex items-start justify-between">
              <span class="grid h-12 w-12 place-items-center rounded-2xl <?= $locked ? 'bg-slate-100 text-slate-400' : 'bg-mint text-sage' ?> text-xl"><i class="fa-solid <?= $it[2] ?>"></i></span>
              <?php if ($locked): ?><span class="rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-extrabold text-amber-700"><i class="fa-solid fa-lock mr-1"></i>GOLD+</span>
              <?php else: ?><span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-extrabold text-emerald-700"><?= $it[6] === 'Free' ? 'FREE' : 'UNLOCKED' ?></span><?php endif; ?>
            </div>
            <h3 class="mt-3 font-bold text-deepteal"><?= e($it[1]) ?></h3>
            <div class="mt-2 flex flex-wrap gap-2 text-xs">
              <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold"><i class="fa-regular fa-clock mr-1"></i><?= $it[3] ?> min</span>
              <span class="rounded-full bg-orange-50 px-2.5 py-1 font-semibold text-coral"><i class="fa-solid fa-fire mr-1"></i>~<?= round($it[3] * $it[4] * $wf) ?> kcal</span>
              <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold"><?= $it[5] ?></span>
            </div>
            <button class="btn <?= $locked ? 'btn-soft' : 'btn-primary' ?> mt-4 w-full" onclick="startWorkout('<?= $it[0] ?>')"><?= $locked ? '<i class="fa-solid fa-lock"></i> Unlock with Gold' : '<i class="fa-solid fa-play"></i> Start Workout' ?></button>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>

    <?php if ($recent): ?>
    <div class="glass mt-6 p-5">
      <h2 class="mb-2 font-bold text-deepteal"><i class="fa-solid fa-clock-rotate-left mr-2 text-sage"></i>Recent workouts</h2>
      <ul class="divide-y divide-slate-100 text-sm" id="recentList">
        <?php foreach ($recent as $w): ?><li class="flex items-center justify-between py-2"><span class="font-semibold"><?= e($w['workout_type']) ?></span><span class="text-xs text-slate-500"><?= (int)$w['duration_mins'] ?> min · <?= (int)$w['calories'] ?> kcal · <?= e(time_ago($w['created_at'])) ?></span></li><?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
  </section>

  <!-- Coach -->
  <aside class="lg:sticky lg:top-6 lg:self-start">
    <div class="glass flex h-[34rem] flex-col overflow-hidden">
      <div class="bg-gradient-to-r from-deepteal to-sage px-5 py-4 text-white">
        <p class="flex items-center gap-2 font-bold"><i class="fa-solid fa-robot"></i> Ask Coach Healix</p>
        <p class="text-xs text-emerald-100"><?= $rank >= 3 ? 'Unlimited coaching · Platinum' : ($rank == 2 ? 'Personalised plans · Gold' : 'Basic coaching · upgrade to Gold for full plans') ?></p>
      </div>
      <div id="coachMsgs" class="flex-1 space-y-3 overflow-y-auto bg-mint/40 p-4"></div>
      <div class="space-y-2 border-t border-white/60 bg-white/60 p-3">
        <div class="flex flex-wrap gap-1.5">
          <?php foreach (['Generate a 15-min low-impact workout', 'Suggest warm-up stretch for lower back pain', 'How much protein should I take post-workout?'] as $q): ?>
            <button class="chip text-left" onclick="coach(<?= e(json_encode($q)) ?>)"><?= e($q) ?></button>
          <?php endforeach; ?>
        </div>
        <details class="rounded-2xl bg-white/70 px-3 py-2 text-xs">
          <summary class="cursor-pointer font-semibold text-deepteal">Custom plan generator</summary>
          <form id="genForm" class="mt-2 grid grid-cols-3 gap-2">
            <input class="input !px-2 !py-1.5 text-xs" type="number" name="mins" min="5" max="60" value="20" aria-label="Minutes">
            <select class="input !px-2 !py-1.5 text-xs" name="kind"><option>low-impact</option><option>HIIT</option><option>strength</option><option>yoga</option><option>cardio</option></select>
            <button class="btn btn-soft !px-2 !py-1.5 text-xs">Generate</button>
          </form>
        </details>
        <form id="coachForm" class="flex gap-2"><input id="coachIn" class="input !py-2" maxlength="400" placeholder="Ask anything about training…" autocomplete="off"><button class="btn btn-primary !px-4" aria-label="Send"><i class="fa-solid fa-paper-plane"></i></button></form>
      </div>
    </div>
  </aside>
</div>

<!-- Stopwatch modal -->
<div id="swModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-deepteal/50 p-4 backdrop-blur-sm">
  <div class="glass fade-in w-full max-w-sm !bg-white p-7 text-center">
    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Live workout</p>
    <h2 id="swName" class="mt-1 text-xl font-extrabold text-deepteal">Workout</h2>
    <div class="relative mx-auto mt-5 grid h-48 w-48 place-items-center rounded-full bg-mint">
      <span id="swPulse" class="absolute inset-0 hidden rounded-full bg-sage/30 ripple"></span>
      <div class="relative"><p id="swTime" class="text-4xl font-extrabold tabular-nums text-deepteal">00:00</p><p class="mt-1 text-sm font-semibold text-coral"><i class="fa-solid fa-fire"></i> <span id="swCal">0</span> kcal</p></div>
    </div>
    <div class="mt-6 grid grid-cols-2 gap-3">
      <button id="swMain" class="btn btn-primary" onclick="swToggle()"><i class="fa-solid fa-play"></i> Start</button>
      <button id="swFinish" class="btn btn-coral" onclick="swFinish()" disabled><i class="fa-solid fa-flag-checkered"></i> Finish</button>
    </div>
    <button class="mt-4 text-xs font-semibold text-slate-400 hover:text-slate-600" onclick="swCancel()">Cancel</button>
  </div>
</div>

<script>
const LIB = <?= json_encode($map, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, WF = <?= json_encode($wf) ?>;
const $ = id => document.getElementById(id);
/* Category tabs */
function showCat(i) {
  document.querySelectorAll('#catTabs button').forEach(b => b.className = 'whitespace-nowrap rounded-2xl px-4 py-2 text-sm font-semibold transition ' + (+b.dataset.cat === i ? 'bg-sage text-white shadow' : 'bg-white/70 text-slate-600 hover:bg-mint'));
  document.querySelectorAll('.catPane').forEach(p => { const on = +p.dataset.cat === i; p.classList.toggle('hidden', !on); p.classList.toggle('grid', on); if (on) p.classList.add('fade-in'); });
}
document.querySelectorAll('#catTabs button').forEach(b => b.onclick = () => showCat(+b.dataset.cat)); showCat(0);

/* Stopwatch */
let cur = null, acc = 0, t0 = 0, iv = null, running = false;
function elapsed() { return acc + (running ? Date.now() - t0 : 0); }
function kcal() { return Math.round(elapsed() / 60000 * cur.rate * WF); }
function paintSw() {
  const s = Math.floor(elapsed() / 1000);
  $('swTime').textContent = String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0'); $('swCal').textContent = kcal();
}
function startWorkout(id) {
  const w = LIB[id];
  if (w.locked) { toast('This session is part of Gold & Platinum. Upgrade to unlock it.', 'info'); setTimeout(() => location.href = 'profile.php#plans', 1200); return; }
  cur = w; acc = 0; running = false; clearInterval(iv);
  $('swName').textContent = w.name; $('swMain').innerHTML = '<i class="fa-solid fa-play"></i> Start'; $('swFinish').disabled = true; $('swPulse').classList.add('hidden'); paintSw(); openModal('swModal');
}
function swToggle() {
  if (!running) { running = true; t0 = Date.now(); iv = setInterval(paintSw, 250); $('swMain').innerHTML = '<i class="fa-solid fa-pause"></i> Pause'; $('swPulse').classList.remove('hidden'); $('swFinish').disabled = false; }
  else { acc += Date.now() - t0; running = false; clearInterval(iv); paintSw(); $('swMain').innerHTML = '<i class="fa-solid fa-play"></i> Resume'; $('swPulse').classList.add('hidden'); }
}
async function swFinish() {
  if (running) swToggle();
  if (elapsed() < 5000) return toast('Move a little longer before finishing 😊', 'info');
  const j = await api('save_workout', { workout_type: cur.name, duration_mins: Math.max(1, Math.round(elapsed() / 60000)), calories: kcal(), intensity: cur.intensity });
  if (j) { toast(j.message); closeModal('swModal'); setTimeout(() => location.reload(), 1200); }
}
function swCancel() { if (elapsed() > 5000 && !confirm('Discard this workout without saving?')) return; running = false; clearInterval(iv); closeModal('swModal'); }

/* Coach */
const box = $('coachMsgs'); let busy = false;
addMsg(box, 'ai', "Hi <?= e(explode(' ', trim($user['name']))[0]) ?>! I'm Coach Healix 💪 Tap a suggestion or ask me anything about workouts, recovery or nutrition.");
async function coach(q) { if (busy) return; busy = true; await chatAI(box, q); busy = false; }
$('coachForm').addEventListener('submit', e => { e.preventDefault(); const q = $('coachIn').value.trim(); if (q) { $('coachIn').value = ''; coach(q); } });
$('genForm').addEventListener('submit', e => { e.preventDefault(); const f = e.target; coach('Generate a ' + f.mins.value + '-min ' + f.kind.value + ' workout'); });
</script>
<?php include __DIR__ . '/footer.php'; ?>
