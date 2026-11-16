<?php
// Espera definidos por quem incluir: $activeNav, $pageTitle, $pageSubtitle
$logado = \App\Core\Auth::usuario();
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Alucar — <?= e($pageTitle) ?></title>
<link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
<link rel="stylesheet" href="<?= url('assets/css/forms.css') ?>">
<link rel="shortcut icon" href="<?= url('assets/img/Favicon.ico') ?>" type="image/x-icon">
</head>
<body>

<?php include __DIR__ . '/aside.php'; ?>

<main class="main">

  <div class="topbar">
    <div>
      <h2><?= e($pageTitle) ?></h2>
      <p><?= e($pageSubtitle) ?></p>
    </div>
    <div class="topbar-right">
      <div class="bell-wrap">
        <img src="<?= url('assets/img/topbar/Sino.svg') ?>" alt="Notificações">
        <span class="bell-dot"></span>
      </div>
      <div class="admin-chip">
        <div class="admin-avatar">
          <img src="<?= url('assets/img/topbar/user.svg') ?>" alt="">
        </div>
        <?= e($logado->getNome()) ?> · <?= e($logado->getTipo()->rotulo()) ?>
      </div>
    </div>
  </div>

  <?php \App\Core\Flash::exibir(); ?>
