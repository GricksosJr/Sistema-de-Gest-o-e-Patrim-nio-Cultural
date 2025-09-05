<?php 
include 'config.php';

// Buscar patrimônios em destaque para exibição pública
try {
    // Patrimônios recentes para galeria
    $stmt = $pdo->query("SELECT p.id, p.nome, p.descricao, p.tipo_patrimonio, p.provincia, p.fotografias, c.nome as categoria 
                         FROM patrimonio p 
                         LEFT JOIN categorias_patrimonio c ON p.categoria_id = c.id 
                         WHERE p.status = 'ativo' AND p.acesso_publico = 1 
                         ORDER BY p.data_registo DESC 
                         LIMIT 6");
    $patrimonios_destaque = $stmt->fetchAll();
    
    // Estatísticas básicas para o público
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM patrimonio WHERE status = 'ativo'");
    $total_patrimonios = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT provincia) as total FROM patrimonio WHERE status = 'ativo' AND provincia IS NOT NULL");
    $total_provincias = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM categorias_patrimonio");
    $total_categorias = $stmt->fetch()['total'];
    
} catch(PDOException $e) {
    $patrimonios_destaque = [];
    $total_patrimonios = 0;
    $total_provincias = 0;
    $total_categorias = 0;
}

// Função para extrair primeira foto do JSON
function getPrimeiraFoto($fotografias_json) {
    if (empty($fotografias_json)) return null;
    $fotos = json_decode($fotografias_json, true);
    return !empty($fotos) && is_array($fotos) && isset($fotos[0]['caminho']) ? $fotos[0]['caminho'] : null;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_TITLE; ?></title>
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

        .login-btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 25px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 100px 20px 20px;
            text-align: center;
        }

        .hero {
            background: rgba(255, 255, 255, 0.9);
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 3rem;
        }

        .hero h1 {
            font-size: 3rem;
            color: #764ba2;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }

        .hero p {
            font-size: 1.2rem;
            color: #666;
            line-height: 1.8;
            margin-bottom: 2rem;
        }

        .stats-quick {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 1.5rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.25rem;
        }

        .gallery-section {
            background: rgba(255, 255, 255, 0.9);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 3rem;
        }

        .gallery-section h2 {
            color: #764ba2;
            margin-bottom: 2rem;
            font-size: 2rem;
        }

        .patrimonio-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .patrimonio-card-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .patrimonio-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .patrimonio-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        .patrimonio-image {
            height: 200px;
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
        }

        .patrimonio-content {
            padding: 1.5rem;
        }

        .patrimonio-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #764ba2;
            margin-bottom: 0.5rem;
        }

        .patrimonio-category {
            background: #667eea;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            display: inline-block;
            margin-bottom: 1rem;
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
            font-size: 0.9rem;
            color: #888;
        }

        .tipo-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
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

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .feature-card-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.9);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            height: 100%;
            cursor: pointer;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
            background: rgba(255, 255, 255, 1);
        }

        .card-arrow {
            position: absolute;
            bottom: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            color: #667eea;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .feature-card:hover .card-arrow {
            opacity: 1;
        }

        .feature-icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }

        .feature-card h3 {
            color: #764ba2;
            margin-bottom: 1rem;
        }

        .no-patrimonio {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 2rem;
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }
            
            .hero {
                padding: 2rem;
            }
            
            .nav {
                padding: 0 1rem;
            }

            .stats-quick {
                flex-direction: column;
                gap: 1rem;
            }

            .patrimonio-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="logo">🏛️ Património Cultural</div>
            <a href="login.php" class="login-btn">Acesso Restrito</a>
        </nav>
    </header>

    <div class="container">
        <div class="hero">
            <h1>Património Cultural de Moçambique</h1>
            <p>
                Explore e descubra a riqueza cultural de Moçambique. Conheça os monumentos, tradições, 
                arte e expressões culturais que formam a nossa identidade nacional.
            </p>
            
            <div class="stats-quick">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $total_patrimonios; ?></div>
                    <div class="stat-label">Patrimônios</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $total_provincias; ?></div>
                    <div class="stat-label">Províncias</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $total_categorias; ?></div>
                    <div class="stat-label">Categorias</div>
                </div>
            </div>
        </div>

        <?php if (!empty($patrimonios_destaque)): ?>
        <div class="gallery-section">
            <h2>🏛️ Patrimônios em Destaque</h2>
            <p>Conheça alguns dos tesouros culturais mais importantes do nosso país</p>
            
            <div class="patrimonio-grid">
                <?php foreach ($patrimonios_destaque as $patrimonio): ?>
                <a href="ver_patrimonio_visitante.php?id=<?php echo htmlspecialchars($patrimonio['id']); ?>" class="patrimonio-card-link">
                    <div class="patrimonio-card">
                        <div class="patrimonio-image">
                            <?php 
                            $primeira_foto = getPrimeiraFoto($patrimonio['fotografias']);
                            if ($primeira_foto && file_exists('uploads/fotos/' . $primeira_foto)): 
                            ?>
                                <img src="uploads/fotos/<?php echo htmlspecialchars($primeira_foto); ?>" 
                                     alt="<?php echo htmlspecialchars($patrimonio['nome']); ?>">
                            <?php else: ?>
                                🏛️
                            <?php endif; ?>
                        </div>
                        
                        <div class="patrimonio-content">
                            <div class="patrimonio-title"><?php echo htmlspecialchars($patrimonio['nome']); ?></div>
                            
                            <?php if ($patrimonio['categoria']): ?>
                            <div class="patrimonio-category"><?php echo htmlspecialchars($patrimonio['categoria']); ?></div>
                            <?php endif; ?>
                            
                            <div class="patrimonio-description">
                                <?php echo htmlspecialchars(substr($patrimonio['descricao'], 0, 150)) . '...'; ?>
                            </div>
                            
                            <div class="patrimonio-meta">
                                <span>
                                    <?php if ($patrimonio['provincia']): ?>
                                    📍 <?php echo htmlspecialchars($patrimonio['provincia']); ?>
                                    <?php endif; ?>
                                </span>
                                <span class="tipo-badge tipo-<?php echo $patrimonio['tipo_patrimonio']; ?>">
                                    <?php echo ucfirst($patrimonio['tipo_patrimonio']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="gallery-section">
            <h2>🏛️ Patrimônios em Destaque</h2>
            <div class="no-patrimonio">
                <p>Ainda não há patrimônios disponíveis para visualização pública.</p>
                <p>Em breve, novos conteúdos serão adicionados.</p>
            </div>
        </div>
        <?php endif; ?>

        <div class="features">
            <a href="categoria.php?id=1" class="feature-card-link">
                <div class="feature-card">
                    <div class="feature-icon">🏛️</div>
                    <h3>Monumentos Históricos</h3>
                    <p>Descubra fortalezas, edifícios históricos e marcos arquitetônicos que contam a história de Moçambique através dos séculos.</p>
                    <div class="card-arrow">→</div>
                </div>
            </a>

            <a href="categoria.php?id=2" class="feature-card-link">
                <div class="feature-card">
                    <div class="feature-icon">🎨</div>
                    <h3>Arte e Artesanato</h3>
                    <p>Explore a rica tradição artística moçambicana, desde esculturas tradicionais até obras de arte contemporânea.</p>
                    <div class="card-arrow">→</div>
                </div>
            </a>

            <a href="categoria.php?id=3" class="feature-card-link">
                <div class="feature-card">
                    <div class="feature-icon">🎵</div>
                    <h3>Música e Dança</h3>
                    <p>Conheça as expressões musicais e dancísticas que celebram a diversidade cultural das diferentes regiões do país.</p>
                    <div class="card-arrow">→</div>
                </div>
            </a>

            <a href="categoria.php?id=4" class="feature-card-link">
                <div class="feature-card">
                    <div class="feature-icon">📚</div>
                    <h3>Literatura e Tradições</h3>
                    <p>Mergulhe nas histórias orais, contos populares e tradições literárias que preservam a sabedoria ancestral.</p>
                    <div class="card-arrow">→</div>
                </div>
            </a>

            <a href="categoria.php?id=6" class="feature-card-link">
                <div class="feature-card">
                    <div class="feature-icon">🏘️</div>
                    <h3>Arquitectura Tradicional</h3>
                    <p>Conheça as habitações e construções tradicionais que refletem a identidade arquitectónica moçambicana.</p>
                    <div class="card-arrow">→</div>
                </div>
            </a>

            <a href="categoria.php?id=13" class="feature-card-link">
                <div class="feature-card">
                    <div class="feature-icon">⛏️</div>
                    <h3>Sítios Arqueológicos</h3>
                    <p>Explore locais de importância arqueológica que revelam a história antiga de Moçambique.</p>
                    <div class="card-arrow">→</div>
                </div>
            </a>
        </div>
    </div>

    <script>
        // Animação suave ao carregar
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.patrimonio-card, .feature-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 150);
            });
        });
    </script>
</body>
</html>
