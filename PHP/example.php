<?php

require("ReportService/fpdf181/fpdf.php");
require("ReportService/FD_ReportService.php");

$pdf = new FD_ReportService("ReportService/template.xml",array('titolo' => 'Titolo di test',
                                                               'chef' => 'Simone Gosetto',
                                                               'procedimento' => 'asd sdas fsdf sadf sadf asdf ',
                                                               'image' => 'http://elba.local/Images/logo_gruppo.gif',
                                                               'ingredienti' => array(
                                                                    array("nome" => "farina", "quantita" => 100, "perc" => 30),
                                                                    array("nome" => "olio", "quantita" => 70, "perc" => 20)
                                                               )
                                                              ));
echo $pdf->createPDF();

