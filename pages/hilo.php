<?php require_auth(); ?>
<div class="game-page" id="hiloPage">
  <div class="game-layout">

    <!-- GAME AREA -->
    <div class="game-main">
      <div class="game-header">
        <h1 class="game-title">🎯 Больше / Меньше</h1>
      </div>

      <!-- HILO GAME AREA -->
      <div class="hilo-arena">
        <div class="hilo-cards-row">
          <!-- Current number card -->
          <div class="hilo-card-wrap">
            <div class="hilo-label">Текущее</div>
            <div class="hilo-card" id="hiloCard1">
              <div class="hc-inner">
                <span class="hc-number" id="hiloNum1">?</span>
                <span class="hc-sub">из 100</span>
              </div>
            </div>
          </div>

          <!-- VS divider -->
          <div class="hilo-vs">
            <div class="hilo-vs-icon" id="hiloVsIcon">VS</div>
          </div>

          <!-- Result card -->
          <div class="hilo-card-wrap">
            <div class="hilo-label">Следующее</div>
            <div class="hilo-card hilo-card--mystery" id="hiloCard2">
              <div class="hc-inner">
                <span class="hc-number hc-mystery" id="hiloNum2">???</span>
                <span class="hc-sub">неизвестно</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Probability bar -->
        <div class="hilo-prob-bar">
          <div class="hpb-label">
            <span id="hpb-higher">📈 Выше</span>
            <span id="hpb-equal">= Равно</span>
            <span id="hpb-lower">📉 Ниже</span>
          </div>
          <div class="hpb-track">
            <div class="hpb-segment hpb-seg-lower" id="hpbLower" style="width:0%"></div>
            <div class="hpb-segment hpb-seg-equal" id="hpbEqual" style="width:1%"></div>
            <div class="hpb-segment hpb-seg-higher" id="hpbHigher" style="width:0%"></div>
          </div>
        </div>

        <!-- Action buttons -->
        <div class="hilo-btns" id="hiloBtns">
          <button class="btn btn-hilo-higher" id="hiloHigherBtn" onclick="hiloPlay('higher')">
            <span class="hlb-icon">📈</span>
            <span class="hlb-text">БОЛЬШЕ</span>
            <span class="hlb-mult" id="hiloHigherMult">×?</span>
          </button>
          <button class="btn btn-hilo-lower" id="hiloLowerBtn" onclick="hiloPlay('lower')">
            <span class="hlb-icon">📉</span>
            <span class="hlb-text">МЕНЬШЕ</span>
            <span class="hlb-mult" id="hiloLowerMult">×?</span>
          </button>
        </div>

        <!-- Result message -->
        <div class="hilo-result" id="hiloResult" style="display:none"></div>

        <button class="btn btn-outline btn-sm" id="hiloNewGameBtn" onclick="hiloNewGame()" style="display:none; margin:0 auto">
          🔄 Новая игра
        </button>
      </div>
    </div>

    <!-- CONTROLS -->
    <div class="game-controls">
      <div class="ctrl-section">
        <label class="ctrl-label">Ставка</label>
        <div class="bet-input-group">
          <input type="number" id="hiloBet" class="bet-input" value="10" min="1" max="50000" step="1">
          <span class="bet-currency">₽</span>
        </div>
        <div class="bet-presets">
          <button class="preset-btn" data-game="hilo" data-amount="10">10</button>
          <button class="preset-btn" data-game="hilo" data-amount="50">50</button>
          <button class="preset-btn" data-game="hilo" data-amount="100">100</button>
          <button class="preset-btn" data-game="hilo" data-amount="500">500</button>
          <button class="preset-btn preset-half" data-game="hilo" data-action="half">½</button>
          <button class="preset-btn preset-double" data-game="hilo" data-action="double">×2</button>
        </div>
      </div>

      <div class="ctrl-stats">
        <div class="cs-row">
          <span class="cs-label">Ставка</span>
          <span class="cs-val" id="hiloDisplayBet">10.00₽</span>
        </div>
        <div class="cs-row">
          <span class="cs-label">Ваш баланс</span>
          <span class="cs-val" id="hiloBalance"><?= fmt_money((float)($user['balance'] ?? 0)) ?>₽</span>
        </div>
      </div>

      <!-- FAIR -->
      <div class="fair-block" id="hiloFairBlock" style="display:none">
        <div class="fair-block-title">🔐 Данные игры</div>
        <div class="fair-field"><span class="ff-label">Server seed:</span><span class="ff-val" id="hiloServerSeed">—</span></div>
        <div class="fair-field"><span class="ff-label">Client seed:</span><span class="ff-val" id="hiloClientSeed">—</span></div>
        <div class="fair-field"><span class="ff-label">Nonce:</span><span class="ff-val" id="hiloNonce">—</span></div>
        <a href="/index.php?page=fair" class="fair-verify-link">Проверить →</a>
      </div>

      <!-- HOW TO PLAY -->
      <div class="howto-block">
        <div class="howto-title">Как играть</div>
        <ol class="howto-list">
          <li>Укажи ставку</li>
          <li>Смотри на текущее число</li>
          <li>Угадай: следующее будет БОЛЬШЕ или МЕНЬШЕ</li>
          <li>Множитель зависит от вероятности угадать</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<script>
window.HILO_CONFIG = {
    csrf: document.querySelector('meta[name=csrf]') ? document.querySelector('meta[name=csrf]').content : '',
    balance: <?= (float)($user['balance'] ?? 0) ?>
};
// Init first number on page load
document.addEventListener('DOMContentLoaded', function() { hiloInit(); });
</script>
