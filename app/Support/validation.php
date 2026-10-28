<?php
require_once __DIR__.'/documents.php';
function validate_record(string $entity, array $input): array {
    $specs = [
        'moradores'=>['nome'=>[true,100],'bloco'=>[true,10],'apartamento'=>[true,10],'telefone'=>[false,20],'cpf'=>[false,20],'email'=>[false,100],'tipo_morador'=>[true,50]],
        'encomendas'=>['tipo_encomenda'=>[true,50],'descricao'=>[false,255],'data_recebimento'=>[true,10],'status_encomenda'=>[true,20]],
        'visitantes'=>['nome_visitante'=>[true,100],'documento'=>[false,20],'data_visita'=>[true,10],'observacao'=>[false,5000],'status_presenca'=>[true,20]],
        'veiculos'=>['placa'=>[true,20],'modelo'=>[false,50],'cor'=>[false,30],'tipo_veiculo'=>[true,20]],
        'avisos'=>['titulo'=>[true,150],'descricao'=>[false,10000],'nivel_urgencia'=>[true,10],'data_aviso'=>[true,10]]
    ];
    $data=[]; $errors=[];
    foreach($specs[$entity] as $field=>[$required,$length]) {
        $value=is_string($input[$field]??'')?trim($input[$field]??''):''; $data[$field]=$value;
        if ($required && $value==='') $errors[$field]='Preencha este campo.';
        elseif(mb_strlen($value)>$length) $errors[$field]="Use até $length caracteres.";
    }
    foreach(['data_visita','data_recebimento','data_aviso'] as $field) if(isset($data[$field])) {
        $date=DateTimeImmutable::createFromFormat('!Y-m-d',$data[$field]);
        if(!$date || $date->format('Y-m-d')!==$data[$field]) $errors[$field]='Informe uma data válida.';
    }
    $enums=['tipo_morador'=>['Proprietário','Inquilino','Dependente'],'tipo_veiculo'=>['Carro','Moto','Outro'],'status_encomenda'=>['Pendente','Retirado'],'status_presenca'=>['Sim','Não'],'nivel_urgencia'=>['baixa','media','alta']];
    foreach($enums as $field=>$values) if(isset($data[$field]) && !in_array($data[$field],$values,true)) $errors[$field]='Selecione uma opção válida.';
    if(!empty($data['email']) && !filter_var($data['email'],FILTER_VALIDATE_EMAIL)) $errors['email']='Informe um e-mail válido.';
    if(!empty($data['cpf']) && !validar_cpf($data['cpf'])) $errors['cpf']='CPF inválido.';
    if(!empty($data['telefone']) && !preg_match('/^\d{10,11}$/',preg_replace('/\D/','',$data['telefone']))) $errors['telefone']='Informe DDD e telefone com 10 ou 11 dígitos.';
    if(!empty($data['bloco']) && !preg_match('/^[1-9]\d{0,9}$/',$data['bloco'])) $errors['bloco']='Informe um bloco válido.';
    if(isset($data['placa'])) {
        $data['placa']=strtoupper(preg_replace('/[\s-]/','',$data['placa']));
        if(!preg_match('/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/',$data['placa'])) $errors['placa']='Informe uma placa válida (ABC1234 ou ABC1D23).';
    }
    // Visitor documents may be RG, CPF or CNPJ; RG has no single national check-digit algorithm.
    if(!empty($data['documento'])) {
        $doc=preg_replace('/[^0-9Xx]/','',$data['documento']);
        if(in_array(strlen($doc),[11,14],true) ? !validar_documento($doc) : !preg_match('/^[0-9]{5,9}[0-9Xx]?$/',$doc)) $errors['documento']='Informe um RG, CPF ou CNPJ válido.';
    }
    return [$data,$errors];
}
function form_error(string $entity, array $errors): void {
    $_SESSION['form']=['entity'=>$entity,'values'=>$_POST,'errors'=>$errors];
    $names=['moradores'=>'morador','encomendas'=>'encomenda','visitantes'=>'visitante','veiculos'=>'veiculo','avisos'=>'avisos'];
    $id=filter_var($_POST['id']??0,FILTER_VALIDATE_INT)?:0;
    header('Location: '.base_url('telas/telasCadastro/cadastro_'.$names[$entity].'.php'.($id?'?id='.$id:''))); exit;
}
