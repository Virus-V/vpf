<?php
define('D_S', DIRECTORY_SEPARATOR);
define('VPF_PATH',dirname(__FILE__));
/* 存放当前目录下所有文件和文件夹的列表 */
$LISTS = array();
if(!empty($_GET['action'])){
    $action = strtolower(trim($_GET['action']));
    unset($_GET['action']);
    /* 调用动作函数 */
    if(function_exists($action)){
        //call_user_func_array
        call_user_func($action);
    }
}

function get_map(){
    global $LISTS;
    checkdir(VPF_PATH);
    exit(json_encode($LISTS));
}

function get_source(){
    if(!isset($_GET['file']))exit;
    $filename = trim(basename($_GET['file']),'/\\ ');
    //为了安全
    $path = VPF_PATH.D_S. str_replace(array('.','/','\\'), array('',D_S,D_S), $_GET['file']);
    $path = preg_replace(array('/\\\\+/','/\/+/'), D_S, $path);
    $path = substr($path,0,-(strlen($filename)-substr_count($filename,'.'))).$filename;
    if(is_file($path)){
        readfile($path);
    }
}

/////////////////////////////////////////
function checkdir($basedir=''){
    global $LISTS;
    $basedir = empty($basedir) ? '.' : $basedir;
    if($dh=opendir($basedir)){
        while (($file = readdir($dh)) !== false){
            if( $file!='.' && $file!='..'){
                $path = $basedir.D_S.$file;
                if(!is_dir($path)){
                    $LISTS['file'][] = array(substr($path,strlen(VPF_PATH)),md5_file($path));
                }else{
                    $LISTS['directory'][] = substr($path,strlen(VPF_PATH));
                    checkdir($path);
                }
            }
        }
        closedir($dh);
    }
}
?>