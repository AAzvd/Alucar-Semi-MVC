<?php

declare(strict_types=1);

namespace App\Core;

use App\Repositories\AluguelRepository;
use App\Repositories\CarroRepository;
use App\Repositories\UsuarioRepository;
use App\Services\AluguelService;
use App\Services\AuthService;
use App\Services\CarroService;
use App\Services\PainelService;
use App\Services\UsuarioService;
use PDO;

/**
 * "Fábrica" central: é o ÚNICO lugar que sabe como montar cada objeto e quais
 * dependências ele recebe. Os services/repositories não criam nada sozinhos;
 * recebem o que precisam pelo construtor (injeção de dependência), o que
 * também permite trocá-los nos testes.
 */
final class Container
{
    private static array $instancias = [];

    private static function unico(string $chave, callable $fabrica): object
    {
        return self::$instancias[$chave] ??= $fabrica();
    }

    public static function pdo(): PDO
    {
        return Database::conexao();
    }

    // ---- Repositories (falam com o banco) ----
    public static function usuarioRepository(): UsuarioRepository
    {
        return self::unico('usuarioRepo', fn() => new UsuarioRepository(self::pdo()));
    }

    public static function carroRepository(): CarroRepository
    {
        return self::unico('carroRepo', fn() => new CarroRepository(self::pdo()));
    }

    public static function aluguelRepository(): AluguelRepository
    {
        return self::unico('aluguelRepo', fn() => new AluguelRepository(self::pdo()));
    }

    // ---- Services (regras de negócio) ----
    public static function authService(): AuthService
    {
        return self::unico('authService', fn() => new AuthService(self::usuarioRepository()));
    }

    public static function usuarioService(): UsuarioService
    {
        return self::unico('usuarioService', fn() => new UsuarioService(self::usuarioRepository()));
    }

    public static function carroService(): CarroService
    {
        return self::unico('carroService', fn() => new CarroService(self::carroRepository()));
    }

    public static function aluguelService(): AluguelService
    {
        return self::unico('aluguelService', fn() => new AluguelService(
            self::pdo(),
            self::carroRepository(),
            self::aluguelRepository(),
            self::usuarioRepository()
        ));
    }

    public static function painelService(): PainelService
    {
        return self::unico('painelService', fn() => new PainelService(
            self::carroRepository(),
            self::aluguelRepository(),
            self::usuarioRepository()
        ));
    }
}
