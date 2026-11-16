<?php
require_once __DIR__ . '/../app/bootstrap.php';

// O controller confere login e permissão (e redireciona com mensagem se não puder).
['ator' => $ator, 'alvo' => $alvo] = (new \App\Controllers\UsuarioController())->formularioEdicao();

// Só admin muda o tipo, e nunca o do próprio usuário.
$podeMudarTipo = $ator->isAdmin() && !$ator->mesmoQue($alvo);

$activeNav = '';
$pageTitle = 'Editar usuário';
$pageSubtitle = 'Atualize os dados de ' . $alvo->getNome() . '.';
include __DIR__ . '/partials/layout_start.php';
?>

<div class="panel">
  <div class="panel-head">
    <h3>Dados do usuário</h3>
  </div>
  <div style="padding: 0 28px 28px;">
    <form action="<?= url('src/usuarios/editaUsuario.php') ?>" method="post" class="user-form">
      <?= \App\Core\Csrf::campo() ?>
      <input type="hidden" name="id" value="<?= (int) $alvo->getId() ?>">

      <label>Nome
        <input type="text" name="nome" value="<?= e($alvo->getNome()) ?>" required maxlength="255">
      </label>
      <label>Email
        <input type="email" name="email" value="<?= e($alvo->getEmail()) ?>" required maxlength="255">
      </label>
      <label>Senha (deixe em branco pra manter a atual)
        <input type="password" name="senha" placeholder="Nova senha (opcional)" minlength="8">
      </label>

      <?php if ($podeMudarTipo): ?>
        <label>Tipo
          <select name="tipo">
            <?php foreach (\App\Enums\TipoUsuario::cases() as $tipo): ?>
              <option value="<?= e($tipo->value) ?>" <?= $alvo->getTipo() === $tipo ? 'selected' : '' ?>>
                <?= e($tipo->rotulo()) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>
      <?php endif; ?>

      <button type="submit" class="btn-add">Salvar</button>
    </form>
  </div>
</div>

<?php include __DIR__ . '/partials/layout_end.php'; ?>
