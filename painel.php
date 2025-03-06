<?php
session_start();
require_once 'config/database.php';
require_once 'includes/seguranca.php';
require_once 'includes/functions.php';
$page_title = "Painel - Certificação Fórmula PetShop";
require_once 'includes/header.php';
verificarLogin();

$usuario_id = $_SESSION['usuario_id'];
$usuario = getUsuarioInfo($db, $usuario_id);
$resultado_nome = atualizarNomeUsuario($db, $usuario_id);
$estatisticas = getEstatisticasUsuario($db, $usuario_id);
$ultimas_provas = getUltimasProvas($db, $usuario_id);
$posicao_ranking = getPosicaoRanking($db, $usuario_id, $estatisticas['total_pontuacao'] ?? 0);
$provas = getStatusProvas($db, $usuario_id);
$nome_preenchido = !empty($usuario['nome']);

if (!$nome_preenchido) ob_start();
?>

<?php if (!$nome_preenchido): ?>
    <div class="modal show d-block" tabindex="-1" style="background: rgba(0, 0, 0, 0.8);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg rounded-4" style="background: linear-gradient(135deg, #ffffff, #f8f9fa);">
                <div class="modal-header bg-danger text-white rounded-top-4">
                    <h5 class="modal-title">Preencha seu Nome e Sobrenome</h5>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted mb-4">Este é seu primeiro acesso. Para continuar, preencha seu nome e sobrenome completo.</p>
                    <?php if ($resultado_nome['mensagem']): ?>
                        <div class="alert alert-success alert-dismissible fade show animate__animated animate__bounceIn mb-3" role="alert">
                            <i class="fas fa-check-circle me-2"></i> <?php echo $resultado_nome['mensagem']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="width: 0.8rem; height: 0.8rem; opacity: 0.5;"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($resultado_nome['erro']): ?>
                        <div class="alert alert-danger alert-dismissible fade show animate__animated animate__shakeX mb-3" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $resultado_nome['erro']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="width: 0.8rem; height: 0.8rem; opacity: 0.5;"></button>
                        </div>
                    <?php endif; ?>
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo gerarCSRFToken(); ?>">
                        <div class="mb-4">
                            <label for="novo_nome" class="form-label fw-bold text-dark">Nome e Sobrenome</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0" style="background-color: #f8f9fa;">
                                    <i class="fas fa-user text-primary"></i>
                                </span>
                                <input type="text" class="form-control border-start-0 rounded-end-4" id="novo_nome" name="novo_nome" placeholder="Digite seu nome e sobrenome" required>
                                <div class="invalid-feedback">
                                    Por favor, insira seu nome e sobrenome completo.
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 rounded-pill py-3 animate__animated animate__pulse" style="background: linear-gradient(45deg, #3498db, #2980b9); border: none;">
                            <i class="fas fa-save me-2"></i> Salvar Nome
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="row justify-content-center min-vh-100 align-items-start" style="background: linear-gradient(135deg, #f0f4f8, #e0e7f0); padding-top: 2rem;">
        <div class="col-12 col-lg-10">
            <h1 class="display-4 text-center mb-5 text-dark fw-bold animate__animated animate__fadeInDown">Painel - Bem-vindo, <?php echo sanitizarString($usuario['nome']); ?>!</h1>

            <div class="row g-4">
                <!-- Card de Perfil (Vertical à Esquerda) -->
                <div class="col-md-3 animate__animated animate__fadeInLeft">
                    <div class="card shadow-sm border-0 rounded-3 h-100" style="background: #ffffff; transition: transform 0.3s ease, box-shadow 0.3s ease;">
                        <div class="card-header bg-primary rounded-top-3" style="background: #2c3e50;">
                            <h5 class="card-title mb-0 text-white"><i class="fas fa-user"></i> Perfil</h5>
                        </div>
                        <div class="card-body p-3">
                            <p class="card-text mb-2"><strong class="text-dark">Nome:</strong> <?php echo sanitizarString($usuario['nome']); ?></p>
                            <p class="card-text mb-2"><strong class="text-dark">Email:</strong> <?php echo sanitizarString($usuario['email']); ?></p>
                            <p class="card-text mb-2"><strong class="text-dark">Cadastrado em:</strong> <?php echo formatarData($usuario['data_cadastro']); ?></p>
                            <p class="card-text mb-3"><strong class="text-dark">Último login:</strong> <?php echo formatarData($usuario['ultimo_login']); ?></p>
                            <button class="btn btn-outline-primary w-100 rounded-2 py-2 animate__animated animate__pulse" data-bs-toggle="modal" data-bs-target="#editNameModal">
                                <i class="fas fa-edit me-2"></i> Editar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Card de Estatísticas (Horizontal à Direita) -->
                <div class="col-md-9 animate__animated animate__fadeInUp delay-1s">
                    <div class="card shadow-sm border-0 rounded-3 h-100" style="background: #ffffff; transition: transform 0.3s ease, box-shadow 0.3s ease;">
                        <div class="card-header bg-success rounded-top-3" style="background: #28a745;">
                            <h5 class="card-title mb-0 text-white"><i class="fas fa-chart-bar"></i> Estatísticas</h5>
                        </div>
                        <div class="card-body p-3">
                            <div class="row row-cols-1 row-cols-md-5 g-3">
                                <div class="col">
                                    <div class="card h-100 border-0 rounded-2 bg-light text-center" style="background: #f8f9fa;">
                                        <div class="card-body p-2">
                                            <h6 class="text-muted mb-1">Realizadas</h6>
                                            <p class="display-6 fw-bold text-dark"><?php echo $estatisticas['provas_realizadas'] ?? 0; ?></p>
                                            <i class="fas fa-check-circle text-success" style="font-size: 1.5rem;"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="card h-100 border-0 rounded-2 bg-light text-center" style="background: #f8f9fa;">
                                        <div class="card-body p-2">
                                            <h6 class="text-muted mb-1">Aprovadas</h6>
                                            <p class="display-6 fw-bold text-dark"><?php echo $estatisticas['provas_aprovadas'] ?? 0; ?></p>
                                            <i class="fas fa-trophy text-success" style="font-size: 1.5rem;"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="card h-100 border-0 rounded-2 bg-light text-center" style="background: #f8f9fa;">
                                        <div class="card-body p-2">
                                            <h6 class="text-muted mb-1">Média</h6>
                                            <p class="display-6 fw-bold text-dark"><?php echo number_format($estatísticas['media_pontuacao'] ?? 0, 1); ?>%</p>
                                            <i class="fas fa-chart-line text-info" style="font-size: 1.5rem;"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="card h-100 border-0 rounded-2 bg-light text-center" style="background: #f8f9fa;">
                                        <div class="card-body p-2">
                                            <h6 class="text-muted mb-1">Maior Pontuação</h6>
                                            <p class="display-6 fw-bold text-dark"><?php echo number_format($estatísticas['maior_pontuacao'] ?? 0, 1); ?>%</p>
                                            <i class="fas fa-star text-warning" style="font-size: 1.5rem;"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="card h-100 border-0 rounded-2 bg-light text-center" style="background: #f8f9fa;">
                                        <div class="card-body p-2">
                                            <h6 class="text-muted mb-1">Ranking</h6>
                                            <p class="display-6 fw-bold text-dark"><?php echo $posicao_ranking; ?><?php echo $posicao_ranking !== '-' ? 'º' : ''; ?></p>
                                            <i class="fas fa-trophy text-secondary" style="font-size: 1.5rem;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seção de Progresso -->
                <div class="col-12 mt-4 animate__animated animate__fadeInUp delay-2s">
                    <div class="card shadow-sm border-0 rounded-3" style="background: #ffffff; transition: transform 0.3s ease, box-shadow 0.3s ease;">
                        <div class="card-header bg-warning rounded-top-3" style="background: #f1c40f;">
                            <h5 class="card-title mb-0 text-white"><i class="fas fa-check-circle"></i> Progresso</h5>
                        </div>
                        <div class="card-body p-3">
                            <div class="row row-cols-1 row-cols-md-2 g-3">
                                <?php foreach ($provas as $i => $prova): 
                                    $status = $prova['pontuacao'] >= 70 ? 'bg-success' : ($prova['pontuacao'] > 0 ? 'bg-danger' : 'bg-secondary');
                                ?>
                                    <div class="col animate__animated animate__fadeIn">
                                        <div class="card h-100 border-0 rounded-2 bg-light" style="background: #f8f9fa;">
                                            <div class="card-body p-2">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="fw-bold text-dark">Prova <?php echo $i; ?></span>
                                                    <span class="text-muted"><?php echo number_format($prova['pontuacao'], 1); ?>%</span>
                                                </div>
                                                <div class="progress" style="height: 10px;">
                                                    <div class="progress-bar <?php echo $status; ?>" role="progressbar" style="width: <?php echo $prova['pontuacao']; ?>%;" aria-valuenow="<?php echo $prova['pontuacao']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <?php if ($prova['bloqueada']): ?>
                                                    <p class="text-muted mt-2 small">Bloqueada por: <?php echo $prova['tempo_restante']; ?></p>
                                                <?php else: ?>
                                                    <a href="provas.php?prova=<?php echo $i; ?>" class="btn btn-sm btn-outline-primary mt-2 rounded-2 <?php echo $prova['pontuacao'] >= 100 ? 'disabled' : ''; ?>">
                                                        <?php echo $prova['pontuacao'] >= 100 ? 'Concluída' : 'Fazer'; ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seção de Últimas Provas -->
                <div class="col-12 mt-4 animate__animated animate__fadeInUp delay-3s">
                    <div class="card shadow-sm border-0 rounded-3" style="background: #ffffff; transition: transform 0.3s ease, box-shadow 0.3s ease;">
                        <div class="card-header bg-secondary rounded-top-3" style="background: #7f8c8d;">
                            <h5 class="card-title mb-0 text-white"><i class="fas fa-history"></i> Últimas Provas</h5>
                        </div>
                        <div class="card-body p-3">
                            <?php if (empty($ultimas_provas)): ?>
                                <p class="text-center text-muted">Nenhuma prova realizada ainda.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead style="background-color: #7f8c8d; color: #ffffff;">
                                            <tr>
                                                <th class="py-2 px-3">Prova</th>
                                                <th class="py-2 px-3">Pontuação</th>
                                                <th class="py-2 px-3">Data</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($ultimas_provas as $prova): ?>
                                                <tr class="animate__animated animate__fadeIn" style="transition: background-color 0.3s ease;">
                                                    <td class="py-2 px-3 text-dark">Prova <?php echo $prova['prova_numero']; ?></td>
                                                    <td class="py-2 px-3 text-dark"><?php echo number_format($prova['pontuacao'], 1); ?>%</td>
                                                    <td class="py-2 px-3 text-dark"><?php echo formatarData($prova['data_realizacao']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editNameModal" tabindex="-1" aria-labelledby="editNameModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg rounded-4" style="background: linear-gradient(135deg, #ffffff, #f8f9fa);">
                <div class="modal-header bg-primary text-white rounded-top-4">
                    <h5 class="modal-title" id="editNameModalLabel">Editar Nome</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="width: 0.8rem; height: 0.8rem; opacity: 0.5;"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo gerarCSRFToken(); ?>">
                        <div class="mb-4">
                            <label for="novo_nome" class="form-label fw-bold text-dark">Novo Nome e Sobrenome</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0" style="background-color: #f8f9fa;">
                                    <i class="fas fa-user text-primary"></i>
                                </span>
                                <input type="text" class="form-control border-start-0 rounded-end-4" id="novo_nome" name="novo_nome" value="<?php echo sanitizarString($usuario['nome']); ?>" required placeholder="Digite seu nome e sobrenome">
                                <div class="invalid-feedback">
                                    Por favor, insira seu nome e sobrenome completo.
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill rounded-pill py-2 animate__animated animate__pulse" style="background: linear-gradient(45deg, #3498db, #2980b9); border: none;">
                                <i class="fas fa-save me-2"></i> Salvar
                            </button>
                            <button type="button" class="btn btn-secondary flex-fill rounded-pill py-2" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
if (!$nome_preenchido) {
    $output = ob_get_clean();
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Preencha Seu Nome</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"></head><body class="bg-light">' . $output . '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script></body></html>';
    exit;
} else {
    require_once 'includes/footer.php';
}
?>

<!-- Estilos personalizados -->
<style>
    .min-vh-100 {
        min-height: 100vh;
    }
    .card, .modal-content {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .card:hover, .modal-content:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
    }
    .progress-bar {
        transition: width 0.5s ease-in-out;
    }
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
        transition: background-color 0.3s ease;
    }
    .animate__fadeInLeft, .animate__fadeInUp, .animate__fadeInRight {
        animation-duration: 1s;
    }
    .delay-1s { animation-delay: 0.5s; }
    .delay-2s { animation-delay: 1s; }
    .delay-3s { animation-delay: 1.5s; }
    .alert-dismissible .btn-close:hover {
        opacity: 0.8;
    }
</style>