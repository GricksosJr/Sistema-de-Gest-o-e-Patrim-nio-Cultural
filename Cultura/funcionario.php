<?php
include 'config.php';

// Verificar se está logado e é funcionário
if (!verificarLogin() || $_SESSION['usuario_tipo'] != 'funcionario') {
    redirecionar('login.php');
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirecionar('index.php');
}

// Obter estatísticas
$stats = [
    'registos_criados' => 0,
    'documentos_enviados' => 0,
    'categorias_disponiveis' => 5,
    'itens_pendentes' => 0
];

try {
    // Contar registos criados pelo funcionário
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM patrimonio WHERE criado_por = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $stats['registos_criados'] = $stmt->fetch()['total'];

    // Contar documentos enviados pelo funcionário
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM documentos d JOIN patrimonio p ON d.patrimonio_id = p.id WHERE p.criado_por = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $stats['documentos_enviados'] = $stmt->fetch()['total'];

    // Contar categorias disponíveis
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM categorias WHERE ativo = 1");
    $stats['categorias_disponiveis'] = $stmt->fetch()['total'];

    // Contar itens pendentes de aprovação
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM patrimonio WHERE status = 'pendente' AND criado_por = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $stats['itens_pendentes'] = $stmt->fetch()['total'];

} catch(PDOException $e) {
    // Em caso de erro, manter valores padrão
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Funcionário - <?php echo SITE_TITLE; ?></title>
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

        .funcionario-badge {
            background: #4CAF50;
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

        .welcome-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
            margin-bottom: 3rem;
        }

        .welcome-icon {
            font-size: 4rem;
            color: #4CAF50;
            margin-bottom: 1rem;
        }

        .welcome-title {
            font-size: 2.5rem;
            color: #764ba2;
            margin-bottom: 1rem;
        }

        .welcome-subtitle {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 2rem;
        }

        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.9);
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #4CAF50;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .funcionario-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.9);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            text-align: center;
            cursor: pointer;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .feature-card.register { border-top: 4px solid #2196F3; }
        .feature-card.register .feature-icon { color: #2196F3; }

        .feature-card.search { border-top: 4px solid #FF9800; }
        .feature-card.search .feature-icon { color: #FF9800; }

        .feature-card.edit { border-top: 4px solid #9C27B0; }
        .feature-card.edit .feature-icon { color: #9C27B0; }

        .feature-card.documentation { border-top: 4px solid #607D8B; }
        .feature-card.documentation .feature-icon { color: #607D8B; }

        .feature-card h3 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }

        .feature-card p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .btn {
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 25px;
            display: inline-block;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn-blue { background: #2196F3; }
        .btn-blue:hover { background: #1976D2; }

        .btn-orange { background: #FF9800; }
        .btn-orange:hover { background: #F57C00; }

        .btn-purple { background: #9C27B0; }
        .btn-purple:hover { background: #7B1FA2; }

        .btn-gray { background: #607D8B; }
        .btn-gray:hover { background: #455A64; }

        @media (max-width: 768px) {
            .nav {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .user-info {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .welcome-section {
                padding: 2rem;
            }
            
            .welcome-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="logo">🏛️ Património Cultural - Funcionário</div>
            <div class="user-info">
                <div class="user-badge">
                    <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>
                    <span class="funcionario-badge">Funcionário</span>
                </div>
                <a href="?logout=1" class="logout-btn">Sair</a>
            </div>
        </nav>
    </header>

    <div class="container">
        <div class="welcome-section">
            <div class="welcome-icon">👨‍💼</div>
            <h1 class="welcome-title">Bem-vindo ao Sistema</h1>
            <p class="welcome-subtitle">
                <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong><br>
                Painel de Trabalho - Sistema de Gestão de Património Cultural
            </p>
        </div>

        <div class="stats-section">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['registos_criados']; ?></div>
                <div class="stat-label">Registos Criados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['documentos_enviados']; ?></div>
                <div class="stat-label">Documentos Enviados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['categorias_disponiveis']; ?></div>
                <div class="stat-label">Categorias Disponíveis</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['itens_pendentes']; ?></div>
                <div class="stat-label">Itens Pendentes</div>
            </div>
        </div>

        <div class="funcionario-features">
            <div class="feature-card register" onclick="window.location.href='registar_patrimonio.php'">
                <div class="feature-icon">📝</div>
                <h3>Registar Património</h3>
                <p>Criar novos registos de património cultural, incluindo monumentos, artefatos e manifestações culturais.</p>
                <a href="registar_patrimonio.php" class="btn btn-blue">📝 Registar Agora</a>
            </div>

            <div class="feature-card search" onclick="window.location.href='pesquisar_patrimonio.php'">
                <div class="feature-icon">🔍</div>
                <h3>Pesquisar e Consultar</h3>
                <p>Pesquisar no catálogo existente de património cultural e consultar informações detalhadas.</p>
                <a href="pesquisar_patrimonio.php" class="btn btn-orange">🔍 Pesquisar Agora</a>
            </div>

            <div class="feature-card edit" onclick="window.location.href='editar_patrimonio.php'">
                <div class="feature-icon">✏️</div>
                <h3>Editar Registos</h3>
                <p>Atualizar informações de registos existentes e adicionar novos dados ou documentação.</p>
                <a href="editar_patrimonio.php" class="btn btn-purple">✏️ Editar Agora</a>
            </div>

            <div class="feature-card documentation" onclick="window.location.href='documentos_patrimonio.php'">
                <div class="feature-icon">📚</div>
                <h3>Documentação</h3>
                <p>Anexar fotos, vídeos, documentos e outros materiais de apoio aos registos de património.</p>
                <a href="documentos_patrimonio.php" class="btn btn-gray">📚 Gerir Documentos</a>
            </div>
        </div>
    </div>
</body>
</html>