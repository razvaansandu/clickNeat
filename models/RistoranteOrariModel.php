<?php
class RistoranteOrariModel
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getByRistorante($ristorante_id)
    {
        $sql = "SELECT * FROM ristorante_orari 
                WHERE ristorante_id = ? 
                ORDER BY giorno ASC";
        return $this->db->select($sql, [$ristorante_id]);
    }

    public function saveOrari($ristorante_id, $orari)
    {
        $this->db->delete('ristorante_orari', 'ristorante_id = ?', [$ristorante_id]);
        
        foreach ($orari as $giorno => $orario) {
            $data = [
                'ristorante_id' => $ristorante_id,
                'giorno' => $giorno,
                'apertura' => !empty($orario['apertura']) ? $orario['apertura'] : null,
                'chiusura' => !empty($orario['chiusura']) ? $orario['chiusura'] : null,
                'chiuso' => isset($orario['chiuso']) ? 1 : 0
            ];
            
            $this->db->insert('ristorante_orari', $data);
        }
        
        return true;
    }
    public function getOrariFormattati($ristorante_id)
    {
        $orari = $this->getByRistorante($ristorante_id);
        $giorni = ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'];
        
        $formattati = [];
        foreach ($orari as $orario) {
            $giorno_nome = $giorni[$orario['giorno']];
            
            if ($orario['chiuso']) {
                $formattati[$giorno_nome] = 'Chiuso';
            } else {
                $apertura = date('H:i', strtotime($orario['apertura']));
                $chiusura = date('H:i', strtotime($orario['chiusura']));
                $formattati[$giorno_nome] = "$apertura - $chiusura";
            }
        }
        
        return $formattati;
    }
}
?>