<?php 
    include __DIR__.'/../../../config/database.php';

    $id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
    if($id === false || $id < 0) fail('Registro inválido.');
    $vei = null;

    $morador_id  = $_GET['morador_id']  ?? '';
    $bloco       = $_GET['bloco']       ?? '';
    $apartamento = $_GET['apartamento'] ?? '';

    if ($id) {
        $stmt = $conn->prepare("SELECT * FROM veiculos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $vei = $res->fetch_assoc();
        $stmt->close();
        if(!$res->num_rows) fail('Registro não encontrado.',404);
    }

    $tituloPagina = $id ? 'Editar Veículo' : 'Cadastro de Veículo';
require_once __DIR__.'/../../Support/forms.php';
$formState = form_state('veiculos');
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
<link rel="stylesheet" href="<?= esc(base_url('assets/css/app.css')) ?>"><meta name="csrf-token" content="<?= csrf_token() ?>"><script src="<?= esc(base_url('assets/js/app.js?v=20261026')) ?>" defer></script>
</head>

<body>


<div class="container">
<?php if(!empty($_SESSION['flash'])): ?><p class="app-alert" role="status"><?= esc($_SESSION['flash']) ?></p><?php unset($_SESSION['flash']); endif ?>
    <h2><?= $id ? 'Editar Veículo' : 'Cadastro de Veículo' ?></h2>

    <form action="../../crud/salvar_veiculo.php" method="POST">
<input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="linha">
            <div class="campo">
                <label>Bloco</label>
                <input type="text" id="bloco" name="bloco"
                    value="<?= htmlspecialchars($vei['bloco'] ?? $bloco) ?>"
                    placeholder="Ex: 1"
                    <?= $bloco ? 'readonly style="background:#f4f4f4;"' : '' ?>>
            </div>
            <div class="campo">
                <label>Apartamento</label>
                <input type="text" name="apartamento" id="apartamento"
                    value="<?= htmlspecialchars($vei['apartamento'] ?? $apartamento) ?>"
                    placeholder="Ex: 101" required
                    <?= $apartamento ? 'readonly style="background:#f4f4f4;"' : '' ?>>
            </div>
        </div>


        <div class="campo">
            <label>Nome do Morador</label>
            <?php resident_select($conn, (int)($vei['morador_id'] ?? $_GET['morador_id'] ?? 0)); ?>
        </div>
        
        <div class="linha">
            <div class="campo">
                <label>Placa do Veículo</label>
                <input type="text" name="placa" value="<?= htmlspecialchars($vei['placa'] ?? '') ?>" placeholder="ABC-1234" required>
            </div>
            <div class="campo">
                <label>Cor</label>
                <input type="text" name="cor" value="<?= htmlspecialchars($vei['cor'] ?? '') ?>" placeholder="Ex: Preto">
            </div>
        </div>

        <div class="campo">
            <label>Modelo do Veículo</label>
            <input type="text" name="modelo" value="<?= htmlspecialchars($vei['modelo'] ?? '') ?>" placeholder="Ex: Corolla">
        </div>

        <div class="campo">
            <label>Tipo de Veículo</label>
            <select name="tipo_veiculo">
                <option value="Carro" <?= (isset($vei['tipo_veiculo']) && $vei['tipo_veiculo'] == 'Carro') ? 'selected' : '' ?>>Carro</option>
                <option value="Moto" <?= (isset($vei['tipo_veiculo']) && $vei['tipo_veiculo'] == 'Moto') ? 'selected' : '' ?>>Moto</option>
                <option value="Outro" <?= (isset($vei['tipo_veiculo']) && $vei['tipo_veiculo'] == 'Outro') ? 'selected' : '' ?>>Outro</option>
            </select>
        </div>

        <div class="botoes">
            <a href="../telasPrincipais/veiculos.php" class="cancelar">Cancelar</a>
            <button type="submit" class="salvar"><?= $id ? 'Atualizar' : 'Cadastrar' ?></button>
        </div>
    </form>
</div>

<script>

</script>

<?php render_form_state($formState); ?>
</body>
</html>
