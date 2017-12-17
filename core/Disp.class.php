<?php
/**
 *--------------------------------------------------
 * VPF URL解析 路由 调度
 *--------------------------------------------------
 * @program     : VPF
 * @create      : 2012-3-10 14:37:30
 * @description :
 */
namespace Vpf;

class Disp extends Vpf
{
    /** URL映射到控制器 */
    public static function dispatch(){
        /* 命令行下的路由 */
        if(IS_CLI && $GLOBALS['argc'] > 1){
            array_shift($GLOBALS['argv']);
            $_GET[C('VAR_PATHINFO')] = array_shift($GLOBALS['argv']);
        }
        
        self::get_pathInfo(); /* 分析PATHINFO信息 */
        
        if(!self::router_check()){
            self::parse_pathinfo(); /* 默认URL调度 */
        }
        /* 定义常量 */
        define('GROUP_NAME', self::get_gc(C('VAR_GROUP')));
        define('CTRL_NAME', self::get_gc(C('VAR_CTRL'), C('DEFAULT_CTRL')));
        define('ACTN_NAME', self::get_actn(C('VAR_ACTN'), C('DEFAULT_ACTN')));
        if(!IS_CLI){
            define('__HOST__', '//'.get_env('HTTP_HOST')); /* 当前应用主机名，Scheme自适应*/
        
            define('__ROOT__', rtrim(__HOST__.'/'.ROOT,'/')); /* 网站根地址 */
            define('__APP__' , rtrim(__HOST__.'/'.APP_ROOT,'/')); /* 当前应用地址 */
        }
        $_REQUEST = array_merge($_POST, $_GET); /* 保证$_REQUEST正常取值 */
    }

    /** 获得服务器的PATH_INFO信息 */
    private static function get_pathInfo(){
        if(!empty($_GET[C('VAR_PATHINFO')])){ /* 兼容 PATHINFO 参数 */
            $path = $_GET[C('VAR_PATHINFO')]; 
            unset($_GET[C('VAR_PATHINFO')]);
            
        }elseif(!empty($_SERVER['PATH_INFO'])){ 
            $pathInfo = $_SERVER['PATH_INFO'];
            //TODO:
            if(0 === strpos($pathInfo, $_SERVER['SCRIPT_NAME'])){
                $path = substr($pathInfo, strlen($_SERVER['SCRIPT_NAME']));
            }else{
                $path = $pathInfo;
            }
            
        }elseif(!empty($_SERVER['ORIG_PATH_INFO'])){
            $pathInfo = $_SERVER['ORIG_PATH_INFO'];
            if(0 === strpos($pathInfo, $_SERVER['SCRIPT_NAME'])){
                $path = substr($pathInfo, strlen($_SERVER['SCRIPT_NAME']));
            }else{
                $path = $pathInfo;
            }
        }elseif (!empty($_SERVER['REDIRECT_PATH_INFO'])){
            $path = $_SERVER['REDIRECT_PATH_INFO'];
        }elseif(!empty($_SERVER["REDIRECT_URL"])){
            $path = $_SERVER["REDIRECT_URL"];
            if(empty($_SERVER['QUERY_STRING']) || $_SERVER['QUERY_STRING'] == $_SERVER["REDIRECT_QUERY_STRING"]){
                $parsedUrl = parse_url($_SERVER["REQUEST_URI"]);
                if(!empty($parsedUrl['query'])){
                    $_SERVER['QUERY_STRING'] = $parsedUrl['query'];
                    parse_str($parsedUrl['query'], $GET);
                    $_GET = array_merge($_GET, $GET);
                    reset($_GET);
                }else{
                    unset($_SERVER['QUERY_STRING']);
                }
                reset($_SERVER);
            }
        }
        
        
        if(C('URL_HTML_SUFFIX') && !empty($path)){
            $path = preg_replace('/\.'.trim(C('URL_HTML_SUFFIX'), '.').'$/', '', $path);
        }
        $_SERVER['PATH_INFO'] = empty($path) ? '/' : $path;
    }

    /** 解析特殊URL调度检查 */
    private static function router_check(){
        $pathinfo = trim($_SERVER['PATH_INFO'], '/');
        if(empty($pathinfo)){
            return true; /* path_info为空则不处理 */
        }
        if(!C('URL_ROUTER')){
            return false; /* 是否开启路由使用 */
        }
        /* 路由文件(routes.php)优先于cfg中的配置定义 */
        $routes = C('URL_ROUTE_RULES');
        if(is_array(C('routes'))){
            $routes = C('routes');
        }
        
        /* 路由处理 */
        if(!empty($routes)){
            /* PATHINFO模式各参数之间的分割符号 */
            $depr = C('URL_PATHINFO_DEPR');
            foreach($routes as $key => $route){
                if(0 === stripos($pathinfo.$depr, $route[0].$depr)){
                    /* 简单: array('路由定义别名','（组名@）控制器/操作名', '路由对应变量', '额外参数') */
                    /* 获得控制器名和操作名 */
                    $var = self::parse_gca($route[1]);
                    /* 获取当前路由参数对应的变量 */ 
                    //str_ireplace 把字符串 $pathinfo.$depr 中的字符 $route[0].$depr（不区分大小写）替换成 ''。
                    $paths_vars = explode($depr, trim(str_ireplace($route[0].$depr, '', $pathinfo.$depr), $depr));
                    $vars = explode(',', $route[2]);
                    for($i=0;$i<count($vars);$i++){
                        $var[$vars[$i]] = array_shift($paths_vars);
                    }
                    /* 解析剩余URL参数 */
                    $res = preg_replace_callback('@(\w+)\/([^,\/]+)@', function($matches) use(&$var){$var[$matches[1]]= $matches[2];}, implode('/', $paths_vars));
                    $_GET = array_merge($var, $_GET);
                    if(isset($route[3])){
                        parse_str($route[3], $params);
                        $_GET = array_merge($_GET, $params);
                    }
                    return true;
                }elseif(1 < substr_count($route[0], '/') && preg_match($route[0], $pathinfo, $matches)){
                    /* 正则: array('正则定义别名', '（组名@）控制器/操作名', '路由对应变量', '额外参数') */
                    $var = self::parse_gca($route[1]);
                    /* 获取当前路由参数对应的变量 */
                    $vars = explode(',', $route[2]);
                    for($i = 0; $i < count($vars); $i++){
                        $var[$vars[$i]] = $matches[$i+1];
                    }
                    /* 解析剩余URL参数 */
                    $res = preg_replace_callback('@(\w+)\/([^,\/]+)@', function($matches) use(&$var){$var[$matches[1]]= $matches[2];}, str_replace($matches[0], '', $pathinfo));
                    $_GET = array_merge($var, $_GET);
                    if(isset($route[3])){
                        parse_str($route[3],$params);
                        $_GET = array_merge($_GET,$params);
                    }
                    return true;
                }
            }
        }
        return false;
    }

    /** 解析pathinfo */
    private static function parse_pathinfo(){
        $depr = C('URL_PATHINFO_DEPR');
        $gdepr = C('VAR_GROUP_DEPR');
        /* 去掉两端的控制参数分割符 */
        $paths = trim($_SERVER['PATH_INFO'], $depr);
        $paths_arr = explode($depr, $paths);
        $var = array();
        /* 判断是否定义了控制组名 */
        if(strpos($paths_arr[0], $gdepr) !== false){ 
            $ctrl_info = explode($gdepr, array_shift($paths_arr), 2);
            /* 定义控制组 */
            if(!isset($_GET[C('VAR_GROUP')])){ /* 还没有定义控制组名称 */
                $var[C('VAR_GROUP')] = filter_path(array_shift($ctrl_info), ''); //过滤所有其他字符
            }else{ //如果定义了，就丢弃
                array_shift($ctrl_info);
            }
            /* 定义控制器 */
            if(!isset($_GET[C('VAR_CTRL')])){ /* 还没有定义控制器名称 */
                $var[C('VAR_CTRL')] = array_shift($ctrl_info);
            }
        }else{
            if(!isset($_GET[C('VAR_CTRL')])){ /* 还没有定义名称 */
                $var[C('VAR_CTRL')] = array_shift($paths_arr);
            }else{
                array_shift($paths_arr);
            }
        }
        if(!isset($_GET[C('VAR_ACTN')])){ /* 还没有定义名称 */
            $var[C('VAR_ACTN')] = array_shift($paths_arr);
        }
        /* 解析剩余的URL参数 */
        $res = preg_replace_callback('@(\w+)'.$depr.'([^'.$depr.'\/]+)@', function($matches) use(&$var){$var[$matches[1]]= $matches[2];}, implode($depr, $paths_arr));
        $_GET = array_merge($var, $_GET);
    }

    /** 获取控制组、控制器和操作名（特殊路由调度中使用） */
    private static function parse_gca($route){
        $var = array();
        $depr = C('URL_PATHINFO_DEPR');
        $gdepr = C('VAR_GROUP_DEPR');
        $route = trim($route, $depr);
        $route_arr = explode($depr, $route);
        if(strpos($route_arr[0], $gdepr) !== false){
            list($var[C('VAR_GROUP')], $var[C('VAR_CTRL')]) = explode($gdepr, array_shift($route_arr), 2);
        }else{
            $var[C('VAR_CTRL')] = array_shift($route_arr);
        }
        $var[C('VAR_ACTN')] = array_shift($route_arr);
        return $var;
    }
    
    /** 获得控制组、控制器名称 */
    private static function get_gc($var, $default = ''){
        /* 不考虑POST */
        $gvar = (!empty($_GET[$var]) ? $_GET[$var] : $default);
        unset($_GET[$var]);
        if(C('URL_CASE_IGNORE')){
            /* index.php/aaa_bbb/index/ 识别到 AaaBbbCtrl控制器 */
            $gvar = parse_name(strtolower($gvar), 1);
        }
        return strip_tags($gvar);
    }
    
    private static function get_actn($var, $default = ''){
        /* 不考虑POST */
        $actn = (!empty($_GET[$var]) ? $_GET[$var] : $default);
        unset($_GET[$var]);
        if(C('URL_CASE_IGNORE')){
            $actn = strtolower($actn);
        }
        return strip_tags($actn);
    }

    /** 私有化构造函数 */
    private function __construct(){}
}
?>