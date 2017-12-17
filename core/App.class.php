<?php
/**
 *--------------------------------------------------
 * VPF 应用基类
 *--------------------------------------------------
 * @program     : VPF
 * @create      : 2012-3-18 22:16:38
 * @description :
 */
namespace Vpf;
use Vpf\Lib;

class App extends Vpf {
    /** 开始运行 */
    public function run(){
        set_error_handler(array(__NAMESPACE__.'\App', 'app_error'));
        /* 注册默认异常处理函数 */
        set_exception_handler(array(__NAMESPACE__.'\App', 'app_exception'));
        /* 注册结束后执行函数 */
        register_shutdown_function(array(__NAMESPACE__.'\App', 'app_shutdown'));
        //注册自动加载函数
        spl_autoload_register('Vpf\load_by_name_space',true);
        //[RUNTIME]
        self::build(); /* 预编译应用 */
        //[/RUNTIME]
        Disp::dispatch(); /* URL调度并定义CTRL_NAME和ACTN_NAME等 */
        self::load_lang(); /* 加载应用语言 */
        self::check_theme(); /* 检测并定义模板变量 */
        if(C('HTML_CACHE')){
            header('Content-Type:'.C('TE_TPL_CONTENT_TYPE').'; charset='.C('DEFAULT_CHARSET')); /* 指定字符集 */
            header('Cache-control: private'); /*支持页面回跳 */
            header('X-Powered-By: Virus.V');
            Lib\HtmlCache::read_htmlCache(); /* 开启静态缓存 */
        }

        date_default_timezone_set(C('DEFAULT_TIMEZONE')); /* 设置时区 */
        G('initTime'); /* 记录初始化时间 */
        $instance = get_instance('App\\Ctrl\\'.(defined('GROUP_NAME')&& GROUP_NAME != '' ? GROUP_NAME.'\\' : '').CTRL_NAME.'Ctrl', '', ACTN_NAME, ''); /* 创建控制器实例 */
        G('execTime'); /* 记录初始化时间 */
        
        /* 调试页面跟踪信息 */
        if(C('SHOW_RUN_TIME')){
            Debug::show_time();
        }
        if(C('SHOW_PAGE_TRACE')){
            Debug::show_trace();
        }

        G('endTime');
    }

    //[RUNTIME]
    /** 读取配置 编译应用 */
    private static function build(){
        $common = '';
        /* 加载应用公共文件 */
        if(is_file(COMM_PATH.D_S.FUNC_FILE)){
            include COMM_PATH.D_S.FUNC_FILE; /* 加载应用公共函数 */
            if(!C('APP_DEBUG')){
                $common .= compile(COMM_PATH.D_S.FUNC_FILE); /* 编译文件 */
            }
        }
        /* 加载动态配置文件 */
        $configs = C('APP_CFG_LIST');
        if(is_string($configs)){
            $configs = explode(',', $configs);
            foreach($configs as $config){
                $file = CFG_PATH.D_S.$config.'.php';
                if(is_file($file)){
                    C($config, array_change_key_case(include $file));
                }
            }
        }
        C('APP_CFG_LIST',''); /* 清除配置参数 */

        /* 编译应用 */
        if(!C('APP_DEBUG')){
            build_runtime_cache($common);
        }
    }
    //[/RUNTIME]

    /** 语言文件加载 */
    private static function load_lang(){
        $langSet = C('DEFAULT_LANG');
        $varLang = C('VAR_LANG');
        if(C('LANG_DETECT')){
            if(isset($_GET[$varLang])){
                $langSet = $_GET[$varLang];
                cookie($varLang, strtolower($langSet), 3600);
            }elseif(cookie($varLang)){
                $langSet = cookie($varLang);
            }elseif(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
                preg_match('/^([a-z\-]+)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);
                $langSet = $matches[1];
                cookie($varLang, strtolower($langSet), 3600);
            }
        }
        //TODO:如果传过来的lang为黑客修改过的
        define('LANG_SET', strtolower($langSet));
        /* 加载框架默认语言 */
        if(is_file(VPF_PATH.'/lang/'.C('DEFAULT_LANG').'.lang.php')){
            L(include VPF_PATH.'/lang/'.C('DEFAULT_LANG').'.lang.php');
        }
        /* 读取应用公共语言包 */
        if(is_file(LANG_PATH.D_S.LANG_SET.D_S.LANG_FILE)){
            L(include LANG_PATH.D_S.LANG_SET.D_S.LANG_FILE);
        }elseif(is_file(LANG_PATH.D_S.C('DEFAULT_LANG').D_S.LANG_FILE)){
            L(include LANG_PATH.D_S.C('DEFAULT_LANG').D_S.LANG_FILE);
        }
        /* 读取控制器语言包 */
        if(is_file(LANG_PATH.D_S.LANG_SET.D_S.(defined('GROUP_NAME')&& GROUP_NAME != '' ? GROUP_NAME.D_S : '').CTRL_NAME.'.lang.php')){
            L(include LANG_PATH.D_S.LANG_SET.D_S.(defined('GROUP_NAME')&& GROUP_NAME != '' ? GROUP_NAME.D_S : '').CTRL_NAME.'.lang.php');
        }elseif(is_file(LANG_PATH.D_S.C('DEFAULT_LANG').D_S.(defined('GROUP_NAME')&& GROUP_NAME != '' ? GROUP_NAME.D_S : '').CTRL_NAME.'.lang.php')){
            L(include LANG_PATH.D_S.C('DEFAULT_LANG').D_S.(defined('GROUP_NAME')&& GROUP_NAME != '' ? GROUP_NAME.D_S : '').CTRL_NAME.'.lang.php');
        }
    }

    /** 设置模板主题 */
    private static function check_theme(){
        $tplSet = C('TE_TPL_THEME');
        if(C('TE_DETECT_THEME')) {  /* 侦测模板主题 */
            $t = C('VAR_TPL');
            if(isset($_GET[$t])){
                $tplSet = $_GET[$t];
            }elseif(cookie($t)){
                $tplSet = cookie($t);
            }
            if(!is_dir(TPL_PATH.D_S.$tplSet)){ /* 主题不存在时使用默认主题 */
                $tplSet = C('TE_TPL_THEME');
            }
            cookie($t, $tplSet);
        }
        /* 模板相关目录常量 */
        C('TE_TPL_THEME', $tplSet); /* 当前主题名称 */
        define('THEME_NAME', $tplSet); /* 当前主题名称 */
        define('THEME_PATH', TPL_PATH.(THEME_NAME ? D_S.THEME_NAME : '')); /* 当前主题路径 */
        if(!IS_CLI){
            define('__THEME__', __APP__.'/'.TPL_DIR.'/'.(THEME_NAME ? THEME_NAME : '')); /* 当前应用模板主题 URL */
            if(APP_PATH != dirname(VPF_PATH)){
                define('__PUBLIC__', __ROOT__.'/'.PUBLIC_DIR); /* 网站公共文件URL */
            }else{
                define('__PUBLIC__', __APP__.'/'.PUBLIC_DIR); /* 实用应用公共文件URL */
            } 
        }
        return;
    }

    /** 自定义错误处理 */
    public static function app_error($errno, $errstr, $errfile, $errline){
        $errno = $errno & error_reporting();
        if(!defined('E_STRICT')) define('E_STRICT', 2048);
        if(!defined('E_RECOVERABLE_ERROR')) define('E_RECOVERABLE_ERROR', 4096);
        switch($errno){
            case E_ERROR:
            //case E_WARNING:
            case E_PARSE:
            case E_NOTICE:
            case E_CORE_ERROR:
            case E_CORE_WARNING:
            case E_COMPILE_ERROR:
            case E_COMPILE_WARNING:
            case E_USER_ERROR:
                $errorStr = "[{$errno}]{$errstr} ".basename($errfile)."({$errline})";
                Log::record($errorStr);
                halt($errorStr);
                break;
            case E_WARNING: //
            case E_USER_WARNING:
            case E_USER_NOTICE:
            case E_STRICT:
            case E_RECOVERABLE_ERROR:
            default:
                $errorStr = "[{$errno}]{$errstr} ".basename($errfile)."({$errline})";
                Log::record($errorStr);
                break;
        }
    }
    
    /** 自定义异常处理 */
    public static function app_exception($exception){
        /* 摘抄自PHP手册 */
        // these are our templates
        $traceline = "#%s %s(%s): %s(%s)";
        $msg = "PHP Fatal error:  Uncaught exception '%s' with message '%s' in %s:%s\nStack trace:\n%s\n  thrown in %s on line %s";

        // alter your trace as you please, here
        $trace = $exception->getTrace();
        foreach ($trace as $key => $stackPoint) {
            // I'm converting arguments to their type
            // (prevents passwords from ever getting logged as anything other than 'string')
            $trace[$key]['args'] = array_map('gettype', $trace[$key]['args']);
        }

        // build your tracelines
        $result = array();
        foreach ($trace as $key => $stackPoint) {
            $result[] = sprintf(
                $traceline,
                $key,
                $stackPoint['file'],
                $stackPoint['line'],
                $stackPoint['function'],
                implode(', ', $stackPoint['args'])
            );
        }
        // trace always ends with {main}
        $result[] = '#' . ++$key . ' {main}';

        // write tracelines into main template
        $msg = sprintf(
            $msg,
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            implode("\n", $result),
            $exception->getFile(),
            $exception->getLine()
        );
        // log or echo as you please
        Log::record($msg);
    }
    
    public static function app_shutdown(){
        $err_info = error_get_last();
        if(!is_null($err_info)){ //如果发现了错误
            $err_str = '';
            $depa = '';
            foreach($err_info as $key=>$value){
                $err_str .= $depa.'['.$key.']:'.$value;
                $depa = "\n";
            }
            Log::record($err_str);
        }
        if(C('LOG_RECORD')){
            //Log::record('app_shutdown');
            Log::save();
        }
    }
    
    /** 保存日志 */
    public function __destruct(){
        //由这两个验证，app如果有错误，则只会调用app_shutdown方法，
        //如果正常退出，则先调用app_shutdown，再调用__destruct。
        //TODO:这个可能是可有可有可无。
        //if(C('LOG_RECORD')){
        //    Log::record('__destruct');
        //    Log::save();
        //}
    }

}

?>