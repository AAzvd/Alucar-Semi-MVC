<?php
require_once __DIR__ . '/../app/bootstrap.php';

// Página pública de cadastro: quem já está logado não precisa dela.
if (\App\Core\Auth::logado()) {
    redirecionar(\App\Core\Auth::paginaInicial());
}

$mostrarTipo = false; // visitante sempre vira cliente
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Alucar — Criar conta</title>
  <link rel="stylesheet" href="<?= url('assets/css/auth.css') ?>">
  <link rel="stylesheet" href="<?= url('assets/css/forms.css') ?>">
  <link rel="shortcut icon" href="<?= url('assets/img/Favicon.ico') ?>" type="image/x-icon">
</head>
<body>
<section class="hero" style="min-height:100vh; display:flex; align-items:center;">
  <div class="hero-inner" style="grid-template-columns: 1fr; max-width:460px; margin:0 auto;">
    <div class="login-card">
      <h2>Criar conta</h2>
      <p>Cadastre-se pra alugar sem depender de vendedor.</p>

      <?php \App\Core\Flash::exibir(); ?>
      <?php include __DIR__ . '/partials/form_novo_usuario.php'; ?>

      <p class="card-foot">Já tem conta? <a href="<?= url('views/login.php') ?>">Entrar</a></p>
    </div>
  </div>
</section>
</body>
</html>
