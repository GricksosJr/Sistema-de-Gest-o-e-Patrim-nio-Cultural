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
    $stmt = $pdo->prepare("SELECT p.*, c.nome as categoria_nome FROM patrimonio p 
                          LEFT JOIN categorias_patrimonio c ON p.categoria_id = c.id 
                          WHERE p.id = ?");
    $stmt->execute([$patrimonio_id]);
    $patrimonio = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$patrimonio) {
        redirecionar('gestao_patrimonio.php?erro=Património não encontrado');
    }
} catch (PDOException $e) {
    $erro = "Erro ao buscar dados: " . $e->getMessage();
}

// Buscar categorias para o select
try {
    $stmt_cat = $pdo->query("SELECT * FROM categorias_patrimonio ORDER BY nome");
    $categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao buscar categorias: " . $e->getMessage();
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['atualizar'])) {
    try {
        // Campos obrigatórios
        $codigo_registo = limparInput($_POST['codigo_registo']);
        $nome = limparInput($_POST['nome']);
        $categoria_id = (int)$_POST['categoria_id'];
        $tipo_patrimonio = limparInput($_POST['tipo_patrimonio']);
        $descricao = limparInput($_POST['descricao']);
        $estado_conservacao = limparInput($_POST['estado_conservacao']);
        
        // Campos opcionais
        $provincia = limparInput($_POST['provincia'] ?? '');
        $distrito = limparInput($_POST['distrito'] ?? '');
        $localidade = limparInput($_POST['localidade'] ?? '');
        $coordenadas_gps = limparInput($_POST['coordenadas_gps'] ?? '');
        $periodo_historico = limparInput($_POST['periodo_historico'] ?? '');
        $data_criacao_aproximada = limparInput($_POST['data_criacao_aproximada'] ?? '');
        $origem = limparInput($_POST['origem'] ?? '');
        $observacoes_estado = limparInput($_POST['observacoes_estado'] ?? '');
        $significado_cultural = limparInput($_POST['significado_cultural'] ?? '');
        $valor_historico = limparInput($_POST['valor_historico'] ?? '');
        $relevancia_comunitaria = limparInput($_POST['relevancia_comunitaria'] ?? '');
        $materiais_construcao = limparInput($_POST['materiais_construcao'] ?? '');
        $tecnicas_utilizadas = limparInput($_POST['tecnicas_utilizadas'] ?? '');
        $dimensoes = limparInput($_POST['dimensoes'] ?? '');
        $peso = limparInput($_POST['peso'] ?? '');
        $praticantes = limparInput($_POST['praticantes'] ?? '');
        $frequencia_pratica = limparInput($_POST['frequencia_pratica'] ?? '');
        $rituais_associados = limparInput($_POST['rituais_associados'] ?? '');
        $conhecimentos_tradicionais = limparInput($_POST['conhecimentos_tradicionais'] ?? '');
        $proprietario = limparInput($_POST['proprietario'] ?? '');
        $gestor_responsavel = limparInput($_POST['gestor_responsavel'] ?? '');
        $contacto_responsavel = limparInput($_POST['contacto_responsavel'] ?? '');
        $acesso_publico = isset($_POST['acesso_publico']) ? 1 : 0;
        $horario_visita = limparInput($_POST['horario_visita'] ?? '');
        $restricoes_acesso = limparInput($_POST['restricoes_acesso'] ?? '');
        $ameacas_identificadas = limparInput($_POST['ameacas_identificadas'] ?? '');
        $nivel_risco = limparInput($_POST['nivel_risco'] ?? 'baixo');
        $medidas_protecao = limparInput($_POST['medidas_protecao'] ?? '');
        $classificacao_oficial = limparInput($_POST['classificacao_oficial'] ?? 'sem_classificacao');
        $data_classificacao = !empty($_POST['data_classificacao']) ? $_POST['data_classificacao'] : null;
        $entidade_classificadora = limparInput($_POST['entidade_classificadora'] ?? '');
        $status = limparInput($_POST['status'] ?? 'ativo');

        // Verificar se o código de registo já existe (excluindo o atual)
        $stmt_check = $pdo->prepare("SELECT id FROM patrimonio WHERE codigo_registo = ? AND id != ?");
        $stmt_check->execute([$codigo_registo, $patrimonio_id]);
        if ($stmt_check->fetch()) {
            throw new Exception("Código de registo já existe.");
        }

        // Preparar query de atualização
        $sql = "UPDATE patrimonio SET 
                codigo_registo = ?, nome = ?, categoria_id = ?, tipo_patrimonio = ?, 
                descricao = ?, provincia = ?, distrito = ?, localidade = ?, 
                coordenadas_gps = ?, periodo_historico = ?, data_criacao_aproximada = ?, 
                origem = ?, estado_conservacao = ?, observacoes_estado = ?, 
                significado_cultural = ?, valor_historico = ?, relevancia_comunitaria = ?, 
                materiais_construcao = ?, tecnicas_utilizadas = ?, dimensoes = ?, 
                peso = ?, praticantes = ?, frequencia_pratica = ?, rituais_associados = ?, 
                conhecimentos_tradicionais = ?, proprietario = ?, gestor_responsavel = ?, 
                contacto_responsavel = ?, acesso_publico = ?, horario_visita = ?, 
                restricoes_acesso = ?, ameacas_identificadas = ?, nivel_risco = ?, 
                medidas_protecao = ?, classificacao_oficial = ?, data_classificacao = ?, 
                entidade_classificadora = ?, status = ?, data_ultima_atualizacao = NOW() 
                WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $codigo_registo, $nome, $categoria_id, $tipo_patrimonio, $descricao,
            $provincia, $distrito, $localidade, $coordenadas_gps, $periodo_historico,
            $data_criacao_aproximada, $origem, $estado_conservacao, $observacoes_estado,
            $significado_cultural, $valor_historico, $relevancia_comunitaria,
            $materiais_construcao, $tecnicas_utilizadas, $dimensoes, $peso,
            $praticantes, $frequencia_pratica, $rituais_associados,
            $conhecimentos_tradicionais, $proprietario, $gestor_responsavel,
            $contacto_responsavel, $acesso_publico, $horario_visita,
            $restricoes_acesso, $ameacas_identificadas, $nivel_risco,
            $medidas_protecao, $classificacao_oficial, $data_classificacao,
            $entidade_classificadora, $status, $patrimonio_id
        ]);

        // Registrar histórico de alteração
        $stmt_hist = $pdo->prepare("INSERT INTO patrimonio_historico 
                                   (patrimonio_id, campo_alterado, valor_anterior, valor_novo, usuario_id, observacoes) 
                                   VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_hist->execute([
            $patrimonio_id, 
            'atualizacao_geral', 
            'Dados anteriores', 
            'Dados atualizados', 
            $_SESSION['usuario_id'], 
            'Atualização completa do registo'
        ]);

        $sucesso = "Património atualizado com sucesso!";
        
        // Recarregar dados atualizados
        $stmt = $pdo->prepare("SELECT p.*, c.nome as categoria_nome FROM patrimonio p 
                              LEFT JOIN categorias_patrimonio c ON p.categoria_id = c.id 
                              WHERE p.id = ?");
        $stmt->execute([$patrimonio_id]);
        $patrimonio = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        $erro = "Erro ao atualizar: " . $e->getMessage();
    }
}

// Upload de foto principal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_foto'])) {
    if (isset($_FILES['foto_principal']) && $_FILES['foto_principal']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['foto_principal']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($filetype), $allowed)) {
            $newname = 'patrimonio_' . uniqid() . '.' . $filetype;
            $upload_path = 'uploads/' . $newname;
            
            // Criar diretório se não existir
            if (!is_dir('uploads')) {
                mkdir('uploads', 0777, true);
            }
            
            if (move_uploaded_file($_FILES['foto_principal']['tmp_name'], $upload_path)) {
                // Remover foto anterior se existir
                if (!empty($patrimonio['foto_principal']) && file_exists($patrimonio['foto_principal'])) {
                    unlink($patrimonio['foto_principal']);
                }
                
                // Atualizar na base de dados
                $stmt_foto = $pdo->prepare("UPDATE patrimonio SET foto_principal = ? WHERE id = ?");
                $stmt_foto->execute([$newname, $patrimonio_id]);
                
                $sucesso = "Foto principal carregada com sucesso!";
                $patrimonio['foto_principal'] = $newname;
            } else {
                $erro = "Erro ao carregar a foto.";
            }
        } else {
            $erro = "Tipo de arquivo não permitido. Use JPG, PNG ou GIF.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Património - <?php echo SITE_TITLE; ?></title>
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

        .form-tabs {
            display: flex;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .tab-btn {
            flex: 1;
            padding: 15px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .tab-btn.active {
            background: #667eea;
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
        }

        label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        input:focus, select:focus, textarea:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin: 0;
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
        }

        .btn:hover {
            background: #5a67d8;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        .btn-success {
            background: #28a745;
        }

        .btn-success:hover {
            background: #218838;
        }

        .actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e1e5e9;
        }

        .foto-section {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }

        .foto-atual {
            text-align: center;
            margin-bottom: 1rem;
        }

        .foto-atual img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .required {
            color: #dc3545;
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
            }
            
            .content {
                padding: 1rem;
            }
            
            .form-row {
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
            <h1>✏️ Editar Património</h1>
            <div class="breadcrumb">
                <a href="gestao_patrimonio.php">← Voltar à Lista</a> | 
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

            <!-- Upload de Foto -->
            <div class="foto-section">
                <h3>📸 Foto Principal</h3>
                <?php if (!empty($patrimonio['foto_principal'])): ?>
                    <div class="foto-atual">
                        <img src="uploads/<?php echo htmlspecialchars($patrimonio['foto_principal']); ?>" alt="Foto Principal">
                        <p><small>Foto atual</small></p>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" style="display: inline;">
                    <div class="form-group">
                        <label>Nova Foto:</label>
                        <input type="file" name="foto_principal" accept="image/*" required>
                    </div>
                    <button type="submit" name="upload_foto" class="btn btn-success">Carregar Foto</button>
                </form>
            </div>

            <!-- Formulário Principal -->
            <form method="POST">
                <div class="form-tabs">
                    <button type="button" class="tab-btn active" onclick="showTab(0)">📋 Informações Básicas</button>
                    <button type="button" class="tab-btn" onclick="showTab(1)">📍 Localização</button>
                    <button type="button" class="tab-btn" onclick="showTab(2)">🏛️ História & Cultura</button>
                    <button type="button" class="tab-btn" onclick="showTab(3)">🔧 Detalhes Técnicos</button>
                    <button type="button" class="tab-btn" onclick="showTab(4)">👥 Gestão & Acesso</button>
                </div>

                <!-- Tab 1: Informações Básicas -->
                <div class="tab-content active">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Código de Registo <span class="required">*</span>:</label>
                            <input type="text" name="codigo_registo" value="<?php echo htmlspecialchars($patrimonio['codigo_registo']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Nome <span class="required">*</span>:</label>
                            <input type="text" name="nome" value="<?php echo htmlspecialchars($patrimonio['nome']); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Categoria <span class="required">*</span>:</label>
                            <select name="categoria_id" required>
                                <option value="">Selecione uma categoria</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id']; ?>" 
                                            <?php echo ($categoria['id'] == $patrimonio['categoria_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($categoria['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tipo de Património <span class="required">*</span>:</label>
                            <select name="tipo_patrimonio" required>
                                <option value="material" <?php echo ($patrimonio['tipo_patrimonio'] == 'material') ? 'selected' : ''; ?>>Material</option>
                                <option value="imaterial" <?php echo ($patrimonio['tipo_patrimonio'] == 'imaterial') ? 'selected' : ''; ?>>Imaterial</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Descrição <span class="required">*</span>:</label>
                        <textarea name="descricao" rows="4" required><?php echo htmlspecialchars($patrimonio['descricao']); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Estado de Conservação <span class="required">*</span>:</label>
                            <select name="estado_conservacao" required>
                                <option value="excelente" <?php echo ($patrimonio['estado_conservacao'] == 'excelente') ? 'selected' : ''; ?>>Excelente</option>
                                <option value="bom" <?php echo ($patrimonio['estado_conservacao'] == 'bom') ? 'selected' : ''; ?>>Bom</option>
                                <option value="regular" <?php echo ($patrimonio['estado_conservacao'] == 'regular') ? 'selected' : ''; ?>>Regular</option>
                                <option value="mau" <?php echo ($patrimonio['estado_conservacao'] == 'mau') ? 'selected' : ''; ?>>Mau</option>
                                <option value="critico" <?php echo ($patrimonio['estado_conservacao'] == 'critico') ? 'selected' : ''; ?>>Crítico</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status:</label>
                            <select name="status">
                                <option value="ativo" <?php echo ($patrimonio['status'] == 'ativo') ? 'selected' : ''; ?>>Ativo</option>
                                <option value="inativo" <?php echo ($patrimonio['status'] == 'inativo') ? 'selected' : ''; ?>>Inativo</option>
                                <option value="em_analise" <?php echo ($patrimonio['status'] == 'em_analise') ? 'selected' : ''; ?>>Em Análise</option>
                                <option value="pendente" <?php echo ($patrimonio['status'] == 'pendente') ? 'selected' : ''; ?>>Pendente</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Localização -->
                <div class="tab-content">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Província:</label>
                            <input type="text" name="provincia" value="<?php echo htmlspecialchars($patrimonio['provincia']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Distrito:</label>
                            <input type="text" name="distrito" value="<?php echo htmlspecialchars($patrimonio['distrito']); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Localidade:</label>
                            <input type="text" name="localidade" value="<?php echo htmlspecialchars($patrimonio['localidade']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Coordenadas GPS:</label>
                            <input type="text" name="coordenadas_gps" value="<?php echo htmlspecialchars($patrimonio['coordenadas_gps']); ?>" placeholder="Ex: -25.9686, 32.5804">
                        </div>
                    </div>
                </div>

                <!-- Tab 3: História & Cultura -->
                <div class="tab-content">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Período Histórico:</label>
                            <input type="text" name="periodo_historico" value="<?php echo htmlspecialchars($patrimonio['periodo_historico']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Data de Criação Aproximada:</label>
                            <input type="text" name="data_criacao_aproximada" value="<?php echo htmlspecialchars($patrimonio['data_criacao_aproximada']); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Origem:</label>
                        <input type="text" name="origem" value="<?php echo htmlspecialchars($patrimonio['origem']); ?>">
                    </div>

                    <div class="form-group">
                        <label>Significado Cultural:</label>
                        <textarea name="significado_cultural" rows="3"><?php echo htmlspecialchars($patrimonio['significado_cultural']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Valor Histórico:</label>
                        <textarea name="valor_historico" rows="3"><?php echo htmlspecialchars($patrimonio['valor_historico']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Relevância Comunitária:</label>
                        <textarea name="relevancia_comunitaria" rows="3"><?php echo htmlspecialchars($patrimonio['relevancia_comunitaria']); ?></textarea>
                    </div>
                </div>

                <!-- Tab 4: Detalhes Técnicos -->
                <div class="tab-content">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Materiais de Construção:</label>
                            <textarea name="materiais_construcao" rows="2"><?php echo htmlspecialchars($patrimonio['materiais_construcao']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Técnicas Utilizadas:</label>
                            <textarea name="tecnicas_utilizadas" rows="2"><?php echo htmlspecialchars($patrimonio['tecnicas_utilizadas']); ?></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Dimensões:</label>
                            <input type="text" name="dimensoes" value="<?php echo htmlspecialchars($patrimonio['dimensoes']); ?>" placeholder="Ex: 50m x 30m x 15m">
                        </div>
                        <div class="form-group">
                            <label>Peso:</label>
                            <input type="text" name="peso" value="<?php echo htmlspecialchars($patrimonio['peso']); ?>" placeholder="Ex: 500kg">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Praticantes:</label>
                            <textarea name="praticantes" rows="2"><?php echo htmlspecialchars($patrimonio['praticantes']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Frequência da Prática:</label>
                            <input type="text" name="frequencia_pratica" value="<?php echo htmlspecialchars($patrimonio['frequencia_pratica']); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Rituais Associados:</label>
                        <textarea name="rituais_associados" rows="3"><?php echo htmlspecialchars($patrimonio['rituais_associados']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Conhecimentos Tradicionais:</label>
                        <textarea name="conhecimentos_tradicionais" rows="3"><?php echo htmlspecialchars($patrimonio['conhecimentos_tradicionais']); ?></textarea>
                    </div>
                </div>

                <!-- Tab 5: Gestão & Acesso -->
                <div class="tab-content">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Proprietário:</label>
                            <input type="text" name="proprietario" value="<?php echo htmlspecialchars($patrimonio['proprietario']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Gestor Responsável:</label>
                            <input type="text" name="gestor_responsavel" value="<?php echo htmlspecialchars($patrimonio['gestor_responsavel']); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Contacto do Responsável:</label>
                            <input type="text" name="contacto_responsavel" value="<?php echo htmlspecialchars($patrimonio['contacto_responsavel']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Horário de Visita:</label>
                            <input type="text" name="horario_visita" value="<?php echo htmlspecialchars($patrimonio['horario_visita']); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="acesso_publico" id="acesso_publico" <?php echo $patrimonio['acesso_publico'] ? 'checked' : ''; ?>>
                            <label for="acesso_publico">Acesso Público</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Restrições de Acesso:</label>
                        <textarea name="restricoes_acesso" rows="3"><?php echo htmlspecialchars($patrimonio['restricoes_acesso']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Ameaças Identificadas:</label>
                        <textarea name="ameacas_identificadas" rows="3"><?php echo htmlspecialchars($patrimonio['ameacas_identificadas']); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Nível de Risco:</label>
                            <select name="nivel_risco">
                                <option value="baixo" <?php echo ($patrimonio['nivel_risco'] == 'baixo') ? 'selected' : ''; ?>>Baixo</option>
                                <option value="medio" <?php echo ($patrimonio['nivel_risco'] == 'medio') ? 'selected' : ''; ?>>Médio</option>
                                <option value="alto" <?php echo ($patrimonio['nivel_risco'] == 'alto') ? 'selected' : ''; ?>>Alto</option>
                                <option value="critico" <?php echo ($patrimonio['nivel_risco'] == 'critico') ? 'selected' : ''; ?>>Crítico</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Classificação Oficial:</label>
                            <select name="classificacao_oficial">
                                <option value="sem_classificacao" <?php echo ($patrimonio['classificacao_oficial'] == 'sem_classificacao') ? 'selected' : ''; ?>>Sem Classificação</option>
                                <option value="monumento_nacional" <?php echo ($patrimonio['classificacao_oficial'] == 'monumento_nacional') ? 'selected' : ''; ?>>Monumento Nacional</option>
                                <option value="bem_interesse_cultural" <?php echo ($patrimonio['classificacao_oficial'] == 'bem_interesse_cultural') ? 'selected' : ''; ?>>Bem de Interesse Cultural</option>
                                <option value="bem_relevante" <?php echo ($patrimonio['classificacao_oficial'] == 'bem_relevante') ? 'selected' : ''; ?>>Bem Relevante</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Medidas de Proteção:</label>
                        <textarea name="medidas_protecao" rows="3"><?php echo htmlspecialchars($patrimonio['medidas_protecao']); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Data de Classificação:</label>
                            <input type="date" name="data_classificacao" value="<?php echo htmlspecialchars($patrimonio['data_classificacao']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Entidade Classificadora:</label>
                            <input type="text" name="entidade_classificadora" value="<?php echo htmlspecialchars($patrimonio['entidade_classificadora']); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Observações sobre o Estado:</label>
                        <textarea name="observacoes_estado" rows="3"><?php echo htmlspecialchars($patrimonio['observacoes_estado']); ?></textarea>
                    </div>
                </div>

                <div class="actions">
                    <a href="gestao_patrimonio.php" class="btn btn-secondary">← Cancelar</a>
                    <a href="documentos_patrimonio.php?id=<?php echo $patrimonio_id; ?>" class="btn">📁 Documentos</a>
                    <button type="submit" name="atualizar" class="btn btn-success">💾 Atualizar Património</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showTab(tabIndex) {
            // Esconder todos os conteúdos das abas
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Remover classe ativa de todos os botões
            const tabBtns = document.querySelectorAll('.tab-btn');
            tabBtns.forEach(btn => btn.classList.remove('active'));
            
            // Mostrar conteúdo da aba selecionada
            tabContents[tabIndex].classList.add('active');
            tabBtns[tabIndex].classList.add('active');
        }

        // Validação do formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            const requiredFields = document.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#dc3545';
                    isValid = false;
                } else {
                    field.style.borderColor = '#e1e5e9';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Por favor, preencha todos os campos obrigatórios marcados com *');
            }
        });

        // Confirmar antes de sair sem salvar
        let formChanged = false;
        document.querySelectorAll('input, select, textarea').forEach(element => {
            element.addEventListener('change', () => {
                formChanged = true;
            });
        });

        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Remover aviso ao submeter
        document.querySelector('form').addEventListener('submit', () => {
            formChanged = false;
        });
    </script>
</body>
</html>