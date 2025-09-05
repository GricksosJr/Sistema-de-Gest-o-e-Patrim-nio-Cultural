<?php
include 'config.php';

// Verificar se está logado e é admin
if (!verificarLogin() || $_SESSION['usuario_tipo'] != 'admin') {
    redirecionar('login.php');
}

// Processar ações
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'backup_database':
                $backup_result = createDatabaseBackup();
                if ($backup_result['success']) {
                    $message = "Backup da base de dados criado com sucesso!";
                    $message_type = 'success';
                } else {
                    $message = "Erro ao criar backup: " . $backup_result['error'];
                    $message_type = 'error';
                }
                break;

            case 'add_category':
                if (!empty($_POST['nome_categoria']) && !empty($_POST['descricao_categoria'])) {
                    $nome = $_POST['nome_categoria'];
                    $descricao = $_POST['descricao_categoria'];
                    
                    $stmt = $pdo->prepare("INSERT INTO categorias_patrimonio (nome, descricao) VALUES (?, ?)");
                    if ($stmt->execute([$nome, $descricao])) {
                        $message = "Categoria adicionada com sucesso!";
                        $message_type = 'success';
                    } else {
                        $message = "Erro ao adicionar categoria!";
                        $message_type = 'error';
                    }
                    $stmt->closeCursor();
                }
                break;

            case 'delete_category':
                if (!empty($_POST['categoria_id'])) {
                    $categoria_id = $_POST['categoria_id'];
                    
                    // Verificar se há patrimónios usando esta categoria
                    $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM patrimonio WHERE categoria_id = ?");
                    $check_stmt->execute([$categoria_id]);
                    $count = $check_stmt->fetchColumn();
                    $check_stmt->closeCursor();
                    
                    if ($count > 0) {
                        $message = "Não é possível eliminar a categoria. Existem $count patrimónios associados.";
                        $message_type = 'error';
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM categorias_patrimonio WHERE id = ?");
                        if ($stmt->execute([$categoria_id])) {
                            $message = "Categoria eliminada com sucesso!";
                            $message_type = 'success';
                        } else {
                            $message = "Erro ao eliminar categoria!";
                            $message_type = 'error';
                        }
                        $stmt->closeCursor();
                    }
                }
                break;

            case 'clean_logs':
                $days = intval($_POST['days_to_keep'] ?? 30);
                $stmt = $pdo->prepare("DELETE FROM user_activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
                if ($stmt->execute([$days])) {
                    $message = "Logs antigos eliminados com sucesso!";
                    $message_type = 'success';
                } else {
                    $message = "Erro ao eliminar logs!";
                    $message_type = 'error';
                }
                $stmt->closeCursor();
                break;

            case 'optimize_database':
                try {
                    $tables = ['usuarios', 'patrimonio', 'categorias_patrimonio', 'patrimonio_historico', 'patrimonio_pessoas', 'user_activity_log', 'sessoes'];
                    
                    // Solução para o erro de unbuffered queries
                    foreach ($tables as $table) {
                        $stmt = $pdo->query("OPTIMIZE TABLE $table");
                        // Fetch all results to clear the buffer
                        $stmt->fetchAll(PDO::FETCH_ASSOC);
                        // Close cursor to free the connection
                        $stmt->closeCursor();
                    }
                    
                    $message = "Base de dados otimizada com sucesso!";
                    $message_type = 'success';
                } catch (Exception $e) {
                    $message = "Erro ao otimizar base de dados: " . $e->getMessage();
                    $message_type = 'error';
                }
                break;
        }
    }
}

// Função para criar backup da base de dados (versão com PHP puro)
function createDatabaseBackup() {
    global $pdo;
    
    try {
        $backup_dir = 'backups/';
        if (!file_exists($backup_dir)) {
            if (!mkdir($backup_dir, 0755, true)) {
                return ['success' => false, 'error' => 'Não foi possível criar o diretório de backups'];
            }
        }
        
        // Verificar se o diretório é gravável
        if (!is_writable($backup_dir)) {
            return ['success' => false, 'error' => 'Diretório de backups não tem permissão de escrita'];
        }
        
        $filename = $backup_dir . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        
        // Abrir arquivo para escrita
        $handle = fopen($filename, 'w');
        if (!$handle) {
            return ['success' => false, 'error' => 'Não foi possível criar o arquivo de backup'];
        }
        
        // Obter todas as tabelas
        $tables_stmt = $pdo->query("SHOW TABLES");
        $tables = $tables_stmt->fetchAll(PDO::FETCH_COLUMN);
        $tables_stmt->closeCursor();
        
        // Escrever cabeçalho do backup
        fwrite($handle, "-- Backup gerado em: " . date('Y-m-d H:i:s') . "\n");
        fwrite($handle, "-- Gerado por: Sistema de Gestão de Patrimônio\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");
        
        // Iterar por cada tabela
        foreach ($tables as $table) {
            // Escrever estrutura da tabela
            fwrite($handle, "--\n-- Estrutura para tabela `$table`\n--\n");
            
            $createTable_stmt = $pdo->query("SHOW CREATE TABLE `$table`");
            $createTable = $createTable_stmt->fetch(PDO::FETCH_ASSOC);
            $createTable_stmt->closeCursor();
            
            fwrite($handle, $createTable['Create Table'] . ";\n\n");
            
            // Obter dados da tabela
            fwrite($handle, "--\n-- Despejando dados para tabela `$table`\n--\n");
            
            $rows_stmt = $pdo->query("SELECT * FROM `$table`");
            $rows = $rows_stmt->fetchAll(PDO::FETCH_ASSOC);
            $rows_stmt->closeCursor();
            
            if (count($rows) > 0) {
                $columns = array_keys($rows[0]);
                $columnsStr = implode('`, `', $columns);
                
                foreach ($rows as $row) {
                    $values = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $values[] = "NULL";
                        } else {
                            $values[] = $pdo->quote($value);
                        }
                    }
                    
                    $valuesStr = implode(', ', $values);
                    fwrite($handle, "INSERT INTO `$table` (`$columnsStr`) VALUES ($valuesStr);\n");
                }
            }
            
            fwrite($handle, "\n");
        }
        
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);
        
        return ['success' => true, 'filename' => $filename];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Obter estatísticas do sistema
$stats = [];

try {
    // Total de patrimónios
    $stmt = $pdo->query("SELECT COUNT(*) FROM patrimonio");
    $stats['total_patrimonio'] = $stmt->fetchColumn();
    $stmt->closeCursor(); // Liberar o buffer
    
    // Total de usuários
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
    $stats['total_usuarios'] = $stmt->fetchColumn();
    $stmt->closeCursor();
    
    // Total de categorias
    $stmt = $pdo->query("SELECT COUNT(*) FROM categorias_patrimonio");
    $stats['total_categorias'] = $stmt->fetchColumn();
    $stmt->closeCursor();
    
    // Tamanho da base de dados
    $stmt = $pdo->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS db_size FROM information_schema.tables WHERE table_schema='" . DB_NAME . "'");
    $stats['db_size'] = $stmt->fetchColumn();
    $stmt->closeCursor();
    
    // Logs de atividade (últimos 30 dias)
    $stmt = $pdo->query("SELECT COUNT(*) FROM user_activity_log WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stats['logs_count'] = $stmt->fetchColumn();
    $stmt->closeCursor();
    
} catch (Exception $e) {
    $stats = [
        'total_patrimonio' => 'Erro',
        'total_usuarios' => 'Erro',
        'total_categorias' => 'Erro',
        'db_size' => 'Erro',
        'logs_count' => 'Erro'
    ];
}

// Obter todas as categorias
$categorias = [];
try {
    $stmt = $pdo->query("SELECT c.*, COUNT(p.id) as total_patrimonios FROM categorias_patrimonio c LEFT JOIN patrimonio p ON c.id = p.categoria_id GROUP BY c.id ORDER BY c.nome");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
} catch (Exception $e) {
    $categorias = [];
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirecionar('index.php');
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações do Sistema - <?php echo SITE_TITLE; ?></title>
    <style>
        /* Estilos mantidos iguais */
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
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #764ba2;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .nav-link {
            color: #666;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background: #f0f0f0;
            color: #764ba2;
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

        .admin-badge {
            background: #ff6b6b;
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: bold;
            text-transform: uppercase;
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 100px 20px 20px;
        }

        .page-header {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
            margin-bottom: 2rem;
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

        .message {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            font-weight: 500;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .config-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }

        .config-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .section-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }

        .section-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .section-title {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
        }

        .section-subtitle {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .section-content {
            padding: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #666;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }

        .form-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-textarea {
            min-height: 80px;
            resize: vertical;
        }

        .form-select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            background: white;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background: #e0a800;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .btn-small {
            padding: 8px 16px;
            font-size: 0.9rem;
        }

        .categories-list {
            max-height: 300px;
            overflow-y: auto;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        .category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }

        .category-item:last-child {
            border-bottom: none;
        }

        .category-info h4 {
            color: #333;
            margin-bottom: 0.25rem;
        }

        .category-info p {
            color: #666;
            font-size: 0.9rem;
        }

        .category-stats {
            text-align: center;
            color: #667eea;
            font-weight: bold;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1.5rem;
        }

        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        @media (max-width: 768px) {
            .config-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .nav {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .user-info {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .page-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="logo">⚙️ Configurações do Sistema</div>
            <div class="nav-links">
                <a href="admin.php" class="nav-link">🏠 Painel Principal</a>
                <a href="gestao_usuarios.php" class="nav-link">👥 Usuários</a>
                <a href="gestao_patrimonio.php" class="nav-link">🏛️ Património</a>
            </div>
            <div class="user-info">
                <div class="user-badge">
                    <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>
                    <span class="admin-badge">Admin</span>
                </div>
                <a href="?logout=1" class="logout-btn">Sair</a>
            </div>
        </nav>
    </header>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">⚙️ Configurações do Sistema</h1>
            <p class="page-subtitle">Gerir configurações, backup e manutenção do sistema</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="config-grid">
            <!-- Estatísticas do Sistema -->
            <div class="config-section">
                <div class="section-header">
                    <span class="section-icon">📊</span>
                    <h2 class="section-title">Estatísticas do Sistema</h2>
                    <p class="section-subtitle">Dados gerais do sistema</p>
                </div>
                <div class="section-content">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $stats['total_patrimonio']; ?></div>
                            <div class="stat-label">Património</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $stats['total_usuarios']; ?></div>
                            <div class="stat-label">Usuários</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $stats['total_categorias']; ?></div>
                            <div class="stat-label">Categorias</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $stats['db_size']; ?> MB</div>
                            <div class="stat-label">Base de Dados</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $stats['logs_count']; ?></div>
                            <div class="stat-label">Logs (30d)</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Backup e Manutenção -->
            <div class="config-section">
                <div class="section-header">
                    <span class="section-icon">💾</span>
                    <h2 class="section-title">Backup e Manutenção</h2>
                    <p class="section-subtitle">Backup e otimização da base de dados</p>
                </div>
                <div class="section-content">
                    <div class="action-buttons">
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="action" value="backup_database">
                            <button type="submit" class="btn btn-success">
                                💾 Criar Backup
                            </button>
                        </form>
                        
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="action" value="optimize_database">
                            <button type="submit" class="btn btn-warning" onclick="return confirm('Deseja otimizar a base de dados?')">
                                🔧 Otimizar BD
                            </button>
                        </form>
                    </div>

                    <div class="form-group" style="margin-top: 2rem;">
                        <form method="post">
                            <input type="hidden" name="action" value="clean_logs">
                            <label class="form-label">Limpar Logs de Atividade</label>
                            <div style="display: flex; gap: 1rem; align-items: end;">
                                <div style="flex: 1;">
                                    <select name="days_to_keep" class="form-select">
                                        <option value="7">Manter últimos 7 dias</option>
                                        <option value="30" selected>Manter últimos 30 dias</option>
                                        <option value="90">Manter últimos 90 dias</option>
                                        <option value="365">Manter último ano</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-warning" onclick="return confirm('Deseja eliminar os logs antigos?')">
                                    🗑️ Limpar Logs
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Gestão de Categorias -->
            <div class="config-section" style="grid-column: span 2;">
                <div class="section-header">
                    <span class="section-icon">📚</span>
                    <h2 class="section-title">Gestão de Categorias</h2>
                    <p class="section-subtitle">Adicionar e gerir categorias de património</p>
                </div>
                <div class="section-content">
                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
                        <!-- Adicionar Nova Categoria -->
                        <div>
                            <h3 style="margin-bottom: 1rem; color: #333;">Nova Categoria</h3>
                            <form method="post">
                                <input type="hidden" name="action" value="add_category">
                                <div class="form-group">
                                    <label class="form-label">Nome da Categoria</label>
                                    <input type="text" name="nome_categoria" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Descrição</label>
                                    <textarea name="descricao_categoria" class="form-input form-textarea" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    ➕ Adicionar Categoria
                                </button>
                            </form>
                        </div>

                        <!-- Lista de Categorias -->
                        <div>
                            <h3 style="margin-bottom: 1rem; color: #333;">Categorias Existentes</h3>
                            <?php if (!empty($categorias)): ?>
                                <div class="categories-list">
                                    <?php foreach ($categorias as $categoria): ?>
                                        <div class="category-item">
                                            <div class="category-info">
                                                <h4><?php echo htmlspecialchars($categoria['nome']); ?></h4>
                                                <p><?php echo htmlspecialchars($categoria['descricao']); ?></p>
                                            </div>
                                            <div style="display: flex; align-items: center; gap: 1rem;">
                                                <div class="category-stats">
                                                    <div><?php echo $categoria['total_patrimonios']; ?></div>
                                                    <small>patrimónios</small>
                                                </div>
                                                <?php if ($categoria['total_patrimonios'] == 0): ?>
                                                    <form method="post" style="display: inline;">
                                                        <input type="hidden" name="action" value="delete_category">
                                                        <input type="hidden" name="categoria_id" value="<?php echo $categoria['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-small" onclick="return confirm('Deseja eliminar esta categoria?')">
                                                            🗑️
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p style="color: #666; text-align: center; padding: 2rem;">Nenhuma categoria encontrada.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informações do Sistema -->
            <div class="config-section">
                <div class="section-header">
                    <span class="section-icon">ℹ️</span>
                    <h2 class="section-title">Informações do Sistema</h2>
                    <p class="section-subtitle">Detalhes técnicos do sistema</p>
                </div>
                <div class="section-content">
                    <div style="font-family: monospace; font-size: 0.9rem; line-height: 1.6;">
                        <p><strong>Versão do Sistema:</strong> 1.0.0</p>
                        <p><strong>PHP:</strong> <?php echo phpversion(); ?></p>
                        <p><strong>Base de Dados:</strong> <?php echo DB_NAME; ?></p>
                        <p><strong>Servidor:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></p>
                        <p><strong>Última Atualização:</strong> <?php echo date('d/m/Y H:i'); ?></p>
                        <p><strong>Timezone:</strong> <?php echo date_default_timezone_get(); ?></p>
                        <p><strong>Memória PHP:</strong> <?php echo ini_get('memory_limit'); ?></p>
                        <p><strong>Upload Máximo:</strong> <?php echo ini_get('upload_max_filesize'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Segurança -->
            <div class="config-section">
                <div class="section-header">
                    <span class="section-icon">🔐</span>
                    <h2 class="section-title">Segurança</h2>
                    <p class="section-subtitle">Configurações de segurança</p>
                </div>
                <div class="section-content">
                    <div style="text-align: center; color: #666;">
                        <p style="margin-bottom: 1rem;">Funcionalidades de segurança:</p>
                        <ul style="text-align: left; max-width: 300px; margin: 0 auto;">
                            <li style="margin-bottom: 0.5rem;">✅ Autenticação de usuários</li>
                            <li style="margin-bottom: 0.5rem;">✅ Controle de acesso por perfil</li>
                            <li style="margin-bottom: 0.5rem;">✅ Log de atividades</li>
                            <li style="margin-bottom: 0.5rem;">✅ Sessões seguras</li>
                            <li style="margin-bottom: 0.5rem;">✅ Validação de dados</li>
                        </ul>
                        <p style="margin-top: 1rem; font-size: 0.9rem; color: #28a745;">
                            🛡️ Sistema protegido
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Função para mostrar loading
        function showLoading(form) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.style.opacity = '0.6';
                submitBtn.style.pointerEvents = 'none';
                submitBtn.innerHTML = '⏳ Processando...';
                
                // Restaurar o botão após 30 segundos (caso algo dê errado)
                setTimeout(function() {
                    submitBtn.style.opacity = '1';
                    submitBtn.style.pointerEvents = 'auto';
                    submitBtn.innerHTML = originalText;
                }, 30000);
            }
        }

        // Adicionar indicador de loading nos botões de formulário
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn && !submitBtn.onclick) {
                    showLoading(form);
                }
            });
        });

        // Auto refresh das estatísticas a cada 5 minutos
        setInterval(function() {
            // Apenas recarregar se não houver formulários sendo processados
            const processingButtons = document.querySelectorAll('button[style*="pointer-events: none"]');
            if (processingButtons.length === 0) {
                window.location.reload();
            }
        }, 300000);

        // Notificação de sucesso para downloads
        document.querySelectorAll('a[download]').forEach(link => {
            link.addEventListener('click', function() {
                setTimeout(function() {
                    alert('Download iniciado! Verifique a pasta de downloads.');
                }, 500);
            });
        });
    </script>
</body>
</html>