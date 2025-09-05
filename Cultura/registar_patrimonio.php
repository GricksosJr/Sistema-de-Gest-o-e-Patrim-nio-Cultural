<?php
include 'config.php';

// Verificar se está logado e é funcionário
if (!verificarLogin() || $_SESSION['usuario_tipo'] != 'funcionario') {
    redirecionar('login.php');
}

$mensagem = '';
$erro = '';

// Buscar categorias
$categorias = [];
try {
    $stmt = $pdo->query("SELECT * FROM categorias_patrimonio ORDER BY nome");
    $categorias = $stmt->fetchAll();
} catch(PDOException $e) {
    $erro = "Erro ao carregar categorias: " . $e->getMessage();
}

// Função para gerar código de registo único
function gerarCodigoRegisto($pdo) {
    $ano = date('Y');
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM patrimonio WHERE YEAR(data_registo) = $ano");
    $count = $stmt->fetch()['total'] + 1;
    return sprintf("PCM-%03d-%d", $count, $ano);
}

// Processar formulário
if ($_POST) {
    $nome = trim($_POST['nome'] ?? '');
    $categoria_id = intval($_POST['categoria_id'] ?? 0);
    $tipo_patrimonio = $_POST['tipo_patrimonio'] ?? '';
    $descricao = trim($_POST['descricao'] ?? '');
    $provincia = trim($_POST['provincia'] ?? '');
    $distrito = trim($_POST['distrito'] ?? '');
    $localidade = trim($_POST['localidade'] ?? '');
    $coordenadas_gps = trim($_POST['coordenadas_gps'] ?? '');
    $periodo_historico = trim($_POST['periodo_historico'] ?? '');
    $data_criacao_aproximada = trim($_POST['data_criacao_aproximada'] ?? '');
    $origem = trim($_POST['origem'] ?? '');
    $estado_conservacao = $_POST['estado_conservacao'] ?? '';
    $observacoes_estado = trim($_POST['observacoes_estado'] ?? '');
    $significado_cultural = trim($_POST['significado_cultural'] ?? '');
    $valor_historico = trim($_POST['valor_historico'] ?? '');
    $relevancia_comunitaria = trim($_POST['relevancia_comunitaria'] ?? '');
    
    // Campos específicos para património material
    $materiais_construcao = trim($_POST['materiais_construcao'] ?? '');
    $tecnicas_utilizadas = trim($_POST['tecnicas_utilizadas'] ?? '');
    $dimensoes = trim($_POST['dimensoes'] ?? '');
    $peso = trim($_POST['peso'] ?? '');
    
    // Campos específicos para património imaterial
    $praticantes = trim($_POST['praticantes'] ?? '');
    $frequencia_pratica = trim($_POST['frequencia_pratica'] ?? '');
    $rituais_associados = trim($_POST['rituais_associados'] ?? '');
    $conhecimentos_tradicionais = trim($_POST['conhecimentos_tradicionais'] ?? '');
    
    // Campos de gestão
    $proprietario = trim($_POST['proprietario'] ?? '');
    $gestor_responsavel = trim($_POST['gestor_responsavel'] ?? '');
    $contacto_responsavel = trim($_POST['contacto_responsavel'] ?? '');
    $acesso_publico = isset($_POST['acesso_publico']) ? 1 : 0;
    $horario_visita = trim($_POST['horario_visita'] ?? '');
    $restricoes_acesso = trim($_POST['restricoes_acesso'] ?? '');
    
    // Campos de conservação
    $ameacas_identificadas = trim($_POST['ameacas_identificadas'] ?? '');
    $nivel_risco = $_POST['nivel_risco'] ?? 'baixo';
    $medidas_protecao = trim($_POST['medidas_protecao'] ?? '');

    if (empty($nome) || empty($descricao) || empty($categoria_id) || empty($tipo_patrimonio) || empty($estado_conservacao)) {
        $erro = "Por favor, preencha todos os campos obrigatórios.";
    } else {
        try {
            $codigo_registo = gerarCodigoRegisto($pdo);
            
            $stmt = $pdo->prepare("
                INSERT INTO patrimonio (
                    codigo_registo, nome, categoria_id, tipo_patrimonio, descricao, 
                    provincia, distrito, localidade, coordenadas_gps, periodo_historico, 
                    data_criacao_aproximada, origem, estado_conservacao, observacoes_estado, 
                    significado_cultural, valor_historico, relevancia_comunitaria, 
                    materiais_construcao, tecnicas_utilizadas, dimensoes, peso,
                    praticantes, frequencia_pratica, rituais_associados, conhecimentos_tradicionais,
                    proprietario, gestor_responsavel, contacto_responsavel, acesso_publico, 
                    horario_visita, restricoes_acesso, ameacas_identificadas, nivel_risco, 
                    medidas_protecao, registado_por, status
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'em_analise'
                )
            ");
            
            $stmt->execute([
                $codigo_registo, $nome, $categoria_id, $tipo_patrimonio, $descricao,
                $provincia, $distrito, $localidade, $coordenadas_gps, $periodo_historico,
                $data_criacao_aproximada, $origem, $estado_conservacao, $observacoes_estado,
                $significado_cultural, $valor_historico, $relevancia_comunitaria,
                $materiais_construcao, $tecnicas_utilizadas, $dimensoes, $peso,
                $praticantes, $frequencia_pratica, $rituais_associados, $conhecimentos_tradicionais,
                $proprietario, $gestor_responsavel, $contacto_responsavel, $acesso_publico,
                $horario_visita, $restricoes_acesso, $ameacas_identificadas, $nivel_risco,
                $medidas_protecao, $_SESSION['usuario_id']
            ]);
            
            $mensagem = "Património registado com sucesso! Código: $codigo_registo - Aguarda aprovação do administrador.";
            
            // Limpar campos
            $_POST = [];
            
        } catch(PDOException $e) {
            $erro = "Erro ao registar património: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registar Património - <?php echo SITE_TITLE; ?></title>
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
            padding: 20px 0;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(45deg, #2196F3, #1976D2);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .header p {
            opacity: 0.9;
        }

        .nav-back {
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem 2rem;
        }

        .nav-back a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-back a:hover {
            opacity: 0.8;
        }

        .form-container {
            padding: 2rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 4px solid #2196F3;
        }

        .form-section h3 {
            color: #2196F3;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .required::after {
            content: " *";
            color: #e74c3c;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #2196F3;
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        select.form-control {
            cursor: pointer;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-row-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1rem;
        }

        .btn {
            background: #2196F3;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            background: #1976D2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn-secondary {
            background: #6c757d;
            margin-right: 1rem;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .form-actions {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid #eee;
            margin-top: 2rem;
        }

        .help-text {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.25rem;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .checkbox-wrapper input[type="checkbox"] {
            width: auto;
        }

        .tipo-patrimonio-toggle {
            display: none;
        }

        .campos-material, .campos-imaterial {
            display: none;
        }

        @media (max-width: 768px) {
            .form-row, .form-row-3 {
                grid-template-columns: 1fr;
            }
            
            .container {
                margin: 0 10px;
            }
            
            .form-container {
                padding: 1rem;
            }
        }
    </style>
    <script>
        function toggleTipoPatrimonio() {
            const tipo = document.querySelector('input[name="tipo_patrimonio"]:checked')?.value;
            const camposMaterial = document.querySelector('.campos-material');
            const camposImaterial = document.querySelector('.campos-imaterial');
            
            if (tipo === 'material') {
                camposMaterial.style.display = 'block';
                camposImaterial.style.display = 'none';
            } else if (tipo === 'imaterial') {
                camposMaterial.style.display = 'none';
                camposImaterial.style.display = 'block';
            } else {
                camposMaterial.style.display = 'none';
                camposImaterial.style.display = 'none';
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const radioButtons = document.querySelectorAll('input[name="tipo_patrimonio"]');
            radioButtons.forEach(radio => {
                radio.addEventListener('change', toggleTipoPatrimonio);
            });
            toggleTipoPatrimonio();
        });
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📝 Registar Património Cultural</h1>
            <p>Adicione um novo item ao catálogo de património cultural</p>
        </div>
        
        <div class="nav-back">
            <a href="painel_funcionario.php">← Voltar ao Painel</a>
        </div>

        <div class="form-container">
            <?php if ($mensagem): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($mensagem); ?></div>
            <?php endif; ?>
            
            <?php if ($erro): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($erro); ?></div>
            <?php endif; ?>

            <form method="POST">
                <!-- Informações Básicas -->
                <div class="form-section">
                    <h3>📋 Informações Básicas</h3>
                    
                    <div class="form-group">
                        <label for="nome" class="required">Nome do Património</label>
                        <input type="text" id="nome" name="nome" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>" required>
                        <div class="help-text">Nome completo ou designação oficial do património</div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="categoria_id" class="required">Categoria</label>
                            <select id="categoria_id" name="categoria_id" class="form-control" required>
                                <option value="">Selecione uma categoria</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" 
                                            <?php echo (($_POST['categoria_id'] ?? '') == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="required">Tipo de Património</label>
                            <div style="display: flex; gap: 2rem; margin-top: 0.5rem;">
                                <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal;">
                                    <input type="radio" name="tipo_patrimonio" value="material" 
                                           <?php echo (($_POST['tipo_patrimonio'] ?? '') == 'material') ? 'checked' : ''; ?> required>
                                    Material (físico)
                                </label>
                                <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal;">
                                    <input type="radio" name="tipo_patrimonio" value="imaterial" 
                                           <?php echo (($_POST['tipo_patrimonio'] ?? '') == 'imaterial') ? 'checked' : ''; ?> required>
                                    Imaterial (tradições)
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="descricao" class="required">Descrição</label>
                        <textarea id="descricao" name="descricao" class="form-control" required><?php echo htmlspecialchars($_POST['descricao'] ?? ''); ?></textarea>
                        <div class="help-text">Descreva detalhadamente o património cultural</div>
                    </div>
                </div>

                <!-- Localização -->
                <div class="form-section">
                    <h3>📍 Localização</h3>
                    
                    <div class="form-row-3">
                        <div class="form-group">
                            <label for="provincia">Província</label>
                            <input type="text" id="provincia" name="provincia" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['provincia'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="distrito">Distrito</label>
                            <input type="text" id="distrito" name="distrito" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['distrito'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="localidade">Localidade</label>
                            <input type="text" id="localidade" name="localidade" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['localidade'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="coordenadas_gps">Coordenadas GPS</label>
                        <input type="text" id="coordenadas_gps" name="coordenadas_gps" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['coordenadas_gps'] ?? ''); ?>">
                        <div class="help-text">Ex: -25.9653, 32.5892 (latitude, longitude)</div>
                    </div>
                </div>

                <!-- Informações Históricas -->
                <div class="form-section">
                    <h3>📜 Informações Históricas</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="periodo_historico">Período Histórico</label>
                            <input type="text" id="periodo_historico" name="periodo_historico" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['periodo_historico'] ?? ''); ?>">
                            <div class="help-text">Ex: Século XIX, Colonial, Pré-colonial...</div>
                        </div>

                        <div class="form-group">
                            <label for="data_criacao_aproximada">Data de Criação Aproximada</label>
                            <input type="text" id="data_criacao_aproximada" name="data_criacao_aproximada" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['data_criacao_aproximada'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="origem">Origem</label>
                        <input type="text" id="origem" name="origem" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['origem'] ?? ''); ?>">
                        <div class="help-text">Grupo étnico, região, civilização de origem</div>
                    </div>
                </div>

                <!-- Estado de Conservação -->
                <div class="form-section">
                    <h3>🔧 Estado de Conservação</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="estado_conservacao" class="required">Estado de Conservação</label>
                            <select id="estado_conservacao" name="estado_conservacao" class="form-control" required>
                                <option value="">Selecione</option>
                                <option value="excelente" <?php echo (($_POST['estado_conservacao'] ?? '') == 'excelente') ? 'selected' : ''; ?>>Excelente</option>
                                <option value="bom" <?php echo (($_POST['estado_conservacao'] ?? '') == 'bom') ? 'selected' : ''; ?>>Bom</option>
                                <option value="regular" <?php echo (($_POST['estado_conservacao'] ?? '') == 'regular') ? 'selected' : ''; ?>>Regular</option>
                                <option value="mau" <?php echo (($_POST['estado_conservacao'] ?? '') == 'mau') ? 'selected' : ''; ?>>Mau</option>
                                <option value="critico" <?php echo (($_POST['estado_conservacao'] ?? '') == 'critico') ? 'selected' : ''; ?>>Crítico</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="nivel_risco">Nível de Risco</label>
                            <select id="nivel_risco" name="nivel_risco" class="form-control">
                                <option value="baixo" <?php echo (($_POST['nivel_risco'] ?? 'baixo') == 'baixo') ? 'selected' : ''; ?>>Baixo</option>
                                <option value="medio" <?php echo (($_POST['nivel_risco'] ?? '') == 'medio') ? 'selected' : ''; ?>>Médio</option>
                                <option value="alto" <?php echo (($_POST['nivel_risco'] ?? '') == 'alto') ? 'selected' : ''; ?>>Alto</option>
                                <option value="critico" <?php echo (($_POST['nivel_risco'] ?? '') == 'critico') ? 'selected' : ''; ?>>Crítico</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="observacoes_estado">Observações sobre o Estado</label>
                        <textarea id="observacoes_estado" name="observacoes_estado" class="form-control"><?php echo htmlspecialchars($_POST['observacoes_estado'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="ameacas_identificadas">Ameaças Identificadas</label>
                        <textarea id="ameacas_identificadas" name="ameacas_identificadas" class="form-control"><?php echo htmlspecialchars($_POST['ameacas_identificadas'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="medidas_protecao">Medidas de Proteção</label>
                        <textarea id="medidas_protecao" name="medidas_protecao" class="form-control"><?php echo htmlspecialchars($_POST['medidas_protecao'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Valor Cultural -->
                <div class="form-section">
                    <h3>💎 Valor Cultural</h3>
                    
                    <div class="form-group">
                        <label for="significado_cultural">Significado Cultural</label>
                        <textarea id="significado_cultural" name="significado_cultural" class="form-control"><?php echo htmlspecialchars($_POST['significado_cultural'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="valor_historico">Valor Histórico</label>
                        <textarea id="valor_historico" name="valor_historico" class="form-control"><?php echo htmlspecialchars($_POST['valor_historico'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="relevancia_comunitaria">Relevância Comunitária</label>
                        <textarea id="relevancia_comunitaria" name="relevancia_comunitaria" class="form-control"><?php echo htmlspecialchars($_POST['relevancia_comunitaria'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Campos específicos para Património Material -->
                <div class="form-section campos-material">
                    <h3>🏗️ Características Físicas (Património Material)</h3>
                    
                    <div class="form-group">
                        <label for="materiais_construcao">Materiais de Construção</label>
                        <textarea id="materiais_construcao" name="materiais_construcao" class="form-control"><?php echo htmlspecialchars($_POST['materiais_construcao'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="tecnicas_utilizadas">Técnicas Utilizadas</label>
                        <textarea id="tecnicas_utilizadas" name="tecnicas_utilizadas" class="form-control"><?php echo htmlspecialchars($_POST['tecnicas_utilizadas'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="dimensoes">Dimensões</label>
                            <input type="text" id="dimensoes" name="dimensoes" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['dimensoes'] ?? ''); ?>">
                            <div class="help-text">Ex: 50m x 30m x 15m</div>
                        </div>

                        <div class="form-group">
                            <label for="peso">Peso</label>
                            <input type="text" id="peso" name="peso" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['peso'] ?? ''); ?>">
                            <div class="help-text">Se aplicável</div>
                        </div>
                    </div>
                </div>

                <!-- Campos específicos para Património Imaterial -->
                <div class="form-section campos-imaterial">
                    <h3>🎭 Características Culturais (Património Imaterial)</h3>
                    
                    <div class="form-group">
                        <label for="praticantes">Praticantes</label>
                        <textarea id="praticantes" name="praticantes" class="form-control"><?php echo htmlspecialchars($_POST['praticantes'] ?? ''); ?></textarea>
                        <div class="help-text">Grupos, comunidades ou indivíduos que praticam esta tradição</div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="frequencia_pratica">Frequência de Prática</label>
                            <input type="text" id="frequencia_pratica" name="frequencia_pratica" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['frequencia_pratica'] ?? ''); ?>">
                            <div class="help-text">Ex: Anual, Mensal, Cerimónias específicas</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="rituais_associados">Rituais Associados</label>
                        <textarea id="rituais_associados" name="rituais_associados" class="form-control"><?php echo htmlspecialchars($_POST['rituais_associados'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="conhecimentos_tradicionais">Conhecimentos Tradicionais</label>
                        <textarea id="conhecimentos_tradicionais" name="conhecimentos_tradicionais" class="form-control"><?php echo htmlspecialchars($_POST['conhecimentos_tradicionais'] ?? ''); ?></textarea>
                        <div class="help-text">Saberes e técnicas transmitidos pela tradição</div>
                    </div>
                </div>

                <!-- Gestão e Acesso -->
                <div class="form-section">
                    <h3>👥 Gestão e Acesso</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="proprietario">Proprietário</label>
                            <input type="text" id="proprietario" name="proprietario" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['proprietario'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="gestor_responsavel">Gestor Responsável</label>
                            <input type="text" id="gestor_responsavel" name="gestor_responsavel" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['gestor_responsavel'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="contacto_responsavel">Contacto do Responsável</label>
                        <input type="text" id="contacto_responsavel" name="contacto_responsavel" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['contacto_responsavel'] ?? ''); ?>">
                        <div class="help-text">Telefone, email ou endereço</div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" id="acesso_publico" name="acesso_publico" value="1" 
                                   <?php echo (isset($_POST['acesso_publico']) && $_POST['acesso_publico']) ? 'checked' : ''; ?>>
                            <label for="acesso_publico" style="font-weight: normal;">Acesso público permitido</label>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="horario_visita">Horário de Visita</label>
                            <input type="text" id="horario_visita" name="horario_visita" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['horario_visita'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="restricoes_acesso">Restrições de Acesso</label>
                        <textarea id="restricoes_acesso" name="restricoes_acesso" class="form-control"><?php echo htmlspecialchars($_POST['restricoes_acesso'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="funcionario.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn">💾 Registar Património</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>