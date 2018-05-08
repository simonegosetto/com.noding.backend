<?php

require("ReportService/fpdf181/fpdf.php");
require("ReportService/FD_ReportService.php");

$pdf = new FD_ReportService("ReportService/template.xml",array('titolo' => 'Titolo di test',
                                                               'chef' => 'Simone Gosetto',
                                                               'procedimento' => 'asd sdas fsdf sadf sadf asdf '
                                                              ));
echo $pdf->createPDF();

