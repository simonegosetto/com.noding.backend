<?php

require("ReportService/FD_ReportEnum.php");

class FD_ReportService extends FPDF
{
    var $data_object;
    var $template = "";
    var $content;
    var $pdf;

    // Function for basic field validation (present and neither empty nor only white space
    private function IsNullOrEmptyString($question){
        return (!isset($question) || trim($question)==='');
    }

    //Costruttore
    function __construct($template,$data_object)
    {
        if($this->IsNullOrEmptyString($template))
        {
            echo '{"error" : "Invalid template"}';
        }

        if(!isset($data_object) || $data_object == null)
        {
            echo '{"error" : "Invalid data"}';
            return;
        }

        $this->template = $template;
        $this->data_object = $data_object;
    }

    // Page header
    function Header()
    {
        // Logo
        $this->Image('logo.png',10,6,30);
        // Arial bold 15
        $this->SetFont('Arial','B',15);
        // Move to the right
        $this->Cell(80);
        // Title
        $this->Cell(30,10,'Title',1,0,'C');
        // Line break
        $this->Ln(20);
    }

    // Page footer
    function Footer()
    {
        // Va a 1.5 cm dal fondo della pagina
        $this->pdf->SetY(-35);
        // Seleziona Arial corsivo 8
        $this->pdf->SetFont('Arial','I',8);
        // Stampa il numero di pagina centrato
        $this->pdf->Cell(0,10,'Page '.$this->pdf->PageNo(),0,0,'R');
    }

    public function createPDF()
    {
        try
        {
            if (file_exists($this->template))
            {
                //take xml template
                $xml = simplexml_load_file($this->template);
                $this->content = json_decode(json_encode($xml),TRUE);

                //var_dump($this->content);return;

                if(in_array($this->content["@attributes"]["orientation"],(new PAGE_ORIENTATION())->getConst()) &&
                   in_array($this->content["@attributes"]["unit"],(new PAGE_UNIT())->getConst()) &&
                   in_array($this->content["@attributes"]["size"],(new PAGE_SIZE())->getConst()))
                {


                    $this->pdf = new FPDF($this->content["@attributes"]["orientation"],$this->content["@attributes"]["unit"],$this->content["@attributes"]["size"]);
                    $this->pdf->AddPage();

                    //GESTIRE EVENTUALE HEADER

                    //insert the keys of array
                    $keys = array_keys($this->content);
                    for($i=0;$i<count($keys);$i++)
                    {
                        if($keys[$i] == "@attributes" || $keys[$i] == "comment" || $keys[$i] == "header" || $keys[$i] == "footer") continue;

                        //check id properties exist in data
                        if(array_key_exists($keys[$i],$this->data_object) || substr($keys[$i],0,2) == "ln")
                        {
                            /* @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ */

                            ////////////////////////////// TEXT ////////////////////////////////////
                            if($this->content[$keys[$i]]["@attributes"]["type"] == "text")
                            {
                                if(in_array($this->content[$keys[$i]]["@attributes"]["font"],(new FONT_FAMILY())->getConst()) &&
                                   in_array($this->content[$keys[$i]]["@attributes"]["font-style"],(new FONT_STYLE())->getConst()))
                                {
                                    $this->pdf->SetFont($this->content[$keys[$i]]["@attributes"]["font"],
                                                        $this->content[$keys[$i]]["@attributes"]["font-style"],
                                                        $this->content[$keys[$i]]["@attributes"]["font-size"]);

                                        if(array_key_exists("x",$this->content[$keys[$i]]["@attributes"]))
                                            $this->pdf->SetX($this->content[$keys[$i]]["@attributes"]["x"]);

                                        if(array_key_exists("y",$this->content[$keys[$i]]["@attributes"]))
                                           $this->pdf->SetY($this->content[$keys[$i]]["@attributes"]["y"]);

                                        $this->pdf->Cell($this->content[$keys[$i]]["@attributes"]["w"],
                                                         $this->content[$keys[$i]]["@attributes"]["h"],
                                                         $this->data_object[$keys[$i]],
                                                         $this->content[$keys[$i]]["@attributes"]["border"],
                                                         $this->content[$keys[$i]]["@attributes"]["ln"],
                                                         $this->content[$keys[$i]]["@attributes"]["align"],
                                                         $this->content[$keys[$i]]["@attributes"]["fill"]
                                                      );
                                }
                                else
                                {
                                    echo '{"error" : "Any '.$keys[$i].'\'s attribute is invalid or missing"}';
                                    return;
                                }
                            }

                            ////////////////////////////// IMAGE ////////////////////////////////////
                            else if($this->content[$keys[$i]]["@attributes"]["type"] == "image")
                            {
                                //take image format
                                $formato = pathinfo($this->data_object[$keys[$i]], PATHINFO_EXTENSION);
                                if(in_array(strtolower($formato),(new IMAGE_FORMAT())->getConst()))
                                {
                                    if(!$this->IsNullOrEmptyString($this->data_object[$keys[$i]]))
                                        $this->pdf->Image($this->data_object[$keys[$i]],
                                                          $this->content[$keys[$i]]["@attributes"]["x"],
                                                          $this->content[$keys[$i]]["@attributes"]["y"],
                                                          $this->content[$keys[$i]]["@attributes"]["w"],
                                                          $this->content[$keys[$i]]["@attributes"]["h"]);
                                }
                                else
                                {
                                    echo '{"error" : "The image '.$keys[$i].' have invalid extension"}';
                                    return;
                                }
                            }
                            ////////////////////////////// LN ////////////////////////////////////
                            else if($this->content[$keys[$i]]["@attributes"]["type"] == "ln")
                            {
                                $this->pdf->Ln($this->content[$keys[$i]]["@attributes"]["h"]);
                            }
                            ////////////////////////////// TABLE ////////////////////////////////////
                            else if($this->content[$keys[$i]]["@attributes"]["type"] == "table")
                            {
                                //imposto coordinate
                                if(array_key_exists("x",$this->content[$keys[$i]]["@attributes"]))
                                    $this->pdf->SetX($this->content[$keys[$i]]["@attributes"]["x"]);

                                if(array_key_exists("y",$this->content[$keys[$i]]["@attributes"]))
                                   $this->pdf->SetY($this->content[$keys[$i]]["@attributes"]["y"]);

                                // Header
                                //$this->pdf->SetFont('Arial');
                                $this->pdf->SetFillColor(225);
                                $this->pdf->SetTextColor(100);
                                foreach($this->content[$keys[$i]] as $name => $col)
                                {
                                    if(array_key_exists("type",$col)) continue;
                                    $this->pdf->SetFont($col["@attributes"]["font"],
                                                        $col["@attributes"]["font-style"],
                                                        $col["@attributes"]["font-size"]);
                                    $this->pdf->Cell(40,6,$col["@attributes"]["headertext"],1,0 ,'C',1);
                                }
                                $this->pdf->Ln();

                                // Data
                                $this->pdf->SetFillColor(255);
                                $this->pdf->SetTextColor(0);
                                foreach($this->data_object[$keys[$i]] as $row)
                                {
                                    foreach($this->content[$keys[$i]] as $name => $col)
                                    {
                                        if(array_key_exists("type",$col)) continue;
                                        $this->pdf->SetFont($col["@attributes"]["font"],
                                                            $col["@attributes"]["font-style"],
                                                            $col["@attributes"]["font-size"]);
                                        $this->pdf->Cell(40,6,$row[$name],1);
                                    }
                                    $this->pdf->Ln();
                                }
                            }
                            /* @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ */

                        }
                        else
                        {
                            echo '{"error" : "The property '.$keys[$i].' is not defined in data array"}';
                            return;
                        }
                    }


                    //TUTTI I CONTROLLI DI VALIDITà DEL TEMPLATE E DEI CAMPI LI FARò SUCCESSIVAMENTE


                    //GESTIRE EVENTUALE FOOTER

                    $this->pdf->Output();
                }
                else
                {
                    echo '{"error" : "Any < report >\'s attribute is invalid or missing"}';
                    return;
                }

            }
            else
            {
                echo '{"error" : "Failed to open the template"}';
                return;
            }
        }
        catch (Exception $e)
        {
            echo '{"error" : "'.$e->getMessage().'"}';
        }
    }

}
