<?php
require_once __DIR__.'/../../config/database.php';require_post();
$id=filter_var($_POST['id']??0,FILTER_VALIDATE_INT);if(!$id || $id<1) fail('Registro inválido.');
try {
    $conn->begin_transaction();
    $stmt=$conn->prepare("SELECT id FROM `$entity` WHERE id=? FOR UPDATE");$stmt->bind_param('i',$id);$stmt->execute();
    if(!$stmt->get_result()->num_rows) { $conn->rollback();fail('Registro não encontrado.',404); }
    $stmt=$conn->prepare("DELETE FROM `$entity` WHERE id=?");$stmt->bind_param('i',$id);$stmt->execute();
    audit($conn,$entity,$id,'excluir');$conn->commit();
} catch(mysqli_sql_exception $error) {
    $conn->rollback();if($error->getCode()===1451) fail('Este morador possui registros vinculados. Preserve o histórico ou transfira os vínculos antes de excluir.',409);throw $error;
}
if(wants_json()) json_response(['success'=>true]);
$_SESSION['flash']='Registro excluído.';header('Location: '.base_url('telas/telasPrincipais/'.$entity.'.php'));
