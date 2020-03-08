// inizializzazione
$mpdf = new \Mpdf\Mpdf([
    'margin_left' => 0,
    'margin_right' => 0,
    'margin_top' => 0,
    'margin_bottom' => 0,
    'margin_header' => 0,
    'margin_footer' => 0,
    'default_font_size' => 16,
    // 'default_font' => 'potatoesandpeas',
    // 'debugfonts' => true,
    'tempDir' => 'mPDF/tmp'
]);
$mpdf->SetTitle("Scheda Atleta");
$mpdf->SetAuthor("Silvano Fedi");
$mpdf->SetDisplayMode('fullpage');

// HTML
$html = '<div class="scheda"></div>';
$html .= '<div class="scritta" style="position: absolute; left: 130px; top: 230px">'.$result['recordset'][0]['cognome'].'</div>';
$html .= '<div class="scritta" style="position: absolute; right: 150px; top: 230px">'.$result['recordset'][0]['nome'].'</div>';
$html .= '<div class="scritta" style="position: absolute; left: 130px; top: 265px">'.date("d/m/Y", strtotime($result['recordset'][0]['data_nascita'])).'</div>';
$html .= '<div class="scritta" style="position: absolute; left: 400px; top: 265px">'.$result['recordset'][0]['luogo_nascita'].'</div>';
$html .= '<div class="scritta" style="position: absolute; left: 130px; top: 300px">'.$result['recordset'][0]['professione'].'</div>';
$html .= '<div class="scritta" style="position: absolute; left: 80px; top: 335px">'.$result['recordset'][0]['indirizzo'].'</div>';
$html .= '<div class="scritta" style="position: absolute; left: 540px; top: 335px">'.$result['recordset'][0]['cap'].'</div>';
$html .= '<div class="scritta" style="position: absolute; left: 700px; top: 335px">'.$result['recordset'][0]['provincia'].'</div>';
$html .= '<div class="scritta" style="position: absolute; left: 130px; top: 370px">'.$result['recordset'][0]['telefono'].'</div>';
$html .= '<div class="scritta" style="position: absolute; left: 380px; top: 370px">'.$result['recordset'][0]['cellulare'].'</div>';
$html .= '<div class="scritta" style="position: absolute; left: 650px; top: 370px">'.$result['recordset'][0]['fax'].'</div>';
$html .= '<div class="scritta" style="position: absolute; left: 130px; top: 405px">'.$result['recordset'][0]['email'].'</div>';
$html .= '<div class="scritta" style="position: absolute; left: 530px; top: 405px">'.$result['recordset'][0]['codice_fiscale'].'</div>';

$html .= '<div class="scritta" style="position: absolute; left: 220px; top: 530px">'.($result['recordset'][0]['scadenza_certificato'] != ''
                                                                                     ? date("d/m/Y", strtotime($result['recordset'][0]['scadenza_certificato']))
                                                                                     : '').'</div>';

$html .= '<div class="scritta" style="position: absolute; left: 140px; top: 565px">'.$result['recordset'][0]['uisp'].'</div>';
$html .= '<div class="scritta" style="position: absolute; left: 375px; top: 565px">'.$result['recordset'][0]['csi'].'</div>';
$html .= '<div class="scritta" style="position: absolute; left: 645px; top: 565px">'.$result['recordset'][0]['fidal'].'</div>';


