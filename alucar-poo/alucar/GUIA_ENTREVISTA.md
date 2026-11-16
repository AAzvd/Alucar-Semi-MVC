# Guia para a entrevista (não precisa entregar; é para você estudar)

## 1. Pitch de 30 segundos

> "O Alucar é um sistema de aluguel de carros em PHP puro com MySQL. Comecei com scripts procedurais e depois refatorei para POO em camadas: Models com as regras, Repositories com o SQL, Services com os casos de uso e Controllers finos. Tem três perfis de acesso, transações para alugar/devolver sem deixar dado pela metade, proteção contra CSRF e testes automatizados das regras de negócio."

## 2. Roteiro de demonstração (5 minutos)

1. Entre como **cliente** → catálogo → alugue um carro (mostre o valor calculado) → veja "Meus aluguéis".
2. Entre como **vendedor** → tente alugar o **mesmo carro** (erro "não está mais disponível") → alugue outro em nome do cliente → devolva.
3. Entre como **admin** → painel (indicadores, atraso) → crie um usuário e escolha o tipo → mostre que vendedor não vê o botão de excluir usuário.
4. No terminal: `php tests/run.php` (32 testes passando).
5. Abra `app/Services/AluguelService.php` e explique o `alugar()` (transação + trava).

## 3. Uma requisição do começo ao fim: "Alugar"

1. `views/alugueis/alugueis.php` mostra o formulário (com token CSRF) e envia POST para `src/alugueis/criaAluguel.php`.
2. O endpoint só chama `AluguelController::criar()`.
3. O Controller valida POST + CSRF, descobre quem está logado (`Auth::exigirLogin()`) e chama `AluguelService::alugar()`.
4. O Service aplica as regras: cliente aluga para si; vendedor/admin precisam informar um cliente válido; carro precisa existir.
5. `Aluguel::registrar()` (Model) valida as datas e calcula o valor. `Carro::marcarComoAlugado()` impede alugar carro indisponível.
6. Dentro de uma **transação**, o `CarroRepository` atualiza o status (`UPDATE ... WHERE status = 'disponivel'`) e o `AluguelRepository` insere o aluguel.
7. O Controller grava uma mensagem (`Flash`) e redireciona de volta.

## 4. Antes × depois

| Antes (procedural) | Depois (POO) |
|---|---|
| Cada arquivo validava, consultava o banco, checava permissão e redirecionava | Cada camada tem uma responsabilidade |
| `funcoes.php` com `insertSQL('tabela', [...])` genérico | Repositories com SQL explícito e tipado |
| Arrays (`$carro['status'] === 'alugado'`) | Objetos e enums (`$carro->estaDisponivel()`) |
| Regras espalhadas e repetidas | Regras dentro do Model (`calcularValor`, `podeEditar`...) |
| Códigos `?error=indisponivel` na URL e `if/elseif` em cada view | Exceções com mensagem + `Flash` |
| `isAdmin()` global lendo `$_SESSION` | `Auth::usuario()->isAdmin()` |

## 5. Problemas que o código antigo tinha (e como ficaram)

Se perguntarem "o que você melhorou?", estes são bons exemplos concretos:

- **Aluguel e status do carro eram gravados em dois comandos separados.** Se o segundo falhasse, o carro ficava "disponível" com aluguel ativo. → Transação.
- **Dois clientes podiam alugar o mesmo carro ao mesmo tempo** (checava e depois gravava). → `UPDATE ... WHERE status = anterior` e confere se 1 linha mudou.
- **Devolver um aluguel já finalizado liberava o carro**, mesmo que outro cliente já o tivesse alugado. → `Aluguel::finalizar()` recusa se já encerrado.
- **Excluir e devolver eram links GET** (`excluiCarro.php?id=3`): um site externo poderia disparar a ação com você logado. → POST + token CSRF.
- **Vendedor conseguia editar o admin** (inclusive trocar a senha dele) chamando o endpoint direto; e a exclusão de usuário só era bloqueada na tela. → Permissões no Service, e `podeEditar` / `podeExcluir` no Model.
- **`cria_banco.php` dava `DROP DATABASE` para quem abrisse a URL.** → Instalador só roda no terminal.
- Cálculo de dias com `strtotime / 86400` (erra com horário de verão e dá decimal) → `DateTime::diff`.
- Sem validação de placa, ano, valor e e-mail; senha sem tamanho mínimo; aluguel com início no passado.
- Erro de conexão exibia host e usuário do banco na tela. → Log + mensagem genérica.

## 6. Perguntas prováveis e respostas curtas

**Por que separar em Controller, Service e Repository?**
Para cada classe ter um motivo só para mudar: se o banco mudar, mexo no Repository; se a regra mudar, no Service/Model; se a tela mudar, na view. Também permite testar a regra sem HTML nem MySQL, que é o que os testes fazem.

**O que é encapsulamento no seu projeto?**
O `Carro` não tem `setStatus()`. O status só muda por `marcarComoAlugado()` e `liberar()`, que validam a regra. Ninguém de fora deixa o carro em estado inválido.

**Por que o construtor do Model é `private`?**
Para obrigar o uso dos construtores nomeados: `cadastrar()` (dados novos, valida) e `hidratar()` (dados já salvos no banco).

**O que é injeção de dependência? Onde aparece?**
Em vez de a classe criar o que precisa (`new PDO` dentro do Repository), ela recebe pelo construtor. Aparece em todos os Services/Repositories; o `Container` monta tudo num lugar só. Nos testes, injeto um banco em memória.

**Por que usar enums?**
Evita strings inventadas (`'Alugado'`, `'alugado '`), o PHP garante que só existem valores válidos e o editor ajuda com autocomplete.

**Como funciona a proteção contra CSRF?**
Cada sessão tem um token aleatório que vai num campo escondido do formulário. O servidor só aceita o POST se o token bater (`hash_equals`). Um site malicioso não consegue ler esse token.

**Por que prepared statements?**
Os dados do usuário nunca são concatenados no SQL, então não há SQL injection.

**O que é uma transação e por que usou?**
Agrupa operações para que ou todas sejam gravadas ou nenhuma (`commit`/`rollback`). Alugar mexe em duas tabelas; sem transação poderia sobrar dado inconsistente.

**Como evita dois clientes alugando o mesmo carro?**
O `UPDATE` só altera se o status ainda for "disponível" e eu confiro quantas linhas mudaram. O segundo pedido muda 0 linhas e recebe erro.

**Por que `Auth` usa métodos `static`?**
A sessão é única por requisição, então não há vários "Auth". É uma simplificação consciente; em um projeto maior eu injetaria um objeto de sessão para facilitar testes.

**Por que as listagens com JOIN devolvem array e não objetos?**
São dados só de exibição, juntando várias tabelas (um *read model*). Criar um objeto de domínio para isso seria burocracia.

**Usou framework?**
Não, de propósito: queria entender as bases (PDO, sessão, arquitetura em camadas). Em produção eu usaria Laravel ou Symfony, e o que aprendi aqui (Service, Repository, DI) se aplica a eles.

**O que faria a seguir?**
Front controller com roteador, limite de tentativas de login, cancelamento de aluguel, paginação, e testes de interface.

## 7. Cuidados na entrevista

- **Abra e leia os arquivos principais antes**: `Carro.php`, `Aluguel.php`, `AluguelService.php`, `Container.php`. Você precisa conseguir explicar cada linha deles.
- Seja honesto sobre o que é aprendizado: "refatorei um projeto procedural para POO" é uma ótima história. Se perguntarem sobre algo que você ainda não domina (por exemplo, `static` ou `readonly`), diga que está estudando.
- Se algo quebrar na demo, mostre os testes: eles provam que as regras funcionam.
