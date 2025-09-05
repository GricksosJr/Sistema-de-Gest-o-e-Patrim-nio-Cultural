<?php
include 'config.php';

// Verificar se o ID do património foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php?erro=' . urlencode('Património não encontrado.'));
    exit;
}

$patrimonio_id = (int)$_GET['id'];
$erro = '';

// Função auxiliar para formatar o tamanho dos ficheiros
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// Função para processar imagens do slideshow
function processarImagensSlideshow($patrimonio) {
    $imagens = [];
    
    // Adicionar foto principal se existir
    if (!empty($patrimonio['foto_principal'])) {
        $caminho_principal = 'uploads/fotos/' . $patrimonio['foto_principal'];
        if (file_exists($caminho_principal)) {
            $imagens[] = [
                'caminho' => $caminho_principal,
                'nome_original' => 'Imagem Principal',
                'descricao' => 'Fotografia principal de ' . $patrimonio['nome']
            ];
        }
    }
    
    // Adicionar fotografias do JSON
    if (!empty($patrimonio['fotografias'])) {
        $fotografias_json = json_decode($patrimonio['fotografias'], true);
        if (is_array($fotografias_json)) {
            foreach ($fotografias_json as $foto) {
                if (isset($foto['caminho']) && file_exists($foto['caminho'])) {
                    $imagens[] = $foto;
                }
            }
        }
    }
    
    return $imagens;
}

try {
    // Buscar apenas patrimónios com status 'ativo' para visitantes
    $stmt = $pdo->prepare("
        SELECT
            p.*,
            c.nome AS categoria_nome,
            u.nome AS usuario_nome
        FROM
            patrimonio p
        LEFT JOIN
            categorias_patrimonio c ON p.categoria_id = c.id
        LEFT JOIN
            usuarios u ON p.registado_por = u.id
        WHERE
            p.id = ? AND p.status = 'ativo'
    ");
    $stmt->execute([$patrimonio_id]);
    $patrimonio = $stmt->fetch(PDO::FETCH_ASSOC);

    // Se o património não for encontrado ou não estiver ativo, redirecionar
    if (!$patrimonio) {
        header('Location: index.php?erro=' . urlencode('Património não encontrado ou não disponível.'));
        exit;
    }

    // Processar imagens para o slideshow
    $imagens_slideshow = processarImagensSlideshow($patrimonio);

    // Decodificar os campos JSON para listar os ficheiros
    $fotografias = json_decode($patrimonio['fotografias'], true) ?? [];
    $videos = json_decode($patrimonio['videos'], true) ?? [];
    $documentos = json_decode($patrimonio['documentos'], true) ?? [];
    $gravacoes_audio = json_decode($patrimonio['gravacoes_audio'], true) ?? [];

} catch (PDOException $e) {
    $erro = "Erro ao carregar informações do património.";
    error_log("Erro na consulta: " . $e->getMessage());
}

// Ícones para categorias
$icones_categoria = [
    1 => '🏛️', 2 => '🎨', 3 => '🎵', 4 => '📚', 5 => '🗣️',
    6 => '🏘️', 7 => '🎹', 8 => '🕯️', 9 => '🍲', 10 => '🌿',
    11 => '⚽', 12 => '🎉', 13 => '⛏️', 14 => '🏗️', 15 => '🗿'
];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($patrimonio['nome'] ?? 'Património Cultural'); ?> - Património Cultural de Moçambique</title>
    <meta name="description" content="<?php echo htmlspecialchars(substr($patrimonio['descricao'] ?? '', 0, 160)); ?>">
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
            color: #333;
            line-height: 1.6;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
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
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        
        .nav-links a {
            color: #555;
            text-decoration: none;
            font-weight: 500;
            padding: 10px 20px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        
        .nav-links a:hover {
            color: #764ba2;
            background: rgba(118, 75, 162, 0.1);
        }

        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)),
                        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 600"><rect fill="%23667eea" width="1200" height="600"/><path fill="%23764ba2" d="M0,400 Q300,300 600,400 T1200,400 L1200,600 L0,600 Z"/></svg>');
            background-size: cover;
            background-position: center;
            padding: 4rem 2rem;
            text-align: center;
            color: white;
            position: relative;
        }

        .hero-content h1 {
            font-size: clamp(2rem, 5vw, 3.5rem);
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.7);
        }

        .hero-content p {
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.7);
            opacity: 0.95;
        }

        .hero-badges {
            margin-top: 2rem;
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .hero-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 25px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }

        .breadcrumb {
            margin-bottom: 2rem;
            font-size: 0.9rem;
            color: #666;
        }

        .breadcrumb a {
            color: #764ba2;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        /* SLIDESHOW MELHORADO */
        .slideshow-section {
            margin-bottom: 3rem;
        }

        .slideshow-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .slideshow-header h2 {
            color: #764ba2;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .slideshow-header p {
            color: #666;
            font-size: 1.1rem;
        }

        .slideshow-container {
            max-width: 1000px;
            position: relative;
            margin: 0 auto;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
            background: #000;
            overflow: hidden;
        }

        .slide {
            display: none;
            position: relative;
            text-align: center;
        }

        .slide.active {
            display: block;
            animation: slideIn 0.8s ease-in-out;
        }

        .slide img {
            width: 100%;
            height: 500px;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .slide:hover img {
            transform: scale(1.02);
        }

        .slide-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.9));
            color: white;
            padding: 2.5rem;
            text-align: left;
        }

        .slide-title {
            font-size: 1.4rem;
            font-weight: bold;
            margin-bottom: 0.8rem;
            color: #fff;
        }

        .slide-description {
            font-size: 1rem;
            opacity: 0.9;
            line-height: 1.5;
        }

        .slide-controls {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0,0,0,0.6);
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .slide-controls:hover {
            background: rgba(0,0,0,0.8);
            transform: translateY(-50%) scale(1.1);
        }

        .prev-slide {
            left: 20px;
        }

        .next-slide {
            right: 20px;
        }

        .slide-indicators {
            text-align: center;
            padding: 1.5rem 0;
            background: rgba(255, 255, 255, 0.95);
        }

        .indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #ddd;
            margin: 0 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .indicator.active,
        .indicator:hover {
            background: #667eea;
            transform: scale(1.3);
            box-shadow: 0 0 10px rgba(102, 126, 234, 0.5);
        }

        .slide-counter {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
        }

        .fullscreen-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(0,0,0,0.7);
            color: white;
            border: none;
            padding: 10px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .fullscreen-btn:hover {
            background: rgba(0,0,0,0.9);
            transform: scale(1.1);
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* Estilos das seções de informação */
        h2 {
            color: #555;
            margin-top: 3rem;
            margin-bottom: 1.5rem;
            border-bottom: 3px solid #667eea;
            padding-bottom: 0.5rem;
            font-size: 1.8rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-item {
            background: linear-gradient(135deg, #f8f9ff, #ffffff);
            padding: 2rem;
            border-radius: 15px;
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .info-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .info-item strong {
            color: #444;
            margin-bottom: 0.8rem;
            font-size: 1.1rem;
            display: block;
        }

        .info-item span {
            color: #666;
            line-height: 1.7;
        }

        .media-section {
            margin-top: 3rem;
        }

        .media-section h3 {
            color: #444;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }
        
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 1.5rem;
        }

        .media-item {
            background: #fff;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: all 0.3s ease;
        }

        .media-item:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }

        .btn {
            padding: 12px 25px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .back-btn-container {
            margin-bottom: 2rem;
        }

        .no-content {
            text-align: center;
            padding: 3rem;
            color: #666;
            font-style: italic;
            background: #f8f9fa;
            border-radius: 15px;
        }

        .footer {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            text-align: center;
            padding: 3rem 2rem;
            margin-top: 4rem;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2rem;
            }
            
            .nav {
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav-links {
                gap: 1rem;
            }
            
            .slideshow-container {
                margin: 1rem 0;
            }
            
            .slide img {
                height: 300px;
            }

            .slide-overlay {
                padding: 1.5rem;
            }
            
            .slide-controls {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }

            .prev-slide { left: 10px; }
            .next-slide { right: 10px; }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .media-grid {
                grid-template-columns: 1fr;
            }

            .hero-badges {
                flex-direction: column;
                align-items: center;
            }
        }

        /* Modo escuro para slideshow */
        .slideshow-container.fullscreen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 9999;
            border-radius: 0;
        }

        .loading-spinner {
            display: none;
            text-align: center;
            padding: 2rem;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav">
            <a href="index.php" class="logo">🏛️ Património Cultural</a>
            <div class="nav-links">
                <a href="index.php">Início</a>
                <a href="sobre.php">Sobre</a>
                <a href="contacto.php">Contacto</a>
            </div>
        </nav>
    </header>

    <?php if (!$erro && $patrimonio): ?>
    <section class="hero-section">
        <div class="hero-content">
            <h1><?php echo htmlspecialchars($patrimonio['nome']); ?></h1>
            <p><?php echo htmlspecialchars($patrimonio['categoria_nome'] ?? 'Património Cultural'); ?></p>
            <div class="hero-badges">
                <span class="hero-badge"><?php echo ucfirst($patrimonio['tipo_patrimonio']); ?></span>
                <span class="hero-badge"><?php echo ucfirst($patrimonio['estado_conservacao']); ?></span>
                <span class="hero-badge"><?php echo $patrimonio['provincia'] ?? 'Moçambique'; ?></span>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <div class="container">
        <div class="breadcrumb">
            <a href="index.php">Início</a> > 
            <a href="index.php#patrimonios">Património</a> > 
            <?php echo htmlspecialchars($patrimonio['nome'] ?? 'Detalhes'); ?>
        </div>
        
        <?php if ($erro): ?>
            <div class="alert error"><?php echo htmlspecialchars($erro); ?></div>
        <?php else: ?>

        <div class="back-btn-container">
            <a href="javascript:history.back()" class="btn btn-primary">← Voltar</a>
        </div>

        <!-- SLIDESHOW MELHORADO -->
        <?php if (!empty($imagens_slideshow)): ?>
        <div class="slideshow-section">
            <div class="slideshow-header">
                <h2>Galeria de Imagens</h2>
                <p>Explore as fotografias de <?php echo htmlspecialchars($patrimonio['nome']); ?></p>
            </div>
            
            <div class="slideshow-container" id="slideshow">
                <button class="fullscreen-btn" onclick="toggleFullscreen()">⛶</button>
                <div class="slide-counter">
                    <span id="current-slide">1</span> / <?php echo count($imagens_slideshow); ?>
                </div>

                <?php foreach ($imagens_slideshow as $index => $imagem): ?>
                <div class="slide <?php echo $index === 0 ? 'active' : ''; ?>">
                    <img src="<?php echo htmlspecialchars($imagem['caminho']); ?>" 
                         alt="<?php echo htmlspecialchars($imagem['descricao']); ?>"
                         loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>">
                    <div class="slide-overlay">
                        <div class="slide-title"><?php echo htmlspecialchars($imagem['nome_original']); ?></div>
                        <div class="slide-description"><?php echo htmlspecialchars($imagem['descricao']); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if (count($imagens_slideshow) > 1): ?>
                <button class="slide-controls prev-slide" onclick="changeSlide(-1)">‹</button>
                <button class="slide-controls next-slide" onclick="changeSlide(1)">›</button>
                <?php endif; ?>

                <div class="loading-spinner" id="loading">
                    <div class="spinner"></div>
                </div>
            </div>

            <?php if (count($imagens_slideshow) > 1): ?>
            <div class="slide-indicators">
                <?php for ($i = 0; $i < count($imagens_slideshow); $i++): ?>
                <span class="indicator <?php echo $i === 0 ? 'active' : ''; ?>" onclick="goToSlide(<?php echo $i; ?>)"></span>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="no-content">
            <h3>Sem imagens disponíveis</h3>
            <p>Ainda não há fotografias cadastradas para este património.</p>
        </div>
        <?php endif; ?>

        <!-- INFORMAÇÕES GERAIS -->
        <h2>Informação Geral</h2>
        <div class="info-grid">
            <div class="info-item">
                <strong>📋 Categoria</strong>
                <span><?php echo htmlspecialchars($patrimonio['categoria_nome'] ?? 'Não especificado'); ?></span>
            </div>
            <div class="info-item">
                <strong>🏛️ Tipo de Património</strong>
                <span><?php echo htmlspecialchars(ucfirst($patrimonio['tipo_patrimonio'] ?? 'Não especificado')); ?></span>
            </div>
            <div class="info-item">
                <strong>🔧 Estado de Conservação</strong>
                <span><?php echo htmlspecialchars(ucfirst($patrimonio['estado_conservacao'] ?? 'Não especificado')); ?></span>
            </div>
            <div class="info-item">
                <strong>⭐ Classificação Oficial</strong>
                <span><?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($patrimonio['classificacao_oficial'] ?? 'Não especificado'))); ?></span>
            </div>
            <?php if (!empty($patrimonio['descricao'])): ?>
            <div class="info-item" style="grid-column: 1 / -1;">
                <strong>📖 Descrição</strong>
                <span><?php echo nl2br(htmlspecialchars($patrimonio['descricao'])); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- LOCALIZAÇÃO -->
        <h2>Localização e Período Histórico</h2>
        <div class="info-grid">
            <div class="info-item">
                <strong>🗺️ Província</strong>
                <span><?php echo htmlspecialchars($patrimonio['provincia'] ?? 'Não especificado'); ?></span>
            </div>
            <div class="info-item">
                <strong>📍 Distrito</strong>
                <span><?php echo htmlspecialchars($patrimonio['distrito'] ?? 'Não especificado'); ?></span>
            </div>
            <div class="info-item">
                <strong>🏘️ Localidade</strong>
                <span><?php echo htmlspecialchars($patrimonio['localidade'] ?? 'Não especificado'); ?></span>
            </div>
            <div class="info-item">
                <strong>🌐 Coordenadas GPS</strong>
                <span><?php echo htmlspecialchars($patrimonio['coordenadas_gps'] ?? 'Não especificado'); ?></span>
            </div>
            <div class="info-item">
                <strong>⏳ Período Histórico</strong>
                <span><?php echo htmlspecialchars($patrimonio['periodo_historico'] ?? 'Não especificado'); ?></span>
            </div>
            <div class="info-item">
                <strong>📅 Data de Criação</strong>
                <span><?php echo htmlspecialchars($patrimonio['data_criacao_aproximada'] ?? 'Não especificado'); ?></span>
            </div>
        </div>

        <!-- SIGNIFICADO CULTURAL -->
        <?php if (!empty($patrimonio['significado_cultural']) || !empty($patrimonio['valor_historico']) || 
                   !empty($patrimonio['relevancia_comunitaria']) || !empty($patrimonio['conhecimentos_tradicionais'])): ?>
        <h2>Significado e Relevância Cultural</h2>
        <div class="info-grid">
            <?php if (!empty($patrimonio['significado_cultural'])): ?>
            <div class="info-item">
                <strong>🎭 Significado Cultural</strong>
                <span><?php echo nl2br(htmlspecialchars($patrimonio['significado_cultural'])); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($patrimonio['valor_historico'])): ?>
            <div class="info-item">
                <strong>📚 Valor Histórico</strong>
                <span><?php echo nl2br(htmlspecialchars($patrimonio['valor_historico'])); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($patrimonio['relevancia_comunitaria'])): ?>
            <div class="info-item">
                <strong>👥 Relevância Comunitária</strong>
                <span><?php echo nl2br(htmlspecialchars($patrimonio['relevancia_comunitaria'])); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($patrimonio['conhecimentos_tradicionais'])): ?>
            <div class="info-item">
                <strong>🧠 Conhecimentos Tradicionais</strong>
                <span><?php echo nl2br(htmlspecialchars($patrimonio['conhecimentos_tradicionais'])); ?></span>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- VÍDEOS -->
        <?php if (!empty($videos)): ?>
        <div class="media-section">
            <h2>🎥 Vídeos</h2>
            <div class="media-grid">
                <?php foreach ($videos as $video): ?>
                    <div class="media-item">
                        <video controls width="100%" src="<?php echo htmlspecialchars($video['caminho']); ?>"></video>
                        <strong><?php echo htmlspecialchars($video['nome_original']); ?></strong>
                        <small><?php echo htmlspecialchars($video['descricao']); ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

      

        <!-- DOCUMENTOS -->
        <?php if (!empty($documentos)): ?>
        <div class="media-section">
            <h2>📄 Documentos</h2>
            <div class="media-grid">
                <?php foreach ($documentos as $doc): ?>
                    <div class="media-item">
                        <strong><?php echo htmlspecialchars($doc['nome_original']); ?></strong>
                        <small><?php echo htmlspecialchars($doc['descricao']); ?></small>
                        <a href="<?php echo htmlspecialchars($doc['caminho']); ?>" target="_blank" class="btn btn-primary">Ver Documento</a>
                        <?php if (isset($doc['tamanho'])): ?>
                        <small>Tamanho: <?php echo formatBytes($doc['tamanho']); ?></small>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- GRAVAÇÕES DE ÁUDIO -->
        <?php if (!empty($gravacoes_audio)): ?>
        <div class="media-section">
            <h2>🎵 Gravações de Áudio</h2>
            <div class="media-grid">
                <?php foreach ($gravacoes_audio as $audio): ?>
                    <div class="media-item">
                        <audio controls style="width: 100%;">
                            <source src="<?php echo htmlspecialchars($audio['caminho']); ?>" type="audio/mpeg">
                            Seu navegador não suporta o elemento de áudio.
                        </audio>
                        <strong><?php echo htmlspecialchars($audio['nome_original']); ?></strong>
                        <small><?php echo htmlspecialchars($audio['descricao']); ?></small>
                        <?php if (isset($audio['tamanho'])): ?>
                        <small>Tamanho: <?php echo formatBytes($audio['tamanho']); ?></small>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>

    <footer class="footer">
        <p>&copy; 2024 Património Cultural de Moçambique</p>
        <p>Preservando a nossa herança cultural para as futuras gerações</p>
    </footer>

    <script>
        // Script para o slideshow de imagens
        let currentSlide = 0;
        const slides = document.querySelectorAll('.slide');
        const indicators = document.querySelectorAll('.indicator');
        const currentSlideElement = document.getElementById('current-slide');
        const slideshowContainer = document.getElementById('slideshow');
        let slideshowInterval;

        function showSlide(index) {
            // Esconder todos os slides
            slides.forEach(slide => slide.classList.remove('active'));
            indicators.forEach(indicator => indicator.classList.remove('active'));
            
            // Ajustar índice se necessário
            if (index >= slides.length) currentSlide = 0;
            if (index < 0) currentSlide = slides.length - 1;
            else currentSlide = index;
            
            // Mostrar slide atual
            slides[currentSlide].classList.add('active');
            if (indicators.length > 0) {
                indicators[currentSlide].classList.add('active');
            }
            
            // Atualizar contador
            if (currentSlideElement) {
                currentSlideElement.textContent = currentSlide + 1;
            }
        }

        function changeSlide(direction) {
            showSlide(currentSlide + direction);
            resetSlideshowInterval();
        }

        function goToSlide(index) {
            showSlide(index);
            resetSlideshowInterval();
        }

        function resetSlideshowInterval() {
            clearInterval(slideshowInterval);
            if (slides.length > 1) {
                slideshowInterval = setInterval(() => changeSlide(1), 5000);
            }
        }

        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                slideshowContainer.requestFullscreen().catch(err => {
                    console.error(`Error attempting to enable full-screen mode: ${err.message}`);
                });
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        }

        // Iniciar slideshow automático se houver mais de uma imagem
        if (slides.length > 1) {
            slideshowInterval = setInterval(() => changeSlide(1), 5000);
        }

        // Teclado: setas para navegar, ESC para sair do fullscreen
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') {
                changeSlide(-1);
            } else if (e.key === 'ArrowRight') {
                changeSlide(1);
            } else if (e.key === 'Escape' && document.fullscreenElement) {
                document.exitFullscreen();
            }
        });

        // Suporte a gestos em dispositivos touch
        let touchStartX = 0;
        let touchEndX = 0;

        slideshowContainer.addEventListener('touchstart', e => {
            touchStartX = e.changedTouches[0].screenX;
        }, false);

        slideshowContainer.addEventListener('touchend', e => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, false);

        function handleSwipe() {
            const minSwipeDistance = 50; // Distância mínima para considerar um swipe
            const distance = touchStartX - touchEndX;

            if (Math.abs(distance) < minSwipeDistance) return;

            if (distance > 0) {
                // Swipe para a esquerda - próxima imagem
                changeSlide(1);
            } else {
                // Swipe para a direita - imagem anterior
                changeSlide(-1);
            }
        }
    </script>
</body>
</html>