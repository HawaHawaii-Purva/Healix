<?php
$pageTitle = 'Member'; $active = 'explore';
require __DIR__ . '/header.php';
$uid = (int)$user['id']; $pdo = db(); $mid = (int)($_GET['id'] ?? $uid);
$st = $pdo->prepare('SELECT id, name, primary_goal, bio, city, created_at FROM users WHERE id = ?'); $st->execute([$mid]); $m = $st->fetch();
if (!$m) { header('Location: explore.php'); exit; }
$cnt = function (string $sql) use ($pdo, $mid) { $s = $pdo->prepare($sql); $s->execute([$mid]); return (int)$s->fetchColumn(); };
$nPosts = $cnt('SELECT COUNT(*) FROM posts WHERE user_id = ?'); $nFollowers = $cnt('SELECT COUNT(*) FROM follows WHERE following_id = ?'); $nFollowing = $cnt('SELECT COUNT(*) FROM follows WHERE follower_id = ?');
$st = $pdo->prepare('SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?'); $st->execute([$uid, $mid]); $isF = (bool)$st->fetchColumn();
$st = $pdo->prepare('SELECT p.id, p.content, p.post_type, p.image_path, p.likes_count, (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS cm FROM posts p WHERE p.user_id = ? ORDER BY p.created_at DESC LIMIT 36');
$st->execute([$mid]); $grid = $st->fetchAll();
$g = ['Workout' => 'from-orange-400 to-rose-500', 'MentalHealth' => 'from-violet-500 to-indigo-500', 'Nutrition' => 'from-lime-500 to-emerald-500', 'Yoga' => 'from-fuchsia-500 to-purple-600', 'Progress' => 'from-sky-500 to-cyan-400'];
?>
<div class="mx-auto max-w-4xl">
  <a href="explore.php" class="mb-3 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-slate-800"><i class="fa-solid fa-arrow-left"></i> Back to community</a>
  <section class="glass overflow-hidden p-0">
    <div class="h-28 bg-gradient-to-r from-pink-500 via-fuchsia-500 to-orange-400 sm:h-36"></div>
    <div class="px-5 pb-6 sm:px-8">
      <div class="-mt-12 flex flex-wrap items-end justify-between gap-3">
        <span class="story-ring rounded-full p-[4px]"><span class="block rounded-full bg-white p-[3px]"><?= avatar($m['name'], (int)$m['id'], 'h-24 w-24', 'text-4xl') ?></span></span>
        <?php if ($mid === $uid): ?><a href="profile.php" class="hx-btn hx-soft"><i class="fa-solid fa-pen"></i> Edit profile</a>
        <?php else: ?><button id="fBtn" class="hx-btn <?= $isF ? 'hx-soft' : 'hx-coral' ?>" onclick="toggleFollow()"><?= $isF ? 'Following' : 'Follow' ?></button><?php endif; ?>
      </div>
      <h1 class="mt-3 text-2xl font-extrabold text-slate-800"><?= e($m['name']) ?></h1>
      <div class="mt-1 flex flex-wrap gap-2 text-xs font-semibold text-slate-500"><span class="rounded-full bg-emerald-100 px-2.5 py-1 text-emerald-700"><i class="fa-solid fa-bullseye mr-1"></i><?= e($m['primary_goal']) ?></span>
        <?php if ($m['city']): ?><span class="rounded-full bg-sky-100 px-2.5 py-1 text-sky-700"><i class="fa-solid fa-location-dot mr-1"></i><?= e($m['city']) ?></span><?php endif; ?>
        <span class="rounded-full bg-violet-100 px-2.5 py-1 text-violet-700">Joined <?= date('M Y', strtotime($m['created_at'])) ?></span></div>
      <?php if ($m['bio']): ?><p class="mt-3 text-sm text-slate-600"><?= e($m['bio']) ?></p><?php endif; ?>
      <div class="mt-4 grid max-w-sm grid-cols-3 text-center"><div><p class="text-xl font-extrabold text-slate-800"><?= $nPosts ?></p><p class="text-xs text-slate-500">Posts</p></div>
        <div><p id="nFol" class="text-xl font-extrabold text-slate-800"><?= $nFollowers ?></p><p class="text-xs text-slate-500">Followers</p></div><div><p class="text-xl font-extrabold text-slate-800"><?= $nFollowing ?></p><p class="text-xs text-slate-500">Following</p></div></div>
    </div>
  </section>
  <div class="mt-5 grid grid-cols-3 gap-1.5 sm:gap-3">
    <?php foreach ($grid as $p): ?>
      <div class="group relative aspect-square overflow-hidden rounded-2xl shadow-sm">
        <?php if ($p['image_path']): ?><img src="<?= e($p['image_path']) ?>" alt="" class="h-full w-full object-cover" loading="lazy">
        <?php else: ?><div class="grid h-full w-full place-items-center bg-gradient-to-br <?= $g[$p['post_type']] ?? $g['Workout'] ?> p-2 text-center text-[11px] font-bold leading-tight text-white sm:p-4 sm:text-sm"><span class="line-clamp-5"><?= e($p['content']) ?></span></div><?php endif; ?>
        <div class="absolute inset-0 flex items-center justify-center gap-4 bg-black/45 text-sm font-bold text-white opacity-0 transition group-hover:opacity-100"><span><i class="fa-solid fa-heart"></i> <?= (int)$p['likes_count'] ?></span><span><i class="fa-solid fa-comment"></i> <?= (int)$p['cm'] ?></span></div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php if (!$grid): ?><p class="mt-8 text-center text-sm text-slate-400">No posts yet.</p><?php endif; ?>
</div>
<script>
let isF = <?= json_encode($isF) ?>;
async function toggleFollow() {
  const j = await api('follow_toggle', { id: <?= $mid ?> }); if (!j) return; isF = j.following;
  const b = document.getElementById('fBtn'); b.textContent = isF ? 'Following' : 'Follow'; b.className = 'hx-btn ' + (isF ? 'hx-soft' : 'hx-coral');
  document.getElementById('nFol').textContent = j.followers; toast(isF ? 'You are now following 💚' : 'Unfollowed', 'info');
}
</script>
<?php include __DIR__ . '/footer.php'; ?>
