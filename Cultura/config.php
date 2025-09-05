<?php
// Configurações da base de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'patrimonio_cultural');
define('DB_USER', 'root'); // Altere conforme sua configuração
define('DB_PASS', '');     // Altere conforme sua configuração

// Configurações da aplicação
define('SITE_URL', 'http://localhost/patrimonio_cultural/');
define('SITE_TITLE', 'Sistema de Gestão de Património Cultural');

// Iniciar sessão
session_start();

// Conectar à base de dados
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

// Função para verificar se o usuário está logado
function verificarLogin() {
    return isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_tipo']);
}

// Função para limpar dados de entrada
function limparInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Função para redirecionar
function redirecionar($url) {
    header("Location: " . $url);
    exit();
}
?>