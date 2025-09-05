<?php
include 'config.php';

// Verificar se está logado e é admin
if (!verificarLogin() || $_SESSION['usuario_tipo'] != 'admin') {
    redirecionar('login.php');
}

// Processamento de ações
$mensagem = '';
$tipo_mensagem = '';

// Criar usuário
if (isset($_POST['criar'])) {
    try {
        $nome = limparInput($_POST['nome']);
        $email = limparInput($_POST['email']);
        $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT); // Segurança melhorada
        $tipo = $_POST['tipo'];

        // Verificar se email já existe
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetchColumn() > 0) {
            $mensagem = 'Este email já está em uso!';
            $tipo_mensagem = 'erro';
        } else {
            $sql = "INSERT INTO usuarios (nome, email, senha, tipo_usuario) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $email, $senha, $tipo]);
            $mensagem = 'Usuário criado com sucesso!';
            $tipo_mensagem = 'sucesso';
        }
    } catch (Exception $e) {
        $mensagem = 'Erro ao criar usuário: ' . $e->getMessage();
        $tipo_mensagem = 'erro';
    }
}

// Editar usuário
if (isset($_POST['editar'])) {
    try {
        $id = $_POST['id'];
        $nome = limparInput($_POST['nome']);
        $email = limparInput($_POST['email']);
        $tipo = $_POST['tipo'];
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        // Verificar se email já existe para outro usuário
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        
        if ($stmt->fetchColumn() > 0) {
            $mensagem = 'Este email já está em uso por outro usuário!';
            $tipo_mensagem = 'erro';
        } else {
            $sql = "UPDATE usuarios SET nome=?, email=?, tipo_usuario=?, ativo=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $email, $tipo, $ativo, $id]);
            $mensagem = 'Usuário atualizado com sucesso!';
            $tipo_mensagem = 'sucesso';
        }
    } catch (Exception $e) {
        $mensagem = 'Erro ao atualizar usuário: ' . $e->getMessage();
        $tipo_mensagem = 'erro';
    }
}

// Apagar usuário
if (isset($_GET['apagar'])) {
    try {
        $id = $_GET['apagar'];
        
        // Não permitir apagar o próprio usuário
        if ($id == $_SESSION['usuario_id']) {
            $mensagem = 'Não é possível apagar o seu próprio usuário!';
            $tipo_mensagem = 'erro';
        } else {
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id=?");
            $stmt->execute([$id]);
            $mensagem = 'Usuário eliminado com sucesso!';
            $tipo_mensagem = 'sucesso';
        }
    } catch (Exception $e) {
        $mensagem = 'Erro ao eliminar usuário: ' . $e->getMessage();
        $tipo_mensagem = 'erro';
    }
}

// Buscar todos usuários com filtros
$filtro = $_GET['filtro'] ?? '';
$tipo_filtro = $_GET['tipo'] ?? '';
$status_filtro = $_GET['status'] ?? '';

$sql = "SELECT * FROM usuarios WHERE 1=1";
$params = [];

if ($filtro) {
    $sql .= " AND (nome LIKE ? OR email LIKE ?)";
    $like_filtro = "%$filtro%";
    $params[] = $like_filtro;
    $params[] = $like_filtro;
}

if ($tipo_filtro) {
    $sql .= " AND tipo_usuario = ?";
    $params[] = $tipo_filtro;
}

if ($status_filtro !== '') {
    $sql .= " AND ativo = ?";
    $params[] = $status_filtro;
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas
$stats = [];
$stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE ativo = 1");
$stats['total_ativos'] = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as admins FROM usuarios WHERE tipo_usuario = 'admin' AND ativo = 1");
$stats['admins'] = $stmt->fetch()['admins'];

$stmt = $pdo->query("SELECT COUNT(*) as funcionarios FROM usuarios WHERE tipo_usuario = 'funcionario' AND ativo = 1");
$stats['funcionarios'] = $stmt->fetch()['funcionarios'];

$stmt = $pdo->query("SELECT COUNT(*) as inativos FROM usuarios WHERE ativo = 0");
$stats['inativos'] = $stmt->fetch()['inativos'];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Usuários - <?php echo SITE_TITLE; ?></title>
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
        .stat-card.admins .stat-number { color: #f44336; }
        .stat-card.funcionarios .stat-number { color: #4CAF50; }
        .stat-card.inativos .stat-number { color: #FF9800; }

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

        .btn-success {
            background: #4CAF50;
            color: white;
        }

        .btn-success:hover {
            background: #45a049;
        }

        .btn-danger {
            background: #f44336;
            color: white;
        }

        .btn-danger:hover {
            background: #d32f2f;
        }

        .btn-small {
            padding: 5px 10px;
            font-size: 0.8rem;
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

        select, input[type="text"], input[type="email"], input[type="password"] {
            padding: 8px 12px;
            border: 2px solid #ddd;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        select:focus, input:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .form-group label {
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select {
            width: 100%;
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

        .badge-admin { background: #ffebee; color: #d32f2f; }
        .badge-funcionario { background: #e3f2fd; color: #1976d2; }
        .badge-ativo { background: #e8f5e8; color: #2e7d32; }
        .badge-inativo { background: #fff3e0; color: #f57c00; }

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
            margin: 5% auto;
            width: 90%;
            max-width: 600px;
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

        .edit-form {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin-top: 1rem;
        }

        .edit-form input,
        .edit-form select {
            margin-bottom: 0.5rem;
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
        }

        @media (max-width: 768px) {
            .actions-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .filters {
                flex-direction: column;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .nav-links {
                flex-direction: column;
                gap: 0.5rem;
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
                <div class="stat-number"><?php echo $stats['total_ativos']; ?></div>
                <div>Usuários Ativos</div>
            </div>
            <div class="stat-card admins">
                <div class="stat-number"><?php echo $stats['admins']; ?></div>
                <div>Administradores</div>
            </div>
            <div class="stat-card funcionarios">
                <div class="stat-number"><?php echo $stats['funcionarios']; ?></div>
                <div>Funcionários</div>
            </div>
            <div class="stat-card inativos">
                <div class="stat-number"><?php echo $stats['inativos']; ?></div>
                <div>Usuários Inativos</div>
            </div>
        </div>

        <div class="main-content">
            <div class="content-header">
                <h1>👥 Gestão de Usuários</h1>
                <p>Gerir contas de administradores e funcionários do sistema</p>
            </div>

            <div class="content-body">
                <?php if ($mensagem): ?>
                    <div class="alert alert-<?php echo $tipo_mensagem; ?>">
                        <?php echo $mensagem; ?>
                    </div>
                <?php endif; ?>

                <div class="actions-bar">
                    <button class="btn btn-primary" onclick="abrirModal()">
                        ➕ Adicionar Usuário
                    </button>

                    <form method="GET" class="filters">
                        <div class="filter-group">
                            <label>Pesquisar:</label>
                            <input type="text" name="filtro" value="<?php echo htmlspecialchars($filtro); ?>" placeholder="Nome ou email...">
                        </div>
                        <div class="filter-group">
                            <label>Tipo:</label>
                            <select name="tipo">
                                <option value="">Todos</option>
                                <option value="admin" <?php echo $tipo_filtro == 'admin' ? 'selected' : ''; ?>>Administrador</option>
                                <option value="funcionario" <?php echo $tipo_filtro == 'funcionario' ? 'selected' : ''; ?>>Funcionário</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Status:</label>
                            <select name="status">
                                <option value="">Todos</option>
                                <option value="1" <?php echo $status_filtro == '1' ? 'selected' : ''; ?>>Ativo</option>
                                <option value="0" <?php echo $status_filtro == '0' ? 'selected' : ''; ?>>Inativo</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">🔍 Filtrar</button>
                    </form>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Tipo</th>
                                <th>Status</th>
                                <th>Criado em</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($usuarios)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 2rem; color: #666;">
                                        Nenhum usuário encontrado
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($usuarios as $u): ?>
                                    <tr>
                                        <td><strong><?php echo $u['id']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($u['nome']); ?></td>
                                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $u['tipo_usuario']; ?>">
                                                <?php echo $u['tipo_usuario'] == 'admin' ? 'Administrador' : 'Funcionário'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $u['ativo'] ? 'ativo' : 'inativo'; ?>">
                                                <?php echo $u['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($u['data_criacao'])); ?></td>
                                        <td>
                                            <button class="btn btn-primary btn-small" onclick="editarUsuario(<?php echo $u['id']; ?>)">✏️ Editar</button>
                                            <?php if ($u['id'] != $_SESSION['usuario_id']): ?>
                                                <a href="?apagar=<?php echo $u['id']; ?>" class="btn btn-danger btn-small" onclick="return confirm('Tem certeza que deseja eliminar este usuário?')">🗑️ Eliminar</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr id="edit-<?php echo $u['id']; ?>" style="display: none;">
                                        <td colspan="7">
                                            <form method="POST" class="edit-form">
                                                <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                                <div class="form-grid">
                                                    <div class="form-group">
                                                        <label>Nome:</label>
                                                        <input type="text" name="nome" value="<?php echo htmlspecialchars($u['nome']); ?>" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Email:</label>
                                                        <input type="email" name="email" value="<?php echo htmlspecialchars($u['email']); ?>" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Tipo:</label>
                                                        <select name="tipo">
                                                            <option value="funcionario" <?php echo $u['tipo_usuario'] == 'funcionario' ? 'selected' : ''; ?>>Funcionário</option>
                                                            <option value="admin" <?php echo $u['tipo_usuario'] == 'admin' ? 'selected' : ''; ?>>Administrador</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <div class="checkbox-group">
                                                            <input type="checkbox" name="ativo" <?php echo $u['ativo'] ? 'checked' : ''; ?>>
                                                            <label>Usuário Ativo</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div style="margin-top: 1rem;">
                                                    <button type="submit" name="editar" class="btn btn-success btn-small">💾 Salvar</button>
                                                    <button type="button" class="btn btn-danger btn-small" onclick="cancelarEdicao(<?php echo $u['id']; ?>)">❌ Cancelar</button>
                                                </div>
                                            </form>
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

    <!-- Modal para adicionar usuário -->
    <div id="modalUsuario" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>➕ Adicionar Novo Usuário</h2>
                <span class="close" onclick="fecharModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" id="formUsuario">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nome Completo *</label>
                            <input type="text" name="nome" required placeholder="Digite o nome completo">
                        </div>
                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" required placeholder="Digite o email">
                        </div>
                        <div class="form-group">
                            <label>Senha *</label>
                            <input type="password" name="senha" required placeholder="Digite a senha" minlength="6">
                        </div>
                        <div class="form-group">
                            <label>Tipo de Usuário *</label>
                            <select name="tipo" required>
                                <option value="">Selecione o tipo</option>
                                <option value="funcionario">Funcionário</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                    </div>

                    <div style="text-align: center; margin-top: 2rem;">
                        <button type="submit" name="criar" class="btn btn-success" style="padding: 12px 30px; font-size: 1.1rem;">
                            💾 Criar Usuário
                        </button>
                        <button type="button" class="btn btn-danger" style="padding: 12px 30px; font-size: 1.1rem; margin-left: 1rem;" onclick="fecharModal()">
                            ❌ Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function abrirModal() {
            document.getElementById('modalUsuario').style.display = 'block';
        }

        function fecharModal() {
            document.getElementById('modalUsuario').style.display = 'none';
            document.getElementById('formUsuario').reset();
        }

        function editarUsuario(id) {
            const editRow = document.getElementById('edit-' + id);
            if (editRow.style.display === 'none') {
                editRow.style.display = 'table-row';
            } else {
                editRow.style.display = 'none';
            }
        }

        function cancelarEdicao(id) {
            document.getElementById('edit-' + id).style.display = 'none';
        }

        // Fechar modal ao clicar fora dele
        window.onclick = function(event) {
            const modal = document.getElementById('modalUsuario');
            if (event.target == modal) {
                fecharModal();
            }
        }

        // Validação do formulário
        document.getElementById('formUsuario').addEventListener('submit', function(e) {
            const senha = document.querySelector('input[name="senha"]').value;
            if (senha.length < 6) {
                e.preventDefault();
                alert('A senha deve ter pelo menos 6 caracteres!');
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