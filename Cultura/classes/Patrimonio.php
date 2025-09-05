<?php
// classes/Patrimonio.php

class Patrimonio {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Listar todo o património com filtros
    public function listar($filtros = []) {
        $sql = "SELECT p.*, c.nome as categoria_nome, 
                       CONCAT(l.cidade, ', ', l.provincia) as localizacao
                FROM patrimonio p
                INNER JOIN categorias c ON p.categoria_id = c.id
                INNER JOIN localizacoes l ON p.localizacao_id = l.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filtros['categoria_id'])) {
            $sql .= " AND p.categoria_id = :categoria_id";
            $params['categoria_id'] = $filtros['categoria_id'];
        }
        
        if (!empty($filtros['estado_conservacao'])) {
            $sql .= " AND p.estado_conservacao = :estado_conservacao";
            $params['estado_conservacao'] = $filtros['estado_conservacao'];
        }
        
        if (!empty($filtros['provincia'])) {
            $sql .= " AND l.provincia = :provincia";
            $params['provincia'] = $filtros['provincia'];
        }
        
        if (!empty($filtros['pesquisa'])) {
            $sql .= " AND (p.nome LIKE :pesquisa OR p.descricao LIKE :pesquisa)";
            $params['pesquisa'] = '%' . $filtros['pesquisa'] . '%';
        }
        
        $sql .= " ORDER BY p.nome ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    // Obter património por ID
    public function obterPorId($id) {
        $sql = "SELECT p.*, c.nome as categoria_nome, 
                       l.provincia, l.cidade, l.bairro, l.endereco_completo, l.coordenadas_gps
                FROM patrimonio p
                INNER JOIN categorias c ON p.categoria_id = c.id
                INNER JOIN localizacoes l ON p.localizacao_id = l.id
                WHERE p.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    
    // Inserir novo património
    public function inserir($dados) {
        $sql = "INSERT INTO patrimonio (nome, descricao, categoria_id, localizacao_id, 
                data_construcao, periodo_historico, estado_conservacao, valor_historico, 
                valor_cultural, status_protecao, proprietario, contacto_proprietario, 
                observacoes, imagem_principal)
                VALUES (:nome, :descricao, :categoria_id, :localizacao_id, 
                :data_construcao, :periodo_historico, :estado_conservacao, :valor_historico, 
                :valor_cultural, :status_protecao, :proprietario, :contacto_proprietario, 
                :observacoes, :imagem_principal)";
        
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($dados)) {
            return $this->db->lastInsertId();
        }
        return false;
    }
    
    // Atualizar património
    public function atualizar($id, $dados) {
        $sql = "UPDATE patrimonio SET 
                nome = :nome, descricao = :descricao, categoria_id = :categoria_id,
                localizacao_id = :localizacao_id, data_construcao = :data_construcao,
                periodo_historico = :periodo_historico, estado_conservacao = :estado_conservacao,
                valor_historico = :valor_historico, valor_cultural = :valor_cultural,
                status_protecao = :status_protecao, proprietario = :proprietario,
                contacto_proprietario = :contacto_proprietario, observacoes = :observacoes";
        
        if (!empty($dados['imagem_principal'])) {
            $sql .= ", imagem_principal = :imagem_principal";
        }
        
        $sql .= " WHERE id = :id";
        
        $dados['id'] = $id;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($dados);
    }
    
    // Eliminar património
    public function eliminar($id) {
        $sql = "DELETE FROM patrimonio WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
    
    // Obter categorias
    public function obterCategorias() {
        $sql = "SELECT * FROM categorias ORDER BY nome";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Obter localizações
    public function obterLocalizacoes() {
        $sql = "SELECT * FROM localizacoes ORDER BY provincia, cidade";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Obter estatísticas
    public function obterEstatisticas() {
        $stats = [];
        
        // Total de património
        $sql = "SELECT COUNT(*) as total FROM patrimonio";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['total'] = $stmt->fetch()['total'];
        
        // Por categoria
        $sql = "SELECT c.nome, COUNT(p.id) as total 
                FROM categorias c 
                LEFT JOIN patrimonio p ON c.id = p.categoria_id 
                GROUP BY c.id, c.nome 
                ORDER BY total DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['por_categoria'] = $stmt->fetchAll();
        
        // Por estado de conservação
        $sql = "SELECT estado_conservacao, COUNT(*) as total 
                FROM patrimonio 
                GROUP BY estado_conservacao 
                ORDER BY total DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['por_estado'] = $stmt->fetchAll();
        
        // Por província
        $sql = "SELECT l.provincia, COUNT(p.id) as total 
                FROM localizacoes l 
                LEFT JOIN patrimonio p ON l.id = p.localizacao_id 
                GROUP BY l.provincia 
                ORDER BY total DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['por_provincia'] = $stmt->fetchAll();
        
        return $stats;
    }
    
    // Adicionar imagem ao património
    public function adicionarImagem($patrimonio_id, $caminho_imagem, $descricao = '') {
        $sql = "INSERT INTO imagens_patrimonio (patrimonio_id, caminho_imagem, descricao) 
                VALUES (:patrimonio_id, :caminho_imagem, :descricao)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'patrimonio_id' => $patrimonio_id,
            'caminho_imagem' => $caminho_imagem,
            'descricao' => $descricao
        ]);
    }
    
    // Obter imagens do património
    public function obterImagens($patrimonio_id) {
        $sql = "SELECT * FROM imagens_patrimonio 
                WHERE patrimonio_id = :patrimonio_id 
                ORDER BY ordem_exibicao, id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['patrimonio_id' => $patrimonio_id]);
        return $stmt->fetchAll();
    }
    
    // Registrar visita
    public function registrarVisita($dados) {
        $sql = "INSERT INTO visitas (patrimonio_id, visitante_id, data_visita, hora_visita, 
                numero_pessoas, proposito_visita, observacoes)
                VALUES (:patrimonio_id, :visitante_id, :data_visita, :hora_visita, 
                :numero_pessoas, :proposito_visita, :observacoes)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($dados);
    }
    
    // Obter visitas do património
    public function obterVisitas($patrimonio_id) {
        $sql = "SELECT v.*, vis.nome as visitante_nome, vis.instituicao
                FROM visitas v
                LEFT JOIN visitantes vis ON v.visitante_id = vis.id
                WHERE v.patrimonio_id = :patrimonio_id
                ORDER BY v.data_visita DESC, v.hora_visita DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['patrimonio_id' => $patrimonio_id]);
        return $stmt->fetchAll();
    }
}
?>