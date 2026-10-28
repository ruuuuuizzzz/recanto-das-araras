<?php
if(PHP_SAPI!=='cli')exit;
require __DIR__.'/../config/database.php';
$schemas=[
'moradores'=>"id INT AUTO_INCREMENT PRIMARY KEY,nome VARCHAR(100),bloco VARCHAR(10),apartamento VARCHAR(10),telefone VARCHAR(20),cpf VARCHAR(20),email VARCHAR(100),tipo_morador VARCHAR(50),data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP",
'visitantes'=>"id INT AUTO_INCREMENT PRIMARY KEY,apartamento VARCHAR(10),morador_nome VARCHAR(100),nome_visitante VARCHAR(100),documento VARCHAR(20),data_visita DATE,observacao TEXT,ativo VARCHAR(20) DEFAULT 'Sim',bloco VARCHAR(10),criado_em DATETIME DEFAULT CURRENT_TIMESTAMP",
'encomendas'=>"id INT AUTO_INCREMENT PRIMARY KEY,apartamento VARCHAR(10),bloco VARCHAR(10),morador_nome VARCHAR(100),tipo_encomenda VARCHAR(50),descricao VARCHAR(255),data_recebimento DATE,status_encomenda ENUM('Pendente','Retirado') NOT NULL DEFAULT 'Pendente',criado_em DATETIME DEFAULT CURRENT_TIMESTAMP",
'veiculos'=>"id INT AUTO_INCREMENT PRIMARY KEY,apartamento VARCHAR(10),morador_nome VARCHAR(100),placa VARCHAR(20),modelo VARCHAR(50),cor VARCHAR(30),tipo_veiculo VARCHAR(20),data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP",
'avisos'=>"id INT AUTO_INCREMENT PRIMARY KEY,titulo VARCHAR(150) NOT NULL,descricao TEXT,nivel_urgencia ENUM('baixa','media','alta') DEFAULT 'baixa',data_aviso DATE NOT NULL"
];
foreach($schemas as $table=>$schema)$conn->query("CREATE TABLE IF NOT EXISTS `$table` ($schema) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
echo "Esquema base instalado. Execute bin/migrate.php.\n";
