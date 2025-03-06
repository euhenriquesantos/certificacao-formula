<?php
session_start();
require_once 'config/database.php';
require_once 'includes/seguranca.php';
require_once 'includes/functions.php';

$resultado = processarLogin($db);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Certificação Fórmula PetShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="/sites/formula/assets/css/custom.css?v=<?php echo time(); ?>">
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
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 1rem;
        }
        .login-title {
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
            border-radius: 8px;
            border-color: #e9ecef;
        }
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.5);
        }
        .btn-primary {
            background: linear-gradient(45deg, #3498db, #2980b9);
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: bold;
        }
        .btn-primary:hover {
            background: linear-gradient(45deg, #2980b9, #1f618d);
            transform: scale(1.05);
            transition: transform 0.3s ease, background 0.3s ease;
        }
        .alert {
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .alert-dismissible .btn-close {
            background: #ffffff;
            width: 0.8rem; /* Reduziu o tamanho do botão de fechar */
            height: 0.8rem;
            opacity: 0.5; /* Ajuste na opacidade para melhor visibilidade */
        }
        .alert-dismissible .btn-close:hover {
            opacity: 0.8; /* Aumenta opacidade ao passar o mouse */
        }
        .animate__bounceIn, .animate__shakeX, .animate__pulse {
            animation-duration: 0.8s;
        }
    </style>
</head>
<body>
    <div class="login-container animate__animated animate__fadeInUp">
        <img src="/sites/formula/assets/images/logo.png" alt="Fórmula PetShop" class="logo">
        <h2 class="login-title">Acesse sua Conta</h2>
        
        <?php if ($resultado['mensagem']): ?>
            <div class="alert alert-success alert-dismissible fade show animate__animated animate__bounceIn mb-3" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo $resultado['mensagem']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if ($resultado['erro']): ?>
            <div class="alert alert-danger alert-dismissible fade show animate__animated animate__shakeX mb-3" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $resultado['erro']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo gerarCSRFToken(); ?>">
            <div class="mb-4">
                <div class="input-group">
                    <span class="input-group-text bg-light border-0" style="background-color: #f8f9fa; border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
                        <i class="fas fa-envelope text-primary"></i>
                    </span>
                    <input type="email" class="form-control border-start-0 rounded-end-4" id="email" name="email" required placeholder="Digite seu email" aria-label="Email">
                    <div class="invalid-feedback">
                        Por favor, insira um email válido.
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 animate__animated animate__pulse">
                <i class="fas fa-sign-in-alt me-2"></i> Entrar
            </button>
            <p class="text-center mt-3 text-muted">
                <a href="index.php" class="text-primary fw-bold">Voltar ao início</a>
            </p>
        </form>
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