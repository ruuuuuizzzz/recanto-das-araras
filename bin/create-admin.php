<?php
if (PHP_SAPI !== 'cli') exit;
require __DIR__.'/../config/database.php';
if ($argc < 3 || !filter_var($argv[2], FILTER_VALIDATE_EMAIL)) exit("Uso: php bin/create-admin.php Nome email (senha solicitada interativamente)\n");
echo 'Senha (mínimo 8 caracteres; entrada visível neste terminal): ';
$password = trim(fgets(STDIN));
if (strlen($password) < 8) exit("Senha muito curta.\n");
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt=$conn->prepare("INSERT INTO usuarios(nome,email,senha,perfil,trocar_senha) VALUES (?,?,?,'administracao',0)");
$stmt->bind_param('sss',$argv[1],$argv[2],$hash); $stmt->execute();
echo "Administrador criado.\n";
