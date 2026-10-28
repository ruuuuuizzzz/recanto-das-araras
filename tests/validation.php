<?php
require __DIR__.'/../app/Support/validation.php';
function check(bool $ok,string $message):void {if(!$ok)throw new RuntimeException($message);echo "OK $message\n";}
$base=['nome'=>'Morador','bloco'=>'1','apartamento'=>'101','telefone'=>'11999999999','cpf'=>'529.982.247-25','email'=>'teste@example.com','tipo_morador'=>'Proprietário'];
[, $errors]=validate_record('moradores',$base);check(!$errors,'Morador válido');
foreach(['cpf'=>'11111111111','email'=>'invalido','telefone'=>'12','bloco'=>'-1','nome'=>'','tipo_morador'=>'Admin'] as $key=>$value){[, $errors]=validate_record('moradores',array_merge($base,[$key=>$value]));check(isset($errors[$key]),'Rejeita '.$key.' inválido');}
[$data,$errors]=validate_record('veiculos',['placa'=>'abc-1234','tipo_veiculo'=>'Carro']);check(!$errors && $data['placa']==='ABC1234','Normaliza placa');
[, $errors]=validate_record('visitantes',['nome_visitante'=>'Visitante','documento'=>'12.345.678-X','data_visita'=>'2026-02-30','status_presenca'=>'Sim']);check(isset($errors['data_visita']) && !isset($errors['documento']),'Data impossível rejeitada e RG aceito');
[, $errors]=validate_record('avisos',['titulo'=>str_repeat('a',151),'data_aviso'=>'2026-10-25','nivel_urgencia'=>'urgente']);check(isset($errors['titulo'],$errors['nivel_urgencia']),'Limites e enumerações');
echo "Validação concluída.\n";
