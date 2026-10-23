<?php
$pageTitle = 'Community'; $active = 'explore';
require __DIR__ . '/header.php';
$uid = (int)$user['id'];
$st = db()->prepare('SELECT p.id, p.content, p.post_type, p.likes_count, p.created_at, u.name, u.tier_id, (pl.user_id IS NOT NULL) AS liked
  FROM posts p JOIN users u ON u.id = p.user_id LEFT JOIN post_likes pl ON pl.post_id = p.id AND pl.user_id = ?
  ORDER BY p.created_at DESC LIMIT 50');
$st->execute([$uid]);
$posts = array_map(function ($p) { $p['ago'] = time_ago($p['created_at']); unset($p['created_at']); $p['liked'] = (int)$p['liked']; $p['likes_count'] = (int)$p['likes_count']; return $p; }, $st->fetchAll());
$canPost = tier_rank($tier) >= 1;
?>
<h1 class="text-2xl font-extrabold text-deepteal sm:text-3xl">Community</h1>
<p class="text-sm text-slate-500">Celebrate wins, share calm moments, and swap healthy meal ideas.</p>

<div class="mx-auto mt-5 max-w-2xl space-y-5">
  <section class="glass p-5">
    <?php if ($canPost): ?>
      <form id="postForm">
        <textarea id="postText" maxlength="500" rows="3" class="input resize-none" placeholder="What's your win today, <?= e(explode(' ', trim($user['name']))[0]) ?>?"></textarea>
        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
          <div class="flex flex-wrap gap-2" id="tagPick">
            <?php foreach (['Workout', 'MentalHealth', 'Nutrition'] as $i => $t): ?><button type="button" data-tag="<?= $t ?>" class="chip">#<?= $t ?></button><?php endforeach; ?>
          </div>
          <div class="flex items-center gap-3"><span id="charCt" class="text-xs text-slate-400">0/500</span><button class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Post</button></div>
        </div>
      </form>
    <?php else: ?>
      <div class="flex flex-col items-center gap-2 text-center"><span class="grid h-11 w-11 place-items-center rounded-2xl bg-mint text-sage"><i class="fa-solid fa-lock"></i></span>
        <p class="text-sm font-semibold text-deepteal">Posting and liking unlock with Silver</p><a href="profile.php#plans" class="btn btn-primary">See plans</a></div>
    <?php endif; ?>
  </section>

  <div class="flex gap-2 overflow-x-auto" id="filters">
    <?php foreach (['All', 'Workout', 'MentalHealth', 'Nutrition'] as $f): ?><button data-f="<?= $f ?>" class="chip whitespace-nowrap"><?= $f === 'All' ? 'All posts' : '#' . $f ?></button><?php endforeach; ?>
  </div>
  <div id="feed" class="space-y-4"></div>
  <p id="emptyFeed" class="hidden text-center text-sm text-slate-400">Nothing here yet — be the first to post 🌱</p>
</div>

<script>
const POSTS = <?= json_encode($posts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, CAN = <?= json_encode($canPost) ?>;
const $ = id => document.getElementById(id);
const TAGC = { Workout: 'bg-emerald-100 text-emerald-700', MentalHealth: 'bg-indigo-100 text-indigo-700', Nutrition: 'bg-orange-100 text-orange-700' };
let filter = 'All', tag = 'Workout';

function postHtml(p) {
  return '<article class="glass fade-in p-5" data-type="' + esc(p.post_type) + '" data-id="' + p.id + '">' +
    '<div class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-full bg-deepteal font-bold text-white">' + esc(p.name.charAt(0).toUpperCase()) + '</span>' +
    '<div class="min-w-0 flex-1"><p class="truncate text-sm font-bold text-deepteal">' + esc(p.name) + ' <span class="ml-1 rounded-full bg-mint px-2 py-0.5 text-[10px] font-extrabold text-sage">' + esc(p.tier_id.toUpperCase()) + '</span></p><p class="text-xs text-slate-400">' + esc(p.ago) + '</p></div>' +
    '<span class="rounded-full px-2.5 py-1 text-[11px] font-bold ' + (TAGC[p.post_type] || '') + '">#' + esc(p.post_type) + '</span></div>' +
    '<p class="mt-3 whitespace-pre-line text-sm leading-relaxed">' + esc(p.content) + '</p>' +
    '<button onclick="like(' + p.id + ', this)" class="likeBtn mt-4 inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-semibold transition active:scale-90 ' + (p.liked ? 'bg-red-50 text-red-500' : 'bg-slate-100 text-slate-500 hover:bg-red-50 hover:text-red-400') + '">' +
    '<i class="' + (p.liked ? 'fa-solid' : 'fa-regular') + ' fa-heart"></i><span class="cnt">' + p.likes_count + '</span></button></article>';
}
function drawFeed() {
  const list = POSTS.filter(p => filter === 'All' || p.post_type === filter);
  $('feed').innerHTML = list.map(postHtml).join(''); $('emptyFeed').classList.toggle('hidden', list.length > 0);
  document.querySelectorAll('#filters button').forEach(b => b.className = 'chip whitespace-nowrap ' + (b.dataset.f === filter ? '!bg-sage !text-white' : ''));
}
async function like(id, btn) {
  if (!CAN) { toast('Upgrade to Silver to like posts.', 'info'); return; }
  const j = await api('like_post', { id }); if (!j) return;
  const p = POSTS.find(x => x.id === id); p.liked = j.liked ? 1 : 0; p.likes_count = j.likes;
  btn.className = 'likeBtn mt-4 inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-semibold transition active:scale-90 ' + (j.liked ? 'bg-red-50 text-red-500' : 'bg-slate-100 text-slate-500 hover:bg-red-50 hover:text-red-400');
  btn.innerHTML = '<i class="' + (j.liked ? 'fa-solid' : 'fa-regular') + ' fa-heart ' + (j.liked ? 'animate-bounce' : '') + '"></i><span class="cnt">' + j.likes + '</span>';
}
document.querySelectorAll('#filters button').forEach(b => b.onclick = () => { filter = b.dataset.f; drawFeed(); });
drawFeed();

if (CAN) {
  const paintTags = () => document.querySelectorAll('#tagPick button').forEach(b => b.className = 'chip ' + (b.dataset.tag === tag ? '!bg-sage !text-white' : ''));
  document.querySelectorAll('#tagPick button').forEach(b => b.onclick = () => { tag = b.dataset.tag; paintTags(); }); paintTags();
  $('postText').addEventListener('input', e => $('charCt').textContent = e.target.value.length + '/500');
  $('postForm').addEventListener('submit', async e => {
    e.preventDefault(); const text = $('postText').value.trim();
    if (text.length < 3) return toast('Write at least a few words first.', 'info');
    const j = await api('create_post', { content: text, post_type: tag }); if (!j) return;
    POSTS.unshift(j.post); $('postText').value = ''; $('charCt').textContent = '0/500'; drawFeed(); toast('Posted to the community 🌿');
  });
}
</script>
<?php include __DIR__ . '/footer.php'; ?>
