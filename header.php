<?php
/* Authenticated layout: sidebar (desktop), top bar + bottom tab bar (mobile). Set $pageTitle and $active before including. */
require_once __DIR__ . '/config.php';
$user = require_login();
$tier = $user['tier_id'];
$active = $active ?? '';
include __DIR__ . '/head.php';
$nav = [
    ['index.php', 'Home', 'fa-house', 'home'],
    ['exercise.php', 'Exercise', 'fa-dumbbell', 'exercise'],
    ['meditation.php', 'Meditate', 'fa-spa', 'meditation'],
    ['consultation.php', 'Doctors', 'fa-user-doctor', 'consultation'],
    ['explore.php', 'Community', 'fa-people-group', 'explore'],
    ['profile.php', 'Profile', 'fa-user', 'profile'],
];
$tierStyle = ['Free' => 'bg-slate-200 text-slate-600', 'Silver' => 'bg-slate-300 text-slate-700', 'Gold' => 'bg-amber-200 text-amber-800', 'Platinum' => 'bg-gradient-to-r from-deepteal to-sage text-white'];
$initial = mb_strtoupper(mb_substr($user['name'], 0, 1));
$tierPill = '<span class="rounded-full px-2.5 py-0.5 text-[10px] font-extrabold tracking-wider ' . $tierStyle[$tier] . '">' . strtoupper($tier) . ($tier === 'Free' ? ' PLAN' : ' MEMBER') . '</span>';
?>
<body class="font-sans text-slate-700 min-h-screen">

<!-- Desktop sidebar -->
<aside class="hidden lg:flex fixed inset-y-0 left-0 w-64 p-4 z-30">
  <div class="glass flex h-full w-full flex-col p-5">
    <a href="index.php" class="flex items-center gap-3 px-2">
      <span class="grid h-10 w-10 place-items-center rounded-2xl bg-sage text-white"><i class="fa-solid fa-leaf"></i></span>
      <span class="text-xl font-extrabold text-deepteal">Healix</span>
    </a>
    <nav class="mt-8 flex-1 space-y-1">
      <?php foreach ($nav as $n): $on = $active === $n[3]; ?>
        <a href="<?= $n[0] ?>" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-semibold transition <?= $on ? 'bg-sage text-white shadow' : 'text-slate-600 hover:bg-mint' ?>">
          <i class="fa-solid <?= $n[2] ?> w-5 text-center"></i><?= $n[1] ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="rounded-2xl bg-mint/70 p-3">
      <div class="flex items-center gap-3">
        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-deepteal font-bold text-white"><?= e($initial) ?></span>
        <div class="min-w-0"><p class="truncate text-sm font-bold text-deepteal"><?= e($user['name']) ?></p><?= $tierPill ?></div>
      </div>
      <a href="logout.php" class="mt-3 flex items-center justify-center gap-2 rounded-xl bg-white/70 py-2 text-xs font-semibold text-slate-600 hover:bg-white"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sign out</a>
    </div>
  </div>
</aside>

<!-- Mobile top bar -->
<header class="lg:hidden sticky top-0 z-30 flex items-center justify-between border-b border-white/50 bg-white/70 px-4 py-3 backdrop-blur-md">
  <a href="index.php" class="flex items-center gap-2 font-extrabold text-deepteal"><span class="grid h-8 w-8 place-items-center rounded-xl bg-sage text-white text-sm"><i class="fa-solid fa-leaf"></i></span>Healix</a>
  <a href="profile.php" class="flex items-center gap-2"><?= $tierPill ?><span class="grid h-8 w-8 place-items-center rounded-full bg-deepteal text-sm font-bold text-white"><?= e($initial) ?></span></a>
</header>

<!-- Mobile bottom tab bar -->
<nav class="lg:hidden fixed bottom-0 inset-x-0 z-30 border-t border-white/60 bg-white/85 backdrop-blur-md pb-[env(safe-area-inset-bottom)]">
  <div class="grid grid-cols-5">
    <?php foreach (array_slice($nav, 0, 5) as $n): $on = $active === $n[3]; ?>
      <a href="<?= $n[0] ?>" class="flex flex-col items-center gap-0.5 py-2.5 text-[11px] font-semibold <?= $on ? 'text-sage' : 'text-slate-400' ?>">
        <i class="fa-solid <?= $n[2] ?> text-lg <?= $on ? '-translate-y-0.5' : '' ?> transition"></i><?= $n[1] ?>
      </a>
    <?php endforeach; ?>
  </div>
</nav>

<div class="lg:pl-64">
<main class="mx-auto max-w-6xl p-4 pb-28 lg:p-8 lg:pb-12 fade-in">
