<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'testeprovas');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8", DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Define o modo padrão para fetch
} catch(PDOException $e) {
    error_log("Erro de conexão com o banco: " . $e->getMessage());
    die("Erro de conexão com o banco de dados. Contate o administrador.");
}

date_default_timezone_set('America/Sao_Paulo');

// Criar índices para performance (se não existirem)
try {
    $db->exec("CREATE INDEX idx_usuario_id ON provas(usuario_id)");
    $db->exec("CREATE INDEX idx_prova_numero ON provas(prova_numero)");
    $db->exec("CREATE INDEX idx_prova_id ON certificados(prova_id)");
    $db->exec("CREATE INDEX idx_usuario_id_cert ON certificados(usuario_id)");
    $db->exec("CREATE INDEX idx_prova_numero_quest ON questoes(prova_numero)");
} catch (PDOException $e) {
    error_log("Índices já existem ou erro ao criar: " . $e->getMessage());
}
?>