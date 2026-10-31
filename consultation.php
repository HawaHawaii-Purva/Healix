<?php
$pageTitle = 'Consultations'; $active = 'consultation';
require __DIR__ . '/header.php';
$uid = (int)$user['id']; $pdo = db(); $rank = tier_rank($tier);
$specs = ['All', 'Cardiologist', 'Nutritionist', 'Mental Health Therapist', 'General Physician', 'Physiotherapist', 'Dermatologist', 'Endocrinologist', 'Sports Medicine', 'Yoga Therapist'];
$specIcon = ['Cardiologist' => 'fa-heart-pulse', 'Nutritionist' => 'fa-apple-whole', 'Mental Health Therapist' => 'fa-brain', 'General Physician' => 'fa-stethoscope', 'Physiotherapist' => 'fa-person-walking', 'Dermatologist' => 'fa-hand-sparkles', 'Endocrinologist' => 'fa-vial', 'Sports Medicine' => 'fa-futbol', 'Yoga Therapist' => 'fa-spa'];
$specGrad = ['Cardiologist' => 'from-rose-500 to-pink-500', 'Nutritionist' => 'from-lime-500 to-emerald-500', 'Mental Health Therapist' => 'from-violet-500 to-fuchsia-500', 'General Physician' => 'from-sky-500 to-blue-600', 'Physiotherapist' => 'from-orange-500 to-amber-500', 'Dermatologist' => 'from-pink-400 to-orange-400', 'Endocrinologist' => 'from-teal-500 to-cyan-500', 'Sports Medicine' => 'from-indigo-500 to-sky-500', 'Yoga Therapist' => 'from-fuchsia-500 to-purple-600'];
$docs = $pdo->query('SELECT * FROM doctors ORDER BY specialty, rating DESC')->fetchAll();
$st = $pdo->prepare("SELECT a.*, d.name AS dname, d.specialty, d.avatar_url FROM appointments a JOIN doctors d ON d.id = a.doctor_id WHERE a.user_id = ? AND a.status = 'upcoming' ORDER BY a.date, a.time_slot");
$st->execute([$uid]); $upcoming = $st->fetchAll();
$st = $pdo->prepare("SELECT a.*, d.name AS dname, d.specialty FROM appointments a JOIN doctors d ON d.id = a.doctor_id WHERE a.user_id = ? AND a.status = 'completed' ORDER BY a.date DESC LIMIT 4");
$st->execute([$uid]); $past = $st->fetchAll();
?>
<section class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-sky-500 via-indigo-500 to-violet-600 p-6 text-white shadow-xl shadow-indigo-500/30 sm:p-8"><div class="pointer-events-none absolute -right-10 -top-10 h-48 w-48 rounded-full bg-white/20 blur-2xl"></div><h1 class="relative text-2xl font-extrabold sm:text-3xl">Consultation Hub</h1><p class="relative text-sm text-white/85"><?= count($docs) ?> verified specialists · video or chat · pick a slot that suits you.</p></section>

<?php if ($upcoming): ?>
<section class="mt-5">
  <h2 class="mb-3 font-bold text-deepteal"><i class="fa-solid fa-calendar-check mr-2 text-sage"></i>Upcoming appointments</h2>
  <div class="grid gap-4 md:grid-cols-2">
    <?php foreach ($upcoming as $a): $isToday = $a['date'] === date('Y-m-d');
      $data = ['id' => (int)$a['id'], 'name' => $a['dname'], 'avatar' => $a['avatar_url'], 'spec' => $a['specialty'], 'code' => $a['meeting_code'], 'type' => $a['type']]; ?>
      <article class="glass lift flex items-center gap-4 p-4">
        <img src="<?= e($a['avatar_url']) ?>" alt="" class="h-14 w-14 rounded-2xl object-cover">
        <div class="min-w-0 flex-1">
          <p class="truncate font-bold text-deepteal"><?= e($a['dname']) ?></p>
          <p class="text-xs text-slate-500"><?= $a['type'] === 'video' ? '📹 Video' : '💬 Chat' ?> · <?= date('D, d M', strtotime($a['date'])) ?> · <?= e($a['time_slot']) ?></p>
          <p class="mt-0.5 text-[11px] text-slate-400">Code <?= e($a['meeting_code']) ?>
            <?php if ($isToday): ?><span class="ml-1 inline-flex items-center gap-1 font-bold text-sage"><span class="h-1.5 w-1.5 animate-pulse rounded-full bg-sage"></span>Today</span><?php endif; ?></p>
        </div>
        <button class="hx-btn hx-primary shrink-0 !px-3" data-appt="<?= e(json_encode($data)) ?>" onclick="<?= $a['type'] === 'video' ? 'joinVideo(this)' : 'openChat(this)' ?>">
          <?= $a['type'] === 'video' ? '<i class="fa-solid fa-video"></i> Join Meeting' : '<i class="fa-solid fa-comments"></i> Open Chat' ?></button>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<div class="relative mt-6"><i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i><input id="docSearch" class="input !pl-11" placeholder="Search by doctor, specialty or qualification…" autocomplete="off"></div>
<div class="mt-3 flex gap-2 overflow-x-auto pb-1 no-scrollbar" id="specFilters">
  <?php foreach ($specs as $s): ?><button data-s="<?= e($s) ?>" class="chip whitespace-nowrap"><?= e($s === 'All' ? 'All specialists' : $s) ?></button><?php endforeach; ?>
</div>

<section class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3" id="docGrid">
  <?php foreach ($docs as $d): $nx = next_available($d['available_days']); ?>
    <article class="glass lift flex flex-col overflow-hidden p-0" data-spec="<?= e($d['specialty']) ?>" data-text="<?= e(mb_strtolower($d['name'] . ' ' . $d['specialty'] . ' ' . $d['qualification'])) ?>">
      <div class="flex items-center gap-4 bg-gradient-to-r <?= $specGrad[$d['specialty']] ?? 'from-sky-500 to-indigo-500' ?> p-4 text-white">
        <img src="<?= e($d['avatar_url']) ?>" alt="" class="h-16 w-16 rounded-2xl border-2 border-white/60 object-cover" loading="lazy">
        <div class="min-w-0"><h3 class="truncate font-bold"><?= e($d['name']) ?></h3><p class="text-xs font-semibold text-white/90"><i class="fa-solid <?= $specIcon[$d['specialty']] ?? 'fa-stethoscope' ?> mr-1"></i><?= e($d['specialty']) ?></p><p class="truncate text-xs text-white/80"><?= e($d['qualification']) ?></p></div>
      </div>
      <div class="flex flex-1 flex-col p-4">
      <div class="mt-4 grid grid-cols-3 gap-2 text-center text-xs">
        <div class="rounded-xl bg-amber-50 p-2"><p class="font-extrabold text-amber-600"><i class="fa-solid fa-star"></i> <?= e($d['rating']) ?></p><p class="text-slate-500">Rating</p></div>
        <div class="rounded-xl bg-mint/70 p-2"><p class="font-extrabold text-deepteal"><?= (int)$d['experience_yrs'] ?> yrs</p><p class="text-slate-500">Experience</p></div>
        <div class="rounded-xl bg-orange-50 p-2"><p class="font-extrabold text-coral"><?= inr((int)$d['fee_inr']) ?></p><p class="text-slate-500">Fee</p></div>
      </div>
      <p class="mt-3 flex items-center gap-2 text-xs text-slate-500"><span class="h-2 w-2 animate-pulse rounded-full bg-emerald-400"></span>Next available: <strong class="text-deepteal"><?= e($nx['label']) ?></strong></p>
      <button class="hx-btn hx-primary mt-4 w-full" data-doc="<?= e(json_encode(['id' => (int)$d['id'], 'name' => $d['name'], 'spec' => $d['specialty'], 'fee' => (int)$d['fee_inr'], 'avatar' => $d['avatar_url'], 'days' => $d['available_days'], 'next' => $nx['date']])) ?>" onclick="openBooking(this)"><i class="fa-regular fa-calendar-plus"></i> Book Appointment</button>
      </div>
    </article>
  <?php endforeach; ?>
</section>

<?php if ($past): ?>
<section class="glass mt-8 p-5"><h2 class="mb-2 font-bold text-deepteal"><i class="fa-solid fa-clock-rotate-left mr-2 text-sage"></i>Past consultations</h2>
  <ul class="divide-y divide-slate-100 text-sm"><?php foreach ($past as $a): ?><li class="flex justify-between py-2"><span class="font-semibold"><?= e($a['dname']) ?> <span class="text-xs font-normal text-slate-400">· <?= e($a['specialty']) ?></span></span><span class="text-xs text-slate-500"><?= date('d M Y', strtotime($a['date'])) ?> · <?= $a['type'] === 'video' ? 'Video' : 'Chat' ?></span></li><?php endforeach; ?></ul></section>
<?php endif; ?>

<!-- Booking modal -->
<div class="modal fade" id="bookModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:460px"><div class="modal-content overflow-hidden border-0" style="border-radius:1.75rem">
    <div class="flex items-center gap-3 bg-gradient-to-r from-sky-500 via-indigo-500 to-violet-500 px-5 py-4 text-white">
      <img id="bkAvatar" src="" alt="" class="h-11 w-11 rounded-xl object-cover"><div class="min-w-0 flex-1"><p id="bkName" class="truncate font-bold">Doctor</p><p id="bkSpec" class="text-xs text-emerald-100"></p></div>
      <button type="button" class="xclose" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="space-y-4 p-5">
      <div><label class="mb-1 block text-xs font-semibold text-slate-500">Date</label><input id="bkDate" type="date" class="input" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+60 days')) ?>"><p id="bkHint" class="mt-1 text-xs text-slate-400"></p></div>
      <div><label class="mb-1 block text-xs font-semibold text-slate-500">Time slot</label><div id="bkSlots" class="grid grid-cols-3 gap-2"></div></div>
      <div><label class="mb-1 block text-xs font-semibold text-slate-500">Consultation type</label>
        <div class="grid grid-cols-2 gap-2"><button type="button" data-t="video" class="typeBtn rounded-2xl border p-3 text-sm font-semibold">📹 Video Call</button><button type="button" data-t="chat" class="typeBtn rounded-2xl border p-3 text-sm font-semibold">💬 Live Chat</button></div></div>
      <div class="rounded-2xl bg-gradient-to-r from-sky-50 to-violet-50 p-3 text-sm"><div class="flex justify-between"><span class="text-slate-600">Consultation fee</span><strong id="bkFee" class="text-indigo-700">₹0</strong></div></div>
      <button id="bkConfirm" class="hx-btn hx-sky w-full" onclick="confirmBooking()"><i class="fa-solid fa-check"></i> Confirm Booking</button>
    </div>
  </div></div>
</div>

<!-- Simulated video call -->
<div id="videoModal" class="fixed inset-0 z-50 hidden flex-col bg-slate-900 text-white">
  <div class="flex items-center justify-between px-4 py-3">
    <div class="flex items-center gap-3"><span class="relative flex h-2.5 w-2.5"><span class="absolute h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span><span class="relative h-2.5 w-2.5 rounded-full bg-red-500"></span></span><div class="leading-tight"><p id="vcName" class="text-sm font-bold">Doctor</p><p class="text-xs text-slate-400">Code <span id="vcCode"></span> · <span id="vcTimer" class="tabular-nums">00:00</span></p></div></div>
    <button onclick="document.getElementById('vcChatPane').classList.toggle('hidden')" class="rounded-full bg-white/10 px-3 py-1.5 text-xs font-semibold lg:hidden"><i class="fa-solid fa-comment-dots mr-1"></i>Chat</button>
  </div>
  <div class="flex min-h-0 flex-1 gap-3 px-3 pb-3">
    <div class="relative flex-1 overflow-hidden rounded-3xl bg-gradient-to-br from-deepteal to-slate-800">
      <div class="absolute inset-0 grid place-items-center">
        <div class="relative"><span class="ripple absolute inset-0 rounded-full bg-emerald-400/30"></span><span class="ripple absolute inset-0 rounded-full bg-emerald-400/20 [animation-delay:.6s]"></span><img id="vcAvatar" src="" alt="" class="relative h-32 w-32 rounded-full border-4 border-white/20 object-cover shadow-2xl sm:h-40 sm:w-40"></div>
      </div>
      <p class="absolute bottom-4 left-4 rounded-full bg-black/40 px-3 py-1 text-xs"><i class="fa-solid fa-microphone mr-1 text-emerald-300"></i><span id="vcName2"></span></p>
      <div class="absolute bottom-4 right-4 h-32 w-24 overflow-hidden rounded-2xl border-2 border-white/30 bg-slate-700 shadow-xl sm:h-40 sm:w-32">
        <video id="selfVideo" autoplay muted playsinline class="hidden h-full w-full -scale-x-100 object-cover"></video>
        <div id="selfPh" class="grid h-full place-items-center text-center text-[10px] text-slate-300"><div><i class="fa-solid fa-video-slash mb-1 text-lg"></i><p>Camera off</p></div></div>
      </div>
    </div>
    <aside id="vcChatPane" class="absolute inset-x-3 bottom-24 top-16 z-10 hidden flex-col rounded-3xl bg-white text-slate-700 shadow-2xl lg:static lg:flex lg:w-80 lg:shrink-0">
      <p class="border-b border-slate-100 px-4 py-3 text-sm font-bold text-deepteal">In-call chat</p>
      <div id="vcMsgs" class="flex-1 space-y-3 overflow-y-auto bg-mint/40 p-3"></div>
      <form id="vcForm" class="flex gap-2 border-t border-slate-100 p-3"><input id="vcIn" class="input !py-2" placeholder="Type a message…" autocomplete="off" maxlength="300"><button class="hx-btn hx-primary !px-3" aria-label="Send"><i class="fa-solid fa-paper-plane"></i></button></form>
    </aside>
  </div>
  <div class="flex justify-center gap-4 pb-6 pt-1">
    <button id="micBtn" onclick="toggleMic()" class="grid h-14 w-14 place-items-center rounded-full bg-white/15 text-lg transition hover:bg-white/25" aria-label="Mute"><i class="fa-solid fa-microphone"></i></button>
    <button id="camBtn" onclick="toggleCam()" class="grid h-14 w-14 place-items-center rounded-full bg-white/15 text-lg transition hover:bg-white/25" aria-label="Camera"><i class="fa-solid fa-video"></i></button>
    <button onclick="endCall()" class="grid h-14 w-20 place-items-center rounded-full bg-red-500 text-lg transition hover:bg-red-600" aria-label="End call"><i class="fa-solid fa-phone-slash"></i></button>
  </div>
</div>

<!-- Simulated chat room -->
<div id="chatModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-deepteal/50 p-4 backdrop-blur-sm">
  <div class="glass fade-in flex h-[32rem] max-h-[85vh] w-full max-w-md flex-col overflow-hidden !bg-white">
    <div class="flex items-center gap-3 bg-gradient-to-r from-violet-600 to-fuchsia-500 px-4 py-3 text-white">
      <div class="relative"><img id="chAvatar" src="" alt="" class="h-10 w-10 rounded-full object-cover"><span class="absolute bottom-0 right-0 h-3 w-3 animate-pulse rounded-full border-2 border-white bg-emerald-400"></span></div>
      <div class="min-w-0 flex-1"><p id="chName" class="truncate text-sm font-bold"></p><p class="text-xs text-emerald-100">Online · replies in a moment</p></div>
      <button onclick="closeChat()" class="grid h-8 w-8 place-items-center rounded-full hover:bg-white/20" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div id="chMsgs" class="flex-1 space-y-3 overflow-y-auto bg-mint/40 p-4"></div>
    <form id="chForm" class="flex gap-2 border-t border-slate-100 p-3"><input id="chIn" class="input !py-2" placeholder="Message your doctor…" autocomplete="off" maxlength="300"><button class="hx-btn hx-primary !px-4" aria-label="Send"><i class="fa-solid fa-paper-plane"></i></button></form>
    <div class="flex items-center justify-between border-t border-slate-100 px-4 py-2 text-[11px] text-slate-400"><span>Simulated chat for demonstration</span><button onclick="endChat()" class="font-semibold text-coral hover:underline">End consultation</button></div>
  </div>
</div>

<script>
const $ = id => document.getElementById(id);
const SLOTS = <?= json_encode(SLOTS) ?>;
const inr = n => '₹' + n.toLocaleString('en-IN');

/* Specialty filter */
let sf = 'All';
function paintFilter() {
  document.querySelectorAll('#specFilters button').forEach(b => b.className = 'chip whitespace-nowrap ' + (b.dataset.s === sf ? '!bg-indigo-600 !text-white !border-indigo-600' : ''));
  const q = $('docSearch').value.trim().toLowerCase();
  document.querySelectorAll('#docGrid article').forEach(a => a.classList.toggle('hidden', (sf !== 'All' && a.dataset.spec !== sf) || (q && !a.dataset.text.includes(q))));
}
document.querySelectorAll('#specFilters button').forEach(b => b.onclick = () => { sf = b.dataset.s; paintFilter(); }); $('docSearch').addEventListener('input', paintFilter); paintFilter();

/* Canned doctor replies (simulation only) */
const REPLIES = {
  pain: ["I'm sorry you're dealing with that. How long has it been going on, and does anything make it better or worse?", "Thanks for sharing. Rest the area, avoid heavy lifting for now, and let me know if it spreads or worsens."],
  sleep: ["Sleep affects everything. Try a fixed bedtime, no screens 45 minutes before bed, and the 4-7-8 breathing on the Meditate page.", "How many hours are you getting on average, and do you wake up during the night?"],
  diet: ["Let's keep it simple: half the plate vegetables, a palm of protein, and whole grains. Tell me what you eat on a typical day.", "Small, steady changes work best. Which meal do you find hardest to keep healthy?"],
  stress: ["That sounds heavy, and it's good that you're talking about it. When do you notice it most — mornings, evenings, or at work?", "A short daily routine helps: 5 minutes of breathing, a walk, and a regular sleep window. Shall we build one together?"],
  med: ["For safety I can't change or prescribe medication over chat. Please share what you currently take and any side effects, and we'll review it together.", "Please don't stop any prescribed medicine on your own. Tell me what you're taking and what you've noticed."],
  def: ["Thanks for explaining. Could you tell me a little more — when did this start?", "I understand. Any other symptoms or recent changes in routine I should know about?", "Noted. Based on this, a few lifestyle tweaks should help. Shall I walk you through them?"]
};
function docReply(t) {
  t = t.toLowerCase();
  const k = /pain|ache|hurt|sore/.test(t) ? 'pain' : /sleep|tired|insomnia|fatigue/.test(t) ? 'sleep' : /diet|food|eat|weight|sugar|meal/.test(t) ? 'diet' : /stress|anx|worr|mood|sad|low/.test(t) ? 'stress' : /medic|tablet|dose|prescri|pill/.test(t) ? 'med' : 'def';
  const a = REPLIES[k]; return a[Math.floor(Math.random() * a.length)];
}
async function docSay(box, name, text, delay = 1300) { const t = typing(box, name.split(' ').slice(0, 2).join(' ') + ' is typing'); await sleep(delay); t.remove(); addMsg(box, 'ai', text); }

/* Booking */
let doc = null, slot = null, ctype = 'video';
function openBooking(btn) {
  doc = JSON.parse(btn.dataset.doc); slot = null; ctype = 'video';
  $('bkAvatar').src = doc.avatar; $('bkName').textContent = doc.name; $('bkSpec').textContent = doc.specialty + ' · ' + doc.days.replace(/,/g, ', ');
  $('bkDate').value = doc.next; $('bkSlots').innerHTML = SLOTS.map(s => '<button type="button" class="slotBtn rounded-xl border px-2 py-2 text-xs font-semibold">' + s + '</button>').join('');
  document.querySelectorAll('.slotBtn').forEach(b => b.onclick = () => { slot = b.textContent; paintBook(); });
  paintBook(); openModal('bookModal');
}
function dayOk() { const d = new Date($('bkDate').value + 'T00:00:00'); return !isNaN(d) && doc.days.split(',').includes(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][d.getDay()]); }
function paintBook() {
  document.querySelectorAll('.slotBtn').forEach(b => b.className = 'slotBtn rounded-xl border px-2 py-2 text-xs font-semibold transition ' + (b.textContent === slot ? 'border-indigo-500 bg-indigo-500 text-white' : 'border-slate-200 hover:bg-mint'));
  document.querySelectorAll('.typeBtn').forEach(b => b.className = 'typeBtn rounded-2xl border p-3 text-sm font-semibold transition ' + (b.dataset.t === ctype ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-slate-200 hover:bg-mint/60'));
  const ok = dayOk(); $('bkHint').textContent = ok ? '' : doc.name + ' is available on ' + doc.days.replace(/,/g, ', ') + '.'; $('bkHint').className = 'mt-1 text-xs ' + (ok ? 'text-slate-400' : 'text-coral');
  $('bkFee').textContent = inr(doc.fee);
}
document.querySelectorAll('.typeBtn').forEach(b => b.onclick = () => { ctype = b.dataset.t; paintBook(); });
$('bkDate').addEventListener('change', paintBook);
async function confirmBooking() {
  if (!dayOk()) return toast('Please pick a day the doctor is available.', 'error');
  if (!slot) return toast('Please choose a time slot.', 'error');
  const btn = $('bkConfirm'); btn.disabled = true;
  const j = await api('book_appointment', { doctor_id: doc.id, date: $('bkDate').value, time_slot: slot, type: ctype });
  btn.disabled = false;
  if (j) { toast(j.message); closeModal('bookModal'); setTimeout(() => location.reload(), 1800); }
}

/* Video call */
let stream = null, callIv = null, callStart = 0, appt = null;
async function joinVideo(btn) {
  appt = JSON.parse(btn.dataset.appt);
  $('vcName').textContent = $('vcName2').textContent = appt.name; $('vcCode').textContent = appt.code; $('vcAvatar').src = appt.avatar; $('vcMsgs').innerHTML = '';
  openModal('videoModal'); callStart = Date.now(); clearInterval(callIv);
  callIv = setInterval(() => { const s = Math.floor((Date.now() - callStart) / 1000); $('vcTimer').textContent = String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0'); }, 1000);
  try {
    stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
    $('selfVideo').srcObject = stream; $('selfVideo').classList.remove('hidden'); $('selfPh').classList.add('hidden');
  } catch (e) { stream = null; toast('Camera/mic unavailable — continuing without self-view.', 'info'); }
  docSay($('vcMsgs'), appt.name, 'Hello <?= e(explode(' ', trim($user['name']))[0]) ?>, I can see you\'re connected. How can I help today?', 1500);
}
function toggleMic() { if (!stream) return toast('No microphone available.', 'info'); const t = stream.getAudioTracks()[0]; if (!t) return; t.enabled = !t.enabled; $('micBtn').className = 'grid h-14 w-14 place-items-center rounded-full text-lg transition ' + (t.enabled ? 'bg-white/15 hover:bg-white/25' : 'bg-red-500/80'); $('micBtn').innerHTML = '<i class="fa-solid ' + (t.enabled ? 'fa-microphone' : 'fa-microphone-slash') + '"></i>'; }
function toggleCam() { if (!stream) return toast('No camera available.', 'info'); const t = stream.getVideoTracks()[0]; if (!t) return; t.enabled = !t.enabled; $('selfVideo').classList.toggle('hidden', !t.enabled); $('selfPh').classList.toggle('hidden', t.enabled); $('camBtn').className = 'grid h-14 w-14 place-items-center rounded-full text-lg transition ' + (t.enabled ? 'bg-white/15 hover:bg-white/25' : 'bg-red-500/80'); $('camBtn').innerHTML = '<i class="fa-solid ' + (t.enabled ? 'fa-video' : 'fa-video-slash') + '"></i>'; }
async function endCall() {
  if (stream) stream.getTracks().forEach(t => t.stop()); stream = null; clearInterval(callIv); closeModal('videoModal');
  const j = await api('complete_appointment', { id: appt.id }); if (j) { toast('Call ended. Consultation marked as completed.'); setTimeout(() => location.reload(), 1200); }
}
$('vcForm').addEventListener('submit', e => { e.preventDefault(); const q = $('vcIn').value.trim(); if (!q) return; $('vcIn').value = ''; addMsg($('vcMsgs'), 'user', q); docSay($('vcMsgs'), appt.name, docReply(q)); });

/* Chat room */
function openChat(btn) {
  appt = JSON.parse(btn.dataset.appt); $('chName').textContent = appt.name; $('chAvatar').src = appt.avatar; $('chMsgs').innerHTML = '';
  openModal('chatModal'); docSay($('chMsgs'), appt.name, 'Hi <?= e(explode(' ', trim($user['name']))[0]) ?>! I\'m ' + appt.name + '. What would you like to discuss today?', 900);
}
function closeChat() { closeModal('chatModal'); }
async function endChat() { closeModal('chatModal'); const j = await api('complete_appointment', { id: appt.id }); if (j) { toast('Consultation completed.'); setTimeout(() => location.reload(), 1000); } }
$('chForm').addEventListener('submit', e => { e.preventDefault(); const q = $('chIn').value.trim(); if (!q) return; $('chIn').value = ''; addMsg($('chMsgs'), 'user', q); docSay($('chMsgs'), appt.name, docReply(q)); });
</script>
<?php include __DIR__ . '/footer.php'; ?>
