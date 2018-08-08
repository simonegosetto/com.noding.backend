<?php

require("ReportService/FD_ReportEnum.php");


/*
 potrei fare una classe PADRE->PDF in un file separato e ridefinirla tutte le volte 
 prima di instanziare la classe FIGLIO->FD_ReportEngine (eval)


 POTREBBE FUNZIONARE !!!!!!!!!!!!!!!

*/

function IsNullOrEmptyString($question)
{
    return (!isset($question) || trim($question)==='');
}

function isAssoc(array $arr)
{
    if (array() === $arr) return false;
    return array_keys($arr) !== range(0, count($arr) - 1);
}

function global_eval($string)
{
    eval($string);
}

class FD_ReportService
{
    var $log;

    //Costruttore
    function __construct($template,$data_object,$logger)
    {
        $this->log = $logger;

        if(IsNullOrEmptyString($template))
        {
            $this->log->lwrite('[ERRORE] - Invalid template');
            echo '{"error" : "Invalid template"}';
        }

        //leggo template
        $xml = simplexml_load_file($template, 'SimpleXMLElement', LIBXML_NOCDATA);

        //compongo classe master
        $master_class = file_get_contents("FD_ReportMaster.php");

        //gestisco header
        if(strpos($master_class,"header") !== false)
        {
            $master_class = str_replace("//<%HEADER%>",json_decode(json_encode($xml),TRUE)["header"],$master_class);
        }
        else
        {
            $master_class = str_replace("//<%HEADER%>","",$master_class);
        }

        //gestisco footer
        if(strpos($master_class,"footer") !== false)
        {
            $master_class = str_replace("//<%FOOTER%>",json_decode(json_encode($xml),TRUE)["footer"],$master_class);
        }
        else
        {
            $master_class = str_replace("//<%FOOTER%>","",$master_class);
        }

        global_eval($master_class);

        $report = new FD_ReportEngine($template,$data_object,$logger);
        echo $report->createPDF();
    }
}

class FD_ReportEngine extends FD_ReportMaster
{
    var $data_object;
    var $data_array;
    var $template = "";
    var $content;
    var $pdf;
    var $log;
    var $is_array = false;
    var $is_object = false;

    //Costruttore
    function __construct($template,$data_object,$logger)
    {
        $this->log = $logger;

        if(IsNullOrEmptyString($template))
        {
            $this->log->lwrite('[ERRORE] - Invalid template');
            echo '{"error" : "Invalid template"}';
        }

        if(!isset($data_object) || $data_object == null)
        {
            $this->log->lwrite('[ERRORE] - Invalid data');
            echo '{"error" : "Invalid data"}';
            return;
        }
        else
        {
            if(!isAssoc($data_object))
            {
                $this->is_array = true;
                $this->is_object = false;
                $data_array = $data_object;
                $data_object = $data_object[0];
            } 
            else
            {
                $this->is_array = false;
                $this->is_object = true;
            }
        }

        $this->template = $template;
        $this->data_object = $data_object;
        $this->data_array = $data_array;
    }

    public function createPDF()
    {
        try
        {
            ob_end_clean();
            
            if (file_exists($this->template))
            {
                //take xml template
                $xml = simplexml_load_file($this->template, 'SimpleXMLElement', LIBXML_NOCDATA);
                $this->content = json_decode(json_encode($xml),TRUE);
                
                
                if(in_array($this->content["@attributes"]["orientation"],(new PAGE_ORIENTATION())->getConst()) &&
                       in_array($this->content["@attributes"]["unit"],(new PAGE_UNIT())->getConst()) &&
                       in_array($this->content["@attributes"]["size"],(new PAGE_SIZE())->getConst()))
                    {

                        $this->pdf = new FD_ReportMaster($this->content["@attributes"]["orientation"],$this->content["@attributes"]["unit"],$this->content["@attributes"]["size"]);
                        $this->pdf->AliasNbPages();
                        if(isset($this->content["bmargin"])) $this->pdf->SetAutoPageBreak(true, $this->content["bmargin"]);
                        $this->pdf->AddPage();

                        //insert the keys of array
                        $keys = array_keys($this->content);
                        for($i=0;$i<count($keys);$i++)
                        {
                            // exclude the special keys
                            if($keys[$i] == "@attributes" || $keys[$i] == "comment" || $keys[$i] == "bmargin") continue;

                            if($keys[$i] == "author") $this->pdf->SetAuthor($this->content[$keys[$i]]);
                            else if ($keys[$i] == "title") $this->pdf->SetTitle($this->content[$keys[$i]]);
                            else if ($keys[$i] == "header") continue;//$GLOBALS['header'] = $this->content[$keys[$i]];
                            else if ($keys[$i] == "footer") continue;//$GLOBALS['footer'] = $this->content[$keys[$i]];
                            else
                                
                            //check id properties exist in data array
                            if(array_key_exists($keys[$i],$this->data_object) || substr($keys[$i],0,2) == "ln" || substr($keys[$i],0,5) == "label" || $keys[$i] == "table")
                            {
                                /* @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ */
                                //imposto coordinate
                                if(array_key_exists("x",$this->content[$keys[$i]]["@attributes"])) $x=$this->content[$keys[$i]]["@attributes"]["x"];
                                if(array_key_exists("xx",$this->content[$keys[$i]]["@attributes"])) $x=$this->pdf->GetX()+$this->content[$keys[$i]]["@attributes"]["xx"];
                                if(array_key_exists("y",$this->content[$keys[$i]]["@attributes"])) $y=$this->content[$keys[$i]]["@attributes"]["y"];
                                if(array_key_exists("yy",$this->content[$keys[$i]]["@attributes"])) $y=$this->pdf->GetY()+$this->content[$keys[$i]]["@attributes"]["yy"];
                                
                                ////////////////////////////// CELL ////////////////////////////////////
                                if($this->content[$keys[$i]]["@attributes"]["type"] == "cell" || $this->content[$keys[$i]]["@attributes"]["type"] == "label")
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
                                        
                                        if((int)$this->content[$keys[$i]]["@attributes"]["border"] == 0)
                                            $this->pdf->SetFillColor(255);

                                        if($this->content[$keys[$i]]["@attributes"]["wordwrap"] == "true")
                                        {
                                            $this->pdf->MultiCell($this->content[$keys[$i]]["@attributes"]["w"],
                                                                strlen($this->data_object[$keys[$i]]) < 100 ? 0 : (strlen($this->data_object[$keys[$i]])/100)*$this->content[$keys[$i]]["@attributes"]["font-size"]/2,
                                                ($this->content[$keys[$i]]["@attributes"]["type"] == "label"
                                                    ? $this->content[$keys[$i]]["@attributes"]["text"]
                                                    : iconv('UTF-8', 'windows-1252',$this->data_object[$keys[$i]])),
                                                                $this->content[$keys[$i]]["@attributes"]["border"],
                                                                $this->content[$keys[$i]]["@attributes"]["ln"],
                                                                $this->content[$keys[$i]]["@attributes"]["align"],
                                                                $this->content[$keys[$i]]["@attributes"]["fill"]
                                            );
                                        }
                                        else
                                        {
                                            $this->pdf->Cell($this->content[$keys[$i]]["@attributes"]["w"],
                                                            $this->content[$keys[$i]]["@attributes"]["h"],
                                                ($this->content[$keys[$i]]["@attributes"]["type"] == "label"
                                                    ? $this->content[$keys[$i]]["@attributes"]["text"]
                                                    : iconv('UTF-8', 'windows-1252',$this->data_object[$keys[$i]])),
                                                            $this->content[$keys[$i]]["@attributes"]["border"],
                                                            $this->content[$keys[$i]]["@attributes"]["ln"],
                                                            $this->content[$keys[$i]]["@attributes"]["align"],
                                                            $this->content[$keys[$i]]["@attributes"]["fill"]
                                            );
                                        }
                                    }
                                    else
                                    {
                                        $this->log->lwrite('[ERRORE] - Any '.$keys[$i].'\'s attribute is invalid or missing');
                                        echo '{"error" : "Any '.$keys[$i].'\'s attribute is invalid or missing"}';
                                        return;
                                    }
                                }
                                ////////////////////////////// TEXT ////////////////////////////////////
                                else if($this->content[$keys[$i]]["@attributes"]["type"] == "text")
                                {
                                    if(in_array($this->content[$keys[$i]]["@attributes"]["font"],(new FONT_FAMILY())->getConst()) &&
                                        in_array($this->content[$keys[$i]]["@attributes"]["font-style"],(new FONT_STYLE())->getConst()))
                                    {
                                        $this->pdf->SetFont($this->content[$keys[$i]]["@attributes"]["font"],
                                            $this->content[$keys[$i]]["@attributes"]["font-style"],
                                            $this->content[$keys[$i]]["@attributes"]["font-size"]);

                                        $this->pdf->Text($x,
                                                         $y,
                                                         iconv('UTF-8', 'windows-1252',$this->data_object[$keys[$i]])
                                        );
                                    }
                                    else
                                    {
                                        $this->log->lwrite('[ERRORE] - Any '.$keys[$i].'\'s attribute is invalid or missing');
                                        echo '{"error" : "Any '.$keys[$i].'\'s attribute is invalid or missing"}';
                                        return;
                                    }
                                }
                                ////////////////////////////// IMAGE ////////////////////////////////////
                                else if($this->content[$keys[$i]]["@attributes"]["type"] == "image")
                                {
                                    //take image format
                                    if(!IsNullOrEmptyString($this->data_object[$keys[$i]]))
                                    {
                                        $formato = pathinfo($this->data_object[$keys[$i]], PATHINFO_EXTENSION);
                                        if(in_array(strtolower($formato),(new IMAGE_FORMAT())->getConst()))
                                        {
                                            if(isset($x))
                                                $this->pdf->SetX($x);

                                            if(isset($y))
                                                $this->pdf->SetY($y);
                                            
                                            $this->pdf->Image($this->data_object[$keys[$i]],
                                                              $x,
                                                              $y,
                                                              $this->content[$keys[$i]]["@attributes"]["w"],
                                                              $this->content[$keys[$i]]["@attributes"]["h"]);
                                            
                                            $this->pdf->SetY($this->pdf->GetY()+$y);
                                        }
                                        else
                                        {
                                            $this->log->lwrite('[ERRORE] - The image '.$keys[$i].' have invalid extension');
                                            echo '{"error" : "The image '.$keys[$i].' have invalid extension"}';
                                            return;
                                        }
                                    }
                                }
                                ////////////////////////////// LN ////////////////////////////////////
                                else if($this->content[$keys[$i]]["@attributes"]["type"] == "ln")
                                {
                                    $this->pdf->Ln($this->content[$keys[$i]]["@attributes"]["h"]);
                                }
                                ////////////////////////////// TABLE ////////////////////////////////////
                                else if($this->content[$keys[$i]]["@attributes"]["type"] == "table" || $keys[$i] == "table")
                                {
                                    if(isset($x) && isset($y))
                                        $this->pdf->SetXY($x,$y);

                                    // Header
                                    $this->pdf->SetFillColor(225);
                                    $this->pdf->SetTextColor(100);
                                    foreach($this->content[$keys[$i]] as $name => $col)
                                    {
                                        if($col["@attributes"]["headertext"] != null)
                                        {
                                            if(isset($col["@attributes"]["drawcolor"]))
                                                $this->pdf->SetDrawColor($col["@attributes"]["drawcolor"]);

                                            if (array_key_exists("type", $col)) continue;
                                            $this->pdf->SetFont($col["@attributes"]["font"],
                                                $col["@attributes"]["font-style"],
                                                $col["@attributes"]["font-size"]);
                                            $this->pdf->Cell($col["@attributes"]["w"],
                                                $col["@attributes"]["h"],
                                                iconv('UTF-8', 'windows-1252',
                                                    $col["@attributes"]["headertext"]),
                                                $col["@attributes"]["border"], 0, 'C', 1);
                                        }
                                    }
                                    $this->pdf->Ln();

                                    $this->pdf->SetDrawColor(0,0,0);

                                    // Data
                                    $this->pdf->SetFillColor(255);
                                    $this->pdf->SetTextColor(0);

                                    //capisco se devo decodificare un json oppure ho già l'array passato dalla query
                                    if($this->is_object)
                                    {
                                        if(is_string($this->data_object[$keys[$i]])) $this->data_object[$keys[$i]] = json_decode($this->data_object[$keys[$i]], true);
                                    }
                                    else
                                    {
                                        $this->data_object[$keys[$i]] = $this->data_array;
                                    }

                                    //var_dump($this);return;

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
                                            
                                            if(isset($col["@attributes"]["drawcolor"]))
                                                $this->pdf->SetDrawColor($col["@attributes"]["drawcolor"]);

                                            if($col["@attributes"]["wordwrap"] == "true")
                                            {
                                                $this->pdf->MultiCell($col["@attributes"]["w"],
                                                                 $col["@attributes"]["h"],
                                                                 $row[$name].iconv('UTF-8', 'windows-1252',$col["@attributes"]["suffix"]),
                                                                 $col["@attributes"]["border"],
                                                                 0,
                                                                 $col["@attributes"]["align"]);
                                            }
                                            else
                                            {
                                                $this->pdf->Cell($col["@attributes"]["w"],
                                                                 $col["@attributes"]["h"],
                                                                 $row[$name].iconv('UTF-8', 'windows-1252',$col["@attributes"]["suffix"]),
                                                                 $col["@attributes"]["border"],
                                                                 0,
                                                                 $col["@attributes"]["align"]);
                                            }

                                            $this->pdf->SetDrawColor(0,0,0);
                                        }
                                        $this->pdf->Ln();
                                    }
                                }
                            
                            
                                
                                /* @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ */
                            }
                            else
                            {
                                $this->log->lwrite('[ERRORE] - The property '.$keys[$i].' is not defined in data array');
                                echo '{"error" : "The property '.$keys[$i].' is not defined in data array"}';
                                return;
                            }
                            
                            unset($x);
			                unset($y);
                        }

		                $this->pdf->Output();
                    
                	}
                    else
                    {
                        $this->log->lwrite('[ERRORE] - Any < report >\'s attribute is invalid or missing');
                        echo '{"error" : "Any < report >\'s attribute is invalid or missing"}';
                        return;
                    }
            }
            else
            {
                $this->log->lwrite('[ERRORE] - Failed to open the template');
                echo '{"error" : "Failed to open the template"}';
                return;
            }
        }
        catch (Exception $e)
        {
            $this->log->lwrite('[ERRORE] - '.$e->getMessage());
            echo '{"error" : "'.$e->getMessage().'"}';
        }
    }

}

