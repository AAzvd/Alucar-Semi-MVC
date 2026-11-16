-- Versão do esquema para SQLite (usada só nos testes automatizados, em memória).
CREATE TABLE usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    senha TEXT NOT NULL,
    tipo TEXT NOT NULL DEFAULT 'cliente',
    telefone TEXT NULL,
    cpf TEXT UNIQUE NULL,
    criado_em TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE carros (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    marca TEXT NOT NULL,
    modelo TEXT NOT NULL,
    ano INTEGER NOT NULL,
    placa TEXT UNIQUE NOT NULL,
    valor_diaria NUMERIC NOT NULL,
    status TEXT NOT NULL DEFAULT 'disponivel',
    criado_em TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE alugueis (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cliente_id INTEGER NOT NULL REFERENCES usuarios(id),
    carro_id INTEGER NOT NULL REFERENCES carros(id),
    vendedor_id INTEGER NULL REFERENCES usuarios(id),
    data_inicio TEXT NOT NULL,
    data_fim TEXT NOT NULL,
    valor_total NUMERIC NOT NULL,
    status TEXT NOT NULL DEFAULT 'pendente',
    criado_em TEXT DEFAULT CURRENT_TIMESTAMP
);
