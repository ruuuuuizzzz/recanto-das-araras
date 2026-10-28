<?php
if(PHP_SAPI!=='cli')exit;
require __DIR__.'/../config/database.php';
$email=$argv[1]??'';if(!filter_var($email,FILTER_VALIDATE_EMAIL))exit("Informe e-mail válido.\n");
$stmt=$conn->prepare('SELECT id FROM usuarios WHERE email=?');$stmt->bind_param('s',$email);$stmt->execute();if($stmt->get_result()->num_rows)exit("Usuário já cadastrado; senha preservada.\n");
$password=bin2hex(random_bytes(10)).'Aa!';$hash=password_hash($password,PASSWORD_DEFAULT);$name='Recanto das Araras';
$stmt=$conn->prepare("INSERT INTO usuarios(nome,email,senha,perfil,trocar_senha) VALUES (?,?,?,'administracao',1)");$stmt->bind_param('sss',$name,$email,$hash);$stmt->execute();
echo "Administrador: $email\nSenha temporária: $password\nTroca obrigatória no primeiro acesso.\n";
