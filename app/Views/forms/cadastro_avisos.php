<?php 
    include __DIR__.'/../../../config/database.php'; 

    $id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
    if($id === false || $id < 0) fail('Registro inválido.');
    $aviso = null;

    if ($id) {

        $stmt = $conn->prepare("SELECT * FROM avisos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $aviso = $res->fetch_assoc();
        $stmt->close();
        if(!$res->num_rows) fail('Registro não encontrado.',404);
    }

    $tituloPagina = $id ? 'Editar Aviso' : 'Novo Aviso';
require_once __DIR__.'/../../Support/forms.php';
$formState = form_state('avisos');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recanto das Araras — <?= htmlspecialchars($tituloPagina) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/style_cadastro_base.css">
    <link rel="stylesheet" href="../../assets/css/componentes.css">
    <link rel="stylesheet" href="../../assets/css/visual-2026.css?v=20261020">
<link rel="stylesheet" href="<?= esc(base_url('assets/css/app.css')) ?>"><meta name="csrf-token" content="<?= csrf_token() ?>"><script src="<?= esc(base_url('assets/js/app.js')) ?>" defer></script>
</head>
<body>

    <div class="container">
<?php if(!empty($_SESSION['flash'])): ?><p class="app-alert" role="status"><?= esc($_SESSION['flash']) ?></p><?php unset($_SESSION['flash']); endif ?>
        <h2><?= $id ? 'Editar Aviso' : 'Novo Aviso' ?></h2>
        
        <form action="../../crud/salvar_avisos.php" method="POST">
<input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="id" value="<?= $id ?>">

            <div class="campo">
                <label>Título do Aviso</label>
                <input type="text" name="titulo" placeholder="Manutenção da piscina" value="<?= htmlspecialchars($aviso['titulo'] ?? '') ?>" required>
            </div>

            <div class="campo">
                <label>Descrição</label>
                <textarea name="descricao" placeholder="A manutenção ocorrerá por uma semana"><?= htmlspecialchars($aviso['descricao'] ?? '') ?></textarea>
            </div>

            <div class="campo">
                <label>Nível de Urgência</label>
                <select name="nivel_urgencia">
                    <option value="baixa" <?= (isset($aviso['nivel_urgencia']) && $aviso['nivel_urgencia'] == 'baixa') ? 'selected' : '' ?>>Baixa</option>
                    <option value="media" <?= (isset($aviso['nivel_urgencia']) && $aviso['nivel_urgencia'] == 'media') ? 'selected' : '' ?>>Média</option>
                    <option value="alta" <?= (isset($aviso['nivel_urgencia']) && $aviso['nivel_urgencia'] == 'alta') ? 'selected' : '' ?>>Alta</option>
                </select>
            </div>

            <div class="campo">
                <label>Data do Aviso</label>
                <input type="date" name="data_aviso" value="<?= $aviso['data_aviso'] ?? date('Y-m-d') ?>" required>
            </div>

            <div class="botoes">
                <a href="../telasPrincipais/avisos.php" class="cancelar">Cancelar</a>
                <button type="submit" class="salvar"><?= $id ? 'Atualizar' : 'Salvar' ?></button>
            </div>
        </form>
    </div>
<?php render_form_state($formState); ?>
</body>
</html>