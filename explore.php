<?php
$pageTitle = 'Community'; $active = 'explore';
require __DIR__ . '/header.php';
$uid = (int)$user['id']; $pdo = db();
$st = $pdo->prepare('SELECT p.id, p.user_id, p.content, p.post_type, p.image_path, p.likes_count, p.created_at, u.name,
  (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comments_count,
  EXISTS(SELECT 1 FROM post_likes l WHERE l.post_id = p.id AND l.user_id = ?) AS liked,
  EXISTS(SELECT 1 FROM post_saves s WHERE s.post_id = p.id AND s.user_id = ?) AS saved
  FROM posts p JOIN users u ON u.id = p.user_id ORDER BY p.created_at DESC LIMIT 60');
$st->execute([$uid, $uid]);
$posts = array_map(fn($p) => ['id' => (int)$p['id'], 'user_id' => (int)$p['user_id'], 'name' => $p['name'], 'content' => $p['content'], 'post_type' => $p['post_type'], 'image' => $p['image_path'],
  'likes_count' => (int)$p['likes_count'], 'liked' => (int)$p['liked'], 'saved' => (int)$p['saved'], 'comments_count' => (int)$p['comments_count'], 'ago' => time_ago($p['created_at']), 'mine' => (int)$p['user_id'] === $uid], $st->fetchAll());
$st = $pdo->prepare('SELECT following_id FROM follows WHERE follower_id = ?'); $st->execute([$uid]); $following = array_map('intval', array_column($st->fetchAll(), 'following_id'));
$st = $pdo->prepare('SELECT u.id, u.name, u.primary_goal, (SELECT COUNT(*) FROM follows f WHERE f.following_id = u.id) AS followers FROM users u
  WHERE u.id <> ? AND u.id NOT IN (SELECT following_id FROM follows WHERE follower_id = ?) ORDER BY followers DESC, u.id LIMIT 6');
$st->execute([$uid, $uid]); $sugg = array_map(fn($r) => ['id' => (int)$r['id'], 'name' => $r['name'], 'goal' => $r['primary_goal'], 'followers' => (int)$r['followers']], $st->fetchAll());
$stories = array_map(fn($r) => ['uid' => (int)$r['user_id'], 'name' => $r['name'], 'content' => $r['content'], 'type' => $r['post_type'], 'image' => $r['image_path'], 'ago' => time_ago($r['created_at'])],
  $pdo->query('SELECT p.user_id, u.name, p.content, p.post_type, p.image_path, p.created_at FROM posts p JOIN users u ON u.id = p.user_id WHERE p.id IN (SELECT MAX(id) FROM posts GROUP BY user_id) ORDER BY p.created_at DESC LIMIT 12')->fetchAll());
$trend = $pdo->query('SELECT post_type, COUNT(*) AS c FROM posts GROUP BY post_type ORDER BY c DESC')->fetchAll();
$one = function (string $sql) use ($pdo, $uid) { $s = $pdo->prepare($sql); $s->execute([$uid]); return (int)$s->fetchColumn(); };
$myPosts = $one('SELECT COUNT(*) FROM posts WHERE user_id = ?'); $myFollowers = $one('SELECT COUNT(*) FROM follows WHERE following_id = ?'); $myFollowing = $one('SELECT COUNT(*) FROM follows WHERE follower_id = ?');
?>
<div class="mx-auto grid max-w-5xl gap-6 lg:grid-cols-3">
  <div class="min-w-0 lg:col-span-2">
    <!-- Stories -->
    <section class="glass p-4">
      <div class="flex gap-4 overflow-x-auto pb-1 no-scrollbar" id="stories">
        <button class="flex w-[4.5rem] shrink-0 flex-col items-center gap-1" onclick="document.getElementById('postText').focus();window.scrollTo({top:document.getElementById('composer').offsetTop-80,behavior:'smooth'})">
          <span class="relative"><?= avatar($user['name'], $uid, 'h-16 w-16', 'text-xl') ?><span class="absolute -bottom-1 -right-1 grid h-6 w-6 place-items-center rounded-full border-2 border-white bg-gradient-to-br from-pink-500 to-orange-400 text-xs text-white"><i class="fa-solid fa-plus"></i></span></span>
          <span class="w-full truncate text-center text-[11px] font-semibold text-slate-600">Your post</span>
        </button>
        <?php foreach ($stories as $i => $s): ?>
          <button class="flex w-[4.5rem] shrink-0 flex-col items-center gap-1" onclick="openStory(<?= $i ?>)">
            <span class="story-ring rounded-full p-[3px]"><span class="block rounded-full bg-white p-[2px]"><?= avatar($s['name'], $s['uid'], 'h-14 w-14', 'text-lg') ?></span></span>
            <span class="w-full truncate text-center text-[11px] font-semibold text-slate-600"><?= e(explode(' ', $s['name'])[0]) ?></span>
          </button>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- Composer -->
    <section id="composer" class="glass mt-5 p-4">
      <form id="postForm">
        <div class="flex gap-3">
          <?= avatar($user['name'], $uid, 'h-11 w-11', 'text-base') ?>
          <textarea id="postText" maxlength="500" rows="2" class="input resize-none" placeholder="Share your wins, <?= e(explode(' ', trim($user['name']))[0]) ?>… workout, meal, mood 🌿"></textarea>
        </div>
        <div id="prevWrap" class="relative mt-3 hidden"><img id="prev" alt="" class="max-h-72 w-full rounded-2xl object-cover"><button type="button" onclick="clearImg()" class="xclose absolute right-2 top-2 !bg-black/50" aria-label="Remove photo"><i class="fa-solid fa-xmark"></i></button></div>
        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
          <div class="flex flex-wrap items-center gap-2">
            <label class="hx-btn hx-soft cursor-pointer !px-3 !py-1.5 text-xs !bg-pink-100 !text-pink-700"><i class="fa-solid fa-image"></i> Photo<input id="postImg" type="file" accept="image/jpeg,image/png,image/webp" class="hidden"></label>
            <div id="tagPick" class="flex flex-wrap gap-1.5"></div>
          </div>
          <div class="flex items-center gap-3"><span id="charCt" class="text-xs text-slate-400">0/500</span><button id="postBtn" class="hx-btn hx-coral !py-2"><i class="fa-solid fa-paper-plane"></i> Share</button></div>
        </div>
      </form>
    </section>

    <!-- Mobile suggestions -->
    <section class="mt-5 lg:hidden"><p class="mb-2 text-sm font-bold text-slate-700">People to follow</p><div id="suggRow" class="flex gap-3 overflow-x-auto pb-1 no-scrollbar"></div></section>

    <!-- Tabs -->
    <div class="mt-5 grid grid-cols-3 rounded-2xl bg-white p-1 text-sm font-bold shadow-sm" id="feedTabs">
      <button data-t="foryou" class="rounded-xl py-2"><i class="fa-solid fa-fire mr-1"></i>For you</button>
      <button data-t="following" class="rounded-xl py-2"><i class="fa-solid fa-user-group mr-1"></i>Following</button>
      <button data-t="saved" class="rounded-xl py-2"><i class="fa-solid fa-bookmark mr-1"></i>Saved</button>
    </div>
    <div class="mt-3 flex gap-2 overflow-x-auto pb-1 no-scrollbar" id="tagFilter"></div>
    <div id="feed" class="mt-4 space-y-5"></div>
    <p id="emptyFeed" class="mt-8 hidden text-center text-sm text-slate-400"></p>
  </div>

  <!-- Sidebar -->
  <aside class="hidden space-y-5 lg:block">
    <div class="sticky top-6 space-y-5">
      <section class="glass overflow-hidden p-0">
        <div class="h-16 bg-gradient-to-r from-pink-500 via-fuchsia-500 to-orange-400"></div>
        <div class="-mt-8 px-5 pb-5 text-center">
          <a href="member.php?id=<?= $uid ?>" class="inline-block rounded-full border-4 border-white"><?= avatar($user['name'], $uid, 'h-16 w-16', 'text-2xl') ?></a>
          <p class="mt-1 font-bold text-slate-800"><?= e($user['name']) ?></p><p class="text-xs text-slate-500"><?= e($user['primary_goal']) ?></p>
          <div class="mt-3 grid grid-cols-3 text-center"><div><p class="font-extrabold text-slate-800" id="myPosts"><?= $myPosts ?></p><p class="text-[11px] text-slate-500">Posts</p></div><div><p class="font-extrabold text-slate-800"><?= $myFollowers ?></p><p class="text-[11px] text-slate-500">Followers</p></div><div><p class="font-extrabold text-slate-800" id="myFollowing"><?= $myFollowing ?></p><p class="text-[11px] text-slate-500">Following</p></div></div>
        </div>
      </section>
      <section class="glass p-5"><h2 class="mb-3 font-bold text-slate-800"><i class="fa-solid fa-user-plus mr-2 text-pink-500"></i>Suggested for you</h2><div id="suggSide" class="space-y-3"></div></section>
      <section class="glass p-5"><h2 class="mb-3 font-bold text-slate-800"><i class="fa-solid fa-hashtag mr-2 text-violet-500"></i>Trending</h2>
        <div class="space-y-2"><?php foreach ($trend as $t): ?><button onclick="setTag('<?= e($t['post_type']) ?>')" class="flex w-full items-center justify-between rounded-xl bg-white px-3 py-2 text-sm shadow-sm hover:bg-violet-50"><span class="font-semibold text-slate-700">#<?= e($t['post_type']) ?></span><span class="text-xs text-slate-400"><?= (int)$t['c'] ?> posts</span></button><?php endforeach; ?></div></section>
    </div>
  </aside>
</div>

<!-- Story viewer (Bootstrap modal, centered, with close button) -->
<div class="modal fade" id="storyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:380px"><div class="modal-content overflow-hidden border-0 bg-slate-900 text-white" style="border-radius:1.75rem">
    <div class="p-3">
      <div class="progress" style="height:4px;background:rgba(255,255,255,.25)"><div id="stBar" class="progress-bar" style="width:0;background:#fff;transition:none"></div></div>
      <div class="mt-3 flex items-center gap-2"><span id="stAv"></span><div class="min-w-0 flex-1 leading-tight"><a id="stName" class="block truncate text-sm font-bold"></a><p id="stAgo" class="text-xs text-white/60"></p></div>
        <button type="button" class="xclose" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button></div>
    </div>
    <div id="stBody" class="relative min-h-[26rem] cursor-pointer" onclick="storyTap(event)"></div>
  </div></div>
</div>

<script>
const POSTS = <?= json_encode($posts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const FOLLOWING = new Set(<?= json_encode($following) ?>), SUGG = <?= json_encode($sugg, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, STORIES = <?= json_encode($stories, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const ME = <?= $uid ?>, $ = id => document.getElementById(id);
const TAG = { Workout: { g: 'from-orange-400 to-rose-500', t: 'bg-orange-100 text-orange-700' }, MentalHealth: { g: 'from-violet-500 to-indigo-500', t: 'bg-violet-100 text-violet-700' },
  Nutrition: { g: 'from-lime-500 to-emerald-500', t: 'bg-lime-100 text-lime-700' }, Yoga: { g: 'from-fuchsia-500 to-purple-600', t: 'bg-fuchsia-100 text-fuchsia-700' }, Progress: { g: 'from-sky-500 to-cyan-400', t: 'bg-sky-100 text-sky-700' } };
let tab = 'foryou', tagF = 'All', tag = 'Workout';
const byId = id => POSTS.find(p => p.id === id);
const av = (id, name, size = 'h-10 w-10', text = 'text-sm') => '<span class="grid shrink-0 place-items-center rounded-full font-bold text-white ' + size + ' ' + text + '" style="background:' + avGrad(id) + '">' + esc(name.charAt(0).toUpperCase()) + '</span>';
const likesLbl = n => n + (n === 1 ? ' like' : ' likes');
const cmLbl = n => n ? 'View all ' + n + ' comment' + (n === 1 ? '' : 's') : 'Add a comment';

function followBtn(uid) {
  if (uid === ME) return '';
  const f = FOLLOWING.has(uid);
  return '<span class="text-slate-300">•</span><button class="fbtn text-xs font-bold ' + (f ? 'text-slate-400' : 'text-pink-600') + '" data-id="' + uid + '" onclick="follow(' + uid + ')">' + (f ? 'Following' : 'Follow') + '</button>';
}
function postHtml(p) {
  const t = TAG[p.post_type] || TAG.Workout;
  const media = p.image
    ? '<img src="' + esc(p.image) + '" alt="" loading="lazy" draggable="false" class="max-h-[36rem] w-full select-none object-cover">'
    : '<div class="grid min-h-[17rem] place-items-center bg-gradient-to-br ' + t.g + ' p-8 text-center text-white"><p class="' + (p.content.length > 140 ? 'text-lg' : 'text-2xl') + ' whitespace-pre-line font-extrabold leading-snug">' + esc(p.content) + '</p></div>';
  return '<article id="post-' + p.id + '" data-pid="' + p.id + '" class="glass fade-in overflow-hidden p-0">' +
    '<div class="flex items-center gap-3 p-4"><a href="member.php?id=' + p.user_id + '" class="story-ring rounded-full p-[2px]"><span class="block rounded-full bg-white p-[2px]">' + av(p.user_id, p.name) + '</span></a>' +
    '<div class="min-w-0 flex-1"><p class="flex flex-wrap items-center gap-x-1.5 text-sm"><a href="member.php?id=' + p.user_id + '" class="font-bold text-slate-800">' + esc(p.name) + '</a><span class="fwrap inline-flex items-center gap-1.5" data-u="' + p.user_id + '">' + followBtn(p.user_id) + '</span></p><p class="text-xs text-slate-400">' + esc(p.ago) + '</p></div>' +
    '<span class="rounded-full px-2.5 py-1 text-[11px] font-bold ' + t.t + '">#' + esc(p.post_type) + '</span>' +
    (p.mine ? '<button onclick="del(' + p.id + ')" class="grid h-8 w-8 place-items-center rounded-full text-slate-400 hover:bg-rose-50 hover:text-rose-500" aria-label="Delete post"><i class="fa-regular fa-trash-can"></i></button>' : '') + '</div>' +
    '<div class="relative" onclick="tap(' + p.id + ', this)">' + media + '<div class="pointer-events-none absolute inset-0 grid place-items-center"><i class="pop fa-solid fa-heart text-7xl text-white drop-shadow-lg" style="opacity:0"></i></div></div>' +
    '<div class="flex items-center gap-1 px-3 pt-3 text-xl">' +
      '<button class="hbtn grid h-10 w-10 place-items-center rounded-full transition active:scale-90 hover:bg-rose-50" onclick="like(' + p.id + ')" aria-label="Like"></button>' +
      '<button class="grid h-10 w-10 place-items-center rounded-full text-slate-700 transition hover:bg-sky-50" onclick="toggleComments(' + p.id + ')" aria-label="Comments"><i class="fa-regular fa-comment"></i></button>' +
      '<button class="grid h-10 w-10 place-items-center rounded-full text-slate-700 transition hover:bg-emerald-50" onclick="share(' + p.id + ')" aria-label="Share"><i class="fa-regular fa-paper-plane"></i></button>' +
      '<button class="sbtn ml-auto grid h-10 w-10 place-items-center rounded-full transition active:scale-90 hover:bg-amber-50" onclick="save(' + p.id + ')" aria-label="Save"></button></div>' +
    '<p class="lc px-4 text-sm font-bold text-slate-800"></p>' +
    (p.image ? '<p class="px-4 pt-1 text-sm text-slate-700"><a href="member.php?id=' + p.user_id + '" class="font-bold text-slate-800">' + esc(p.name) + '</a> ' + esc(p.content) + '</p>' : '') +
    '<button class="cbtn block px-4 pb-3 pt-1 text-left text-sm text-slate-400" onclick="toggleComments(' + p.id + ')"></button>' +
    '<div id="cm-' + p.id + '" class="hidden border-t border-slate-100 px-4 pb-4 pt-2"><div class="cl max-h-56 overflow-y-auto"></div>' +
    '<form class="mt-2 flex gap-2" onsubmit="addComment(event,' + p.id + ')"><input name="c" maxlength="300" autocomplete="off" placeholder="Add a comment…" class="input !py-2"><button class="hx-btn hx-violet !px-4 !py-2" aria-label="Post comment"><i class="fa-solid fa-paper-plane"></i></button></form></div></article>';
}
function paintPost(id) {
  const p = byId(id), el = document.getElementById('post-' + id); if (!p || !el) return;
  el.querySelector('.hbtn').innerHTML = '<i class="' + (p.liked ? 'fa-solid text-rose-500' : 'fa-regular text-slate-700') + ' fa-heart"></i>';
  el.querySelector('.sbtn').innerHTML = '<i class="' + (p.saved ? 'fa-solid text-amber-500' : 'fa-regular text-slate-700') + ' fa-bookmark"></i>';
  el.querySelector('.lc').textContent = likesLbl(p.likes_count); el.querySelector('.cbtn').textContent = cmLbl(p.comments_count);
}
function visible() {
  return POSTS.filter(p => (tagF === 'All' || p.post_type === tagF) && (tab === 'foryou' || (tab === 'following' && (FOLLOWING.has(p.user_id) || p.mine)) || (tab === 'saved' && p.saved)));
}
function draw() {
  const list = visible();
  $('feed').innerHTML = list.map(postHtml).join(''); list.forEach(p => paintPost(p.id));
  const msg = { foryou: 'Nothing here yet — be the first to post 🌱', following: 'Follow a few members to see their posts here 💚', saved: 'Tap the bookmark on a post to save it for later 🔖' }[tab];
  $('emptyFeed').textContent = msg; $('emptyFeed').classList.toggle('hidden', list.length > 0);
  document.querySelectorAll('#feedTabs button').forEach(b => b.className = 'rounded-xl py-2 transition ' + (b.dataset.t === tab ? 'bg-gradient-to-r from-pink-500 to-orange-400 text-white shadow' : 'text-slate-500'));
  document.querySelectorAll('#tagFilter button').forEach(b => b.className = 'chip whitespace-nowrap ' + (b.dataset.f === tagF ? '!bg-slate-800 !text-white !border-slate-800' : ''));
}
function setTag(t) { tagF = t; draw(); }
$('tagFilter').innerHTML = ['All', ...Object.keys(TAG)].map(f => '<button class="chip" data-f="' + f + '">' + (f === 'All' ? 'All posts' : '#' + f) + '</button>').join('');
document.querySelectorAll('#tagFilter button').forEach(b => b.onclick = () => setTag(b.dataset.f));
document.querySelectorAll('#feedTabs button').forEach(b => b.onclick = () => { tab = b.dataset.t; draw(); });

/* Likes, double-tap, save, share, delete */
let lastTap = {};
function tap(id, el) {
  const now = Date.now();
  if (lastTap[id] && now - lastTap[id] < 320) { lastTap[id] = 0; const h = el.querySelector('.pop'); h.classList.remove('heart-pop'); void h.offsetWidth; h.classList.add('heart-pop'); if (!byId(id).liked) like(id); }
  else lastTap[id] = now;
}
async function like(id) { const j = await api('like_post', { id }); if (!j) return; const p = byId(id); p.liked = j.liked ? 1 : 0; p.likes_count = j.likes; paintPost(id); }
async function save(id) { const j = await api('save_post', { id }); if (!j) return; byId(id).saved = j.saved ? 1 : 0; paintPost(id); toast(j.saved ? 'Saved 🔖' : 'Removed from saved', 'info'); if (tab === 'saved') draw(); }
async function share(id) {
  const url = location.origin + location.pathname + '#post-' + id;
  try { await navigator.clipboard.writeText(url); toast('Post link copied 🔗'); } catch (e) { prompt('Copy this link:', url); }
}
async function del(id) {
  if (!confirm('Delete this post?')) return; const j = await api('delete_post', { id }); if (!j) return;
  POSTS.splice(POSTS.findIndex(p => p.id === id), 1); draw(); toast('Post deleted'); const m = $('myPosts'); m.textContent = Math.max(0, +m.textContent - 1);
}

/* Comments */
const commentHtml = c => '<div class="flex gap-2 py-1.5">' + av(c.user_id, c.name, 'h-7 w-7', 'text-xs') + '<p class="text-sm"><a href="member.php?id=' + c.user_id + '" class="font-bold text-slate-800">' + esc(c.name) + '</a> ' + esc(c.content) + ' <span class="text-[11px] text-slate-400">' + esc(c.ago) + '</span></p></div>';
async function toggleComments(id) {
  const box = $('cm-' + id); box.classList.toggle('hidden'); if (box.classList.contains('hidden') || box.dataset.loaded) return;
  const j = await api('get_comments', { post_id: id }); if (!j) return; box.dataset.loaded = 1;
  box.querySelector('.cl').innerHTML = j.comments.map(commentHtml).join('') || '<p class="empty py-2 text-xs text-slate-400">Be the first to comment 💬</p>';
}
async function addComment(e, id) {
  e.preventDefault(); const inp = e.target.elements.c, t = inp.value.trim(); if (!t) return; inp.value = '';
  const j = await api('add_comment', { post_id: id, content: t }); if (!j) { inp.value = t; return; }
  const cl = $('cm-' + id).querySelector('.cl'); const em = cl.querySelector('.empty'); if (em) em.remove();
  cl.insertAdjacentHTML('beforeend', commentHtml(j.comment)); cl.scrollTop = cl.scrollHeight; byId(id).comments_count = j.count; paintPost(id);
}

/* Follow */
async function follow(uid) {
  const j = await api('follow_toggle', { id: uid }); if (!j) return;
  j.following ? FOLLOWING.add(uid) : FOLLOWING.delete(uid);
  document.querySelectorAll('.fwrap[data-u="' + uid + '"]').forEach(w => w.innerHTML = followBtn(uid));
  const mf = $('myFollowing'); if (mf) mf.textContent = FOLLOWING.size;
  drawSugg(); if (tab === 'following') draw(); toast(j.following ? 'You are now following 💚' : 'Unfollowed', 'info');
}
function suggCard(s, row) {
  const f = FOLLOWING.has(s.id);
  return row
    ? '<div class="glass flex w-40 shrink-0 flex-col items-center p-3 text-center"><a href="member.php?id=' + s.id + '">' + av(s.id, s.name, 'h-14 w-14', 'text-xl') + '</a><p class="mt-2 w-full truncate text-sm font-bold text-slate-800">' + esc(s.name) + '</p><p class="w-full truncate text-[11px] text-slate-400">' + esc(s.goal) + '</p><button onclick="follow(' + s.id + ')" class="hx-btn ' + (f ? 'hx-soft' : 'hx-coral') + ' mt-2 w-full !py-1.5 text-xs">' + (f ? 'Following' : 'Follow') + '</button></div>'
    : '<div class="flex items-center gap-3"><a href="member.php?id=' + s.id + '">' + av(s.id, s.name, 'h-10 w-10') + '</a><div class="min-w-0 flex-1"><a href="member.php?id=' + s.id + '" class="block truncate text-sm font-bold text-slate-800">' + esc(s.name) + '</a><p class="truncate text-[11px] text-slate-400">' + esc(s.goal) + ' · ' + s.followers + ' followers</p></div><button onclick="follow(' + s.id + ')" class="text-xs font-bold ' + (f ? 'text-slate-400' : 'text-pink-600') + '">' + (f ? 'Following' : 'Follow') + '</button></div>';
}
function drawSugg() {
  $('suggSide').innerHTML = SUGG.map(s => suggCard(s, false)).join('') || '<p class="text-xs text-slate-400">You are following everyone — wow! 🎉</p>';
  $('suggRow').innerHTML = SUGG.map(s => suggCard(s, true)).join('') || '<p class="text-xs text-slate-400">You are following everyone 🎉</p>';
}

/* Composer */
const TAGLBL = Object.keys(TAG);
function paintTags() { $('tagPick').innerHTML = TAGLBL.map(t => '<button type="button" data-tag="' + t + '" class="chip ' + (t === tag ? '!bg-gradient-to-r !from-pink-500 !to-orange-400 !text-white !border-transparent' : '') + '">#' + t + '</button>').join(''); document.querySelectorAll('#tagPick button').forEach(b => b.onclick = () => { tag = b.dataset.tag; paintTags(); }); }
paintTags();
$('postText').addEventListener('input', e => $('charCt').textContent = e.target.value.length + '/500');
$('postImg').addEventListener('change', e => {
  const f = e.target.files[0]; if (!f) return;
  if (!/^image\/(jpeg|png|webp)$/.test(f.type)) { toast('Please choose a JPG, PNG or WebP photo.', 'error'); e.target.value = ''; return; }
  if (f.size > 4 * 1024 * 1024) { toast('Photos must be under 4 MB.', 'error'); e.target.value = ''; return; }
  $('prev').src = URL.createObjectURL(f); $('prevWrap').classList.remove('hidden');
});
function clearImg() { $('postImg').value = ''; $('prevWrap').classList.add('hidden'); }
$('postForm').addEventListener('submit', async e => {
  e.preventDefault(); const text = $('postText').value.trim(), file = $('postImg').files[0];
  if (text.length < 3 && !file) return toast('Write a few words or add a photo first.', 'info');
  const fd = new FormData(); fd.append('content', text); fd.append('post_type', tag); if (file) fd.append('image', file);
  const btn = $('postBtn'); btn.disabled = true;
  try {
    const r = await fetch('post_create.php', { method: 'POST', body: fd, headers: { 'X-CSRF-Token': CSRF }, credentials: 'same-origin' });
    const j = await r.json().catch(() => null);
    if (r.status === 401) return location.href = 'login.php';
    if (!j || !r.ok || j.ok === false) return toast((j && j.error) || 'Could not post. Please try again.', 'error');
    POSTS.unshift(j.post); $('postText').value = ''; $('charCt').textContent = '0/500'; clearImg(); tab = 'foryou'; tagF = 'All'; draw();
    const m = $('myPosts'); if (m) m.textContent = +m.textContent + 1; toast('Shared with the community 🌿');
  } catch (err) { toast('Network error — please try again.', 'error'); }
  finally { btn.disabled = false; }
});

/* Stories */
let si = 0, stTimer = null;
function showStory() {
  const s = STORIES[si], t = TAG[s.type] || TAG.Workout;
  $('stAv').innerHTML = av(s.uid, s.name, 'h-9 w-9'); $('stName').textContent = s.name; $('stName').href = 'member.php?id=' + s.uid; $('stAgo').textContent = s.ago + ' · #' + s.type;
  $('stBody').innerHTML = s.image
    ? '<img src="' + esc(s.image) + '" class="h-[26rem] w-full object-cover" alt=""><div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 to-transparent p-5 pt-16 text-sm font-semibold">' + esc(s.content) + '</div>'
    : '<div class="grid min-h-[26rem] place-items-center bg-gradient-to-br ' + t.g + ' p-8 text-center"><p class="text-xl font-extrabold leading-snug whitespace-pre-line">' + esc(s.content) + '</p></div>';
  const bar = $('stBar'); bar.style.transition = 'none'; bar.style.width = '0'; void bar.offsetWidth; bar.style.transition = 'width 5s linear'; bar.style.width = '100%';
  clearTimeout(stTimer); stTimer = setTimeout(() => nextStory(1), 5000);
}
function openStory(i) { si = i; openModal('storyModal'); showStory(); }
function nextStory(d) { si += d; if (si < 0) si = 0; if (si >= STORIES.length) { closeModal('storyModal'); return; } showStory(); }
function storyTap(e) { const r = e.currentTarget.getBoundingClientRect(); nextStory(e.clientX - r.left < r.width / 3 ? -1 : 1); }
document.getElementById('storyModal').addEventListener('hidden.bs.modal', () => clearTimeout(stTimer));

draw(); drawSugg();
if (location.hash.startsWith('#post-')) setTimeout(() => { const el = document.querySelector(location.hash); if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' }); }, 300);
</script>
<?php include __DIR__ . '/footer.php'; ?>
