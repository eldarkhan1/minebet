<?php
$last_wins = get_last_wins(12);
$top3 = get_top_players(3);
?>
<div class="page-home">

  <!-- HERO -->
  <section class="hero">
    <div class="hero-badge">
      <span class="pulse-dot"></span> Провабли-фэйр казино
    </div>
    <h1 class="hero-title">
      Играй.<br><em>Выигрывай.</em>
    </h1>
    <p class="hero-sub">Честные мины и больше/меньше с криптографической проверкой каждого результата</p>
    <div class="hero-actions">
      <a href="/index.php?page=mines" class="btn btn-green btn-hero">
        <span>💣</span> Играть в Мины
      </a>
      <a href="/index.php?page=hilo" class="btn btn-outline btn-hero">
        <span>🎯</span> Больше / Меньше
      </a>
    </div>
    <div class="hero-stats">
      <?php
      $total_games = db_scalar("SELECT COUNT(*) FROM games") ?? 0;
      $total_won   = db_scalar("SELECT COALESCE(SUM(payout),0) FROM games WHERE result='win'") ?? 0;
      $total_users = db_scalar("SELECT COUNT(*) FROM users") ?? 0;
      ?>
      <div class="hstat"><span class="hstat-val"><?= number_format($total_games) ?></span><span class="hstat-label">Игр сыграно</span></div>
      <div class="hstat-div"></div>
      <div class="hstat"><span class="hstat-val"><?= fmt_money_ru($total_won) ?></span><span class="hstat-label">Выплачено</span></div>
      <div class="hstat-div"></div>
      <div class="hstat"><span class="hstat-val"><?= number_format($total_users) ?></span><span class="hstat-label">Игроков</span></div>
    </div>
  </section>

  <!-- GAME CARDS -->
  <section class="section">
    <h2 class="section-title">Игры</h2>
    <div class="game-cards">
      <a href="/index.php?page=mines" class="game-card game-card--mines">
        <div class="gc-glow"></div>
        <div class="gc-icon">💣</div>
        <div class="gc-info">
          <h3>МИНЫ</h3>
          <p>Открывай клетки 5×5, избегай мин. Чем больше ходов — тем выше множитель!</p>
          <div class="gc-badges">
            <span class="gc-badge">До ×1000</span>
            <span class="gc-badge gc-badge--green">1–24 мины</span>
          </div>
        </div>
        <div class="gc-arrow">→</div>
      </a>
      <a href="/index.php?page=hilo" class="game-card game-card--hilo">
        <div class="gc-glow"></div>
        <div class="gc-icon">🎯</div>
        <div class="gc-info">
          <h3>БОЛЬШЕ / МЕНЬШЕ</h3>
          <p>Угадай, будет ли следующее число больше или меньше. Динамический множитель!</p>
          <div class="gc-badges">
            <span class="gc-badge">До ×99</span>
            <span class="gc-badge gc-badge--blue">Числа 1–100</span>
          </div>
        </div>
        <div class="gc-arrow">→</div>
      </a>
    </div>
  </section>

  <!-- LAST WINS -->
  <section class="section">
    <div class="section-header">
      <h2 class="section-title">Последние выигрыши</h2>
      <span class="live-badge"><span class="pulse-dot"></span>LIVE</span>
    </div>
    <div class="wins-grid" id="winsGrid">
      <?php foreach ($last_wins as $w): ?>
      <div class="win-card <?= $w['game_type'] === 'mines' ? 'wc-mines' : 'wc-hilo' ?>">
        <div class="wc-avatar" style="--acolor:<?= esc($w['avatar_color']) ?>"><?= avatar_initials($w['username']) ?></div>
        <div class="wc-info">
          <span class="wc-user"><?= esc($w['username']) ?></span>
          <span class="wc-game"><?= $w['game_type'] === 'mines' ? '💣 Мины' : '🎯 Хайло' ?></span>
        </div>
        <div class="wc-right">
          <span class="wc-payout">+<?= fmt_money($w['payout']) ?>₽</span>
          <span class="wc-mult">×<?= number_format($w['multiplier'], 2) ?></span>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (empty($last_wins)): ?>
        <div class="empty-state">Пока нет выигрышей. Будь первым!</div>
      <?php endif; ?>
    </div>
  </section>

  <!-- WHY US -->
  <section class="section">
    <h2 class="section-title">Почему MineBet</h2>
    <div class="features-grid">
      <div class="feature-card">
        <div class="fc-icon">🔐</div>
        <h4>Провабли-фэйр</h4>
        <p>Каждый результат можно проверить. HMAC-SHA256 гарантирует честность.</p>
      </div>
      <div class="feature-card">
        <div class="fc-icon">⚡</div>
        <h4>Мгновенные выплаты</h4>
        <p>Выигрыш зачисляется на баланс сразу после окончания игры.</p>
      </div>
      <div class="feature-card">
        <div class="fc-icon">🎁</div>
        <h4>Реферальная программа</h4>
        <p>Приглашай друзей и получай <?= REF_BONUS_REFERRER ?>₽ за каждого.</p>
      </div>
      <div class="feature-card">
        <div class="fc-icon">💳</div>
        <h4>Быстрое пополнение</h4>
        <p>Пополнение через YooKassa. Карты, SberPay, ЮMoney.</p>
      </div>
    </div>
  </section>

</div>
