<?php
include 'config.php';

// Verificar se foi passado um ID de categoria
$categoria_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($categoria_id <= 0) {
    header('Location: index.php');
    exit;
}

try {
    // Buscar informações da categoria
    $stmt = $pdo->prepare("SELECT * FROM categorias_patrimonio WHERE id = ?");
    $stmt->execute([$categoria_id]);
    $categoria = $stmt->fetch();
    
    if (!$categoria) {
        header('Location: index.php');
        exit;
    }
    
    // Buscar patrimônios desta categoria (apenas públicos e ativos)
    $stmt = $pdo->prepare("SELECT p.*, c.nome as categoria_nome 
                          FROM patrimonio p 
                          LEFT JOIN categorias_patrimonio c ON p.categoria_id = c.id 
                          WHERE p.categoria_id = ? AND p.status = 'ativo' AND p.acesso_publico = 1
                          ORDER BY p.data_registo DESC");
    $stmt->execute([$categoria_id]);
    $patrimonios = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $categoria = null;
    $patrimonios = [];
}

// Função para extrair primeira foto do JSON
function getPrimeiraFoto($fotografias_json, $foto_principal = null) {
    // Primeiro tentar a foto principal
    if ($foto_principal && file_exists('uploads/fotos/' . $foto_principal)) {
        return 'uploads/fotos/' . $foto_principal;
    }
    
    // Se não houver foto principal, tentar o JSON de fotografias
    if (empty($fotografias_json)) return null;
    
    $fotos = json_decode($fotografias_json, true);
    
    if (!empty($fotos) && is_array($fotos)) {
        foreach ($fotos as $foto) {
            if (isset($foto['caminho']) && file_exists($foto['caminho'])) {
                return $foto['caminho'];
            }
        }
    }
    
    return null;
}

// Ícones para cada categoria
$icones_categoria = [
    1 => '🏛️',   // Monumentos Históricos
    2 => '🎨',    // Arte e Artesanato
    3 => '🎵',    // Música e Dança
    4 => '📚',    // Línguas e Literatura
    5 => '🗣️',    // Tradições Orais
    6 => '🏘️',    // Arquitectura Tradicional
    7 => '🎹',    // Instrumentos Musicais
    8 => '🕯️',    // Rituais e Cerimónias
    9 => '🍲',    // Gastronomia Tradicional
    10 => '🌿',   // Medicina Tradicional
    11 => '⚽',   // Jogos e Desportos Tradicionais
    12 => '🎉',   // Festivais e Celebrações
    13 => '⛏️',   // Sítios Arqueológicos
    14 => '🏗️',   // Património Colonial
    15 => '🗿'    // Arte Rupestre
];

$icone_categoria = isset($icones_categoria[$categoria_id]) ? $icones_categoria[$categoria_id] : '🏛️';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $categoria ? htmlspecialchars($categoria['nome']) : 'Categoria'; ?> - Património Cultural</title>
    <meta name="description" content="<?php echo $categoria ? htmlspecialchars($categoria['descricao']) : 'Explore o património cultural de Moçambique'; ?>">
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

        .back-btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            color: white;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .breadcrumb {
            background: rgba(255, 255, 255, 0.9);
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            font-size: 0.9rem;
            color: #666;
        }

        .breadcrumb a {
            color: #764ba2;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .category-header {
            background: rgba(255, 255, 255, 0.95);
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 3rem;
            text-align: center;
        }

        .category-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .category-header h1 {
            font-size: 2.5rem;
            color: #764ba2;
            margin-bottom: 1rem;
        }

        .category-description {
            font-size: 1.1rem;
            color: #666;
            line-height: 1.6;
            margin-bottom: 1.5rem;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .category-stats {
            display: inline-flex;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 500;
        }

        .patrimonios-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .section-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .section-header h2 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .section-subtitle {
            opacity: 0.9;
            font-size: 1rem;
        }

        .patrimonios-grid {
            padding: 2rem;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }

        .patrimonio-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .patrimonio-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }

        .patrimonio-image {
            height: 220px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #adb5bd;
            position: relative;
            overflow: hidden;
        }

        .patrimonio-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .patrimonio-card:hover .patrimonio-image img {
            transform: scale(1.05);
        }

        .patrimonio-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(transparent 60%, rgba(0,0,0,0.8));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .patrimonio-card:hover .patrimonio-overlay {
            opacity: 1;
        }

        .view-btn {
            position: absolute;
            bottom: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.9);
            color: #764ba2;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
        }

        .patrimonio-card:hover .view-btn {
            opacity: 1;
            transform: translateY(0);
        }

        .patrimonio-content {
            padding: 1.5rem;
        }

        .patrimonio-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #764ba2;
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }

        .patrimonio-location {
            color: #667eea;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .patrimonio-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .patrimonio-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
            gap: 1rem;
        }

        .tipo-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .tipo-material {
            background: #d4edda;
            color: #155724;
        }

        .tipo-imaterial {
            background: #fff3cd;
            color: #856404;
        }

        .estado-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            color: white;
            font-weight: 500;
        }

        .estado-excelente { background: #28a745; }
        .estado-bom { background: #17a2b8; }
        .estado-regular { background: #ffc107; color: #333; }
        .estado-mau { background: #fd7e14; }
        .estado-critico { background: #dc3545; }

        .no-patrimonios {
            text-align: center;
            padding: 4rem 2rem;
            color: #666;
        }

        .no-patrimonios-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .no-patrimonios h3 {
            margin-bottom: 1rem;
            color: #555;
        }

        .no-patrimonios p {
            margin-bottom: 0.5rem;
        }

        .footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 2rem;
            margin-top: 3rem;
        }

        .footer p {
            margin-bottom: 0.5rem;
        }

        @media (max-width: 768px) {
            .category-header {
                padding: 2rem;
            }

            .category-header h1 {
                font-size: 2rem;
            }

            .patrimonios-grid {
                grid-template-columns: 1fr;
                padding: 1rem;
                gap: 1.5rem;
            }

            .nav {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                gap: 1rem;
            }

            .patrimonio-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }

        .loading {
            display: none;
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 2s linear infinite;
            margin: 0 auto 1rem;
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
                <a href="index.php" class="back-btn">← Voltar</a>
            </div>
        </nav>
    </header>

    <div class="container">
        <div class="breadcrumb">
            <a href="index.php">Início</a> > 
            <a href="index.php#categorias">Categorias</a> > 
            <?php echo $categoria ? htmlspecialchars($categoria['nome']) : 'Categoria'; ?>
        </div>

        <?php if ($categoria): ?>
        <div class="category-header">
            <div class="category-icon"><?php echo $icone_categoria; ?></div>
            <h1><?php echo htmlspecialchars($categoria['nome']); ?></h1>
            <div class="category-description">
                <?php echo htmlspecialchars($categoria['descricao']); ?>
            </div>
            <div class="category-stats">
                <?php echo count($patrimonios); ?> património<?php echo count($patrimonios) != 1 ? 's' : ''; ?> disponível<?php echo count($patrimonios) != 1 ? 'eis' : ''; ?>
            </div>
        </div>

        <div class="patrimonios-section">
            <div class="section-header">
                <h2>Património Cultural desta Categoria</h2>
                <p class="section-subtitle">Clique em qualquer item para ver os detalhes completos</p>
            </div>

            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>Carregando detalhes...</p>
            </div>

            <?php if (!empty($patrimonios)): ?>
            <div class="patrimonios-grid">
                <?php foreach ($patrimonios as $patrimonio): ?>
                <div class="patrimonio-card" onclick="verDetalhes(<?php echo $patrimonio['id']; ?>)">
                    <div class="patrimonio-image">
                        <?php 
                        $primeira_foto = getPrimeiraFoto($patrimonio['fotografias'], $patrimonio['foto_principal']);
                        if ($primeira_foto): 
                        ?>
                            <img src="<?php echo htmlspecialchars($primeira_foto); ?>" 
                                 alt="<?php echo htmlspecialchars($patrimonio['nome']); ?>">
                        <?php else: ?>
                            <?php echo $icone_categoria; ?>
                        <?php endif; ?>
                        
                        <div class="patrimonio-overlay">
                            <div class="view-btn">Ver Detalhes</div>
                        </div>
                    </div>
                    
                    <div class="patrimonio-content">
                        <div class="patrimonio-title"><?php echo htmlspecialchars($patrimonio['nome']); ?></div>
                        
                        <?php if ($patrimonio['provincia'] || $patrimonio['distrito']): ?>
                        <div class="patrimonio-location">
                            <span>📍</span>
                            <?php 
                            $localizacao = [];
                            if ($patrimonio['distrito']) $localizacao[] = $patrimonio['distrito'];
                            if ($patrimonio['provincia']) $localizacao[] = $patrimonio['provincia'];
                            echo htmlspecialchars(implode(', ', $localizacao));
                            ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="patrimonio-description">
                            <?php echo htmlspecialchars(substr($patrimonio['descricao'], 0, 150)) . (strlen($patrimonio['descricao']) > 150 ? '...' : ''); ?>
                        </div>
                        
                        <div class="patrimonio-meta">
                            <span class="tipo-badge tipo-<?php echo $patrimonio['tipo_patrimonio']; ?>">
                                <?php echo ucfirst($patrimonio['tipo_patrimonio']); ?>
                            </span>
                            <span class="estado-badge estado-<?php echo $patrimonio['estado_conservacao']; ?>">
                                <?php echo ucfirst($patrimonio['estado_conservacao']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="no-patrimonios">
                <div class="no-patrimonios-icon"><?php echo $icone_categoria; ?></div>
                <h3>Nenhum património disponível</h3>
                <p>Ainda não há patrimónios públicos cadastrados nesta categoria.</p>
                <p>Volte em breve para descobrir novos conteúdos!</p>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="category-header">
            <div class="category-icon">❌</div>
            <h1>Categoria não encontrada</h1>
            <div class="category-description">
                A categoria solicitada não existe ou não está disponível.
            </div>
        </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <p>&copy; 2024 Património Cultural de Moçambique</p>
        <p>Preservando a nossa herança cultural para as futuras gerações</p>
    </footer>

    <script>
        // Função para ver detalhes do património - CORRIGIDA
        function verDetalhes(id) {
            // Mostrar indicador de carregamento
            const loading = document.getElementById('loading');
            if (loading) {
                loading.style.display = 'block';
            }
            
            // Redirecionar para a página de detalhes CORRETA
            setTimeout(() => {
                window.location.href = 'ver_patrimonio_visitante.php?id=' + id;
            }, 300);
        }

        // Animação suave ao carregar
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.patrimonio-card');
            
            // Observer para animação ao aparecer na tela
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0)';
                        }, index * 100);
                    }
                });
            }, {
                threshold: 0.1
            });
            
            // Configurar animação inicial
            cards.forEach((card) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(card);
            });

            // Animação da categoria header
            const categoryHeader = document.querySelector('.category-header');
            if (categoryHeader) {
                categoryHeader.style.opacity = '0';
                categoryHeader.style.transform = 'translateY(-30px)';
                categoryHeader.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
                
                setTimeout(() => {
                    categoryHeader.style.opacity = '1';
                    categoryHeader.style.transform = 'translateY(0)';
                }, 200);
            }
        });

        // Função para busca dinâmica (futura implementação)
        function filtrarPatrimonios(termo) {
            const cards = document.querySelectorAll('.patrimonio-card');
            cards.forEach(card => {
                const titulo = card.querySelector('.patrimonio-title').textContent.toLowerCase();
                const descricao = card.querySelector('.patrimonio-description').textContent.toLowerCase();
                const localizacao = card.querySelector('.patrimonio-location');
                const localizacaoTexto = localizacao ? localizacao.textContent.toLowerCase() : '';
                
                const termoBusca = termo.toLowerCase();
                
                if (titulo.includes(termoBusca) || descricao.includes(termoBusca) || localizacaoTexto.includes(termoBusca)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>