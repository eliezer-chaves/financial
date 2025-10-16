<?php
// index.php da Hostinger
//DEBUG: Descomente as linhas abaixo para verificar o que está acontecendo
// error_log("REQUEST_URI: " . $_SERVER['REQUEST_URI']);
// error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);

$folder_name = 'login-project';
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$clean_uri   = strtok($request_uri, '?');

// Caminho para o Laravel
$laravel_path = $_SERVER['DOCUMENT_ROOT'] . '/../' . $folder_name . '/public/index.php';

/**
 * Redireciona para o Laravel
 */
function runLaravel($laravel_path, $request_uri) {
    // Preserva as variáveis originais
    $_SERVER['SCRIPT_FILENAME'] = $laravel_path;
    $_SERVER['SCRIPT_NAME']     = '/index.php';
    
    // Não modifica REQUEST_URI - Laravel precisa da URL completa
    
    if (!file_exists($laravel_path)) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Laravel não encontrado',
            'path' => $laravel_path,
            'document_root' => $_SERVER['DOCUMENT_ROOT']
        ]);
        exit;
    }
    
    // Limpa o buffer de saída
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Executa o Laravel
    require $laravel_path;
    exit;
}

// ============================================
// 1. ROTAS API LARAVEL (Prioridade máxima)
// ============================================
if (strpos($clean_uri, '/api/') === 0) {
    // Headers CORS
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    
    // Preflight
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
    
    runLaravel($laravel_path, $request_uri);
}

// ============================================
// 2. LOG VIEWER LARAVEL
// ============================================
if (strpos($clean_uri, '/log-viewer') === 0) {
    runLaravel($laravel_path, $request_uri);
}

// ============================================
// 3. ARQUIVOS ESTÁTICOS DO ANGULAR
// ============================================
$file_path = __DIR__ . $clean_uri;

// Se for arquivo físico existente (JS, CSS, imagens, fontes, etc)
if (is_file($file_path)) {
    // Deixa o servidor servir o arquivo
    return false;
}

// ============================================
// 4. SPA ANGULAR (todas as outras rotas)
// ============================================
$angular_file = __DIR__ . '/index.html';

if (file_exists($angular_file)) {
    http_response_code(200);
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    readfile($angular_file);
    exit;
}

// ============================================
// 5. ERRO - INDEX.HTML NÃO ENCONTRADO
// ============================================
http_response_code(500);
echo '<h1>Erro: index.html do Angular não encontrado</h1>';
echo '<p>Caminho esperado: ' . htmlspecialchars($angular_file) . '</p>';
exit;