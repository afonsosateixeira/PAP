-- Criando a tabela de usuários para o sistema de login
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

ALTER TABLE users 
ADD COLUMN profile_picture VARCHAR(255) DEFAULT 'assets/images/default.png',
ADD COLUMN description VARCHAR(255) DEFAULT 'Bem-vindo!';


-- Criando a tabela de categorias
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    color VARCHAR(7) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Criando a tabela de notas
CREATE TABLE notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    schedule_date DATETIME NULL,
    category_id INT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Criando a tabela de sessões para gerenciar autenticação
CREATE TABLE sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Criando a tabela de tarefas
CREATE TABLE tbtarefas (
    idTarefa INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tituloTarefa VARCHAR(255) NOT NULL,
    descricaoTarefa TEXT NOT NULL,
    recorrenciaTarefa INT NOT NULL DEFAULT '0',
    statusTarefa TINYINT(1) NOT NULL DEFAULT '0',
    dataconclusao_date DATETIME NULL,
    datalembrete_date DATETIME NULL,
    category_id INT NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);
ALTER TABLE tbtarefas
ADD COLUMN user_id INT NOT NULL;


ALTER TABLE tbtarefas
ADD CONSTRAINT fk_user_id
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;


-- Criando a tabela de alarmes
CREATE TABLE alarms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    time TIME NOT NULL,
    ringtone VARCHAR(255) DEFAULT NULL,
    recurrence VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Criando a tabela de temporizadores
CREATE TABLE timers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    duration INT NOT NULL COMMENT 'Duração em segundos',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

ALTER TABLE timers
ADD COLUMN name VARCHAR(255) NOT NULL AFTER user_id;

ALTER TABLE alarms
ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1,
ADD COLUMN days_of_week VARCHAR(50) DEFAULT NULL;

