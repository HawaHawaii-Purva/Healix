<?php
$pageTitle = 'Profile & Plans'; $active = 'profile';
require __DIR__ . '/header.php';
$uid = (int)$user['id'];
$st = db()->prepare('SELECT * FROM subscriptions WHERE user_id = ? ORDER BY id DESC LIMIT 6'); $st->execute([$uid]);
$subs = $st->fetchAll();
$rank = tier_rank($tier);
?>
<h1 class="text-2xl font-extrabold text-deepteal sm:text-3xl">Profile & Subscription</h1>
<p class="text-sm text-slate-500">Keep your metrics current so Healix can personalise your coaching.</p>

<!-- Metrics editor -->
<section class="glass mt-5 p-6">
  <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
    <h2 class="font-bold text-deepteal"><i class="fa-solid fa-sliders mr-2 text-sage"></i>Personal Metrics</h2>
    <span id="bmiChip" class="rounded-full bg-mint px-3 py-1 text-xs font-bold text-deepteal">BMI —</span>
  </div>
  <form id="profileForm" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <div class="sm:col-span-2 lg:col-span-1"><label class="mb-1 block text-xs font-semibold text-slate-500">Name</label><input class="input" name="name" required maxlength="100" value="<?= e($user['name']) ?>"></div>
    <div><label class="mb-1 block text-xs font-semibold text-slate-500">Age</label><input class="input" type="number" name="age" min="10" max="100" required value="<?= e($user['age']) ?>"></div>
    <div><label class="mb-1 block text-xs font-semibold text-slate-500">Height (cm)</label><input class="input" type="number" name="height_cm" min="50" max="260" required value="<?= e($user['height_cm']) ?>"></div>
    <div><label class="mb-1 block text-xs font-semibold text-slate-500">Weight (kg)</label><input class="input" type="number" step="0.1" name="weight_kg" min="20" max="400" required value="<?= e($user['weight_kg']) ?>"></div>
    <div><label class="mb-1 block text-xs font-semibold text-slate-500">Target weight (kg)</label><input class="input" type="number" step="0.1" name="target_weight_kg" min="20" max="400" required value="<?= e($user['target_weight_kg']) ?>"></div>
    <div><label class="mb-1 block text-xs font-semibold text-slate-500">Health goal</label>
      <select class="input" name="primary_goal"><?php foreach (GOALS_LIST as $g): ?><option <?= $user['primary_goal'] === $g ? 'selected' : '' ?>><?= e($g) ?></option><?php endforeach; ?></select></div>
    <div class="sm:col-span-2 lg:col-span-3"><button class="btn btn-primary w-full sm:w-auto"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button></div>
  </form>
</section>

<!-- Plans -->
<section id="plans" class="mt-8 scroll-mt-6">
  <div class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-end">
    <div><h2 class="text-xl font-extrabold text-deepteal">Choose your plan</h2>
      <p class="text-sm text-slate-500">Currently: <strong><?= e($tier) ?></strong><?= $user['sub_expiry'] ? ' · valid till ' . date('d M Y', strtotime($user['sub_expiry'])) : '' ?></p></div>
    <div class="grid grid-cols-3 rounded-2xl bg-mint p-1 text-sm font-semibold" id="cycleSwitch">
      <?php foreach ([3, 6, 12] as $c): ?><button type="button" data-cycle="<?= $c ?>" class="rounded-xl px-4 py-2 transition"><?= $c ?> Months</button><?php endforeach; ?>
    </div>
  </div>
  <div class="mt-5 grid gap-5 lg:grid-cols-3">
    <?php foreach (PLANS as $name => $p): $pop = $name === 'Gold'; $cur = $tier === $name; ?>
      <article class="glass lift relative flex flex-col p-6 <?= $pop ? 'ring-2 ring-sage lg:-translate-y-2' : '' ?>">
        <?php if ($pop): ?><span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-coral px-3 py-1 text-[11px] font-extrabold tracking-wider text-white shadow">MOST POPULAR</span><?php endif; ?>
        <p class="text-3xl"><?= $p['icon'] ?></p>
        <h3 class="mt-1 text-xl font-extrabold text-deepteal"><?= $name ?> Tier</h3>
        <p class="mt-3"><span class="price text-4xl font-extrabold text-deepteal" data-tier="<?= $name ?>">₹0</span></p>
        <p class="mt-1 text-xs text-slate-500"><span class="perMonth" data-tier="<?= $name ?>"></span> <span class="save ml-1 rounded-full bg-emerald-100 px-2 py-0.5 font-bold text-emerald-700" data-tier="<?= $name ?>"></span></p>
        <ul class="my-5 flex-1 space-y-2 text-sm">
          <?php foreach ($p['features'] as $f): ?><li class="flex gap-2"><i class="fa-solid fa-circle-check mt-0.5 text-sage"></i><span><?= e($f) ?></span></li><?php endforeach; ?>
        </ul>
        <button class="btn <?= $pop ? 'btn-primary' : 'btn-soft' ?> w-full" onclick="openUpgrade('<?= $name ?>')">
          <?= $cur ? 'Renew / Extend' : (TIERS[$name] < $rank ? 'Switch to ' . $name : 'Upgrade to ' . $name) ?>
        </button>
        <?php if ($cur): ?><p class="mt-2 text-center text-xs font-semibold text-sage">✓ Your current plan</p><?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<?php if ($subs): ?>
<section class="glass mt-8 p-6">
  <h2 class="mb-3 font-bold text-deepteal"><i class="fa-solid fa-receipt mr-2 text-sage"></i>Billing history</h2>
  <div class="overflow-x-auto"><table class="w-full min-w-[420px] text-sm">
    <thead><tr class="text-left text-xs uppercase text-slate-400"><th class="py-2">Plan</th><th>Cycle</th><th>Amount</th><th>Valid till</th><th>Status</th></tr></thead>
    <tbody><?php foreach ($subs as $s): ?>
      <tr class="border-t border-slate-100"><td class="py-2 font-semibold"><?= e($s['plan_tier']) ?></td><td><?= e($s['billing_cycle']) ?></td><td><?= inr((int)$s['amount_inr']) ?></td><td><?= date('d M Y', strtotime($s['end_date'])) ?></td>
        <td><span class="rounded-full px-2 py-0.5 text-xs font-semibold <?= $s['status'] === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' ?>"><?= e(ucfirst($s['status'])) ?></span></td></tr>
    <?php endforeach; ?></tbody></table></div>
</section>
<?php endif; ?>

<!-- Mock Razorpay / UPI modal -->
<div id="payModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-deepteal/50 p-4 backdrop-blur-sm">
  <div class="glass fade-in w-full max-w-md overflow-hidden !bg-white">
    <div class="flex items-center justify-between bg-gradient-to-r from-deepteal to-sage px-5 py-4 text-white">
      <div><p class="text-xs text-emerald-100">Razorpay Secure · Demo checkout</p><p class="font-bold" id="payTitle">Upgrade</p></div>
      <button onclick="closeModal('payModal')" class="grid h-8 w-8 place-items-center rounded-full hover:bg-white/20" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="space-y-4 p-5">
      <div class="rounded-2xl bg-mint/70 p-4 text-sm">
        <div class="flex justify-between"><span class="text-slate-500">Plan</span><strong id="paySum">—</strong></div>
        <div class="mt-1 flex justify-between"><span class="text-slate-500">Valid until</span><strong id="payUntil">—</strong></div>
        <div class="mt-2 flex justify-between border-t border-white pt-2 text-base"><span class="font-semibold">Total</span><strong id="payAmt" class="text-deepteal">₹0</strong></div>
      </div>
      <div class="grid grid-cols-2 rounded-2xl bg-mint p-1 text-sm font-semibold">
        <button type="button" id="m-upi" onclick="setMethod('upi')" class="rounded-xl py-2"><i class="fa-solid fa-mobile-screen mr-1"></i>UPI</button>
        <button type="button" id="m-card" onclick="setMethod('card')" class="rounded-xl py-2"><i class="fa-solid fa-credit-card mr-1"></i>Card</button>
      </div>
      <div id="f-upi"><label class="mb-1 block text-xs font-semibold text-slate-500">UPI ID</label><input id="upiId" class="input" placeholder="yourname@okaxis" autocomplete="off"></div>
      <div id="f-card" class="hidden space-y-3">
        <input class="input" inputmode="numeric" maxlength="19" placeholder="Card number (demo only)" id="cardNo" autocomplete="off">
        <div class="grid grid-cols-2 gap-3"><input class="input" placeholder="MM / YY" maxlength="7" autocomplete="off"><input class="input" placeholder="CVV" maxlength="4" type="password" autocomplete="off"></div>
      </div>
      <button id="payBtn" class="btn btn-primary w-full" onclick="confirmPayment()"><i class="fa-solid fa-lock"></i> Confirm Payment</button>
      <p class="text-center text-[11px] text-slate-400">Demo only — no real payment is processed and card details are never sent to the server.</p>
    </div>
  </div>
</div>

<script>
const PLANS = <?= json_encode(PLANS, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
let cycle = 6, selTier = null, method = 'upi';
const inr = n => '₹' + n.toLocaleString('en-IN');
function paint() {
  document.querySelectorAll('#cycleSwitch button').forEach(b => b.className = 'rounded-xl px-4 py-2 transition ' + (+b.dataset.cycle === cycle ? 'bg-white text-deepteal shadow-sm' : 'text-slate-500'));
  for (const [t, p] of Object.entries(PLANS)) {
    const pr = p.prices[cycle], base = p.prices[3] * cycle / 3, off = Math.round((1 - pr / base) * 100);
    document.querySelector('.price[data-tier=' + t + ']').textContent = inr(pr);
    document.querySelector('.perMonth[data-tier=' + t + ']').textContent = 'for ' + cycle + ' months · ' + inr(Math.round(pr / cycle)) + '/mo';
    document.querySelector('.save[data-tier=' + t + ']').textContent = off > 0 ? 'Save ' + off + '%' : '';
  }
}
document.querySelectorAll('#cycleSwitch button').forEach(b => b.onclick = () => { cycle = +b.dataset.cycle; paint(); });
paint();

function openUpgrade(t) {
  selTier = t; const pr = PLANS[t].prices[cycle], d = new Date(); d.setMonth(d.getMonth() + cycle);
  document.getElementById('payTitle').textContent = PLANS[t].icon + ' ' + t + ' Tier';
  document.getElementById('paySum').textContent = t + ' · ' + cycle + ' months';
  document.getElementById('payUntil').textContent = d.toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }) + (<?= json_encode($tier) ?> === t ? ' (extends current plan)' : '');
  document.getElementById('payAmt').textContent = inr(pr);
  setMethod('upi'); openModal('payModal');
}
function setMethod(m) {
  method = m;
  ['upi', 'card'].forEach(k => { document.getElementById('f-' + k).classList.toggle('hidden', k !== m); document.getElementById('m-' + k).className = 'rounded-xl py-2 ' + (k === m ? 'bg-white text-deepteal shadow-sm' : 'text-slate-500'); });
}
async function confirmPayment() {
  if (method === 'upi' && !/^[\w.\-]{2,}@[A-Za-z]{2,}$/.test(document.getElementById('upiId').value.trim())) return toast('Enter a valid UPI ID, e.g. name@okaxis', 'error');
  if (method === 'card' && document.getElementById('cardNo').value.replace(/\D/g, '').length < 13) return toast('Enter a valid card number (demo).', 'error');
  const btn = document.getElementById('payBtn'); btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing…';
  await sleep(1400);
  const j = await api('upgrade', { tier: selTier, cycle, method, upi: document.getElementById('upiId').value.trim() });
  if (j) { toast(j.message); await sleep(1200); location.reload(); }
  else { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-lock"></i> Confirm Payment'; }
}

const form = document.getElementById('profileForm');
function bmi() {
  const h = +form.height_cm.value / 100, w = +form.weight_kg.value, c = document.getElementById('bmiChip');
  if (!h || !w) return c.textContent = 'BMI —';
  const b = w / (h * h); c.textContent = 'BMI ' + b.toFixed(1) + ' · ' + (b < 18.5 ? 'Underweight' : b < 25 ? 'Healthy' : b < 30 ? 'Overweight' : 'Obese');
}
form.addEventListener('input', bmi); bmi();
form.addEventListener('submit', async e => {
  e.preventDefault(); const j = await api('update_profile', Object.fromEntries(new FormData(form)));
  if (j) { toast(j.message); }
});
</script>
<?php include __DIR__ . '/footer.php'; ?>
