<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificação Fórmula PetShop - <?php echo $page_title ?? 'Início'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="/sites/formula/assets/css/custom.css?v=<?php echo time(); ?>">
    <style>
        body {
            min-height: 100vh; /* Altura mínima da viewport */
            display: flex;
            flex-direction: column;
            margin: 0;
        }
        main {
            flex: 1 0 auto; /* Faz o main ocupar o espaço disponível, empurrando o footer para baixo */
        }
        footer {
            flex-shrink: 0; /* Impede o footer de encolher */
            background-color: #2c3e50; /* Cinza escuro sólido */
            color: #ffffff;
            width: 100%;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid">
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <a class="navbar-brand" href="painel.php">
                        <img src="/sites/formula/assets/images/logo.png" alt="Fórmula PetShop" class="logo" style="max-height: 40px;">
                    </a>
                <?php else: ?>
                    <a class="navbar-brand" href="index.php">
                        <img src="/sites/formula/assets/images/logo.png" alt="Fórmula PetShop" class="logo" style="max-height: 40px;">
                    </a>
                <?php endif; ?>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <?php if (isset($_SESSION['usuario_id'])): ?>
                            <li class="nav-item"><a class="nav-link" href="painel.php">Painel</a></li>
                            <li class="nav-item"><a class="nav-link" href="provas.php">Provas</a></li>
                            <li class="nav-item"><a class="nav-link" href="certificados.php">Certificados</a></li>
                            <li class="nav-item"><a class="nav-link" href="ranking.php">Ranking</a></li>
                            <?php if (isset($_SESSION['superadmin']) && $_SESSION['superadmin']): ?>
                                <li class="nav-item"><a class="nav-link" href="superadmin.php">Superadmin</a></li>
                            <?php endif; ?>
                            <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Sair</a></li>
                        <?php else: ?>
                            <li class="nav-item"><a class="nav-link" href="index.php">Início</a></li>
                            <li class="nav-item"><a class="nav-link" href="login.php">Entrar</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <main class="container my-4">