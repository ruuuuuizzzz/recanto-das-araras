<?php
if (PHP_SAPI !== 'cli') exit;
require __DIR__.'/../../config/database.php';
$conn->query("CREATE TABLE IF NOT EXISTS schema_migrations (versao VARCHAR(80) PRIMARY KEY, aplicada_em DATETIME DEFAULT CURRENT_TIMESTAMP)");
function column(mysqli $db, string $table, string $name, string $definition): void {
    if (!$db->query("SHOW COLUMNS FROM `$table` LIKE '$name'")->num_rows) $db->query("ALTER TABLE `$table` ADD COLUMN `$name` $definition");
}
$conn->query("CREATE TABLE IF NOT EXISTS usuarios (id INT AUTO_INCREMENT PRIMARY KEY, nome VARCHAR(100) NOT NULL, email VARCHAR(100) NOT NULL UNIQUE, senha VARCHAR(255) NOT NULL, perfil ENUM('administracao','portaria') NOT NULL, ativo TINYINT NOT NULL DEFAULT 1, criado_em DATETIME DEFAULT CURRENT_TIMESTAMP)");
column($conn,'usuarios','trocar_senha','TINYINT NOT NULL DEFAULT 1');
$conn->query('CREATE TABLE IF NOT EXISTS unidade_locks (unidade VARCHAR(30) PRIMARY KEY)');
$conn->query("CREATE TABLE IF NOT EXISTS auditoria (id BIGINT AUTO_INCREMENT PRIMARY KEY, usuario_id INT NULL, entidade VARCHAR(30) NOT NULL, registro_id INT NOT NULL, acao VARCHAR(40) NOT NULL, detalhes JSON NULL, criado_em DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX idx_auditoria_registro (entidade,registro_id,criado_em))");
$conn->query("CREATE TABLE IF NOT EXISTS notificacoes (id BIGINT AUTO_INCREMENT PRIMARY KEY, chave VARCHAR(150) NOT NULL UNIQUE, destinatario VARCHAR(100) NOT NULL, assunto VARCHAR(200) NOT NULL, corpo TEXT NOT NULL, status ENUM('pendente','processando','enviado','falhou') DEFAULT 'pendente', tentativas INT NOT NULL DEFAULT 0, proxima_tentativa DATETIME DEFAULT CURRENT_TIMESTAMP, bloqueado_em DATETIME NULL, enviado_em DATETIME NULL, ultimo_erro VARCHAR(255) NULL, criado_em DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX idx_fila (status,proxima_tentativa))");
$conn->query("CREATE TABLE IF NOT EXISTS login_tentativas (chave CHAR(64) PRIMARY KEY, tentativas INT NOT NULL DEFAULT 0, inicio DATETIME NOT NULL)");
foreach (['visitantes','encomendas','veiculos'] as $table) {
    column($conn, $table, 'morador_id', 'INT NULL');
    column($conn, $table, 'bloco', 'VARCHAR(10) NULL');
    // Only unique, exact legacy matches are linked; ambiguous rows stay visible for manual correction.
    $block = $table === 'veiculos' ? '' : ' AND m.bloco = r.bloco';
    $conn->query("UPDATE `$table` r JOIN (SELECT r.id, MIN(m.id) mid FROM `$table` r JOIN moradores m ON m.nome=r.morador_nome AND m.apartamento=r.apartamento $block WHERE r.morador_id IS NULL GROUP BY r.id HAVING COUNT(*)=1) x ON r.id=x.id JOIN moradores m ON m.id=x.mid SET r.morador_id=m.id, r.bloco=m.bloco");
    $fk = 'fk_'.$table.'_morador';
    $check = $conn->query("SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='$table' AND CONSTRAINT_NAME='$fk'");
    if (!$check->num_rows) $conn->query("ALTER TABLE `$table` ADD CONSTRAINT `$fk` FOREIGN KEY (morador_id) REFERENCES moradores(id) ON DELETE RESTRICT");
}
foreach (['entrada_em','saida_em'] as $name) column($conn, 'visitantes', $name, 'DATETIME NULL');
column($conn, 'encomendas', 'retirada_em', 'DATETIME NULL');
column($conn, 'encomendas', 'retirado_por', 'VARCHAR(100) NULL');
foreach (['moradores'=>['idx_unidade'=>'bloco, apartamento'], 'visitantes'=>['idx_presenca'=>'ativo','idx_visita'=>'data_visita'], 'encomendas'=>['idx_status'=>'status_encomenda','idx_recebimento'=>'data_recebimento'], 'veiculos'=>['idx_placa'=>'placa']] as $table=>$indexes) {
    foreach ($indexes as $name=>$cols) if (!$conn->query("SHOW INDEX FROM `$table` WHERE Key_name='$name'")->num_rows) $conn->query("CREATE INDEX `$name` ON `$table` ($cols)");
}
// A generated unique key also prevents concurrent creation of two owners of one unit.
if (!$conn->query("SELECT bloco,apartamento FROM moradores WHERE tipo_morador='Proprietário' GROUP BY bloco,apartamento HAVING COUNT(*)>1")->num_rows) {
    column($conn, 'moradores', 'unidade_proprietario', "VARCHAR(30) GENERATED ALWAYS AS (CASE WHEN tipo_morador='Proprietário' THEN CONCAT(bloco,':',apartamento) ELSE NULL END) STORED");
    if (!$conn->query("SHOW INDEX FROM moradores WHERE Key_name='uq_proprietario'")->num_rows) $conn->query('CREATE UNIQUE INDEX uq_proprietario ON moradores(unidade_proprietario)');
} else echo "ATENÇÃO: proprietários duplicados; corrigir e executar novamente para criar restrição.\n";
$conn->query("INSERT IGNORE INTO schema_migrations (versao) VALUES ('20261025_seguranca_integridade')");
foreach (['visitantes','encomendas','veiculos'] as $table) {
    $count = $conn->query("SELECT COUNT(*) total FROM `$table` WHERE morador_id IS NULL")->fetch_assoc()['total'];
    echo "$table: $count registro(s) sem vínculo inequívoco; corrigir na tela de edição.\n";
}
echo "Migração concluída.\n";
