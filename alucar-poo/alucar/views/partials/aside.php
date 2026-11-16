<?php
// Espera $activeNav definido por quem incluir este arquivo.
$logado = \App\Core\Auth::usuario();
?>
<aside class="sidebar">
  <div class="brand">
    <img class="logo-alucar" src="<?= url('assets/img/sidebar/logo.png') ?>" alt="Logo Alucar">
    <p>ALUGUEL COM ESTILO &amp; MISTÉRIO</p>
  </div>

  <nav>
    <?php if (!$logado->isCliente()): ?>
      <a class="nav-item <?= $activeNav === 'painel' ? 'active' : '' ?>" href="<?= url('views/dashboard.php') ?>">
        <img src="<?= url('assets/img/sidebar/quadrados.svg') ?>" alt="">
        Painel
      </a>
    <?php endif; ?>

    <a class="nav-item <?= $activeNav === 'carros' ? 'active' : '' ?>" href="<?= url('views/carros/carros.php') ?>">
      <img src="<?= url('assets/img/sidebar/carrinho.svg') ?>" alt="">
      Carros
    </a>

    <a class="nav-item <?= $activeNav === 'alugueis' ? 'active' : '' ?>" href="<?= url('views/alugueis/alugueis.php') ?>">
      <img src="<?= url('assets/img/sidebar/Alugueis.svg') ?>" alt="">
      Aluguéis
    </a>

    <?php if ($logado->isAdmin()): ?>
      <a class="nav-item" href="#">
        <img src="<?= url('assets/img/sidebar/Financeiro.svg') ?>" alt="">
        Financeiro
      </a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-spacer"></div>

  <?php if ($logado->podeGerenciarCarros()): ?>
    <a class="btn-new-vehicle" href="<?= url('views/carros/carros.php#cadastrar') ?>">
      <img src="<?= url('assets/img/sidebar/Cruz.svg') ?>" alt="">
      Adicionar Carro
    </a>
  <?php endif; ?>

  <hr class="divider">

  <div class="sidebar-bottom-item">
    <img src="<?= url('assets/img/sidebar/Engrenagem.svg') ?>" alt="">
    Configuração
  </div>

  <!-- Sair é POST (com token) para que um link malicioso não deslogue o usuário -->
  <form action="<?= url('src/logout.php') ?>" method="post" class="logout-form">
    <?= \App\Core\Csrf::campo() ?>
    <button type="submit" class="sidebar-bottom-item">
      <img src="<?= url('assets/img/sidebar/Sair.svg') ?>" alt="">
      Sair
    </button>
  </form>
</aside>
