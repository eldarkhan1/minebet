<?php
$page_titles = [
    'home'     => 'Главная',
    'mines'    => '💣 Мины',
    'hilo'     => '🎯 Больше/Меньше',
    'profile'  => 'Профиль',
    'wallet'   => 'Кошелёк',
    'top'      => 'Топ игроков',
    'fair'     => 'Честная игра',
    'ref'      => 'Рефералы',
    'login'    => 'Вход',
    'register' => 'Регистрация',
];
$title = ($page_titles[$page] ?? 'MineBet') . ' — ' . APP_NAME;
$ref_url = isset($user) ? APP_URL . '/index.php?page=register&ref=' . $user['ref_code'] : '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= esc($title) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/main.css">
<meta name="description" content="MineBet — честное казино с Mines и Hi-Lo">
</head>
<body>

<!-- GRID CANVAS BACKGROUND -->
<canvas id="gridCanvas" aria-hidden="true"></canvas>

<!-- TOAST CONTAINER -->
<div id="toastContainer" aria-live="polite"></div>

<!-- HEADER -->
<header class="site-header" id="siteHeader">
  <div class="header-inner">
    <a href="/index.php?page=home" class="logo">
      <span class="logo-icon">◈</span>
      <span class="logo-text">Mine<em>Bet</em></span>
    </a>

    <nav class="main-nav" id="mainNav">
      <a href="/index.php?page=home"  class="nav-link <?= $page==='home'?'active':'' ?>">Главная</a>
      <a href="/index.php?page=mines" class="nav-link <?= $page==='mines'?'active':'' ?>">💣 Мины</a>
      <a href="/index.php?page=hilo"  class="nav-link <?= $page==='hilo'?'active':'' ?>">🎯 Хайло</a>
      <a href="/index.php?page=top"   class="nav-link <?= $page==='top'?'active':'' ?>">⭐ Топ</a>
      <a href="/index.php?page=fair"  class="nav-link <?= $page==='fair'?'active':'' ?>">🔐 Честность</a>
    </nav>

    <div class="header-right">
      <?php if ($user && !empty($user['id'])): ?>
        <div class="balance-pill">
          <span class="balance-icon">◈</span>
          <span class="balance-val" id="headerBalance"><?= fmt_money((float)($user['balance'] ?? 0)) ?>₽</span>
          <a href="/index.php?page=wallet" class="balance-add" title="Пополнить">+</a>
        </div>
        <div class="user-menu" id="userMenuTrigger">
          <div class="avatar" style="--acolor:<?= esc($user['avatar_color'] ?? '#00e676') ?>"><?= avatar_initials($user['username'] ?? '??') ?></div>
          <span class="uname"><?= esc($user['username'] ?? '') ?></span>
          <span class="chevron">▾</span>
          <div class="user-dropdown" id="userDropdown">
            <a href="/index.php?page=profile" class="dd-item">👤 Профиль</a>
            <a href="/index.php?page=wallet"  class="dd-item">💳 Кошелёк</a>
            <a href="/index.php?page=ref"     class="dd-item">🔗 Рефералы</a>
            <div class="dd-divider"></div>
            <a href="/index.php?page=logout"  class="dd-item dd-danger">⏻ Выход</a>
          </div>
        </div>
      <?php else: ?>
        <a href="/index.php?page=login"    class="btn btn-ghost btn-sm">Войти</a>
        <a href="/index.php?page=register" class="btn btn-green btn-sm">Регистрация</a>
      <?php endif; ?>
      <button class="burger" id="burgerBtn" aria-label="Меню">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</header>

<!-- MOBILE DRAWER -->
<div class="mobile-drawer" id="mobileDrawer">
  <nav class="drawer-nav">
    <a href="/index.php?page=home"  class="drawer-link">🏠 Главная</a>
    <a href="/index.php?page=mines" class="drawer-link">💣 Мины</a>
    <a href="/index.php?page=hilo"  class="drawer-link">🎯 Больше/Меньше</a>
    <a href="/index.php?page=top"   class="drawer-link">⭐ Топ игроков</a>
    <a href="/index.php?page=fair"  class="drawer-link">🔐 Честная игра</a>
    <?php if ($user): ?>
    <div class="drawer-divider"></div>
    <a href="/index.php?page=profile" class="drawer-link">👤 Профиль</a>
    <a href="/index.php?page=wallet"  class="drawer-link">💳 Кошелёк</a>
    <a href="/index.php?page=ref"     class="drawer-link">🔗 Рефералы</a>
    <a href="/index.php?page=logout"  class="drawer-link drawer-danger">⏻ Выход</a>
    <?php else: ?>
    <div class="drawer-divider"></div>
    <a href="/index.php?page=login"    class="drawer-link">Войти</a>
    <a href="/index.php?page=register" class="drawer-link drawer-green">Регистрация</a>
    <?php endif; ?>
  </nav>
</div>
<div class="drawer-overlay" id="drawerOverlay"></div>

<!-- MAIN WRAPPER -->
<main class="site-main" id="siteMain">
