</main>
</div>

<!-- Floating AI assistant: hidden until the button is pressed -->
<button id="fab" onclick="toggleAI()" aria-label="Open Healix AI"
  class="fixed bottom-20 right-4 lg:bottom-8 lg:right-8 z-40 grid h-14 w-14 place-items-center rounded-full bg-gradient-to-br from-violet-500 via-fuchsia-500 to-orange-400 text-xl text-white shadow-xl shadow-fuchsia-500/40 transition hover:scale-110 active:scale-95">
  <span class="absolute inset-0 rounded-full bg-fuchsia-400/40 ripple"></span><i class="fa-solid fa-wand-magic-sparkles relative"></i>
</button>
<div id="aiPanel" class="glass fixed z-40 right-4 lg:right-8 bottom-36 lg:bottom-28 hidden h-[30rem] max-h-[70vh] w-[calc(100%-2rem)] sm:w-96 flex-col overflow-hidden translate-y-6 opacity-0 transition-all duration-300">
  <div class="flex items-center justify-between bg-gradient-to-r from-violet-600 via-fuchsia-500 to-orange-400 px-4 py-3 text-white">
    <div class="flex items-center gap-2"><i class="fa-solid fa-robot"></i><div><p class="text-sm font-bold leading-none">Healix AI</p><p class="mt-1 flex items-center gap-1 text-[10px] text-white/80"><span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-300"></span>Online · knows your stats</p></div></div>
    <button onclick="toggleAI(false)" class="xclose" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
  </div>
  <div id="aiMsgs" class="flex-1 space-y-3 overflow-y-auto bg-violet-50/60 p-4"></div>
  <div class="flex gap-1.5 overflow-x-auto border-t border-white/60 bg-white/70 px-3 py-2 no-scrollbar">
    <?php foreach (['How is my health score?', 'Water goal status', 'Quick stress relief', 'Give me a 10-min stretch routine'] as $q): ?>
      <button class="chip whitespace-nowrap" onclick="askFab(<?= e(json_encode($q)) ?>)"><?= e($q) ?></button>
    <?php endforeach; ?>
  </div>
  <form id="aiForm" class="flex gap-2 border-t border-white/60 bg-white/70 p-3">
    <input id="aiInput" maxlength="400" autocomplete="off" placeholder="Ask about workouts, sleep, nutrition…" class="input !py-2">
    <button class="hx-btn hx-violet !px-4" aria-label="Send"><i class="fa-solid fa-paper-plane"></i></button>
  </form>
</div>
<script>
(() => {
  const panel = document.getElementById('aiPanel'), box = document.getElementById('aiMsgs');
  let greeted = false, busy = false, isOpen = false;
  window.toggleAI = (force) => {
    isOpen = force === undefined ? !isOpen : force;
    if (isOpen) {
      panel.classList.remove('hidden'); panel.classList.add('flex');
      requestAnimationFrame(() => requestAnimationFrame(() => panel.classList.remove('opacity-0', 'translate-y-6')));
      if (!greeted) { greeted = true; addMsg(box, 'ai', 'Hi <?= e(explode(' ', trim($user['name']))[0]) ?> 🌿 I\'m Healix AI. Ask me about workouts, hydration, sleep or nutrition — I can see your stats for today.'); }
      document.getElementById('aiInput').focus();
    } else {
      panel.classList.add('opacity-0', 'translate-y-6');
      setTimeout(() => { if (!isOpen) { panel.classList.add('hidden'); panel.classList.remove('flex'); } }, 300);
    }
  };
  window.askFab = async (q) => { if (busy) return; busy = true; await chatAI(box, q); busy = false; };
  document.getElementById('aiForm').addEventListener('submit', async e => {
    e.preventDefault(); const i = document.getElementById('aiInput'), q = i.value.trim();
    if (!q) return; i.value = ''; await askFab(q);
  });
})();
</script>
</body>
</html>
