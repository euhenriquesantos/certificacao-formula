<?php
session_start();
require_once 'config/database.php';
require_once 'includes/seguranca.php';
require_once 'includes/functions.php';
$page_title = "Certificação Fórmula PetShop";
require_once 'includes/header.php';

$total_usuarios = getTotalUsuarios($db);
?>

<div class="row justify-content-center min-vh-100 align-items-center" style="background: linear-gradient(135deg, #f5f7fa, #c3cfe2);">
    <div class="col-12 col-lg-10">
        <div class="text-center mb-5 animate__animated animate__fadeInDown">
            <h1 class="display-3 fw-bold text-dark mb-3">Certificação Fórmula PetShop</h1>
            <p class="lead text-muted mb-4">Alcance o próximo nível na sua carreira com nosso programa de certificação exclusivo para profissionais do mercado pet.</p>
        </div>

        <div class="row row-cols-1 row-cols-md-3 g-4 mb-5">
            <div class="col animate__animated animate__fadeInLeft">
                <div class="card h-100 shadow-lg border-0 rounded-4" style="background: rgba(255, 255, 255, 0.95); transition: transform 0.3s ease, box-shadow 0.3s ease;">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon mb-4">
                            <img src="/sites/formula/assets/images/certificate.png" alt="Certificação" class="img-fluid" style="max-width: 80px; filter: drop-shadow(0 5px 5px rgba(0, 0, 0, 0.1));">
                        </div>
                        <h5 class="card-title fw-bold text-primary">Certificação</h5>
                        <p class="card-text text-muted">Conquiste um certificado reconhecido e valorize seu currículo.</p>
                    </div>
                </div>
            </div>
            <div class="col animate__animated animate__fadeInUp delay-1s">
                <div class="card h-100 shadow-lg border-0 rounded-4" style="background: rgba(255, 255, 255, 0.95); transition: transform 0.3s ease, box-shadow 0.3s ease;">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon mb-4">
                            <img src="/sites/formula/assets/images/trophy.png" alt="Ranking" class="img-fluid" style="max-width: 80px; filter: drop-shadow(0 5px 5px rgba(0, 0, 0, 0.1));">
                        </div>
                        <h5 class="card-title fw-bold text-success">Ranking</h5>
                        <p class="card-text text-muted">Destaque-se entre os melhores e ganhe visibilidade.</p>
                    </div>
                </div>
            </div>
            <div class="col animate__animated animate__fadeInRight delay-2s">
                <div class="card h-100 shadow-lg border-0 rounded-4" style="background: rgba(255, 255, 255, 0.95); transition: transform 0.3s ease, box-shadow 0.3s ease;">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon mb-4">
                            <img src="/sites/formula/assets/images/checklist.png" alt="Provas" class="img-fluid" style="max-width: 80px; filter: drop-shadow(0 5px 5px rgba(0, 0, 0, 0.1));">
                        </div>
                        <h5 class="card-title fw-bold text-info">Provas</h5>
                        <p class="card-text text-muted">Teste seus conhecimentos com nosso sistema prático.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center animate__animated animate__fadeInUp delay-3s">
            <p class="text-muted mb-4">Junte-se a <span class="fw-bold text-primary"><?php echo $total_usuarios; ?></span> usuários que já estão transformando suas carreiras!</p>
            <a href="login.php" class="btn btn-primary btn-lg rounded-pill px-5 py-3 animate__animated animate__pulse" style="background: linear-gradient(45deg, #3498db, #2980b9); border: none;">
                <i class="fas fa-sign-in-alt me-2"></i> Entrar Agora
            </a>
        </div>
    </div>
</div>

<!-- Estilos personalizados -->
<style>
    .min-vh-100 {
        min-height: 100vh;
    }
    .card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
    }
    .feature-icon img {
        transition: transform 0.3s ease;
    }
    .feature-icon img:hover {
        transform: scale(1.1);
    }
    .btn-primary:hover {
        background: linear-gradient(45deg, #2980b9, #1f618d);
        transform: scale(1.05);
        transition: transform 0.3s ease, background 0.3s ease;
    }
    .animate__fadeInLeft, .animate__fadeInUp, .animate__fadeInRight {
        animation-duration: 1s;
    }
    .delay-1s { animation-delay: 0.5s; }
    .delay-2s { animation-delay: 1s; }
    .delay-3s { animation-delay: 1.5s; }
</style>

<?php require_once 'includes/footer.php'; ?>