'use strict';

// ─── GRID CANVAS ──────────────────────────────────────────
(function () {
  const canvas = document.getElementById('gridCanvas');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  function resize() {
    canvas.width  = window.innerWidth;
    canvas.height = window.innerHeight;
    draw();
  }
  function draw() {
    const W = canvas.width, H = canvas.height;
    ctx.clearRect(0, 0, W, H);
    ctx.strokeStyle = 'rgba(0,230,118,0.055)';
    ctx.lineWidth = 1;
    for (let x = 0; x <= W; x += 44) { ctx.beginPath(); ctx.moveTo(x,0); ctx.lineTo(x,H); ctx.stroke(); }
    for (let y = 0; y <= H; y += 44) { ctx.beginPath(); ctx.moveTo(0,y); ctx.lineTo(W,y); ctx.stroke(); }
    const gr = ctx.createRadialGradient(W/2,0,0,W/2,0,W*0.6);
    gr.addColorStop(0,'rgba(0,230,118,0.04)'); gr.addColorStop(1,'transparent');
    ctx.fillStyle = gr; ctx.fillRect(0,0,W,H);
  }
  window.addEventListener('resize', resize);
  resize();
})();

// ─── TOAST ────────────────────────────────────────────────
function showToast(msg, type, duration) {
  type = type || 'info'; duration = duration || 3500;
  var c = document.getElementById('toastContainer');
  if (!c) return;
  var icons = { success:'✅', error:'❌', info:'ℹ️', gold:'🏆' };
  var el = document.createElement('div');
  el.className = 'toast toast-' + type;
  el.innerHTML = '<span>' + (icons[type]||'ℹ️') + '</span><span>' + escHtml(msg) + '</span>';
  c.appendChild(el);
  setTimeout(function() {
    el.classList.add('toast-out');
    setTimeout(function(){ if(el.parentNode) el.parentNode.removeChild(el); }, 300);
  }, duration);
}
function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ─── USER MENU ────────────────────────────────────────────
var userMenu = document.getElementById('userMenuTrigger');
if (userMenu) {
  userMenu.addEventListener('click', function(e) { e.stopPropagation(); userMenu.classList.toggle('open'); });
  document.addEventListener('click', function() { userMenu.classList.remove('open'); });
}

// ─── MOBILE DRAWER ────────────────────────────────────────
var burger  = document.getElementById('burgerBtn');
var drawer  = document.getElementById('mobileDrawer');
var overlay = document.getElementById('drawerOverlay');
if (burger && drawer && overlay) {
  burger.addEventListener('click', function() {
    var isOpen = drawer.classList.contains('open');
    drawer.classList.toggle('open', !isOpen);
    overlay.classList.toggle('show', !isOpen);
    burger.classList.toggle('open', !isOpen);
  });
  overlay.addEventListener('click', function() {
    drawer.classList.remove('open'); overlay.classList.remove('show'); burger.classList.remove('open');
  });
}

// ─── PASSWORD TOGGLE ──────────────────────────────────────
document.querySelectorAll('.eye-btn').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var inp = document.getElementById(btn.dataset.target);
    if (!inp) return;
    inp.type = inp.type === 'password' ? 'text' : 'password';
    btn.textContent = inp.type === 'password' ? '👁' : '🙈';
  });
});

// ─── API ──────────────────────────────────────────────────
function getCsrf() {
  var m = document.querySelector('meta[name=csrf]');
  return m ? m.content : '';
}

function apiPost(url, params) {
  params = params || {};
  var fd = new FormData();
  fd.append('_csrf', getCsrf());
  Object.keys(params).forEach(function(k) { fd.append(k, params[k]); });
  return fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: fd
  }).then(function(res) {
    return res.text();
  }).then(function(text) {
    try { return JSON.parse(text); }
    catch(e) { return { ok: false, msg: 'Ответ сервера: ' + text.substring(0, 120) }; }
  }).catch(function(e) {
    return { ok: false, msg: 'Ошибка сети' };
  });
}

// ─── HELPERS ──────────────────────────────────────────────
function fmtMoney(n) {
  return parseFloat(n || 0).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function fmtMult(n) { return '×' + parseFloat(n || 0).toFixed(2); }

function setEl(id, text, styles) {
  var el = (typeof id === 'string') ? document.getElementById(id) : id;
  if (!el) return;
  if (text !== null && text !== undefined) el.textContent = text;
  if (styles) {
    if (styles.display  !== undefined) el.style.display  = styles.display;
    if (styles.width    !== undefined) el.style.width    = styles.width;
    if (styles.disabled !== undefined) el.disabled       = styles.disabled;
  }
}

function updateGlobalBalance(val) {
  setEl('headerBalance', fmtMoney(val) + '₽');
}

// ─── BET PRESETS ──────────────────────────────────────────
document.querySelectorAll('.preset-btn').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var inp = document.getElementById((btn.dataset.game || '') + 'Bet');
    if (!inp) return;
    var cur = parseFloat(inp.value) || 0;
    if (btn.dataset.action === 'half')        inp.value = Math.max(1, Math.floor(cur / 2));
    else if (btn.dataset.action === 'double') inp.value = Math.min(50000, Math.floor(cur * 2));
    else if (btn.dataset.amount)              inp.value = btn.dataset.amount;
    inp.dispatchEvent(new Event('input'));
  });
});

// ═══════════════════════════════════════════════════════════
// MINES GAME
// ═══════════════════════════════════════════════════════════

if (document.getElementById('minesGrid')) {

  var minesActive   = false;
  var minesCount    = 5;
  var minesRevealed = [];

  // Picker
  document.querySelectorAll('.mines-pick-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      if (minesActive) return;
      document.querySelectorAll('.mines-pick-btn').forEach(function(b) { b.classList.remove('active'); });
      btn.classList.add('active');
      minesCount = parseInt(btn.dataset.mines);
      updatePickInfo();
    });
  });

  var minesBetInput = document.getElementById('minesBet');
  if (minesBetInput) minesBetInput.addEventListener('input', updatePickInfo);

  function updatePickInfo() {
    var safe = 25 - minesCount;
    setEl('minesPickInfo', minesCount + ' мин · ' + safe + ' безопасных');
    var bet = parseFloat((minesBetInput && minesBetInput.value) || 0);
    setEl('minesPotential', fmtMoney(bet) + '₽');
    setEl('minesRevealed', '0 / ' + safe);
  }
  updatePickInfo();

  // Cell icons
  function cellIcon(cell, state) {
    var gem  = cell.querySelector('.cell-gem');
    var mine = cell.querySelector('.cell-mine');
    var safe = cell.querySelector('.cell-safe');
    if(gem)  gem.style.display  = (state === 'default') ? '' : 'none';
    if(mine) mine.style.display = (state === 'mine-hit' || state === 'mine-show') ? '' : 'none';
    if(safe) safe.style.display = (state === 'safe') ? '' : 'none';
  }

  function resetGrid(enableCells) {
    document.querySelectorAll('.mine-cell').forEach(function(c) {
      c.className = 'mine-cell';
      c.disabled  = !enableCells;
      cellIcon(c, 'default');
    });
  }

  resetGrid(false);

  // Bind clicks
  document.querySelectorAll('.mine-cell').forEach(function(cell) {
    cell.addEventListener('click', function() {
      if (!minesActive || cell.disabled) return;
      cell.disabled = true;
      doReveal(parseInt(cell.dataset.cell));
    });
  });

  // START
  window.minesStart = function() {
    var bet = parseFloat(minesBetInput && minesBetInput.value || 0);
    if (bet <= 0) { showToast('Введите ставку', 'error'); return; }

    var startBtn = document.getElementById('minesStartBtn');
    if (startBtn) { startBtn.disabled = true; startBtn.textContent = 'Загрузка...'; }

    apiPost('/api/game.php', { action:'mines_start', bet: bet, mines: minesCount }).then(function(data) {
      if (startBtn) { startBtn.disabled = false; startBtn.textContent = '🎮 Начать игру'; }
      if (!data.ok) { showToast(data.msg || 'Ошибка', 'error'); return; }

      minesActive   = true;
      minesRevealed = [];

      resetGrid(true);

      setEl('minesStartBtn',     null, { display: 'none' });
      setEl('minesCashoutBtn',   null, { display: '' });
      setEl('minesFairBlock',    null, { display: '' });
      setEl('minesSeedHash',     data.server_seed_hash);
      setEl('minesClientSeed',   data.client_seed);
      setEl('minesNonce',        data.nonce);
      setEl('minesServerSeedRow',null, { display: 'none' });
      setEl('minesMultiplier',   '×1.00');
      setEl('minesPotential',    fmtMoney(bet) + '₽');
      setEl('minesRevealed',     '0 / ' + (25 - minesCount));
      setEl('cashoutAmount',     fmtMoney(bet) + '₽');
      setBar(0);
      setStatus('Открывай клетки — избегай мин!', '');
      updateGlobalBalance(data.balance);
      setEl('minesBalance', fmtMoney(data.balance) + '₽');
    });
  };

  // REVEAL
  function doReveal(cellIndex) {
    apiPost('/api/game.php', { action:'mines_reveal', cell: cellIndex }).then(function(data) {
      var cell = document.getElementById('cell-' + cellIndex);

      if (!data.ok) {
        showToast(data.msg || 'Ошибка', 'error');
        if (cell) cell.disabled = false;
        return;
      }

      if (data.hit_mine) {
        if (cell) { cell.className = 'mine-cell cell-mine-hit'; cellIcon(cell, 'mine-hit'); }
        (data.mine_pos || []).forEach(function(pos) {
          if (pos === cellIndex) return;
          var c = document.getElementById('cell-' + pos);
          if (c) { c.className = 'mine-cell cell-mine-show'; cellIcon(c, 'mine-show'); }
        });
        document.querySelectorAll('.mine-cell').forEach(function(c) { c.disabled = true; });
        minesActive = false;
        setEl('minesStartBtn',   null, { display: '' });
        setEl('minesCashoutBtn', null, { display: 'none' });
        setBar(0);
        setStatus('💥 Попал на мину! Игра окончена.', 'gs-lose');
        showSeed(data.server_seed);
        updateGlobalBalance(data.balance);
        setEl('minesBalance', fmtMoney(data.balance) + '₽');
        showToast('💥 Мина! Проигрыш.', 'error');
        return;
      }

      // Safe
      if (cell) { cell.className = 'mine-cell cell-safe-revealed'; cellIcon(cell, 'safe'); }
      minesRevealed = data.revealed || [];
      var safeTotal = 25 - minesCount;
      var pct = Math.min(100, (minesRevealed.length / safeTotal) * 100);

      setEl('minesMultiplier', fmtMult(data.multiplier));
      setEl('minesPotential',  fmtMoney(data.potential) + '₽');
      setEl('minesRevealed',   minesRevealed.length + ' / ' + safeTotal);
      setEl('cashoutAmount',   fmtMoney(data.potential) + '₽');
      setBar(pct);
      setStatus('✦ Безопасно! Множитель ' + fmtMult(data.multiplier), 'gs-win');

      if (data.all_safe) {
        document.querySelectorAll('.mine-cell').forEach(function(c) { c.disabled = true; });
        minesActive = false;
        setEl('minesStartBtn',   null, { display: '' });
        setEl('minesCashoutBtn', null, { display: 'none' });
        setStatus('🎉 Все клетки открыты! +' + fmtMoney(data.potential) + '₽', 'gs-win');
        showSeed(data.server_seed);
        updateGlobalBalance(data.balance);
        setEl('minesBalance', fmtMoney(data.balance) + '₽');
        showToast('🎉 Все открыты! +' + fmtMoney(data.potential) + '₽', 'gold', 5000);
      }
    });
  }

  // CASHOUT
  window.minesCashout = function() {
    if (!minesActive || minesRevealed.length === 0) {
      showToast('Откройте хотя бы одну клетку', 'error'); return;
    }
    var cashBtn = document.getElementById('minesCashoutBtn');
    if (cashBtn) cashBtn.disabled = true;

    apiPost('/api/game.php', { action:'mines_cashout' }).then(function(data) {
      if (cashBtn) cashBtn.disabled = false;
      if (!data.ok) { showToast(data.msg || 'Ошибка', 'error'); return; }

      document.querySelectorAll('.mine-cell').forEach(function(c) { c.disabled = true; });
      minesActive = false;

      (data.mine_pos || []).forEach(function(pos) {
        var c = document.getElementById('cell-' + pos);
        if (c) { c.className = 'mine-cell cell-mine-show'; cellIcon(c, 'mine-show'); }
      });

      setEl('minesStartBtn',   null, { display: '' });
      setEl('minesCashoutBtn', null, { display: 'none' });
      setStatus('💰 Забрал ' + fmtMoney(data.payout) + '₽ (' + fmtMult(data.multiplier) + ')', 'gs-win');
      showSeed(data.server_seed);
      updateGlobalBalance(data.balance);
      setEl('minesBalance', fmtMoney(data.balance) + '₽');
      showToast('💰 +' + fmtMoney(data.payout) + '₽', 'success');
    });
  };

  function setStatus(text, cls) {
    var el = document.getElementById('minesStatus');
    if (!el) return;
    el.className = 'game-status' + (cls ? ' ' + cls : '');
    el.textContent = text;
  }
  function setBar(pct) { setEl('multBar', null, { width: pct + '%' }); }
  function showSeed(seed) {
    if (!seed) return;
    setEl('minesServerSeed', seed);
    setEl('minesServerSeedRow', null, { display: '' });
  }
}

// ═══════════════════════════════════════════════════════════
// HI-LO GAME
// ═══════════════════════════════════════════════════════════

if (document.getElementById('hiloCard1')) {

  var hiloActive   = false;
  var hiloNum1Val  = null;
  var hiloNextData = null;

  window.hiloInit = function() {
    apiPost('/api/game.php', { action: 'hilo_init' }).then(function(data) {
      if (!data.ok) { showToast('Ошибка загрузки игры', 'error'); return; }
      applyNum1(data.num1);
      updateProbBar(data.num1);
      updateMults(data.mult_higher, data.mult_lower);
      hiloActive = true;
      setFairInfo(data.client_seed, data.nonce, '(раскроется после игры)');
    });
  };

  function applyNum1(num) {
    hiloNum1Val = num;
    setEl('hiloNum1', num);
    var c = document.getElementById('hiloCard1');
    if (c) c.className = 'hilo-card';
  }

  function updateProbBar(num) {
    var higher = Math.max(0, 100 - parseInt(num));
    var lower  = Math.max(0, parseInt(num) - 1);
    setEl('hpbHigher', null, { width: higher + '%' });
    setEl('hpbEqual',  null, { width: '1%' });
    setEl('hpbLower',  null, { width: lower  + '%' });
    setEl('hpb-higher', '📈 Выше (' + higher + '%)');
    setEl('hpb-lower',  '📉 Ниже (' + lower  + '%)');
  }

  function updateMults(higher, lower) {
    setEl('hiloHigherMult', isFinite(higher) && higher > 0 ? fmtMult(higher) : '×∞');
    setEl('hiloLowerMult',  isFinite(lower)  && lower  > 0 ? fmtMult(lower)  : '×∞');
  }

  function setFairInfo(cs, nonce, ss) {
    setEl('hiloFairBlock', null, { display: '' });
    if (cs    !== null && cs    !== undefined) setEl('hiloClientSeed', cs);
    if (nonce !== null && nonce !== undefined) setEl('hiloNonce',      nonce);
    if (ss    !== null && ss    !== undefined) setEl('hiloServerSeed', ss);
  }

  window.hiloPlay = function(guess) {
    if (!hiloActive) { showToast('Игра не инициализирована', 'error'); return; }
    var bet = parseFloat(document.getElementById('hiloBet') && document.getElementById('hiloBet').value || 0);
    if (bet <= 0) { showToast('Введите ставку', 'error'); return; }

    setEl('hiloHigherBtn', null, { disabled: true });
    setEl('hiloLowerBtn',  null, { disabled: true });
    hiloActive = false;

    apiPost('/api/game.php', { action:'hilo_play', bet: bet, guess: guess }).then(function(data) {
      if (!data.ok) {
        showToast(data.msg || 'Ошибка', 'error');
        setEl('hiloHigherBtn', null, { disabled: false });
        setEl('hiloLowerBtn',  null, { disabled: false });
        hiloActive = true;
        return;
      }

      // Show card 2
      var card2 = document.getElementById('hiloCard2');
      if (card2) {
        card2.classList.remove('hilo-card--mystery');
        card2.classList.add(data.win ? 'hc-win' : 'hc-lose');
      }
      var num2el = document.getElementById('hiloNum2');
      if (num2el) { num2el.textContent = data.num2; num2el.classList.remove('hc-mystery'); }

      var card1 = document.getElementById('hiloCard1');
      if (card1 && data.win) card1.classList.add('hc-win');

      if (data.win) {
        showHiloResult('🎉 ПОБЕДА! +' + fmtMoney(data.payout) + '₽  (' + fmtMult(data.multiplier) + ')', 'win');
        showToast('🎉 +' + fmtMoney(data.payout) + '₽', 'success');
      } else {
        showHiloResult('💸 Проигрыш. Следующее было: ' + data.num2, 'lose');
        showToast('💸 Проигрыш', 'error');
      }

      setFairInfo(null, null, data.server_seed);
      updateGlobalBalance(data.balance);
      setEl('hiloBalance', fmtMoney(data.balance) + '₽');
      setEl('hiloNewGameBtn', null, { display: '' });
      hiloNextData = data;
    });
  };

  window.hiloNewGame = function() {
    if (!hiloNextData) { hiloInit(); return; }
    var d = hiloNextData;
    hiloNextData = null;

    var card1 = document.getElementById('hiloCard1');
    var card2 = document.getElementById('hiloCard2');
    if (card1) card1.className = 'hilo-card';
    if (card2) card2.className = 'hilo-card hilo-card--mystery';

    setEl('hiloNum1', d.next_num);
    var num2el = document.getElementById('hiloNum2');
    if (num2el) { num2el.textContent = '???'; num2el.className = 'hc-number hc-mystery'; }

    hiloNum1Val = d.next_num;
    hiloActive  = true;

    updateProbBar(d.next_num);
    updateMults(d.mult_higher, d.mult_lower);
    setFairInfo(d.next_client_seed, d.next_nonce, '(раскроется после игры)');
    hideHiloResult();

    setEl('hiloNewGameBtn', null, { display: 'none' });
    setEl('hiloHigherBtn',  null, { disabled: false });
    setEl('hiloLowerBtn',   null, { disabled: false });
  };

  function showHiloResult(msg, type) {
    var el = document.getElementById('hiloResult');
    if (!el) return;
    el.style.display = '';
    el.className = 'hilo-result ' + (type === 'win' ? 'win-msg' : 'lose-msg');
    el.textContent = msg;
  }
  function hideHiloResult() {
    var el = document.getElementById('hiloResult');
    if (el) el.style.display = 'none';
  }

  var hiloBetInput = document.getElementById('hiloBet');
  if (hiloBetInput) {
    hiloBetInput.addEventListener('input', function() {
      setEl('hiloDisplayBet', fmtMoney(this.value || 0) + '₽');
    });
  }
}

// ─── LIVE WINS ────────────────────────────────────────────
var winsGrid = document.getElementById('winsGrid');
if (winsGrid) {
  var lastWinId = null;
  setInterval(function() {
    fetch('/api/game.php?action=last_wins', { credentials:'same-origin' })
      .then(function(r){ return r.json(); })
      .then(function(data) {
        if (!data.ok || !data.wins || !data.wins.length) return;
        var w = data.wins[0];
        if (!w || w.id == lastWinId) return;
        lastWinId = w.id;
        var initials = String(w.username).substring(0,2).toUpperCase();
        var card = document.createElement('div');
        card.className = 'win-card ' + (w.game_type === 'mines' ? 'wc-mines' : 'wc-hilo');
        card.innerHTML =
          '<div class="wc-avatar" style="--acolor:' + escHtml(w.avatar_color||'#00e676') + '">' + escHtml(initials) + '</div>' +
          '<div class="wc-info"><span class="wc-user">' + escHtml(w.username) + '</span>' +
          '<span class="wc-game">' + (w.game_type==='mines'?'💣 Мины':'🎯 Хайло') + '</span></div>' +
          '<div class="wc-right"><span class="wc-payout">+' + fmtMoney(w.payout) + '₽</span>' +
          '<span class="wc-mult">×' + parseFloat(w.multiplier||0).toFixed(2) + '</span></div>';
        winsGrid.insertBefore(card, winsGrid.firstChild);
        var all = winsGrid.querySelectorAll('.win-card');
        if (all.length > 12) all[all.length-1].remove();
      }).catch(function(){});
  }, 5000);
}
