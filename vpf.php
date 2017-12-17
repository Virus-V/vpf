<?php
/**
 *--------------------------------------------------
 * 框架公共文件
 *--------------------------------------------------
 * @program     : VPF
 * @create      : 2012-2-28 10:59:29
 * @description :
 */

/* PHP版本检查 */
namespace Vpf;

if(version_compare(PHP_VERSION, '5.3.0', '<')) die('PHP Version must > 5.3!');

G('beginTime');

define('IN_VPF',true);

/* 分隔符 */
define('D_S', DIRECTORY_SEPARATOR);
define('P_S', PATH_SEPARATOR); /*include多个路径使用，在win下，当你要include多个路径的话，你要用”;”分号 隔开，但在linux下就使用”:”冒号 隔开的。*/

define('MEMORY_ANALYSE', function_exists('memory_get_usage'));
define('RUNTIME_DIR','runtime'); /*定义runtime目录名*/

if(MEMORY_ANALYSE){
    $GLOBALS['_startUseMems'] = memory_get_usage(); /* 记录内存初始使用 */
}
/* 定义路径 */
if(!defined('APP_NAME')) define('APP_NAME', basename(dirname($_SERVER['SCRIPT_FILENAME']))); /* 应用名称 */
if(!defined('APP_PATH')) define('APP_PATH', dirname($_SERVER['SCRIPT_FILENAME'])); /* 应用的配置路径 */
if(!defined('APP_ENTRY')) define('APP_ENTRY',__FILE__); /* 进入应用的入口文件完整路径 */
if(!defined('VPF_PATH')) define('VPF_PATH', dirname(__FILE__)); /* VPF框架路径 */

if(!defined('RUNTIME_PATH')) define('RUNTIME_PATH',APP_PATH.D_S.RUNTIME_DIR); /* runtime目录*/
$runtime = '~runtime.php';
if(is_file(RUNTIME_PATH.D_S.$runtime)){
    require RUNTIME_PATH.D_S.$runtime;
}else{
    /* 加载常量定义文件 */
    require VPF_PATH.D_S.'comm'.D_S.'define.php'; 
    
    require VPF_PATH.D_S.COMM_DIR.D_S.'func.php'; /* 加载框架函数库 */
    C(require VPF_PATH.D_S.COMM_DIR.D_S.'options.php'); /* 加载框架默认选项 */
    require VPF_PATH.D_S.COMM_DIR.D_S.'runtime.php'; /* 检查部署 */
    
    require(CFG_PATH.D_S.DEFINE_FILE);  /* 加载应用常量定义 */
    C(require(CFG_PATH.D_S.CONFIG_FILE)); /* 加载应用配置文件 */
    
    $core_list = include VPF_PATH.D_S.COMM_DIR.D_S.'core.php'; /* 读取框架核心文件列表 */
    /* 加载框架核心文件 */
    foreach($core_list as $key => $file){
        require_cache($file);
    }
    $interface_list = include VPF_PATH.D_S.COMM_DIR.D_S.'interface.php';/* 读取接口列表 */
    /* 加载框架核心文件 */
    foreach($interface_list as $key => $file){
        require_cache($file);
    }
    
}
/* 测量加载时间 */
G('loadTime');

/* 过滤敏感字符 */
foreach(C('TO_FILTER') as $X) !empty(${$X}) && ${$X} = !MAGIC_QUOTES_GPC && C('PARAM_FILTER') ? array_map_recursive(C('PARAM_FILTER_ACTION'),${$X}) : ${$X}; 

/* 遍历数组应用过滤函数 */
function array_map_recursive($filter, $data){
    $result = array();
    foreach ($data as $key => $val) {
        $result[$key] = is_array($val)
        ? array_map_recursive($filter, $val)
        : call_user_func($filter, $val);
    }
    return $result;
}

/* 记录和统计时间 */
function G($start, $end = '', $dec = 6){
    static $_info = array();
    if(!empty($end)){
        if(!isset($_info[$end])){
            $_info[$end] = microtime(true);
        }
        return number_format(($_info[$end]-$_info[$start]),$dec);
    }else{
        $_info[$start] = microtime(true);
    }
}
?>