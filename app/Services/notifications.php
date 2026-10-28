<?php
function enqueue_notification(mysqli $db, string $entity, array $row, string $event, string $key): void {
    if(empty($row['morador_id'])) return;
    $stmt=$db->prepare('SELECT nome,email FROM moradores WHERE id=?');$stmt->bind_param('i',$row['morador_id']);$stmt->execute();$resident=$stmt->get_result()->fetch_assoc();
    if(!$resident || !filter_var($resident['email'],FILTER_VALIDATE_EMAIL)) {
        audit($db,$entity,(int)$row['id'],'notificacao_sem_destinatario');return;
    }
    $subject=['recebida'=>'Encomenda recebida','retirada'=>'Encomenda retirada','entrada'=>'Entrada de visitante','saida'=>'Saída de visitante'][$event];
    $description=$entity==='encomendas'?($row['tipo_encomenda'].' — '.($row['descricao']??'')):$row['nome_visitante'];
    $body='<p>Olá, '.esc($resident['nome']).'.</p><p>'.esc($subject).': '.esc($description).'.</p><p>Bloco '.esc($row['bloco']).' · Apartamento '.esc($row['apartamento']).'</p><p>Registrado em '.date('d/m/Y H:i').'.</p>';
    if(!empty($row['retirado_por'])) $body.='<p>Recebido por: '.esc($row['retirado_por']).'.</p>';
    $stmt=$db->prepare('INSERT INTO notificacoes(chave,destinatario,assunto,corpo) VALUES (?,?,?,?)');
    $stmt->bind_param('ssss',$key,$resident['email'],$subject,$body);$stmt->execute();
}
