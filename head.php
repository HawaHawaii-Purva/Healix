<?php /* Shared <head>: Bootstrap 5 + Tailwind (CDN), fonts, icons, design system, global JS helpers. Expects $pageTitle. */ ?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf" content="<?= e(csrf_token()) ?>">
<meta name="theme-color" content="#10b981">
<title><?= e($pageTitle ?? 'Healix') ?> · Healix</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<!-- Bootstrap first, Tailwind after (Tailwind runs with important:true so its utilities win over Bootstrap's) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = { important: true, theme: { extend: {
  fontFamily: { sans: ['"Plus Jakarta Sans"', 'Inter', 'system-ui', 'sans-serif'] },
  colors: { ice: '#F4F7F6', mint: '#E8F1EE', sage: '#2E6B56', deepteal: '#1F4E4A', coral: '#E07A5F' }
}}};
</script>
<style type="text/tailwindcss">
@layer components {
  .glass { @apply bg-white/85 backdrop-blur-md border border-white/70 shadow-lg shadow-emerald-900/5 rounded-3xl; }
  .lift { @apply transition duration-300 hover:-translate-y-1 hover:shadow-xl; }
  .hx-btn { @apply inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-semibold transition active:scale-95 disabled:opacity-50 disabled:pointer-events-none; }
  .hx-primary { @apply bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-md shadow-emerald-500/30 hover:brightness-110; }
  .hx-soft { @apply bg-emerald-100 text-emerald-800 hover:bg-emerald-200; }
  .hx-coral { @apply bg-gradient-to-r from-orange-400 to-pink-500 text-white shadow-md shadow-pink-500/30 hover:brightness-110; }
  .hx-violet { @apply bg-gradient-to-r from-violet-500 to-fuchsia-500 text-white shadow-md shadow-fuchsia-500/30 hover:brightness-110; }
  .hx-sky { @apply bg-gradient-to-r from-sky-500 to-indigo-500 text-white shadow-md shadow-sky-500/30 hover:brightness-110; }
  .input { @apply w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200; }
  .chip { @apply rounded-full border border-emerald-200 bg-white/80 px-3 py-1.5 text-xs font-semibold text-emerald-800 transition hover:bg-emerald-50; }
  .tile { @apply grid place-items-center rounded-2xl text-white shadow-md; }
  .xclose { @apply grid h-9 w-9 shrink-0 place-items-center rounded-full bg-white/20 text-white transition hover:bg-white/35; }
}
</style>
<style>
body {
  background:
    radial-gradient(900px 520px at 6% -6%, rgba(52,211,153,.38), transparent 60%),
    radial-gradient(760px 520px at 98% 2%, rgba(244,114,182,.30), transparent 60%),
    radial-gradient(820px 620px at 50% 108%, rgba(251,191,36,.28), transparent 60%),
    radial-gradient(640px 420px at 100% 72%, rgba(56,189,248,.30), transparent 60%),
    radial-gradient(560px 380px at 0% 70%, rgba(167,139,250,.26), transparent 60%), #F4F7F6;
  background-attachment: fixed;
}
a, a:hover { color: inherit; text-decoration: none; }
@keyframes fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
.fade-in { animation: fadeUp .4s ease both; }
@keyframes ripple { 0% { transform: scale(1); opacity: .5; } 100% { transform: scale(1.9); opacity: 0; } }
.ripple { animation: ripple 1.8s ease-out infinite; }
@keyframes heartPop { 0% { transform: scale(0); opacity: 0; } 30% { transform: scale(1.25); opacity: 1; } 70% { transform: scale(1); opacity: 1; } 100% { transform: scale(1.1); opacity: 0; } }
.heart-pop { animation: heartPop .8s ease both; }
.story-ring { background: conic-gradient(from 200deg, #f59e0b, #ec4899, #8b5cf6, #06b6d4, #f59e0b); }
.no-scrollbar::-webkit-scrollbar { display: none; } .no-scrollbar { scrollbar-width: none; }
.progress { background: rgba(0,0,0,.06); border-radius: 999px; }
.progress-bar { border-radius: 999px; transition: width .7s ease; }
</style>
<script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const CSRF = document.querySelector('meta[name=csrf]').content;
const AVG = <?= json_encode(AV_GRADS) ?>;
const avGrad = id => 'linear-gradient(135deg,' + AVG[id % AVG.length] + ')';
const sleep = ms => new Promise(r => setTimeout(r, ms));
const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

/** Tiny formatter for AI/chat text: **bold**, "- " bullets, paragraphs (input is escaped first). */
function fmt(t) {
  let out = '', inList = false;
  for (const l of esc(t).split('\n')) {
    if (l.startsWith('- ')) {
      if (!inList) { out += '<ul class="list-disc pl-5 space-y-1 my-1">'; inList = true; }
      out += '<li>' + l.slice(2) + '</li>';
    } else {
      if (inList) { out += '</ul>'; inList = false; }
      if (l.trim()) out += '<p class="my-1">' + l + '</p>';
    }
  }
  if (inList) out += '</ul>';
  return out.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
}

async function api(action, data = {}, url = 'api.php') {
  try {
    const r = await fetch(url, { method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
      body: JSON.stringify({ action, ...data }) });
    if (r.status === 401) { location.href = 'login.php'; return null; }
    const j = await r.json().catch(() => null);
    if (!j || !r.ok || j.ok === false) { toast((j && j.error) || 'Something went wrong. Please try again.', 'error'); return null; }
    return j;
  } catch (e) { toast('Network error — please check your connection.', 'error'); return null; }
}

function toast(msg, type = 'success') {
  let c = document.getElementById('toasts');
  if (!c) {
    c = document.createElement('div'); c.id = 'toasts';
    c.className = 'fixed top-4 right-4 z-[3000] flex flex-col gap-2 w-[calc(100%-2rem)] max-w-sm pointer-events-none';
    document.body.appendChild(c);
  }
  const cfg = { success: ['bg-emerald-50 border-emerald-200 text-emerald-800', 'fa-circle-check text-emerald-600'],
    error: ['bg-red-50 border-red-200 text-red-700', 'fa-circle-exclamation text-red-500'],
    info: ['bg-white border-slate-200 text-slate-700', 'fa-leaf text-emerald-600'] }[type] || ['bg-white border-slate-200', 'fa-leaf'];
  const t = document.createElement('div');
  t.className = 'pointer-events-auto flex items-start gap-3 rounded-2xl border px-4 py-3 text-sm font-medium shadow-lg backdrop-blur-md transition-all duration-300 translate-x-8 opacity-0 ' + cfg[0];
  t.innerHTML = '<i class="fa-solid ' + cfg[1] + ' mt-0.5"></i><span>' + esc(msg) + '</span>';
  c.appendChild(t);
  requestAnimationFrame(() => t.classList.remove('translate-x-8', 'opacity-0'));
  setTimeout(() => { t.classList.add('translate-x-8', 'opacity-0'); setTimeout(() => t.remove(), 300); }, 3600);
}

/* Modals: Bootstrap modals (class "modal") are centered with a close button; legacy overlays use the hidden/flex toggle. */
function bsModal(id) {
  const m = document.getElementById(id);
  if (!m.classList.contains('modal')) return null;
  if (m.parentElement !== document.body) document.body.appendChild(m);
  return bootstrap.Modal.getOrCreateInstance(m);
}
function openModal(id) {
  const b = bsModal(id); if (b) return b.show();
  const m = document.getElementById(id); m.classList.remove('hidden'); m.classList.add('flex'); document.body.classList.add('overflow-hidden');
}
function closeModal(id) {
  const b = bsModal(id); if (b) return b.hide();
  const m = document.getElementById(id); m.classList.add('hidden'); m.classList.remove('flex'); document.body.classList.remove('overflow-hidden');
}

function addMsg(box, role, text) {
  const d = document.createElement('div');
  d.className = 'flex fade-in ' + (role === 'user' ? 'justify-end' : 'justify-start');
  d.innerHTML = '<div class="max-w-[88%] px-4 py-2.5 text-sm rounded-2xl ' + (role === 'user' ? 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-br-md' : 'bg-white text-slate-700 border border-emerald-100 rounded-bl-md shadow-sm') + '">' + (role === 'user' ? esc(text) : fmt(text)) + '</div>';
  box.appendChild(d); box.scrollTop = box.scrollHeight; return d;
}
function typing(box, label = 'Healix AI is typing') {
  const d = document.createElement('div');
  d.className = 'flex justify-start fade-in';
  d.innerHTML = '<div class="px-4 py-2.5 rounded-2xl rounded-bl-md bg-white border border-emerald-100 text-xs text-slate-500 flex items-center gap-2"><span class="flex gap-1"><span class="block h-1.5 w-1.5 rounded-full bg-emerald-500 animate-bounce"></span><span class="block h-1.5 w-1.5 rounded-full bg-pink-400 animate-bounce [animation-delay:.15s]"></span><span class="block h-1.5 w-1.5 rounded-full bg-violet-400 animate-bounce [animation-delay:.3s]"></span></span>' + esc(label) + '...</div>';
  box.appendChild(d); box.scrollTop = box.scrollHeight; return d;
}
/** Ask the Healix mock AI and render the exchange inside `box`. */
async function chatAI(box, prompt) {
  addMsg(box, 'user', prompt);
  const t = typing(box);
  const [j] = await Promise.all([api('ask', { prompt }, 'ai_mock.php'), sleep(1100)]);
  t.remove();
  addMsg(box, 'ai', j ? j.reply : "Sorry, I couldn't reach Healix AI right now. Please try again.");
}
</script>
</head>
