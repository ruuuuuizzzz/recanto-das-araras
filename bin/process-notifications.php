<?php
if(PHP_SAPI!=='cli') exit;
require __DIR__.'/../config/database.php';require __DIR__.'/../vendor/autoload.php';require __DIR__.'/../config/email.php';
if(!$config_email['smtp_user'] || !$config_email['smtp_password'] || !filter_var($config_email['remetente_email'],FILTER_VALIDATE_EMAIL)) {fwrite(STDERR,"Configure SMTP_USER, SMTP_PASSWORD e REMETENTE_EMAIL. Nenhuma mensagem foi enviada.\n");exit(1);}
if(strtolower($config_email['smtp_host'])==='smtp.gmail.com') {
    $config_email['smtp_password']=preg_replace('/\s/','',$config_email['smtp_password']);
    if(strlen($config_email['smtp_password'])!==16) {
        fwrite(STDERR,"A senha de aplicativo do Gmail deve ter 16 caracteres. Execute configure-email.ps1 com uma senha de aplicativo valida. A fila foi preservada.\n");exit(1);
    }
}
if(in_array('--check',$argv,true)) {
    echo "Configuração SMTP disponível. Nenhuma mensagem enviada nesta verificação.\n";
    foreach($conn->query('SELECT status,COUNT(*) quantidade FROM notificacoes GROUP BY status') as $row) echo $row['status'].': '.$row['quantidade']."\n";
    exit;
}
// Expired leases recover after worker interruption. SMTP cannot guarantee exactly-once delivery.
$conn->query("UPDATE notificacoes SET status=IF(tentativas>=5,'falhou','pendente'), bloqueado_em=NULL WHERE status='processando' AND bloqueado_em<DATE_SUB(NOW(),INTERVAL 10 MINUTE)");
for($i=0;$i<50;$i++) {
    $conn->begin_transaction();
    $row=$conn->query("SELECT * FROM notificacoes WHERE status='pendente' AND tentativas<5 AND proxima_tentativa<=NOW() ORDER BY id LIMIT 1 FOR UPDATE")->fetch_assoc();
    if(!$row) {$conn->commit();break;}
    $conn->query("UPDATE notificacoes SET status='processando', tentativas=tentativas+1,bloqueado_em=NOW() WHERE id=".(int)$row['id']);$conn->commit();
    try {
        $mail=new PHPMailer\PHPMailer\PHPMailer(true);$mail->isSMTP();$mail->Host=$config_email['smtp_host'];$mail->Port=(int)$config_email['smtp_port'];$mail->SMTPAuth=true;
        $mail->Username=$config_email['smtp_user'];$mail->Password=$config_email['smtp_password'];$mail->SMTPSecure=$config_email['smtp_secure'];$mail->Timeout=20;$mail->CharSet='UTF-8';
        $mail->setFrom($config_email['remetente_email'],$config_email['remetente_nome']);$mail->addAddress($row['destinatario']);$mail->isHTML(true);$mail->Subject=$row['assunto'];$mail->Body=$row['corpo'];$mail->AltBody=strip_tags($row['corpo']);
        $mail->MessageID='<recanto-'.$row['chave'].'@'.substr(strrchr($config_email['remetente_email'],'@'),1).'>';
        $mail->send();$stmt=$conn->prepare("UPDATE notificacoes SET status='enviado',enviado_em=NOW(),ultimo_erro=NULL,bloqueado_em=NULL WHERE id=?");$stmt->bind_param('i',$row['id']);$stmt->execute();
    } catch(Throwable $error) {
        error_log('Notificação '.$row['id'].': '.$error->getMessage());
        $status=$row['tentativas']>=4?'falhou':'pendente';$next=date('Y-m-d H:i:s',time()+60*(2**((int)$row['tentativas']+1)));$message='Falha no envio SMTP. Consulte o log do servidor.';
        $stmt=$conn->prepare('UPDATE notificacoes SET status=?,proxima_tentativa=?,ultimo_erro=?,bloqueado_em=NULL WHERE id=?');$stmt->bind_param('sssi',$status,$next,$message,$row['id']);$stmt->execute();
    }
}
echo "Processamento concluído.\n";
