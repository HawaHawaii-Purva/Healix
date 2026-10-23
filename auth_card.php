<?php /* Shared sign-in / registration card. Expects $tab ('login'|'register'), $error, $old. */
$old = $old ?? []; $tab = $tab ?? 'login'; $error = $error ?? '';
?>
<body class="font-sans text-slate-700 min-h-screen flex items-center justify-center p-4">
<div class="pointer-events-none fixed -top-24 -left-24 h-72 w-72 rounded-full bg-emerald-200/50 blur-3xl"></div>
<div class="pointer-events-none fixed -bottom-24 -right-24 h-80 w-80 rounded-full bg-orange-200/50 blur-3xl"></div>
<div class="glass relative w-full max-w-md p-7 sm:p-9 fade-in">
  <div class="mb-6 text-center">
    <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-sage text-2xl text-white shadow-lg"><i class="fa-solid fa-leaf"></i></span>
    <h1 class="mt-3 text-2xl font-extrabold text-deepteal">Healix</h1>
    <p class="text-sm text-slate-500">Breathe in. Move well. Live lighter.</p>
  </div>
  <div class="mb-5 grid grid-cols-2 rounded-2xl bg-mint p-1 text-sm font-semibold">
    <button type="button" id="tab-login" onclick="showTab('login')" class="rounded-xl py-2 transition">Sign In</button>
    <button type="button" id="tab-register" onclick="showTab('register')" class="rounded-xl py-2 transition">Register</button>
  </div>
  <?php if ($error): ?><div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><i class="fa-solid fa-circle-exclamation mr-1"></i><?= e($error) ?></div><?php endif; ?>

  <form id="form-login" method="post" action="login.php" class="space-y-4" novalidate>
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <div><label class="mb-1 block text-xs font-semibold text-slate-500">Email</label><input class="input" type="email" name="email" required autocomplete="email" value="<?= e($tab === 'login' ? ($old['email'] ?? '') : '') ?>" placeholder="you@example.com"></div>
    <div><label class="mb-1 block text-xs font-semibold text-slate-500">Password</label><input class="input" type="password" name="password" required autocomplete="current-password" placeholder="••••••••"></div>
    <button class="btn btn-primary w-full">Sign In <i class="fa-solid fa-arrow-right"></i></button>
  </form>

  <form id="form-register" method="post" action="register.php" class="hidden space-y-4" novalidate>
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <div><label class="mb-1 block text-xs font-semibold text-slate-500">Full name</label><input class="input" name="name" required maxlength="100" autocomplete="name" value="<?= e($old['name'] ?? '') ?>" placeholder="Your name"></div>
    <div><label class="mb-1 block text-xs font-semibold text-slate-500">Email</label><input class="input" type="email" name="email" required autocomplete="email" value="<?= e($tab === 'register' ? ($old['email'] ?? '') : '') ?>" placeholder="you@example.com"></div>
    <div class="grid grid-cols-2 gap-3">
      <div><label class="mb-1 block text-xs font-semibold text-slate-500">Password</label><input class="input" type="password" name="password" required minlength="8" autocomplete="new-password" placeholder="Min 8 chars"></div>
      <div><label class="mb-1 block text-xs font-semibold text-slate-500">Confirm</label><input class="input" type="password" name="confirm" required autocomplete="new-password" placeholder="Repeat"></div>
    </div>
    <div><label class="mb-1 block text-xs font-semibold text-slate-500">Primary goal</label>
      <select class="input" name="primary_goal"><?php foreach (GOALS_LIST as $g): ?><option <?= ($old['primary_goal'] ?? '') === $g ? 'selected' : '' ?>><?= e($g) ?></option><?php endforeach; ?></select></div>
    <button class="btn btn-primary w-full">Create Account <i class="fa-solid fa-seedling"></i></button>
  </form>
</div>
<script>
function showTab(t) {
  ['login', 'register'].forEach(k => {
    document.getElementById('form-' + k).classList.toggle('hidden', k !== t);
    document.getElementById('tab-' + k).className = 'rounded-xl py-2 transition ' + (k === t ? 'bg-white text-deepteal shadow-sm' : 'text-slate-500');
  });
  document.getElementById('form-' + t).classList.add('fade-in');
}
showTab('<?= e($tab) ?>');
</script>
</body>
</html>
