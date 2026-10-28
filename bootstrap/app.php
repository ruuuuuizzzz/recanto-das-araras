<?php
declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors', '0');
error_reporting(E_ALL);
if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
    session_start();
}
function esc($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function base_url(string $path = ''): string {
    return rtrim(getenv('APP_BASE_URL') ?: '/recantodasararas', '/') . '/' . ltrim($path, '/');
}
function json_response(array $data, int $status = 200): void {
    http_response_code($status); header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE); exit;
}
function wants_json(): bool {
    return strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false ||
        in_array(basename($_SERVER['SCRIPT_NAME'] ?? ''), ['getmoradores.php','atualizar_status_encomenda.php','excluir_encomenda.php'], true);
}
function fail(string $message, int $status = 400): void {
    if (PHP_SAPI === 'cli') { fwrite(STDERR, $message.PHP_EOL); exit(1); }
    if (wants_json()) json_response(['success'=>false, 'message'=>$message], $status);
    http_response_code($status);
    echo '<!doctype html><html lang="pt-BR"><meta charset="utf-8"><title>Recanto das Araras</title><main><h1>Não foi possível concluir</h1><p>'.esc($message).'</p><a href="'.esc(base_url()).'">Voltar ao painel</a></main></html>'; exit;
}
set_exception_handler(function (Throwable $error): void {
    error_log((string)$error);
    fail('Ocorreu um erro interno. Tente novamente ou contate a administração.', 500);
});
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function require_post(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { header('Allow: POST'); fail('Utilize o formulário para esta operação.', 405); }
    $token = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) fail('Sua sessão expirou. Atualize a página e tente novamente.', 403);
}
function authorize(): void {
    if (empty($_SESSION['user'])) {
        if (wants_json()) json_response(['success'=>false,'message'=>'Entre novamente para continuar.'], 401);
        header('Location: '.base_url('login.php')); exit;
    }
    if (time() - ($_SESSION['last_seen'] ?? time()) > 1800) {
        $_SESSION = []; fail('Sessão expirada. Entre novamente.', 401);
    }
    $_SESSION['last_seen'] = time();
    if (empty($GLOBALS['conn'])) return;
    $file = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if (!empty($_SESSION['user']['trocar_senha']) && !in_array($file,['minha-conta.php','logout.php'],true)) {
        if(wants_json()) fail('Troque sua senha temporária em Minha conta para continuar.',403);
        header('Location: '.base_url('minha-conta.php'));exit;
    }
    $admin = ['salvar_morador.php','salvar_avisos.php','cadastro_morador.php','cadastro_avisos.php','usuarios.php','auditoria.php','notificacoes.php','pendencias.php'];
    if ((strpos($file, 'excluir') === 0 || in_array($file, $admin, true)) && $_SESSION['user']['perfil'] !== 'administracao') fail('Esta operação exige acesso da administração.', 403);
}
function audit(mysqli $db, string $entity, int $id, string $action, array $details = []): void {
    $user = $_SESSION['user']['id'] ?? null;
    $stmt = $db->prepare('INSERT INTO auditoria (usuario_id, entidade, registro_id, acao, detalhes) VALUES (?, ?, ?, ?, ?)');
    $payload = json_encode($details, JSON_UNESCAPED_UNICODE);
    $stmt->bind_param('isiss', $user, $entity, $id, $action, $payload); $stmt->execute();
}
function pagination_sql(): string {
    $page = min(100000, max(1, (int)($_GET['pagina'] ?? 1)));
    return ' LIMIT 51 OFFSET '.(($page - 1) * 50);
}
function pagination(array &$rows): void {
    $GLOBALS['has_next_page'] = count($rows) > 50;
    if (count($rows) > 50) array_pop($rows);
}
function pagination_links(): void {
    $page = min(100000, max(1, (int)($_GET['pagina'] ?? 1)));
    echo '<nav aria-label="Paginação" class="pagination"><span>Página '. $page .' · até 50 registros por página</span> ';
    foreach (['Anterior'=>$page-1,'Próxima'=>$page+1] as $label=>$target) {
        if ($target < 1 || ($target > $page && empty($GLOBALS['has_next_page']))) continue;
        echo '<a href="?'.esc(http_build_query(array_merge($_GET, ['pagina'=>$target]))).'">'. $label .'</a> ';
    }
    echo '</nav>';
}
