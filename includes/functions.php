<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/seguranca.php';

/**
 * Verifica se o usuário está logado, redirecionando para login se não estiver
 */
function verificarLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: login.php");
        exit;
    }
}

/**
 * Verifica se o usuário é superadmin, redirecionando para painel se não for
 */
function verificarSuperadmin() {
    if (!isset($_SESSION['superadmin']) || !$_SESSION['superadmin']) {
        header("Location: painel.php");
        exit;
    }
}

/**
 * Verifica se o usuário é superadmin
 */
function isSuperadmin($db, $usuario_id) {
    $stmt = $db->prepare("SELECT is_superadmin FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch();
    return $usuario && $usuario['is_superadmin'] == 1;
}

/**
 * Realiza o login do usuário
 */
function processarLogin($db) {
    $mensagem = '';
    $erro = '';
    $ip = $_SERVER['REMOTE_ADDR'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && verificarCSRFToken($_POST['csrf_token'])) {
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);

        if (verificarTentativasLogin($db, $ip)) {
            $erro = "Muitas tentativas de login. Tente novamente em uma hora.";
        } elseif (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $stmt = $db->prepare("SELECT id, is_superadmin FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();

            if ($usuario) {
                $usuario_id = $usuario['id'];
                $stmt = $db->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?");
                $stmt->execute([$usuario_id]);
                registrarTentativaLogin($db, $ip, true);

                $_SESSION['usuario_id'] = $usuario_id;
                $_SESSION['email'] = $email;
                $_SESSION['superadmin'] = $usuario['is_superadmin'] == 1;

                $stmt = $db->prepare("SELECT nome FROM usuarios WHERE id = ?");
                $stmt->execute([$usuario_id]);
                $nome = $stmt->fetchColumn();
                $_SESSION['nome_preenchido'] = !empty($nome);

                $mensagem = "Login realizado com sucesso!";
                header("Location: painel.php");
                exit;
            } else {
                $erro = "Email não cadastrado. Contate o administrador.";
                registrarTentativaLogin($db, $ip, false);
            }
        } else {
            $erro = "Por favor, insira um email válido.";
            registrarTentativaLogin($db, $ip, false);
        }
    }
    return ['mensagem' => $mensagem, 'erro' => $erro];
}

/**
 * Obtém o total de usuários cadastrados
 */
function getTotalUsuarios($db) {
    $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios");
    return $stmt->fetch()['total'];
}

/**
 * Obtém informações do usuário logado
 */
function getUsuarioInfo($db, $usuario_id) {
    $stmt = $db->prepare("SELECT nome, email, data_cadastro, ultimo_login FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    return $stmt->fetch();
}

/**
 * Atualiza o nome do usuário
 */
function atualizarNomeUsuario($db, $usuario_id) {
    $mensagem = '';
    $erro = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['novo_nome']) && verificarCSRFToken($_POST['csrf_token'])) {
        $novo_nome = sanitizarString(trim($_POST['novo_nome']));
        if ($novo_nome) {
            $stmt = $db->prepare("UPDATE usuarios SET nome = ? WHERE id = ?");
            $stmt->execute([$novo_nome, $usuario_id]);
            $mensagem = "Nome atualizado com sucesso!";
            $_SESSION['nome_preenchido'] = true;
            header("Location: painel.php");
            exit;
        } else {
            $erro = "Por favor, insira um nome válido.";
        }
    }
    return ['mensagem' => $mensagem, 'erro' => $erro];
}

/**
 * Obtém estatísticas do usuário
 */
function getEstatisticasUsuario($db, $usuario_id) {
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as provas_realizadas,
            SUM(CASE WHEN pontuacao >= 70 THEN 1 ELSE 0 END) as provas_aprovadas,
            AVG(pontuacao) as media_pontuacao,
            MAX(pontuacao) as maior_pontuacao,
            SUM(pontuacao) as total_pontuacao
        FROM provas 
        WHERE usuario_id = ?
    ");
    $stmt->execute([$usuario_id]);
    return $stmt->fetch();
}

/**
 * Obtém as últimas provas do usuário
 */
function getUltimasProvas($db, $usuario_id) {
    $stmt = $db->prepare("
        SELECT prova_numero, pontuacao, data_realizacao 
        FROM provas 
        WHERE usuario_id = ? 
        ORDER BY data_realizacao DESC 
        LIMIT 5
    ");
    $stmt->execute([$usuario_id]);
    return $stmt->fetchAll();
}

/**
 * Calcula a posição do usuário no ranking
 */
function getPosicaoRanking($db, $usuario_id, $total_pontuacao) {
    $posicao_ranking = '-';
    if ($total_pontuacao > 0) {
        $stmt = $db->prepare("
            SELECT COUNT(*) + 1 as posicao 
            FROM (
                SELECT u.id, SUM(p.pontuacao) as total 
                FROM usuarios u 
                LEFT JOIN provas p ON u.id = p.usuario_id 
                WHERE u.nome IS NOT NULL
                GROUP BY u.id 
                HAVING total > ? AND total IS NOT NULL
            ) as ranking
        ");
        $stmt->execute([$total_pontuacao]);
        $posicao_ranking = $stmt->fetch()['posicao'];
    }
    return $posicao_ranking;
}

/**
 * Obtém o status de todas as provas do usuário
 */
function getStatusProvas($db, $usuario_id) {
    $provas = [];
    for ($i = 1; $i <= 4; $i++) {
        $prova_status = getProvaStatus($db, $usuario_id, $i);
        $provas[$i] = [
            'pontuacao' => $prova_status['pontuacao'] ?? 0,
            'data_ultima' => $prova_status['data_realizacao'] ?? null,
            'bloqueada' => isProvaBloqueada($prova_status['data_realizacao'] ?? null),
            'tempo_restante' => isProvaBloqueada($prova_status['data_realizacao'] ?? null) ? calcularTempoRestante($prova_status['data_realizacao']) : ''
        ];
    }
    return $provas;
}

/**
 * Obtém o status de uma prova específica para um usuário
 */
function getProvaStatus($db, $usuario_id, $prova_numero) {
    $stmt = $db->prepare("
        SELECT pontuacao, data_realizacao, tentativas 
        FROM provas 
        WHERE usuario_id = ? AND prova_numero = ? 
        ORDER BY data_realizacao DESC 
        LIMIT 1
    ");
    $stmt->execute([$usuario_id, $prova_numero]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Verifica se uma prova está bloqueada por tempo (24 horas)
 */
function isProvaBloqueada($dataUltimaTentativa) {
    if (!$dataUltimaTentativa) return false;
    $diferenca = (strtotime($dataUltimaTentativa) + 24*3600) - time();
    return $diferenca > 0;
}

/**
 * Calcula o tempo restante até que uma nova tentativa de prova seja permitida (24 horas)
 */
function calcularTempoRestante($dataUltimaTentativa) {
    if (!$dataUltimaTentativa) return false;
    $diferenca = (strtotime($dataUltimaTentativa) + 24*3600) - time();
    if ($diferenca <= 0) return false;
    $horas = floor($diferenca / 3600);
    $minutos = floor(($diferenca % 3600) / 60);
    return "$horas horas e $minutos minutos";
}

/**
 * Obtém os certificados do usuário
 */
function getCertificados($db, $usuario_id) {
    $stmt = $db->prepare("
        SELECT p.prova_numero, p.pontuacao, p.data_realizacao, c.token 
        FROM provas p 
        LEFT JOIN certificados c ON p.id = c.prova_id 
        WHERE p.usuario_id = ? AND p.pontuacao >= 70
    ");
    $stmt->execute([$usuario_id]);
    return $stmt->fetchAll();
}

/**
 * Obtém o ranking geral
 */
function getRanking($db) {
    $stmt = $db->prepare("
        SELECT u.nome, SUM(p.pontuacao) as total_pontuacao
        FROM usuarios u
        LEFT JOIN provas p ON u.id = p.usuario_id
        WHERE u.nome IS NOT NULL
        GROUP BY u.id, u.nome
        ORDER BY total_pontuacao DESC
        LIMIT 10
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Valida um certificado
 */
function validarCertificado($db, $requireCSRF = true) {
    $mensagem = '';
    $erro = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($requireCSRF && !verificarCSRFToken($_POST['csrf_token'] ?? '')) {
            $erro = "Erro de segurança. Tente novamente.";
        } else {
            $codigo = sanitizarString(trim($_POST['codigo'] ?? ''));
            if (empty($codigo)) {
                $erro = "Por favor, insira um código de certificado válido.";
            } else {
                try {
                    // Verifique a conexão com o banco
                    if (!$db) {
                        throw new Exception("Erro na conexão com o banco de dados.");
                    }

                    // Query para validar o certificado
                    $stmt = $db->prepare("
                        SELECT u.nome, p.prova_numero, p.pontuacao, c.data_emissao 
                        FROM certificados c
                        JOIN provas p ON c.prova_id = p.id
                        JOIN usuarios u ON c.usuario_id = u.id
                        WHERE c.token = ?
                    ");
                    $stmt->execute([$codigo]);
                    $certificado = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($certificado) {
                        $mensagem = "Certificado válido para " . sanitizarString($certificado['nome']) . ", Prova " . $certificado['prova_numero'] . 
                                    " com " . number_format($certificado['pontuacao'], 1) . "% em " . formatarData($certificado['data_emissao']) . ".";
                    } else {
                        $erro = "Código de certificado inválido.";
                    }
                } catch (Exception $e) {
                    $erro = "Erro ao validar o certificado: " . $e->getMessage();
                    // error_log("Erro SQL: " . $e->getMessage()); // Log para depuração, remova em produção
                }
            }
        }
    }
    return ['mensagem' => $mensagem, 'erro' => $erro];
}

/**
 * Obtém todos os usuários (para superadmin)
 */
function getTodosUsuarios($db) {
    $stmt = $db->prepare("SELECT id, nome, email, data_cadastro FROM usuarios ORDER BY data_cadastro DESC");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Registra uma tentativa de prova no banco de dados
 */
function registrarProva($db, $usuario_id, $prova_numero, $pontuacao) {
    $stmt = $db->prepare("
        SELECT tentativas, pontuacao 
        FROM provas 
        WHERE usuario_id = ? AND prova_numero = ? 
        ORDER BY data_realizacao DESC 
        LIMIT 1
    ");
    $stmt->execute([$usuario_id, $prova_numero]);
    $ultima_prova = $stmt->fetch();
    
    $tentativas = $ultima_prova ? $ultima_prova['tentativas'] + 1 : 1;
    $pontuacao_final = ($ultima_prova && $pontuacao < $ultima_prova['pontuacao']) 
        ? $ultima_prova['pontuacao'] 
        : $pontuacao;
    
    $stmt = $db->prepare("
        INSERT INTO provas (usuario_id, prova_numero, pontuacao, data_realizacao, tentativas) 
        VALUES (?, ?, ?, NOW(), ?) 
        ON DUPLICATE KEY UPDATE 
            pontuacao = ?, 
            data_realizacao = NOW(), 
            tentativas = ?
    ");
    $stmt->execute([$usuario_id, $prova_numero, $pontuacao_final, $tentativas, $pontuacao_final, $tentativas]);
    
    return $db->lastInsertId();
}

/**
 * Gera um certificado para uma prova aprovada
 */
function gerarCertificado($db, $usuario_id, $prova_id) {
    $token = gerarToken();
    $stmt = $db->prepare("
        INSERT INTO certificados (usuario_id, prova_id, token, data_emissao) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$usuario_id, $prova_id, $token]);
    return $token;
}

/**
 * Processa a realização de uma prova
 */
function processarProva($db, $usuario_id, $prova_numero, &$tempo_restante_prova) {
    $mensagem = '';
    $erro = '';
    $resultado = null;
    $prova_status = getProvaStatus($db, $usuario_id, $prova_numero);
    $bloqueada = isProvaBloqueada($prova_status['data_realizacao'] ?? null);
    $tempo_restante = $bloqueada ? calcularTempoRestante($prova_status['data_realizacao']) : '';
    $tempo_restante_prova = null;

    if (!$bloqueada) {
        if (isset($_SESSION['prova_iniciada'][$prova_numero])) {
            $tempo_decorrido = time() - $_SESSION['prova_iniciada'][$prova_numero];
            $tempo_restante_prova = max(0, 1800 - $tempo_decorrido);
            if ($tempo_restante_prova <= 0) {
                $erro = "Tempo esgotado para a Prova $prova_numero.";
                unset($_SESSION['prova_iniciada'][$prova_numero]);
                return ['mensagem' => $mensagem, 'erro' => $erro, 'resultado' => $resultado, 'bloqueada' => $bloqueada, 'tempo_restante' => $tempo_restante, 'prova_status' => $prova_status, 'tempo_restante_prova' => 0];
            }
        }
    }

    return [
        'mensagem' => $mensagem,
        'erro' => $erro,
        'resultado' => $resultado,
        'bloqueada' => $bloqueada,
        'tempo_restante' => $tempo_restante,
        'prova_status' => $prova_status,
        'tempo_restante_prova' => $tempo_restante_prova
    ];
}

/**
 * Obtém as questões de uma prova
 */
function getQuestoesProva($db, $prova_numero) {
    $stmt = $db->prepare("SELECT id, pergunta, opcoes, resposta FROM questoes WHERE prova_numero = ? LIMIT 20");
    $stmt->execute([$prova_numero]);
    return $stmt->fetchAll();
}

/**
 * Adiciona uma nova questão ao banco de dados
 */
function adicionarQuestao($db, $prova_numero, $pergunta, $opcoes, $resposta) {
    $opcoes_json = json_encode($opcoes);
    $stmt = $db->prepare("INSERT INTO questoes (prova_numero, pergunta, opcoes, resposta) VALUES (?, ?, ?, ?)");
    $stmt->execute([$prova_numero, $pergunta, $opcoes_json, $resposta]);
    return $db->lastInsertId();
}

/**
 * Edita uma questão existente no banco de dados
 */
function editarQuestao($db, $id, $pergunta, $opcoes, $resposta) {
    $opcoes_json = json_encode($opcoes);
    $stmt = $db->prepare("UPDATE questoes SET pergunta = ?, opcoes = ?, resposta = ? WHERE id = ?");
    $stmt->execute([$pergunta, $opcoes_json, $resposta, $id]);
    return $stmt->rowCount();
}

/**
 * Exclui uma questão do banco de dados
 */
function excluirQuestao($db, $id) {
    $stmt = $db->prepare("DELETE FROM questoes WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->rowCount();
}

/**
 * Obtém todas as questões de uma prova específica para o superadmin
 */
function getQuestoesPorProva($db, $prova_numero) {
    $stmt = $db->prepare("SELECT id, pergunta, opcoes, resposta FROM questoes WHERE prova_numero = ?");
    $stmt->execute([$prova_numero]);
    return $stmt->fetchAll();
}

/**
 * Verifica tentativas de login por IP
 */
function verificarTentativasLogin($db, $ip, $limite = 5, $tempo = 3600) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND timestamp > NOW() - INTERVAL ? SECOND");
    $stmt->execute([$ip, $tempo]);
    return $stmt->fetchColumn() >= $limite;
}

/**
 * Registra uma tentativa de login
 */
function registrarTentativaLogin($db, $ip, $sucesso) {
    $stmt = $db->prepare("INSERT INTO login_attempts (ip, sucesso, timestamp) VALUES (?, ?, NOW())");
    $stmt->execute([$ip, $sucesso ? 1 : 0]);
}

/**
 * Formata uma data MySQL para o formato brasileiro
 */
function formatarData($data) {
    return $data ? date('d/m/Y H:i', strtotime($data)) : 'Não logado';
}

/**
 * Sanitiza uma string para exibição segura
 */
function sanitizarString($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Gera um token único para certificados
 */
function gerarToken() {
    return bin2hex(random_bytes(16));
}
?>