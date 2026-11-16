<?php
require_once __DIR__ . '/../../app/bootstrap.php';

use App\Enums\StatusAluguel;

[
    'ator' => $ator,
    'filtro' => $filtro,
    'alugueis' => $alugueis,
    'clientes' => $clientes,
    'carrosDisponiveis' => $carrosDisponiveis,
] = (new \App\Controllers\AluguelController())->index();

$activeNav = 'alugueis';
$pageTitle = 'Aluguéis';
$pageSubtitle = $ator->isCliente() ? 'Seus aluguéis, ativos e histórico.'
    : ($ator->isVendedor() ? 'Aluguéis que você realizou.' : 'Todos os aluguéis da locadora.');
$hoje = date('Y-m-d');
include __DIR__ . '/../partials/layout_start.php';
?>

<div class="panel" style="margin-bottom:28px;">
  <div class="panel-head">
    <h3>Alugar um carro</h3>
  </div>
  <div style="padding: 0 28px 28px;">
    <?php if (empty($carrosDisponiveis)): ?>
      <p>Nenhum carro disponível no momento.</p>
    <?php else: ?>
      <form action="<?= url('src/alugueis/criaAluguel.php') ?>" method="post" class="rental-form">
        <?= \App\Core\Csrf::campo() ?>
        <?php if (!$ator->isCliente()): ?>
          <label>Cliente
            <select name="cliente_id" required>
              <option value="">Selecione</option>
              <?php foreach ($clientes as $cliente): ?>
                <option value="<?= (int) $cliente->getId() ?>"><?= e($cliente->getNome()) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        <?php endif; ?>

        <label>Carro
          <select name="carro_id" required>
            <option value="">Selecione</option>
            <?php foreach ($carrosDisponiveis as $carro): ?>
              <option value="<?= (int) $carro->getId() ?>">
                <?= e($carro->getNomeCompleto() . ' - ' . $carro->getPlaca()) ?>
                (<?= e(dinheiro($carro->getValorDiaria())) ?>/dia)
              </option>
            <?php endforeach; ?>
          </select>
        </label>

        <label>Data início<input type="date" name="data_inicio" min="<?= $hoje ?>" required></label>
        <label>Data fim<input type="date" name="data_fim" min="<?= $hoje ?>" required></label>

        <button type="submit" class="btn-add">Alugar</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<div class="panel">
  <div class="panel-head">
    <h3><?= $ator->isCliente() ? 'Meus aluguéis' : 'Histórico de aluguéis' ?></h3>
  </div>

  <div class="filter-bar">
    <a href="alugueis.php" class="<?= $filtro === null ? 'active' : '' ?>">Todos</a>
    <?php foreach ([StatusAluguel::Confirmado, StatusAluguel::EmAndamento, StatusAluguel::Finalizado, StatusAluguel::Cancelado] as $opcao): ?>
      <a href="alugueis.php?status=<?= e($opcao->value) ?>" class="<?= $filtro === $opcao ? 'active' : '' ?>">
        <?= e($opcao->rotulo()) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <table style="margin-top:14px;">
    <thead>
      <tr>
        <?php if (!$ator->isCliente()): ?><th>CLIENTE</th><?php endif; ?>
        <th>CARRO</th>
        <?php if ($ator->isAdmin()): ?><th>VENDEDOR</th><?php endif; ?>
        <th>PERÍODO</th>
        <th>VALOR</th>
        <th>STATUS</th>
        <?php if ($ator->podeGerenciarUsuarios()): ?><th>AÇÕES</th><?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($alugueis)): ?>
        <tr><td colspan="6">Nenhum aluguel encontrado.</td></tr>
      <?php endif; ?>
      <?php foreach ($alugueis as $linha): ?>
        <?php $status = StatusAluguel::from($linha['status']); ?>
        <tr>
          <?php if (!$ator->isCliente()): ?><td class="vehicle-name"><?= e($linha['cliente_nome']) ?></td><?php endif; ?>
          <td class="vehicle-sub"><?= e($linha['marca'] . ' ' . $linha['modelo'] . ' (' . $linha['placa'] . ')') ?></td>
          <?php if ($ator->isAdmin()): ?>
            <td class="vehicle-sub"><?= $linha['vendedor_nome'] ? e($linha['vendedor_nome']) : 'Direto (sem vendedor)' ?></td>
          <?php endif; ?>
          <td class="vehicle-sub"><?= e($linha['data_inicio']) ?> a <?= e($linha['data_fim']) ?></td>
          <td class="price"><?= e(dinheiro((float) $linha['valor_total'])) ?></td>
          <td>
            <span class="status <?= $status->classeCss() ?>"><span class="dot <?= $status->classeCss() ?>"></span><?= e($status->rotulo()) ?></span>
          </td>
          <?php if ($ator->podeGerenciarUsuarios()): ?>
            <td>
              <?php
                $ehDono = $linha['vendedor_id'] !== null && (int) $linha['vendedor_id'] === $ator->getId();
                $podeDevolver = $status->estaAtivo() && ($ator->isAdmin() || $ehDono);
              ?>
              <?php if ($podeDevolver): ?>
                <form action="<?= url('src/alugueis/devolveCarro.php') ?>" method="post" class="inline-form"
                      onsubmit="return confirm('Confirmar devolução deste carro?')">
                  <?= \App\Core\Csrf::campo() ?>
                  <input type="hidden" name="id" value="<?= (int) $linha['id'] ?>">
                  <button type="submit" class="link-btn">Devolver</button>
                </form>
              <?php else: ?>
                <span class="vehicle-sub">—</span>
              <?php endif; ?>
            </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/../partials/layout_end.php'; ?>
