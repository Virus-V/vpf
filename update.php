<?php
set_time_limit(0);
header('Content-Type: text/html; charset=utf-8');
define('D_S', DIRECTORY_SEPARATOR);
define('VPF_PATH',dirname(__FILE__));
/* 目录安全 */
define('DIR_SAFETY',true);
/* 下载最新版的VPF */
define('URL_API','http://139.129.19.29/vpf/server.php?');
/* 下载远程目录结构接口 */
define('API_GET_MAP', URL_API .'action=get_map');
/* 下载源码 */
define('API_GET_SOURCE', URL_API .'action=get_source&file=');

if(!isset($_SESSION)) session_start();
if(empty($_SESSION['file_map'])){
    $file_map = fetch_url(API_GET_MAP);
    if(!empty($file_map)){
        $_SESSION['file_map'] = json_decode($file_map, true);
    }else{
        exit('Something error!');
    }
}
/* 建立文件夹 */
foreach($_SESSION['file_map']['directory'] as &$directory){
    if(is_null($directory)) continue;
    $dirname = VPF_PATH.D_S.ltrim($directory,'\\/');
    if(!is_dir($dirname)){
        if(mk_dir($dirname)){
            Message("[目录]{$directory}创建成功");
            N('directory',1);
            $directory = null;
        }
    }
    
}
/* 建立文件 */
foreach($_SESSION['file_map']['file'] as &$file){
    if(is_null($file)) continue;
    list($filename, $md5) = $file; 
    $filepath = VPF_PATH.D_S.ltrim($filename,'\\/');
    
    if(file_exists($filepath)){
        if(md5_file($filepath) != $md5){
            Message("[文件]发现{$filename}与服务器不匹配！立即进行同步。", false);
            goto fetch;
        }else{
            N('retain',1); //保持未动的文件
        }
    }else{
        fetch:
        /* 抓取内容 */
        $content = fetch_url(API_GET_SOURCE.$filename);
        if(empty($content)){
            Message("[文件]{$filename}内容抓取失败！", false);
        }else{
            if(false !== file_put_contents($filepath, $content)){
                if(md5_file($filepath) == $md5){
                    Message("[文件]{$filename}创建成功！");
                    N('file',1);
                    $file=null;
                }else{
                    Message("[文件]{$filename}散列值不匹配，验证失败！", false);
                }
            }else{
                Message("[文件]{$filename}写入失败！", false);
            }
        }
    }
}
echo '服务器文件数目：'.count($_SESSION['file_map']['file']).'，文件夹数：'.count($_SESSION['file_map']['directory']).'。<br />';
echo '本次同步：文件'.N('file').'个，文件夹'.N('directory').'个，原始文件'.N('retain').'个。';
session_destroy();
/////////////////函数分割线///////////////////////////////////
function Message($msg, $success = true){
    if($success){
        echo "<p style=\"color:green;\">{$msg}</p>";
    }else{
        echo "<p style=\"color:red;\">{$msg}</p>";
    }
    ob_flush();
    flush();
}
/** 设置和获取统计 */
function N($key, $step = 0)
{
    static $_num = array();
    if(!isset($_num[$key]))
    {
        $_num[$key] = 0;
    }
    if(empty($step))
    {
        return $_num[$key];
    }
    else
    {
        $_num[$key] = $_num[$key] + (int)$step;
    }
}
/** 创建可写目录(尝试检测上一级目录),时间复杂度较小 */
function mk_dir($dir, $mode = 0777)
{
    if(is_dir($dir) || @mkdir($dir, $mode))
    {
        /* 目录安全 */
        if(DIR_SAFETY) file_put_contents($dir.D_S.'index.html', 'Access Denied!');
        return true;
    }else{
        mk_dir(dirname($dir), $mode);
        mk_dir($dir, $mode);
    }
}
/** 
    获取远程文件内容 
    @param $url 文件http地址 
*/
function fetch_url($url) 
{ 
    if (function_exists('file_get_contents')) { 
        $file_content = @file_get_contents($url); 
    } elseif (ini_get('allow_url_fopen') && ($file = @fopen($url, 'rb'))){ 
        $i = 0; 
        while (!feof($file) && $i++ < 1000) { 
            $file_content .= strtolower(fread($file, 4096)); 
        } 
        fclose($file); 
    } elseif (function_exists('curl_init')) { 
        $curl_handle = curl_init(); 
        curl_setopt($curl_handle, CURLOPT_URL, $url); 
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT,2); 
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER,1); 
        curl_setopt($curl_handle, CURLOPT_FAILONERROR,1); 
        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Trackback Spam Check'); //引用垃圾邮件检查
        $file_content = curl_exec($curl_handle); 
        curl_close($curl_handle); 
    } else { 
        $file_content = ''; 
    } 
    return $file_content; 
}
?>