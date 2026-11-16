-- Esquema do Alucar (MySQL / MariaDB).
-- Mesmas tabelas do projeto original; só foi adicionado utf8mb4 e IF NOT EXISTS.

CREATE TABLE IF NOT EXISTS usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('cliente', 'vendedor', 'admin') NOT NULL DEFAULT 'cliente',
    telefone VARCHAR(20) NULL,
    cpf VARCHAR(14) UNIQUE NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS carros (
    id INT PRIMARY KEY AUTO_INCREMENT,
    marca VARCHAR(100) NOT NULL,
    modelo VARCHAR(100) NOT NULL,
    ano INT NOT NULL,
    placa VARCHAR(10) UNIQUE NOT NULL,
    valor_diaria DECIMAL(10,2) NOT NULL,
    status ENUM('disponivel', 'alugado', 'manutencao') NOT NULL DEFAULT 'disponivel',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS alugueis (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_id INT NOT NULL,
    carro_id INT NOT NULL,
    vendedor_id INT NULL,                 -- NULL = cliente alugou sozinho pelo site
    data_inicio DATE NOT NULL,
    data_fim DATE NOT NULL,
    valor_total DECIMAL(10,2) NOT NULL,
    status ENUM('pendente', 'confirmado', 'em_andamento', 'finalizado', 'cancelado') NOT NULL DEFAULT 'pendente',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (cliente_id) REFERENCES usuarios(id),
    FOREIGN KEY (carro_id) REFERENCES carros(id),
    FOREIGN KEY (vendedor_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
