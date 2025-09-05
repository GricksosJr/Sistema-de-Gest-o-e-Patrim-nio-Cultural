<?php
include 'config.php';

// Verificar se está logado
if (!verificarLogin()) {
    redirecionar('login.php');
}

// Função para upload de foto
function uploadFoto($file) {
    $diretorio_upload = 'uploads/patrimonio/fotos/';
    
    // Criar diretório se não existir
    if (!is_dir($diretorio_upload)) {
        mkdir($diretorio_upload, 0755, true);
    }
    
    // Verificar se foi enviado um arquivo
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    // Validar tipo de arquivo
    $tipos_permitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $tipos_permitidos)) {
        throw new Exception('Tipo de arquivo não permitido. Use apenas JPEG, PNG ou GIF.');
    }
    
    // Validar tamanho (máximo 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('Arquivo muito grande. Máximo de 5MB permitido.');
    }
    
    // Gerar nome único para o arquivo
    $extensao = pathinfo($file['name'], PATHINFO_EXTENSION);
    $nome_arquivo = uniqid('patrimonio_') . '.' . $extensao;
    $caminho_completo = $diretorio_upload . $nome_arquivo;
    
    // Mover arquivo para o diretório
    if (move_uploaded_file($file['tmp_name'], $caminho_completo)) {
        return $nome_arquivo;
    } else {
        throw new Exception('Erro ao fazer upload da foto.');
    }
}

// Processamento de ações
$acao = $_GET['acao'] ?? '';
$mensagem = '';
$tipo_mensagem = '';

// Adicionar novo património
if ($_POST['acao'] ?? '' === 'adicionar') {
    try {
        // Validar campos obrigatórios
        $campos_obrigatorios = ['codigo_registo', 'nome', 'tipo_patrimonio', 'descricao', 'estado_conservacao'];
        foreach ($campos_obrigatorios as $campo) {
            if (empty($_POST[$campo])) {
                throw new Exception("Campo obrigatório não preenchido: " . $campo);
            }
        }

        // Verificar se o código já existe
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM patrimonio WHERE codigo_registo = ?");
        $stmt_check->execute([limparInput($_POST['codigo_registo'])]);
        if ($stmt_check->fetchColumn() > 0) {
            throw new Exception("Já existe um património com este código de registo.");
        }

        // Fazer upload da foto se fornecida
        $foto_nome = null;
        if (isset($_FILES['foto_principal']) && $_FILES['foto_principal']['error'] === UPLOAD_ERR_OK) {
            $foto_nome = uploadFoto($_FILES['foto_principal']);
        }

        $sql = "INSERT INTO patrimonio (
            codigo_registo, nome, categoria_id, tipo_patrimonio, descricao,
            provincia, distrito, localidade, coordenadas_gps,
            periodo_historico, data_criacao_aproximada, origem,
            estado_conservacao, observacoes_estado,
            significado_cultural, valor_historico, relevancia_comunitaria,
            materiais_construcao, tecnicas_utilizadas, dimensoes, peso,
            praticantes, frequencia_pratica, rituais_associados, conhecimentos_tradicionais,
            foto_principal,
            proprietario, gestor_responsavel, contacto_responsavel,
            acesso_publico, horario_visita, restricoes_acesso,
            ameacas_identificadas, nivel_risco, medidas_protecao,
            classificacao_oficial, data_classificacao, entidade_classificadora,
            registado_por
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute([
            limparInput($_POST['codigo_registo']),
            limparInput($_POST['nome']),
            !empty($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null,
            limparInput($_POST['tipo_patrimonio']),
            limparInput($_POST['descricao']),
            !empty($_POST['provincia']) ? limparInput($_POST['provincia']) : null,
            !empty($_POST['distrito']) ? limparInput($_POST['distrito']) : null,
            !empty($_POST['localidade']) ? limparInput($_POST['localidade']) : null,
            !empty($_POST['coordenadas_gps']) ? limparInput($_POST['coordenadas_gps']) : null,
            !empty($_POST['periodo_historico']) ? limparInput($_POST['periodo_historico']) : null,
            !empty($_POST['data_criacao_aproximada']) ? limparInput($_POST['data_criacao_aproximada']) : null,
            !empty($_POST['origem']) ? limparInput($_POST['origem']) : null,
            limparInput($_POST['estado_conservacao']),
            !empty($_POST['observacoes_estado']) ? limparInput($_POST['observacoes_estado']) : null,
            !empty($_POST['significado_cultural']) ? limparInput($_POST['significado_cultural']) : null,
            !empty($_POST['valor_historico']) ? limparInput($_POST['valor_historico']) : null,
            !empty($_POST['relevancia_comunitaria']) ? limparInput($_POST['relevancia_comunitaria']) : null,
            !empty($_POST['materiais_construcao']) ? limparInput($_POST['materiais_construcao']) : null,
            !empty($_POST['tecnicas_utilizadas']) ? limparInput($_POST['tecnicas_utilizadas']) : null,
            !empty($_POST['dimensoes']) ? limparInput($_POST['dimensoes']) : null,
            !empty($_POST['peso']) ? limparInput($_POST['peso']) : null,
            !empty($_POST['praticantes']) ? limparInput($_POST['praticantes']) : null,
            !empty($_POST['frequencia_pratica']) ? limparInput($_POST['frequencia_pratica']) : null,
            !empty($_POST['rituais_associados']) ? limparInput($_POST['rituais_associados']) : null,
            !empty($_POST['conhecimentos_tradicionais']) ? limparInput($_POST['conhecimentos_tradicionais']) : null,
            $foto_nome,
            !empty($_POST['proprietario']) ? limparInput($_POST['proprietario']) : null,
            !empty($_POST['gestor_responsavel']) ? limparInput($_POST['gestor_responsavel']) : null,
            !empty($_POST['contacto_responsavel']) ? limparInput($_POST['contacto_responsavel']) : null,
            isset($_POST['acesso_publico']) ? 1 : 0,
            !empty($_POST['horario_visita']) ? limparInput($_POST['horario_visita']) : null,
            !empty($_POST['restricoes_acesso']) ? limparInput($_POST['restricoes_acesso']) : null,
            !empty($_POST['ameacas_identificadas']) ? limparInput($_POST['ameacas_identificadas']) : null,
            !empty($_POST['nivel_risco']) ? limparInput($_POST['nivel_risco']) : 'baixo',
            !empty($_POST['medidas_protecao']) ? limparInput($_POST['medidas_protecao']) : null,
            !empty($_POST['classificacao_oficial']) ? limparInput($_POST['classificacao_oficial']) : 'sem_classificacao',
            !empty($_POST['data_classificacao']) ? $_POST['data_classificacao'] : null,
            !empty($_POST['entidade_classificadora']) ? limparInput($_POST['entidade_classificadora']) : null,
            (int)$_SESSION['usuario_id']
        ]);

        if ($resultado) {
            $mensagem = 'Património adicionado com sucesso!';
            $tipo_mensagem = 'sucesso';
        } else {
            // Se houve erro na inserção e uma foto foi feita upload, eliminar a foto
            if ($foto_nome && file_exists('uploads/patrimonio/fotos/' . $foto_nome)) {
                unlink('uploads/patrimonio/fotos/' . $foto_nome);
            }
            throw new Exception("Erro ao executar a query de inserção.");
        }

    } catch (Exception $e) {
        $mensagem = 'Erro ao adicionar património: ' . $e->getMessage();
        $tipo_mensagem = 'erro';
        // Log do erro para depuração
        error_log("Erro no cadastro de património: " . $e->getMessage());
    }
}

// Eliminar património
if ($acao === 'eliminar' && isset($_GET['id'])) {
    try {
        // Buscar foto antes de eliminar para poder remover do servidor
        $stmt = $pdo->prepare("SELECT foto_principal FROM patrimonio WHERE id = ?");
        $stmt->execute([(int)$_GET['id']]);
        $patrimonio = $stmt->fetch();
        
        // Eliminar o registro
        $stmt = $pdo->prepare("DELETE FROM patrimonio WHERE id = ?");
        $stmt->execute([(int)$_GET['id']]);
        
        // Eliminar foto do servidor se existir
        if ($patrimonio && $patrimonio['foto_principal'] && 
            file_exists('uploads/patrimonio/fotos/' . $patrimonio['foto_principal'])) {
            unlink('uploads/patrimonio/fotos/' . $patrimonio['foto_principal']);
        }
        
        $mensagem = 'Património eliminado com sucesso!';
        $tipo_mensagem = 'sucesso';
    } catch (Exception $e) {
        $mensagem = 'Erro ao eliminar património: ' . $e->getMessage();
        $tipo_mensagem = 'erro';
    }
}

// Buscar dados para a listagem (incluindo a foto)
$filtro = $_GET['filtro'] ?? '';
$categoria_filtro = $_GET['categoria'] ?? '';
$tipo_filtro = $_GET['tipo'] ?? '';

$sql = "SELECT p.*, c.nome as categoria_nome, u.nome as registado_por_nome 
        FROM patrimonio p 
        LEFT JOIN categorias_patrimonio c ON p.categoria_id = c.id
        LEFT JOIN usuarios u ON p.registado_por = u.id
        WHERE 1=1";
$params = [];

if (!empty($filtro)) {
    $sql .= " AND (p.nome LIKE ? OR p.descricao LIKE ? OR p.provincia LIKE ?)";
    $like_filtro = "%$filtro%";
    $params[] = $like_filtro;
    $params[] = $like_filtro;
    $params[] = $like_filtro;
}

if (!empty($categoria_filtro)) {
    $sql .= " AND p.categoria_id = ?";
    $params[] = (int)$categoria_filtro;
}

if (!empty($tipo_filtro)) {
    $sql .= " AND p.tipo_patrimonio = ?";
    $params[] = $tipo_filtro;
}

$sql .= " ORDER BY p.data_registo DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$patrimonios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar categorias para os filtros e formulário
$stmt = $pdo->query("SELECT * FROM categorias_patrimonio ORDER BY nome");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas
$stats = [];
$stmt = $pdo->query("SELECT COUNT(*) as total FROM patrimonio WHERE status = 'ativo'");
$stats['total'] = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) as material FROM patrimonio WHERE tipo_patrimonio = 'material' AND status = 'ativo'");
$stats['material'] = $stmt->fetch()['material'] ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) as imaterial FROM patrimonio WHERE tipo_patrimonio = 'imaterial' AND status = 'ativo'");
$stats['imaterial'] = $stmt->fetch()['imaterial'] ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) as critico FROM patrimonio WHERE nivel_risco = 'critico' AND status = 'ativo'");
$stats['risco_critico'] = $stmt->fetch()['critico'] ?? 0;
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Património - <?php echo SITE_TITLE; ?></title>
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
            gap: 1rem;
        }

        .nav-links a {
            text-decoration: none;
            color: #666;
            padding: 8px 15px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            background: #667eea;
            color: white;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 100px 20px 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-card.total .stat-number { color: #2196F3; }
        .stat-card.material .stat-number { color: #4CAF50; }
        .stat-card.imaterial .stat-number { color: #FF9800; }
        .stat-card.risco .stat-number { color: #f44336; }

        .main-content {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .content-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .content-body {
            padding: 2rem;
        }

        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-danger {
            background: #f44336;
            color: white;
        }

        .btn-danger:hover {
            background: #d32f2f;
        }

        .filters {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }

        select, input[type="text"] {
            padding: 8px 12px;
            border: 2px solid #ddd;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        select:focus, input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
        }

        .table-container {
            overflow-x: auto;
            margin-top: 2rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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

        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .badge-material { background: #e3f2fd; color: #1976d2; }
        .badge-imaterial { background: #fff3e0; color: #f57c00; }
        .badge-excelente { background: #e8f5e8; color: #2e7d32; }
        .badge-bom { background: #e3f2fd; color: #1976d2; }
        .badge-regular { background: #fff3e0; color: #f57c00; }
        .badge-mau { background: #ffebee; color: #d32f2f; }
        .badge-critico { background: #ffcdd2; color: #b71c1c; }

        /* Estilos para imagens na tabela */
        .patrimonio-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }

        .modal-content {
            position: relative;
            background: white;
            margin: 2% auto;
            width: 90%;
            max-width: 800px;
            border-radius: 20px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 1.5rem;
            border-radius: 20px 20px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .close {
            color: white;
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }

        .modal-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 1rem;
        }

        .form-group textarea {
            height: 100px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
        }

        /* Estilos para upload de foto */
        .upload-area {
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .upload-area:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }

        .upload-area.dragover {
            border-color: #667eea;
            background: #e3f2fd;
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
        }

        .file-input {
            position: absolute;
            left: -9999px;
        }

        .file-input-label {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-input-label:hover {
            background: #5a6fd8;
        }

        .preview-container {
            margin-top: 1rem;
        }

        .preview-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        .alert-sucesso {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-erro {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .actions-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .filters {
                flex-direction: column;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="logo">🏛️ Património Cultural</div>
            <div class="nav-links">
                <a href="admin.php">🏠 Painel</a>
                <a href="gestao_usuarios.php">👥 Usuários</a>
                <a href="gestao_patrimonio.php">🏛️ Património</a>
                <a href="?logout=1">🚪 Sair</a>
            </div>
        </nav>
    </header>

    <div class="container">
        <!-- Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div>Total de Património</div>
            </div>
            <div class="stat-card material">
                <div class="stat-number"><?php echo $stats['material']; ?></div>
                <div>Património Material</div>
            </div>
            <div class="stat-card imaterial">
                <div class="stat-number"><?php echo $stats['imaterial']; ?></div>
                <div>Património Imaterial</div>
            </div>
            <div class="stat-card risco">
                <div class="stat-number"><?php echo $stats['risco_critico']; ?></div>
                <div>Risco Crítico</div>
            </div>
        </div>

        <div class="main-content">
            <div class="content-header">
                <h1>🏛️ Gestão de Património Cultural</h1>
                <p>Gerir todo o catálogo de património cultural de Moçambique</p>
            </div>

            <div class="content-body">
                <?php if ($mensagem): ?>
                    <div class="alert alert-<?php echo $tipo_mensagem; ?>">
                        <?php echo $mensagem; ?>
                    </div>
                <?php endif; ?>

                <div class="actions-bar">
                    <button class="btn btn-primary" onclick="abrirModal()">
                        ➕ Adicionar Património
                    </button>

                    <form method="GET" class="filters">
                        <div class="filter-group">
                            <label>Pesquisar:</label>
                            <input type="text" name="filtro" value="<?php echo htmlspecialchars($filtro); ?>" placeholder="Nome, descrição, província...">
                        </div>
                        <div class="filter-group">
                            <label>Categoria:</label>
                            <select name="categoria">
                                <option value="">Todas</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id']; ?>" <?php echo $categoria_filtro == $categoria['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($categoria['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Tipo:</label>
                            <select name="tipo">
                                <option value="">Todos</option>
                                <option value="material" <?php echo $tipo_filtro == 'material' ? 'selected' : ''; ?>>Material</option>
                                <option value="imaterial" <?php echo $tipo_filtro == 'imaterial' ? 'selected' : ''; ?>>Imaterial</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">🔍 Filtrar</button>
                    </form>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>Código</th>
                                <th>Nome</th>
                                <th>Categoria</th>
                                <th>Tipo</th>
                                <th>Província</th>
                                <th>Estado</th>
                                <th>Registado</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($patrimonios)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 2rem; color: #666;">
                                        Nenhum património encontrado
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($patrimonios as $patrimonio): ?>
                                    <tr>
                                        <td>
                                            <?php if ($patrimonio['foto_principal'] && file_exists('uploads/patrimonio/fotos/' . $patrimonio['foto_principal'])): ?>
                                                <img src="uploads/patrimonio/fotos/<?php echo htmlspecialchars($patrimonio['foto_principal']); ?>" 
                                                     alt="<?php echo htmlspecialchars($patrimonio['nome']); ?>" 
                                                     class="patrimonio-thumb">
                                            <?php else: ?>
                                                <div style="width: 60px; height: 60px; background: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #999; font-size: 0.8rem;">
                                                    📷
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($patrimonio['codigo_registo']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($patrimonio['nome']); ?></td>
                                        <td><?php echo htmlspecialchars($patrimonio['categoria_nome'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $patrimonio['tipo_patrimonio']; ?>">
                                                <?php echo ucfirst($patrimonio['tipo_patrimonio']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($patrimonio['provincia'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $patrimonio['estado_conservacao']; ?>">
                                                <?php echo ucfirst($patrimonio['estado_conservacao']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($patrimonio['registado_por_nome']); ?></td>
                                        <td>
                                            <a href="ver_patrimonio.php?id=<?php echo $patrimonio['id']; ?>" class="btn" style="background: #2196F3; color: white; padding: 5px 10px; font-size: 0.8rem;">👁️ Ver</a>
                                            <a href="editar_patrimonio.php?id=<?php echo $patrimonio['id']; ?>" class="btn" style="background: #FF9800; color: white; padding: 5px 10px; font-size: 0.8rem;">✏️ Editar</a>
                                            <?php if ($_SESSION['usuario_tipo'] == 'admin'): ?>
                                                <a href="?acao=eliminar&id=<?php echo $patrimonio['id']; ?>" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;" onclick="return confirm('Tem certeza que deseja eliminar este património?')">🗑️ Eliminar</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para adicionar património -->
    <div id="modalPatrimonio" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>➕ Adicionar Novo Património</h2>
                <span class="close" onclick="fecharModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" id="formPatrimonio" enctype="multipart/form-data">
                    <input type="hidden" name="acao" value="adicionar">
                    
                    <!-- Upload de Foto -->
                    <h3 style="color: #667eea; margin-bottom: 1rem;">📸 Foto Principal</h3>
                    
                    <div class="form-group">
                        <label>Foto do Património</label>
                        <div class="upload-area" id="uploadArea">
                            <div class="file-input-wrapper">
                                <input type="file" name="foto_principal" id="foto_principal" class="file-input" accept="image/*">
                                <label for="foto_principal" class="file-input-label">
                                    📁 Escolher Foto
                                </label>
                            </div>
                            <p style="margin: 1rem 0 0; color: #666; font-size: 0.9rem;">
                                Arraste uma imagem aqui ou clique para seleccionar<br>
                                <small>Formatos aceites: JPEG, PNG, GIF (máx. 5MB)</small>
                            </p>
                            <div class="preview-container" id="previewContainer"></div>
                        </div>
                    </div>
                    
                    <!-- Informações Básicas -->
                    <h3 style="color: #667eea; margin: 2rem 0 1rem;">📝 Informações Básicas</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Código de Registo *</label>
                            <input type="text" name="codigo_registo" required placeholder="Ex: PCM-004-2024">
                        </div>
                        <div class="form-group">
                            <label>Nome *</label>
                            <input type="text" name="nome" required placeholder="Nome do património">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Categoria</label>
                            <select name="categoria_id">
                                <option value="">Selecione uma categoria</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id']; ?>">
                                        <?php echo htmlspecialchars($categoria['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tipo de Património *</label>
                            <select name="tipo_patrimonio" required onchange="toggleFields()">
                                <option value="">Selecione o tipo</option>
                                <option value="material">Material</option>
                                <option value="imaterial">Imaterial</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Descrição *</label>
                        <textarea name="descricao" required placeholder="Descrição detalhada do património"></textarea>
                    </div>

                    <!-- Localização -->
                    <h3 style="color: #667eea; margin: 2rem 0 1rem;">📍 Localização</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Província</label>
                            <select name="provincia">
                                <option value="">Selecione a província</option>
                                <option value="Maputo">Maputo</option>
                                <option value="Gaza">Gaza</option>
                                <option value="Inhambane">Inhambane</option>
                                <option value="Manica">Manica</option>
                                <option value="Sofala">Sofala</option>
                                <option value="Tete">Tete</option>
                                <option value="Zambézia">Zambézia</option>
                                <option value="Nampula">Nampula</option>
                                <option value="Cabo Delgado">Cabo Delgado</option>
                                <option value="Niassa">Niassa</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Distrito</label>
                            <input type="text" name="distrito" placeholder="Nome do distrito">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Localidade</label>
                            <input type="text" name="localidade" placeholder="Nome da localidade">
                        </div>
                        <div class="form-group">
                            <label>Coordenadas GPS</label>
                            <input type="text" name="coordenadas_gps" placeholder="Ex: -25.9692, 32.5732">
                        </div>
                    </div>

                    <!-- Dados Históricos -->
                    <h3 style="color: #667eea; margin: 2rem 0 1rem;">📚 Dados Históricos</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Período Histórico</label>
                            <input type="text" name="periodo_historico" placeholder="Ex: Século XVIII">
                        </div>
                        <div class="form-group">
                            <label>Data de Criação (aproximada)</label>
                            <input type="text" name="data_criacao_aproximada" placeholder="Ex: 1780-1790">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Origem</label>
                        <input type="text" name="origem" placeholder="Origem ou criador do património">
                    </div>

                    <!-- Estado de Conservação -->
                    <h3 style="color: #667eea; margin: 2rem 0 1rem;">🔧 Estado de Conservação</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Estado de Conservação *</label>
                            <select name="estado_conservacao" required>
                                <option value="">Selecione o estado</option>
                                <option value="excelente">Excelente</option>
                                <option value="bom">Bom</option>
                                <option value="regular">Regular</option>
                                <option value="mau">Mau</option>
                                <option value="critico">Crítico</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Nível de Risco</label>
                            <select name="nivel_risco">
                                <option value="baixo">Baixo</option>
                                <option value="medio">Médio</option>
                                <option value="alto">Alto</option>
                                <option value="critico">Crítico</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Observações sobre o Estado</label>
                        <textarea name="observacoes_estado" placeholder="Detalhes sobre o estado atual"></textarea>
                    </div>

                    <!-- Importância Cultural -->
                    <h3 style="color: #667eea; margin: 2rem 0 1rem;">🌟 Importância Cultural</h3>
                    
                    <div class="form-group">
                        <label>Significado Cultural</label>
                        <textarea name="significado_cultural" placeholder="Importância cultural e simbólica"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Valor Histórico</label>
                            <textarea name="valor_historico" placeholder="Importância histórica"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Relevância Comunitária</label>
                            <textarea name="relevancia_comunitaria" placeholder="Importância para a comunidade"></textarea>
                        </div>
                    </div>

                    <!-- Campos específicos para Património Material -->
                    <div id="camposMaterial" style="display: none;">
                        <h3 style="color: #667eea; margin: 2rem 0 1rem;">🏗️ Dados Técnicos (Património Material)</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Materiais de Construção</label>
                                <input type="text" name="materiais_construcao" placeholder="Ex: Pedra, madeira, ferro">
                            </div>
                            <div class="form-group">
                                <label>Técnicas Utilizadas</label>
                                <input type="text" name="tecnicas_utilizadas" placeholder="Técnicas de construção">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Dimensões</label>
                                <input type="text" name="dimensoes" placeholder="Ex: 20m x 15m x 8m">
                            </div>
                            <div class="form-group">
                                <label>Peso</label>
                                <input type="text" name="peso" placeholder="Peso estimado">
                            </div>
                        </div>
                    </div>

                    <!-- Campos específicos para Património Imaterial -->
                    <div id="camposImaterial" style="display: none;">
                        <h3 style="color: #667eea; margin: 2rem 0 1rem;">🎭 Dados do Património Imaterial</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Praticantes</label>
                                <input type="text" name="praticantes" placeholder="Quem pratica esta tradição">
                            </div>
                            <div class="form-group">
                                <label>Frequência da Prática</label>
                                <input type="text" name="frequencia_pratica" placeholder="Ex: Anualmente, mensalmente">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Rituais Associados</label>
                                <textarea name="rituais_associados" placeholder="Rituais e cerimónias relacionadas"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Conhecimentos Tradicionais</label>
                                <textarea name="conhecimentos_tradicionais" placeholder="Saberes e conhecimentos associados"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Propriedade e Gestão -->
                    <h3 style="color: #667eea; margin: 2rem 0 1rem;">👥 Propriedade e Gestão</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Proprietário</label>
                            <input type="text" name="proprietario" placeholder="Nome do proprietário">
                        </div>
                        <div class="form-group">
                            <label>Gestor Responsável</label>
                            <input type="text" name="gestor_responsavel" placeholder="Responsável pela gestão">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Contacto do Responsável</label>
                        <input type="text" name="contacto_responsavel" placeholder="Telefone ou email">
                    </div>

                    <!-- Acessibilidade -->
                    <h3 style="color: #667eea; margin: 2rem 0 1rem;">🚪 Acessibilidade</h3>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="acesso_publico" id="acesso_publico">
                            <label for="acesso_publico">Acesso Público</label>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Horário de Visita</label>
                            <input type="text" name="horario_visita" placeholder="Ex: 8h às 17h, Segunda a Sexta">
                        </div>
                        <div class="form-group">
                            <label>Restrições de Acesso</label>
                            <input type="text" name="restricoes_acesso" placeholder="Limitações ou requisitos">
                        </div>
                    </div>

                    <!-- Ameaças e Proteção -->
                    <h3 style="color: #667eea; margin: 2rem 0 1rem;">⚠️ Ameaças e Proteção</h3>
                    
                    <div class="form-group">
                        <label>Ameaças Identificadas</label>
                        <textarea name="ameacas_identificadas" placeholder="Riscos e ameaças ao património"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Medidas de Proteção</label>
                        <textarea name="medidas_protecao" placeholder="Ações para proteger o património"></textarea>
                    </div>

                    <!-- Classificação Oficial -->
                    <h3 style="color: #667eea; margin: 2rem 0 1rem;">🏅 Classificação Oficial</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Classificação</label>
                            <select name="classificacao_oficial">
                                <option value="sem_classificacao">Sem Classificação</option>
                                <option value="monumento_nacional">Monumento Nacional</option>
                                <option value="bem_interesse_cultural">Bem de Interesse Cultural</option>
                                <option value="bem_relevante">Bem Relevante</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Data da Classificação</label>
                            <input type="date" name="data_classificacao">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Entidade Classificadora</label>
                        <input type="text" name="entidade_classificadora" placeholder="Ex: Ministério da Cultura">
                    </div>

                    <div style="text-align: center; margin-top: 2rem;">
                        <button type="submit" class="btn btn-primary" style="padding: 12px 30px; font-size: 1.1rem;">
                            💾 Guardar Património
                        </button>
                        <button type="button" class="btn" style="background: #666; color: white; padding: 12px 30px; font-size: 1.1rem; margin-left: 1rem;" onclick="fecharModal()">
                            ❌ Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Funcionalidades do Modal
        function abrirModal() {
            document.getElementById('modalPatrimonio').style.display = 'block';
        }

        function fecharModal() {
            document.getElementById('modalPatrimonio').style.display = 'none';
            document.getElementById('formPatrimonio').reset();
            document.getElementById('camposMaterial').style.display = 'none';
            document.getElementById('camposImaterial').style.display = 'none';
            document.getElementById('previewContainer').innerHTML = '';
        }

        function toggleFields() {
            const tipo = document.querySelector('select[name="tipo_patrimonio"]').value;
            const camposMaterial = document.getElementById('camposMaterial');
            const camposImaterial = document.getElementById('camposImaterial');

            if (tipo === 'material') {
                camposMaterial.style.display = 'block';
                camposImaterial.style.display = 'none';
            } else if (tipo === 'imaterial') {
                camposMaterial.style.display = 'none';
                camposImaterial.style.display = 'block';
            } else {
                camposMaterial.style.display = 'none';
                camposImaterial.style.display = 'none';
            }
        }

        // Funcionalidades de Upload de Foto
        const uploadArea = document.getElementById('uploadArea');
        const fotoInput = document.getElementById('foto_principal');
        const previewContainer = document.getElementById('previewContainer');

        // Drag and Drop
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fotoInput.files = files;
                previewFoto(files[0]);
            }
        });

        // Preview da foto quando selecionada
        fotoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                previewFoto(file);
            }
        });

        function previewFoto(file) {
            // Validar tipo
            if (!file.type.match('image.*')) {
                alert('Por favor, selecione apenas arquivos de imagem.');
                return;
            }

            // Validar tamanho (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('Arquivo muito grande. Máximo de 5MB permitido.');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                previewContainer.innerHTML = `
                    <div style="text-align: center;">
                        <img src="${e.target.result}" class="preview-image" alt="Preview">
                        <p style="margin-top: 0.5rem; color: #666; font-size: 0.9rem;">
                            ${file.name} (${Math.round(file.size / 1024)} KB)
                        </p>
                    </div>
                `;
            };
            reader.readAsDataURL(file);
        }

        // Fechar modal ao clicar fora dele
        window.onclick = function(event) {
            const modal = document.getElementById('modalPatrimonio');
            if (event.target == modal) {
                fecharModal();
            }
        }

        // Validação do formulário
        document.getElementById('formPatrimonio').addEventListener('submit', function(e) {
            const codigo = document.querySelector('input[name="codigo_registo"]').value;
            const nome = document.querySelector('input[name="nome"]').value;
            const tipo = document.querySelector('select[name="tipo_patrimonio"]').value;
            const descricao = document.querySelector('textarea[name="descricao"]').value;
            const estado = document.querySelector('select[name="estado_conservacao"]').value;

            if (!codigo || !nome || !tipo || !descricao || !estado) {
                e.preventDefault();
                alert('Por favor, preencha todos os campos obrigatórios (*)');
                return false;
            }

            // Validar formato do código
            const formatoCodigo = /^PCM-\d{3}-\d{4}$/;
            if (!formatoCodigo.test(codigo)) {
                e.preventDefault();
                alert('O código de registo deve seguir o formato: PCM-XXX-YYYY (ex: PCM-004-2024)');
                return false;
            }
        });
    </script>

    <?php if (isset($_GET['logout'])): ?>
        <script>
            window.location.href = 'index.php';
        </script>
    <?php endif; ?>

</body>
</html>