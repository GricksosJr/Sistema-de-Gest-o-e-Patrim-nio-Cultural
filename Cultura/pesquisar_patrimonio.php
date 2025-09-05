<?php
include 'config.php';

// Verificar se está logado e é funcionário
if (!verificarLogin() || $_SESSION['usuario_tipo'] != 'funcionario') {
    redirecionar('login.php');
}

// Parâmetros de pesquisa
$busca = trim($_GET['busca'] ?? '');
$categoria_id = intval($_GET['categoria_id'] ?? 0);
$tipo_patrimonio = $_GET['tipo_patrimonio'] ?? '';
$provincia = trim($_GET['provincia'] ?? '');
$estado_conservacao = $_GET['estado_conservacao'] ?? '';
$status = $_GET['status'] ?? '';

// Buscar categorias para o filtro
$categorias = [];
try {
    $stmt = $pdo->query("SELECT * FROM categorias_patrimonio ORDER BY nome");
    $categorias = $stmt->fetchAll();
} catch(PDOException $e) {
    $categorias = [];
}

// Construir consulta
$where_conditions = ["1=1"];
$params = [];

if (!empty($busca)) {
    $where_conditions[] = "(p.nome LIKE ? OR p.descricao LIKE ? OR p.localidade LIKE ? OR p.distrito LIKE ? OR p.provincia LIKE ?)";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
}

if ($categoria_id > 0) {
    $where_conditions[] = "p.categoria_id = ?";
    $params[] = $categoria_id;
}

if (!empty($tipo_patrimonio)) {
    $where_conditions[] = "p.tipo_patrimonio = ?";
    $params[] = $tipo_patrimonio;
}

if (!empty($provincia)) {
    $where_conditions[] = "p.provincia LIKE ?";
    $params[] = "%$provincia%";
}

if (!empty($estado_conservacao)) {
    $where_conditions[] = "p.estado_conservacao = ?";
    $params[] = $estado_conservacao;
}

if (!empty($status)) {
    $where_conditions[] = "p.status = ?";
    $params[] = $status;
}

$where_clause = implode(' AND ', $where_conditions);

// Buscar patrimónios
$patrimonios = [];
$total_resultados = 0;

try {
    $sql = "
        SELECT p.*, c.nome as categoria_nome, u.nome as registado_por_nome
        FROM patrimonio p 
        LEFT JOIN categorias_patrimonio c ON p.categoria_id = c.id 
        LEFT JOIN usuarios u ON p.registado_por = u.id
        WHERE $where_clause
        ORDER BY p.data_registo DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $patrimonios = $stmt->fetchAll();
    $total_resultados = count($patrimonios);
    
} catch(PDOException $e) {
    $erro = "Erro na pesquisa: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesquisar Património - <?php echo SITE_TITLE; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(45deg, #FF9800, #F57C00);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .header p {
            opacity: 0.9;
        }

        .nav-back {
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem 2rem;
        }

        .nav-back a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-back a:hover {
            opacity: 0.8;
        }

        .search-container {
            padding: 2rem;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .search-form {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 1rem;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .form-control {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #FF9800;
            box-shadow: 0 0 0 3px rgba(255, 152, 0, 0.1);
        }

        .btn {
            background: #FF9800;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn:hover {
            background: #F57C00;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #6c757d;
            margin-left: 0.5rem;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .filters-section {
            padding: 1rem 2rem;
            background: #fff;
            border-bottom: 1px solid #dee2e6;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .results-container {
            padding: 2rem;
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #eee;
        }

        .results-count {
            font-size: 1.1rem;
            color: #666;
        }

        .results-count strong {
            color: #FF9800;
        }

        .patrimonio-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 2rem;
        }

        .patrimonio-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 1.5rem;
            transition: all 0.3s ease;
            border-left: 4px solid #FF9800;
        }

        .patrimonio-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .patrimonio-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .patrimonio-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .patrimonio-code {
            background: #FF9800;
            color: white;
            padding: 4px 8px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .patrimonio-info {
            margin-bottom: 1rem;
        }

        .info-item {
            display: flex;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .info-label {
            font-weight: 600;
            color: #666;
            width: 100px;
            margin-right: 10px;
        }

        .info-value {
            color: #333;
            flex: 1;
        }

        .patrimonio-description {
            color: #666;
            line-height: 1.5;
            margin-bottom: 1rem;
            max-height: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .patrimonio-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .tag {
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .tag.material {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .tag.imaterial {
            background: #fff3e0;
            color: #f57c00;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-ativo {
            background: #d4edda;
            color: #155724;
        }

        .status-em_analise {
            background: #fff3cd;
            color: #856404;
        }

        .status-pendente {
            background: #f8d7da;
            color: #721c24;
        }

        .conservacao-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .conservacao-excelente {
            background: #d4edda;
            color: #155724;
        }

        .conservacao-bom {
            background: #d1ecf1;
            color: #0c5460;
        }

        .conservacao-regular {
            background: #fff3cd;
            color: #856404;
        }

        .conservacao-mau {
            background: #f8d7da;
            color: #721c24;
        }

        .conservacao-critico {
            background: #f5c6cb;
            color: #721c24;
        }

        .patrimonio-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 0.8rem;
        }

        .btn-view {
            background: #17a2b8;
        }

        .btn-view:hover {
            background: #138496;
        }

        .no-results {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .no-results-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        @media (max-width: 768px) {
            .search-form {
                grid-template-columns: 1fr;
            }
            
            .filters-grid {
                grid-template-columns: 1fr;
            }
            
            .patrimonio-grid {
                grid-template-columns: 1fr;
            }
            
            .container {
                margin: 0 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Pesquisar Património Cultural</h1>
            <p>Consulte o catálogo completo de património cultural</p>
        </div>
        
        <div class="nav-back">
            <a href="painel_funcionario.php">← Voltar ao Painel</a>
        </div>

        <!-- Formulário de Pesquisa -->
        <div class="search-container">
            <form method="GET" class="search-form">
                <div class="form-group">
                    <label for="busca">Pesquisa Geral</label>
                    <input type="text" id="busca" name="busca" class="form-control" 
                           value="<?php echo htmlspecialchars($busca); ?>" 
                           placeholder="Nome, descrição, localização...">
                </div>
                <div class="form-group">
                    <label for="categoria_id">Categoria</label>
                    <select id="categoria_id" name="categoria_id" class="form-control">
                        <option value="">Todas as categorias</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" 
                                    <?php echo ($categoria_id == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="tipo_patrimonio">Tipo</label>
                    <select id="tipo_patrimonio" name="tipo_patrimonio" class="form-control">
                        <option value="">Todos os tipos</option>
                        <option value="material" <?php echo ($tipo_patrimonio == 'material') ? 'selected' : ''; ?>>Material</option>
                        <option value="imaterial" <?php echo ($tipo_patrimonio == 'imaterial') ? 'selected' : ''; ?>>Imaterial</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn">🔍 Pesquisar</button>
                    <a href="pesquisar_patrimonio.php" class="btn btn-secondary">Limpar</a>
                </div>
            </form>
        </div>

        <!-- Filtros Adicionais -->
        <div class="filters-section">
            <form method="GET" class="filters-grid">
                <input type="hidden" name="busca" value="<?php echo htmlspecialchars($busca); ?>">
                <input type="hidden" name="categoria_id" value="<?php echo $categoria_id; ?>">
                <input type="hidden" name="tipo_patrimonio" value="<?php echo htmlspecialchars($tipo_patrimonio); ?>">
                
                <div class="form-group">
                    <label for="provincia">Província</label>
                    <input type="text" id="provincia" name="provincia" class="form-control" 
                           value="<?php echo htmlspecialchars($provincia); ?>" placeholder="Ex: Maputo">
                </div>
                
                <div class="form-group">
                    <label for="estado_conservacao">Estado de Conservação</label>
                    <select id="estado_conservacao" name="estado_conservacao" class="form-control">
                        <option value="">Todos os estados</option>
                        <option value="excelente" <?php echo ($estado_conservacao == 'excelente') ? 'selected' : ''; ?>>Excelente</option>
                        <option value="bom" <?php echo ($estado_conservacao == 'bom') ? 'selected' : ''; ?>>Bom</option>
                        <option value="regular" <?php echo ($estado_conservacao == 'regular') ? 'selected' : ''; ?>>Regular</option>
                        <option value="mau" <?php echo ($estado_conservacao == 'mau') ? 'selected' : ''; ?>>Mau</option>
                        <option value="critico" <?php echo ($estado_conservacao == 'critico') ? 'selected' : ''; ?>>Crítico</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="">Todos os status</option>
                        <option value="ativo" <?php echo ($status == 'ativo') ? 'selected' : ''; ?>>Ativo</option>
                        <option value="em_analise" <?php echo ($status == 'em_analise') ? 'selected' : ''; ?>>Em Análise</option>
                        <option value="pendente" <?php echo ($status == 'pendente') ? 'selected' : ''; ?>>Pendente</option>
                        <option value="inativo" <?php echo ($status == 'inativo') ? 'selected' : ''; ?>>Inativo</option>
                    </select>
                </div>
                
                <div>
                    <button type="submit" class="btn">Aplicar Filtros</button>
                </div>
            </form>
        </div>

        <!-- Resultados -->
        <div class="results-container">
            <div class="results-header">
                <div class="results-count">
                    <strong><?php echo $total_resultados; ?></strong> 
                    <?php echo $total_resultados == 1 ? 'resultado encontrado' : 'resultados encontrados'; ?>
                </div>
            </div>

            <?php if (empty($patrimonios)): ?>
                <div class="no-results">
                    <div class="no-results-icon">🔍</div>
                    <h3>Nenhum património encontrado</h3>
                    <p>Tente ajustar os critérios de pesquisa ou filtros.</p>
                </div>
            <?php else: ?>
                <div class="patrimonio-grid">
                    <?php foreach ($patrimonios as $patrimonio): ?>
                        <div class="patrimonio-card">
                            <div class="patrimonio-header">
                                <div>
                                    <div class="patrimonio-title"><?php echo htmlspecialchars($patrimonio['nome']); ?></div>
                                    <div class="patrimonio-code"><?php echo htmlspecialchars($patrimonio['codigo_registo']); ?></div>
                                </div>
                            </div>

                            <div class="patrimonio-info">
                                <div class="info-item">
                                    <span class="info-label">Categoria:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($patrimonio['categoria_nome'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Tipo:</span>
                                    <span class="info-value"><?php echo ucfirst($patrimonio['tipo_patrimonio']); ?></span>
                                </div>
                                <?php if (!empty($patrimonio['provincia'])): ?>
                                <div class="info-item">
                                    <span class="info-label">Localização:</span>
                                    <span class="info-value">
                                        <?php echo htmlspecialchars($patrimonio['provincia']); ?>
                                        <?php if (!empty($patrimonio['distrito'])): ?>
                                            - <?php echo htmlspecialchars($patrimonio['distrito']); ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                                <div class="info-item">
                                    <span class="info-label">Registado:</span>
                                    <span class="info-value"><?php echo date('d/m/Y', strtotime($patrimonio['data_registo'])); ?></span>
                                </div>
                            </div>

                            <div class="patrimonio-description">
                                <?php echo htmlspecialchars(substr($patrimonio['descricao'], 0, 200)) . '...'; ?>
                            </div>

                            <div class="patrimonio-tags">
                                <span class="tag <?php echo $patrimonio['tipo_patrimonio']; ?>">
                                    <?php echo ucfirst($patrimonio['tipo_patrimonio']); ?>
                                </span>
                                <span class="status-badge status-<?php echo $patrimonio['status']; ?>">
                                    <?php echo str_replace('_', ' ', ucfirst($patrimonio['status'])); ?>
                                </span>
                                <span class="conservacao-badge conservacao-<?php echo $patrimonio['estado_conservacao']; ?>">
                                    <?php echo ucfirst($patrimonio['estado_conservacao']); ?>
                                </span>
                            </div>

                            <div class="patrimonio-actions">
                                <a href="ver_patrimonio.php?id=<?php echo $patrimonio['id']; ?>" class="btn btn-view btn-small">
                                    👁️ Ver Detalhes
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>