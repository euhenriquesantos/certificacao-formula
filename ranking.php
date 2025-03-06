<?php
session_start();
require_once 'config/database.php';
require_once 'includes/seguranca.php';
require_once 'includes/functions.php';
$page_title = "Ranking - Certificação Fórmula PetShop";
require_once 'includes/header.php';
verificarLogin();

$ranking = getRanking($db);
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-10">
        <h2 class="mb-5 text-center display-4 fw-bold text-dark animate__animated animate__fadeInDown">Ranking Geral</h2>
        
        <?php if (empty($ranking)): ?>
            <div class="alert alert-info text-center animate__animated animate__fadeIn" role="alert" style="background-color: #cce5ff; border-color: #b8daff;">
                <i class="fas fa-info-circle me-2"></i> Nenhum dado disponível no ranking ainda. Continue participando para aparecer aqui!
            </div>
        <?php else: ?>
            <div class="card shadow-lg border-0 rounded-4 animate__animated animate__fadeInUp" style="background-color: #ffffff;">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="rankingTable">
                            <thead style="background-color: #2c3e50; color: #ffffff;">
                                <tr>
                                    <th scope="col" class="py-3 px-4 clickable" onclick="sortTable(0)">Posição <i class="fas fa-sort ms-1"></i></th>
                                    <th scope="col" class="py-3 px-4 clickable" onclick="sortTable(1)">Nome <i class="fas fa-sort ms-1"></i></th>
                                    <th scope="col" class="py-3 px-4 clickable" onclick="sortTable(2)">Pontuação <i class="fas fa-sort ms-1"></i></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ranking as $index => $jogador): ?>
                                    <tr class="ranking-row <?php echo $index < 3 ? 'top-rank' : ''; ?>" data-index="<?php echo $index; ?>" style="background-color: <?php echo $index < 3 ? '#fff8e1' : '#f8f9fa'; ?>;">
                                        <td class="py-3 px-4 position-cell">
                                            <span class="badge rounded-pill fw-bold fs-6" style="background-color: <?php echo $index === 0 ? '#ffd700' : ($index === 1 ? '#c0c0c0' : ($index === 2 ? '#cd7f32' : '#6c757d')); ?>; color: #ffffff;">
                                                <?php 
                                                if ($index === 0) echo '<i class="fas fa-medal text-dark me-1"></i>';
                                                elseif ($index === 1) echo '<i class="fas fa-medal text-dark me-1"></i>';
                                                elseif ($index === 2) echo '<i class="fas fa-medal text-dark me-1"></i>';
                                                echo ($index + 1) . 'º';
                                                ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-dark"><?php echo sanitizarString($jogador['nome']); ?></td>
                                        <td class="py-3 px-4">
                                            <div class="progress" style="height: 20px; background-color: #e9ecef;">
                                                <div class="progress-bar" 
                                                     role="progressbar" 
                                                     style="width: <?php echo $jogador['total_pontuacao']; ?>%; background-color: <?php echo $index === 0 ? '#ffd700' : ($index === 1 ? '#c0c0c0' : ($index === 2 ? '#cd7f32' : '#17a2b8')); ?>; color: #ffffff;" 
                                                     aria-valuenow="<?php echo $jogador['total_pontuacao']; ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    <?php echo number_format($jogador['total_pontuacao'], 1); ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Estilos personalizados -->
<style>
    .ranking-row {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .ranking-row:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
    }
    .top-rank {
        background-color: #fff8e1 !important;
    }
    .clickable {
        cursor: pointer;
        user-select: none;
    }
    .progress-bar {
        transition: width 0.5s ease-in-out;
        font-weight: bold;
        color: #ffffff;
    }
    .table thead th {
        border: none;
        background-color: #2c3e50;
        color: #ffffff;
    }
    .table tbody tr td {
        border: none;
    }
    .badge {
        padding: 6px 12px;
    }
</style>

<!-- Script para ordenação da tabela -->
<script>
    function sortTable(columnIndex) {
        const table = document.getElementById("rankingTable");
        let rows, switching = true, i, shouldSwitch, dir = "asc", switchCount = 0;
        while (switching) {
            switching = false;
            rows = table.rows;
            for (i = 1; i < (rows.length - 1); i++) {
                shouldSwitch = false;
                const x = rows[i].getElementsByTagName("TD")[columnIndex];
                const y = rows[i + 1].getElementsByTagName("TD")[columnIndex];
                let xValue = columnIndex === 0 ? parseInt(x.querySelector('.badge').textContent) :
                            columnIndex === 2 ? parseFloat(x.querySelector('.progress-bar').textContent) :
                            x.textContent.toLowerCase();
                let yValue = columnIndex === 0 ? parseInt(y.querySelector('.badge').textContent) :
                            columnIndex === 2 ? parseFloat(y.querySelector('.progress-bar').textContent) :
                            y.textContent.toLowerCase();

                if (dir === "asc" && xValue > yValue) {
                    shouldSwitch = true;
                    break;
                } else if (dir === "desc" && xValue < yValue) {
                    shouldSwitch = true;
                    break;
                }
            }
            if (shouldSwitch) {
                rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                switching = true;
                switchCount++;
            } else if (switchCount === 0 && dir === "asc") {
                dir = "desc";
                switching = true;
            }
        }
        updatePositions();
    }

    function updatePositions() {
        const rows = document.querySelectorAll('.ranking-row');
        rows.forEach((row, index) => {
            const positionCell = row.querySelector('.position-cell .badge');
            positionCell.style.backgroundColor = index === 0 ? '#ffd700' : (index === 1 ? '#c0c0c0' : (index === 2 ? '#cd7f32' : '#6c757d'));
            positionCell.style.color = '#ffffff';
            positionCell.innerHTML = (index === 0 ? '<i class="fas fa-medal text-dark me-1"></i>' :
                                     index === 1 ? '<i class="fas fa-medal text-dark me-1"></i>' :
                                     index === 2 ? '<i class="fas fa-medal text-dark me-1"></i>' : '') +
                                     (index + 1) + 'º';
            row.classList.toggle('top-rank', index < 3);
            row.style.backgroundColor = index < 3 ? '#fff8e1' : '#f8f9fa';
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        const progressBars = document.querySelectorAll('.progress-bar');
        progressBars.forEach(bar => {
            const width = bar.getAttribute('aria-valuenow');
            bar.style.width = '0%';
            setTimeout(() => bar.style.width = `${width}%`, 100);
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>