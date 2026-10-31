<?php
$pageTitle = 'Exercise & Coach'; $active = 'exercise';
require __DIR__ . '/header.php';
$uid = (int)$user['id'];
$wf = $user['weight_kg'] ? max(0.6, min(1.8, $user['weight_kg'] / 70)) : 1;
// category => [gradient, "id|Name|icon|mins|kcal per min (70 kg)|intensity|equipment|blurb"...]
$library = [
 'Cardio' => ['from-orange-400 to-rose-500', [
  'brisk-walk|Brisk Walk|fa-person-walking|20|4.5|Low|None|Steady walk that warms up the heart',
  'jump-rope|Jump Rope Basics|fa-circle-notch|10|10|Moderate|Jump rope|Coordination plus a serious calorie burn',
  'steady-jog|Steady-State Jog|fa-person-running|30|9|Moderate|None|Build stamina at a conversational pace',
  'dance-cardio|Dance Cardio Party|fa-music|25|7|Moderate|None|Move to the beat, forget it is a workout',
  'cycling|Cycling Ride|fa-bicycle|30|7.5|Moderate|Bike|Low-impact leg and lung builder',
  'swim-laps|Swim Laps|fa-person-swimming|30|8|Moderate|Pool|Full-body cardio that is easy on joints',
  'shadow-boxing|Shadow Boxing|fa-hand-fist|15|8.5|Moderate|None|Punch combos for speed and shoulders',
  'stair-climb|Stair Climb|fa-arrow-up|15|8|Moderate|Stairs|Quick, glute-firing cardio anywhere']],
 'HIIT' => ['from-rose-500 to-fuchsia-500', [
  'tabata|Tabata Blast|fa-bolt|12|12|High|None|20 s on, 10 s off — maximum effort',
  'lowimpact-hiit|Low-Impact HIIT|fa-wind|15|8|Moderate|None|Intervals without the jumping',
  'burpee-ladder|Burpee Ladder|fa-fire|15|13|High|None|Climb the ladder one burpee at a time',
  'sprint-intervals|Sprint Intervals|fa-stopwatch|20|12|High|Track / road|All-out sprints with smart recovery',
  'kettlebell-swings|Kettlebell Swing Burn|fa-weight-hanging|15|11|High|Kettlebell|Hips, glutes and heart rate through the roof',
  'jump-squat-ladder|Jump Squat Ladder|fa-arrow-up-from-bracket|12|11|High|None|Explosive legs in a short finisher',
  'mountain-climber-blast|Mountain Climber Blast|fa-mountain|10|10|High|None|Core and cardio in one move',
  'beginner-hiit|Beginner HIIT|fa-seedling|15|7|Moderate|None|Gentle intro to interval training']],
 'Yoga & Flexibility' => ['from-violet-400 to-indigo-500', [
  'sun-salute|Sun Salutation Flow|fa-sun|15|3.5|Low|Mat|Classic morning sequence to wake the body',
  'morning-mobility|Morning Mobility|fa-person-praying|10|3|Low|Mat|Loosen joints before the day begins',
  'power-vinyasa|Power Vinyasa|fa-leaf|30|5.5|Moderate|Mat|Strong, flowing, breath-led practice',
  'yin-stretch|Yin Deep Stretch|fa-moon|25|2.8|Low|Mat, blocks|Long holds for deep release',
  'hatha-basics|Hatha Basics|fa-spa|20|3|Low|Mat|Slow poses, steady breath',
  'hip-opener|Hip Opener Flow|fa-person|15|3.2|Low|Mat|Release tight hips after sitting',
  'back-care-yoga|Back-Care Yoga|fa-child-reaching|15|3|Low|Mat|Gentle poses for a stiff back',
  'bedtime-yoga|Bedtime Wind-Down|fa-bed|10|2.2|Low|Mat|Calm the nervous system before sleep']],
 'Strength' => ['from-sky-500 to-blue-600', [
  'bodyweight|Bodyweight Basics|fa-child-reaching|20|6|Moderate|None|Squats, push-ups and lunges anywhere',
  'dumbbell-full|Dumbbell Full Body|fa-dumbbell|30|7|Moderate|Dumbbells|One session for every major muscle',
  'upper-sculpt|Upper Body Sculpt|fa-hand-fist|25|6.5|Moderate|Dumbbells|Shoulders, chest, back and arms',
  'lower-burn|Lower Body Burn|fa-shoe-prints|25|7|Moderate|Dumbbells|Quads, hamstrings and calves',
  'glute-builder|Glute Builder|fa-circle|20|6|Moderate|Band|Bridges, kickbacks and banded steps',
  'resistance-band|Resistance Band Circuit|fa-link|20|5.5|Moderate|Band|Portable strength anywhere',
  'kettlebell-strength|Kettlebell Strength|fa-weight-hanging|25|8|Moderate|Kettlebell|Goblet squats, presses and rows',
  'pushup-ladder|Push-Up Ladder|fa-arrows-up-down|10|7|Moderate|None|Build pressing strength rep by rep']],
 'Core & Pilates' => ['from-emerald-400 to-teal-500', [
  'core-crusher|Core Crusher|fa-circle-dot|12|6|Moderate|Mat|Targeted abs circuit',
  'plank-challenge|Plank Challenge|fa-grip-lines|8|5|Moderate|Mat|Plank variations for a rock-solid trunk',
  'pilates-basics|Pilates Basics|fa-spa|20|4|Low|Mat|Control, breath and alignment',
  'pilates-flow|Pilates Flow|fa-wave-square|30|4.5|Moderate|Mat|Smooth mat sequence for deep core',
  'standing-core|Standing Core|fa-person|12|4.5|Low|None|Core work without lying down',
  'bird-dog-balance|Bird-Dog Balance|fa-dog|10|3.5|Low|Mat|Spine-friendly stability training',
  'russian-twist-set|Russian Twist Set|fa-rotate|10|6|Moderate|Optional weight|Obliques and rotational strength',
  'abs-ladder|Abs Ladder|fa-bars-staggered|12|6.5|Moderate|Mat|Crunches, leg raises and bicycles']],
 'Mobility & Recovery' => ['from-amber-400 to-yellow-500', [
  'foam-rolling|Foam Rolling|fa-bars|10|2.5|Low|Foam roller|Release tight muscles after training',
  'neck-shoulder|Neck & Shoulder Relief|fa-user|8|2|Low|None|Undo desk-day tension',
  'desk-stretch|Desk Stretch Break|fa-chair|5|2|Low|Chair|Quick reset between meetings',
  'post-run-cooldown|Post-Run Cool-Down|fa-person-walking|10|3|Low|None|Bring heart rate down, stretch the legs',
  'lower-back-release|Lower-Back Release|fa-heart-pulse|12|2.5|Low|Mat|Gentle decompression for the lumbar spine',
  'ankle-knee-prep|Ankle & Knee Prep|fa-shoe-prints|8|2.5|Low|None|Joint prep before running or lifting',
  'full-body-stretch|Full-Body Stretch|fa-arrows-left-right|20|2.8|Low|Mat|Head-to-toe flexibility session',
  'breath-reset|Breath & Reset|fa-wind|6|1.8|Low|None|Calm breathing after a hard day']],
];
$map = []; $cats = array_keys($library);
foreach ($library as $cat => [$grad, $rows]) foreach ($rows as $r) { $f = explode('|', $r); $map[$f[0]] = ['name' => $f[1], 'rate' => (float)$f[4], 'intensity' => $f[5]]; }
$st = db()->prepare('SELECT * FROM workouts WHERE user_id = ? ORDER BY created_at DESC LIMIT 5'); $st->execute([$uid]);
$recent = $st->fetchAll();
$intTone = ['Low' => 'bg-emerald-100 text-emerald-700', 'Moderate' => 'bg-amber-100 text-amber-700', 'High' => 'bg-rose-100 text-rose-700'];
?>
<section class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-orange-500 via-rose-500 to-fuchsia-500 p-6 text-white shadow-xl shadow-rose-500/30 sm:p-8">
  <div class="pointer-events-none absolute -right-10 -top-10 h-48 w-48 rounded-full bg-white/20 blur-2xl"></div>
  <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div><h1 class="text-2xl font-extrabold sm:text-3xl">Exercise Library</h1><p class="text-sm text-white/85"><?= count($map) ?> guided sessions across <?= count($cats) ?> categories — pick one and time it live.</p></div>
    <button class="hx-btn bg-white text-rose-600 hover:bg-rose-50" data-bs-toggle="offcanvas" data-bs-target="#coachCanvas"><i class="fa-solid fa-robot"></i> Ask Coach Healix</button>
  </div>
</section>

<!-- Filters -->
<div class="mt-5 flex flex-col gap-3 lg:flex-row lg:items-center">
  <div class="relative flex-1"><i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i><input id="q" class="input !pl-11" placeholder="Search exercises, equipment…" autocomplete="off"></div>
  <div class="flex gap-2" id="intTabs"><?php foreach (['All', 'Low', 'Moderate', 'High'] as $i): ?><button class="chip" data-i="<?= $i ?>"><?= $i === 'All' ? 'Any intensity' : $i ?></button><?php endforeach; ?></div>
</div>
<div class="mt-3 flex gap-2 overflow-x-auto pb-1 no-scrollbar" id="catTabs">
  <button class="whitespace-nowrap rounded-2xl px-4 py-2 text-sm font-semibold transition" data-cat="All">All</button>
  <?php foreach ($cats as $c): ?><button class="whitespace-nowrap rounded-2xl px-4 py-2 text-sm font-semibold transition" data-cat="<?= e($c) ?>"><?= e($c) ?></button><?php endforeach; ?>
</div>

<section id="grid" class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
  <?php foreach ($library as $cat => [$grad, $rows]): foreach ($rows as $r): $f = explode('|', $r); ?>
    <article class="glass lift flex flex-col overflow-hidden p-0" data-cat="<?= e($cat) ?>" data-int="<?= $f[5] ?>" data-text="<?= e(mb_strtolower($f[1] . ' ' . $f[6] . ' ' . $cat . ' ' . $f[7])) ?>">
      <div class="flex items-center gap-3 bg-gradient-to-r <?= $grad ?> p-4 text-white">
        <span class="tile h-12 w-12 bg-white/25 text-xl"><i class="fa-solid <?= $f[2] ?>"></i></span>
        <div class="min-w-0"><h3 class="truncate font-bold"><?= e($f[1]) ?></h3><p class="text-xs text-white/85"><?= e($cat) ?></p></div>
      </div>
      <div class="flex flex-1 flex-col p-4">
        <p class="text-sm text-slate-500"><?= e($f[7]) ?></p>
        <div class="mt-3 flex flex-wrap gap-2 text-xs">
          <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold"><i class="fa-regular fa-clock mr-1"></i><?= $f[3] ?> min</span>
          <span class="rounded-full bg-orange-50 px-2.5 py-1 font-semibold text-orange-600"><i class="fa-solid fa-fire mr-1"></i>~<?= round($f[3] * $f[4] * $wf) ?> kcal</span>
          <span class="rounded-full px-2.5 py-1 font-semibold <?= $intTone[$f[5]] ?>"><?= $f[5] ?></span>
          <span class="rounded-full bg-sky-50 px-2.5 py-1 font-semibold text-sky-600"><i class="fa-solid fa-toolbox mr-1"></i><?= e($f[6]) ?></span>
        </div>
        <button class="hx-btn hx-primary mt-4 w-full" onclick="startWorkout('<?= $f[0] ?>')"><i class="fa-solid fa-play"></i> Start Workout</button>
      </div>
    </article>
  <?php endforeach; endforeach; ?>
</section>
<p id="noRes" class="mt-6 hidden text-center text-sm text-slate-400">No exercises match your search.</p>

<?php if ($recent): ?>
<section class="glass mt-6 p-5">
  <h2 class="mb-2 font-bold text-slate-800"><i class="fa-solid fa-clock-rotate-left mr-2 text-orange-500"></i>Recent workouts</h2>
  <ul class="divide-y divide-slate-100 text-sm"><?php foreach ($recent as $w): ?><li class="flex items-center justify-between py-2"><span class="font-semibold"><?= e($w['workout_type']) ?></span><span class="text-xs text-slate-500"><?= (int)$w['duration_mins'] ?> min · <?= (int)$w['calories'] ?> kcal · <?= e(time_ago($w['created_at'])) ?></span></li><?php endforeach; ?></ul>
</section>
<?php endif; ?>

<!-- Coach (Bootstrap offcanvas – opens only when the button is pressed) -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="coachCanvas" style="width:min(430px,100vw)">
  <div class="flex items-center justify-between bg-gradient-to-r from-violet-600 via-fuchsia-500 to-orange-400 px-5 py-4 text-white">
    <div><p class="flex items-center gap-2 text-lg font-extrabold"><i class="fa-solid fa-robot"></i> Coach Healix</p><p class="text-xs text-white/85">Plans, form tips and nutrition — tailored to you</p></div>
    <button type="button" class="xclose" data-bs-dismiss="offcanvas" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
  </div>
  <div id="coachMsgs" class="flex-1 space-y-3 overflow-y-auto bg-violet-50/60 p-4"></div>
  <div class="space-y-2 border-t border-slate-100 bg-white p-3">
    <div class="flex flex-wrap gap-1.5">
      <?php foreach (['Generate a 15-min low-impact workout', 'Suggest warm-up stretch for lower back pain', 'How much protein should I take post-workout?'] as $q): ?>
        <button class="chip text-left" onclick="coach(<?= e(json_encode($q)) ?>)"><?= e($q) ?></button>
      <?php endforeach; ?>
    </div>
    <details class="rounded-2xl bg-violet-50 px-3 py-2 text-xs">
      <summary class="cursor-pointer font-semibold text-violet-700">Custom plan generator</summary>
      <form id="genForm" class="mt-2 grid grid-cols-3 gap-2">
        <input class="input !px-2 !py-1.5 text-xs" type="number" name="mins" min="5" max="60" value="20" aria-label="Minutes">
        <select class="input !px-2 !py-1.5 text-xs" name="kind"><option>low-impact</option><option>HIIT</option><option>strength</option><option>yoga</option><option>cardio</option></select>
        <button class="hx-btn hx-violet !px-2 !py-1.5 text-xs">Generate</button>
      </form>
    </details>
    <form id="coachForm" class="flex gap-2"><input id="coachIn" class="input !py-2" maxlength="400" placeholder="Ask anything about training…" autocomplete="off"><button class="hx-btn hx-violet !px-4" aria-label="Send"><i class="fa-solid fa-paper-plane"></i></button></form>
  </div>
</div>

<!-- Stopwatch modal (Bootstrap, centered, with close button) -->
<div class="modal fade" id="swModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered" style="max-width:400px">
    <div class="modal-content overflow-hidden border-0" style="border-radius:1.75rem">
      <div class="flex items-center justify-between bg-gradient-to-r from-orange-500 to-fuchsia-500 px-5 py-4 text-white">
        <div><p class="text-xs font-bold uppercase tracking-wider text-white/75">Live workout</p><h2 id="swName" class="text-lg font-extrabold">Workout</h2></div>
        <button type="button" class="xclose" onclick="swCancel()" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="p-6 text-center">
        <div class="relative mx-auto grid h-48 w-48 place-items-center rounded-full bg-gradient-to-br from-orange-50 to-fuchsia-50">
          <span id="swPulse" class="absolute inset-0 hidden rounded-full bg-fuchsia-400/30 ripple"></span>
          <div class="relative"><p id="swTime" class="text-4xl font-extrabold tabular-nums text-slate-800">00:00</p><p class="mt-1 text-sm font-bold text-orange-500"><i class="fa-solid fa-fire"></i> <span id="swCal">0</span> kcal</p></div>
        </div>
        <div class="mt-6 grid grid-cols-2 gap-3">
          <button id="swMain" class="hx-btn hx-primary" onclick="swToggle()"><i class="fa-solid fa-play"></i> Start</button>
          <button id="swFinish" class="hx-btn hx-coral" onclick="swFinish()" disabled><i class="fa-solid fa-flag-checkered"></i> Finish</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const LIB = <?= json_encode($map, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, WF = <?= json_encode($wf) ?>, CATS = <?= json_encode($cats) ?>;
const $ = id => document.getElementById(id);
$('coachCanvas').parentElement !== document.body && document.body.appendChild($('coachCanvas'));

/* Filters */
let cat = 'All', inten = 'All';
function applyFilters() {
  const q = $('q').value.trim().toLowerCase(); let n = 0;
  document.querySelectorAll('#grid article').forEach(a => {
    const ok = (cat === 'All' || a.dataset.cat === cat) && (inten === 'All' || a.dataset.int === inten) && (!q || a.dataset.text.includes(q));
    a.classList.toggle('hidden', !ok); if (ok) n++;
  });
  $('noRes').classList.toggle('hidden', n > 0);
  document.querySelectorAll('#catTabs button').forEach(b => b.className = 'whitespace-nowrap rounded-2xl px-4 py-2 text-sm font-semibold transition ' + (b.dataset.cat === cat ? 'bg-gradient-to-r from-orange-500 to-fuchsia-500 text-white shadow-md' : 'bg-white/80 text-slate-600 hover:bg-white'));
  document.querySelectorAll('#intTabs button').forEach(b => b.className = 'chip ' + (b.dataset.i === inten ? '!bg-slate-800 !text-white !border-slate-800' : ''));
}
document.querySelectorAll('#catTabs button').forEach(b => b.onclick = () => { cat = b.dataset.cat; applyFilters(); });
document.querySelectorAll('#intTabs button').forEach(b => b.onclick = () => { inten = b.dataset.i; applyFilters(); });
$('q').addEventListener('input', applyFilters); applyFilters();

/* Stopwatch */
let cur = null, acc = 0, t0 = 0, iv = null, running = false;
const elapsed = () => acc + (running ? Date.now() - t0 : 0), kcal = () => Math.round(elapsed() / 60000 * cur.rate * WF);
function paintSw() { const s = Math.floor(elapsed() / 1000); $('swTime').textContent = String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0'); $('swCal').textContent = kcal(); }
function startWorkout(id) {
  cur = LIB[id]; acc = 0; running = false; clearInterval(iv);
  $('swName').textContent = cur.name; $('swMain').innerHTML = '<i class="fa-solid fa-play"></i> Start'; $('swFinish').disabled = true; $('swPulse').classList.add('hidden'); paintSw(); openModal('swModal');
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
