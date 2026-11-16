<?php
// Formulário de cadastro de usuário. Espera $mostrarTipo (bool): só o admin escolhe o tipo.
$mostrarTipo ??= false;
?>
<form action="<?= url('src/usuarios/criaUsuario.php') ?>" method="post" class="user-form">
  <?= \App\Core\Csrf::campo() ?>
  <label>Nome
    <input type="text" name="nome" required maxlength="255">
  </label>
  <label>Email
    <input type="email" name="email" required maxlength="255">
  </label>
  <label>Senha (mín. 8 caracteres)
    <input type="password" name="senha" required minlength="8">
  </label>

  <?php if ($mostrarTipo): ?>
    <label>Tipo
      <select name="tipo">
        <?php foreach (\App\Enums\TipoUsuario::cases() as $tipo): ?>
          <option value="<?= e($tipo->value) ?>"><?= e($tipo->rotulo()) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  <?php endif; ?>

  <button type="submit" class="btn-add">Cadastrar</button>
</form>
