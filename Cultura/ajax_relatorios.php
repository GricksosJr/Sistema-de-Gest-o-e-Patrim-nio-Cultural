<?php
include 'config.php';

// Verificar se está logado e é admin
if (!verificarLogin() || $_SESSION['usuario_tipo'] != 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'estatisticas_filtradas':
            echo json_encode(obterEstatisticasFiltradas());
            break;
            
        case 'relatorio_detalhado':
            echo json_encode(obterRelatorioDetalhado());
            break;
            
        case 'exportar_dados':
            echo json_encode(exportarDados());
            break;
            
        case 'grafico_temporal':
            echo json_encode(obterDadosTemporais());
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Ação não encontrada']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno: ' . $e->getMessage()]);
}

function obterEstatisticasFiltradas() {
    global $pdo;
    
    $periodo = $_GET['periodo'] ?? '30';
    $categoria = $_GET['categoria'] ?? '';
    $estado = $_GET['estado'] ?? '';
    $localizacao = $_GET['localizacao'] ?? '';
    
    // Construir WHERE clause baseado nos filtros (ajustado para JOINs)
    $where = ['1=1'];
    $params = [];
    
    if ($periodo != 'all') {
        $where[] = 'p.data_registo >= DATE_SUB(NOW(), INTERVAL ? DAY)';
        $params[] = intval($periodo);
    }
    
    if ($categoria) {
        $where[] = 'c.nome = ?';
        $params[] = $categoria;
    }
    
    if ($estado) {
        $where[] = 'p.estado_conservacao = ?';
        $params[] = $estado;
    }
    
    if ($localizacao) {
        $where[] = 'p.provincia LIKE ?';
        $params[] = '%' . $localizacao . '%';
    }
    
    $whereClause = implode(' AND ', $where);
    
    $stats = [];
    
    // Total filtrado
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM patrimonio p 
        LEFT JOIN categorias_patrimonio c ON p.categoria_id = c.id 
        WHERE $whereClause
    ");
    $stmt->execute($params);
    $stats['total_filtrado'] = $stmt->fetch()['total'];
    
    // Por categoria (filtrado)
    $stmt = $pdo->prepare("
        SELECT c.nome as categoria, COUNT(*) as total 
        FROM patrimonio p 
        LEFT JOIN categorias_patrimonio c ON p.categoria_id = c.id 
        WHERE $whereClause 
        GROUP BY c.nome 
        ORDER BY total DESC
    ");
    $stmt->execute($params);
    $stats['categorias'] = $stmt->fetchAll();
    
    // Por estado (filtrado)
    $stmt = $pdo->prepare("
        SELECT p.estado_conservacao, COUNT(*) as total 
        FROM patrimonio p 
        LEFT JOIN categorias_patrimonio c ON p.categoria_id = c.id 
        WHERE $whereClause 
        GROUP BY p.estado_conservacao
    ");
    $stmt->execute($params);
    $stats['estados'] = $stmt->fetchAll();
    
    // Por localização (filtrado)
    $stmt = $pdo->prepare("
        SELECT p.provincia, COUNT(*) as total 
        FROM patrimonio p 
        LEFT JOIN categorias_patrimonio c ON p.categoria_id = c.id 
        WHERE $whereClause AND p.provincia IS NOT NULL
        GROUP BY p.provincia 
        ORDER BY total DESC 
        LIMIT 10
    ");
    $stmt->execute($params);
    $stats['localizacoes'] = $stmt->fetchAll();
    
    return $stats;
}

function obterRelatorioDetalhado() {
    global $pdo;
    
    $tipo = $_GET['tipo'] ?? 'geral';
    
    switch ($tipo) {
        case 'conservacao':
            return relatorioConservacao();
        case 'categoria':
            return relatorioCategoria();
        case 'localizacao':
            return relatorioLocalizacao();
        case 'temporal':
            return relatorioTemporal();
        default:
            return relatorioGeral();
    }
}

function relatorioConservacao() {
    global $pdo;
    
    $stmt = $pdo->query("
        SELECT 
            estado_conservacao,
            COUNT(*) as total,
            AVG(YEAR(CURDATE()) - YEAR(data_registo)) as idade_media,
            MIN(data_registo) as mais_antigo,
            MAX(data_registo) as mais_recente
        FROM patrimonio 
        GROUP BY estado_conservacao
    ");
    
    return [
        'titulo' => 'Relatório de Estado de Conservação',
        'dados' => $stmt->fetchAll(),
        'tipo' => 'conservacao'
    ];
}

function relatorioCategoria() {
    global $pdo;
    
    $stmt = $pdo->query("
        SELECT 
            c.nome as categoria,
            COUNT(*) as total,
            COUNT(CASE WHEN p.estado_conservacao = 'bom' THEN 1 END) as bom_estado,
            COUNT(CASE WHEN p.estado_conservacao = 'regular' THEN 1 END) as estado_regular,
            COUNT(CASE WHEN p.estado_conservacao = 'mau' THEN 1 END) as mau_estado,
            AVG(YEAR(CURDATE()) - YEAR(p.data_registo)) as idade_media
        FROM patrimonio p
        LEFT JOIN categorias_patrimonio c ON p.categoria_id = c.id 
        GROUP BY c.nome
        ORDER BY total DESC
    ");
    
    return [
        'titulo' => 'Relatório por Categoria',
        'dados' => $stmt->fetchAll(),
        'tipo' => 'categoria'
    ];
}

function relatorioLocalizacao() {
    global $pdo;
    
    $stmt = $pdo->query("
        SELECT 
            provincia,
            COUNT(*) as total,
            COUNT(DISTINCT c.nome) as categorias_distintas,
            COUNT(CASE WHEN estado_conservacao = 'mau' THEN 1 END) as necessita_atencao
        FROM patrimonio p
        LEFT JOIN categorias_patrimonio c ON p.categoria_id = c.id
        WHERE provincia IS NOT NULL
        GROUP BY provincia
        ORDER BY total DESC
        LIMIT 20
    ");
    
    return [
        'titulo' => 'Relatório por Província',
        'dados' => $stmt->fetchAll(),
        'tipo' => 'localizacao'
    ];
}

function relatorioTemporal() {
    global $pdo;
    
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(data_registo, '%Y-%m') as mes,
            COUNT(*) as total,
            COUNT(DISTINCT c.nome) as categorias,
            COUNT(DISTINCT provincia) as provincias
        FROM patrimonio p
        LEFT JOIN categorias_patrimonio c ON p.categoria_id = c.id 
        WHERE data_registo >= DATE_SUB(NOW(), INTERVAL 24 MONTH)
        GROUP BY DATE_FORMAT(data_registo, '%Y-%m')
        ORDER BY mes
    ");
    
    return [
        'titulo' => 'Relatório Temporal (24 meses)',
        'dados' => $stmt->fetchAll(),
        'tipo' => 'temporal'
    ];
}

function relatorioGeral() {
    global $pdo;
    
    $dados = [];
    
    // Resumo geral
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_patrimonios,
            COUNT(DISTINCT c.nome) as total_categorias,
            COUNT(DISTINCT p.provincia) as total_provincias,
            COUNT(CASE WHEN p.estado_conservacao = 'mau' THEN 1 END) as necessita_atencao,
            MIN(p.data_registo) as primeiro_registro,
            MAX(p.data_registo) as ultimo_registro
        FROM patrimonio p
        LEFT JOIN categorias_patrimonio c ON p.categoria_id = c.id
    ");
    $dados['resumo'] = $stmt->fetch();
    
    // Top 5 categorias
    $stmt = $pdo->query("
        SELECT c.nome as categoria, COUNT(*) as total 
        FROM patrimonio p
        LEFT JOIN categorias_patrimonio c ON p.categoria_id = c.id 
        GROUP BY c.nome 
        ORDER BY total DESC 
        LIMIT 5
    ");
    $dados['top_categorias'] = $stmt->fetchAll();
    
    return [
        'titulo' => 'Relatório Geral do Sistema',
        'dados' => $dados,
        'tipo' => 'geral'
    ];
}

function obterDadosTemporais() {
    global $pdo;
    
    $periodo = $_GET['periodo'] ?? '12';
    
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(p.data_registo, '%Y-%m') as periodo,
            COUNT(*) as total,
            c.nome as categoria
        FROM patrimonio p
        LEFT JOIN categorias_patrimonio c ON p.categoria_id = c.id 
        WHERE p.data_registo >= DATE_SUB(NOW(), INTERVAL ? MONTH)
        GROUP BY DATE_FORMAT(p.data_registo, '%Y-%m'), c.nome
        ORDER BY periodo, categoria
    ");
    $stmt->execute([intval($periodo)]);
    
    return [
        'dados' => $stmt->fetchAll(),
        'periodo' => $periodo
    ];
}

function exportarDados() {
    global $pdo;
    
    $formato = $_GET['formato'] ?? 'json';
    $tipo = $_GET['tipo'] ?? 'completo';
    
    switch ($tipo) {
        case 'completo':
            $stmt = $pdo->query("
                SELECT 
                    p.id, p.codigo_registo, p.nome, c.nome as categoria, p.tipo_patrimonio,
                    p.provincia, p.distrito, p.estado_conservacao, p.descricao, 
                    p.data_registo, u.nome as responsavel_registro
                FROM patrimonio p
                LEFT JOIN categorias_patrimonio c ON p.categoria_id = c.id
                LEFT JOIN usuarios u ON p.registado_por = u.id
                ORDER BY p.id
            ");
            break;
            
        case 'resumo':
            $stmt = $pdo->query("
                SELECT 
                    c.nome as categoria, 
                    COUNT(*) as total,
                    COUNT(CASE WHEN p.estado_conservacao = 'bom' THEN 1 END) as bom,
                    COUNT(CASE WHEN p.estado_conservacao = 'regular' THEN 1 END) as regular,
                    COUNT(CASE WHEN p.estado_conservacao = 'mau' THEN 1 END) as mau
                FROM patrimonio p
                LEFT JOIN categorias_patrimonio c ON p.categoria_id = c.id 
                GROUP BY c.nome
                ORDER BY total DESC
            ");
            break;
            
        default:
            return ['error' => 'Tipo de exportação inválido'];
    }
    
    $dados = $stmt->fetchAll();
    
    return [
        'dados' => $dados,
        'total_registros' => count($dados),
        'formato' => $formato,
        'data_exportacao' => date('Y-m-d H:i:s'),
        'tipo' => $tipo
    ];
}
?>