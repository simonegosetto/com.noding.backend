<?php

/*function json_encode($data)
{
    $numeric = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
    $nonnumeric = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    preg_match_all("/\"[0\+]+(\d+)\"/", $nonnumeric, $vars);
    foreach($vars[0] as $k => $v){
        $numeric = preg_replace("/\:\s*{$vars[1][$k]},/",": {$v},",$numeric);
    }
    return $numeric;
}*/

require("Tools/FD_Crypt.php");

$crypt = new FD_Crypt();

$string = 'sp_commissionirighe_distinct';
$encrypted = $crypt->stored_crypt($string);
echo $encrypted;

echo "\n".$crypt->stored_decrypt($encrypted);



