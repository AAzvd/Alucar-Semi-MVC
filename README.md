# Alucar-Semi-MVC
Continuação do Alucar

## Atualizações

**Refatoração para POO em camadas** (inspirada em MVC)
- Controllers recebem a requisição, Services aplicam regras e permissões, Repositories concentram o SQL (PDO) e Models encapsulam as regras de `Usuario`, `Carro` e `Aluguel`.
- Enums para tipos e status; exceções de domínio com mensagens amigáveis.
- Autoload PSR-4 e injeção de dependência por construtor.

**Segurança**
- Proteção CSRF com token; excluir, devolver e sair agora só aceitam POST.
- Saída escapada contra XSS, prepared statements e senhas com `password_hash`.
- Cookie de sessão `HttpOnly`/`SameSite` e sessão regenerada no login.
- Permissões validadas nos Services (um vendedor não edita um admin).

**Integridade dos dados**
- Aluguel e devolução em transação.
- Trava contra alugar o mesmo carro duas vezes ao mesmo tempo.
- Validação de placa, ano, valor, e-mail e datas.

**Banco de dados**
- `database/alucar_workbench.sql`: cria o banco no MySQL Workbench com dados de demonstração (14 usuários, 12 carros, 164 aluguéis e 155 pagamentos), com datas relativas a hoje.
- `database/alucar_consultas.sql`: consultas para explorar os dados.
- Nova tabela `pagamentos`, base da futura área financeira.
- `database/install.php` (somente terminal) substitui o antigo script que apagava o banco.

**Testes**
- 32 testes automatizados (`php tests/run.php`), sem dependências, usando SQLite em memória.

**Próximos passos**
- Área financeira, roteador único (front controller), paginação e responsividade.
- Imagens, imagem de perfil, dos carros, receber imagem via $FILES talvez, verificarei com Morais como será os proximos conteudos
