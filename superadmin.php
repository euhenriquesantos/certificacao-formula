<?php
session_start();
require_once 'config/database.php';
require_once 'includes/seguranca.php';
require_once 'includes/functions.php';
$page_title = "Superadmin - Certificação Fórmula PetShop";
require_once 'includes/header.php';
verificarLogin();
verificarSuperadmin();

$usuarios = getTodosUsuarios($db);
$acao = filter_input(INPUT_GET, 'acao', FILTER_SANITIZE_STRING);
$prova_numero = filter_input(INPUT_GET, 'prova', FILTER_VALIDATE_INT) ?: 1;
$questoes = getQuestoesPorProva($db, $prova_numero);

// Processa adição de questão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $acao === 'adicionar' && verificarCSRFToken($_POST['csrf_token'])) {
    $pergunta = sanitizarString(trim($_POST['pergunta']));
    $opcoes = array_map('sanitizarString', array_filter([$_POST['opcao_a'], $_POST['opcao_b'], $_POST['opcao_c'], $_POST['opcao_d'], $_POST['opcao_e']]));
    $resposta = (int)$_POST['resposta'];

    if ($pergunta && count($opcoes) >= 2 && $resposta >= 0 && $resposta < count($opcoes)) {
        adicionarQuestao($db, $prova_numero, $pergunta, $opcoes, $resposta);
        header("Location: superadmin.php?prova=$prova_numero");
        exit;
    } else {
        $erro = "Preencha todos os campos obrigatórios corretamente.";
    }
}

// Processa edição de questão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $acao === 'editar' && verificarCSRFToken($_POST['csrf_token'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $pergunta = sanitizarString(trim($_POST['pergunta']));
    $opcoes = array_map('sanitizarString', array_filter([$_POST['opcao_a'], $_POST['opcao_b'], $_POST['opcao_c'], $_POST['opcao_d'], $_POST['opcao_e']]));
    $resposta = (int)$_POST['resposta'];

    if ($id && $pergunta && count($opcoes) >= 2 && $resposta >= 0 && $resposta < count($opcoes)) {
        editarQuestao($db, $id, $pergunta, $opcoes, $resposta);
        header("Location: superadmin.php?prova=$prova_numero");
        exit;
    } else {
        $erro = "Preencha todos os campos obrigatórios corretamente.";
    }
}

// Processa exclusão de questão
if ($acao === 'excluir' && $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)) {
    excluirQuestao($db, $id);
    header("Location: superadmin.php?prova=$prova_numero");
    exit;
}

// Dados para edição
$editar_questao = null;
if ($acao === 'editar' && $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)) {
    $stmt = $db->prepare("SELECT * FROM questoes WHERE id = ?");
    $stmt->execute([$id]);
    $editar_questao = $stmt->fetch();
    $editar_questao['opcoes'] = json_decode($editar_questao['opcoes'], true);
}
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4 text-center">Painel Superadmin</h2>

        <ul class="nav nav-tabs mb-4">
            <li class="nav-item"><a class="nav-link <?php echo !isset($_GET['tab']) || $_GET['tab'] === 'usuarios' ? 'active' : ''; ?>" href="?tab=usuarios">Usuários</a></li>
            <li class="nav-item"><a class="nav-link <?php echo isset($_GET['tab']) && $_GET['tab'] === 'provas' ? 'active' : ''; ?>" href="?tab=provas">Provas</a></li>
        </ul>

        <?php if (!isset($_GET['tab']) || $_GET['tab'] === 'usuarios'): ?>
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Usuários Cadastrados</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Data de Cadastro</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <tr>
                                        <td><?php echo $usuario['id']; ?></td>
                                        <td><?php echo sanitizarString($usuario['nome'] ?? 'Não preenchido'); ?></td>
                                        <td><?php echo sanitizarString($usuario['email']); ?></td>
                                        <td><?php echo formatarData($usuario['data_cadastro']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php elseif ($_GET['tab'] === 'provas'): ?>
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Gerenciar Provas</h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-pills mb-3">
                        <?php for ($i = 1; $i <= 4; $i++): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $prova_numero == $i ? 'active' : ''; ?>" href="?tab=provas&prova=<?php echo $i; ?>">Prova <?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>

                    <?php if (isset($erro)): ?>
                        <div class="alert alert-danger"><?php echo $erro; ?></div>
                    <?php endif; ?>

                    <!-- Formulário para Adicionar/Editar Questão -->
                    <form method="POST" action="?tab=provas&prova=<?php echo $prova_numero; ?>&acao=<?php echo $editar_questao ? 'editar' : 'adicionar'; ?>" class="mb-4">
                        <input type="hidden" name="csrf_token" value="<?php echo gerarCSRFToken(); ?>">
                        <?php if ($editar_questao): ?>
                            <input type="hidden" name="id" value="<?php echo $editar_questao['id']; ?>">
                        <?php endif; ?>
                        <div class="mb-3">
                            <label for="pergunta" class="form-label">Pergunta</label>
                            <textarea class="form-control" id="pergunta" name="pergunta" rows="3" required><?php echo $editar_questao ? sanitizarString($editar_questao['pergunta']) : ''; ?></textarea>
                        </div>
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <div class="mb-3">
                                <label for="opcao_<?php echo chr(97 + $i); ?>" class="form-label">Opção <?php echo chr(97 + $i); ?></label>
                                <input type="text" class="form-control" id="opcao_<?php echo chr(97 + $i); ?>" name="opcao_<?php echo chr(97 + $i); ?>" value="<?php echo $editar_questao && isset($editar_questao['opcoes'][$i]) ? sanitizarString($editar_questao['opcoes'][$i]) : ''; ?>" <?php echo $i < 2 ? 'required' : ''; ?>>
                            </div>
                        <?php endfor; ?>
                        <div class="mb-3">
                            <label for="resposta" class="form-label">Resposta Correta</label>
                            <select class="form-select" id="resposta" name="resposta" required>
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $editar_questao && $editar_questao['resposta'] == $i ? 'selected' : ''; ?>><?php echo chr(97 + $i); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary"><?php echo $editar_questao ? 'Salvar Alterações' : 'Adicionar Questão'; ?></button>
                        <?php if ($editar_questao): ?>
                            <a href="?tab=provas&prova=<?php echo $prova_numero; ?>" class="btn btn-secondary ms-2">Cancelar</a>
                        <?php endif; ?>
                    </form>

                    <!-- Listagem de Questões -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Pergunta</th>
                                    <th>Opções</th>
                                    <th>Resposta Correta</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($questoes)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Nenhuma questão cadastrada para a Prova <?php echo $prova_numero; ?>.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($questoes as $questao): ?>
                                        <?php $opcoes = json_decode($questao['opcoes'], true); ?>
                                        <tr>
                                            <td><?php echo $questao['id']; ?></td>
                                            <td><?php echo sanitizarString($questao['pergunta']); ?></td>
                                            <td>
                                                <ul>
                                                    <?php foreach ($opcoes as $idx => $opcao): ?>
                                                        <li><?php echo chr(97 + $idx) . ") " . sanitizarString($opcao); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </td>
                                            <td><?php echo chr(97 + $questao['resposta']); ?></td>
                                            <td>
                                                <a href="?tab=provas&prova=<?php echo $prova_numero; ?>&acao=editar&id=<?php echo $questao['id']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                                <a href="?tab=provas&prova=<?php echo $prova_numero; ?>&acao=excluir&id=<?php echo $questao['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir esta questão?');">Excluir</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>