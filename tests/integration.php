<?php
if(PHP_SAPI!=='cli')exit;
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$root=dirname(__DIR__);$dbName='recanto_test_'.bin2hex(random_bytes(5));
$db=new mysqli(getenv('DB_HOST')?:'localhost',getenv('DB_USER')?:'root',getenv('DB_PASS')?:'');$db->set_charset('utf8mb4');
$db->query("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");$db->select_db($dbName);
$server=null;$client=curl_init();$checks=0;
function expect(bool $ok,string $message):void {global $checks;if(!$ok)throw new RuntimeException('FALHOU: '.$message);$checks++;echo "OK $message\n";}
function request(string $path,?array $data=null,bool $json=false):array {
    global $client,$port;
    curl_setopt_array($client,[CURLOPT_URL=>'http://127.0.0.1:'.$port.$path,CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_COOKIEFILE=>'',CURLOPT_HTTPHEADER=>$json?['Accept: application/json']:[],CURLOPT_POST=>$data!==null,CURLOPT_TIMEOUT=>10]);
    if($data!==null)curl_setopt($client,CURLOPT_POSTFIELDS,http_build_query($data));
    $response=curl_exec($client);if($response===false)throw new RuntimeException(curl_error($client));
    $size=curl_getinfo($client,CURLINFO_HEADER_SIZE);return [curl_getinfo($client,CURLINFO_HTTP_CODE),substr($response,$size),substr($response,0,$size)];
}
function csrf(string $body):string {if(!preg_match('/name="csrf" value="([^"]+)"/',$body,$m))throw new RuntimeException('Token ausente');return html_entity_decode($m[1]);}
function loginAs(string $email,string $password):string {
    global $client;curl_close($client);$client=curl_init();
    [, $body]=request('/login.php');$token=csrf($body);
    [$status]=request('/login.php',['csrf'=>$token,'email'=>$email,'senha'=>$password]);expect($status===302,'Login '.$email);
    [, $body]=request('/minha-conta.php');return csrf($body);
}
try {
    putenv('DB_NAME='.$dbName);putenv('APP_BASE_URL=/');
    foreach(['install','migrate'] as $script) {passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/bin/'.$script.'.php'),$code);expect($code===0,$script.' no banco isolado');}
    foreach(['administracao','portaria'] as $role) {
        $email=$role.'@example.com';$hash=password_hash('Teste-seguro-2026!',PASSWORD_DEFAULT);$stmt=$db->prepare('INSERT INTO usuarios(nome,email,senha,perfil,trocar_senha) VALUES (?,?,?,?,0)');$stmt->bind_param('ssss',$role,$email,$hash,$role);$stmt->execute();
    }
    $socket=stream_socket_server('tcp://127.0.0.1:0',$errno,$error);if(!$socket)throw new RuntimeException($error);$address=stream_socket_get_name($socket,false);$port=(int)substr(strrchr($address,':'),1);fclose($socket);
    $log=tempnam(sys_get_temp_dir(),'recanto-test-');
    $server=proc_open([PHP_BINARY,'-S','127.0.0.1:'.$port,'-t',$root.'/public',$root.'/public/index.php'],[['pipe','r'],['file',$log,'a'],['file',$log,'a']],$pipes,$root);
    for($i=0;$i<30;$i++){usleep(100000);$ready=@fsockopen('127.0.0.1',$port);if($ready){fclose($ready);break;}}
    [$status]=request('/index.php');expect($status===302,'Acesso anônimo redirecionado');
    foreach(['/assets/css/app.css','/assets/js/moradores.js','/assets/images/logo_novo.png'] as $assetPath) {
        [$status,$body]=request($assetPath);expect($status===200 && $body!=='','Recurso público '.$assetPath);
    }
    foreach(['/config/database.php','/storage/private/smtp.local.json','/app/Views/pages/moradores.php','/routes/web.php'] as $privatePath) {
        [$status]=request($privatePath);expect($status===404,'Código e configuração não expostos: '.$privatePath);
    }
    [$status]=request('/crud/getmoradores.php?bloco=1',null,true);expect($status===401,'API exige login');
    $token=loginAs('administracao@example.com','Teste-seguro-2026!');
    [$status]=request('/usuarios.php',['csrf'=>$token,'nome'=>'Nova Conta','email'=>'nova@example.com','perfil'=>'portaria','senha'=>'1234567']);
    expect($status===200 && (int)$db->query("SELECT COUNT(*) n FROM usuarios WHERE email='nova@example.com'")->fetch_assoc()['n']===0,'Cadastro rejeita senha com 7 caracteres');
    [$status]=request('/usuarios.php',['csrf'=>$token,'nome'=>'Nova Conta','email'=>'nova@example.com','perfil'=>'portaria','senha'=>'Teste123']);
    expect($status===302,'Cadastro aceita senha com 8 caracteres');
    $token=loginAs('nova@example.com','Teste123');
    [$status]=request('/index.php');expect($status===200,'Nova conta acessa painel sem troca obrigatória');
    [$status]=request('/minha-conta.php',['csrf'=>$token,'senha_atual'=>'Teste123','nova_senha'=>'Nova123','confirmacao'=>'Nova123']);
    expect($status===200,'Alteração rejeita senha com 7 caracteres');
    [$status]=request('/minha-conta.php',['csrf'=>$token,'senha_atual'=>'Teste123','nova_senha'=>'Nova1234','confirmacao'=>'Nova1234']);
    expect($status===302,'Alteração aceita senha com 8 caracteres');
    $token=loginAs('nova@example.com','Nova1234');
    [$status]=request('/index.php');expect($status===200,'Senha de 8 caracteres permite novo login');
    $token=loginAs('administracao@example.com','Teste-seguro-2026!');
    $db->query("UPDATE usuarios SET trocar_senha=1 WHERE perfil='administracao'");
    [$status,, $headers]=request('/index.php');expect($status===302 && strpos($headers,'minha-conta.php')!==false,'Senha temporária exige troca');
    [$status]=request('/minha-conta.php',['csrf'=>$token,'senha_atual'=>'Teste-seguro-2026!','nova_senha'=>'Nova-senha-2026!','confirmacao'=>'Nova-senha-2026!']);expect($status===302,'Troca de senha completa');
    [, $body]=request('/minha-conta.php');$token=csrf($body);
    [$status]=request('/crud/excluir_morador.php?id=1');expect($status===405,'GET não exclui');
    [$status]=request('/crud/salvar_morador.php',['nome'=>'Teste']);expect($status===403,'CSRF ausente rejeitado');
    $resident=['csrf'=>$token,'id'=>'','nome'=>'Mesmo Nome','bloco'=>'1','apartamento'=>'101','telefone'=>'11999999999','cpf'=>'52998224725','email'=>'morador@example.com','tipo_morador'=>'Proprietário'];
    [$status]=request('/crud/salvar_morador.php',$resident);expect($status===302,'Morador cadastrado');$mid=(int)$db->query('SELECT id FROM moradores LIMIT 1')->fetch_assoc()['id'];
    [$status]=request('/crud/salvar_morador.php',$resident);expect($status===302 && (int)$db->query('SELECT COUNT(*) n FROM moradores')->fetch_assoc()['n']===1,'Proprietário duplicado bloqueado');
    [$status,$body]=request('/telas/telasCadastro/cadastro_morador.php');expect($status===200 && strpos($body,'form-state')!==false,'Erro preserva dados do formulário');
    [$status,$body]=request('/telas/telasCadastro/cadastro_morador.php?id='.$mid);
    expect($status===200 && strpos($body,'value="Mesmo Nome"')!==false && strpos($body,'value="morador@example.com"')!==false,'Edição de morador carrega os dados existentes');
    $editedResident=array_merge($resident,['id'=>$mid,'email'=>'atualizado@example.com','telefone'=>'11988888888']);
    [$status]=request('/crud/salvar_morador.php',$editedResident);
    $savedResident=$db->query('SELECT email,telefone FROM moradores WHERE id='.$mid)->fetch_assoc();
    expect($status===302 && $savedResident['email']==='atualizado@example.com' && $savedResident['telefone']==='11988888888','Edição de morador salva as alterações');
    [$status,$body]=request('/telas/telasCadastro/cadastro_morador.php?id='.$mid);
    expect($status===200 && strpos($body,'value="atualizado@example.com"')!==false,'Edição de morador exibe os dados atualizados');
    [$status]=request('/telas/telasCadastro/cadastro_morador.php?id=999999');expect($status===404,'Morador inexistente retorna 404');
    [$status]=request('/telas/telasCadastro/cadastro_morador.php?id=invalido');expect($status===400,'ID de morador inválido rejeitado');
    $resident['bloco']='2';request('/crud/salvar_morador.php',$resident);$other=(int)$db->query('SELECT MAX(id) id FROM moradores')->fetch_assoc()['id'];
    $visitor=['csrf'=>$token,'id'=>0,'morador_id'=>$mid,'nome_visitante'=>'Visitante Teste','documento'=>'','data_visita'=>'2026-10-25','status_presenca'=>'Sim','observacao'=>''];
    [$status]=request('/crud/salvar_visitante.php',$visitor);expect($status===302,'Visitante sem documento cadastrado');
    $vid=(int)$db->query('SELECT id FROM visitantes')->fetch_assoc()['id'];
    $linked=$db->query('SELECT morador_id,bloco,entrada_em FROM visitantes')->fetch_assoc();expect((int)$linked['morador_id']===$mid && $linked['bloco']==='1' && $linked['entrada_em']!==null,'Vínculo por ID e entrada registrada');
    $count=(int)$db->query('SELECT COUNT(*) n FROM notificacoes')->fetch_assoc()['n'];
    request('/crud/toggle_presenca_visitante.php',['csrf'=>$token,'id'=>$vid,'acao'=>'entrada']);expect((int)$db->query('SELECT COUNT(*) n FROM notificacoes')->fetch_assoc()['n']===$count,'Entrada repetida não reenvia');
    request('/crud/toggle_presenca_visitante.php',['csrf'=>$token,'id'=>$vid,'acao'=>'saida']);expect($db->query('SELECT saida_em FROM visitantes')->fetch_assoc()['saida_em']!==null,'Saída registrada');
    $package=['csrf'=>$token,'id'=>0,'morador_id'=>$mid,'tipo_encomenda'=>'Correios','descricao'=>'Caixa','data_recebimento'=>'2026-10-25','status_encomenda'=>'Pendente'];
    [$status]=request('/crud/salvar_encomenda.php',$package);expect($status===302,'Encomenda cadastrada');$pid=(int)$db->query('SELECT id FROM encomendas')->fetch_assoc()['id'];
    [$status]=request('/crud/atualizar_status_encomenda.php',['csrf'=>$token,'id'=>$pid,'status'=>'Retirado'],true);expect($status===400,'Retirada exige recebedor');
    $transition=['csrf'=>$token,'id'=>$pid,'status'=>'Retirado','retirado_por'=>'Pessoa Teste'];
    [$status]=request('/crud/atualizar_status_encomenda.php',$transition,true);expect($status===200,'Retirada confirmada');
    $count=(int)$db->query('SELECT COUNT(*) n FROM notificacoes')->fetch_assoc()['n'];request('/crud/atualizar_status_encomenda.php',$transition,true);expect((int)$db->query('SELECT COUNT(*) n FROM notificacoes')->fetch_assoc()['n']===$count,'Retirada repetida não reenvia');
    $row=$db->query('SELECT retirada_em,retirado_por FROM encomendas')->fetch_assoc();expect($row['retirada_em']!==null && $row['retirado_por']==='Pessoa Teste','Comprovante de retirada persistido');
    [$status]=request('/crud/excluir_morador.php',['csrf'=>$token,'id'=>$mid]);expect($status===409,'Exclusão de morador com vínculos bloqueada');
    $car=['csrf'=>$token,'id'=>0,'morador_id'=>$mid,'placa'=>'abc-1234','tipo_veiculo'=>'Carro','modelo'=>'Teste','cor'=>'Azul'];
    [$status]=request('/crud/salvar_veiculo.php',$car);expect($status===302,'Veículo cadastrado');
    request('/crud/salvar_veiculo.php',$car);expect((int)$db->query('SELECT COUNT(*) n FROM veiculos')->fetch_assoc()['n']===1,'Placa duplicada bloqueada');
    request('/crud/salvar_avisos.php',['csrf'=>$token,'id'=>0,'titulo'=>'Aviso teste','descricao'=>'Descrição','nivel_urgencia'=>'alta','data_aviso'=>'2026-10-25']);
    foreach(['/index.php','/telas/telasPrincipais/moradores.php','/telas/telasPrincipais/encomendas.php','/telas/telasPrincipais/visitantes.php','/telas/telasPrincipais/veiculos.php','/telas/telasPrincipais/avisos.php','/telas/telasCadastro/cadastro_encomenda.php?id='.$pid,'/telas/telasCadastro/cadastro_visitante.php?id='.$vid,'/telas/telasCadastro/cadastro_veiculo.php?id=1','/usuarios.php','/pendencias.php'] as $path) {[$status,$body]=request($path);expect($status===200 && strpos($body,'Ocorreu um erro interno')===false,'Tela '.$path);}
    [$status]=request('/telas/telasCadastro/cadastro_visitante.php?id=999999');expect($status===404,'Edição inexistente retorna 404');
    [$status,$body]=request('/crud/getmoradores.php?bloco=1',null,true);$data=json_decode($body,true);expect($status===200 && count($data['moradores'])===1 && $data['moradores'][0]['bloco']==='1','Busca de moradores por bloco');
    for($i=0;$i<55;$i++)$db->query("INSERT INTO avisos(titulo,nivel_urgencia,data_aviso) VALUES ('Página $i','baixa','2026-10-25')");
    [, $body]=request('/telas/telasPrincipais/avisos.php');expect(strpos($body,'pagina=2')!==false,'Paginação oferece próxima página');
    [, $body]=request('/telas/telasPrincipais/avisos.php?pagina=2');expect(strpos($body,'pagina=1')!==false && strpos($body,'pagina=3')===false,'Última página limitada corretamente');
    $token=loginAs('portaria@example.com','Teste-seguro-2026!');
    foreach(['/usuarios.php','/pendencias.php','/telas/telasCadastro/cadastro_morador.php'] as $path){[$status]=request($path);expect($status===403,'Portaria sem acesso a '.$path);}
    [$status,$body]=request('/telas/telasPrincipais/moradores.php');
    expect($status===200 && strpos($body,'btnAdicionarMorador')===false,'Portaria consulta moradores sem botão de cadastro');
    [$status,$body]=request('/crud/getmoradores.php?bloco=1',null,true);$data=json_decode($body,true);
    expect($status===200 && (int)$data['moradores'][0]['id']===$mid,'Portaria lista moradores por bloco');
    [$status]=request('/telas/telasCadastro/cadastro_morador.php?id='.$mid);expect($status===403,'Portaria não acessa edição de morador');
    $beforeResident=$db->query('SELECT * FROM moradores WHERE id='.$mid)->fetch_assoc();
    foreach([0,$mid] as $residentId) {
        [$status]=request('/crud/salvar_morador.php',array_merge($resident,['csrf'=>$token,'id'=>$residentId]));
        expect($status===403,'Portaria não grava morador com id '.$residentId);
    }
    [$status]=request('/crud/excluir_morador.php',['csrf'=>$token,'id'=>$mid]);expect($status===403,'Portaria não exclui morador');
    expect($db->query('SELECT * FROM moradores WHERE id='.$mid)->fetch_assoc()===$beforeResident,'Informações do morador preservadas');
    foreach(['veiculo','encomenda','visitante'] as $form) {
        [$status]=request('/telas/telasCadastro/cadastro_'.$form.'.php?morador_id='.$mid.'&bloco=1&apartamento=101');
        expect($status===200,'Portaria acessa cadastro de '.$form);
    }
    [$status]=request('/crud/salvar_veiculo.php',array_merge($car,['csrf'=>$token,'placa'=>'DEF5678']));
    expect($status===302 && (int)$db->query("SELECT COUNT(*) n FROM veiculos WHERE placa='DEF5678'")->fetch_assoc()['n']===1,'Portaria registra veículo');
    [$status]=request('/crud/salvar_encomenda.php',array_merge($package,['csrf'=>$token,'descricao'=>'Encomenda da portaria']));
    expect($status===302 && (int)$db->query("SELECT COUNT(*) n FROM encomendas WHERE descricao='Encomenda da portaria'")->fetch_assoc()['n']===1,'Portaria registra encomenda');
    [$status]=request('/crud/salvar_visitante.php',array_merge($visitor,['csrf'=>$token,'nome_visitante'=>'Visitante da portaria']));
    expect($status===302 && (int)$db->query("SELECT COUNT(*) n FROM visitantes WHERE nome_visitante='Visitante da portaria'")->fetch_assoc()['n']===1,'Portaria registra visitante');
    foreach(['veiculo','visitante'] as $entity) {
        [$status]=request('/crud/excluir_'.$entity.'.php',['csrf'=>$token,'id'=>1]);expect($status===403,'Portaria não exclui '.$entity);
    }
    [$status]=request('/crud/excluir_encomenda.php',['csrf'=>$token,'id'=>$pid],true);expect($status===403,'Portaria não exclui');
    $transition['csrf']=$token;$transition['status']='Pendente';[$status]=request('/crud/atualizar_status_encomenda.php',$transition,true);expect($status===200,'Portaria altera status');
    $db->query("UPDATE usuarios SET ativo=0 WHERE perfil='portaria'");[$status]=request('/index.php');expect($status===401,'Desativação invalida sessão existente');
    expect((int)$db->query('SELECT COUNT(*) n FROM auditoria')->fetch_assoc()['n']>=8,'Auditoria das operações gravada');
    $token=loginAs('administracao@example.com','Nova-senha-2026!');
    [$status]=request('/crud/excluir_encomenda.php',['csrf'=>$token,'id'=>$pid],true);expect($status===200,'Administração exclui por POST');
    putenv('SMTP_USER');putenv('SMTP_PASSWORD');putenv('REMETENTE_EMAIL');
    $before=(int)$db->query("SELECT SUM(tentativas) n FROM notificacoes")->fetch_assoc()['n'];
    exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/bin/process-notifications.php').' 2>&1',$workerOutput,$workerCode);
    expect($workerCode===1 && (int)$db->query('SELECT SUM(tentativas) n FROM notificacoes')->fetch_assoc()['n']===$before,'Fila sem SMTP preserva mensagens e sinaliza falha');
    putenv('SMTP_HOST=127.0.0.1');putenv('SMTP_PORT=1');putenv('SMTP_USER=test');putenv('SMTP_PASSWORD=test');putenv('REMETENTE_EMAIL=test@example.com');
    $count=(int)$db->query('SELECT COUNT(*) n FROM notificacoes')->fetch_assoc()['n'];
    exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/bin/process-notifications.php').' 2>&1',$workerOutput,$workerCode);
    expect($workerCode===0 && (int)$db->query("SELECT COUNT(*) n FROM notificacoes WHERE tentativas=1 AND status='pendente' AND proxima_tentativa>NOW()")->fetch_assoc()['n']===$count,'Falhas SMTP reagendadas sem envio externo');
    echo "SUCESSO: $checks verificações de integração. Nenhum e-mail enviado.\n";
} finally {
    if(is_resource($server)){proc_terminate($server);proc_close($server);}
    // Only the randomly created test schema may be dropped.
    if(preg_match('/^recanto_test_[a-f0-9]{10}$/',$dbName))$db->query("DROP DATABASE `$dbName`");
    curl_close($client);
}
