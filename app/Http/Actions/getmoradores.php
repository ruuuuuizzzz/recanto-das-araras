<?php
require __DIR__.'/../../../config/database.php';
$block=trim((string)($_GET['bloco']??''));$search='%'.trim((string)($_GET['busca']??'')).'%';
if(!preg_match('/^[1-9]\d{0,9}$/',$block))fail('Bloco inválido.');
$stmt=$conn->prepare('SELECT id,nome,apartamento,bloco FROM moradores WHERE bloco=? AND nome LIKE ? ORDER BY CAST(apartamento AS UNSIGNED),id'.pagination_sql());
$stmt->bind_param('ss',$block,$search);$stmt->execute();$rows=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);pagination($rows);
json_response(['moradores'=>$rows,'proxima'=>!empty($GLOBALS['has_next_page'])]);