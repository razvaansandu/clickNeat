<?php
class MenuGiornalieroModel
{
    private $db;
    private $giorni = ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'];

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getByRistorante($ristorante_id)
    {
        $sql = "SELECT * FROM menus 
        WHERE ristorante_id = ? 
        ORDER BY FIELD(type, 'daily', 'fallback'), 
                 CASE WHEN type = 'daily' THEN weekday ELSE 7 END";
        $menus = $this->db->select($sql, [$ristorante_id]);
        
        foreach ($menus as &$menu) {
            $menu['piatti'] = $this->getPiattiByMenu($menu['id']);
        }
        
        return $menus;
    }

    public function getById($id)
    {
        $sql = "SELECT * FROM menus WHERE id = ?";
        $menu = $this->db->selectOne($sql, [$id]);
        
        if ($menu) {
            $menu['piatti'] = $this->getPiattiByMenu($menu['id']);
        }
        
        return $menu;
    }

    public function getByGiorno($ristorante_id, $giorno)
    {
        $sql = "SELECT * FROM menus 
        WHERE ristorante_id = ? AND type = 'daily' AND weekday = ? AND is_active = 1";
        $menu = $this->db->selectOne($sql, [$ristorante_id, $giorno]);
        
        if ($menu) {
            $menu['piatti'] = $this->getPiattiByMenu($menu['id']);
        }
        
        return $menu;
    }

    public function getFallback($ristorante_id)
    {
        $sql = "SELECT * FROM menus 
                WHERE ristorante_id = ? AND type = 'fallback' AND is_active = 1";
        $menu = $this->db->selectOne($sql, [$ristorante_id]);
        
        if ($menu) {
            $menu['piatti'] = $this->getPiattiByMenu($menu['id']);
        }
        
        return $menu;
    }

    public function getPiattiByMenu($menu_id)
    {
        $sql = "SELECT mi.* FROM menu_entries m
                JOIN menu_items mi ON m.menu_item_id = mi.id
                WHERE m.menu_id = ? AND mi.deleted_at IS NULL
                ORDER BY m.sort_order ASC";
        return $this->db->select($sql, [$menu_id]);
    }

    public function saveMenuGiornaliero($ristorante_id, $giorno, $titolo, $piatti_ids)
{
   $esistente = $this->db->selectOne(
    "SELECT id FROM menus WHERE ristorante_id = ? AND type = 'daily' AND weekday = ?",
    [$ristorante_id, $giorno]
    );
    
    if ($esistente) {
        $menu_id = $esistente['id'];
        $this->db->update('menus', [
            'title' => $titolo,
            'is_active' => 1
        ], 'id = ?', [$menu_id]);
    } else {
       $menu_id = $this->db->insert('menus', [
    'ristorante_id' => $ristorante_id,
    'type' => 'daily',
    'weekday' => $giorno,
    'title' => $titolo,
    'is_active' => 1
        ]);
    }
    
    if ($menu_id) {
        $this->db->delete('menu_entries', 'menu_id = ?', [$menu_id]);
        
        foreach ($piatti_ids as $index => $piatto_id) {
            $this->db->insert('menu_entries', [
                'menu_id' => $menu_id,
                'menu_item_id' => $piatto_id,
                'sort_order' => $index
            ]);
        }
    }
    
    return $menu_id;
}

    public function saveMenuFallback($ristorante_id, $titolo, $piatti_ids)
    {
        $esistente = $this->db->selectOne(
            "SELECT id FROM menus WHERE ristorante_id = ? AND type = 'fallback'",
            [$ristorante_id]
        );
        
        if ($esistente) {
            $menu_id = $esistente['id'];
            $this->db->update('menus', [
                'title' => $titolo,
                'is_active' => 1
            ], 'id = ?', [$menu_id]);
        } else {
            $menu_id = $this->db->insert('menus', [
                'ristorante_id' => $ristorante_id,
                'type' => 'fallback',
                'title' => $titolo,
                'is_active' => 1
            ]);
        }
        
        if ($menu_id) {
            $this->db->delete('menu_entries', 'menu_id = ?', [$menu_id]);
            
            foreach ($piatti_ids as $index => $piatto_id) {
                $this->db->insert('menu_entries', [
                    'menu_id' => $menu_id,
                    'menu_item_id' => $piatto_id,
                    'sort_order' => $index
                ]);
            }
        }
        
        return $menu_id;
    }

    public function delete($id)
    {
        return $this->db->delete('menus', 'id = ?', [$id]);
    }

    public function toggleActive($id)
    {
        $menu = $this->getById($id);
        if (!$menu) return false;
        
        $nuovo_stato = $menu['is_active'] ? 0 : 1;
        return $this->db->update('menus', ['is_active' => $nuovo_stato], 'id = ?', [$id]);
    }

    public function getMenuAttivo($ristorante_id, $giorno)
{
    $menu_giornaliero = $this->getByGiorno($ristorante_id, $giorno);
    
    if ($menu_giornaliero && !empty($menu_giornaliero['piatti'])) {
        return [
            'tipo' => 'giornaliero',
            'giorno' => $giorno,
            'titolo' => $menu_giornaliero['title'],
            'piatti' => $menu_giornaliero['piatti']
        ];
    }
    
    $menu_fallback = $this->getFallback($ristorante_id);
    
    if ($menu_fallback && !empty($menu_fallback['piatti'])) {
        return [
            'tipo' => 'fallback',
            'titolo' => $menu_fallback['title'],
            'piatti' => $menu_fallback['piatti']
        ];
    }
    
    return null;
}

    public function getGiornoNome($giorno)
    {
        return $this->giorni[$giorno] ?? 'Giorno non valido';
    }
}
?>