<?php
require_once __DIR__ . '/../vendor/autoload.php';

class FatturaModel
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    public function generateNumeroFattura()
    {
        $anno = date('Y');
        $last = $this->db->selectOne(
            "SELECT numero FROM fatture WHERE numero LIKE ? ORDER BY id DESC LIMIT 1",
            ["FAT-$anno-%"]
        );
        $progressivo = $last ? intval(substr($last['numero'], -4)) + 1 : 1;
        return "FAT-$anno-" . str_pad($progressivo, 4, '0', STR_PAD_LEFT);
    }

    public function createFattura($order_id, $user_id)
    {
        $order = $this->db->selectOne("SELECT * FROM orders WHERE id = ?", [$order_id]);
        if (!$order) return false;

        $user = $this->db->selectOne("SELECT * FROM users WHERE id = ?", [$user_id]);
        if (!$user) return false;

        $iva_perc      = 22.00;
        $importo_netto = $order['total_amount'] / (1 + $iva_perc / 100);
        $iva_importo   = $order['total_amount'] - $importo_netto;

        $numero = $this->generateNumeroFattura();

        $fattura_id = $this->db->insert('fatture', [
            'order_id'         => $order_id,
            'user_id'          => $user_id,
            'numero'           => $numero,
            'importo_netto'    => round($importo_netto, 2),
            'iva_perc'         => $iva_perc,
            'iva_importo'      => round($iva_importo, 2),
            'totale'           => $order['total_amount'],
            'cliente_nome'     => $user['username'],
            'cliente_email'    => $user['email'],
            'cliente_cf'       => $user['codice_fiscale'],
            'cliente_piva'     => $user['partita_iva'],
            'cliente_indirizzo'=> $user['indirizzo'],
            'cliente_citta'    => $user['citta'],
            'cliente_cap'      => $user['cap'],
            'cliente_provincia'=> $user['provincia']
        ]);

        if ($fattura_id) {
            $this->generatePDF($fattura_id);
            return $fattura_id;
        }
        return false;
    }

    public function generatePDF($fattura_id)
    {
        $fattura = $this->db->selectOne("SELECT * FROM fatture WHERE id = ?", [$fattura_id]);
        if (!$fattura) return false;

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('ClickNeat');
        $pdf->SetAuthor('ClickNeat');
        $pdf->SetTitle("Fattura {$fattura['numero']}");

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 24);
        $pdf->Cell(0, 10, 'FATTURA', 0, 1, 'C');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, 'ClickNeat S.r.l.', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'Via Roma 123, 93012 Gela (CL)', 0, 1);
        $pdf->Cell(0, 5, 'P.IVA: 01234567890', 0, 1);
        $pdf->Ln(8);

        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(60, 6, "Numero: {$fattura['numero']}", 0, 0);
        $pdf->Cell(0, 6, 'Data: ' . date('d/m/Y', strtotime($fattura['created_at'])), 0, 1);
        $pdf->Ln(8);

        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, 'INTESTATA A:', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, $fattura['cliente_nome'], 0, 1);
        if ($fattura['cliente_cf']) $pdf->Cell(0, 5, "CF: {$fattura['cliente_cf']}", 0, 1);
        if ($fattura['cliente_piva']) $pdf->Cell(0, 5, "P.IVA: {$fattura['cliente_piva']}", 0, 1);
        if ($fattura['cliente_indirizzo']) {
            $indirizzo = $fattura['cliente_indirizzo'];
            if ($fattura['cliente_cap']) $indirizzo .= ", {$fattura['cliente_cap']}";
            if ($fattura['cliente_citta']) $indirizzo .= " {$fattura['cliente_citta']}";
            if ($fattura['cliente_provincia']) $indirizzo .= " ({$fattura['cliente_provincia']})";
            $pdf->Cell(0, 5, $indirizzo, 0, 1);
        }
        $pdf->Ln(10);

        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(120, 8, 'Descrizione', 1, 0, 'L', true);
        $pdf->Cell(35, 8, 'Importo', 1, 0, 'R', true);
        $pdf->Cell(35, 8, 'IVA', 1, 1, 'R', true);

        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(120, 8, 'Servizio di ordinazione ClickNeat', 1, 0, 'L');
        $pdf->Cell(35, 8, '€ ' . number_format($fattura['importo_netto'], 2, ',', '.'), 1, 0, 'R');
        $pdf->Cell(35, 8, '€ ' . number_format($fattura['iva_importo'], 2, ',', '.'), 1, 1, 'R');

        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(120, 10, '', 0, 0);
        $pdf->Cell(35, 10, 'TOTALE:', 1, 0, 'L', true);
        $pdf->Cell(35, 10, '€ ' . number_format($fattura['totale'], 2, ',', '.'), 1, 1, 'R', true);

        $filename = "fattura_{$fattura['numero']}.pdf";
        $filepath = __DIR__ . "/../public/fatture/$filename";
        
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        $pdf->Output($filepath, 'F');

        $this->db->update('fatture', ['pdf_path' => "fatture/$filename"], 'id = ?', [$fattura_id]);

        return $filepath;
    }

    public function getFatturaByOrderId($order_id)
    {
        return $this->db->selectOne("SELECT * FROM fatture WHERE order_id = ?", [$order_id]);
    }

    public function sendFatturaEmail($fattura_id)
    {
        $fattura = $this->db->selectOne("SELECT * FROM fatture WHERE id = ?", [$fattura_id]);
        if (!$fattura || !$fattura['pdf_path']) return false;

        $mail = require __DIR__ . "/../src/mailer.php";

        try {
            $mail->addAddress($fattura['cliente_email']);
            $mail->Subject = "Fattura {$fattura['numero']} - ClickNeat";
            $mail->Body    = "
                <h2>Ciao {$fattura['cliente_nome']},</h2>
                <p>In allegato trovi la fattura per il tuo ordine.</p>
                <p><strong>Numero fattura:</strong> {$fattura['numero']}<br>
                <strong>Importo:</strong> € " . number_format($fattura['totale'], 2, ',', '.') . "</p>
                <p>Grazie per aver scelto ClickNeat!</p>
            ";

            $filepath = __DIR__ . "/../public/{$fattura['pdf_path']}";
            $mail->addAttachment($filepath, "Fattura_{$fattura['numero']}.pdf");

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Errore invio fattura: " . $mail->ErrorInfo);
            return false;
        }
    }

    public function getUserBillingData($user_id)
{
    return $this->db->selectOne(
        "SELECT codice_fiscale, partita_iva, indirizzo, citta, cap, provincia
         FROM users WHERE id = ?",
        [$user_id]
    );
}

}
