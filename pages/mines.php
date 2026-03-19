<?php require_auth(); ?>
<div class="game-page" id="minesPage">
  <div class="game-layout">

    <!-- GAME AREA -->
    <div class="game-main">
      <div class="game-header">
        <h1 class="game-title">💣 Мины</h1>
        <div class="game-seed-info" id="minesSeedInfo" style="display:none">
          <span class="seed-label">Hash:</span>
          <span class="seed-val" id="minesHashDisplay"></span>
        </div>
      </div>

      <!-- MINES GRID -->
      <div class="mines-board">
        <div class="mines-grid" id="minesGrid">
          <?php for ($i = 0; $i < 25; $i++): ?>
          <button class="mine-cell" data-cell="<?= $i ?>" disabled>
            <span class="cell-inner">
              <span class="cell-gem">◈</span>
              <span class="cell-mine" style="display:none">💣</span>
              <span class="cell-safe" style="display:none">✦</span>
            </span>
          </button>
          <?php endfor; ?>
        </div>
      </div>

      <!-- MULTIPLIER BAR -->
      <div class="mult-bar">
        <div class="mult-bar-inner" id="multBar" style="width:0%"></div>
      </div>

      <!-- GAME STATUS -->
      <div class="game-status" id="minesStatus">Выберите ставку и начните игру</div>
    </div>

    <!-- CONTROLS SIDEBAR -->
    <div class="game-controls">
      <div class="ctrl-section">
        <label class="ctrl-label">Ставка</label>
        <div class="bet-input-group">
          <input type="number" id="minesBet" class="bet-input" value="10" min="1" max="50000" step="1">
          <span class="bet-currency">₽</span>
        </div>
        <div class="bet-presets">
          <button class="preset-btn" data-game="mines" data-amount="10">10</button>
          <button class="preset-btn" data-game="mines" data-amount="50">50</button>
          <button class="preset-btn" data-game="mines" data-amount="100">100</button>
          <button class="preset-btn" data-game="mines" data-amount="500">500</button>
          <button class="preset-btn preset-half" data-game="mines" data-action="half">½</button>
          <button class="preset-btn preset-double" data-game="mines" data-action="double">×2</button>
        </div>
      </div>

      <div class="ctrl-section">
        <label class="ctrl-label">Количество мин</label>
        <div class="mines-picker" id="minesPicker">
          <?php foreach ([1,2,3,4,5,7,10,15,20,24] as $m): ?>
          <button class="mines-pick-btn <?= $m==5?'active':'' ?>" data-mines="<?= $m ?>">
            <?= $m ?>
          </button>
          <?php endforeach; ?>
        </div>
        <div class="mines-pick-info">
          <span id="minesPickInfo">5 мин · <?= 25-5 ?> безопасных</span>
        </div>
      </div>

      <!-- LIVE STATS -->
      <div class="ctrl-stats">
        <div class="cs-row">
          <span class="cs-label">Множитель</span>
          <span class="cs-val green" id="minesMultiplier">×1.00</span>
        </div>
        <div class="cs-row">
          <span class="cs-label">Выигрыш</span>
          <span class="cs-val" id="minesPotential">10.00₽</span>
        </div>
        <div class="cs-row">
          <span class="cs-label">Открыто</span>
          <span class="cs-val" id="minesRevealed">0 / 20</span>
        </div>
        <div class="cs-row">
          <span class="cs-label">Ваш баланс</span>
          <span class="cs-val" id="minesBalance"><?= fmt_money((float)($user['balance'] ?? 0)) ?>₽</span>
        </div>
      </div>

      <!-- ACTION BUTTONS -->
      <button class="btn btn-green btn-block btn-lg game-btn" id="minesStartBtn" onclick="minesStart()">
        🎮 Начать игру
      </button>
      <button class="btn btn-gold btn-block btn-lg game-btn" id="minesCashoutBtn" style="display:none" onclick="minesCashout()">
        💰 Забрать <span id="cashoutAmount">0₽</span>
      </button>

      <!-- FAIR INFO -->
      <div class="fair-block" id="minesFairBlock" style="display:none">
        <div class="fair-block-title">🔐 Данные игры</div>
        <div class="fair-field"><span class="ff-label">Server seed hash:</span><span class="ff-val" id="minesSeedHash">—</span></div>
        <div class="fair-field"><span class="ff-label">Client seed:</span><span class="ff-val" id="minesClientSeed">—</span></div>
        <div class="fair-field"><span class="ff-label">Nonce:</span><span class="ff-val" id="minesNonce">—</span></div>
        <div class="fair-field revealed-only" id="minesServerSeedRow" style="display:none">
          <span class="ff-label">Server seed:</span><span class="ff-val" id="minesServerSeed">—</span>
        </div>
        <a href="/index.php?page=fair" class="fair-verify-link">Проверить →</a>
      </div>
    </div>
  </div>
</div>

<script>
window.MINES_CONFIG = {
    csrf: document.querySelector('meta[name=csrf]') ? document.querySelector('meta[name=csrf]').content : '',
    balance: <?= (float)($user['balance'] ?? 0) ?>
};
</script>
