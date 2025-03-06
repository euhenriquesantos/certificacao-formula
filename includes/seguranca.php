<?php
/**
 * Gera um token CSRF para proteção contra ataques
 * @return string Token CSRF armazenado na sessão
 */
function gerarCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica se o token CSRF enviado é válido
 * @param string $token Token recebido do formulário
 * @return bool True se válido, false caso contrário
 */
function verificarCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>