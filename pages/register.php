<?php
// POST handling is done in index.php before any output.
// $error is set there if registration failed.
$ref_code = $_GET['ref'] ?? $_POST['ref_code'] ?? '';
?>
<div class="auth-page">
  <div class="auth-card auth-card--wide">
    <div class="auth-logo">◈ MineBet</div>
    <h1 class="auth-title">Создать аккаунт</h1>

    <?php if (!empty($error)): ?>
      <div class="alert alert-error"><?= esc($error) ?></div>
    <?php endif; ?>

    <?php if ($ref_code): ?>
      <div class="alert alert-success">
        🎁 Реферальный бонус активирован! +<?= REF_BONUS_NEW_USER ?>₽ при регистрации
      </div>
    <?php endif; ?>

    <form method="POST" action="/index.php?page=register" class="auth-form">
      <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="r_username">Имя пользователя</label>
          <input type="text" id="r_username" name="username" class="form-input"
                 placeholder="от 3 символов, латиница"
                 value="<?= esc($_POST['username'] ?? '') ?>"
                 required pattern="[a-zA-Z0-9_]{3,20}" autocomplete="username">
          <span class="form-hint">Только буквы, цифры и _</span>
        </div>
        <div class="form-group">
          <label class="form-label" for="r_email">Email</label>
          <input type="email" id="r_email" name="email" class="form-input"
                 placeholder="your@email.com"
                 value="<?= esc($_POST['email'] ?? '') ?>"
                 required autocomplete="email">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="r_password">Пароль</label>
        <div class="input-eye-wrap">
          <input type="password" id="r_password" name="password" class="form-input"
                 placeholder="минимум 6 символов"
                 required autocomplete="new-password">
          <button type="button" class="eye-btn" data-target="r_password">👁</button>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="r_ref">
          Реферальный код <span class="form-optional">(необязательно)</span>
        </label>
        <input type="text" id="r_ref" name="ref_code" class="form-input"
               placeholder="XXXXXXXX"
               value="<?= esc($ref_code) ?>">
      </div>

      <div class="reg-bonus-row">
        <div class="reg-bonus-item">🔗 <strong>С реф. кодом:</strong> +<?= REF_BONUS_NEW_USER ?>₽</div>
        <div class="reg-bonus-item">🎁 <strong>Пригласи друга:</strong> +<?= REF_BONUS_REFERRER ?>₽</div>
      </div>

      <button type="submit" class="btn btn-green btn-block btn-lg">Создать аккаунт</button>
    </form>

    <div class="auth-footer">
      Уже есть аккаунт? <a href="/index.php?page=login">Войти</a>
    </div>
  </div>
</div>
