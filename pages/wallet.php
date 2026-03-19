<?php
// POST handling (deposit/withdraw) is done in index.php.
// $error and $success are set there.
$uid  = current_uid();
$user = current_user();


$txs            = get_user_transactions($uid, 30);
$total_deposit  = (float) db_scalar(
    "SELECT COALESCE(SUM(amount),0) FROM transactions WHERE user_id=? AND type='deposit' AND status='completed'",
    [$uid]
);
$total_withdraw = (float) db_scalar(
    "SELECT COALESCE(SUM(amount),0) FROM transactions WHERE user_id=? AND type='withdraw' AND status IN ('completed','pending')",
    [$uid]
);
?>
<div class="page-wallet">
  <h1 class="page-title">💳 Кошелёк</h1>

  <?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= esc($success) ?></div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= esc($error) ?></div>
  <?php endif; ?>

  <!-- BALANCE CARD -->
  <div class="wallet-balance-card">
    <div class="wbc-label">Ваш баланс</div>
    <div class="wbc-amount"><?= fmt_money((float)($user['balance'] ?? 0)) ?><span class="wbc-currency">₽</span></div>
    <div class="wbc-sub-row">
      <span>Пополнено: <strong><?= fmt_money($total_deposit) ?>₽</strong></span>
      <span>Выведено: <strong><?= fmt_money($total_withdraw) ?>₽</strong></span>
    </div>
  </div>

  <div class="wallet-forms-grid">

    <!-- DEPOSIT -->
    <div class="wallet-form-card wfc-deposit">
      <div class="wfc-title"><span class="wfc-icon">💳</span> Пополнение через YooKassa</div>
      <p class="wfc-desc">Карта, СберПэй, ЮМани, СБП и другие способы</p>
      <form method="POST" action="/index.php?page=wallet">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="deposit">
        <div class="form-group">
          <label class="form-label">Сумма пополнения</label>
          <div class="bet-input-group">
            <input type="number" name="amount" id="depositAmount" class="bet-input"
                   placeholder="<?= MIN_DEPOSIT ?>" min="<?= MIN_DEPOSIT ?>" max="500000" step="1" required>
            <span class="bet-currency">₽</span>
          </div>
        </div>
        <div class="deposit-amounts">
          <?php foreach ([100, 300, 500, 1000, 2000, 5000] as $amt): ?>
            <button type="button" class="deposit-amt-btn" onclick="document.getElementById('depositAmount').value=<?= $amt ?>"><?= number_format($amt) ?>₽</button>
          <?php endforeach; ?>
        </div>
        <div class="yk-badges">
          <span class="yk-badge">💳 Карты</span>
          <span class="yk-badge">🟢 СберПэй</span>
          <span class="yk-badge">📱 ЮМани</span>
          <span class="yk-badge">⚡ СБП</span>
        </div>
        <button type="submit" class="btn btn-green btn-block">Пополнить через YooKassa →</button>
        <div class="form-hint" style="text-align:center;margin-top:8px">Минимум: <?= MIN_DEPOSIT ?>₽</div>
      </form>
    </div>

    <!-- WITHDRAW -->
    <div class="wallet-form-card wfc-withdraw">
      <div class="wfc-title"><span class="wfc-icon">🏦</span> Вывод средств</div>
      <p class="wfc-desc">Заявки обрабатываются до 24 часов</p>
      <form method="POST" action="/index.php?page=wallet">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="withdraw">
        <div class="form-group">
          <label class="form-label">Номер карты</label>
          <input type="text" name="card" id="cardInput" class="form-input"
                 placeholder="0000 0000 0000 0000" maxlength="19" required>
        </div>
        <div class="form-group">
          <label class="form-label">Сумма вывода</label>
          <div class="bet-input-group">
            <input type="number" name="amount" class="bet-input"
                   placeholder="<?= MIN_WITHDRAW ?>"
                   min="<?= MIN_WITHDRAW ?>"
                   max="<?= (float)($user['balance'] ?? 0) ?>"
                   step="1" required>
            <span class="bet-currency">₽</span>
          </div>
          <div class="form-hint">
            Доступно: <strong><?= fmt_money((float)($user['balance'] ?? 0)) ?>₽</strong>
            · Минимум: <?= MIN_WITHDRAW ?>₽
          </div>
        </div>
        <button type="submit" class="btn btn-outline btn-block"
          <?= ((float)($user['balance'] ?? 0)) < MIN_WITHDRAW ? 'disabled' : '' ?>>
          Оформить вывод
        </button>
      </form>
    </div>
  </div>

  <!-- TRANSACTIONS -->
  <div class="wallet-txs">
    <h3 class="section-title">История транзакций</h3>
    <?php if (empty($txs)): ?>
      <div class="empty-state">Транзакций пока нет</div>
    <?php else: ?>
      <div class="tx-list">
        <?php foreach ($txs as $tx): ?>
          <div class="tx-item">
            <div class="tx-icon <?= $tx['type']==='deposit'?'txi-green':($tx['type']==='withdraw'?'txi-red':'txi-gold') ?>">
              <?= match($tx['type']) { 'deposit'=>'↓','withdraw'=>'↑','referral'=>'🔗',default=>'◈' } ?>
            </div>
            <div class="tx-info">
              <span class="tx-desc"><?= esc($tx['description'] ?: $tx['type']) ?></span>
              <span class="tx-time"><?= date('d.m.Y H:i', strtotime($tx['created_at'])) ?></span>
            </div>
            <div class="tx-amount <?= in_array($tx['type'],['deposit','referral'])?'ta-green':'ta-red' ?>">
              <?= in_array($tx['type'],['deposit','referral'])?'+':'-' ?><?= fmt_money((float)$tx['amount']) ?>₽
            </div>
            <div class="tx-status status-<?= esc($tx['status']) ?>"><?= esc($tx['status']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
var cardInput = document.getElementById('cardInput');
if (cardInput) {
  cardInput.addEventListener('input', function() {
    var v = this.value.replace(/\D/g,'').substring(0,16);
    this.value = v.replace(/(\d{4})(?=\d)/g,'$1 ');
  });
}
</script>
