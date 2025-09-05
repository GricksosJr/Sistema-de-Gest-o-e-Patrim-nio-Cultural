<?php 
include 'config.php';

// Se já estiver logado, redirecionar
if (verificarLogin()) {
    if ($_SESSION['usuario_tipo'] == 'admin') {
        redirecionar('admin.php');
    } else {
        redirecionar('funcionario.php');
    }
}

$erro = '';

if ($_POST) {
    $email = limparInput($_POST['email']);
    $senha = limparInput($_POST['senha']);
    
    if (!empty($email) && !empty($senha)) {
        $senha_hash = md5($senha); // Em produção, use password_hash() e password_verify()
        
        $stmt = $pdo->prepare("SELECT id, nome, tipo_usuario FROM usuarios WHERE email = ? AND senha = ? AND ativo = 1");
        $stmt->execute([$email, $senha_hash]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_tipo'] = $usuario['tipo_usuario'];
            
            // Redirecionar conforme o tipo de usuário
            if ($usuario['tipo_usuario'] == 'admin') {
                redirecionar('admin.php');
            } else {
                redirecionar('funcionario.php');
            }
        } else {
            $erro = 'Email ou senha incorretos!';
        }
    } else {
        $erro = 'Por favor, preencha todos os campos!';
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_TITLE; ?></title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
            backdrop-filter: blur(10px);
        }

        .logo-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-icon {
            font-size: 4rem;
            color: #764ba2;
            margin-bottom: 0.5rem;
        }

        .logo-text {
            font-size: 1.5rem;
            color: #764ba2;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .logo-subtitle {
            color: #666;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e1e1;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.9);
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error-message {
            background: #ff6b6b;
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
            font-size: 0.9rem;
        }

        .back-link {
            text-align: center;
            margin-top: 2rem;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .back-link a:hover {
            color: #764ba2;
        }

        .demo-info {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
            color: #1565c0;
        }

        .demo-info strong {
            display: block;
            margin-bottom: 0.5rem;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 2rem;
            }
            
            .logo-icon {
                font-size: 3rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-section">
            <div class="logo-icon">🏛️</div>
            <div class="logo-text">Património Cultural</div>
            <div class="logo-subtitle">Sistema de Gestão</div>
        </div>

        <div class="demo-info">
            <strong>Dados de Demonstração:</strong>
            Admin: admin@patrimonio.gov.mz<br>
            Funcionário: joao.silva@patrimonio.gov.mz<br>
            Senha para ambos: <strong>123456</strong>
        </div>

        <?php if ($erro): ?>
            <div class="error-message"><?php echo $erro; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" class="form-control" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" class="form-control" required>
            </div>

            <button type="submit" class="btn-login">Entrar no Sistema</button>
        </form>

        <div class="back-link">
            <a href="index.php">← Voltar à página inicial</a>
        </div>
    </div>
</body>
</html>