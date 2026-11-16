<?php
require_once __DIR__ . '/../app/bootstrap.php';

// O controller prepara os dados; esta view só os exibe.
['ator' => $ator, 'resumo' => $resumo, 'linhas' => $linhas] = (new \App\Controllers\PainelController())->index();

$mostrarTipo = $ator->isAdmin(); // usado pelo formulário "Novo usuário"
$activeNav = 'painel';
$pageTitle = 'Painel';
$pageSubtitle = 'Bem-vindo(a), ' . $ator->getNome() . '. Status do sistema: Operational.';
include __DIR__ . '/partials/layout_start.php';
?>

<div class="stats">
  <div class="stat-card">
    <div class="stat-top">
      <img src="<?= url('assets/img/sidebar/carrinho.svg') ?>" alt="">
    </div>
    <div class="stat-label">CARROS DISPONÍVEIS</div>
    <div class="stat-value"><?= (int) $resumo['carrosDisponiveis'] ?></div>
  </div>

  <div class="stat-card">
    <div class="stat-top">
      <img src="<?= url('assets/img/sidebar/Alugueis.svg') ?>" alt="">
      <span class="stat-badge neutral">Ativos</span>
    </div>
    <div class="stat-label">ALUGUÉIS</div>
    <div class="stat-value"><?= (int) $resumo['alugueisAtivos'] ?></div>
  </div>

  <div class="stat-card">
    <div class="stat-top">
      <img src="<?= url('assets/img/sidebar/users.svg') ?>" alt="">
    </div>
    <div class="stat-label">CLIENTES</div>
    <div class="stat-value"><?= (int) $resumo['totalClientes'] ?></div>
  </div>

  <div class="stat-card">
    <div class="stat-top">
      <img src="<?= url('assets/img/topbar/Dinheiro.svg') ?>" alt="">
      <span class="stat-badge neutral">Concluídos</span>
    </div>
    <div class="stat-label">FATURAMENTO</div>
    <div class="stat-value"><?= e(dinheiro($resumo['faturamento'], 0)) ?></div>
  </div>
</div>

<div class="panel" id="usuarios" style="margin-bottom:28px;">
  <div class="panel-head">
    <h3>Novo usuário</h3>
  </div>
  <div style="padding: 0 28px 24px;">
    <?php include __DIR__ . '/partials/form_novo_usuario.php'; ?>
  </div>
</div>

<div class="panel">
  <div class="panel-head">
    <h3>Usuários</h3>
  </div>
  <table>
    <thead>
      <tr>
        <th>NOME</th>
        <th>EMAIL</th>
        <th>TIPO</th>
        <th>SITUAÇÃO</th>
        <th>AÇÕES</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($linhas as ['usuario' => $usuario, 'ativos' => $ativos, 'atrasados' => $atrasados]): ?>
        <tr>
          <td class="vehicle-name"><?= e($usuario->getNome()) ?></td>
          <td class="vehicle-sub"><?= e($usuario->getEmail()) ?></td>
          <td><span class="tag"><?= e($usuario->getTipo()->rotulo()) ?></span></td>
          <td>
            <?php if ($usuario->isCliente()): ?>
              <?php if ($atrasados > 0): ?>
                <span class="status rental"><span class="dot rental"></span>Atraso na devolução</span>
              <?php elseif ($ativos > 0): ?>
                <span class="status maintenance"><span class="dot maintenance"></span>Com aluguel</span>
              <?php else: ?>
                <span class="status available"><span class="dot available"></span>Sem aluguel</span>
              <?php endif; ?>
            <?php else: ?>
              <span class="vehicle-sub">—</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="actions">
              <?php if ($ator->podeEditar($usuario)): ?>
                <a href="<?= url('views/editaUsuarioForm.php?id=' . (int) $usuario->getId()) ?>" title="Editar">
                  <img src="<?= url('assets/img/topbar/Lapis.svg') ?>" alt="Editar" style="width:16px;">
                </a>
              <?php endif; ?>
              <?php if ($ator->podeExcluir($usuario)): ?>
                <form action="<?= url('src/usuarios/excluiUsuario.php') ?>" method="post" class="inline-form"
                      onsubmit="return confirm('Excluir este usuário?')">
                  <?= \App\Core\Csrf::campo() ?>
                  <input type="hidden" name="id" value="<?= (int) $usuario->getId() ?>">
                  <button type="submit" class="icon-btn" title="Excluir">
                    <img src="<?= url('assets/img/topbar/Lixeira.svg') ?>" alt="Excluir" style="width:16px;">
                  </button>
                </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/partials/layout_end.php'; ?>
