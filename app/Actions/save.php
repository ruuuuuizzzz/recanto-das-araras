<?php
require_once __DIR__.'/../../config/database.php';
require_once __DIR__.'/../Support/validation.php';
require_once __DIR__.'/../Services/notifications.php';
require_post();
[$data,$errors]=validate_record($entity,$_POST);
$id=filter_var(($_POST['id']??'')===''?0:$_POST['id'],FILTER_VALIDATE_INT);
if($id===false || $id<0) fail('Registro inválido.');
$existing=null;
if($id) {
    $stmt=$conn->prepare("SELECT * FROM `$entity` WHERE id=?");$stmt->bind_param('i',$id);$stmt->execute();$existing=$stmt->get_result()->fetch_assoc();
    if(!$existing) fail('Registro não encontrado.',404);
}
if(in_array($entity,['visitantes','encomendas','veiculos'],true)) {
    $mid=filter_var($_POST['morador_id']??0,FILTER_VALIDATE_INT)?:0;
    $stmt=$conn->prepare('SELECT id,nome,bloco,apartamento FROM moradores WHERE id=?');$stmt->bind_param('i',$mid);$stmt->execute();$resident=$stmt->get_result()->fetch_assoc();
    if(!$resident) $errors['morador_id']='Selecione o morador responsável pelo registro.';
    else $data+=['morador_id'=>$resident['id'],'morador_nome'=>$resident['nome'],'bloco'=>$resident['bloco'],'apartamento'=>$resident['apartamento']];
}
if($entity==='encomendas') {
    $recipient=trim((string)($_POST['retirado_por']??''));
    if($data['status_encomenda']==='Retirado' && ($recipient==='' || mb_strlen($recipient)>100)) $errors['retirado_por']='Informe quem recebeu a encomenda (até 100 caracteres).';
    $data['retirado_por']=$data['status_encomenda']==='Retirado'?$recipient:null;
}
if($errors) form_error($entity,$errors);
try {
    $conn->begin_transaction();
    if($id) {
        $stmt=$conn->prepare("SELECT * FROM `$entity` WHERE id=? FOR UPDATE");$stmt->bind_param('i',$id);$stmt->execute();$existing=$stmt->get_result()->fetch_assoc();
        if(!$existing) throw new RuntimeException('Registro removido por outra operação.');
    }
    if($entity==='moradores') {
        // Serialize all writes to one unit, including deployments with legacy duplicate owners.
        $unit=$data['bloco'].':'.$data['apartamento'];
        $stmt=$conn->prepare('INSERT IGNORE INTO unidade_locks(unidade) VALUES (?)');$stmt->bind_param('s',$unit);$stmt->execute();
        $stmt=$conn->prepare('SELECT unidade FROM unidade_locks WHERE unidade=? FOR UPDATE');$stmt->bind_param('s',$unit);$stmt->execute();$stmt->get_result()->fetch_all();
        $stmt=$conn->prepare("SELECT id FROM moradores WHERE bloco=? AND apartamento=? AND tipo_morador='Proprietário' AND id<>? FOR UPDATE");
        $stmt->bind_param('ssi',$data['bloco'],$data['apartamento'],$id);$stmt->execute();
        if($data['tipo_morador']==='Proprietário' && $stmt->get_result()->num_rows) { $conn->rollback();form_error($entity,['tipo_morador'=>'Já existe um proprietário nesta unidade.']); }
    }
    if($entity==='veiculos') {
        $plateLock='placa:'.$data['placa'];
        $stmt=$conn->prepare('INSERT IGNORE INTO unidade_locks(unidade) VALUES (?)');$stmt->bind_param('s',$plateLock);$stmt->execute();
        $stmt=$conn->prepare('SELECT unidade FROM unidade_locks WHERE unidade=? FOR UPDATE');$stmt->bind_param('s',$plateLock);$stmt->execute();$stmt->get_result()->fetch_all();
        $stmt=$conn->prepare("SELECT id FROM veiculos WHERE REPLACE(REPLACE(placa,'-',''),' ','')=? AND id<>? FOR UPDATE");
        $stmt->bind_param('si',$data['placa'],$id);$stmt->execute();
        if($stmt->get_result()->num_rows) {$conn->rollback();form_error($entity,['placa'=>'Esta placa já está cadastrada.']);}
    }
    if($entity==='visitantes') {
        $data['ativo']=$data['status_presenca'];unset($data['status_presenca']);
        if(!$existing || $existing['ativo']!==$data['ativo']) $data[$data['ativo']==='Sim'?'entrada_em':'saida_em']=date('Y-m-d H:i:s');
    }
    if($entity==='encomendas' && (!$existing || $existing['status_encomenda']!==$data['status_encomenda'])) $data['retirada_em']=$data['status_encomenda']==='Retirado'?date('Y-m-d H:i:s'):null;
    $cols=array_keys($data);$values=array_values($data);
    if($id) {$sql="UPDATE `$entity` SET ".implode(',',array_map(fn($c)=>"`$c`=?",$cols)).' WHERE id=?';$values[]=$id;}
    else $sql="INSERT INTO `$entity` (`".implode('`,`',$cols).'`) VALUES ('.implode(',',array_fill(0,count($values),'?')).')';
    $stmt=$conn->prepare($sql);$stmt->bind_param(str_repeat('s',count($values)),...$values);$stmt->execute();
    $recordId=$id?:$conn->insert_id;
    audit($conn,$entity,(int)$recordId,$id?'atualizar':'criar',['campos'=>array_keys($data),'status_anterior'=>$existing['ativo']??$existing['status_encomenda']??null,'status_atual'=>$data['ativo']??$data['status_encomenda']??null,'retirado_por'=>$data['retirado_por']??null]);
    $event=null;
    if($entity==='encomendas') {
        if(!$existing) $event=$data['status_encomenda']==='Retirado'?'retirada':'recebida';
        elseif($existing['status_encomenda']!==$data['status_encomenda'] && $data['status_encomenda']==='Retirado') $event='retirada';
    }
    if($entity==='visitantes' && (!$existing || $existing['ativo']!==$data['ativo'])) $event=$data['ativo']==='Sim'?'entrada':($existing?'saida':null);
    if($event) enqueue_notification($conn,$entity,$data+['id'=>$recordId],$event,bin2hex(random_bytes(24)));
    $conn->commit();unset($_SESSION['form']);
    $_SESSION['flash']='Registro salvo com sucesso.';
    header('Location: '.base_url('telas/telasPrincipais/'.$entity.'.php'));exit;
} catch(mysqli_sql_exception $error) {
    $conn->rollback();
    if($error->getCode()===1062) form_error($entity,['geral'=>'Já existe um registro com estes dados.']);
    throw $error;
}
