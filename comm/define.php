<?php
/**
 *--------------------------------------------------
 * 常量定义
 *--------------------------------------------------
 * @program     : VPF
 * @create      : 2012-3-2 13:28:41
 * @edit        : 2017-5-14 15:29:31
 */
namespace Vpf;

if(!defined('VPF_PATH')) exit();

/* 版本信息 */
define('VPF_VERSION', '6.3.9');
define('VPF_RELEASE', '20170514');

if(version_compare(PHP_VERSION,'5.4.0','<'))
{
    ini_set('magic_quotes_runtime',0);
    //@set_magic_quotes_runtime(0);
    define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc() ? true : false);
}else{
    define('MAGIC_QUOTES_GPC', false);
}
/* 定义模式 */
define('IS_CGI', substr(PHP_SAPI, 0, 3)=='cgi' ? 1 : 0);
define('IS_WIN', strstr(PHP_OS, 'WIN') ? 1 : 0);
define('IS_CLI', PHP_SAPI == 'cli'? 1 : 0);

if(!IS_CLI)
{
    /* 当前文件名 */
    if(!defined('_SELF_URL_')) 
    {
        /* CGI/FASTCGI模式下 */
        if(IS_CGI) 
        {
            $_temp = explode('.php', $_SERVER["PHP_SELF"]);
            define('_SELF_URL_', rtrim(str_replace($_SERVER["HTTP_HOST"],'',$_temp[0].'.php'),'/'));
        }else{
            define('_SELF_URL_', rtrim($_SERVER["SCRIPT_NAME"],'/'));
        }
    }
    
    if(!defined('DOCUMENT_ROOT')) /* 服务器根目录，绝对目录，用于存储文件 */
    {
        $self_path_arr = preg_split("/((\\\\)|(\/))/", dirname(_SELF_URL_),0,PREG_SPLIT_NO_EMPTY);
        $entry_path_arr = preg_split("/((\\\\)|(\/))/", dirname(APP_ENTRY),0,PREG_SPLIT_NO_EMPTY);
        $doucment_root_arr = array_diff($entry_path_arr,$self_path_arr);
        define('DOCUMENT_ROOT', join(D_S,$doucment_root_arr));
    }
    /* 网站URL根目录，也就是vpf所在的目录*/
    if(!defined('ROOT'))
    {
        $vpf_path_arr = preg_split("/((\\\\)|(\/))/", dirname(VPF_PATH),0,PREG_SPLIT_NO_EMPTY);
        $document_root_arr = preg_split("/((\\\\)|(\/))/", DOCUMENT_ROOT,0,PREG_SPLIT_NO_EMPTY);
        $root_path_arr = array_diff($vpf_path_arr,$document_root_arr);
        define('ROOT', join('/',$root_path_arr));
    }
    /* 应用在url中访问的目录，就是APP*/
    if(!defined('APP_ROOT'))
    {
        $app_path_arr = preg_split("/((\\\\)|(\/))/", APP_PATH,0,PREG_SPLIT_NO_EMPTY);
        $document_root_arr = preg_split("/((\\\\)|(\/))/", DOCUMENT_ROOT,0,PREG_SPLIT_NO_EMPTY);
        $app_root_path_arr = array_diff($app_path_arr,$document_root_arr);
        define('APP_ROOT', join('/',$app_root_path_arr));
    }
    /* 普通模式  
     index.php?c=ctrl&a=actn&param1=1
     c为 VAR_CTRL，a为VAR_ACTN
    */
    define('URL_COMMON', 1); 
    
    /* PATHINFO模式 
     index.php/ctrl/actn/param1/1
    */
    define('URL_PATHINFO', 2); 
    
    /* REWRITE模式 
    PATHINFO 重写
    .htaccess Example：
    
    # 将 RewriteEngine 模式打开
    RewriteEngine On
    
    # 如果程序放在子目录中，请将 / 修改为 /子目录/
    RewriteBase /
    
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^(.*)$ index.php/$1 [QSA,PT,L]
    
    */
    define('URL_REWRITE', 3); 
    
    /* 兼容模式 
     index.php?s=/ctrl/actn/param1/1
    */
    define('URL_COMPAT', 4); 
}
/* 定义文件名 */
define('PAGE_TRACE_FILE','page_trace.php');
define('DEBUG_ERR_FILE','err_debug.php');
define('ERR_FILE','err_no_msg.php');
define('DISP_JUMP_FILE','disp_jump.php');
define('DEFINE_FILE','defines.php'); /* 应用常量定义文件 */
define('CONFIG_FILE','config.php'); /* 应用配置参数文件 */
define('FUNC_FILE','func.comm.php'); /* 应用公共函数文件 */
define('LANG_FILE','lang.comm.php'); /* 应用语言包公共语言文件 */

/* 设置目录 */
define('HTML_DIR', 'html');
define('CFG_DIR', 'cfg');
define('COMM_DIR', 'comm');
define('LIB_DIR', 'lib');
define('LIB_CTRL_DIR', 'ctrl');
define('LIB_MODL_DIR', 'modl');
define('LIB_CLASS_DIR', 'class');
define('LANG_DIR', 'lang');
define('TPL_DIR', 'tpl');
/* define('INTERFACE_DIR', 'interface'); */
define('CACHE_DIR', 'cache'); /* 模板缓存目录 */
define('TEMP_DIR', 'temp'); /* 数据缓存目录 Cache类 S() */
define('DATA_DIR', 'data'); /* 数据文件目录 F() */
define('LOG_DIR', 'log'); /* 日志目录 */
define('PUBLIC_DIR', 'public'); /* 公共文件目录 */

/* 设置路径 */
define('HTML_PATH', APP_PATH.D_S.HTML_DIR);
define('CFG_PATH', APP_PATH.D_S.CFG_DIR);
define('COMM_PATH', APP_PATH.D_S.COMM_DIR);
define('LIB_PATH', APP_PATH.D_S.LIB_DIR);
define('LIB_CTRL_PATH', LIB_PATH.D_S.LIB_CTRL_DIR);
define('LIB_MODL_PATH', LIB_PATH.D_S.LIB_MODL_DIR);
define('LIB_CLASS_PATH', LIB_PATH.D_S.LIB_CLASS_DIR);
define('LANG_PATH', APP_PATH.D_S.LANG_DIR);
define('TPL_PATH', APP_PATH.D_S.TPL_DIR);
define('CACHE_PATH', RUNTIME_PATH.D_S.CACHE_DIR);
define('TEMP_PATH', RUNTIME_PATH.D_S.TEMP_DIR);
define('DATA_PATH', RUNTIME_PATH.D_S.DATA_DIR);
define('LOG_PATH', RUNTIME_PATH.D_S.LOG_DIR);
if(APP_PATH != dirname(VPF_PATH))
{
    define('PUBLIC_PATH', dirname(VPF_PATH).D_S.PUBLIC_DIR); /* 公用文件夹目录放置根目录下 */
}else{
    define('PUBLIC_PATH', APP_PATH.D_S.PUBLIC_DIR); /* 公用文件夹目录放置应用目录下 */
}
/* VPF相关路径 */
/*define('VPF_INTERFACE_PATH', VPF_PATH.D_S.INTERFACE_DIR);*/ /* VPF接口目录 */
define('VPF_EXT_PATH', VPF_PATH.D_S.LIB_DIR); /* VPF扩展类库目录 */
define('VPF_VENDER_PATH', VPF_PATH.D_S.'vender'); /* VPF第三方类库目录 */
?>