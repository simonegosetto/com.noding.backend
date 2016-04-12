<?php
/**
 * Created by PhpStorm.
 * User: Simone
 * Date: 11/04/2016
 * Time: 16:41
 */

include "FD_Crypt.php";
$crypt = new FD_Crypt();
$totem = "4VWsyZadW51jkFSVwtbGpc2mmlpuV5iWZpxfZpaSYmm6ZG2eZmhzk2eSaGKTvINdhpqYrJ2k1IhviJ+X1MiSrNulmsObpqDVl9eloIPf";
$totem2 = $crypt->simple_crypt($totem,"decrypt");
$totem_array = json_decode($totem2, true);
echo $totem_array["action"];