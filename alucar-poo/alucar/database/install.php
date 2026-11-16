<?php

declare(strict_types=1);

/**
 * Instalador do banco. Só roda pelo terminal (CLI), nunca pelo navegador:
 * o antigo cria_banco.php dava DROP DATABASE para qualquer visitante que abrisse a URL.
 *
 *   php database/install.php                 cria o banco e as tabelas (se não existirem)
 *   php database/install.php --seed          ... e insere dados de demonstração
 *   php database/install.php --fresh --seed  apaga as tabelas antes (recomeça do zero)
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Execute pelo terminal: php database/install.php');
}

require_once __DIR__ . '/../app/bootstrap.php';

use App\Models\Aluguel;
use App\Models\Carro;
use App\Models\Usuario;
use App\Enums\TipoUsuario;
use App\Repositories\AluguelRepository;
use App\Repositories\CarroRepository;
use App\Repositories\UsuarioRepository;

$opcoes = array_slice($argv, 1);
$fresh = in_array('--fresh', $opcoes, true);
$seed = in_array('--seed', $opcoes, true);

$c = config('db');
if ($c['driver'] !== 'mysql') {
    exit("Este instalador é para MySQL/MariaDB.\n");
}

// 1) Conecta SEM escolher banco, para poder criá-lo.
$servidor = new PDO(
    sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $c['host'], $c['porta']),
    $c['usuario'],
    $c['senha'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$banco = str_replace('`', '', $c['nome']);
$servidor->exec("CREATE DATABASE IF NOT EXISTS `$banco` CHARACTER SET utf8mb4");
$servidor->exec("USE `$banco`");

// 2) Tabelas.
if ($fresh) {
    $servidor->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach (['alugueis', 'carros', 'usuarios'] as $tabela) {
        $servidor->exec("DROP TABLE IF EXISTS $tabela");
    }
    $servidor->exec('SET FOREIGN_KEY_CHECKS = 1');
    echo "Tabelas antigas removidas.\n";
}

$sql = preg_replace('/--.*$/m', '', file_get_contents(__DIR__ . '/schema.sql')); // tira comentários
foreach (array_filter(array_map('trim', explode(';', $sql))) as $comando) {
    $servidor->exec($comando);
}
echo "Banco '$banco' pronto.\n";

// 3) Dados de demonstração (passam pelas mesmas classes do sistema, então as senhas ficam com hash).
if ($seed) {
    $pdo = App\Core\Database::conexao();
    $usuarios = new UsuarioRepository($pdo);
    $carros = new CarroRepository($pdo);
    $alugueis = new AluguelRepository($pdo);

    if ($usuarios->contarPorTipo(TipoUsuario::Admin) > 0) {
        exit("Já existem usuários; seed ignorado (use --fresh para recomeçar).\n");
    }

    $usuarios->inserir(Usuario::cadastrar('Administrador', 'admin@alucar.com', 'admin1234', TipoUsuario::Admin));
    $usuarios->inserir(Usuario::cadastrar('Vera Vendedora', 'vendedor@alucar.com', 'vendedor1234', TipoUsuario::Vendedor));
    $usuarios->inserir(Usuario::cadastrar('Carlos Cliente', 'cliente@alucar.com', 'cliente1234', TipoUsuario::Cliente));
    $usuarios->inserir(Usuario::cadastrar('Marina Souza', 'marina@alucar.com', 'cliente1234', TipoUsuario::Cliente));

    $carros->inserir(Carro::cadastrar('Honda', 'Civic G10', 2019, 'ABC1D23', 180.00));
    $carros->inserir(Carro::cadastrar('Fiat', 'Uno Way', 2020, 'DEF2E34', 95.00));
    $carros->inserir(Carro::cadastrar('BYD', 'Atto 3', 2024, 'GHI3F45', 260.00));
    $carros->inserir(Carro::cadastrar('Volkswagen', 'Gol', 2018, 'JKL4G56', 110.00));

    echo "Dados de demonstração inseridos:\n";
    echo "  admin@alucar.com     / admin1234\n";
    echo "  vendedor@alucar.com  / vendedor1234\n";
    echo "  cliente@alucar.com   / cliente1234\n";
}
