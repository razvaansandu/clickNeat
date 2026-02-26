<?php
class MenuRistoratoreModel
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getByRestaurant($restaurant_id)
    {
        $results = $this->db->select(
            "SELECT * FROM menu_items WHERE restaurant_id = ? AND deleted_at IS NULL ORDER BY created_at DESC",
            [$restaurant_id]
        );
        
        // Decodifica gli allergeni per ogni piatto
        foreach ($results as &$item) {
            if (isset($item['allergeni']) && !empty($item['allergeni'])) {
                $item['allergeni_array'] = json_decode($item['allergeni'], true);
            } else {
                $item['allergeni_array'] = [];
            }
        }
        
        return $results;
    }

    public function getById($id)
    {
        $result = $this->db->selectOne(
            "SELECT * FROM menu_items WHERE id = ? AND deleted_at IS NULL",
            [$id]
        );
        
        // Decodifica gli allergeni
        if ($result && isset($result['allergeni']) && !empty($result['allergeni'])) {
            $result['allergeni_array'] = json_decode($result['allergeni'], true);
        } elseif ($result) {
            $result['allergeni_array'] = [];
        }
        
        return $result;
    }

    public function create($restaurant_id, $name, $description, $price, $categoria = "altro", $image_url = null, $allergeni = '[]')
    {
        $final_image = (!empty($image_url)) ? $image_url : null;
        $final_category = (!empty($categoria)) ? $categoria : "altro";
        
        // Verifica che gli allergeni siano un JSON valido
        if (is_array($allergeni)) {
            $allergeni = json_encode($allergeni);
        } elseif (!is_string($allergeni) || !$this->isJson($allergeni)) {
            $allergeni = '[]';
        }
        
        return $this->db->insert('menu_items', [
            'restaurant_id' => $restaurant_id,
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'image_url' => $final_image,
            'categoria' => $final_category,
            'allergeni' => $allergeni,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function update($id, $name, $description, $price, $image_url = null, $allergeni = null)
    {
        $data = [
            'name' => $name,
            'description' => $description,
            'price' => $price 
        ];

        if ($image_url !== null) {
            $data['image_url'] = $image_url;
        }
        
        if ($allergeni !== null) {
            // Verifica che gli allergeni siano un JSON valido
            if (is_array($allergeni)) {
                $data['allergeni'] = json_encode($allergeni);
            } elseif (!is_string($allergeni) || !$this->isJson($allergeni)) {
                $data['allergeni'] = '[]';
            } else {
                $data['allergeni'] = $allergeni;
            }
        }

        return $this->db->update('menu_items', $data, 'id = ?', [$id]);
    }

    public function update_piatto($id, $data)
    {
        // Gestione speciale per gli allergeni
        if (isset($data['allergeni'])) {
            if (is_array($data['allergeni'])) {
                $data['allergeni'] = json_encode($data['allergeni']);
            } elseif (!$this->isJson($data['allergeni'])) {
                $data['allergeni'] = '[]';
            }
        }
        
        return $this->db->update('menu_items', $data, 'id = ?', [$id]);
    }

    public function delete($id)
    {
        return $this->db->update(
            'menu_items',
            ['deleted_at' => date('Y-m-d H:i:s')],
            'id = ?',
            [$id]
        );
    }

    public function delete_piatto($id)
    {
        // Prima verifica se il piatto è presente in qualche ordine
        $orderCheck = $this->db->selectOne(
            "SELECT id FROM order_items WHERE menu_item_id = ? LIMIT 1",
            [$id]
        );
        
        if ($orderCheck) {
            // Il piatto è in un ordine, non possiamo eliminarlo fisicamente
            // ma possiamo nasconderlo con soft delete
            return $this->db->update(
                'menu_items',
                ['deleted_at' => date('Y-m-d H:i:s')],
                'id = ?',
                [$id]
            );
        } else {
            // Il piatto non è in nessun ordine, possiamo eliminarlo definitivamente
            return $this->db->delete('menu_items', 'id = ?', [$id]);
        }
    }

    /**
     * Ottiene tutti i piatti con un determinato allergene
     */
    public function getByAllergene($restaurant_id, $allergene)
    {
        $results = $this->db->select(
            "SELECT * FROM menu_items WHERE restaurant_id = ? AND deleted_at IS NULL",
            [$restaurant_id]
        );
        
        // Filtra manualmente i risultati per l'allergene specificato
        $filtered = [];
        foreach ($results as $item) {
            if (isset($item['allergeni']) && !empty($item['allergeni'])) {
                $allergeni = json_decode($item['allergeni'], true);
                if (is_array($allergeni) && in_array($allergene, $allergeni)) {
                    $item['allergeni_array'] = $allergeni;
                    $filtered[] = $item;
                }
            }
        }
        
        return $filtered;
    }

    /**
     * Ottiene statistiche sugli allergeni per un ristorante
     */
    public function getAllergeniStats($restaurant_id)
    {
        $results = $this->db->select(
            "SELECT allergeni FROM menu_items WHERE restaurant_id = ? AND deleted_at IS NULL AND allergeni IS NOT NULL",
            [$restaurant_id]
        );
        
        $stats = [];
        foreach ($results as $item) {
            if (!empty($item['allergeni'])) {
                $allergeni = json_decode($item['allergeni'], true);
                if (is_array($allergeni)) {
                    foreach ($allergeni as $allergene) {
                        if (!isset($stats[$allergene])) {
                            $stats[$allergene] = 0;
                        }
                        $stats[$allergene]++;
                    }
                }
            }
        }
        
        arsort($stats);
        return $stats;
    }

    /**
     * Verifica se una stringa è un JSON valido
     */
    private function isJson($string)
    {
        if (!is_string($string)) {
            return false;
        }
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * Ottiene tutti gli allergeni unici presenti nei piatti del ristorante
     */
    public function getAllergeniUnici($restaurant_id)
    {
        $results = $this->db->select(
            "SELECT allergeni FROM menu_items WHERE restaurant_id = ? AND deleted_at IS NULL AND allergeni IS NOT NULL",
            [$restaurant_id]
        );
        
        $allergeniUnici = [];
        foreach ($results as $item) {
            if (!empty($item['allergeni'])) {
                $allergeni = json_decode($item['allergeni'], true);
                if (is_array($allergeni)) {
                    foreach ($allergeni as $allergene) {
                        if (!in_array($allergene, $allergeniUnici)) {
                            $allergeniUnici[] = $allergene;
                        }
                    }
                }
            }
        }
        
        sort($allergeniUnici);
        return $allergeniUnici;
    }

    /**
     * Aggiorna solo gli allergeni di un piatto
     */
    public function updateAllergeni($id, $allergeni)
    {
        if (is_array($allergeni)) {
            $allergeniJson = json_encode($allergeni);
        } elseif ($this->isJson($allergeni)) {
            $allergeniJson = $allergeni;
        } else {
            $allergeniJson = '[]';
        }
        
        return $this->db->update(
            'menu_items',
            ['allergeni' => $allergeniJson],
            'id = ?',
            [$id]
        );
    }
}
?>