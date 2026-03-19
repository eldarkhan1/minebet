<?php
// Color change POST is handled in index.php.
$user = current_user();


$stats   = get_user_stats($user['id']);
$txs     = get_user_transactions($user['id'], 10);
$winrate = ($stats['total_games'] ?? 0) > 0
    ? round(($stats['wins'] / $stats['total_games']) * 100, 1)
    : 0;
$profit  = (float)($stats['total_won'] ?? 0) - (float)($stats['total_wagered'] ?? 0);
?>
<div class="page-profile">
  <div class="profile-layout">

    <!-- SIDEBAR -->
    <div class="profile-sidebar">
      <div class="profile-card">
        <div class="profile-avatar-big" style="--acolor:<?= esc($user['avatar_color'] ?? '#00e676') ?>">
          <?= avatar_initials($user['username'] ?? '??') ?>
        </div>
        <h2 class="profile-username"><?= esc($user['username'] ?? '') ?></h2>
        <div class="profile-since">
          С нами с <?= date('d.m.Y', strtotime($user['created_at'] ?? 'now')) ?>
        </div>

        <div class="profile-balance">
          <div class="pb-label">Баланс</div>
          <div class="pb-val"><?= fmt_money((float)($user['balance'] ?? 0)) ?>₽</div>
        </div>

        <!-- COLOR PICKER -->
        <form method="POST" action="/index.php?page=profile">
          <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
          <input type="hidden" name="change_color" value="1">
          <div class="cp-label">Цвет аватара</div>
          <div class="cp-swatches">
            <?php
            $colors = ['#00e676','#00bcd4','#ff6b35','#a259ff','#ff1744','#ffd740','#40c4ff','#f06292'];
            foreach ($colors as $c):
            ?>
            <label class="cp-swatch <?= ($user['avatar_color'] ?? '') === $c ? 'active' : '' ?>">
              <input type="radio" name="color" value="<?= $c ?>"
                     <?= ($user['avatar_color'] ?? '') === $c ? 'checked' : '' ?>
                     onchange="this.form.submit()">
              <span style="background:<?= $c ?>"></span>
            </label>
            <?php endforeach; ?>
          </div>
        </form>

        <div class="profile-actions">
          <a href="/index.php?page=wallet" class="btn btn-green btn-sm btn-block">💳 Пополнить</a>
          <a href="/index.php?page=ref"    class="btn btn-outline btn-sm btn-block">🔗 Рефералы</a>
        </div>
      </div>
    </div>

    <!-- CONTENT -->
    <div class="profile-content">
      <div class="stats-grid">
        <div class="stat-card">
          <div class="sc-icon">🎮</div>
          <div class="sc-val"><?= number_format((int)($stats['total_games'] ?? 0)) ?></div>
          <div class="sc-label">Игр сыграно</div>
        </div>
        <div class="stat-card">
          <div class="sc-icon">🏆</div>
          <div class="sc-val"><?= $winrate ?>%</div>
          <div class="sc-label">Процент побед</div>
        </div>
        <div class="stat-card <?= $profit >= 0 ? 'sc-green' : 'sc-red' ?>">
          <div class="sc-icon"><?= $profit >= 0 ? '📈' : '📉' ?></div>
          <div class="sc-val"><?= ($profit >= 0 ? '+' : '') . fmt_money($profit) ?>₽</div>
          <div class="sc-label">Прибыль/убыток</div>
        </div>
        <div class="stat-card">
          <div class="sc-icon">⚡</div>
          <div class="sc-val">×<?= number_format((float)($stats['biggest_mult'] ?? 0), 2) ?></div>
          <div class="sc-label">Лучший множитель</div>
        </div>
        <div class="stat-card">
          <div class="sc-icon">💰</div>
          <div class="sc-val"><?= fmt_money((float)($stats['biggest_win'] ?? 0)) ?>₽</div>
          <div class="sc-label">Лучший выигрыш</div>
        </div>
        <div class="stat-card">
          <div class="sc-icon">🔗</div>
          <div class="sc-val"><?= (int)($stats['ref_count'] ?? 0) ?></div>
          <div class="sc-label">Рефералов</div>
        </div>
        <div class="stat-card">
          <div class="sc-icon">💸</div>
          <div class="sc-val"><?= fmt_money((float)($stats['total_wagered'] ?? 0)) ?>₽</div>
          <div class="sc-label">Всего поставлено</div>
        </div>
        <div class="stat-card">
          <div class="sc-icon">🎁</div>
          <div class="sc-val"><?= fmt_money((float)($stats['ref_earned'] ?? 0)) ?>₽</div>
          <div class="sc-label">Заработано с рефералов</div>
        </div>
      </div>

      <div class="profile-section">
        <h3 class="section-title">Последние транзакции</h3>
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
                <span class="tx-time"><?= time_ago($tx['created_at']) ?></span>
              </div>
              <div class="tx-amount <?= in_array($tx['type'],['deposit','game_win','referral'])?'ta-green':'ta-red' ?>">
                <?= in_array($tx['type'],['deposit','game_win','referral'])?'+':'-' ?><?= fmt_money((float)$tx['amount']) ?>₽
              </div>
              <div class="tx-status status-<?= esc($tx['status']) ?>"><?= esc($tx['status']) ?></div>
            </div>
            <?php endforeach; ?>
          </div>
          <a href="/index.php?page=wallet" class="btn btn-ghost btn-sm" style="margin-top:16px">Все транзакции →</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
