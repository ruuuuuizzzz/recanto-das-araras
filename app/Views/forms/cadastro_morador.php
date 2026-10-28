<?php 
    include __DIR__.'/../../../config/database.php';

    $id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
    if ($id === false || $id < 0) fail('Registro inválido.');
    $bloco_url = isset($_GET['bloco']) ? intval($_GET['bloco']) : 1;
    $morador = null;

    if ($id) {

        $stmt = $conn->prepare("SELECT * FROM moradores WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $morador = $result->fetch_assoc();
        $stmt->close();
        if (!$morador) fail('Registro não encontrado.',404);
    }

    $tituloPagina = $id ? 'Editar Morador' : 'Cadastro de Morador';
require_once __DIR__.'/../../Support/forms.php';
$formState = form_state('moradores');
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../assets/css/visual-2026.css?v=20261020">
<link rel="stylesheet" href="<?= esc(base_url('assets/css/app.css')) ?>"><meta name="csrf-token" content="<?= csrf_token() ?>"><script src="<?= esc(base_url('assets/js/app.js')) ?>" defer></script>
</head>
<body>


<div class="container">
<?php if(!empty($_SESSION['flash'])): ?><p class="app-alert" role="status"><?= esc($_SESSION['flash']) ?></p><?php unset($_SESSION['flash']); endif ?>
    <h2><?= $id ? 'Editar Morador' : 'Cadastro de Morador' ?></h2>

    <form action="../../crud/salvar_morador.php" method="POST">
<input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="campo">
            <label>Nome Completo</label>
            <input type="text" name="nome"
                   value="<?= htmlspecialchars($morador['nome'] ?? '') ?>"
                   placeholder="Digite o nome completo"
                   required>
        </div>

        <div class="linha">
            <div class="campo">
                <label>Bloco</label>
                <select name="bloco">
                    <?php for ($i = 1; $i <= 3; $i++):
                        $selecionado = ($morador ? $morador['bloco'] : $bloco_url) == $i ? 'selected' : '';
                    ?>
                        <option value="<?= $i ?>" <?= $selecionado ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="campo">
                <label>Apartamento</label>
                <input type="text" name="apartamento"
                       value="<?= htmlspecialchars($morador['apartamento'] ?? '') ?>"
                       placeholder="Ex: 101"
                       required>
            </div>
        </div>

        <div class="campo">
            <label>Telefone</label>
            <input type="tel" name="telefone"
                   value="<?= htmlspecialchars($morador['telefone'] ?? '') ?>"
                   placeholder="(00) 00000-0000">
        </div>

        <div class="campo">
            <label>CPF</label>
            <input type="text" name="cpf"
                   value="<?= htmlspecialchars($morador['cpf'] ?? '') ?>"
                   placeholder="000.000.000-00">
        </div>

        <div class="campo">
            <label>Email</label>
            <input type="email" name="email"
                   value="<?= htmlspecialchars($morador['email'] ?? '') ?>"
                   placeholder="email@exemplo.com">
        </div>

        <div class="campo">
            <label>Tipo de Morador</label>
            <select name="tipo_morador">
                <?php
                $tipos = ['Proprietário', 'Inquilino', 'Dependente'];
                foreach ($tipos as $t): ?>
                    <option value="<?= $t ?>"
                        <?= (isset($morador['tipo_morador']) && $morador['tipo_morador'] === $t) ? 'selected' : '' ?>>
                        <?= $t ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="botoes">
            <a href="../telasPrincipais/moradores.php" class="cancelar">Cancelar</a>

            <?php if ($id): ?>
                <button type="submit" class="salvar">Alterar</button>
                <a href="#"
                   class="excluir"
                   onclick="confirmarExclusao(<?= $id ?>); return false;">
                    Excluir
                </a>
            <?php else: ?>
                <button type="submit" class="salvar">Cadastrar</button>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
function confirmarExclusao(id) {
    Swal.fire({
        title: 'Tem certeza?',
        text: "Você não poderá reverter isso!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#a12d2d',
        cancelButtonColor: '#2e864e',
        confirmButtonText: 'Sim, excluir!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
           
            submitAction('../../crud/excluir_morador.php', id);
        }
    });
}
    
    <?php if (isset($_GET['erro']) && $_GET['erro'] == 'proprietario_existente'): ?>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                title: 'Atenção!',
                text: 'Já existe um Proprietário cadastrado para este Bloco e Apartamento.',
                icon: 'error',
                confirmButtonColor: '#a12d2d',
                confirmButtonText: 'Entendi'
            });
        });
    <?php endif; ?>
</script>

<?php render_form_state($formState); ?>
</body>
</html>
