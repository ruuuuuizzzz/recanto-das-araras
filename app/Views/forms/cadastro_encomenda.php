<?php 
    include __DIR__.'/../../../config/database.php'; 

    $id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
    if($id === false || $id < 0) fail('Registro inválido.');
    $enc = null;

    $morador_id  = $_GET['morador_id']  ?? '';
    $bloco       = $_GET['bloco']       ?? '';
    $apartamento = $_GET['apartamento'] ?? '';

    if ($id) {

        $stmt = $conn->prepare("SELECT * FROM encomendas WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $enc = $res->fetch_assoc();
        $stmt->close();
        if(!$res->num_rows) fail('Registro não encontrado.',404);
    }

    $tituloPagina = $id ? 'Editar Encomenda' : 'Cadastro de Encomenda';
require_once __DIR__.'/../../Support/forms.php';
$formState = form_state('encomendas');
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
    <style>
        input, select { width: 100%; box-sizing: border-box; }
        .linha { display: flex; gap: 10px; width: 100%; }
        .linha .campo { flex: 1; min-width: 0; }
    </style>
    <link rel="stylesheet" href="../../assets/css/visual-2026.css?v=20261020">
<link rel="stylesheet" href="<?= esc(base_url('assets/css/app.css')) ?>"><meta name="csrf-token" content="<?= csrf_token() ?>"><script src="<?= esc(base_url('assets/js/app.js?v=20261026')) ?>" defer></script>
</head>

<body>


<div class="container">
<?php if(!empty($_SESSION['flash'])): ?><p class="app-alert" role="status"><?= esc($_SESSION['flash']) ?></p><?php unset($_SESSION['flash']); endif ?>
    <h2><?= $id ? 'Editar Encomenda' : 'Cadastro de Encomenda' ?></h2>

    <form action="../../crud/salvar_encomenda.php" method="POST">
<input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="linha">
            <div class="campo">
                <label>Bloco</label>
                <input type="text" id="bloco" name="bloco"
                    value="<?= htmlspecialchars($enc['bloco'] ?? $bloco) ?>"
                    placeholder="Ex: 1"
                    <?= $bloco ? 'readonly style="background:#f4f4f4;"' : '' ?>>
            </div>
            <div class="campo">
                <label>Apartamento</label>
                <input type="text" name="apartamento" id="apartamento"
                    value="<?= htmlspecialchars($enc['apartamento'] ?? $apartamento) ?>"
                    placeholder="Ex: 101" required
                    <?= $apartamento ? 'readonly style="background:#f4f4f4;"' : '' ?>>
            </div>
        </div>

        <div class="campo">
            <label>Nome do Morador</label>
            <?php resident_select($conn, (int)($enc['morador_id'] ?? $_GET['morador_id'] ?? 0)); ?>
        </div>

        <div class="campo">
            <label>Tipo de Encomenda</label>
            <select name="tipo_encomenda">
                <option value="Correios"        <?= (isset($enc['tipo_encomenda']) && $enc['tipo_encomenda'] == 'Correios')        ? 'selected' : '' ?>>Correios</option>
                <option value="Mercado Livre"   <?= (isset($enc['tipo_encomenda']) && $enc['tipo_encomenda'] == 'Mercado Livre')   ? 'selected' : '' ?>>Mercado Livre</option>
                <option value="Amazon"          <?= (isset($enc['tipo_encomenda']) && $enc['tipo_encomenda'] == 'Amazon')          ? 'selected' : '' ?>>Amazon</option>
                <option value="Particular"      <?= (isset($enc['tipo_encomenda']) && $enc['tipo_encomenda'] == 'Particular')      ? 'selected' : '' ?>>Entrega Particular</option>
            </select>
        </div>

        <div class="campo">
            <label>Descrição</label>
            <input type="text" name="descricao"
                value="<?= htmlspecialchars($enc['descricao'] ?? '') ?>"
                placeholder="Ex: Pacote pequeno, caixa azul...">
        </div>

        <div class="campo">
            <label>Data de Recebimento</label>
            <input type="date" name="data_recebimento"
                value="<?= htmlspecialchars($enc['data_recebimento'] ?? date('Y-m-d')) ?>">
        </div>

        <label>Status<select name="status_encomenda"><option value="Pendente" <?= ($enc['status_encomenda']??'Pendente')==='Pendente'?'selected':'' ?>>Pendente</option><option value="Retirado" <?= ($enc['status_encomenda']??'')==='Retirado'?'selected':'' ?>>Retirado</option></select></label>

        <label>Quem recebeu a encomenda (obrigatório ao retirar)<input name="retirado_por" maxlength="100" value="<?= esc($enc['retirado_por'] ?? '') ?>"></label>
<div class="botoes">
            <a href="../telasPrincipais/encomendas.php" class="cancelar">Voltar</a>
            <?php if ($id && $_SESSION['user']['perfil']==='administracao'): ?>
                <button type="button" class="excluir" onclick="excluirEncomenda(<?php echo $id; ?>)">Excluir</button>
            <?php endif; ?>
            <button type="submit" class="salvar"><?= $id ? 'Atualizar' : 'Cadastrar' ?></button>
        </div>
    </form>
</div>

<script>


function excluirEncomenda(id) {
    if (confirm('Tem certeza que deseja excluir esta encomenda?')) {
        const formData = new FormData();
        formData.append('id', id);

        fetch('../../crud/excluir_encomenda.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Encomenda excluída com sucesso!');
                window.location.href = '../telasPrincipais/encomendas.php';
            } else {
                alert('Erro ao excluir encomenda: ' + (data.message || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao excluir encomenda. Tente novamente.');
        });
    }
}
</script>

<?php render_form_state($formState); ?>
</body>
</html>
