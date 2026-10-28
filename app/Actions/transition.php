<?php
require_once __DIR__.'/../../config/database.php';require_once __DIR__.'/../Services/notifications.php';require_post();
$id=filter_var($_POST['id']??0,FILTER_VALIDATE_INT);if(!$id || $id<1) fail('Registro inválido.');
$package=$entity==='encomendas';$field=$package?'status_encomenda':'ativo';
$target=$package?($_POST['status']??''):(($_POST['acao']??'')==='entrada'?'Sim':(($_POST['acao']??'')==='saida'?'Não':''));
if(!in_array($target,$package?['Pendente','Retirado']:['Sim','Não'],true)) fail('Status inválido.');
$conn->begin_transaction();
$stmt=$conn->prepare("SELECT * FROM `$entity` WHERE id=? FOR UPDATE");$stmt->bind_param('i',$id);$stmt->execute();$row=$stmt->get_result()->fetch_assoc();
if(!$row) {$conn->rollback();fail('Registro não encontrado.',404);}
if($row[$field]!==$target) {
    $timeField=$package?'retirada_em':($target==='Sim'?'entrada_em':'saida_em');
    $timestamp=($package && $target==='Pendente')?null:date('Y-m-d H:i:s');
    $recipient=trim((string)($_POST['retirado_por']??''));
    if($package && $target==='Retirado' && ($recipient==='' || mb_strlen($recipient)>100)) {$conn->rollback();fail('Informe quem recebeu a encomenda.');}
    $sql="UPDATE `$entity` SET `$field`=?, `$timeField`=?".($package?', retirado_por=?':'').' WHERE id=?';
    $stmt=$conn->prepare($sql);
    if($package) {$recipient=$target==='Retirado'?$recipient:null;$stmt->bind_param('sssi',$target,$timestamp,$recipient,$id);}
    else $stmt->bind_param('ssi',$target,$timestamp,$id);
    $stmt->execute();
    audit($conn,$entity,$id,$package?'alterar_status':($target==='Sim'?'entrada':'saida'),['anterior'=>$row[$field],'atual'=>$target,'retirado_por'=>$recipient]);
    $event=$package?($target==='Retirado'?'retirada':null):($target==='Sim'?'entrada':'saida');
    if($event) { $row['retirado_por']=$recipient;enqueue_notification($conn,$entity,$row,$event,bin2hex(random_bytes(24))); }
}
$conn->commit();
if($package) json_response(['success'=>true,'novoStatus'=>$target]);
header('Location: '.base_url('telas/telasPrincipais/visitantes.php?'.http_build_query(array_intersect_key($_POST,array_flip(['busca','status'])))));
