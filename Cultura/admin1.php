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
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - <?php echo SITE_TITLE; ?></title>
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
            color: #667eea;
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

        .admin-features {
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
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .feature-card.users { border-top: 4px solid #4CAF50; }
        .feature-card.users .feature-icon { color: #4CAF50; }

        .feature-card.patrimony { border-top: 4px solid #2196F3; }
        .feature-card.patrimony .feature-icon { color: #2196F3; }

        .feature-card.reports { border-top: 4px solid #FF9800; }
        .feature-card.reports .feature-icon { color: #FF9800; }

        .feature-card.settings { border-top: 4px solid #9C27B0; }
        .feature-card.settings .feature-icon { color: #9C27B0; }

        .feature-card h3 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }

        .feature-card p {
            color: #666;
            line-height: 1.6;
        }

        .coming-soon {
            background: #f0f0f0;
            color: #999;
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-top: 1rem;
            display: inline-block;
        }

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
        <div class="welcome-section">
            <div class="welcome-icon">👑</div>
            <h1 class="welcome-title">Bem-vindo ao Sistema</h1>
            <p class="welcome-subtitle">
                <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong><br>
                Painel Administrativo do Sistema de Gestão de Património Cultural
            </p>
        </div>

        <div class="admin-features">
            <div class="feature-card users">
                <div class="feature-icon">👥</div>
                <h3>Gestão de Usuários</h3>
                <p>Gerir contas de administradores e funcionários, definir permissões e controlar acessos ao sistema.</p>
                <div class="coming-soon">Em desenvolvimento</div>
            </div>

            <div class="feature-card patrimony">
                <div class="feature-icon">🏛️</div>
                <h3>Gestão de Património</h3>
                <p>Administrar todo o catálogo de património cultural, aprovar novos registos e gerir categorias.</p>
                <div class="coming-soon">Em desenvolvimento</div>
            </div>

            <div class="feature-card reports">
                <div class="feature-icon">📊</div>
                <h3>Relatórios e Estatísticas</h3>
                <p>Visualizar relatórios detalhados sobre o património catalogado e estatísticas de uso do sistema.</p>
                <div class="coming-soon">Em desenvolvimento</div>
            </div>

            <div class="feature-card settings">
                <div class="feature-icon">⚙️</div>
                <h3>Configurações do Sistema</h3>
                <p>Configurar parâmetros gerais do sistema, backup de dados e configurações de segurança.</p>
                <div class="coming-soon">Em desenvolvimento</div>
            </div>
        </div>
    </div>
</body>
</html>