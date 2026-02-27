<?php
class PrenotazioneTavoloModel
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    public function isTavoloDisponibile($tavolo_id, $data, $ora_inizio, $ora_fine)
    {
        $result = $this->db->selectOne(
            "SELECT COUNT(*) as count FROM prenotazioni_tavoli
             WHERE tavolo_id = ?
             AND data_prenotazione = ?
             AND stato NOT IN ('cancellata')
             AND ora_prenotazione < ? AND ora_fine > ?",
            [$tavolo_id, $data, $ora_fine, $ora_inizio]
        );
        return $result['count'] == 0;
    }

    public function isOrarioValido($data, $ora)
    {
        $timestamp = strtotime("$data $ora");
        if ($timestamp < time()) return false;

        $ora_int = (int) date('H', strtotime($ora));
        return $ora_int >= 12 && $ora_int < 22;
    }

    public function create($dati)
    {
        $dati['ora_fine'] = date('H:i:s',
            strtotime($dati['ora_prenotazione']) + 90 * 60
        );
        $dati['stato'] = 'in_attesa';
        return $this->db->insert('prenotazioni_tavoli', $dati);
    }

    public function getByRistoranteEData($ristorante_id, $data)
    {
        return $this->db->select(
            "SELECT p.*, t.nome as tavolo_nome, t.capacita, t.pos_x, t.pos_y, t.forma
             FROM prenotazioni_tavoli p
             JOIN ristorante_tavoli t ON p.tavolo_id = t.id
             WHERE p.ristorante_id = ?
             AND p.data_prenotazione = ?
             AND p.stato != 'cancellata'
             ORDER BY p.ora_prenotazione ASC",
            [$ristorante_id, $data]
        );
    }

    public function getByCliente($cliente_id)
    {
        return $this->db->select(
            "SELECT p.*, t.nome as tavolo_nome, r.nome as ristorante_nome
             FROM prenotazioni_tavoli p
             JOIN ristorante_tavoli t ON p.tavolo_id = t.id
             JOIN ristoranti r ON p.ristorante_id = r.id
             WHERE p.cliente_id = ?
             ORDER BY p.data_prenotazione DESC, p.ora_prenotazione DESC",
            [$cliente_id]
        );
    }

    public function updateStato($prenotazione_id, $stato)
    {
        return $this->db->update('prenotazioni_tavoli',
            ['stato' => $stato], 'id = ?', [$prenotazione_id]
        );
    }
}
