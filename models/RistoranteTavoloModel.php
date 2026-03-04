<?php
class RistoranteTavoloModel
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    public function getByRistorante($ristorante_id)
    {
        return $this->db->select(
            "SELECT * FROM ristorante_tavoli
             WHERE ristorante_id = ? AND disponibile = 1
             ORDER BY nome ASC",
            [$ristorante_id]
        );
    }

    public function getTavoliConDisponibilita($ristorante_id, $data, $ora_inizio, $ora_fine)
    {
        return $this->db->select(
            "SELECT t.*,
                CASE WHEN EXISTS (
                    SELECT 1 FROM prenotazioni_tavoli p
                    WHERE p.tavolo_id = t.id
                    AND p.data_prenotazione = ?
                    AND p.stato NOT IN ('cancellata')
                    AND p.ora_prenotazione < ? AND p.ora_fine > ?
                ) THEN 'occupato' ELSE 'libero' END AS disponibilita
             FROM ristorante_tavoli t
             WHERE t.ristorante_id = ? AND t.disponibile = 1
             ORDER BY t.nome",
            [$data, $ora_fine, $ora_inizio, $ristorante_id]
        );
    }

    public function updatePosizione($tavolo_id, $pos_x, $pos_y)
    {
        return $this->db->update('ristorante_tavoli',
            ['pos_x' => $pos_x, 'pos_y' => $pos_y],
            'id = ?', [$tavolo_id]
        );
    }

    public function create($ristorante_id, $nome, $capacita, $forma = 'quadrato', $zona = 'interno')
    {
        return $this->db->insert('ristorante_tavoli', [
            'ristorante_id' => $ristorante_id,
            'nome'          => $nome,
            'capacita'      => $capacita,
            'forma'         => $forma,
            'zona'          => $zona,
            'disponibile'   => 1,
            'pos_x'         => rand(50, 600),
            'pos_y'         => rand(50, 400)
        ]);
    }

    public function delete($tavolo_id)
    {
        return $this->db->update('ristorante_tavoli',
            ['disponibile' => 0], 'id = ?', [$tavolo_id]
        );
    }
}
