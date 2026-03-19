<?php
$verify_result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    csrf_check();
    $ss   = trim($_POST['server_seed'] ?? '');
    $cs   = trim($_POST['client_seed'] ?? '');
    $nc   = (int)($_POST['nonce'] ?? 0);
    $type = $_POST['game_type'] ?? 'mines';
    $mc   = (int)($_POST['mines_count'] ?? 5);

    if ($ss && $cs && $nc > 0) {
        if ($type === 'mines') {
            $verify_result = verify_mines($ss, $cs, $nc, $mc);
            $verify_result['type'] = 'mines';
        } else {
            $verify_result = verify_hilo($ss, $cs, $nc);
            $verify_result['type'] = 'hilo';
        }
        $verify_result['server_seed_hash_check'] = hash('sha256', $ss);
    } else {
        $verify_error = 'Заполните все поля';
    }
}
?>
<div class="page-fair">
  <h1 class="page-title">🔐 Честная игра</h1>

  <!-- EXPLANATION -->
  <div class="fair-explanation">
    <div class="fe-grid">
      <div class="fe-card">
        <h3>Что такое Провабли-Фэйр?</h3>
        <p>Каждая игра использует криптографически безопасный алгоритм. Перед игрой вам виден хэш серверного сида — вы можете убедиться, что он не менялся после вашей ставки. После игры раскрывается настоящий серверный сид.</p>
      </div>
      <div class="fe-card">
        <h3>Как это работает?</h3>
        <div class="fe-steps">
          <div class="fe-step"><span class="fe-step-num">1</span>Сервер генерирует случайный <em>server_seed</em> и показывает его SHA-256 хэш</div>
          <div class="fe-step"><span class="fe-step-num">2</span>Вы делаете ставку (client_seed генерируется автоматически)</div>
          <div class="fe-step"><span class="fe-step-num">3</span>Результат = HMAC-SHA256(client_seed:nonce, server_seed)</div>
          <div class="fe-step"><span class="fe-step-num">4</span>После игры server_seed раскрывается — вы можете проверить</div>
        </div>
      </div>
    </div>

    <div class="fe-algo-card">
      <div class="fe-algo-title">Алгоритмы</div>
      <div class="fe-algo-grid">
        <div class="fe-algo-item">
          <div class="fai-title">💣 Мины — позиции</div>
          <code class="fai-code">hash = HMAC-SHA256(client:nonce, server_seed)
positions = Fisher-Yates shuffle([0..24], hash)[0..N]</code>
        </div>
        <div class="fe-algo-item">
          <div class="fai-title">🎯 Хайло — число</div>
          <code class="fai-code">hash = HMAC-SHA256(client:nonce, server_seed)
number = SHA256(hash + "_1")[0:8] % 100 + 1</code>
        </div>
      </div>
    </div>
  </div>

  <!-- VERIFY FORM -->
  <div class="fair-verify-card">
    <h3 class="fv-title">Проверить игру</h3>

    <?php if (!empty($verify_error)): ?>
      <div class="alert alert-error"><?= esc($verify_error) ?></div>
    <?php endif; ?>

    <form method="POST" class="fv-form">
      <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="verify" value="1">

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Тип игры</label>
          <select name="game_type" class="form-input" id="fairGameType" onchange="toggleMinesCount()">
            <option value="mines">💣 Мины</option>
            <option value="hilo">🎯 Больше/Меньше</option>
          </select>
        </div>
        <div class="form-group" id="minesCountGroup">
          <label class="form-label">Количество мин</label>
          <select name="mines_count" class="form-input">
            <?php for ($i=1; $i<=24; $i++) echo "<option value='$i' ".($i==5?'selected':'').">$i</option>"; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Server Seed</label>
        <input type="text" name="server_seed" class="form-input font-mono"
               placeholder="Раскрытый server seed из игры"
               value="<?= esc($_POST['server_seed'] ?? '') ?>">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Client Seed</label>
          <input type="text" name="client_seed" class="form-input font-mono"
                 placeholder="Client seed из игры"
                 value="<?= esc($_POST['client_seed'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Nonce</label>
          <input type="number" name="nonce" class="form-input font-mono"
                 placeholder="Nonce из игры"
                 value="<?= esc($_POST['nonce'] ?? '') ?>">
        </div>
      </div>

      <button type="submit" class="btn btn-green">🔍 Проверить</button>
    </form>

    <!-- RESULT -->
    <?php if ($verify_result): ?>
    <div class="fv-result">
      <div class="fvr-title">✅ Результат проверки</div>
      <div class="fvr-grid">
        <div class="fvr-item">
          <span class="fvr-label">HMAC хэш:</span>
          <code class="fvr-val"><?= esc($verify_result['hash']) ?></code>
        </div>
        <div class="fvr-item">
          <span class="fvr-label">SHA256(server_seed):</span>
          <code class="fvr-val"><?= esc($verify_result['server_seed_hash_check']) ?></code>
        </div>
        <?php if ($verify_result['type'] === 'mines'): ?>
        <div class="fvr-item">
          <span class="fvr-label">Позиции мин (0-24):</span>
          <code class="fvr-val">[<?= implode(', ', $verify_result['mine_positions']) ?>]</code>
        </div>
        <?php else: ?>
        <div class="fvr-item">
          <span class="fvr-label">Первое число:</span>
          <code class="fvr-val"><?= $verify_result['num1'] ?></code>
        </div>
        <div class="fvr-item">
          <span class="fvr-label">Второе число:</span>
          <code class="fvr-val"><?= $verify_result['num2'] ?></code>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
function toggleMinesCount() {
  const v = document.getElementById('fairGameType').value;
  document.getElementById('minesCountGroup').style.display = v === 'mines' ? '' : 'none';
}
</script>
