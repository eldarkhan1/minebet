<?php
// POST handling is done in index.php before any output.
// $error is set there if login failed.
$ref_param = isset($_GET['ref']) ? '&ref=' . esc($_GET['ref']) : '';
?>
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo">◈ MineBet</div>
    <h1 class="auth-title">Вход в аккаунт</h1>

    <?php if (!empty($error)): ?>
      <div class="alert alert-error"><?= esc($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/index.php?page=login<?= isset($_GET['redirect']) ? '&redirect='.esc($_GET['redirect']) : '' ?>" class="auth-form">
      <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">

      <div class="form-group">
        <label class="form-label" for="f_login">Логин или Email</label>
        <input type="text" id="f_login" name="login" class="form-input"
               placeholder="username или email@mail.ru"
               value="<?= esc($_POST['login'] ?? '') ?>"
               required autocomplete="username">
      </div>

      <div class="form-group">
        <label class="form-label" for="f_password">Пароль</label>
        <div class="input-eye-wrap">
          <input type="password" id="f_password" name="password" class="form-input"
                 placeholder="••••••••" required autocomplete="current-password">
          <button type="button" class="eye-btn" data-target="f_password">👁</button>
        </div>
      </div>

      <button type="submit" class="btn btn-green btn-block btn-lg">Войти</button>
    </form>

    <div class="auth-footer">
      Нет аккаунта?
      <a href="/index.php?page=register<?= $ref_param ?>">Зарегистрироваться</a>
    </div>
  </div>
</div>
