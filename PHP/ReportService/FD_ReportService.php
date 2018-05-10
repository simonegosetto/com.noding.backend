<?php

require("ReportService/FD_ReportEnum.php");

class PDF extends FPDF
{
    // Page header
    function Header()
    {
        /*
        $this->SetFont('Arial','I',8);
        // Title
        $this->Cell(30,10,'Title',1,0,'C');
        // Line break
        $this->Ln(20);
        */
    }

    // Page footer
    function Footer()
    {
        // Va a 1.5 cm dal fondo della pagina
        $this->SetY(-25);
        // Seleziona Arial corsivo 8
        $this->SetFont('Arial','I',8);
        // Stampa il numero di pagina centrato
        $this->Cell(0,10,'P.'.$this->PageNo(),0,0,'R');
    }
}

class FD_ReportService extends PDF
{
    var $data_object;
    var $template = "";
    var $content;
    var $pdf;

    // Function for basic field validation (present and neither empty nor only white space
    private function IsNullOrEmptyString($question)
    {
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

    public function createPDF()
    {
        try
        {
            ob_end_clean();

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


                    $this->pdf = new PDF($this->content["@attributes"]["orientation"],$this->content["@attributes"]["unit"],$this->content["@attributes"]["size"]);
                    $this->pdf->AddPage();

                    //GESTIRE EVENTUALE HEADER

                    //insert the keys of array
                    $keys = array_keys($this->content);
                    for($i=0;$i<count($keys);$i++)
                    {
                        if($keys[$i] == "@attributes" || $keys[$i] == "comment" || $keys[$i] == "header" || $keys[$i] == "footer") continue;

                        if($keys[$i] == "author") $this->pdf->SetAuthor($this->content[$keys[$i]]);
                        else if ($keys[$i] == "title") $this->pdf->SetTitle($this->content[$keys[$i]]);
                        else

                        //sviluppare campo CUSTOM !!!!!!!!!
                        //check id properties exist in data array
                        if(array_key_exists($keys[$i],$this->data_object) || substr($keys[$i],0,2) == "ln")
                        {
                            /* @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ */
                            //imposto coordinate
                            if(array_key_exists("x",$this->content[$keys[$i]]["@attributes"])) $x=$this->content[$keys[$i]]["@attributes"]["x"];
                            if(array_key_exists("xx",$this->content[$keys[$i]]["@attributes"])) $x=$this->pdf->GetX()+$this->content[$keys[$i]]["@attributes"]["xx"];
                            if(array_key_exists("y",$this->content[$keys[$i]]["@attributes"])) $y=$this->content[$keys[$i]]["@attributes"]["y"];
                            if(array_key_exists("yy",$this->content[$keys[$i]]["@attributes"])) $y=$this->pdf->GetY()+$this->content[$keys[$i]]["@attributes"]["yy"];

                            ////////////////////////////// TEXT ////////////////////////////////////
                            if($this->content[$keys[$i]]["@attributes"]["type"] == "text")
                            {
                                if(in_array($this->content[$keys[$i]]["@attributes"]["font"],(new FONT_FAMILY())->getConst()) &&
                                   in_array($this->content[$keys[$i]]["@attributes"]["font-style"],(new FONT_STYLE())->getConst()))
                                {
                                    $this->pdf->SetFont($this->content[$keys[$i]]["@attributes"]["font"],
                                                        $this->content[$keys[$i]]["@attributes"]["font-style"],
                                                        $this->content[$keys[$i]]["@attributes"]["font-size"]);

                                    if(isset($x))
                                        $this->pdf->SetX($x);

                                    if(isset($y))
                                        $this->pdf->SetY($y);

                                    $this->pdf->Cell($this->content[$keys[$i]]["@attributes"]["w"],
                                                     $this->content[$keys[$i]]["@attributes"]["h"],
                                                     iconv('UTF-8', 'windows-1252',$this->data_object[$keys[$i]]),
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
                                                          $x,
                                                          $y,
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
                                if(isset($x) && isset($y))
                                    $this->pdf->SetXY($x,$y);

                                // Header
                                $this->pdf->SetFillColor(225);
                                $this->pdf->SetTextColor(100);
                                foreach($this->content[$keys[$i]] as $name => $col)
                                {
                                    if(array_key_exists("type",$col)) continue;
                                    $this->pdf->SetFont($col["@attributes"]["font"],
                                                        $col["@attributes"]["font-style"],
                                                        $col["@attributes"]["font-size"]);
                                    $this->pdf->Cell(40,6,iconv('UTF-8', 'windows-1252',$col["@attributes"]["headertext"]),1,0 ,'C',1);
                                }
                                $this->pdf->Ln();

                                // Data
                                $this->pdf->SetFillColor(255);
                                $this->pdf->SetTextColor(0);
                                foreach($this->data_object[$keys[$i]] as $row)
                                {
                                    //force the x
                                    if(array_key_exists("x",$this->content[$keys[$i]]["@attributes"]))
                                        $this->pdf->SetXY($this->content[$keys[$i]]["@attributes"]["x"],$this->pdf->GetY());

                                    if(array_key_exists("xx",$this->content[$keys[$i]]["@attributes"]))
                                        $this->pdf->SetXY($this->pdf->GetX()+$this->content[$keys[$i]]["@attributes"]["xx"],$this->pdf->GetY());

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

                        unset($x);
                        unset($y);
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
