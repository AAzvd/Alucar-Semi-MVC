<?php

declare(strict_types=1);

/**
 * Testes automatizados sem dependências:  php tests/run.php
 * Usam um banco SQLite em memória, então não precisam de MySQL.
 */

if (PHP_SAPI !== 'cli') {
    exit('Execute pelo terminal: php tests/run.php');
}

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;
use App\Enums\StatusAluguel;
use App\Enums\StatusCarro;
use App\Enums\TipoUsuario;
use App\Exceptions\AcessoNegadoException;
use App\Exceptions\RegraDeNegocioException;
use App\Models\Aluguel;
use App\Models\Carro;
use App\Models\Usuario;
use App\Repositories\AluguelRepository;
use App\Repositories\CarroRepository;
use App\Repositories\UsuarioRepository;
use App\Services\AluguelService;
use App\Services\AuthService;
use App\Services\CarroService;
use App\Services\UsuarioService;

// ---------------------------------------------------------------- mini framework
$total = 0;
$falhas = [];

function teste(string $nome, callable $corpo): void
{
    global $total, $falhas;
    $total++;
    try {
        $corpo();
        echo "  ok   $nome\n";
    } catch (Throwable $e) {
        $falhas[] = $nome;
        echo "  FALHOU  $nome\n          " . $e->getMessage() . "\n";
    }
}

function afirma(bool $condicao, string $mensagem = 'afirmação falsa'): void
{
    if (!$condicao) {
        throw new RuntimeException($mensagem);
    }
}

function igual(mixed $esperado, mixed $atual, string $contexto = ''): void
{
    if ($esperado !== $atual) {
        throw new RuntimeException(
            trim("$contexto esperado " . var_export($esperado, true) . ', veio ' . var_export($atual, true))
        );
    }
}

/** Confere que $acao lança a exceção $classe (e, opcionalmente, com um trecho na mensagem). */
function lanca(string $classe, callable $acao, ?string $trecho = null): void
{
    try {
        $acao();
    } catch (Throwable $e) {
        if (!$e instanceof $classe) {
            throw new RuntimeException("esperava $classe, veio " . $e::class . ': ' . $e->getMessage());
        }
        if ($trecho !== null && !str_contains($e->getMessage(), $trecho)) {
            throw new RuntimeException("mensagem '{$e->getMessage()}' não contém '$trecho'");
        }
        return;
    }
    throw new RuntimeException("esperava $classe, mas nada foi lançado");
}

/** Banco novo e vazio + serviços montados (como o Container faz em produção). */
function ambiente(): object
{
    $pdo = new PDO('sqlite::memory:', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec(file_get_contents(__DIR__ . '/schema.sqlite.sql'));
    Database::usar($pdo);

    $usuarios = new UsuarioRepository($pdo);
    $carros = new CarroRepository($pdo);
    $alugueis = new AluguelRepository($pdo);

    return (object) [
        'pdo' => $pdo,
        'usuarios' => $usuarios,
        'carros' => $carros,
        'alugueis' => $alugueis,
        'usuarioService' => new UsuarioService($usuarios),
        'carroService' => new CarroService($carros),
        'aluguelService' => new AluguelService($pdo, $carros, $alugueis, $usuarios),
        'authService' => new AuthService($usuarios),
    ];
}

function novoUsuario(object $a, string $nome, TipoUsuario $tipo): Usuario
{
    $email = strtolower(str_replace(' ', '', $nome)) . '@teste.com';
    $a->usuarios->inserir(Usuario::cadastrar($nome, $email, 'senha1234', $tipo));
    return $a->usuarios->buscarPorEmail($email);
}

function novoCarro(object $a, string $placa = 'ABC1D23', float $diaria = 100.0): Carro
{
    $a->carros->inserir(Carro::cadastrar('Fiat', 'Uno', 2020, $placa, $diaria));
    foreach ($a->carros->listar() as $c) {
        if ($c->getPlaca() === strtoupper($placa)) {
            return $c;
        }
    }
    throw new RuntimeException('carro não encontrado');
}

$amanha = (new DateTimeImmutable('tomorrow'))->format('Y-m-d');
$daquiA3 = (new DateTimeImmutable('today +3 days'))->format('Y-m-d');
$daquiA5 = (new DateTimeImmutable('today +5 days'))->format('Y-m-d');

// ---------------------------------------------------------------- Carro
echo "\nCarro\n";

teste('normaliza a placa (maiúsculas, sem hífen)', function () {
    igual('ABC1234', Carro::cadastrar('Fiat', 'Uno', 2020, ' abc-1234 ', 100)->getPlaca());
});
teste('aceita placa Mercosul', function () {
    igual('ABC1D23', Carro::cadastrar('Fiat', 'Uno', 2020, 'abc1d23', 100)->getPlaca());
});
teste('rejeita placa inválida', function () {
    lanca(RegraDeNegocioException::class, fn() => Carro::cadastrar('Fiat', 'Uno', 2020, '12', 100), 'Placa');
});
teste('rejeita ano fora do intervalo', function () {
    lanca(RegraDeNegocioException::class, fn() => Carro::cadastrar('Fiat', 'Uno', 1800, 'ABC1234', 100), 'ano');
});
teste('rejeita diária zero ou negativa', function () {
    lanca(RegraDeNegocioException::class, fn() => Carro::cadastrar('Fiat', 'Uno', 2020, 'ABC1234', 0), 'diária');
});
teste('calcula o valor pelos dias', function () {
    igual(450.0, Carro::cadastrar('Fiat', 'Uno', 2020, 'ABC1234', 150)->calcularValor(3));
});
teste('não aluga duas vezes o mesmo objeto', function () {
    $c = Carro::cadastrar('Fiat', 'Uno', 2020, 'ABC1234', 100);
    $c->marcarComoAlugado();
    lanca(RegraDeNegocioException::class, fn() => $c->marcarComoAlugado(), 'disponível');
});
teste('liberar volta a disponível e bloqueia exclusão enquanto alugado', function () {
    $c = Carro::cadastrar('Fiat', 'Uno', 2020, 'ABC1234', 100);
    $c->marcarComoAlugado();
    afirma(!$c->podeSerExcluido());
    $c->liberar();
    afirma($c->estaDisponivel() && $c->podeSerExcluido());
});

// ---------------------------------------------------------------- Usuario
echo "\nUsuario\n";

teste('valida e-mail, nome e tamanho da senha', function () {
    lanca(RegraDeNegocioException::class, fn() => Usuario::cadastrar('Ana', 'sem-arroba', 'senha1234'), 'e-mail');
    lanca(RegraDeNegocioException::class, fn() => Usuario::cadastrar('  ', 'a@a.com', 'senha1234'), 'nome');
    lanca(RegraDeNegocioException::class, fn() => Usuario::cadastrar('Ana', 'a@a.com', '123'), 'senha');
});
teste('guarda a senha com hash e verifica', function () {
    $u = Usuario::cadastrar('Ana', 'ANA@a.com', 'senha1234');
    afirma($u->getSenhaHash() !== 'senha1234');
    afirma($u->verificarSenha('senha1234') && !$u->verificarSenha('errada'));
    igual('ana@a.com', $u->getEmail(), 'e-mail em minúsculas');
});
teste('matriz de permissões de edição/exclusão', function () {
    $admin = Usuario::hidratar(['id' => 1, 'nome' => 'A', 'email' => 'a@a.com', 'senha' => 'x', 'tipo' => 'admin']);
    $vend = Usuario::hidratar(['id' => 2, 'nome' => 'V', 'email' => 'v@a.com', 'senha' => 'x', 'tipo' => 'vendedor']);
    $cli = Usuario::hidratar(['id' => 3, 'nome' => 'C', 'email' => 'c@a.com', 'senha' => 'x', 'tipo' => 'cliente']);

    afirma($admin->podeEditar($vend) && $admin->podeEditar($cli));
    afirma($vend->podeEditar($cli) && $vend->podeEditar($vend));
    afirma(!$vend->podeEditar($admin), 'vendedor NÃO edita admin');
    afirma($cli->podeEditar($cli) && !$cli->podeEditar($vend));
    afirma($admin->podeExcluir($cli) && !$admin->podeExcluir($admin));
    afirma(!$vend->podeExcluir($cli), 'vendedor não exclui');
});

// ---------------------------------------------------------------- Aluguel (modelo)
echo "\nAluguel (regras do modelo)\n";

teste('calcula total e nasce confirmado', function () use ($amanha, $daquiA3) {
    $c = Carro::hidratar(['id' => 1, 'marca' => 'F', 'modelo' => 'U', 'ano' => 2020, 'placa' => 'ABC1234', 'valor_diaria' => 100, 'status' => 'disponivel']);
    $a = Aluguel::registrar($c, 5, null, new DateTimeImmutable($amanha), new DateTimeImmutable($daquiA3));
    igual(200.0, $a->getValorTotal());
    igual(StatusAluguel::Confirmado, $a->getStatus());
});
teste('rejeita data final igual/anterior à inicial', function () use ($amanha) {
    $c = Carro::hidratar(['id' => 1, 'marca' => 'F', 'modelo' => 'U', 'ano' => 2020, 'placa' => 'ABC1234', 'valor_diaria' => 100, 'status' => 'disponivel']);
    lanca(RegraDeNegocioException::class, fn() => Aluguel::registrar($c, 5, null, new DateTimeImmutable($amanha), new DateTimeImmutable($amanha)), 'depois');
});
teste('rejeita início no passado', function () {
    $c = Carro::hidratar(['id' => 1, 'marca' => 'F', 'modelo' => 'U', 'ano' => 2020, 'placa' => 'ABC1234', 'valor_diaria' => 100, 'status' => 'disponivel']);
    lanca(RegraDeNegocioException::class, fn() => Aluguel::registrar($c, 5, null, new DateTimeImmutable('2020-01-01'), new DateTimeImmutable('2020-01-05')), 'passado');
});

// ---------------------------------------------------------------- AluguelService (com banco)
echo "\nAluguelService (integração com banco)\n";

teste('cliente aluga para si: grava aluguel e marca o carro como alugado', function () use ($amanha, $daquiA3) {
    $a = ambiente();
    $cli = novoUsuario($a, 'Cliente Um', TipoUsuario::Cliente);
    $carro = novoCarro($a);

    $a->aluguelService->alugar($cli, 999 /* ignorado para cliente */, $carro->getId(), $amanha, $daquiA3);

    igual(StatusCarro::Alugado, $a->carros->buscarPorId($carro->getId())->getStatus());
    $lista = $a->aluguelService->listarPara($cli);
    igual(1, count($lista));
    igual($cli->getId(), (int) $lista[0]['cliente_id']);
    igual(null, $lista[0]['vendedor_id']);
    igual(200.0, (float) $lista[0]['valor_total']);
});
teste('vendedor aluga em nome do cliente e fica como responsável', function () use ($amanha, $daquiA3) {
    $a = ambiente();
    $vend = novoUsuario($a, 'Vendedor Um', TipoUsuario::Vendedor);
    $cli = novoUsuario($a, 'Cliente Um', TipoUsuario::Cliente);
    $carro = novoCarro($a);

    $a->aluguelService->alugar($vend, $cli->getId(), $carro->getId(), $amanha, $daquiA3);

    $lista = $a->aluguelService->listarPara($vend);
    igual($vend->getId(), (int) $lista[0]['vendedor_id']);
    igual($cli->getId(), (int) $lista[0]['cliente_id']);
});
teste('não aluga carro que já está alugado', function () use ($amanha, $daquiA3) {
    $a = ambiente();
    $c1 = novoUsuario($a, 'Cliente Um', TipoUsuario::Cliente);
    $c2 = novoUsuario($a, 'Cliente Dois', TipoUsuario::Cliente);
    $carro = novoCarro($a);

    $a->aluguelService->alugar($c1, 0, $carro->getId(), $amanha, $daquiA3);
    lanca(RegraDeNegocioException::class, fn() => $a->aluguelService->alugar($c2, 0, $carro->getId(), $amanha, $daquiA3), 'disponível');
    igual(1, count($a->aluguelService->listarPara(novoUsuario($a, 'Admin', TipoUsuario::Admin))), 'só 1 aluguel');
});
teste('trava de concorrência: status mudou depois da leitura -> recusa e não grava aluguel', function () use ($amanha, $daquiA3) {
    $a = ambiente();
    $cli = novoUsuario($a, 'Cliente Um', TipoUsuario::Cliente);
    $carro = novoCarro($a);

    // Simula "outra requisição" alugando o carro entre a leitura e a gravação.
    $obsoleto = $a->carros->buscarPorId($carro->getId());
    $a->pdo->exec("UPDATE carros SET status = 'alugado' WHERE id = " . (int) $carro->getId());
    $obsoleto->marcarComoAlugado();
    afirma($a->carros->atualizarStatus($obsoleto, StatusCarro::Disponivel) === false);
});
teste('rejeita datas inválidas e cliente inexistente, sem alterar o carro', function () use ($amanha, $daquiA3) {
    $a = ambiente();
    $vend = novoUsuario($a, 'Vendedor Um', TipoUsuario::Vendedor);
    $carro = novoCarro($a);

    lanca(RegraDeNegocioException::class, fn() => $a->aluguelService->alugar($vend, 12345, $carro->getId(), $amanha, $daquiA3), 'cliente');
    $cli = novoUsuario($a, 'Cliente Um', TipoUsuario::Cliente);
    lanca(RegraDeNegocioException::class, fn() => $a->aluguelService->alugar($vend, $cli->getId(), $carro->getId(), 'lixo', $daquiA3), 'datas');
    lanca(RegraDeNegocioException::class, fn() => $a->aluguelService->alugar($vend, $cli->getId(), $carro->getId(), $daquiA3, $amanha), 'depois');
    igual(StatusCarro::Disponivel, $a->carros->buscarPorId($carro->getId())->getStatus());
});
teste('devolver encerra o aluguel e libera o carro', function () use ($amanha, $daquiA3) {
    $a = ambiente();
    $admin = novoUsuario($a, 'Admin', TipoUsuario::Admin);
    $cli = novoUsuario($a, 'Cliente Um', TipoUsuario::Cliente);
    $carro = novoCarro($a);
    $a->aluguelService->alugar($cli, 0, $carro->getId(), $amanha, $daquiA3);
    $id = (int) $a->aluguelService->listarPara($admin)[0]['id'];

    $a->aluguelService->devolver($admin, $id);

    igual(StatusCarro::Disponivel, $a->carros->buscarPorId($carro->getId())->getStatus());
    igual(StatusAluguel::Finalizado, $a->alugueis->buscarPorId($id)->getStatus());
    igual(100.0 * 2, $a->alugueis->faturamento());
});
teste('devolver duas vezes falha e NÃO libera um carro já alugado por outra pessoa', function () use ($amanha, $daquiA3, $daquiA5) {
    $a = ambiente();
    $admin = novoUsuario($a, 'Admin', TipoUsuario::Admin);
    $c1 = novoUsuario($a, 'Cliente Um', TipoUsuario::Cliente);
    $c2 = novoUsuario($a, 'Cliente Dois', TipoUsuario::Cliente);
    $carro = novoCarro($a);

    $a->aluguelService->alugar($c1, 0, $carro->getId(), $amanha, $daquiA3);
    $id1 = (int) $a->aluguelService->listarPara($admin)[0]['id'];
    $a->aluguelService->devolver($admin, $id1);
    $a->aluguelService->alugar($c2, 0, $carro->getId(), $daquiA3, $daquiA5); // outro cliente pega o carro

    lanca(RegraDeNegocioException::class, fn() => $a->aluguelService->devolver($admin, $id1), 'encerrado');
    igual(StatusCarro::Alugado, $a->carros->buscarPorId($carro->getId())->getStatus(), 'carro do c2 continua alugado');
});
teste('permissões da devolução: cliente nunca; vendedor só os próprios; admin todos', function () use ($amanha, $daquiA3) {
    $a = ambiente();
    $admin = novoUsuario($a, 'Admin', TipoUsuario::Admin);
    $v1 = novoUsuario($a, 'Vendedor Um', TipoUsuario::Vendedor);
    $v2 = novoUsuario($a, 'Vendedor Dois', TipoUsuario::Vendedor);
    $cli = novoUsuario($a, 'Cliente Um', TipoUsuario::Cliente);
    $carro = novoCarro($a);
    $a->aluguelService->alugar($v1, $cli->getId(), $carro->getId(), $amanha, $daquiA3);
    $id = (int) $a->aluguelService->listarPara($admin)[0]['id'];

    lanca(AcessoNegadoException::class, fn() => $a->aluguelService->devolver($cli, $id));
    lanca(AcessoNegadoException::class, fn() => $a->aluguelService->devolver($v2, $id));
    $a->aluguelService->devolver($v1, $id); // dono pode
    igual(StatusAluguel::Finalizado, $a->alugueis->buscarPorId($id)->getStatus());
});
teste('cada perfil lista só o seu recorte', function () use ($amanha, $daquiA3, $daquiA5) {
    $a = ambiente();
    $admin = novoUsuario($a, 'Admin', TipoUsuario::Admin);
    $v1 = novoUsuario($a, 'Vendedor Um', TipoUsuario::Vendedor);
    $c1 = novoUsuario($a, 'Cliente Um', TipoUsuario::Cliente);
    $c2 = novoUsuario($a, 'Cliente Dois', TipoUsuario::Cliente);
    $x = novoCarro($a, 'AAA1A11');
    $y = novoCarro($a, 'BBB2B22');

    $a->aluguelService->alugar($v1, $c1->getId(), $x->getId(), $amanha, $daquiA3);
    $a->aluguelService->alugar($c2, 0, $y->getId(), $amanha, $daquiA5);

    igual(2, count($a->aluguelService->listarPara($admin)), 'admin vê todos');
    igual(1, count($a->aluguelService->listarPara($v1)), 'vendedor vê os seus');
    igual(1, count($a->aluguelService->listarPara($c1)), 'cliente vê os seus');
    igual(1, count($a->aluguelService->listarPara($c2, StatusAluguel::Confirmado)));
    igual(0, count($a->aluguelService->listarPara($c2, StatusAluguel::Finalizado)));
});

// ---------------------------------------------------------------- CarroService
echo "\nCarroService\n";

teste('cliente não cadastra carro; vendedor sim; placa duplicada é recusada', function () {
    $a = ambiente();
    $cli = novoUsuario($a, 'Cliente Um', TipoUsuario::Cliente);
    $vend = novoUsuario($a, 'Vendedor Um', TipoUsuario::Vendedor);

    lanca(AcessoNegadoException::class, fn() => $a->carroService->cadastrar($cli, 'Fiat', 'Uno', 2020, 'ABC1234', 100));
    $a->carroService->cadastrar($vend, 'Fiat', 'Uno', 2020, 'ABC1234', 100);
    lanca(RegraDeNegocioException::class, fn() => $a->carroService->cadastrar($vend, 'Fiat', 'Mobi', 2021, 'abc-1234', 90), 'placa');
});
teste('não exclui carro alugado nem carro com histórico; exclui carro livre', function () use ($amanha, $daquiA3) {
    $a = ambiente();
    $admin = novoUsuario($a, 'Admin', TipoUsuario::Admin);
    $cli = novoUsuario($a, 'Cliente Um', TipoUsuario::Cliente);
    $usado = novoCarro($a, 'AAA1A11');
    $livre = novoCarro($a, 'BBB2B22');

    $a->aluguelService->alugar($cli, 0, $usado->getId(), $amanha, $daquiA3);
    lanca(RegraDeNegocioException::class, fn() => $a->carroService->excluir($admin, $usado->getId()), 'alugado');

    $id = (int) $a->aluguelService->listarPara($admin)[0]['id'];
    $a->aluguelService->devolver($admin, $id);
    lanca(RegraDeNegocioException::class, fn() => $a->carroService->excluir($admin, $usado->getId()), 'histórico');

    $a->carroService->excluir($admin, $livre->getId());
    igual(null, $a->carros->buscarPorId($livre->getId()));
});

// ---------------------------------------------------------------- UsuarioService / Auth
echo "\nUsuarioService e AuthService\n";

teste('cadastro público sempre vira cliente; admin escolhe o tipo', function () {
    $a = ambiente();
    $admin = novoUsuario($a, 'Admin', TipoUsuario::Admin);

    $a->usuarioService->cadastrar(null, 'Visitante', 'v@teste.com', 'senha1234', 'admin'); // tentativa de burlar
    igual(TipoUsuario::Cliente, $a->usuarios->buscarPorEmail('v@teste.com')->getTipo());

    $a->usuarioService->cadastrar($admin, 'Novo Vend', 'nv@teste.com', 'senha1234', 'vendedor');
    igual(TipoUsuario::Vendedor, $a->usuarios->buscarPorEmail('nv@teste.com')->getTipo());
});
teste('vendedor não consegue promover ninguém nem editar admin', function () {
    $a = ambiente();
    $admin = novoUsuario($a, 'Admin', TipoUsuario::Admin);
    $vend = novoUsuario($a, 'Vendedor Um', TipoUsuario::Vendedor);
    $cli = novoUsuario($a, 'Cliente Um', TipoUsuario::Cliente);

    lanca(AcessoNegadoException::class, fn() => $a->usuarioService->editar($vend, $admin->getId(), 'Admin', 'admin@teste.com', 'novasenha1', null));

    $a->usuarioService->editar($vend, $cli->getId(), 'Cliente Editado', 'cliente1@teste.com', null, 'admin'); // tenta promover
    $depois = $a->usuarios->buscarPorId($cli->getId());
    igual('Cliente Editado', $depois->getNome());
    igual(TipoUsuario::Cliente, $depois->getTipo(), 'tipo não muda');
});
teste('admin muda tipo de outros, mas não o próprio', function () {
    $a = ambiente();
    $admin = novoUsuario($a, 'Admin', TipoUsuario::Admin);
    $cli = novoUsuario($a, 'Cliente Um', TipoUsuario::Cliente);

    $a->usuarioService->editar($admin, $cli->getId(), 'Cliente Um', 'cliente1@teste.com', '', 'vendedor');
    igual(TipoUsuario::Vendedor, $a->usuarios->buscarPorId($cli->getId())->getTipo());

    $a->usuarioService->editar($admin, $admin->getId(), 'Admin', 'admin@teste.com', '', 'cliente');
    igual(TipoUsuario::Admin, $a->usuarios->buscarPorId($admin->getId())->getTipo());
});
teste('troca de senha só quando enviada; e-mail duplicado é recusado', function () {
    $a = ambiente();
    $cli = novoUsuario($a, 'Cliente Um', TipoUsuario::Cliente);
    novoUsuario($a, 'Cliente Dois', TipoUsuario::Cliente);

    $a->usuarioService->editar($cli, $cli->getId(), 'Cliente Um', 'cliente1@teste.com', '', null);
    afirma($a->usuarios->buscarPorId($cli->getId())->verificarSenha('senha1234'), 'senha antiga mantida');

    $a->usuarioService->editar($cli, $cli->getId(), 'Cliente Um', 'cliente1@teste.com', 'outrasenha9', null);
    afirma($a->usuarios->buscarPorId($cli->getId())->verificarSenha('outrasenha9'));

    lanca(RegraDeNegocioException::class, fn() => $a->usuarioService->editar($cli, $cli->getId(), 'Cliente Um', 'clientedois@teste.com', null, null), 'já está cadastrado');
});
teste('só admin exclui (e não a si mesmo); usuário com histórico não é excluído', function () use ($amanha, $daquiA3) {
    $a = ambiente();
    $admin = novoUsuario($a, 'Admin', TipoUsuario::Admin);
    $vend = novoUsuario($a, 'Vendedor Um', TipoUsuario::Vendedor);
    $cli = novoUsuario($a, 'Cliente Um', TipoUsuario::Cliente);
    $outro = novoUsuario($a, 'Cliente Dois', TipoUsuario::Cliente);

    lanca(AcessoNegadoException::class, fn() => $a->usuarioService->excluir($vend, $cli->getId()));
    lanca(AcessoNegadoException::class, fn() => $a->usuarioService->excluir($admin, $admin->getId()));

    $a->aluguelService->alugar($cli, 0, novoCarro($a)->getId(), $amanha, $daquiA3);
    lanca(RegraDeNegocioException::class, fn() => $a->usuarioService->excluir($admin, $cli->getId()), 'histórico');

    $a->usuarioService->excluir($admin, $outro->getId());
    igual(null, $a->usuarios->buscarPorId($outro->getId()));
});
teste('painel: só gestor lista usuários; resumo conta ativos e atrasados', function () {
    $a = ambiente();
    $admin = novoUsuario($a, 'Admin', TipoUsuario::Admin);
    $cli = novoUsuario($a, 'Cliente Um', TipoUsuario::Cliente);
    $carro = novoCarro($a);

    lanca(AcessoNegadoException::class, fn() => $a->usuarioService->listarParaPainel($cli));

    // aluguel já vencido, inserido direto no banco (o modelo não permite criar no passado)
    $a->pdo->exec("INSERT INTO alugueis (cliente_id, carro_id, data_inicio, data_fim, valor_total, status)
                   VALUES ({$cli->getId()}, {$carro->getId()}, '2020-01-01', '2020-01-03', 200, 'confirmado')");
    foreach ($a->usuarioService->listarParaPainel($admin) as $linha) {
        if ($linha['usuario']->isCliente()) {
            igual(1, $linha['ativos']);
            igual(1, $linha['atrasados']);
        }
    }
});
teste('login: senha certa entra; senha/e-mail errados dão a mesma mensagem', function () {
    $a = ambiente();
    novoUsuario($a, 'Cliente Um', TipoUsuario::Cliente);

    igual('Cliente Um', $a->authService->autenticar('CLIENTEUM@teste.com', 'senha1234')->getNome());
    lanca(RegraDeNegocioException::class, fn() => $a->authService->autenticar('clienteum@teste.com', 'errada'), 'inválidos');
    lanca(RegraDeNegocioException::class, fn() => $a->authService->autenticar('naoexiste@teste.com', 'senha1234'), 'inválidos');
});

// ---------------------------------------------------------------- resultado
echo "\n";
if ($falhas) {
    echo count($falhas) . " de $total testes falharam.\n";
    exit(1);
}
echo "Todos os $total testes passaram.\n";
