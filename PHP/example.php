<?php


//require("WebTools/FD_HTTP.php");
require("WebTools/FD_Logger.php");

//$report = new FD_HTTP();
//echo $report->Post("https://test.costofacile.it/zzz/ReportService/FD_ReportService.php",null);

session_start();

$_SESSION["ReportData"] = array(
                "template" => "../Reports/template.xml",
                "data_object" => array('titolo' => 'Titolo di test',
                                        'chef' => 'Simone Gosetto',
                                        'procedimento' => 'asd sdas fsdf sadf sadf asdf ',
                                        'image' => 'https://www.google.it/images/branding/googlelogo/2x/googlelogo_color_272x92dp.png',
                                        'ingredienti' => array(
                                            array("nome" => "farina", "quantita" => 100, "perc" => 30),
                                            array("nome" => "olio", "quantita" => 70, "perc" => 20)
                                        )
                                ),
                "logger" => new FD_Logger(null)
        );



Header("Location: ReportService/FD_ReportService.php");


/*,
        array(
            "template" => "ReportService/template.xml",
            "data_object" => array('titolo' => 'Titolo di test',
                                    'chef' => 'Simone Gosetto',
                                    'procedimento' => 'asd sdas fsdf sadf sadf asdf ',
                                    'image' => 'https://www.google.it/images/branding/googlelogo/2x/googlelogo_color_272x92dp.png',
                                    'ingredienti' => array(
                                        array("nome" => "farina", "quantita" => 100, "perc" => 30),
                                        array("nome" => "olio", "quantita" => 70, "perc" => 20)
                                    )
                              ),
            new FD_Logger(null)
        )*/


/*
$pdf = new FD_ReportService("ReportService/template.xml",
                            array('titolo' => 'Titolo di test',
                               'chef' => 'Simone Gosetto',
                               'procedimento' => 'asd sdas fsdf sadf sadf asdf ',
                               'image' => 'http://elba.local/Images/logo_gruppo.gif',
                               'ingredienti' => array(
                                    array("nome" => "farina", "quantita" => 100, "perc" => 30),
                                    array("nome" => "olio", "quantita" => 70, "perc" => 20)
                               )
                              ));
//echo $pdf->createPDF();
*/
