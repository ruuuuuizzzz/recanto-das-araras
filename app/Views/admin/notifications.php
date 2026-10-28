<?php
require __DIR__.'/../../../config/database.php';
if($_SERVER['REQUEST_METHOD']==='POST') {
    require_post();$id=(int)($_POST['id']??0);
    $conn->begin_transaction();$stmt=$conn->prepare("UPDATE notificacoes SET status='pendente',tentativas=0,proxima_tentativa=NOW(),ultimo_erro=NULL WHERE id=? AND status='falhou'");$stmt->bind_param('i',$id);$stmt->execute();
    if($stmt->affected_rows)audit($conn,'notificacoes',$id,'reagendar');$conn->commit();header('Location: '.base_url('notificacoes.php'));exit;
}
$rows=$conn->query('SELECT id,destinatario,assunto,status,tentativas,ultimo_erro,criado_em,enviado_em FROM notificacoes ORDER BY id DESC'.pagination_sql())->fetch_all(MYSQLI_ASSOC);pagination($rows);
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Notificações</title><link rel="stylesheet" href="<?= esc(base_url('assets/css/app.css')) ?>"></head><body><main class="admin-main"><a href="<?= esc(base_url()) ?>">Voltar ao painel</a><h1>Notificações</h1><p>Mensagens aguardam o processamento da fila. Falhas podem ser reagendadas após corrigir a configuração de envio.</p><table><thead><tr><th>Destinatário</th><th>Assunto</th><th>Status</th><th>Tentativas</th><th>Detalhes</th></tr></thead><tbody><?php foreach($rows as $row):?><tr><td><?= esc($row['destinatario']) ?></td><td><?= esc($row['assunto']) ?></td><td><?= esc($row['status']) ?></td><td><?= (int)$row['tentativas'] ?></td><td><?= esc($row['ultimo_erro']??$row['enviado_em']??'') ?><?php if($row['status']==='falhou'):?><form method="post"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="id" value="<?= $row['id'] ?>"><button>Tentar novamente</button></form><?php endif ?></td></tr><?php endforeach ?></tbody></table><?php pagination_links() ?></main></body></html>
