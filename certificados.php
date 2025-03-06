<?php
session_start();
require_once 'config/database.php';
require_once 'includes/seguranca.php';
require_once 'includes/functions.php';
$page_title = "Certificados - Certificação Fórmula PetShop";
require_once 'includes/header.php';
verificarLogin();

require_once '/Applications/XAMPP/xamppfiles/htdocs/sites/formula/vendor/autoload.php';

$usuario_id = $_SESSION['usuario_id'];
$certificados = getCertificados($db, $usuario_id);
$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);

if ($token) {
    $stmt = $db->prepare("SELECT c.token, p.prova_numero, p.pontuacao, p.data_realizacao, u.nome 
                          FROM certificados c 
                          JOIN provas p ON c.prova_id = p.id 
                          JOIN usuarios u ON c.usuario_id = u.id 
                          WHERE c.token = ? AND c.usuario_id = ?");
    $stmt->execute([$token, $usuario_id]);
    $certificado = $stmt->fetch();

    if ($certificado) {
        $dompdf = new \Dompdf\Dompdf();
        $html = "
            <h1 style='text-align: center;'>Certificado de Conclusão</h1>
            <p style='text-align: center;'>Certificamos que <strong>{$certificado['nome']}</strong> concluiu com sucesso a Prova {$certificado['prova_numero']}.</p>
            <p style='text-align: center;'>Pontuação: " . number_format($certificado['pontuacao'], 1) . "%</p>
            <p style='text-align: center;'>Data: " . formatarData($certificado['data_realizacao']) . "</p>
            <p style='text-align: center;'>Código do Certificado: {$certificado['token']}</p>
        ";
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream("certificado_prova_{$certificado['prova_numero']}_{$certificado['token']}.pdf", ["Attachment" => true]);
        exit;
    } else {
        $erro = "Certificado não encontrado ou inválido.";
    }
}
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4 text-center">Meus Certificados</h2>
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger text-center"><?php echo $erro; ?></div>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Prova</th>
                        <th>Pontuação</th>
                        <th>Data</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($certificados)): ?>
                        <tr>
                            <td colspan="4" class="text-center">Nenhum certificado disponível.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($certificados as $certificado): ?>
                            <tr>
                                <td>Prova <?php echo $certificado['prova_numero']; ?></td>
                                <td><?php echo number_format($certificado['pontuacao'], 1); ?>%</td>
                                <td><?php echo formatarData($certificado['data_realizacao']); ?></td>
                                <td>
                                    <?php if ($certificado['token']): ?>
                                        <a href="?token=<?php echo $certificado['token']; ?>" class="btn btn-sm btn-outline-primary">Baixar PDF</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>