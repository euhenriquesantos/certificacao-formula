<?php
ob_start();
ini_set('display_errors', 1); // Para desenvolvimento; em produção, mude para 0
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/functions.php';
require_once 'vendor/autoload.php';

class CertificadoGenerator {
    private $pdf;

    public function __construct() {
        $this->pdf = new FPDF('L', 'mm', [297, 210]); // A4 horizontal
    }

    public function gerarPDF($certificado, $token) {
        $this->pdf->AddPage();
        $this->applyBackground();
        $this->addHeader();
        $this->addBody($certificado);
        $this->addFooter($certificado, $token);
        return $this->pdf->Output('S'); // 'S' retorna como string
    }

    private function applyBackground() {
        $this->pdf->SetFillColor(245, 245, 220); // Fundo bege claro
        $this->pdf->Rect(0, 0, 297, 210, 'F');
    }

    private function addHeader() {
        $this->pdf->SetFont('Times', 'B', 32);
        $this->pdf->SetTextColor(139, 69, 19); // Marrom escuro
        $this->pdf->Cell(0, 20, $this->encodeText('CERTIFICADO DE CONCLUSÃO'), 0, 1, 'C');
    }

    private function addBody($certificado) {
        $this->pdf->SetFont('Times', 'B', 24);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Ln(20);
        $nome = $this->encodeText($certificado['nome'] ?? 'Nome não informado');
        $this->pdf->Cell(0, 15, $nome, 0, 1, 'C');

        $this->pdf->SetFont('Times', '', 16);
        $merito = $this->encodeText($this->obterMerito($certificado['prova_numero'] ?? 0));
        $provaNumero = $certificado['prova_numero'] ?? 'N/A';
        $texto = $this->encodeText("Certifica que o(a) acima mencionado(a) concluiu com sucesso a Prova {$provaNumero} do Curso Fórmula PetShop, recebendo o mérito de {$merito}.");
        $this->pdf->MultiCell(0, 10, $texto, 0, 'C');
    }

    private function addFooter($certificado, $token) {
        $this->pdf->SetFont('Times', '', 12);
        $this->pdf->Ln(10);
        $dataEmissao = isset($certificado['data_emissao']) ? formatarData($certificado['data_emissao']) : date('d/m/Y H:i');
        $this->pdf->Cell(0, 8, $this->encodeText("Data de Emissão: " . $dataEmissao), 0, 1, 'L');
        $pontuacao = isset($certificado['pontuacao']) ? number_format($certificado['pontuacao'], 2) : 'N/A';
        $this->pdf->Cell(0, 8, $this->encodeText("Pontuação: " . $pontuacao . "%"), 0, 1, 'L');
        $this->pdf->Cell(0, 8, $this->encodeText("Código de Validação: " . ($token ?? 'N/A')), 0, 1, 'L');
        $this->adicionarAssinaturas();
    }

    private function adicionarAssinaturas() {
        $this->pdf->SetFont('Times', 'B', 12);
        // Ajustado para subir a assinatura e reduzir o espaçamento
        $this->pdf->SetXY(84.5, 160); // Subiu de 170 para 160
        $this->pdf->Cell(128, 8, "_______________________________", 0, 0, 'C');
        $this->pdf->SetXY(84.5, 165); // Subiu de 175 para 165
        $this->pdf->Cell(128, 8, $this->encodeText("Ricardo de Oliveira"), 0, 0, 'C');
        $this->pdf->SetXY(84.5, 170); // Subiu de 180 para 170
        $this->pdf->Cell(128, 8, $this->encodeText("CEO, Fórmula PetShop"), 0, 1, 'C');
    }

    private function obterMerito($numero) {
        $meritos = [
            1 => 'Planejador Pet Shop',
            2 => 'Negociador Pet Shop',
            3 => 'Gestor Pet Shop',
            4 => 'Estrategista Pet Shop'
        ];
        return $meritos[$numero] ?? 'Participante';
    }

    private function encodeText($text) {
        return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
    }
}

// Verifica login e processa solicitação
verificarLogin();

// Limpa o buffer antes de processar
ob_clean();

if (!isset($_GET['token'])) {
    header("Location: certificados.php");
    exit;
}

$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);
if (!$token || strlen($token) !== 32 || !ctype_alnum($token)) {
    header("Location: certificados.php");
    exit;
}

global $db;
try {
    $stmt = $db->prepare("
        SELECT u.nome, u.email, p.prova_numero, p.pontuacao, c.data_emissao
        FROM certificados c
        JOIN usuarios u ON c.usuario_id = u.id
        JOIN provas p ON c.prova_id = p.id
        WHERE c.token = :token AND c.usuario_id = :usuario_id
    ");
    $stmt->execute(['token' => $token, 'usuario_id' => $_SESSION['usuario_id']]);
    $certificado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$certificado) {
        header("Location: certificados.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Erro no banco de dados: " . $e->getMessage());
    die("Erro ao consultar o banco de dados. Veja os logs para mais detalhes.");
}

// Gera e entrega o certificado
$generator = new CertificadoGenerator();
$pdfContent = $generator->gerarPDF($certificado, $token);

// Define cabeçalhos para forçar o download do PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="certificado_prova_' . $certificado['prova_numero'] . '.pdf"');
header('Content-Length: ' . strlen($pdfContent));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Garante que o buffer está limpo antes de enviar o PDF
ob_end_clean();

// Entrega o PDF
echo $pdfContent;
exit;