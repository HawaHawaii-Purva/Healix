<?php
$pageTitle = 'Meditation'; $active = 'meditation';
require __DIR__ . '/header.php';
$uid = (int)$user['id'];
$streak = mindful_streak($uid);
$st = db()->prepare('SELECT * FROM meditation_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 6'); $st->execute([$uid]);
$logs = $st->fetchAll();
$st = db()->prepare('SELECT COALESCE(SUM(duration_mins),0) FROM meditation_logs WHERE user_id = ?'); $st->execute([$uid]);
$totalMins = (int)$st->fetchColumn();
/* Ambient tracks. If a host blocks hot-linking, upload MP3s to /sounds/ and point these URLs there. */
$tracks = [
  ['rain', 'Gentle Rain', 'fa-cloud-rain', 'https://www.soundjay.com/nature/sounds/rain-01.mp3'],
  ['ocean', 'Ocean Waves', 'fa-water', 'https://www.soundjay.com/nature/sounds/ocean-wave-1.mp3'],
];
?>
<div class="flex flex-wrap items-end justify-between gap-3">
  <div><h1 class="text-2xl font-extrabold text-deepteal sm:text-3xl">Mindful Meditation</h1><p class="text-sm text-slate-500">Slow down with a guided 4-7-8 breath and calming soundscapes.</p></div>
  <div class="glass px-4 py-2.5 text-sm font-bold text-coral"><span class="inline-block animate-pulse">🔥</span> <span id="streakVal"><?= $streak ?></span> day streak</div>
</div>

<div class="mt-5 grid gap-5 lg:grid-cols-3">
  <section class="glass p-6 lg:col-span-2">
    <div class="relative mx-auto flex h-72 w-72 max-w-full items-center justify-center">
      <div id="ring" class="absolute inset-4 rounded-full bg-emerald-200/40 transition-transform ease-in-out"></div>
      <div id="orb" class="relative grid h-40 w-40 place-items-center rounded-full bg-gradient-to-br from-emerald-300 to-sage text-center text-white shadow-xl transition-transform ease-in-out">
        <div><p id="phase" class="text-lg font-bold">Ready</p><p id="count" class="text-3xl font-extrabold tabular-nums">&nbsp;</p></div>
      </div>
    </div>
    <div class="mt-4 grid grid-cols-3 gap-3 text-center text-xs font-semibold text-slate-500">
      <?php foreach ([['in', 'Inhale (s)', 4], ['hold', 'Hold (s)', 7], ['out', 'Exhale (s)', 8]] as $d): ?>
        <label><?= $d[1] ?><input id="d-<?= $d[0] ?>" type="number" min="<?= $d[0] === 'hold' ? 0 : 1 ?>" max="20" value="<?= $d[2] ?>" class="input mt-1 text-center"></label>
      <?php endforeach; ?>
    </div>
    <div class="mt-4 flex items-center justify-center gap-6 text-sm text-slate-500">
      <span><i class="fa-regular fa-clock mr-1"></i><b id="elapsedLbl" class="tabular-nums text-deepteal">00:00</b></span>
      <span><i class="fa-solid fa-rotate mr-1"></i><b id="cyclesLbl" class="text-deepteal">0</b> cycles</span>
    </div>
    <div class="mt-5 flex flex-wrap justify-center gap-3">
      <button id="startBtn" class="btn btn-primary min-w-32" onclick="toggleBreath()"><i class="fa-solid fa-play"></i> Start</button>
      <button class="btn btn-soft" onclick="resetBreath()"><i class="fa-solid fa-rotate-left"></i> Reset</button>
      <button id="logBtn" class="btn btn-coral" onclick="logSession()"><i class="fa-solid fa-circle-check"></i> Finish & Log</button>
    </div>
  </section>

  <aside class="space-y-5">
    <section class="glass p-5">
      <h2 class="font-bold text-deepteal"><i class="fa-solid fa-headphones mr-2 text-sage"></i>Ambient Sounds</h2>
      <div class="mt-3 grid grid-cols-2 gap-2">
        <?php foreach ($tracks as $t): ?>
          <button data-track="<?= $t[0] ?>" onclick="playTrack('<?= $t[0] ?>')" class="trackBtn flex flex-col items-center gap-1 rounded-2xl border border-emerald-100 bg-white/70 p-3 text-xs font-semibold transition hover:bg-mint"><i class="fa-solid <?= $t[2] ?> text-xl text-sage"></i><?= e($t[1]) ?></button>
        <?php endforeach; ?>
      </div>
      <p id="nowPlaying" class="mt-3 text-xs text-slate-500">Choose a soundscape to begin.</p>
      <audio id="amb" controls loop preload="none" class="mt-2 w-full"></audio>
    </section>
    <section class="glass p-5">
      <h2 class="font-bold text-deepteal"><i class="fa-solid fa-book-open mr-2 text-sage"></i>Your sessions</h2>
      <p class="mt-1 text-xs text-slate-500"><span id="totalMins"><?= $totalMins ?></span> mindful minutes in total</p>
      <ul id="logList" class="mt-3 space-y-2 text-sm">
        <?php foreach ($logs as $l): ?><li class="flex items-center justify-between rounded-xl bg-mint/60 px-3 py-2"><span class="font-semibold"><?= e($l['session_title']) ?></span><span class="text-xs text-slate-500"><?= (int)$l['duration_mins'] ?> min · <?= e(time_ago($l['created_at'])) ?></span></li><?php endforeach; ?>
        <?php if (!$logs): ?><li id="emptyLog" class="text-xs text-slate-400">No sessions yet — your first one starts the streak.</li><?php endif; ?>
      </ul>
    </section>
  </aside>
</div>

<script>
const TRACKS = <?= json_encode(array_column(array_map(fn($t) => [$t[0], ['name' => $t[1], 'url' => $t[3]]], $tracks), 1, 0), JSON_HEX_TAG | JSON_HEX_AMP) ?>;
const $ = id => document.getElementById(id), orb = $('orb'), ring = $('ring'), amb = $('amb');
let run = 0, running = false, elapsed = 0, cycles = 0, clock = null, track = 'None';

const dur = () => [Math.max(1, +$('d-in').value || 4), Math.max(0, +$('d-hold').value || 0), Math.max(1, +$('d-out').value || 8)];
function phase(label, secs, scale, token) {
  return new Promise(res => {
    if (secs <= 0) return res();
    orb.style.transitionDuration = secs + 's'; ring.style.transitionDuration = secs + 's';
    orb.style.transform = 'scale(' + scale + ')'; ring.style.transform = 'scale(' + (scale * 1.18) + ')';
    let left = secs; $('phase').textContent = label; $('count').textContent = left;
    const iv = setInterval(() => { if (token !== run) { clearInterval(iv); return res(); } left--; if (left <= 0) { clearInterval(iv); res(); } else $('count').textContent = left; }, 1000);
  });
}
async function breathe(token) {
  while (token === run) {
    const [i, h, x] = dur();
    await phase('Inhale', i, 1.45, token); if (token !== run) break;
    await phase('Hold', h, 1.45, token); if (token !== run) break;
    await phase('Exhale', x, 1, token); if (token !== run) break;
    cycles++; $('cyclesLbl').textContent = cycles;
  }
}
function tick() { elapsed++; $('elapsedLbl').textContent = String(Math.floor(elapsed / 60)).padStart(2, '0') + ':' + String(elapsed % 60).padStart(2, '0'); }
function toggleBreath() {
  if (!running) { running = true; run++; clock = setInterval(tick, 1000); breathe(run); $('startBtn').innerHTML = '<i class="fa-solid fa-pause"></i> Pause'; }
  else { running = false; run++; clearInterval(clock); $('startBtn').innerHTML = '<i class="fa-solid fa-play"></i> Resume'; $('phase').textContent = 'Paused'; $('count').innerHTML = '&nbsp;'; }
}
function resetBreath() {
  running = false; run++; clearInterval(clock); elapsed = 0; cycles = 0; $('elapsedLbl').textContent = '00:00'; $('cyclesLbl').textContent = 0;
  orb.style.transitionDuration = '1s'; ring.style.transitionDuration = '1s'; orb.style.transform = ring.style.transform = 'scale(1)';
  $('phase').textContent = 'Ready'; $('count').innerHTML = '&nbsp;'; $('startBtn').innerHTML = '<i class="fa-solid fa-play"></i> Start';
}
async function logSession() {
  if (elapsed < 10) return toast('Breathe for at least 10 seconds before logging.', 'info');
  const [i, h, x] = dur(), mins = Math.max(1, Math.round(elapsed / 60));
  const j = await api('log_meditation', { title: i + '-' + h + '-' + x + ' Breathing', track, duration_mins: mins });
  if (!j) return;
  toast('Session logged · ' + mins + ' min 🌿'); $('streakVal').textContent = j.streak;
  const e = $('emptyLog'); if (e) e.remove();
  const li = document.createElement('li'); li.className = 'fade-in flex items-center justify-between rounded-xl bg-mint/60 px-3 py-2';
  li.innerHTML = '<span class="font-semibold">' + esc(j.entry.title) + '</span><span class="text-xs text-slate-500">' + j.entry.mins + ' min · Just now</span>';
  $('logList').prepend(li); $('totalMins').textContent = (+$('totalMins').textContent) + mins; resetBreath();
}

function playTrack(id) {
  const t = TRACKS[id];
  if (track === t.name && !amb.paused) { amb.pause(); track = 'None'; paintTracks(null); $('nowPlaying').textContent = 'Paused.'; return; }
  amb.src = t.url; track = t.name;
  amb.play().then(() => { paintTracks(id); $('nowPlaying').textContent = 'Now playing: ' + t.name; })
    .catch(() => { track = 'None'; toast('Could not stream ' + t.name + '. The host may block remote playback — upload the MP3 to your own site and update the URL.', 'error'); });
}
function paintTracks(id) { document.querySelectorAll('.trackBtn').forEach(b => b.classList.toggle('!bg-emerald-100', b.dataset.track === id)); }
amb.addEventListener('error', () => { if (amb.src) toast('Audio failed to load. Check the sound URL or your connection.', 'error'); });
amb.addEventListener('pause', () => { if (amb.paused && track !== 'None') $('nowPlaying').textContent = 'Paused: ' + track; });
amb.addEventListener('play', () => { if (track !== 'None') $('nowPlaying').textContent = 'Now playing: ' + track; });
</script>
<?php include __DIR__ . '/footer.php'; ?>
