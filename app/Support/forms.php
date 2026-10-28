<?php
function form_state(string $entity): array {
    if(($_SESSION['form']['entity']??'')!==$entity)return [];
    $state=$_SESSION['form'];unset($_SESSION['form']);return $state;
}
function render_form_state(array $state): void {
    if(!$state)return;
    echo '<script type="application/json" id="form-state">'.json_encode($state,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).'</script>';
}
function resident_select(mysqli $db, int $selected): void {
    $res=$db->query('SELECT id,nome,bloco,apartamento FROM moradores ORDER BY bloco,apartamento,nome');
    echo '<select name="morador_id" id="morador_id" required><option value="">Selecione o morador responsável</option>';
    foreach($res as $row)echo '<option value="'.(int)$row['id'].'" data-bloco="'.esc($row['bloco']).'" data-apartamento="'.esc($row['apartamento']).'" '.($selected===(int)$row['id']?'selected':'').'>'.esc($row['nome'].' · Bloco '.$row['bloco'].' · Apto '.$row['apartamento']).'</option>';
    echo '</select>';
}
