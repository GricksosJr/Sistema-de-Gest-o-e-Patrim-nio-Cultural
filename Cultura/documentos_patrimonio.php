<?php
include 'config.php';

// Verificar se está logado
if (!verificarLogin()) {
    redirecionar('login.php');
}

// Verificar se o ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirecionar('gestao_patrimonio.php?erro=ID não fornecido');
}

$patrimonio_id = (int)$_GET['id'];
$sucesso = '';
$erro = '';

// Buscar os dados do património
try {
    $stmt = $pdo->prepare("SELECT * FROM patrimonio WHERE id = ?");
    $stmt->execute([$patrimonio_id]);
    $patrimonio = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$patrimonio) {
        redirecionar('gestao_patrimonio.php?erro=Património não encontrado');
    }
} catch (PDOException $e) {
    $erro = "Erro ao buscar dados: " . $e->getMessage();
}

// Criar diretórios se não existirem
$upload_dirs = ['uploads/fotos/', 'uploads/videos/', 'uploads/documentos/', 'uploads/audios/'];
foreach ($upload_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Processar upload de arquivos
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tipo_arquivo = $_POST['tipo_arquivo'] ?? '';
    
    if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] == 0) {
        $file = $_FILES['arquivo'];
        $filename = $file['name'];
        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $filesize = $file['size'];
        $descricao = limparInput($_POST['descricao'] ?? '');
        
        // Definir tipos permitidos e diretório baseado no tipo
        $allowed_types = [];
        $upload_dir = '';
        $max_size = 0;
        
        switch ($tipo_arquivo) {
            case 'foto':
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $upload_dir = 'uploads/fotos/';
                $max_size = 5 * 1024 * 1024; // 5MB
                break;
            case 'video':
                $allowed_types = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'];
                $upload_dir = 'uploads/videos/';
                $max_size = 50 * 1024 * 1024; // 50MB
                break;
            case 'documento':
                $allowed_types = ['pdf', 'doc', 'docx', 'txt', 'odt', 'rtf'];
                $upload_dir = 'uploads/documentos/';
                $max_size = 10 * 1024 * 1024; // 10MB
                break;
            case 'audio':
                $allowed_types = ['mp3', 'wav', 'ogg', 'aac', 'm4a'];
                $upload_dir = 'uploads/audios/';
                $max_size = 20 * 1024 * 1024; // 20MB
                break;
            default:
                $erro = "Tipo de arquivo não especificado.";
        }
        
        if (empty($erro)) {
            // Validar tipo de arquivo
            if (!in_array($filetype, $allowed_types)) {
                $erro = "Tipo de arquivo não permitido. Tipos aceitos: " . implode(', ', $allowed_types);
            }
            // Validar tamanho
            elseif ($filesize > $max_size) {
                $erro = "Arquivo muito grande. Tamanho máximo: " . formatBytes($max_size);
            }
            else {
                // Gerar nome único
                $newname = $tipo_arquivo . '_' . $patrimonio_id . '_' . uniqid() . '.' . $filetype;
                $upload_path = $upload_dir . $newname;
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    // Adicionar ao JSON correspondente na base de dados
                    $campo_json = '';
                    switch ($tipo_arquivo) {
                        case 'foto': $campo_json = 'fotografias'; break;
                        case 'video': $campo_json = 'videos'; break;
                        case 'documento': $campo_json = 'documentos'; break;
                        case 'audio': $campo_json = 'gravacoes_audio'; break;
                    }
                    
                    // Buscar JSON atual
                    $stmt_json = $pdo->prepare("SELECT $campo_json FROM patrimonio WHERE id = ?");
                    $stmt_json->execute([$patrimonio_id]);
                    $current_json = $stmt_json->fetchColumn();
                    
                    $files_array = $current_json ? json_decode($current_json, true) : [];
                    if (!is_array($files_array)) $files_array = [];
                    
                    // Adicionar novo arquivo
                    $files_array[] = [
                        'nome_original' => $filename,
                        'nome_arquivo' => $newname,
                        'caminho' => $upload_path,
                        'tamanho' => $filesize,
                        'tipo' => $filetype,
                        'descricao' => $descricao,
                        'data_upload' => date('Y-m-d H:i:s'),
                        'usuario_upload' => $_SESSION['usuario_id']
                    ];
                    
                    // Atualizar na base de dados
                    $stmt_update = $pdo->prepare("UPDATE patrimonio SET $campo_json = ?, data_ultima_atualizacao = NOW() WHERE id = ?");
                    $stmt_update->execute([json_encode($files_array), $patrimonio_id]);
                    
                    // Registrar histórico
                    $stmt_hist = $pdo->prepare("INSERT INTO patrimonio_historico 
                                               (patrimonio_id, campo_alterado, valor_anterior, valor_novo, usuario_id, observacoes) 
                                               VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt_hist->execute([
                        $patrimonio_id, 
                        $campo_json, 
                        'Arquivo adicionado', 
                        $filename, 
                        $_SESSION['usuario_id'], 
                        "Upload de $tipo_arquivo: $filename"
                    ]);
                    
                    $sucesso = ucfirst($tipo_arquivo) . " carregado com sucesso!";
                } else {
                    $erro = "Erro ao carregar o arquivo.";
                }
            }
        }
    } else {
        $erro = "Nenhum arquivo selecionado ou erro no upload.";
    }
    
    // Recarregar dados
    $stmt = $pdo->prepare("SELECT * FROM patrimonio WHERE id = ?");
    $stmt->execute([$patrimonio_id]);
    $patrimonio = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Processar remoção de arquivo
if (isset($_GET['remover']) && isset($_GET['tipo'])) {
    $arquivo_index = (int)$_GET['remover'];
    $tipo_remover = $_GET['tipo'];
    
    $campo_json = '';
    switch ($tipo_remover) {
        case 'foto': $campo_json = 'fotografias'; break;
        case 'video': $campo_json = 'videos'; break;
        case 'documento': $campo_json = 'documentos'; break;
        case 'audio': $campo_json = 'gravacoes_audio'; break;
    }
    
    if ($campo_json) {
        try {
            $stmt_json = $pdo->prepare("SELECT $campo_json FROM patrimonio WHERE id = ?");
            $stmt_json->execute([$patrimonio_id]);
            $current_json = $stmt_json->fetchColumn();
            
            $files_array = $current_json ? json_decode($current_json, true) : [];
            
            if (isset($files_array[$arquivo_index])) {
                $arquivo_removido = $files_array[$arquivo_index];
                
                // Remover arquivo físico
                if (file_exists($arquivo_removido['caminho'])) {
                    unlink($arquivo_removido['caminho']);
                }
                
                // Remover do array
                unset($files_array[$arquivo_index]);
                $files_array = array_values($files_array); // Reindexar
                
                // Atualizar na base de dados
                $stmt_update = $pdo->prepare("UPDATE patrimonio SET $campo_json = ?, data_ultima_atualizacao = NOW() WHERE id = ?");
                $stmt_update->execute([json_encode($files_array), $patrimonio_id]);
                
                $sucesso = "Arquivo removido com sucesso!";
                
                // Recarregar dados
                $stmt = $pdo->prepare("SELECT * FROM patrimonio WHERE id = ?");
                $stmt->execute([$patrimonio_id]);
                $patrimonio = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            $erro = "Erro ao remover arquivo: " . $e->getMessage();
        }
    }
}

// Função para formatar bytes
function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}

// Função para obter ícone do arquivo
function getFileIcon($tipo) {
    switch ($tipo) {
        case 'jpg': case 'jpeg': case 'png': case 'gif': case 'webp':
            return '🖼️';
        case 'mp4': case 'avi': case 'mov': case 'wmv': case 'flv': case 'webm':
            return '🎥';
        case 'pdf':
            return '📄';
        case 'doc': case 'docx': case 'odt':
            return '📝';
        case 'mp3': case 'wav': case 'ogg': case 'aac': case 'm4a':
            return '🎵';
        default:
            return '📁';
    }
}

// Preparar arrays de arquivos
$fotografias = $patrimonio['fotografias'] ? json_decode($patrimonio['fotografias'], true) : [];
$videos = $patrimonio['videos'] ? json_decode($patrimonio['videos'], true) : [];
$documentos = $patrimonio['documentos'] ? json_decode($patrimonio['documentos'], true) : [];
$audios = $patrimonio['gravacoes_audio'] ? json_decode($patrimonio['gravacoes_audio'], true) : [];

if (!is_array($fotografias)) $fotografias = [];
if (!is_array($videos)) $videos = [];
if (!is_array($documentos)) $documentos = [];
if (!is_array($audios)) $audios = [];
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos do Património - <?php echo SITE_TITLE; ?></title>
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
            padding: 20px;
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
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .breadcrumb {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .breadcrumb a {
            color: white;
            text-decoration: none;
        }

        .content {
            padding: 2rem;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .upload-section {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            border: 2px dashed #dee2e6;
        }

        .upload-section h3 {
            color: #667eea;
            margin-bottom: 1rem;
            text-align: center;
        }

        .upload-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        select, input, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        select:focus, input:focus, textarea:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn:hover {
            background: #5a67d8;
            transform: translateY(-1px);
        }

        .btn-danger {
            background: #dc3545;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        .files-section {
            margin-bottom: 3rem;
        }

        .files-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e1e5e9;
        }

        .files-header h3 {
            color: #333;
            font-size: 1.3rem;
        }

        .file-count {
            background: #667eea;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .files-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
        }

        .file-card {
            background: white;
            border: 1px solid #e1e5e9;
            border-radius: 10px;
            padding: 1rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .file-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .file-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.5rem;
        }

        .file-icon {
            font-size: 2rem;
        }

        .file-name {
            font-weight: 600;
            color: #333;
            flex: 1;
            word-break: break-word;
        }

        .file-info {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .file-description {
            background: #f8f9fa;
            padding: 0.5rem;
            border-radius: 5px;
            font-style: italic;
            color: #666;
            margin-bottom: 1rem;
        }

        .file-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: space-between;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 0.8rem;
        }

        .image-preview {
            width: 100%;
            max-height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin: 0.5rem 0;
        }

        .empty-state {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 2rem;
            border: 1px dashed #ccc;
            border-radius: 10px;
            background: #f9f9f9;
        }

        .actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e1e5e9;
        }

        .progress-bar {
            display: none;
            width: 100%;
            height: 8px;
            background: #e1e5e9;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 1rem;
        }

        .progress-fill {
            height: 100%;
            background: #667eea;
            transition: width 0.3s ease;
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
            }
            
            .content {
                padding: 1rem;
            }
            
            .upload-form {
                grid-template-columns: 1fr;
            }
            
            .files-grid {
                grid-template-columns: 1fr;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📁 Documentos e Arquivos</h1>
            <div class="breadcrumb">
                <a href="gestao_patrimonio.php">← Lista de Património</a> |
                <a href="editar_patrimonio.php?id=<?php echo $patrimonio_id; ?>">Editar</a> |
                <strong><?php echo htmlspecialchars($patrimonio['nome']); ?></strong>
            </div>
        </div>

        <div class="content">
            <?php if ($sucesso): ?>
                <div class="alert alert-success"><?php echo $sucesso; ?></div>
            <?php endif; ?>
            
            <?php if ($erro): ?>
                <div class="alert alert-danger"><?php echo $erro; ?></div>
            <?php endif; ?>

            <!-- Seção de Upload -->
            <div class="upload-section">
                <h3>📤 Carregar Novo Arquivo</h3>
                <form method="POST" enctype="multipart/form-data" class="upload-form" id="uploadForm">
                    <div class="form-group">
                        <label>Tipo de Arquivo:</label>
                        <select name="tipo_arquivo" required>
                            <option value="">Selecione o tipo</option>
                            <option value="foto">🖼️ Fotografia</option>
                            <option value="video">🎥 Vídeo</option>
                            <option value="documento">📄 Documento</option>
                            <option value="audio">🎵 Áudio</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Arquivo:</label>
                        <input type="file" name="arquivo" required>
                    </div>
                    <div class="form-group">
                        <label>Descrição:</label>
                        <textarea name="descricao" rows="3" placeholder="Descrição opcional do arquivo"></textarea>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn">📤 Carregar Arquivo</button>
                    </div>
                </form>
                <div class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
            </div>

            <!-- Fotografias -->
            <div class="files-section">
                <div class="files-header">
                    <h3>🖼️ Fotografias</h3>
                    <span class="file-count"><?php echo count($fotografias); ?> arquivo(s)</span>
                </div>
                
                <?php if (empty($fotografias)): ?>
                    <div class="empty-state">
                        Nenhuma fotografia carregada ainda.
                    </div>
                <?php else: ?>
                    <div class="files-grid">
                        <?php foreach ($fotografias as $index => $foto): ?>
                            <div class="file-card">
                                <div class="file-header">
                                    <span class="file-icon"><?php echo getFileIcon($foto['tipo']); ?></span>
                                    <span class="file-name"><?php echo htmlspecialchars($foto['nome_original']); ?></span>
                                </div>
                                
                                <?php if (file_exists($foto['caminho'])): ?>
                                    <img src="<?php echo htmlspecialchars($foto['caminho']); ?>" alt="Preview" class="image-preview">
                                <?php endif; ?>
                                
                                <div class="file-info">
                                    <strong>Tamanho:</strong> <?php echo formatBytes($foto['tamanho']); ?><br>
                                    <strong>Upload:</strong> <?php echo date('d/m/Y H:i', strtotime($foto['data_upload'])); ?>
                                </div>
                                                                <?php if (!empty($foto['descricao'])): ?>
                                    <div class="file-description">
                                        <?php echo htmlspecialchars($foto['descricao']); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="file-actions">
                                    <a href="<?php echo htmlspecialchars($foto['caminho']); ?>" target="_blank" class="btn-secondary btn-small">🔍 Visualizar</a>
                                    <a href="?id=<?php echo $patrimonio_id; ?>&remover=<?php echo $index; ?>&tipo=foto" class="btn-danger btn-small" onclick="return confirm('Tem certeza que deseja remover esta foto?');">🗑️ Remover</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Vídeos -->
            <div class="files-section">
                <div class="files-header">
                    <h3>🎥 Vídeos</h3>
                    <span class="file-count"><?php echo count($videos); ?> arquivo(s)</span>
                </div>
                <?php if (empty($videos)): ?>
                    <div class="empty-state">Nenhum vídeo carregado ainda.</div>
                <?php else: ?>
                    <div class="files-grid">
                        <?php foreach ($videos as $index => $video): ?>
                            <div class="file-card">
                                <div class="file-header">
                                    <span class="file-icon"><?php echo getFileIcon($video['tipo']); ?></span>
                                    <span class="file-name"><?php echo htmlspecialchars($video['nome_original']); ?></span>
                                </div>
                                <video src="<?php echo htmlspecialchars($video['caminho']); ?>" controls style="width:100%; max-height:200px; border-radius:8px;"></video>
                                <div class="file-info">
                                    <strong>Tamanho:</strong> <?php echo formatBytes($video['tamanho']); ?><br>
                                    <strong>Upload:</strong> <?php echo date('d/m/Y H:i', strtotime($video['data_upload'])); ?>
                                </div>
                                <?php if (!empty($video['descricao'])): ?>
                                    <div class="file-description"><?php echo htmlspecialchars($video['descricao']); ?></div>
                                <?php endif; ?>
                                <div class="file-actions">
                                    <a href="<?php echo htmlspecialchars($video['caminho']); ?>" target="_blank" class="btn-secondary btn-small">🔍 Visualizar</a>
                                    <a href="?id=<?php echo $patrimonio_id; ?>&remover=<?php echo $index; ?>&tipo=video" class="btn-danger btn-small" onclick="return confirm('Tem certeza que deseja remover este vídeo?');">🗑️ Remover</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Documentos -->
            <div class="files-section">
                <div class="files-header">
                    <h3>📄 Documentos</h3>
                    <span class="file-count"><?php echo count($documentos); ?> arquivo(s)</span>
                </div>
                <?php if (empty($documentos)): ?>
                    <div class="empty-state">Nenhum documento carregado ainda.</div>
                <?php else: ?>
                    <div class="files-grid">
                        <?php foreach ($documentos as $index => $doc): ?>
                            <div class="file-card">
                                <div class="file-header">
                                    <span class="file-icon"><?php echo getFileIcon($doc['tipo']); ?></span>
                                    <span class="file-name"><?php echo htmlspecialchars($doc['nome_original']); ?></span>
                                </div>
                                <div class="file-info">
                                    <strong>Tamanho:</strong> <?php echo formatBytes($doc['tamanho']); ?><br>
                                    <strong>Upload:</strong> <?php echo date('d/m/Y H:i', strtotime($doc['data_upload'])); ?>
                                </div>
                                <div class="file-description"><?php echo htmlspecialchars($doc['descricao']); ?></div>
                                <div class="file-actions">
                                    <a href="<?php echo htmlspecialchars($doc['caminho']); ?>" target="_blank" class="btn-secondary btn-small">🔍 Visualizar</a>
                                    <a href="?id=<?php echo $patrimonio_id; ?>&remover=<?php echo $index; ?>&tipo=documento" class="btn-danger btn-small" onclick="return confirm('Tem certeza que deseja remover este documento?');">🗑️ Remover</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Áudios -->
            <div class="files-section">
                <div class="files-header">
                    <h3>🎧 Áudios</h3>
                    <span class="file-count"><?php echo count($audios); ?> arquivo(s)</span>
                </div>
                <?php if (empty($audios)): ?>
                    <div class="empty-state">Nenhuma gravação de áudio carregada ainda.</div>
                <?php else: ?>
                    <div class="files-grid">
                        <?php foreach ($audios as $index => $audio): ?>
                            <div class="file-card">
                                <div class="file-header">
                                    <span class="file-icon"><?php echo getFileIcon($audio['tipo']); ?></span>
                                    <span class="file-name"><?php echo htmlspecialchars($audio['nome_original']); ?></span>
                                </div>
                                <audio src="<?php echo htmlspecialchars($audio['caminho']); ?>" controls style="width:100%;"></audio>
                                <div class="file-info">
                                    <strong>Tamanho:</strong> <?php echo formatBytes($audio['tamanho']); ?><br>
                                    <strong>Upload:</strong> <?php echo date('d/m/Y H:i', strtotime($audio['data_upload'])); ?>
                                </div>
                                <?php if (!empty($audio['descricao'])): ?>
                                    <div class="file-description"><?php echo htmlspecialchars($audio['descricao']); ?></div>
                                <?php endif; ?>
                                <div class="file-actions">
                                    <a href="<?php echo htmlspecialchars($audio['caminho']); ?>" target="_blank" class="btn-secondary btn-small">🔍 Visualizar</a>
                                    <a href="?id=<?php echo $patrimonio_id; ?>&remover=<?php echo $index; ?>&tipo=audio" class="btn-danger btn-small" onclick="return confirm('Tem certeza que deseja remover este áudio?');">🗑️ Remover</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>