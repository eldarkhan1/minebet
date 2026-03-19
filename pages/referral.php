<?php
require_auth();
$user = current_user();


$ref_url = APP_URL . '/index.php?page=register&ref=' . $user['ref_code'];
$refs = db_fetchAll(
    "SELECT u.username, u.avatar_color, u.created_at,
            COALESCE(SUM(CASE WHEN t.type='deposit' AND t.status='completed' THEN t.amount END),0) as deposited
     FROM users u
     LEFT JOIN transactions t ON t.user_id = u.id
     WHERE u.referred_by = ?
     GROUP BY u.id ORDER BY u.created_at DESC",
    [$user['id']]
);
$ref_earned = (float) db_scalar(
    "SELECT COALESCE(SUM(amount),0) FROM transactions WHERE user_id=? AND type='referral' AND status='completed'",
    [$user['id']]
);
?>
<div class="page-ref">
  <h1 class="page-title">🔗 Реферальная программа</h1>

  <!-- PROMO BANNER -->
  <div class="ref-banner">
    <div class="rb-content">
      <div class="rb-icon">🎁</div>
      <div class="rb-text">
        <h2>Приглашай друзей — получай деньги</h2>
        <p>За каждого зарегистрированного по вашей ссылке — <strong><?= REF_BONUS_REFERRER ?>₽</strong> вам и <strong><?= REF_BONUS_NEW_USER ?>₽</strong> другу</p>
      </div>
    </div>
    <div class="rb-stats">
      <div class="rb-stat">
        <span class="rbs-val"><?= count($refs) ?></span>
        <span class="rbs-label">Рефералов</span>
      </div>
      <div class="rb-stat">
        <span class="rbs-val"><?= fmt_money($ref_earned) ?>₽</span>
        <span class="rbs-label">Заработано</span>
      </div>
    </div>
  </div>

  <!-- REF LINK -->
  <div class="ref-link-card">
    <div class="rlc-label">Ваша реферальная ссылка</div>
    <div class="rlc-row">
      <input type="text" class="rlc-input" id="refLinkInput" value="<?= esc($ref_url) ?>" readonly>
      <button class="btn btn-green rlc-copy" onclick="copyRefLink()">Копировать</button>
    </div>
    <div class="rlc-code-row">
      <span class="rlc-code-label">Код:</span>
      <span class="rlc-code"><?= esc($user['ref_code']) ?></span>
    </div>
    <div class="ref-share-btns">
      <a href="https://t.me/share/url?url=<?= urlencode($ref_url) ?>&text=<?= urlencode('Играй в честное казино MineBet и получи +' . REF_BONUS_NEW_USER . '₽ бонус!') ?>"
         target="_blank" class="btn btn-tg">Поделиться в Telegram</a>
      <a href="https://vk.com/share.php?url=<?= urlencode($ref_url) ?>"
         target="_blank" class="btn btn-vk">ВКонтакте</a>
    </div>
  </div>

  <!-- HOW IT WORKS -->
  <div class="ref-howto">
    <h3 class="section-title">Как это работает</h3>
    <div class="rh-steps">
      <div class="rh-step">
        <div class="rhs-num">1</div>
        <div class="rhs-text">Поделись своей реферальной ссылкой с другом</div>
      </div>
      <div class="rh-arrow">→</div>
      <div class="rh-step">
        <div class="rhs-num">2</div>
        <div class="rhs-text">Друг регистрируется по ссылке и получает <strong><?= REF_BONUS_NEW_USER ?>₽</strong></div>
      </div>
      <div class="rh-arrow">→</div>
      <div class="rh-step">
        <div class="rhs-num">3</div>
        <div class="rhs-text">Ты получаешь <strong><?= REF_BONUS_REFERRER ?>₽</strong> мгновенно на баланс</div>
      </div>
    </div>
  </div>

  <!-- REFERRALS LIST -->
  <div class="ref-list-section">
    <h3 class="section-title">Ваши рефералы (<?= count($refs) ?>)</h3>
    <?php if (empty($refs)): ?>
      <div class="empty-state">
        <div style="font-size:48px;margin-bottom:12px">👥</div>
        Пока никого нет. Поделитесь ссылкой!
      </div>
    <?php else: ?>
      <div class="ref-users-grid">
        <?php foreach ($refs as $r): ?>
        <div class="ref-user-card">
          <div class="ruc-avatar" style="--acolor:<?= esc($r['avatar_color']) ?>"><?= avatar_initials($r['username']) ?></div>
          <div class="ruc-info">
            <span class="ruc-name"><?= esc($r['username']) ?></span>
            <span class="ruc-since">Зарег. <?= date('d.m.Y', strtotime($r['created_at'])) ?></span>
          </div>
          <div class="ruc-right">
            <span class="ruc-deposited"><?= fmt_money($r['deposited']) ?>₽</span>
            <span class="ruc-dep-label">пополнений</span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
function copyRefLink() {
  const inp = document.getElementById('refLinkInput');
  navigator.clipboard.writeText(inp.value).then(() => {
    showToast('Ссылка скопирована!', 'success');
  }).catch(() => {
    inp.select();
    document.execCommand('copy');
    showToast('Ссылка скопирована!', 'success');
  });
}
</script>
