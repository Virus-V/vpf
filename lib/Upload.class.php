<?php
/**
 *--------------------------------------------------
 * VPF 文件上传
 *--------------------------------------------------
 * @program     : VPF
 * @create      : 2013-4-25 15:13:30
 * @modeify     : 2016年9月29日21:50:27
 * @modeify     : 2016年10月3日13:28:26
 * @description :
 */
namespace Vpf\Lib;
use Vpf;

if(!defined('D_S')){
    define('D_S', DIRECTORY_SEPARATOR);
}
class Upload
{
    private $uploadDir;
    private $maxSize = 2097152; //字节，2mb
    private $typeSet = array('gif', 'jpg', 'jpeg', 'png');
    private $typeGetMethod = 1; //1: extension, 2: file header info
    private $fileNaming = 1; //0:original, 1:md5($name.time()), other:自定义

    public $uploadError;
    
    /* 构造函数 */
    public function __construct($config = array()){
        foreach($config as $k => $v){
            if(property_exists($this, $k)){
                $this->$k = $v;
            }
        }
        $this->set_uploadDir($this->uploadDir);
    }
    
    /* 设定上传目录 */
    public function set_uploadDir($uploadDir){
        $uploadDir = rtrim($uploadDir, "\\/").D_S;
        if(!is_writable($uploadDir)){
            $this->uploadError['code'][] = "-1";
            $this->uploadError['msg'][] = "upload dir is read only.";
            return false;
        }
        $this->uploadDir = $uploadDir;
        return true;
    }

    /* 执行上传动作 */
    public function do_upload($field, $uploadDir = ''){
        /* 如果有错误，则取消上传 */
        if(!empty($this->uploadError)) return false;
        
        if(!empty($uploadDir)){
            $this->set_uploadDir($uploadDir);
        }
        if(isset($_FILES[$field])){
            $name = $_FILES[$field]['name'];
            $tmpName = $_FILES[$field]['tmp_name'];
            $error = $_FILES[$field]['error'];
            $size = $_FILES[$field]['size'];
            if('1' == $this->typeGetMethod){
                if(is_array($name)){
                    foreach($name as $index=>$t_name){
                        $type[$index] = $this->get_fileType($t_name);
                    }
                }else{
                    $type = $this->get_fileType($name);
                }
            }else{
                if(is_array($tmpName)){
                    foreach($tmpName as $index=>$t_name){
                        $type[$index] = $this->get_fileRealType($t_name);
                    }
                }else{
                    $type = $this->get_fileRealType($tmpName);
                }
            }

            $this->check_error($error);
            $this->check_size($size);
            $this->check_fileType($type, $this->typeSet);

            /* 检查错误 */
            if(!empty($this->uploadError)) return false;
            
            $newName = $this->get_newName($name, $type, $this->fileNaming);
            $this->save_file($tmpName, $newName);
            
            if(!empty($this->uploadError)) return false;
            $uploadFile['name'] = $newName;
            $uploadFile['original_name'] = $name;
            $uploadFile['type'] = $type;
            $uploadFile['size'] = $size;
            return $uploadFile;
            
        }
        return false;
    }

    private function check_error($error){
        if(is_array($error)){
            foreach($error as $e){
                $this->check_error($e);
            }
        }elseif($error > 0){
            switch($error){
                case 1:
                    $this->uploadError['code'][] = "1";
                    $this->uploadError['msg'][] = "file size exceeds php.ini define.";
                    break;
                case 2:
                    $this->uploadError['code'][] = "2";
                    $this->uploadError['msg'][] = "file size exceeds HTML form define.";
                    break;
                case 3:
                    $this->uploadError['code'][] = "3";
                    $this->uploadError['msg'][] = "only partially uploaded.";
                    break;
                case 4:
                    $this->uploadError['code'][] = "4";
                    $this->uploadError['msg'][] = "no file was uploaded.";
                    break;
                case 6:
                    $this->uploadError['code'][] = "6";
                    $this->uploadError['msg'][] = "missing a temporary folder.";
                    break;
                case 7:
                    $this->uploadError['code'][] = "7";
                    $this->uploadError['msg'][] = "failed to write file to disk.";
                    break;
                default:
                    $this->uploadError['code'][] = "8";
                    $this->uploadError['msg'][] = "unknow error.";
            }
        }
    }

    private function check_size($size){
        if(is_array($size)){
            foreach($size as $s){
                $this->check_size($s);
            }
        }elseif($this->maxSize < $size){
            $this->uploadError['code'][] = "-2";
            $this->uploadError['msg'][] = "file size exceeds class define: ".Vpf\byte_format($this->maxSize);
        }
    }

    private function check_fileType($type, $typeSet){
        if(is_array($type)){
            foreach($type as $t){
                $this->check_fileType($t, $typeSet);
            }
        }else{
            if(!in_array($type, $typeSet)){
                $this->uploadError['code'][] = "-3";
                $this->uploadError['msg'][] = "file type is not allowed: {$type}";
            }
        }
    }

    private function get_newName($name, $type, $fileNaming){
        if('0' == $fileNaming){
            $newName = $name;
        }elseif('1' == $fileNaming){
            if(is_array($name)){
                foreach($name as $k => $name){
                    $newName[$k] = substr(md5($name.time()), 0, 16).'.'.$type[$k];
                }
            }else{
                $newName = substr(md5($name.time()), 0, 16).'.'.$type;
            }
        }else{
            if(is_array($name)){
                foreach($name as $k => $name){
                    $newName[$k] = date($fileNaming);
                }
            }else{
                $newName = date($fileNaming);
            }
        }
        return $newName;
    }

    private function save_file($tmpName, $newName){
        if(is_array($tmpName)){
            foreach($tmpName as $k => $tn){
                if(!move_uploaded_file($tn, $this->uploadDir.$newName[$k])){
                    $this->uploadError['code'][] = "-4";
                    $this->uploadError['msg'][] = "upload failed.";
                }
            }
        }elseif(is_uploaded_file($tmpName)){
            if(!move_uploaded_file($tmpName, $this->uploadDir.$newName)){
                $this->uploadError['code'][] = "-4";
                $this->uploadError['msg'][] = "upload failed.";
            }
        }else{
            $this->uploadError['code'][] = "-5";
            $this->uploadError['msg'][] = "tmpFile is not uploaded file.";
        }
    }

    private function get_fileType($fileName){
        if(!empty($fileName) && !is_dir($fileName)){
            $fileName = explode('.', $fileName);
            return $fileType = strtolower($fileName[count($fileName) - 1]);
        }
        return NULL;
    }

    private function get_fileRealType($fileName){
        if(file_exists($fileName)){
            $file = fopen($fileName, "rb");
            $bin = fread($file, 2);
            fclose($file);
            $strInfo = @unpack("c2chars", $bin);
            $typeCode = intval($strInfo['chars1'].$strInfo['chars2']);
            $fileType = '';
            switch ($typeCode){
                case 7790:
                    $fileType = 'exe';
                    break;
                case 7784:
                    $fileType = 'midi';
                    break;
                case 8297:
                    $fileType = 'rar';
                    break;
                case 255216:
                    $fileType = 'jpg';
                    break;
                case -1:
                    $fileType = 'jpg';
                    break;
                case 7173:
                    $fileType = 'gif';
                    break;
                case 6677:
                    $fileType = 'bmp';
                    break;
                case 8075:
                    $fileType = 'zip';
                    break;
                case 13780:
                    $fileType = 'png';
                    break;
                case -11980:
                    $fileType = 'png';
                    break;
                default:
                    $fileType = 'unknown';
            }
            return $fileType;
        }
        return NULL;
    }
}
/**
Usage:
----------------------------------------
$field = 'upload_file';
$field1 = 'upload_file_1';
$uploadDir = './upload/';
$uploadDir1 = './upload1/';

$_upload = new Upload(array(
        'uploadDir' => $uploadDir,
        'maxSize' => '1000000',
        'typeSet' => array('gif', 'jpg', 'png'))
    );
print_r($upload = $_upload->do_upload($field));
print_r($upload = $_upload->do_upload($field1, $uploadDir1));
----------------------------------------
<form action="" method="post" enctype="multipart/form-data">
<input type="hidden" name="MAX_FILE_SIZE" value="1000000" />
<input name="upload_file[]" type="file" accept="up_field" size="20" />
<input name="upload_file[]" type="file" accept="up_field" size="20" />
<input name="upload_file_1" type="file" accept="up_field" size="20" />
<input type="submit" />
</form>
*/
?>