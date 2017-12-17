<?php
/**
 *--------------------------------------------------
 * VPF 图片处理
 *--------------------------------------------------
 * @program     : VPF
 * @create      : 2012-7-25 13:55:39
 * @description :
 */
namespace Vpf\Lib;
use Vpf;

if(!defined('D_S')){
    define('D_S', DIRECTORY_SEPARATOR);
}
class Image{
    private $sourceDir = '';
    private $outputDir = '';
    public $imageError;

    public function __construct($config){
        $classVar = get_class_vars(get_class($this));
        foreach ($config as $k => $v){
            if(array_key_exists($k, $classVar)){
                $this->$k = $v;
            }
        }
    }

    public function set_outputDir($outputDir){
        $outputDir = rtrim($outputDir, "\\/").D_S;
        if(!empty($outputDir)){
            $outputDir = rtrim($this->outputDir, "\\/").D_S;
        }
        if(!is_writable($outputDir)){
            $this->imageError[] = "output dir is read only.";
            return false;
        }
        $this->outputDir = $outputDir;
        return true;
    }

    //generate thumbnail
    public function thumbnail($sourceImageName, $thumbWidth = 64, $thumbHeight = 64, $proportional = true, $prefix = 'thumb_', $outputDir = ''){
        $imageError = array();
        if(!empty($outputDir)){
            $this->set_outputDir($outputDir);
        }else{
            $this->set_outputDir($this->outputDir);
        }
        $sourceInfo = $this->get_imageInfo(rtrim($this->sourceDir, "\\/").D_S.$sourceImageName);
        $thumbInfo = $this->get_thumbInfo($sourceInfo, $thumbWidth, $thumbHeight, $prefix, $proportional);
        $thumbResource = $this->thumbImage($thumbInfo, $sourceInfo);
        if(empty($this->imageError)){
            return $this->output_image($thumbResource, $thumbInfo);
        }
        return NULL;
    }

    //water mark
    public function mark($sourceImageName, $markImage, $positonNo = '9', $prefix = 'water_', $outputDir){
        $imageError = array();
        if(!empty($outputDir)){
            $this->set_outputDir($outputDir);
        }else{
            $this->set_outputDir($this->outputDir);
        }
        $sourceInfo = $this->get_imageInfo(rtrim($this->sourceDir, "\\/").D_S.$sourceImageName);
        $markInfo = $this->get_markInfo($markImage);
        $markedInfo = $this->get_markedInfo($sourceInfo, $markInfo, $positonNo, $prefix);
        $markedResource = $this->markImage($markedInfo, $sourceInfo, $markInfo);
        if(empty($this->imageError)){
            return $this->output_image($markedResource, $markedInfo);
        }
        return NULL;
    }

    private function get_imageInfo($imageName){
        if(!empty($this->imageError)){
            return NULL;
        }
        if(!file_exists($imageName)){
            $this->imageError[] = "image file[{$imageName}] do not exists.";
            return NULL;
        }
        $imageInfo['name'] = basename($imageName);
        $gis = getimagesize($imageName);
        $imageInfo['width'] = $gis[0];
        $imageInfo['height'] = $gis[1];
        switch($gis[2]){
            case 1:
                $imageInfo['type'] = 'gif';
                break;
            case 2:
                $imageInfo['type'] = 'jpg';
                break;
            case 3:
                $imageInfo['type'] = 'png';
                break;
            default:
                $imageInfo['type'] = 'unknown';
        }
        return $imageInfo;
    }

    private function get_imageResource($imageInfo){
        switch($imageInfo['type']){
            case 'gif':
                $resource = imagecreatefromgif(rtrim($this->sourceDir, "\\/").D_S.$imageInfo['name']);
                break;
            case 'jpg':
                $resource = imagecreatefromjpeg(rtrim($this->sourceDir, "\\/").D_S.$imageInfo['name']);
                break;
            case 'png':
                $resource = imagecreatefrompng(rtrim($this->sourceDir, "\\/").D_S.$imageInfo['name']);
                break;
            default:
                $resource = imagecreatefromjpeg(rtrim($this->sourceDir, "\\/").D_S.$imageInfo['name']);
        }
        return $resource;
    }

    private function get_thumbInfo($sourceInfo, $thumbWidth, $thumbHeight, $prefix = 'thumb_', $proportional = true){
        if(!empty($this->imageError)){
            return NULL;
        }
        $thumbInfo['name'] = $prefix.$sourceInfo['name'];
        $thumbInfo['type'] = $sourceInfo['type'];
        if($proportional){
            $sourceWHRate = $sourceInfo['width'] / $sourceInfo['height'];
            $WWRate = $sourceInfo['width'] / $thumbWidth;
            $HHRate = $sourceInfo['height'] / $thumbHeight;
            if($WWRate > $HHRate){
                $thumbInfo['width'] = $thumbWidth;
                $thumbInfo['height'] = round($thumbWidth / $sourceWHRate);
            }else{
                $thumbInfo['width'] = round($thumbHeight * $sourceWHRate);
                $thumbInfo['height'] = $thumbHeight;
            }
        }else{
            $thumbInfo['width'] = $thumbWidth;
            $thumbInfo['height'] = $thumbHeight;
        }
        return $thumbInfo;
    }

    private function thumbImage($outputInfo, $sourceInfo){
        if(!empty($this->imageError)){
            return NULL;
        }
        $thumbResource = imagecreatetruecolor($outputInfo['width'], $outputInfo['height']);
        $sourceResource = $this->get_imageResource($sourceInfo);

        //deal with transparent color
        $otsc = imagecolortransparent($sourceResource);
        if($otsc >= 0 && $otsc < imagecolorstotal($sourceResource)){
            $TC = imagecolorsforindex($sourceResource, $otsc);
            $transColor = imagecolorallocate($thumbResource, $TC['red'], $TC['green'], $TC['blue']);
            imagefill($thumbResource, 0, 0, $transColor);
            imagecolortransparent($thumbResource, $transColor);
        }

        imagecopyresized($thumbResource, $sourceResource, 0, 0, 0, 0, $outputInfo['width'], $outputInfo['height'], $sourceInfo['width'], $sourceInfo['height']);

        imagedestroy($sourceResource);
        return $thumbResource;
    }

    private function get_markInfo($markImage){
        if(!empty($this->imageError)){
            return NULL;
        }
        if(!file_exists($markImage)){
            $this->imageError[] = "markImage[{$markImage}] do not exists.";
            return NULL;
        }
        $imageInfo['name'] = $markImage;
        $gis = getimagesize($markImage);
        $imageInfo['width'] = $gis[0];
        $imageInfo['height'] = $gis[1];
        switch($gis[2]){
            case 1:
                $imageInfo['type'] = 'gif';
                break;
            case 2:
                $imageInfo['type'] = 'jpg';
                break;
            case 3:
                $imageInfo['type'] = 'png';
                break;
            default:
                $imageInfo['type'] = 'unknown';
        }
        return $imageInfo;
    }

    private function get_markedInfo($sourceInfo, $markInfo, $positonNo, $prefix){
        if(!empty($this->imageError)){
            return NULL;
        }
        if($sourceInfo['width'] < $markInfo['width'] || $sourceInfo['height'] < $markInfo['height']){
            $this->imageError[] = "source image must be larger than mark image.";
            return NULL;
        }

        $markedInfo['name'] = $prefix.$sourceInfo['name'];
        $markedInfo['type'] = $sourceInfo['type'];
        $markedInfo['width'] = $sourceInfo['width'];
        $markedInfo['height'] = $sourceInfo['height'];
        switch($positonNo){
            case '0':
                $markedInfo['position']['x'] = rand(0, ($sourceInfo['width'] - $markInfo['width']));
                $markedInfo['position']['y'] = rand(0, ($sourceInfo['height'] - $markInfo['height']));
                break;
            case '1':
                $markedInfo['position']['x'] = 0;
                $markedInfo['position']['y'] = 0;
                break;
            case '2':
                $markedInfo['position']['x'] = ($sourceInfo['width'] - $markInfo['width']) / 2;
                $markedInfo['position']['y'] = 0;
                break;
            case '3':
                $markedInfo['position']['x'] = ($sourceInfo['width'] - $markInfo['width']);
                $markedInfo['position']['y'] = 0;
                break;
            case '4':
                $markedInfo['position']['x'] = 0;
                $markedInfo['position']['y'] = ($sourceInfo['height'] - $markInfo['height']) / 2;
                break;
            case '5':
                $markedInfo['position']['x'] = ($sourceInfo['width'] - $markInfo['width']) / 2;
                $markedInfo['position']['y'] = ($sourceInfo['height'] - $markInfo['height']) / 2;
                break;
            case '6':
                $markedInfo['position']['x'] = ($sourceInfo['width'] - $markInfo['width']);
                $markedInfo['position']['y'] = ($sourceInfo['height'] - $markInfo['height']) / 2;
                break;
            case '7':
                $markedInfo['position']['x'] = 0;
                $markedInfo['position']['y'] = ($sourceInfo['height'] - $markInfo['height']);
                break;
            case '8':
                $markedInfo['position']['x'] = ($sourceInfo['width'] - $markInfo['width']) / 2;
                $markedInfo['position']['y'] = ($sourceInfo['height'] - $markInfo['height']);
                break;
            case '9':
                $markedInfo['position']['x'] = ($sourceInfo['width'] - $markInfo['width']);
                $markedInfo['position']['y'] = ($sourceInfo['height'] - $markInfo['height']);
                break;
            default:
                $markedInfo['position']['x'] = rand(0, ($sourceInfo['width'] - $markInfo['width']));
                $markedInfo['position']['y'] = rand(0, ($sourceInfo['height'] - $markInfo['height']));
        }

        return $markedInfo;
    }

    private function get_markResource($markInfo){
        switch($markInfo['type']){
            case 'gif':
                $resource = imagecreatefromgif($markInfo['name']);
                break;
            case 'jpg':
                $resource = imagecreatefromjpeg($markInfo['name']);
                break;
            case 'png':
                $resource = imagecreatefrompng($markInfo['name']);
                break;
            default:
                $resource = imagecreatefromjpeg($markInfo['name']);
        }
        return $resource;
    }

    private function markImage($markedInfo, $sourceInfo, $markInfo){
        if(!empty($this->imageError)){
            return NULL;
        }
        $sourceResource = $this->get_imageResource($sourceInfo);
        $markResource = $this->get_markResource($markInfo);
        imagecopy($sourceResource, $markResource, $markedInfo['position']['x'], $markedInfo['position']['y'], 0, 0, $markInfo["width"], $markInfo["height"]);
        imagedestroy($markResource);
        return $sourceResource;
    }

    private function output_image($outputResource, $outputInfo){
        if(!empty($this->imageError)){
            return NULL;
        }
        switch($outputInfo['type']){
            case 'gif':
                imagegif($outputResource, $this->outputDir.$outputInfo['name']);
                break;
            case 'jpg':
                imagejpeg($outputResource, $this->outputDir.$outputInfo['name']);
                break;
            case 'png':
                imagepng($outputResource, $this->outputDir.$outputInfo['name']);
                break;
            default:
                imagejpeg($outputResource, $this->outputDir.$outputInfo['name']);
        }
        imagedestroy($outputResource);
        return $outputInfo;
    }
}
/**
Usage: thumbnail
----------------------------------------
$uploadDir = './upload/';
$outputDir = './output/';
$_aimage = new Image(array('sourceDir' => $uploadDir, 'outputDir' => $outputDir));
$thumb = $_aimage->thumbnail($imageName, 300, 100, false);
echo "<img src=\"{$outputDir}{$thumb['name']}\" width={$thumb['width']} height={$thumb['height']} />";
$mark = $_aimage->mark($imageName, $waterfile, 9);
echo "<img src=\"{$outputDir}{$mark['name']}\" width={$mark['width']} height={$mark['height']} />";
 */
?>