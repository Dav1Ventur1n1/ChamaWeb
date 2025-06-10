CREATE DATABASE IF NOT EXISTS chamaweb;
USE chamaweb;

/* Tabela de Usuários */
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    role ENUM('usuario','analista','administrador') NOT NULL DEFAULT 'usuario',
    blocked TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

/* Tabela para rastreamento de usuários Cognito */
CREATE TABLE IF NOT EXISTS cognito_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    cognito_sub VARCHAR(255) NOT NULL UNIQUE,
    tokens JSON,
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

/* Adicionar campo para rastrear origem da autenticação */
ALTER TABLE users
ADD COLUMN auth_provider ENUM('local', 'cognito') DEFAULT 'local';

/* Tabela de Preferências de Usuário (Notificações) */
CREATE TABLE IF NOT EXISTS user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    receive_email TINYINT(1) NOT NULL DEFAULT 1,
    receive_whatsapp TINYINT(1) NOT NULL DEFAULT 0,
    notification_frequency ENUM('imediato','diario','semanal') DEFAULT 'imediato',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

/* Tabela de Equipes */
CREATE TABLE IF NOT EXISTS teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL
);

/* Tabela de Categorias */
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL
);

/* Tabela de Chamados */
CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT NOT NULL,
    categoria_id INT,
    servico_impactado VARCHAR(100),
    tipo ENUM('Incidente','Requisicao') DEFAULT 'Incidente',
    prioridade ENUM('Baixo','Medio','Alto','Critico') DEFAULT 'Baixo',
    estado ENUM('Aberto','Em Analise','Aguardando Usuario','Resolvido','Fechado') DEFAULT 'Aberto',
    risco ENUM('Baixo','Medio','Alto') DEFAULT 'Baixo',
    user_id INT NOT NULL,
    assigned_to INT,
    assigned_team_id INT,
    sla_due DATETIME,
    data_abertura DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_fechamento DATETIME,
    FOREIGN KEY (categoria_id) REFERENCES categories(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    FOREIGN KEY (assigned_team_id) REFERENCES teams(id)
);

/* Tabela de Comentários (Interações) */
CREATE TABLE IF NOT EXISTS comentarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    conteudo TEXT NOT NULL,
    visivel_usuario TINYINT(1) DEFAULT 1,
    anexo VARCHAR(255),
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

/* Tabela de Histórico de Alterações (RF14) */
CREATE TABLE IF NOT EXISTS changes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    campo VARCHAR(50) NOT NULL,
    valor_anterior VARCHAR(255),
    valor_novo VARCHAR(255),
    data_alteracao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

/* Tabela de Logs (Acesso e Auditoria) (RF07, RF20) */
CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    tipo ENUM('LOGIN','LOGOUT','ERRO_LOGIN','ACAO') DEFAULT 'ACAO',
    descricao TEXT,
    data_log DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Cria usuário admin
INSERT INTO users (nome,email,senha,role)
VALUES ('Admin','admin@sistema.com', SHA2('admin123',256),'administrador');

-- Cria equipe de infraestrutura e desenvolvimento
INSERT INTO teams (nome) VALUES ('Infraestrutura'),('Desenvolvimento');

-- Cria algumas categorias
INSERT INTO categories (nome) VALUES ('Rede'),('Banco de Dados'),('Aplicações'),('Hardware');
