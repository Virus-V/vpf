<?php
/**
 *--------------------------------------------------
 * VPF 验证码
 *--------------------------------------------------
 * @program     : VPF
 * @create      : 2013-6-25 11:35:39
 * @description :
 */
namespace Vpf\Lib;
use Vpf;

class VCode
{
    private $vCodeImage;
    private $imageWidth;
    private $imageHeight;
    private $codeLenght;
    private $codeFont;
    public $vCode;

    public function __construct($imageWidth = '90', $imageHeight = '30', $codeLenght ='4', $codeFont = ''){
        $this->imageWidth = $imageWidth;
        $this->imageHeight = $imageHeight;
        $this->codeLenght = $codeLenght;
        $this->codeFont = $codeFont;
        $this->vCode = $this->random_char($this->codeLenght);
        $this->create_vCodeImage();
    }

    private function create_vCodeImage(){
        /* create canvas */
        $this->vCodeImage = imagecreatetruecolor($this->imageWidth, $this->imageHeight);
        $bgColor = imagecolorallocate($this->vCodeImage, mt_rand(192, 255), mt_rand(192, 255), mt_rand(192, 255));
        imagefill($this->vCodeImage, 0, 0, $bgColor);

        /* create jam */
        $jamPointNum = floor($this->imageWidth * $this->imageHeight / 20);
        for($i = 0; $i < $jamPointNum; $i++){
            $jamColor = imagecolorallocate($this->vCodeImage, mt_rand(128, 192), mt_rand(128, 192), mt_rand(128, 192));
            imagesetpixel($this->vCodeImage, mt_rand(0, $this->imageWidth), mt_rand(0, $this->imageHeight), $jamColor);
        }
        $jamArcNum = $this->codeLenght * 2;
        for($i = 0; $i < 10; $i++){
            $jamColor = imagecolorallocate($this->vCodeImage, mt_rand(128, 192), mt_rand(128, 192), mt_rand(128, 192));
            imagearc($this->vCodeImage, mt_rand(-10, $this->imageWidth), mt_rand(-10, $this->imageHeight), mt_rand(30, 300), mt_rand(20, 200), mt_rand(0, 360), mt_rand(0, 360), $jamColor);
        }

        /* write text */
        for($i = 0; $i < $this->codeLenght; $i++){
            if(0 == strlen($this->codeFont)){
                $fontSize = rand(4, 6);
                $x = floor($this->imageWidth / $this->codeLenght) * $i + 3;
                $y = mt_rand(0, $this->imageHeight - 15);
                $charColor = imagecolorallocate($this->vCodeImage, mt_rand(0, 128), mt_rand(0, 128), mt_rand(0, 128));
                imagechar($this->vCodeImage, $fontSize, $x, $y, $this->vCode[rand(0, $this->codeLenght - 1)], $charColor);
            }else{
                $fontSize = rand($this->imageHeight / 2, $this->imageHeight / 3 * 2);
                $x = floor($this->imageWidth / $this->codeLenght) * $i + 3;
                $y = mt_rand($this->imageHeight / 2, $this->imageHeight);
                $charColor = imagecolorallocate($this->vCodeImage, mt_rand(0, 128), mt_rand(0, 128), mt_rand(0, 128));
                imagettftext($this->vCodeImage, $fontSize, mt_rand(-10, 10), $x, $y, $charColor, $this->codeFont, $this->vCode[$i]);
            }
        }
    }

    public function show_vCodeImage(){
        if(imagetypes() & IMG_PNG){
            header("Content-Type:image/png");
            header('Content-Disposition:attachment; filename=vCode.png');
            imagepng($this->vCodeImage);
        }elseif(imagetypes() & IMG_GIF){
            header("Content-Type:image/gif");
            header('Content-Disposition:attachment; filename=vCode.gif');
            imagegif($this->vCodeImage);
        }elseif(imagetypes() & IMG_JPEG){
            header("Content-Type:image/jpeg");
            header('Content-Disposition:attachment; filename=vCode.jpg');
            imagejpeg($this->vCodeImage);
        }
    }

    /* 产生随机字母 */
    private function random_char($length){
        $hash = '';
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++){
            $hash .= $chars[mt_rand(0, $max)];
        }
        return $hash;
    }
}
/**
Usage:
----------------------------------------
session_start();
require_once('./lib/AvCode.class.php');
$codeFont =  './static/font/438-CAI978.ttf';
$vc = new AvCode(80, 30, 4, $codeFont);
$vc->show_vCodeImage();
$_SESSION['vCode']['user_seed'] = $vc->vCode;
*/
?>