<?php
// Requer apenas as funções e a conexão com o banco
require_once 'config/database.php';
require_once 'includes/functions.php'; // Inclui a função validarCertificado

$page_title = "Validar Certificado - Certificação Fórmula PetShop";

// Inicialize as variáveis para evitar avisos de undefined
$mensagem = '';
$erro = '';
$certificado = null;

// Use a função existente com $requireCSRF = false para contextos públicos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = validarCertificado($db, false); // Desativa a verificação de CSRF
    $mensagem = $resultado['mensagem'];
    $erro = $resultado['erro'];

    // Se houver mensagem de sucesso, extrair os dados do certificado
    if ($mensagem) {
        // Extrair dados do certificado a partir da mensagem (pode ser ajustado para melhor precisão, se necessário)
        preg_match('/para (.+?), Prova (\d+) com (\d+\.\d+)% em (.+?)\./', $mensagem, $matches);
        if (count($matches) === 5) {
            $certificado = [
                'nome' => $matches[1],
                'prova_numero' => $matches[2],
                'pontuacao' => $matches[3],
                'data_emissao' => $matches[4]
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
            font-family: 'Arial', sans-serif;
        }
        .validate-container {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            padding: 2rem;
            width: 100%;
            max-width: 500px; /* Aumentado para acomodar o certificado */
            text-align: center;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 1.5rem;
        }
        .validate-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .form-label {
            font-weight: bold;
            color: #2c3e50;
        }
        .form-control {
            border-radius: 12px;
            border: 2px solid #e0e0e0;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            background: #ffffff;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
            outline: none;
        }
        .btn-primary {
            background: #3498db;
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: bold;
            color: #ffffff;
            width: 100%;
            font-size: 1rem;
        }
        .btn-primary:hover {
            background: #2980b9;
            transform: scale(1.05);
            transition: transform 0.3s ease, background 0.3s ease;
        }
        .success-feedback, .certificate-display {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            border: 2px solid #a5d6a7;
            border-radius: 12px;
            padding: 1.5rem;
            color: #2e7d32;
            font-size: 1rem;
            margin-bottom: 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.2);
        }
        .success-feedback i, .certificate-display i {
            margin-bottom: 1rem;
            font-size: 2rem;
        }
        .certificate-display h3 {
            font-size: 1.25rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        .certificate-detail {
            font-size: 1rem;
            color: #2e7d32;
            margin: 0.5rem 0;
            line-height: 1.5;
        }
        .error-feedback {
            background: #ffebee;
            border: 2px solid #ffcdd2;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            color: #d32f2f;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-feedback i {
            margin-right: 0.5rem;
            font-size: 1rem;
        }
        .animate__fadeInUp {
            animation-duration: 1s;
        }
    </style>
</head>
<body>
    <div class="validate-container animate__animated animate__fadeInUp">
        <img src="/sites/formula/assets/images/logo.png" alt="Fórmula PetShop" class="logo">
        <h2 class="validate-title">Validar Certificado</h2>
        
        <?php if ($mensagem && $certificado): ?>
            <div class="certificate-display">
                <i class="fas fa-certificate text-success"></i>
                <h3>Certificado Válido</h3>
                <div class="certificate-detail">
                    <strong>Nome:</strong> <?php echo sanitizarString($certificado['nome']); ?>
                </div>
                <div class="certificate-detail">
                    <strong>Prova:</strong> Prova <?php echo $certificado['prova_numero']; ?>
                </div>
                <div class="certificate-detail">
                    <strong>Pontuação:</strong> <?php echo number_format($certificado['pontuacao'], 1); ?>%
                </div>
                <div class="certificate-detail">
                    <strong>Data de Emissão:</strong> <?php echo formatarData($certificado['data_emissao']); ?>
                </div>
            </div>
        <?php elseif ($erro): ?>
            <div class="error-feedback">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $erro; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$mensagem && !$erro): ?>
        <form method="POST" class="needs-validation" novalidate>
            <div class="mb-4">
                <input type="text" class="form-control" id="codigo" name="codigo" required placeholder="Digite o código do certificado">
                <div class="invalid-feedback">
                    Por favor, insira um código válido.
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search me-2"></i> Validar
            </button>
        </form>
        <?php endif; ?>
    </div>

    <!-- Validação do formulário -->
    <script>
        (function () {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>