<?php
/* Authenticated layout: sidebar (desktop), top bar + bottom tab bar (mobile). Set $pageTitle and $active before including. */
require_once __DIR__ . '/config.php';
$user = require_login();
$tier = $user['tier_id'];   // subscription info is only displayed on profile.php
$active = $active ?? '';
include __DIR__ . '/head.php';
// href, label, icon, key, gradient
$nav = [
    ['index.php', 'Home', 'fa-house', 'home', 'from-emerald-400 to-teal-500'],
    ['exercise.php', 'Exercise', 'fa-dumbbell', 'exercise', 'from-orange-400 to-rose-500'],
    ['meditation.php', 'Meditate', 'fa-spa', 'meditation', 'from-violet-400 to-fuchsia-500'],
    ['consultation.php', 'Doctors', 'fa-user-doctor', 'consultation', 'from-sky-400 to-indigo-500'],
    ['explore.php', 'Community', 'fa-heart', 'explore', 'from-pink-400 to-rose-500'],
    ['profile.php', 'Profile', 'fa-user', 'profile', 'from-amber-400 to-orange-500'],
];
?>
<body class="font-sans text-slate-700 min-h-screen">

<aside class="hidden lg:flex fixed inset-y-0 left-0 w-64 p-4 z-30">
  <div class="glass flex h-full w-full flex-col p-5">
    <a href="index.php" class="flex items-center gap-3 px-2">
      <span class="tile h-11 w-11 bg-gradient-to-br from-emerald-400 via-teal-500 to-sky-500 text-lg"><i class="fa-solid fa-leaf"></i></span>
      <span class="bg-gradient-to-r from-emerald-600 to-sky-600 bg-clip-text text-2xl font-extrabold text-transparent">Healix</span>
    </a>
    <nav class="mt-7 flex-1 space-y-1.5">
      <?php foreach ($nav as $n): $on = $active === $n[3]; ?>
        <a href="<?= $n[0] ?>" class="flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-semibold transition <?= $on ? 'bg-gradient-to-r ' . $n[4] . ' text-white shadow-lg' : 'text-slate-600 hover:bg-white' ?>">
          <span class="tile h-9 w-9 text-sm <?= $on ? 'bg-white/25' : 'bg-gradient-to-br ' . $n[4] ?>"><i class="fa-solid <?= $n[2] ?>"></i></span><?= $n[1] ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="rounded-2xl bg-gradient-to-br from-emerald-50 to-sky-50 p-3">
      <a href="profile.php" class="flex items-center gap-3"><?= avatar($user['name'], (int)$user['id'], 'h-11 w-11', 'text-base') ?>
        <div class="min-w-0"><p class="truncate text-sm font-bold text-slate-800"><?= e($user['name']) ?></p><p class="truncate text-xs text-slate-500"><?= e($user['primary_goal']) ?></p></div></a>
      <a href="logout.php" class="mt-3 flex items-center justify-center gap-2 rounded-xl bg-white py-2 text-xs font-semibold text-slate-600 shadow-sm hover:bg-rose-50 hover:text-rose-600"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sign out</a>
    </div>
  </div>
</aside>

<header class="lg:hidden sticky top-0 z-30 flex items-center justify-between border-b border-white/60 bg-white/75 px-4 py-3 backdrop-blur-md">
  <a href="index.php" class="flex items-center gap-2 text-lg font-extrabold"><span class="tile h-9 w-9 bg-gradient-to-br from-emerald-400 via-teal-500 to-sky-500 text-sm"><i class="fa-solid fa-leaf"></i></span><span class="bg-gradient-to-r from-emerald-600 to-sky-600 bg-clip-text text-transparent">Healix</span></a>
  <a href="profile.php"><?= avatar($user['name'], (int)$user['id'], 'h-9 w-9') ?></a>
</header>

<nav class="lg:hidden fixed bottom-0 inset-x-0 z-30 border-t border-white/70 bg-white/90 backdrop-blur-md pb-[env(safe-area-inset-bottom)]">
  <div class="grid grid-cols-5">
    <?php foreach (array_slice($nav, 0, 5) as $n): $on = $active === $n[3]; ?>
      <a href="<?= $n[0] ?>" class="flex flex-col items-center gap-0.5 py-2 text-[11px] font-semibold <?= $on ? 'text-slate-800' : 'text-slate-400' ?>">
        <span class="tile h-8 w-8 text-sm <?= $on ? 'bg-gradient-to-br ' . $n[4] . ' scale-110' : 'bg-slate-200 !text-slate-500 !shadow-none' ?> transition"><i class="fa-solid <?= $n[2] ?>"></i></span><?= $n[1] ?>
      </a>
    <?php endforeach; ?>
  </div>
</nav>

<div class="lg:pl-64">
<main class="mx-auto max-w-6xl p-4 pb-28 lg:p-8 lg:pb-12 fade-in">
