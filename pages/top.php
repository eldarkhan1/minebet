<?php
$top = get_top_players(50);
$top_mines = db_fetchAll(
    "SELECT u.username, u.avatar_color, MAX(g.multiplier) as best_mult, MAX(g.payout) as best_win
     FROM games g JOIN users u ON g.user_id = u.id
     WHERE g.game_type='mines' AND g.result='win'
     GROUP BY g.user_id ORDER BY best_win DESC LIMIT 10"
);
$top_hilo = db_fetchAll(
    "SELECT u.username, u.avatar_color, MAX(g.multiplier) as best_mult, MAX(g.payout) as best_win
     FROM games g JOIN users u ON g.user_id = u.id
     WHERE g.game_type='hilo' AND g.result='win'
     GROUP BY g.user_id ORDER BY best_win DESC LIMIT 10"
);
?>
<div class="page-top">
  <h1 class="page-title">⭐ Топ игроков</h1>

  <!-- TABS -->
  <div class="tabs-nav">
    <button class="tab-btn active" data-tab="all">По прибыли</button>
    <button class="tab-btn" data-tab="mines">Мины</button>
    <button class="tab-btn" data-tab="hilo">Хайло</button>
  </div>

  <!-- ALL PLAYERS -->
  <div class="tab-panel active" id="tab-all">
    <?php if (empty($top)): ?>
      <div class="empty-state">Пока нет данных</div>
    <?php else: ?>
      <div class="top-podium">
        <?php
        $medals = ['🥇','🥈','🥉'];
        foreach (array_slice($top, 0, 3) as $i => $p):
        ?>
        <div class="podium-card podium-<?= $i+1 ?>">
          <div class="pc-medal"><?= $medals[$i] ?></div>
          <div class="pc-avatar" style="--acolor:<?= esc($p['avatar_color']) ?>"><?= avatar_initials($p['username']) ?></div>
          <div class="pc-name"><?= esc($p['username']) ?></div>
          <div class="pc-profit <?= $p['profit']>=0?'green':'red' ?>"><?= ($p['profit']>=0?'+':'') . fmt_money($p['profit']) ?>₽</div>
          <div class="pc-games"><?= $p['games_played'] ?> игр</div>
        </div>
        <?php endforeach; ?>
      </div>

      <table class="top-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Игрок</th>
            <th>Прибыль</th>
            <th>Поставлено</th>
            <th>Игр</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($top as $i => $p): ?>
          <tr class="<?= $i < 3 ? 'tr-top' : '' ?>">
            <td>
              <?php if ($i < 3) echo $medals[$i];
              else echo '<span class="rank-num">' . ($i+1) . '</span>'; ?>
            </td>
            <td>
              <div class="tt-user">
                <div class="tt-avatar" style="--acolor:<?= esc($p['avatar_color']) ?>"><?= avatar_initials($p['username']) ?></div>
                <?= esc($p['username']) ?>
              </div>
            </td>
            <td class="<?= $p['profit']>=0?'td-green':'td-red' ?>"><?= ($p['profit']>=0?'+':'').fmt_money($p['profit']) ?>₽</td>
            <td class="td-muted"><?= fmt_money($p['total_wagered']) ?>₽</td>
            <td class="td-muted"><?= $p['games_played'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <!-- MINES TOP -->
  <div class="tab-panel" id="tab-mines">
    <?php if (empty($top_mines)): ?>
      <div class="empty-state">Нет данных по Минам</div>
    <?php else: ?>
      <table class="top-table">
        <thead><tr><th>#</th><th>Игрок</th><th>Лучший выигрыш</th><th>Лучший множитель</th></tr></thead>
        <tbody>
          <?php foreach ($top_mines as $i => $p): ?>
          <tr>
            <td><?= $i < 3 ? $medals[$i] : ($i+1) ?></td>
            <td><div class="tt-user"><div class="tt-avatar" style="--acolor:<?= esc($p['avatar_color']) ?>"><?= avatar_initials($p['username']) ?></div><?= esc($p['username']) ?></div></td>
            <td class="td-green">+<?= fmt_money($p['best_win']) ?>₽</td>
            <td class="td-green">×<?= number_format($p['best_mult'],2) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <!-- HILO TOP -->
  <div class="tab-panel" id="tab-hilo">
    <?php if (empty($top_hilo)): ?>
      <div class="empty-state">Нет данных по Хайло</div>
    <?php else: ?>
      <table class="top-table">
        <thead><tr><th>#</th><th>Игрок</th><th>Лучший выигрыш</th><th>Лучший множитель</th></tr></thead>
        <tbody>
          <?php foreach ($top_hilo as $i => $p): ?>
          <tr>
            <td><?= $i < 3 ? $medals[$i] : ($i+1) ?></td>
            <td><div class="tt-user"><div class="tt-avatar" style="--acolor:<?= esc($p['avatar_color']) ?>"><?= avatar_initials($p['username']) ?></div><?= esc($p['username']) ?></div></td>
            <td class="td-green">+<?= fmt_money($p['best_win']) ?>₽</td>
            <td class="td-green">×<?= number_format($p['best_mult'],2) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<script>
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tab-btn, .tab-panel').forEach(el => el.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
  });
});
</script>
