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
        var_dump($this->content["footer"]);//["@attributes"]["showPages"]);
        if($this->content["footer"]["@attributes"]["showPages"] == "true")
        {
            // Va a 1.5 cm dal fondo della pagina
            $this->SetY(-15);
            // Seleziona Arial corsivo 8
            $this->SetFont('Arial','I',8);
            // Stampa il numero di pagina centrato
            $this->Cell(0,10,'Page '.$this->PageNo(),0,0,'C');
        }
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

                //var_dump($this->content);

                if(in_array($this->content["@attributes"]["orientation"],(new PAGE_ORIENTATION())->getConst()) &&
                   in_array($this->content["@attributes"]["unit"],(new PAGE_UNIT())->getConst()) &&
                   in_array($this->content["@attributes"]["size"],(new PAGE_SIZE())->getConst()))
                {


                    $this->pdf = new FPDF($this->content["@attributes"]["orientation"],$this->content["@attributes"]["unit"],$this->content["@attributes"]["size"]);
                    $this->pdf->AddPage();

                    //GESTIRE EVENTUALE HEADER

                    //insert labels
                    for($i=0;$i<count($this->content["label"]);$i++)
                    {
                        if(in_array($this->content["label"][$i]["@attributes"]["font"],(new FONT_FAMILY())->getConst()) &&
                           in_array($this->content["label"][$i]["@attributes"]["font-style"],(new FONT_STYLE())->getConst()))
                        {
                            $this->pdf->SetFont($this->content["label"][$i]["@attributes"]["font"],
                                                $this->content["label"][$i]["@attributes"]["font-style"],
                                                $this->content["label"][$i]["@attributes"]["font-size"]);

                            //check id properties exist in data
                            if(array_key_exists($this->content["label"][$i]["@attributes"]["field"],$this->data_object))
                            {
                                $this->pdf->SetXY($this->content["label"][$i]["@attributes"]["x"],
                                                  $this->content["label"][$i]["@attributes"]["y"]);

                                $this->pdf->Cell($this->content["label"][$i]["@attributes"]["w"],
                                           $this->content["label"][$i]["@attributes"]["h"],
                                           $this->data_object[$this->content["label"][$i]["@attributes"]["field"]],
                                           intval($this->content["label"][$i]["@attributes"]["border"]),
                                           $this->content["label"][$i]["@attributes"]["ln"],
                                           $this->content["label"][$i]["@attributes"]["align"],
                                           $this->content["label"][$i]["@attributes"]["fill"]
                                          );
                            }
                            else
                            {
                                echo '{"error" : "The property '.$this->content["label"][$i]["@attributes"]["field"].' is not defined in data object"}';
                                return;
                            }
                        }
                        else
                        {
                            echo '{"error" : "Any < label >\'s attribute is invalid or missing"}';
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
                }

            }
            else
            {
                echo '{"error" : "Failed to open the template"}';
            }
        }
        catch (Exception $e)
        {
            echo '{"error" : "'.$e->getMessage().'"}';
        }
    }

}
