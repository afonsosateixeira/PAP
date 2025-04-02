-- Criando a tabela de utilizadores para o sistema de login
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    profile_picture VARCHAR(255) DEFAULT 'assets/images/default.png',
    description VARCHAR(255) DEFAULT 'Bem-vindo!',
    timezone VARCHAR(255) DEFAULT 'Europe/Lisbon'
);

-- Criando a tabela de categorias
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    color VARCHAR(7) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES utilizadores(id) ON DELETE CASCADE
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
    FOREIGN KEY (categoria_id) REFERENCES categories(id) ON DELETE SET NULL
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
    user_id INT NOT NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Criando a tabela de alarmes
CREATE TABLE alarms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    time TIME NOT NULL,
    ringtone VARCHAR(255) DEFAULT NULL,
    recurrence VARCHAR(255) DEFAULT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    days_of_week VARCHAR(50) DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Criando a tabela de temporizadores
CREATE TABLE timers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    duration INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Criando a tabela de reposição de horas
CREATE TABLE reposicao_horas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    disciplina VARCHAR(50) NOT NULL,
    professor VARCHAR(50) NOT NULL,
    modulo INT NOT NULL,
    horas INT NULL,
    justificativa ENUM('justificada', 'injustificada') NOT NULL,
    datahora_reposicao DATETIME NULL,
    nota VARCHAR(255) DEFAULT NULL,
    tipo ENUM('horas', 'modulos') NOT NULL DEFAULT 'horas',
    status ENUM('pendente', 'concluido') NOT NULL DEFAULT 'pendente',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
