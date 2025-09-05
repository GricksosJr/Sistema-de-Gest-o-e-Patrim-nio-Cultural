<?php
include 'config.php';

// Verificar se está logado e é admin
if (!verificarLogin() || $_SESSION['usuario_tipo'] != 'admin') {
    redirecionar('login.php');
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirecionar('index.php');
}

// Função para buscar estatísticas gerais
function obterEstatisticasGerais($pdo) {
    $stats = [];
    
    // Total de patrimónios
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM patrimonio");
    $stats['total_patrimonios'] = $stmt->fetch()['total'];
    
    // Total de usuários
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $stats['total_usuarios'] = $stmt->fetch()['total'];
    
    // Patrimónios por estado
    $stmt = $pdo->query("SELECT estado_conservacao, COUNT(*) as total FROM patrimonio GROUP BY estado_conservacao");
    $stats['por_estado'] = $stmt->fetchAll();
    
    // Patrimónios por categoria (usando JOIN com categorias_patrimonio)
    $stmt = $pdo->query("
        SELECT c.nome as categoria, COUNT(*) as total 
        FROM patrimonio p 
        LEFT JOIN categorias_patrimonio c ON p.categoria_id = c.id 
        GROUP BY c.nome 
        ORDER BY total DESC
    ");
    $stats['por_categoria'] = $stmt->fetchAll();
    
    // Patrimónios por localização (usando província)
    $stmt = $pdo->query("SELECT provincia, COUNT(*) as total FROM patrimonio WHERE provincia IS NOT NULL GROUP BY provincia ORDER BY total DESC LIMIT 10");
    $stats['por_localizacao'] = $stmt->fetchAll();
    
    // Registos por mês (últimos 12 meses)
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(data_registo, '%Y-%m') as mes,
            COUNT(*) as total 
        FROM patrimonio 
        WHERE data_registo >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(data_registo, '%Y-%m')
        ORDER BY mes
    ");
    $stats['por_mes'] = $stmt->fetchAll();
    
    return $stats;
}

// Função para exportar relatório em CSV
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    $tipo = $_GET['tipo'] ?? 'geral';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=relatorio_' . $tipo . '_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    if ($tipo == 'geral') {
        fputcsv($output, ['ID', 'Código', 'Nome', 'Categoria', 'Província', 'Estado', 'Data Registo']);
        
        $stmt = $pdo->query("
            SELECT p.id, p.codigo_registo, p.nome, c.nome as categoria, p.provincia, p.estado_conservacao, p.data_registo 
            FROM patrimonio p 
            LEFT JOIN categorias_patrimonio c ON p.categoria_id = c.id 
            ORDER BY p.id
        ");
        while ($row = $stmt->fetch()) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit;
}

$stats = obterEstatisticasGerais($pdo);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios e Estatísticas - <?php echo SITE_TITLE; ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
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
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #764ba2;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: #667eea;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-badge {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .logout-btn {
            background: #ff6b6b;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 20px;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .logout-btn:hover {
            background: #ff5252;
            transform: translateY(-1px);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 100px 20px 20px;
        }

        .page-header {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            text-align: center;
        }

        .page-title {
            font-size: 2.5rem;
            color: #764ba2;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #666;
            font-size: 1.1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 1rem;
        }

        .stat-card.primary { border-top: 4px solid #667eea; }
        .stat-card.primary .stat-icon { color: #667eea; }
        .stat-card.primary .stat-number { color: #667eea; }

        .stat-card.success { border-top: 4px solid #4CAF50; }
        .stat-card.success .stat-icon { color: #4CAF50; }
        .stat-card.success .stat-number { color: #4CAF50; }

        .stat-card.warning { border-top: 4px solid #FF9800; }
        .stat-card.warning .stat-icon { color: #FF9800; }
        .stat-card.warning .stat-number { color: #FF9800; }

        .stat-card.info { border-top: 4px solid #2196F3; }
        .stat-card.info .stat-icon { color: #2196F3; }
        .stat-card.info .stat-number { color: #2196F3; }

        .reports-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .report-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .report-title {
            font-size: 1.3rem;
            color: #333;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 1rem;
        }

        .export-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .export-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .btn {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 25px;
            transition: all 0.3s ease;
            font-weight: 500;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn.success { background: #4CAF50; }
        .btn.success:hover { background: #45a049; }

        .btn.warning { background: #FF9800; }
        .btn.warning:hover { background: #f57c00; }

        .btn.info { background: #2196F3; }
        .btn.info:hover { background: #1976D2; }

        .table-container {
            overflow-x: auto;
            margin-top: 1rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        tr:hover {
            background: #f8f9fa;
        }

        @media (max-width: 768px) {
            .nav {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .nav-links {
                order: 1;
            }
            
            .user-info {
                order: 2;
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .reports-section {
                grid-template-columns: 1fr;
            }
            
            .export-buttons {
                flex-direction: column;
            }
            
            .page-title {
                font-size: 2rem;
            }
        }

        .filter-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-group label {
            font-weight: 500;
            color: #333;
        }

        .filter-group select,
        .filter-group input {
            padding: 8px 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: border-color 0.3s ease;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #667eea;
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="logo">📊 Relatórios - Património Cultural</div>
            <div class="nav-links">
                <a href="admin.php">🏠 Painel</a>
                <a href="gestao_usuarios.php">👥 Usuários</a>
                <a href="gestao_patrimonio.php">🏛️ Património</a>
            </div>
            <div class="user-info">
                <div class="user-badge">
                    <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>
                </div>
                <a href="?logout=1" class="logout-btn">Sair</a>
            </div>
        </nav>
    </header>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">📊 Relatórios e Estatísticas</h1>
            <p class="page-subtitle">Análise completa do património cultural e uso do sistema</p>
        </div>

        <!-- Estatísticas Gerais -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-icon">🏛️</div>
                <div class="stat-number"><?php echo number_format($stats['total_patrimonios'], 0, ',', '.'); ?></div>
                <div class="stat-label">Total de Patrimónios</div>
            </div>

            <div class="stat-card success">
                <div class="stat-icon">👥</div>
                <div class="stat-number"><?php echo number_format($stats['total_usuarios'], 0, ',', '.'); ?></div>
                <div class="stat-label">Usuários Registados</div>
            </div>

            <div class="stat-card warning">
                <div class="stat-icon">📅</div>
                <div class="stat-number"><?php echo date('d/m/Y'); ?></div>
                <div class="stat-label">Última Actualização</div>
            </div>

            <div class="stat-card info">
                <div class="stat-icon">📈</div>
                <div class="stat-number"><?php echo count($stats['por_categoria']); ?></div>
                <div class="stat-label">Categorias Activas</div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filter-section">
            <h3>🔍 Filtros de Relatório</h3>
            <div class="filter-grid">
                <div class="filter-group">
                    <label for="periodo">Período:</label>
                    <select id="periodo">
                        <option value="30">Últimos 30 dias</option>
                        <option value="90">Últimos 3 meses</option>
                        <option value="365">Último ano</option>
                        <option value="all">Todos os registos</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="categoria">Categoria:</label>
                    <select id="categoria">
                        <option value="">Todas as categorias</option>
                        <?php foreach ($stats['por_categoria'] as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['categoria']); ?>">
                                <?php echo htmlspecialchars($cat['categoria']); ?> (<?php echo $cat['total']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="estado">Estado:</label>
                    <select id="estado">
                        <option value="">Todos os estados</option>
                        <?php foreach ($stats['por_estado'] as $est): ?>
                            <option value="<?php echo htmlspecialchars($est['estado_conservacao']); ?>">
                                <?php echo ucfirst($est['estado_conservacao']); ?> (<?php echo $est['total']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>&nbsp;</label>
                    <button class="btn" onclick="aplicarFiltros()">🔍 Aplicar Filtros</button>
                </div>
            </div>
        </div>

        <!-- Gráficos e Relatórios -->
        <div class="reports-section">
            <!-- Patrimónios por Categoria -->
            <div class="report-card">
                <h3 class="report-title">📊 Patrimónios por Categoria</h3>
                <div class="chart-container">
                    <canvas id="categoriaChart"></canvas>
                </div>
            </div>

            <!-- Estado de Conservação -->
            <div class="report-card">
                <h3 class="report-title">🔧 Estado de Conservação</h3>
                <div class="chart-container">
                    <canvas id="estadoChart"></canvas>
                </div>
            </div>

            <!-- Registos por Mês -->
            <div class="report-card">
                <h3 class="report-title">📈 Registos por Mês</h3>
                <div class="chart-container">
                    <canvas id="mesChart"></canvas>
                </div>
            </div>

            <!-- Top Localizações -->
            <div class="report-card">
                <h3 class="report-title">📍 Top Províncias</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Província</th>
                                <th>Quantidade</th>
                                <th>Percentual</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['por_localizacao'] as $loc): 
                                $percent = ($loc['total'] / $stats['total_patrimonios']) * 100;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($loc['provincia'] ?? 'Não especificado'); ?></td>
                                    <td><?php echo $loc['total']; ?></td>
                                    <td><?php echo number_format($percent, 1); ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Exportação de Dados -->
        <div class="export-section">
            <h3>📤 Exportação de Relatórios</h3>
            <p>Exporte dados do sistema em diferentes formatos para análise externa.</p>
            <div class="export-buttons">
                <a href="?export=csv&tipo=geral" class="btn success">
                    📄 Exportar CSV Geral
                </a>
                <button class="btn info" onclick="exportarPDF()">
                    📋 Gerar Relatório PDF
                </button>
                <button class="btn warning" onclick="exportarExcel()">
                    📊 Exportar Excel
                </button>
                <button class="btn" onclick="imprimirRelatorio()">
                    🖨️ Imprimir Relatório
                </button>
            </div>
        </div>
    </div>

    <script>
        // Dados para os gráficos
        const categoriaData = {
            labels: <?php echo json_encode(array_column($stats['por_categoria'], 'categoria')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($stats['por_categoria'], 'total')); ?>,
                backgroundColor: [
                    '#667eea', '#764ba2', '#4CAF50', '#FF9800', 
                    '#2196F3', '#9C27B0', '#FF6B6B', '#20C997'
                ],
                borderWidth: 0
            }]
        };

        const estadoData = {
            labels: <?php echo json_encode(array_column($stats['por_estado'], 'estado_conservacao')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($stats['por_estado'], 'total')); ?>,
                backgroundColor: ['#4CAF50', '#FF9800', '#FF6B6B', '#2196F3'],
                borderWidth: 0
            }]
        };

        const mesData = {
            labels: <?php echo json_encode(array_column($stats['por_mes'], 'mes')); ?>,
            datasets: [{
                label: 'Novos Registos',
                data: <?php echo json_encode(array_column($stats['por_mes'], 'total')); ?>,
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                fill: true,
                tension: 0.4
            }]
        };

        // Configuração dos gráficos
        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                }
            }
        };

        // Criar gráficos
        new Chart(document.getElementById('categoriaChart'), {
            type: 'doughnut',
            data: categoriaData,
            options: chartOptions
        });

        new Chart(document.getElementById('estadoChart'), {
            type: 'pie',
            data: estadoData,
            options: chartOptions
        });

        new Chart(document.getElementById('mesChart'), {
            type: 'line',
            data: mesData,
            options: {
                ...chartOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Funções auxiliares
        function aplicarFiltros() {
            const periodo = document.getElementById('periodo').value;
            const categoria = document.getElementById('categoria').value;
            const estado = document.getElementById('estado').value;
            
            // Aqui implementaria a lógica para aplicar filtros via AJAX
            alert('Funcionalidade de filtros será implementada via AJAX');
        }

        function exportarPDF() {
            alert('Exportação PDF em desenvolvimento');
        }

        function exportarExcel() {
            alert('Exportação Excel em desenvolvimento');
        }

        function imprimirRelatorio() {
            window.print();
        }

        // Atualização automática das estatísticas a cada 5 minutos
        setInterval(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>