<?php
require_once __DIR__ . '/../app/bootstrap.php';

// Quem já está logado vai direto para a tela inicial do perfil.
if (\App\Core\Auth::logado()) {
    redirecionar(\App\Core\Auth::paginaInicial());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Alucar — Login</title>
<link rel="stylesheet" href="<?= url('assets/css/auth.css') ?>">
<link rel="stylesheet" href="<?= url('assets/css/forms.css') ?>">
<link rel="shortcut icon" href="<?= url('assets/img/Favicon.ico') ?>" type="image/x-icon">
</head>
<body>

<header class="topnav">
  <div class="logo-alucar"><img src="<?= url('assets/img/sidebar/logo.png') ?>" alt="logo alucar"></div>
</header>

<section class="hero">
  <div class="hero-inner">
    <div class="hero-copy">
      <div class="eyebrow">EXPERIÊNCIA NOTURNA SUPREMA</div>
      <h1>Onde a Performance Encontra o <span class="accent">Mistério</span>.</h1>
      <p>Domine a escuridão com nossa frota exclusiva de veículos de alto desempenho. Elegância gótica pra quem não se contenta com o comum.</p>
    </div>

    <div class="login-card">
      <h2>Acessar Portal</h2>
      <p>Insira suas credenciais pra continuar.</p>

      <?php \App\Core\Flash::exibir(); ?>

      <form action="<?= url('src/login.php') ?>" method="post">
        <?= \App\Core\Csrf::campo() ?>
        <div class="field-group">
          <label>E-MAIL</label>
          <div class="input-wrap">
            <input type="email" name="email" placeholder="Seu email" required>
          </div>
        </div>

        <div class="field-group">
          <label>SENHA</label>
          <div class="input-wrap">
            <input type="password" name="senha" placeholder="••••••••" required>
          </div>
        </div>

        <button type="submit" class="btn-block">ENTRAR</button>
      </form>

      <p class="card-foot">Ainda não possui um pacto? <a href="<?= url('views/criaUsuarioForm.php') ?>">Criar conta</a></p>
    </div>
  </div>
</section>

<footer class="site-footer">
  <h4>ALU<span>CAR</span></h4>
  <p class="quote">"O que é um homem? Um mísero monte de segredos. Mas os nossos aluguéis são baratos."</p>
  <p class="copyright">© 2026 Alucar. Todos os direitos reservados.</p>
</footer>

</body>
</html>
