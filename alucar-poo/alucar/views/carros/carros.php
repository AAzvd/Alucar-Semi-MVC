<?php
require_once __DIR__ . '/../../app/bootstrap.php';

['ator' => $ator, 'carros' => $carros] = (new \App\Controllers\CarroController())->index();

$activeNav = 'carros';
$pageTitle = 'Carros';
$pageSubtitle = $ator->podeGerenciarCarros() ? 'Gerencie o inventário da frota.' : 'Confira os carros disponíveis pra alugar.';
include __DIR__ . '/../partials/layout_start.php';
?>

<?php if ($ator->podeGerenciarCarros()): ?>
<div class="panel" id="cadastrar" style="margin-bottom:28px;">
  <div class="panel-head">
    <h3>Cadastrar novo carro</h3>
  </div>
  <div style="padding: 0 28px 28px;">
    <form action="<?= url('src/carros/criaCarro.php') ?>" method="post" class="car-form">
      <?= \App\Core\Csrf::campo() ?>
      <label>Marca<input type="text" name="marca" required></label>
      <label>Modelo<input type="text" name="modelo" required></label>
      <label>Ano<input type="number" name="ano" min="1950" max="<?= (int) date('Y') + 1 ?>" required></label>
      <label>Placa<input type="text" name="placa" maxlength="10" placeholder="ABC1D23" required></label>
      <label>Diária (R$)<input type="number" step="0.01" min="0.01" name="valor_diaria" required></label>
      <button type="submit" class="btn-add">Cadastrar</button>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="panel">
  <div class="panel-head">
    <h3>Inventário de Carros</h3>
    <div class="search-box">
      <img src="<?= url('assets/img/topbar/Lupa.svg') ?>" alt="">
      <input type="text" id="buscaCarro" placeholder="Procurar carro ou placa...">
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th>CARRO</th>
        <th>PLACA</th>
        <th>STATUS</th>
        <th>PREÇO/DIA</th>
        <?php if ($ator->podeGerenciarCarros()): ?><th>AÇÕES</th><?php endif; ?>
      </tr>
    </thead>
    <tbody id="tabelaCarros">
      <?php if (empty($carros)): ?>
        <tr><td colspan="5">Nenhum carro cadastrado ainda.</td></tr>
      <?php endif; ?>
      <?php foreach ($carros as $carro): ?>
        <tr>
          <td>
            <div class="vehicle-cell">
              <div class="vehicle-thumb" style="background:linear-gradient(135deg,#2b2b2f,#0e0e10);"></div>
              <div>
                <div class="vehicle-name"><?= e($carro->getNomeCompleto()) ?></div>
                <div class="vehicle-sub"><?= $carro->getAno() ?></div>
              </div>
            </div>
          </td>
          <td class="plate"><?= e($carro->getPlaca()) ?></td>
          <td>
            <?php $status = $carro->getStatus(); ?>
            <span class="status <?= $status->classeCss() ?>"><span class="dot <?= $status->classeCss() ?>"></span><?= e($status->rotulo()) ?></span>
          </td>
          <td class="price"><?= e(dinheiro($carro->getValorDiaria())) ?></td>
          <?php if ($ator->podeGerenciarCarros()): ?>
            <td>
              <div class="actions">
                <?php if ($carro->podeSerExcluido()): ?>
                  <form action="<?= url('src/carros/excluiCarro.php') ?>" method="post" class="inline-form"
                        onsubmit="return confirm('Excluir este carro?')">
                    <?= \App\Core\Csrf::campo() ?>
                    <input type="hidden" name="id" value="<?= (int) $carro->getId() ?>">
                    <button type="submit" class="icon-btn" title="Excluir">
                      <img src="<?= url('assets/img/topbar/Lixeira.svg') ?>" alt="Excluir" style="width:16px;">
                    </button>
                  </form>
                <?php else: ?>
                  <span class="vehicle-sub" title="Devolva o carro antes de excluir">—</span>
                <?php endif; ?>
              </div>
            </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
document.getElementById('buscaCarro').addEventListener('input', function () {
  const termo = this.value.toLowerCase();
  document.querySelectorAll('#tabelaCarros tr').forEach(function (linha) {
    linha.style.display = linha.textContent.toLowerCase().includes(termo) ? '' : 'none';
  });
});
</script>

<?php include __DIR__ . '/../partials/layout_end.php'; ?>
