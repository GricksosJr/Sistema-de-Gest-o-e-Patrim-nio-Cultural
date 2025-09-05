<?php
include 'config.php';

// Verificar se o utilizador está logado
if (!verificarLogin()) {
    redirecionar('login.php');
}

// Verificar se o ID do património foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirecionar('gestao_patrimonio.php?erro=' . urlencode('ID do património não fornecido.'));
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

try {
    // Buscar os dados do património, juntando com as tabelas de categorias e utilizadores
    $stmt = $pdo->prepare("
        SELECT
            p.*,
            c.nome AS categoria_nome,
            u.nome AS registado_por_nome
        FROM
            patrimonio p
        LEFT JOIN
            categorias_patrimonio c ON p.categoria_id = c.id
        LEFT JOIN
            usuarios u ON p.registado_por = u.id
        WHERE
            p.id = ?
    ");
    $stmt->execute([$patrimonio_id]);
    $patrimonio = $stmt->fetch(PDO::FETCH_ASSOC);

    // Se o património não for encontrado, redirecionar com uma mensagem de erro
    if (!$patrimonio) {
        redirecionar('gestao_patrimonio.php?erro=' . urlencode('Património não encontrado.'));
    }

    // Decodificar os campos JSON para listar os ficheiros
    $fotografias = json_decode($patrimonio['fotografias'], true) ?? [];
    $videos = json_decode($patrimonio['videos'], true) ?? [];
    $documentos = json_decode($patrimonio['documentos'], true) ?? [];
    $gravacoes_audio = json_decode($patrimonio['gravacoes_audio'], true) ?? [];

} catch (PDOException $e) {
    $erro = "Erro ao buscar dados do património: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Património - <?php echo htmlspecialchars($patrimonio['nome']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
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
            margin: 2rem auto;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        h1 {
            color: #764ba2;
            margin-bottom: 1rem;
            text-align: center;
        }

        h2 {
            color: #555;
            margin-top: 2rem;
            margin-bottom: 1rem;
            border-bottom: 2px solid #ddd;
            padding-bottom: 0.5rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-weight: bold;
            text-align: center;
        }

        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            background: #f9f9f9;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .info-item strong {
            color: #444;
            margin-bottom: 0.3rem;
        }

        .info-item span {
            color: #666;
        }

        .media-section {
            margin-top: 2rem;
        }

        /* Estilos do Slideshow */
        .slideshow-container {
            max-width: 800px;
            position: relative;
            margin: auto;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            background-color: #f0f0f0;
            padding: 1rem;
            margin-bottom: 2rem;
        }

        .mySlides {
            display: none;
            text-align: center;
        }

        .mySlides img {
            width: 100%;
            height: auto;
            border-radius: 8px;
        }

        .text {
            color: #555;
            font-size: 1rem;
            padding: 8px 12px;
            position: absolute;
            bottom: 8px;
            width: 100%;
            text-align: center;
            background-color: rgba(255, 255, 255, 0.7);
            border-radius: 0 0 8px 8px;
        }

        .prev, .next {
            cursor: pointer;
            position: absolute;
            top: 50%;
            width: auto;
            padding: 16px;
            margin-top: -22px;
            color: white;
            font-weight: bold;
            font-size: 18px;
            transition: 0.6s ease;
            border-radius: 0 3px 3px 0;
            user-select: none;
            background-color: rgba(0,0,0,0.5);
        }

        .next {
            right: 0;
            border-radius: 3px 0 0 3px;
        }

        .prev:hover, .next:hover {
            background-color: rgba(0,0,0,0.8);
        }

        .dot {
            cursor: pointer;
            height: 15px;
            width: 15px;
            margin: 0 2px;
            background-color: #bbb;
            border-radius: 50%;
            display: inline-block;
            transition: background-color 0.6s ease;
        }

        .active, .dot:hover {
            background-color: #717171;
        }

        .fade {
            animation-name: fade;
            animation-duration: 1.5s;
        }

        @keyframes fade {
            from {opacity: .4}
            to {opacity: 1}
        }
        
        .media-list {
            list-style: none;
            padding: 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .media-item {
            background: #fff;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .media-item img, .media-item video, .media-item audio {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }
        
        .media-item a {
            color: #667eea;
            text-decoration: none;
            font-weight: bold;
        }
        
        .btn {
            padding: 12px 25px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }

        .btn-secondary {
            background: #ccc;
            color: #333;
        }

        .btn-secondary:hover {
            background: #bbb;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .back-btn-container {
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .nav {
                flex-direction: column;
                gap: 1rem;
            }
            .user-info {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="logo">🏛️ Património Cultural - Admin</div>
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
        <div class="back-btn-container">
            <a href="gestao_patrimonio.php" class="btn btn-secondary">← Voltar</a>
        </div>
        
        <?php if ($erro): ?>
            <div class="alert error"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <h1>Detalhes do Património: <?php echo htmlspecialchars($patrimonio['nome']); ?></h1>
        
        <h2>Informação Geral</h2>
        <div class="info-grid">
            <div class="info-item">
                <strong>Código de Registo:</strong>
                <span><?php echo htmlspecialchars($patrimonio['codigo_registo'] ?? 'Não especificado'); ?></span>
            </div>
            <div class="info-item">
                <strong>Categoria:</strong>
                <span><?php echo htmlspecialchars($patrimonio['categoria_nome'] ?? 'Não especificado'); ?></span>
            </div>
            <div class="info-item">
                <strong>Tipo de Património:</strong>
                <span><?php echo htmlspecialchars(ucfirst($patrimonio['tipo_patrimonio'] ?? 'Não especificado')); ?></span>
            </div>
            <div class="info-item">
                <strong>Estado de Conservação:</strong>
                <span><?php echo htmlspecialchars(ucfirst($patrimonio['estado_conservacao'] ?? 'Não especificado')); ?></span>
            </div>
            <div class="info-item">
                <strong>Classificação Oficial:</strong>
                <span><?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($patrimonio['classificacao_oficial'] ?? 'Não especificado'))); ?></span>
            </div>
            <div class="info-item">
                <strong>Status:</strong>
                <span><?php echo htmlspecialchars(ucfirst($patrimonio['status'] ?? 'Não especificado')); ?></span>
            </div>
            <div class="info-item" style="grid-column: span 2;">
                <strong>Descrição:</strong>
                <span><?php echo nl2br(htmlspecialchars($patrimonio['descricao'] ?? 'Não especificado')); ?></span>
            </div>
        </div>

        <h2>Localização e Período Histórico</h2>
        <div class="info-grid">
            <div class="info-item">
                <strong>Província:</strong>
                <span><?php echo htmlspecialchars($patrimonio['provincia'] ?? 'Não especificado'); ?></span>
            </div>
            <div class="info-item">
                <strong>Distrito:</strong>
                <span><?php echo htmlspecialchars($patrimonio['distrito'] ?? 'Não especificado'); ?></span>
            </div>
            <div class="info-item">
                <strong>Localidade:</strong>
                <span><?php echo htmlspecialchars($patrimonio['localidade'] ?? 'Não especificado'); ?></span>
            </div>
            <div class="info-item">
                <strong>Coordenadas GPS:</strong>
                <span><?php echo htmlspecialchars($patrimonio['coordenadas_gps'] ?? 'Não especificado'); ?></span>
            </div>
            <div class="info-item">
                <strong>Período Histórico:</strong>
                <span><?php echo htmlspecialchars($patrimonio['periodo_historico'] ?? 'Não especificado'); ?></span>
            </div>
            <div class="info-item">
                <strong>Data de Criação (aproximada):</strong>
                <span><?php echo htmlspecialchars($patrimonio['data_criacao_aproximada'] ?? 'Não especificado'); ?></span>
            </div>
        </div>

        <h2>Significado e Relevância</h2>
        <div class="info-grid">
            <div class="info-item">
                <strong>Significado Cultural:</strong>
                <span><?php echo nl2br(htmlspecialchars($patrimonio['significado_cultural'] ?? 'Não especificado')); ?></span>
            </div>
            <div class="info-item">
                <strong>Valor Histórico:</strong>
                <span><?php echo nl2br(htmlspecialchars($patrimonio['valor_historico'] ?? 'Não especificado')); ?></span>
            </div>
            <div class="info-item">
                <strong>Relevância Comunitária:</strong>
                <span><?php echo nl2br(htmlspecialchars($patrimonio['relevancia_comunitaria'] ?? 'Não especificado')); ?></span>
            </div>
            <div class="info-item">
                <strong>Conhecimentos Tradicionais:</strong>
                <span><?php echo nl2br(htmlspecialchars($patrimonio['conhecimentos_tradicionais'] ?? 'Não especificado')); ?></span>
            </div>
        </div>

        <h2>Ficheiros Multimédia</h2>
        <div class="media-section">
            <h3>Fotos</h3>
            <?php if (empty($fotografias)): ?>
                <p>Nenhuma foto carregada.</p>
            <?php else: ?>
                <div class="slideshow-container">
                    <?php foreach ($fotografias as $foto): ?>
                        <div class="mySlides fade">
                            <img src="<?php echo htmlspecialchars($foto['caminho']); ?>" alt="<?php echo htmlspecialchars($foto['descricao']); ?>">
                            <div class="text">
                                <strong><?php echo htmlspecialchars($foto['nome_original']); ?></strong><br>
                                <small><?php echo htmlspecialchars($foto['descricao']); ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <a class="prev" onclick="plusSlides(-1)">&#10094;</a>
                    <a class="next" onclick="plusSlides(1)">&#10095;</a>
                </div>
                <br>
                <div style="text-align:center">
                    <?php for ($i = 1; $i <= count($fotografias); $i++): ?>
                        <span class="dot" onclick="currentSlide(<?php echo $i; ?>)"></span>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="media-section">
            <h3>Vídeos</h3>
            <ul class="media-list">
                <?php if (empty($videos)): ?>
                    <li>Nenhum vídeo carregado.</li>
                <?php else: ?>
                    <?php foreach ($videos as $video): ?>
                        <li class="media-item">
                            <video controls width="200" src="<?php echo htmlspecialchars($video['caminho']); ?>"></video>
                            <strong><?php echo htmlspecialchars($video['nome_original']); ?></strong>
                            <small><?php echo htmlspecialchars($video['descricao']); ?></small>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>

        <div class="media-section">
            <h3>Documentos</h3>
            <ul class="media-list">
                <?php if (empty($documentos)): ?>
                    <li>Nenhum documento carregado.</li>
                <?php else: ?>
                    <?php foreach ($documentos as $doc): ?>
                        <li class="media-item" style="border-left: 4px solid #F44336;">
                            <strong><?php echo htmlspecialchars($doc['nome_original']); ?></strong>
                            <small><?php echo htmlspecialchars($doc['descricao']); ?></small>
                            <a href="<?php echo htmlspecialchars($doc['caminho']); ?>" target="_blank">Ver Documento</a>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>

        <div class="media-section">
            <h3>Áudios</h3>
            <ul class="media-list">
                <?php if (empty($gravacoes_audio)): ?>
                    <li>Nenhum áudio carregado.</li>
                <?php else: ?>
                    <?php foreach ($gravacoes_audio as $audio): ?>
                        <li class="media-item" style="border-left: 4px solid #FFC107;">
                            <audio controls src="<?php echo htmlspecialchars($audio['caminho']); ?>"></audio>
                            <strong><?php echo htmlspecialchars($audio['nome_original']); ?></strong>
                            <small><?php echo htmlspecialchars($audio['descricao']); ?></small>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <script>
        let slideIndex = 0;
        let slideshowInterval;

        function showSlides() {
            let i;
            let slides = document.getElementsByClassName("mySlides");
            let dots = document.getElementsByClassName("dot");
            for (i = 0; i < slides.length; i++) {
                slides[i].style.display = "none";
            }
            slideIndex++;
            if (slideIndex > slides.length) { slideIndex = 1; }
            for (i = 0; i < dots.length; i++) {
                dots[i].className = dots[i].className.replace(" active", "");
            }
            if (slides.length > 0) {
                slides[slideIndex - 1].style.display = "block";
                dots[slideIndex - 1].className += " active";
            }
        }

        function plusSlides(n) {
            clearInterval(slideshowInterval);
            slideIndex += n;
            if (slideIndex > document.getElementsByClassName("mySlides").length) { slideIndex = 1; }
            if (slideIndex < 1) { slideIndex = document.getElementsByClassName("mySlides").length; }
            showSlidesManual();
            startSlideshow();
        }

        function currentSlide(n) {
            clearInterval(slideshowInterval);
            slideIndex = n;
            showSlidesManual();
            startSlideshow();
        }

        function showSlidesManual() {
            let i;
            let slides = document.getElementsByClassName("mySlides");
            let dots = document.getElementsByClassName("dot");
            for (i = 0; i < slides.length; i++) {
                slides[i].style.display = "none";
            }
            for (i = 0; i < dots.length; i++) {
                dots[i].className = dots[i].className.replace(" active", "");
            }
            if (slides.length > 0) {
                slides[slideIndex - 1].style.display = "block";
                dots[slideIndex - 1].className += " active";
            }
        }

        function startSlideshow() {
            slideshowInterval = setInterval(showSlides, 5000); // Mude a imagem a cada 5 segundos
        }

        window.onload = function() {
            showSlides();
            startSlideshow();
        };
    </script>
</body>
</html>