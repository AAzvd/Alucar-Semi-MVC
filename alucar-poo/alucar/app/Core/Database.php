<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

/**
 * Cria UMA conexão PDO por requisição e a reaproveita (padrão Singleton simples).
 * Substitui a antiga função pdo(), que abria uma conexão nova a cada consulta.
 */
final class Database
{
    private static ?PDO $conexao = null;

    public static function conexao(): PDO
    {
        return self::$conexao ??= self::criar(config('db'));
    }

    /** Troca a conexão (usado pelos testes para injetar um banco em memória). */
    public static function usar(?PDO $pdo): void
    {
        self::$conexao = $pdo;
    }

    private static function criar(array $c): PDO
    {
        $opcoes = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,      // erros viram exceções
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,              // prepared statements de verdade
        ];

        if ($c['driver'] === 'sqlite') {
            $pdo = new PDO('sqlite:' . $c['arquivo'], null, null, $opcoes);
            $pdo->exec('PRAGMA foreign_keys = ON');
            return $pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $c['host'],
            $c['porta'],
            $c['nome']
        );
        return new PDO($dsn, $c['usuario'], $c['senha'], $opcoes);
    }

    /**
     * Executa $acao dentro de uma transação: ou tudo é salvo, ou nada.
     * Se qualquer exceção acontecer, desfaz (rollback) e repassa o erro.
     */
    public static function transacao(PDO $pdo, callable $acao): mixed
    {
        $pdo->beginTransaction();
        try {
            $resultado = $acao();
            $pdo->commit();
            return $resultado;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
