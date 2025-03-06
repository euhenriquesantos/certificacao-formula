<?php
session_start();
require_once 'config/database.php';
require_once 'includes/seguranca.php';
require_once 'includes/functions.php';
$page_title = "Provas - Certificação Fórmula PetShop";
require_once 'includes/header.php';
verificarLogin();

$usuario_id = $_SESSION['usuario_id'];
$provas = getStatusProvas($db, $usuario_id);
$prova_selecionada = filter_input(INPUT_GET, 'prova', FILTER_VALIDATE_INT);
$resultado_prova = null;
$questoes = [];
$tempo_restante_prova = null;
$erros_usuario = [];

if ($prova_selecionada) {
    $resultado_prova = processarProva($db, $usuario_id, $prova_selecionada, $tempo_restante_prova);
    if (!$resultado_prova['bloqueada'] && !$resultado_prova['resultado']) {
        $questoes = getQuestoesProva($db, $prova_selecionada);
        if (count($questoes) < 20) {
            $resultado_prova['erro'] = "A Prova $prova_selecionada está incompleta com apenas " . count($questoes) . " questões. São necessárias 20 questões.";
        } else {
            shuffle($questoes); // Embaralha as questões
            if (!isset($_SESSION['prova_iniciada'][$prova_selecionada])) {
                $_SESSION['prova_iniciada'][$prova_selecionada] = time();
                $tempo_restante_prova = 1800; // 30 minutos
            } else {
                $tempo_decorrido = time() - $_SESSION['prova_iniciada'][$prova_selecionada];
                $tempo_restante_prova = max(0, 1800 - $tempo_decorrido);
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $prova_selecionada && !$resultado_prova['bloqueada'] && verificarCSRFToken($_POST['csrf_token'])) {
    $respostas = $_POST['respostas'] ?? [];
    $stmt = $db->prepare("SELECT id, pergunta, resposta, opcoes FROM questoes WHERE prova_numero = ?");
    $stmt->execute([$prova_selecionada]);
    $questoes_db = $stmt->fetchAll();
    
    if (empty($questoes_db) || count($questoes_db) < 20) {
        $resultado_prova['erro'] = "Nenhuma questão ou prova incompleta para a Prova $prova_selecionada.";
    } else {
        $total_questoes = count($questoes_db);
        $corretas = 0;

        foreach ($questoes_db as $questao) {
            $resposta_usuario = $respostas[$questao['id']] ?? '';
            $opcoes = json_decode($questao['opcoes'], true);
            $letras = ['a', 'b', 'c', 'd', 'e'];
            $indice_usuario = array_search($resposta_usuario, $letras);
            $resposta_correta = (int)$questao['resposta'];

            if ($indice_usuario !== false && $resposta_correta === $indice_usuario) {
                $corretas++;
            } else {
                $erros_usuario[$questao['id']] = [
                    'pergunta' => $questao['pergunta'],
                    'resposta_usuario' => $resposta_usuario ? $opcoes[$indice_usuario] : 'Nenhuma selecionada',
                    'resposta_correta' => $opcoes[$resposta_correta],
                ];
            }
        }

        $pontuacao = ($corretas / $total_questoes) * 100;
        $prova_id = registrarProva($db, $usuario_id, $prova_selecionada, $pontuacao);

        if ($pontuacao >= 70) {
            $resultado_prova['mensagem'] = "Parabéns! Prova concluída com sucesso! Você acertou $corretas de $total_questoes questões.";
            $token = gerarCertificado($db, $usuario_id, $prova_id);
            $resultado_prova['mensagem'] .= " Certificado gerado: $token.";
            $resultado_prova['certificado_token'] = $token;
        } else {
            $resultado_prova['mensagem'] = "Você foi reprovado! Você acertou $corretas de $total_questoes questões. Pontuação: " . number_format($pontuacao, 1) . "%. Você poderá refazer a prova em 24 horas.";
            $resultado_prova['erros'] = $erros_usuario;
        }
        $resultado_prova['resultado'] = $pontuacao;
        unset($_SESSION['prova_iniciada'][$prova_selecionada]);
    }
}
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4 text-center">Provas Disponíveis</h2>

        <?php if ($resultado_prova && $resultado_prova['mensagem']): ?>
            <div class="alert alert-<?php echo $resultado_prova['resultado'] >= 70 ? 'success' : 'danger'; ?> text-center">
                <?php echo $resultado_prova['mensagem']; ?>
            </div>
        <?php endif; ?>
        <?php if ($resultado_prova && $resultado_prova['erro']): ?>
            <div class="alert alert-danger text-center"><?php echo $resultado_prova['erro']; ?></div>
        <?php endif; ?>

        <?php if (!$prova_selecionada): ?>
            <div class="row row-cols-1 row-cols-md-2 g-4">
                <?php foreach ($provas as $i => $prova): ?>
                    <div class="col">
                        <div class="card shadow-sm h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">Prova <?php echo $i; ?></h5>
                                <p class="card-text">Pontuação: <?php echo number_format($prova['pontuacao'], 1); ?>%</p>
                                <?php if ($prova['bloqueada']): ?>
                                    <p class="text-muted">Bloqueada por 24 horas. Tempo restante: <?php echo $prova['tempo_restante']; ?></p>
                                <?php else: ?>
                                    <a href="?prova=<?php echo $i; ?>" class="btn btn-primary <?php echo $prova['pontuacao'] >= 100 ? 'disabled' : ''; ?>">
                                        <?php echo $prova['pontuacao'] >= 100 ? 'Concluída' : 'Fazer'; ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($resultado_prova['bloqueada']): ?>
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-white text-center">
                    <h5 class="card-title mb-0">Prova <?php echo $prova_selecionada; ?> Bloqueada</h5>
                </div>
                <div class="card-body text-center">
                    <p>Esta prova está bloqueada por 24 horas. Tempo restante: <?php echo $resultado_prova['tempo_restante']; ?>.</p>
                    <a href="provas.php" class="btn btn-primary">Voltar</a>
                </div>
            </div>
        <?php elseif ($resultado_prova['resultado'] !== null): ?>
            <div class="card shadow-sm">
                <div class="card-header bg-<?php echo $resultado_prova['resultado'] >= 70 ? 'success' : 'danger'; ?> text-white text-center">
                    <h5 class="card-title mb-0">Resultado da Prova <?php echo $prova_selecionada; ?></h5>
                </div>
                <div class="card-body">
                    <p class="text-center">Sua pontuação: <strong><?php echo number_format($resultado_prova['resultado'], 1); ?>%</strong></p>
                    <?php if ($resultado_prova['resultado'] >= 70): ?>
                        <p class="text-success fw-bold text-center">Você foi aprovado!</p>
                        <div class="text-center mt-3">
                            <a href="certificados.php?token=<?php echo $resultado_prova['certificado_token']; ?>" class="btn btn-outline-success">Baixar Certificado</a>
                            <a href="provas.php" class="btn btn-primary ms-2">Voltar</a>
                        </div>
                    <?php else: ?>
                        <p class="text-danger fw-bold text-center">Você foi reprovado! Veja abaixo as questões que você errou:</p>
                        <div class="mt-3">
                            <?php foreach ($resultado_prova['erros'] as $id => $erro): ?>
                                <div class="mb-3 p-3 border rounded bg-light">
                                    <h6 class="text-danger"><?php echo sanitizarString($erro['pergunta']); ?></h6>
                                    <p><strong>Sua resposta:</strong> <?php echo sanitizarString($erro['resposta_usuario']); ?></p>
                                    <p><strong>Resposta correta:</strong> <span class="text-success"><?php echo sanitizarString($erro['resposta_correta']); ?></span></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-center">Você poderá refazer a prova em 24 horas.</p>
                        <div class="text-center mt-3">
                            <a href="provas.php" class="btn btn-primary">Voltar</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif (empty($questoes)): ?>
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white text-center">
                    <h5 class="card-title mb-0">Erro</h5>
                </div>
                <div class="card-body text-center">
                    <p>Nenhuma questão disponível ou prova incompleta para a Prova <?php echo $prova_selecionada; ?>.</p>
                    <a href="provas.php" class="btn btn-primary">Voltar</a>
                </div>
            </div>
        <?php else: ?>
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Prova <?php echo $prova_selecionada; ?></h5>
                    <div id="timer" class="badge bg-light text-dark p-2">Tempo Restante: <span id="tempo"><?php echo floor($tempo_restante_prova / 60); ?>:<?php echo str_pad($tempo_restante_prova % 60, 2, '0', STR_PAD_LEFT); ?></span> (<strong><?php echo count($questoes); ?></strong> questões)</div>
                </div>
                <div class="card-body">
                    <form method="POST" action="?prova=<?php echo $prova_selecionada; ?>" id="provaForm">
                        <input type="hidden" name="csrf_token" value="<?php echo gerarCSRFToken(); ?>">
                        <?php foreach ($questoes as $index => $questao): ?>
                            <div class="mb-4 p-3 border rounded bg-light">
                                <h6 class="mb-3">Questão <?php echo ($index + 1); ?>: <?php echo sanitizarString($questao['pergunta']); ?></h6>
                                <?php 
                                $opcoes = json_decode($questao['opcoes'], true);
                                $letras = ['a', 'b', 'c', 'd', 'e'];
                                $idx = 0;
                                foreach ($opcoes as $opcao): 
                                    if ($idx >= 5) break;
                                ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="respostas[<?php echo $questao['id']; ?>]" 
                                               id="opcao_<?php echo $letras[$idx] . "_" . $questao['id']; ?>" 
                                               value="<?php echo $letras[$idx]; ?>" <?php echo $idx === 0 ? 'required' : ''; ?>>
                                        <label class="form-check-label" for="opcao_<?php echo $letras[$idx] . "_" . $questao['id']; ?>">
                                            <?php echo sanitizarString($opcao); ?>
                                        </label>
                                    </div>
                                <?php $idx++; endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">Enviar Respostas</button>
                            <a href="provas.php" class="btn btn-secondary btn-lg ms-2">Voltar</a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($prova_selecionada && $tempo_restante_prova > 0): ?>
    <script>
    let tempoRestante = <?php echo $tempo_restante_prova; ?>;
    const timerElement = document.getElementById('tempo');
    const formElement = document.getElementById('provaForm');

    function atualizarTimer() {
        if (tempoRestante <= 0) {
            timerElement.innerHTML = '00:00 (<strong><?php echo count($questoes); ?></strong> questões)';
            formElement.submit();
            return;
        }
        const minutos = Math.floor(tempoRestante / 60);
        const segundos = tempoRestante % 60;
        timerElement.innerHTML = `${minutos.toString().padStart(2, '0')}:${segundos.toString().padStart(2, '0')} (<strong><?php echo count($questoes); ?></strong> questões)`;
        tempoRestante--;
    }

    setInterval(atualizarTimer, 1000);
    atualizarTimer();
    </script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>