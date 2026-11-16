# Alucar — Sistema de Aluguel de Carros

Sistema web de locadora de veículos feito em **PHP 8 puro (sem framework) + MySQL**, com arquitetura em camadas e **Programação Orientada a Objetos**.
Três perfis de acesso (cliente, vendedor e admin), controle de frota, aluguéis com cálculo de valor e devolução.

## Funcionalidades

| Perfil | O que pode fazer |
|---|---|
| **Cliente** | Criar a própria conta, ver o catálogo, alugar um carro para si, acompanhar seu histórico |
| **Vendedor** | Tudo do cliente + cadastrar/excluir carros, alugar em nome de um cliente, devolver os aluguéis que ele realizou, editar clientes |
| **Admin** | Tudo acima + painel com indicadores, criar/editar/excluir qualquer usuário, definir perfis, ver todos os aluguéis |

## Arquitetura

```
Navegador ──> views/ (HTML)          só exibem dados
          └─> src/  (endpoints)      1 linha: chamam um Controller
                       │
                 app/Controllers     leem a requisição, chamam o Service, redirecionam
                       │
                 app/Services        REGRAS DE NEGÓCIO e permissões (quem pode o quê)
                       │
          app/Models ◄─┴─► app/Repositories      Models = objetos com regras próprias
                                  │               Repositories = único lugar com SQL (PDO)
                               MySQL
```

```
app/
  Core/          Database (PDO), Auth (sessão), Csrf, Flash (mensagens), Container (monta os objetos)
  Enums/         TipoUsuario, StatusCarro, StatusAluguel
  Models/        Usuario, Carro, Aluguel          <- encapsulamento e validação
  Repositories/  UsuarioRepository, CarroRepository, AluguelRepository
  Services/      AluguelService, CarroService, UsuarioService, AuthService, PainelService
  Controllers/   AuthController, UsuarioController, CarroController, AluguelController, PainelController
  Exceptions/    RegraDeNegocioException, AcessoNegadoException
config/          configuração do banco
database/        schema.sql e install.php (instalador por terminal)
views/           telas (HTML + PHP de exibição)
src/             endpoints de ação (login, criar carro, alugar...)
tests/           32 testes automatizados (sem dependências)
```

### Conceitos de POO aplicados

- **Encapsulamento**: propriedades `private`; o status de um carro só muda por `marcarComoAlugado()` / `liberar()` (não existe `setStatus`).
- **Named constructors**: `Usuario::cadastrar()` valida e gera hash; `Usuario::hidratar()` reconstrói dados vindos do banco.
- **Enums** (PHP 8.1) no lugar de strings soltas como `'alugado'`.
- **Injeção de dependência**: services e repositories recebem o que precisam pelo construtor; `Container` é o único que os monta.
- **Separação de responsabilidades**: Controller → Service → Repository → Model.
- **Exceções de domínio**: regras violadas lançam `RegraDeNegocioException` com mensagem amigável.

## Decisões de segurança

- Senhas com `password_hash` / `password_verify`; senha mínima de 8 caracteres.
- Todas as consultas usam **prepared statements** (PDO com `EMULATE_PREPARES` desligado).
- **CSRF**: todo formulário POST carrega token; ações destrutivas (excluir, devolver, sair) só aceitam POST (antes eram links GET).
- **XSS**: toda saída HTML passa por `e()` (`htmlspecialchars`).
- Sessão com cookie `HttpOnly` + `SameSite=Lax` e `session_regenerate_id` no login.
- Permissões checadas **no Service** (não só escondendo botões): o usuário é recarregado do banco a cada requisição, então mudanças de perfil valem na hora.
- Mensagem de login idêntica para e-mail inexistente e senha errada.
- Erros internos vão para o log, nunca para a tela. Pastas internas bloqueadas com `.htaccess`.

## Integridade dos dados

- **Transação** ao alugar/devolver: aluguel e status do carro são gravados juntos, ou nenhum.
- **Trava contra corrida**: `UPDATE ... WHERE status = <anterior>` — se dois clientes tentam alugar o mesmo carro ao mesmo tempo, só um consegue.
- Não é possível devolver o mesmo aluguel duas vezes, nem excluir carro alugado ou com histórico.

## Como rodar (XAMPP / Laragon)

Requisitos: **PHP 8.1+** com `pdo_mysql`, MySQL ou MariaDB.

1. Copie a pasta `alucar` para `htdocs` (XAMPP) ou `www` (Laragon).
2. Inicie Apache e MySQL.
3. Crie o banco e os dados de demonstração pelo terminal, dentro da pasta do projeto:
   ```
   php database/install.php --seed
   ```
   (`--fresh` apaga as tabelas antes de recriar.)
4. Acesse `http://localhost/alucar/`.

Se o MySQL tiver outra senha/porta, defina variáveis de ambiente (`ALUCAR_DB_USER`, `ALUCAR_DB_PASS`, `ALUCAR_DB_HOST`, `ALUCAR_DB_PORT`, `ALUCAR_DB_NAME`) ou edite `config/config.php`.

### Contas de demonstração

| Perfil | E-mail | Senha |
|---|---|---|
| Admin | admin@alucar.com | admin1234 |
| Vendedor | vendedor@alucar.com | vendedor1234 |
| Cliente | cliente@alucar.com | cliente1234 |

## Testes

```
php tests/run.php
```

Roda 32 testes (regras dos Models, permissões, alugar/devolver, concorrência, exclusões) em um SQLite em memória, sem precisar de MySQL.

## Próximos passos

- Roteador único (front controller) com URLs amigáveis no lugar dos scripts em `src/`.
- Limite de tentativas de login e recuperação de senha.
- Cancelamento de aluguel e atualização automática de "em andamento"/atrasado.
- Paginação nas listagens e testes de interface.
