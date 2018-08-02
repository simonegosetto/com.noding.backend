<?php
/**
 * Created by Brackets.
 * User: Simone
 * Date: 14/03/2018
 * Time: 16:23
 */

final class FD_Image
{


    function __construct(){}

    /* *******************
	 * Public
	 * *******************/

    /*
     * Metodo di compressione delle immagini
     */
    public function compress_image($source_url, $destination_url, $quality)
    {
        $info = getimagesize($source_url);
        if ($info['mime'] == 'image/jpeg') $image = imagecreatefromjpeg($source_url);
        elseif ($info['mime'] == 'image/gif') $image = imagecreatefromgif($source_url);
        elseif ($info['mime'] == 'image/png') $image = imagecreatefrompng($source_url);
        imagejpeg($image, $destination_url, $quality);
        return $destination_url;
    }

    /*
     * Metodo di resize delle immagini
     */
    public function resize($width, $height, $destination_url, $file)
    {
        /* Get original image x y*/
        list($w, $h) = getimagesize($file['tmp_name']);
        /* calculate new image size with ratio */
        $ratio = max($width/$w, $height/$h);
        $h = ceil($height / $ratio);
        $x = ($w - $width / $ratio) / 2;
        $w = ceil($width / $ratio);
        /* new file name */
        $path = $destination_url;
        /* read binary data from image file */
        $imgString = file_get_contents($file['tmp_name']);
        /* create image from string */
        $image = imagecreatefromstring($imgString);
        $tmp = imagecreatetruecolor($width, $height);
        imagecopyresampled($tmp, $image,
            0, 0,
            $x, 0,
            $width, $height,
            $w, $h);
        /* Save image */
        switch ($file['type'])
        {
            case 'image/jpeg':
                imagejpeg($tmp, $path, 100);
                break;
            case 'image/png':
                imagepng($tmp, $path, 0);
                break;
            case 'image/gif':
                imagegif($tmp, $path);
                break;
            default:
                exit;
                break;
        }
        return $path;
        /* cleanup memory */
        imagedestroy($image);
        imagedestroy($tmp);
    }

    /*
     * Metodo di resize + compressione delle immagini
     */
    public function resize_and_compression($width, $height, $destination_url, $quality, $file)
    {
        /* Get original image x y*/
        list($w, $h) = getimagesize($file['tmp_name']);
        /* calculate new image size with ratio */
        $ratio = max($width/$w, $height/$h);
        $h = ceil($height / $ratio);
        $x = ($w - $width / $ratio) / 2;
        $w = ceil($width / $ratio);
        /* new file name */
        $path = $destination_url;
        /* read binary data from image file */
        $imgString = file_get_contents($file['tmp_name']);
        /* create image from string */
        $image = imagecreatefromstring($imgString);
        $tmp = imagecreatetruecolor($width, $height);
        imagecopyresampled($tmp, $image,
            0, 0,
            $x, 0,
            $width, $height,
            $w, $h);
        /* Save image */
        switch ($file['type'])
        {
            case 'image/jpeg':
                imagejpeg($tmp, $path, $quality);
                break;
            case 'image/png':
                imagepng($tmp, $path, 0);
                break;
            case 'image/gif':
                imagegif($tmp, $path);
                break;
            default:
                exit;
                break;
        }

        /* cleanup memory */
        cleanup_memory($image);
        cleanup_memory($tmp);

        return compress_image($path,$destination_url,$quality);
    }


    /* *******************
	 * Private
	 * *******************/

    private function cleanup_memory($image)
    {
        imagedestroy($image);
    }

}
