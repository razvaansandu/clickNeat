<?php
class RistoranteTavoliModel
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getByRistorante($ristorante_id)
    {
        $sql = "SELECT * FROM ristorante_tavoli 
                WHERE ristorante_id = ? 
                ORDER BY capacita ASC, nome ASC";
        return $this->db->select($sql, [$ristorante_id]);
    }

    public function getById($id)
    {
        $sql = "SELECT * FROM ristorante_tavoli WHERE id = ?";
        return $this->db->selectOne($sql, [$id]);
    }

    public function create($ristorante_id, $nome, $capacita, $posizione = null)
    {
        $data = [
            'ristorante_id' => $ristorante_id,
            'nome' => $nome,
            'capacita' => $capacita,
            'posizione' => $posizione,
            'disponibile' => 1
        ];
        
        return $this->db->insert('ristorante_tavoli', $data);
    }

    public function update($id, $nome, $capacita, $posizione, $disponibile)
    {
        $data = [
            'nome' => $nome,
            'capacita' => $capacita,
            'posizione' => $posizione,
            'disponibile' => $disponibile ? 1 : 0
        ];
        
        return $this->db->update('ristorante_tavoli', $data, 'id = ?', [$id]);
    }

    public function delete($id)
    {
        return $this->db->delete('ristorante_tavoli', 'id = ?', [$id]);
    }

    public function getStatistiche($ristorante_id)
    {
        $sql = "SELECT 
                    COUNT(*) as totale_tavoli,
                    SUM(CASE WHEN disponibile = 1 THEN 1 ELSE 0 END) as tavoli_disponibili,
                    SUM(capacita) as posti_totali
                FROM ristorante_tavoli 
                WHERE ristorante_id = ?";
        
        return $this->db->selectOne($sql, [$ristorante_id]);
    }
}
?>