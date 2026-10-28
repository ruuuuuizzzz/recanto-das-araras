<?php
    require_once __DIR__.'/../bootstrap/app.php';
    if (PHP_SAPI !== 'cli' && !defined('PUBLIC_PAGE')) authorize();
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $host = getenv('DB_HOST') ?: 'localhost';
    $user = getenv('DB_USER');
    $pass = getenv('DB_PASS');
    $db   = getenv('DB_NAME');
    if (!$user || !$db || $pass === false || $pass === '') {
        throw new RuntimeException('Configure DB_USER, DB_PASS e DB_NAME no ambiente do servidor.');
    }

    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        
        error_log('Erro de conexão: ' . $conn->connect_error);
        die('Erro interno. Tente novamente mais tarde.');
    }

    $conn->set_charset('utf8mb4');
    $conn->query("SET time_zone = '-03:00'");
    if (PHP_SAPI !== 'cli' && !defined('PUBLIC_PAGE')) {
        $stmt = $conn->prepare('SELECT id,nome,email,perfil,trocar_senha FROM usuarios WHERE id=? AND ativo=1');
        $stmt->bind_param('i', $_SESSION['user']['id']); $stmt->execute();
        $currentUser=$stmt->get_result()->fetch_assoc();
        if (!$currentUser) { $_SESSION=[]; fail('Acesso desativado. Contate a administração.', 401); }
        $_SESSION['user']=$currentUser;
        authorize();
    }
    
?>
