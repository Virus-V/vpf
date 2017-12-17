<?php 
/**
 *--------------------------------------------------
 * VPF 公共函数库
 *--------------------------------------------------
 * @program     : VPF
 * @create      : 2012-3-18 22:16:38
 * @description :
 */
namespace Vpf;

if(!defined('VPF_PATH')) exit();
/** 获取环境信息 */
function get_env($varName){
    if(isset($_SERVER[$varName])){
        return $_SERVER[$varName];
    }elseif(isset($_ENV[$varName])){
        return $_ENV[$varName];
    }elseif(getenv($varName)){
        return getenv($varName);
    }elseif(function_exists('apache_getenv') && apache_getenv($varName, true)){
        return apache_getenv($varName, true);
    }
    return false;
}

/** 批量创建目录 */
function mkdirs($dirs, $mode = 0777){
    foreach ($dirs as $dir) mk_dir($dir, $mode);
}

/** 创建可写目录(尝试检测上一级目录),时间复杂度较小 */
function mk_dir($dir, $mode = 0777){
    if(is_dir($dir) || @mkdir($dir, $mode)){
        /* 目录安全 */
        if(C('DIR_INDEX')) file_put_contents($dir.D_S.C('DIR_INDEX_FILENAME'), C('DIR_INDEX_CONTENT'));
        return true;
    }else{
        mk_dir(dirname($dir), $mode);
        mk_dir($dir, $mode);
    }
}

 /*复制xCopy函数用法：    
  *   xCopy("feiy","feiy2",1):拷贝feiy下的文件到   feiy2,包括子目录    
  *   xCopy("feiy","feiy2",0):拷贝feiy下的文件到   feiy2,不包括子目录    
  *参数说明：    
  *   $source:源目录名    
  *   $destination:目的目录名    
  *   $child:复制时，是不是包含的子目录 
  */
function xcopy($source, $destination, $child=1){
    if (!file_exists($destination)){
        if (!mk_dir(rtrim($destination, '/'), 0777)){
            exit("Can not make dir!");
            return false;
        }
        @chmod($destination, 0777);
    }
    if(!is_dir($source)){  
        return 0;
    }
    if(!is_dir($destination)){
        mk_dir($destination,0777);   
    }
    $handle=dir($source);
    while($entry=$handle->read()){
        if(($entry!=".")&&($entry!="..")){
            if(is_dir($source."/".$entry)){ 
                if($child)
                    xcopy($source."/".$entry,$destination."/".$entry,$child);
            }else{
                copy($source."/".$entry,$destination."/".$entry);
            }
        }    
    }    
    return 1;
}

/** 删除目录中的文件 $self:是否删除目录 */
function del_dir($dir, $self = true){
    if(is_dir($dir) == false){
        halt("The Directory Is Not Exist!");
    }
    $handle = opendir($dir);
    while(($file = readdir($handle)) !== false){
        if($file != "." && $file != ".."){
            is_dir("$dir/$file") ? del_dir("$dir/$file") : @unlink("$dir/$file");
        }
    }
    if(readdir($handle) == false){
        closedir($handle);
        if($self){
            @rmdir($dir);
        }
    }
}

/** 自动加载类 */
function load_by_name_space($classname)
{
    /* 加载应用的Ctrl类和Modl类 */
    $class_type = substr($classname, -4);
    /* 提取命名空间和类名称 */
    //if(strpos($classname, '\\') !== false){  //处女座建议：最好不要用这句，不然不舒服
    if(strpos($classname, 'Vpf\\') === 0){
        list($classname, $group_name) = explode('\\', strrev($classname), 2);
        $classname = strrev($classname);
        //$group_name = str_replace('\\', D_S, strrev($group_name)).D_S;
        $group_name = strrev($group_name);
    }
    /* 根据类的类型选择加载 */
    if('Modl' == $class_type){
        if(isset($group_name)){
            $group_name = ltrim(str_ireplace('Vpf\\App\\Modl', '', $group_name), '\\');
        }
        /* 获得model类的绝对路径 */
        $classfile = LIB_MODL_PATH.D_S.(!empty($group_name) ? str_replace('\\', D_S, $group_name).D_S : '').$classname.'.class.php';
        require_cache($classfile);
    }elseif('Ctrl' == $class_type){
        if(isset($group_name)){
            $group_name = ltrim(str_ireplace('Vpf\\App\\Ctrl', '', $group_name), '\\');
        }
        /* 构建ctrl类的绝对路径 */
        $classfile = LIB_CTRL_PATH.D_S.(!empty($group_name) ? str_replace('\\', D_S, $group_name).D_S : '').$classname.'.class.php';
        require_cache($classfile);
    }else{
        $include_path = get_include_path();
        $include_path .= P_S.LIB_CLASS_PATH; /*应用扩展类库目录*/
        $include_path .= P_S.VPF_EXT_PATH; /* VPF扩展类库目录 */
        $include_path .= P_S.VPF_VENDER_PATH; /* VPF第三方类库目录 */
        /* 根据自动加载路径设置尝试搜索 */
        if(C('APP_AUTOLOAD_PATH')){
            $paths = str_replace(',', P_S, C('APP_AUTOLOAD_PATH'));
            $include_path .= P_S.$paths;
        }
        set_include_path($include_path);
        import($classname);
    }
    return;
}

/** 设置和获取统计 */
function N($key, $step = 0)
{
    static $_num = array();
    if(!isset($_num[$key])){
        $_num[$key] = 0;
    }
    if(empty($step)){
        return $_num[$key];
    }else{
        $_num[$key] = $_num[$key] + (int)$step;
    }
}

/** 实例化Modl，传入的为表名
 *  把表名进行下划线命名法到
 *  帕斯卡命名法的一个转换，
 *  所以数据库里的表名中不能
 *  有大写字符。到模型类中还
 *  会把帕斯卡命名法转换成下划线命名法。
 */
/* Test => Vpf\Modl\TestModl */
/* AAA@Test => Vpf\Model\Aaa\TestModl */
/* Test_table => Vpf\Model\TestTableModl */
/* test_table => Vpf\Model\TestTableModl */
/* test_Table => Vpf\Model\TestTableModl */
/* TEST_TABLE => Vpf\Model\TestTableModl */
function M($tablename = '', $args = '', $connCfg = '')
{
    static $_modl = array();
    if(empty($tablename)){
        return new Modl('', $connCfg);
    }
    /* 检测是否定义组，且组名被严格定义，不会做任何变动；但模型名有个转换(大写全部转换成小写，下划线命名法转换成帕斯卡命名法) */
    if(strpos($tablename, C('VAR_GROUP_DEPR')) !== false){
        $tablename = str_replace(C('VAR_GROUP_DEPR'), '\\', $tablename);
        list($classname, $group_name) = explode('\\', strrev($tablename), 2);
        $classname = parse_name(strtolower(strrev($classname)), 1); //命名风格转换
        $_classname = 'Vpf\\App\\Modl\\'.strrev($group_name).'\\'.$classname;
    }else{
        $_classname = 'Vpf\\App\\Modl\\'.parse_name(strtolower(trim($tablename,'\\/ ')),1);
    }
	/* 上面全是获得_classname动作 */
    if(isset($_modl[$_classname])){
        return $_modl[$_classname];
    }
    $className = $_classname.'Modl';
    if(class_exists($className)){
        $modl = empty($args) ? new $className() : new $className($args);
    }else{
        $modl = new Modl($_classname, $connCfg);
    }
    $_modl[$_classname] = $modl;
    return $modl;
}

/** 简单类型数据(字符串,数组)快速文件数据读取和保存 */
function F($name, $value = '', $path = DATA_PATH)
{
    static $_cache = array();
    $path = rtrim($path, "/\\").D_S;
    $filename = $path.$name.'.php';
    if('' !== $value){
        if(is_null($value)){
            return @unlink($filename); /* 删除缓存 */
        }else{
            $dir = dirname($filename); /* 缓存数据 */
            if(!is_dir($dir)){
                mk_dir($dir); /* 目录不存在则创建 */
            }
            $_cache[$name] = $value;
            return file_put_contents($filename, strip_whitespace("<?php\nreturn ".var_export($value, true).";\n?>"));
        }
    }
    if(isset($_cache[$name])){
        return $_cache[$name];
    }
    if(is_file($filename)){ /* 获取缓存数据 */
        $value = include $filename;
        $_cache[$name] = $value;
        return $value;
    }
    return false;
}

/** 全局缓存设置和读取 */
/* 注意：从缓存中读取的数据会缓存个副本到内存，可能无法及时响应expire */
function S($name, $value = '', $expire = null, $options = array())
{
    $type = isset($options['cacheType']) ? $options['cacheType'] : '';
    static $_cache = array();
    $cache = Lib\Cache::connect($options);
    if ('' !== $value){
        if(is_null($value)){ /* 删除缓存 */
            $result = $cache->del($name);
            if($result){
                unset($_cache[$type.'_'.$name]);
            }
            return $result;
        }else{ /* 缓存数据 */
            $result = $cache->set($name, $value, $expire);
            if($result){
                $_cache[$type.'_'.$name] = $value;
            }
            return $result;
        }
    }
    if(isset($_cache[$type.'_'.$name])){
        return $_cache[$type.'_'.$name];
    }
    /* 获取缓存数据 */
    $value = $cache->get($name);
    $_cache[$type.'_'.$name] = $value;
    return $value;
}

/** 获取,定义语言(不区分大小写) */
function L($name = null, $value = null)
{
    static $_lang = array();
    if(empty($name)){ /* 无参数时获取所有 */
        return $_lang;
    }
    /* 获取或定义 */
    if(is_string($name)){
        $name = strtoupper($name);
        if(is_null($value)){
            return isset($_lang[$name]) ? $_lang[$name] : $name;
        }
        $_lang[$name] = $value; /* 语言定义 */
        return;
    }
    /* 批量定义 */
    if (is_array($name)){
        $_lang = array_merge($_lang, array_change_key_case($name, CASE_UPPER));
    }
    return;
}

/** 获取,定义配置 */
function C($name = null, $value = null)
{
    static $_config = array();
    if(empty($name)){ /* 无参数时获取所有 */
        return $_config;
    }
    /* 获取或定义 */
    if (is_string($name)){
        $name = strtolower($name);
        if (!strpos($name, '.')){
            if (is_null($value)){
                return isset($_config[$name]) ? $_config[$name] : null;
            }
            $_config[$name] = is_array($value) ? array_change_key_case($value) : $value;
            return;
        }
        /* 二维数组 */
        $name = explode('.', $name);
        if (is_null($value)){
            return isset($_config[$name[0]][$name[1]]) ? $_config[$name[0]][$name[1]] : null;
        }
        $_config[$name[0]][$name[1]] = $value;
        return;
    }
    /* 批量设置 */
    if (is_array($name)){
        foreach ($name as $key => $val){
            if(is_array($val)){
                $name[$key] = array_change_key_case($val);
            }
        }
        return $_config = array_merge($_config, array_change_key_case($name));
    }
    return null; /* 避免非法参数 */
}

/** URL组装 支持不同模式和路由 
parame:
ctrl:array(ctrl,actn) 调度器和控制器
params:array(key=>value,...) 参数 
suffix:生成的url是否带.html后缀
*/
function U($ctrl='', $params='', $suffix = true)
{
    /* 解析URL参数 */
    if(is_string($params)){
        parse_str($params, $params);
    }
    /* 解析控制信息 */
    if(is_string($ctrl)){
        if(strpos($ctrl, C('VAR_GROUP_DEPR')) !== false){
            list($group, $ctrl) = explode(C('VAR_GROUP_DEPR'), $ctrl);
            $_group = $group != 'THIS' ? $group : GROUP_NAME;
        }
        $ctrl = explode('/',$ctrl);
    }elseif(count($ctrl) > 2){
        $group = array_shift($ctrl);
        $_group = $group != 'THIS' ? $group : GROUP_NAME;
    }
    $_ctrl = (isset($ctrl[0]) && $ctrl[0] != 'THIS') ? $ctrl[0] : CTRL_NAME;
    $_actn = (isset($ctrl[1]) && $ctrl[1] != 'THIS') ? $ctrl[1] : ACTN_NAME;
    $url = '';
    switch(C('URL_TYPE')){
        case URL_REWRITE: /*重写模式*/
            $url_tmp = dirname(_SELF_URL_);
            if($url_tmp == '/' || $url_tmp == '\\'){
                $url_tmp = '';
            }
        break;
        
        case URL_COMPAT: /*兼容模式*/
            $url_tmp = _SELF_URL_.'?'.C('VAR_PATHINFO').'=';
        break;
        
        case URL_PATHINFO: /*PATHINFO模式*/
        case URL_COMMON: /*普通模式*/
        default:
            $url_tmp = _SELF_URL_;
    }
    if(C('URL_TYPE') > 1){
        $depr = C('URL_PATHINFO_DEPR');
        $str = $depr;
        foreach($params as $k => $v){
            $str .= $k.$depr.$v.$depr;
        }
        $str = substr($str, 0, -1);
        $url = strip_tags($url_tmp).'/'.(isset($_group) && $_group != '' ? $_group.C('VAR_GROUP_DEPR') : '').$_ctrl.$depr.$_actn.$str;
        if($suffix && C('URL_HTML_SUFFIX')){
            $url .= C('URL_HTML_SUFFIX');
        }
    }else{ /* 普通模式 */
        $params = http_build_query($params);
        $params = !empty($params) ? '&' . $params : '';
        $url = strip_tags($url_tmp).'?'.(isset($_group) && $_group != '' ? C('VAR_GROUP').'='.$_group.'&' : '').C('VAR_CTRL').'='.$_ctrl.'&'.C('VAR_ACTN').'='.$_actn.$params;
    }
    return $url;
}

/** 优化require_once */
function require_cache($filename)
{
    static $_importFiles = array();
    $filename = realpath($filename);
    if(!isset($_importFiles[$filename]) && file_exists($filename)){
        return $_importFiles[$filename] = require $filename;
    }
}

/** 导入函数(文件名中包含'.'时用'#'替换) */
function load($name, $basePath = '', $ext = '.php')
{
    $name = str_replace(array('.', '#'), array('/', '.'), $name);
    if(empty($basePath)){
        $basePath = COMM_PATH; /* 加载应用公共目录 */
    }
    $basePath = rtrim($basePath, '/\\').D_S;
    return require_cache($basePath.$name.$ext);
}

/** 导入第三方类(文件名中包含'.'时用'#'替换) */
function import($class, $basePath = '', $ext='.class.php')
{
    static $_file = array();
    static $_class = array();
    $class = str_replace(array('.', '#'), array('/', '.'), $class);
    if(isset($_file[$class.$basePath])){
        return true;
    }else{
        $_file[$class.$basePath] = true;
    }
    if(empty($basePath)){ /* 默认导入set_include_path路径中的文件 */
        $include_path = get_include_path();
        $paths = explode(P_S, $include_path);
        foreach($paths as $path){
            $classfile = $path.D_S.$class.$ext;
            /* 区分文件名大小写 */
            if(basename(realpath($classfile)) == basename($classfile)){
                return require_cache($classfile);
            }
        }
    }
    if(substr($basePath, -1) != '/'){
        $basePath .= '/';
    }
    $classfile = $basePath.$class.$ext;
    if($ext == '.class.php' && is_file($classfile)){
        /* 类冲突检测 */
        $class = basename($classfile, $ext);
        if(isset($_class[$class])){
            halt(L('_CLASS_CONFLICT_').' : '.$_class[$class].' '.$classfile);
        }
        $_class[$class] = $classfile;
    }
    return require_cache($classfile);
}

/** 取得对象实例 */
function get_instance($class, $classArgs = array(), $method = '', $methodArgs = array())
{
    static $_instance = array();
    /* 提取命名空间和类名称 */
    $classname = $class;
    if(strpos($class, '\\') !== false){
        list($classname, $namespace) = explode('\\', strrev($class), 2);
        $classname = strrev($classname);
        $namespace = strrev($namespace);
    }
    /* 获得完整的类名称 */
    $_classname = (isset($namespace) ? 'Vpf\\'.$namespace.'\\' : '').$classname;
    /* 计算类的身份id */
    $identify = empty($classArgs) ? $_classname : $_classname.get_guid_string($classArgs);
    
    if(!isset($_instance[$identify])){
        if(class_exists($_classname)) {
            $o = new $_classname($classArgs);
            $_instance[$identify] = &$o;
        }else{
            halt(L('_CLASS_NOT_EXIST_').' : '.$_classname);
        }
    }
    /* 处理调用方法 */
    if('' == $method){ //如果调用方法为空，则直接返回对象
        return $_instance[$identify];
    }elseif(method_exists($_instance[$identify], $method)){ //否则直接返回方法的返回值
        if(!empty($methodArgs)){
            /* 返回返回值 */
            return call_user_func_array(array(&$_instance[$identify], $method), $methodArgs);
        }else{
            /* 返回返回值 */
            return $_instance[$identify]->$method();
        }
    }else{
        halt(L('_METHOD_NOT_EXIST_').' : '.$method);
    }
}

/** 从各类型变量生成唯一标识号 */
function get_guid_string($mix)
{
    if(is_object($mix) && function_exists('spl_object_hash')){
        return spl_object_hash($mix);
    }elseif(is_resource($mix)){
        $mix = get_resource_type($mix).strval($mix);
    }else{
        $mix = serialize($mix);
    }
    return md5($mix);
}

/** 去除代码中的空白和注释 */
function strip_whitespace($content)
{
    $stripStr = '';
    $tokens = token_get_all($content); /* 分析php源码 */
    $last_space = false;
    for($i = 0, $j = count($tokens); $i < $j; $i++){
        if(is_string($tokens[$i])){
            $last_space = false;
            $stripStr .= $tokens[$i];
        }else{
            switch($tokens[$i][0]){
                /* 过滤各种PHP注释 */
                case T_COMMENT:
                case T_DOC_COMMENT:
                    break;
                /* 过滤多个空格 */
                case T_WHITESPACE:
                    if(!$last_space){
                        $stripStr .= ' ';
                        $last_space = true;
                    }
                    break;
                case T_START_HEREDOC:
                    $stripStr .= "<<<VPF\n";
                    break;
                case T_END_HEREDOC:
                    $stripStr .= "VPF;\n";
                    for($k = $i+1; $k < $j; $k++){
                        if(is_string($tokens[$k]) && $tokens[$k] == ";"){
                            $i = $k;
                            break;
                        }else if($tokens[$k][0] == T_CLOSE_TAG){
                            break;
                        }
                    }
                    break;
                default:
                    $last_space = false;
                    $stripStr .= $tokens[$i][1];
            }
        }
    }
    return $stripStr;
}

//[RUNTIME]
/** 编译文件 */
function compile($filename)
{
    $content = file_get_contents($filename);
    /* 替换预编译指令 */
    $content = preg_replace('/\/\/\[RUNTIME\](.*?)\/\/\[\/RUNTIME\]/s', '', $content);
    $content = substr(trim($content),5);
    if('?>' == substr($content, -2)){
        $content = substr($content, 0, -2);
    }
    return $content;
}

/** 根据数组生成常量定义 */
function array_define($array)
{
    $content = '';
    foreach ($array as $key => $val){
        $key = strtoupper($key);
        $content .= 'if(!defined(\''.$key.'\')) ';
        if(is_int($val) || is_float($val)){
            $content .= "define('".$key."',".$val. ");";
        }elseif(is_bool($val)){
            $val = ($val) ? 'true' : 'false';
            $content .= "define('".$key."',".$val.");";
        }elseif(is_string($val)){
            $content .= "define('".$key."','".addslashes($val)."');";
        }
    }
    return $content;
}
//[/RUNTIME]

/** 帕斯卡命名和下划线命名风格转换 0:AaaBbb -> aaa_bbb, 1:aaa_bbb -> AaaBbb */
function parse_name($name, $type = 0)
{
    if($type){
        return ucfirst(preg_replace_callback("/_([a-zA-Z])/", function($matches){return strtoupper($matches[1]);}, $name));
    }else{
        /* strtolower(preg_replace('/((?<=[a-z])(?=[A-Z]))/', '_', 'AbcasdasdadaFSDFDSFDefGhijk')); */
        $name = preg_replace("/[A-Z]/", "_\\0", $name);
        return strtolower(trim($name, "_"));
    }
}

/** 友好变量输出 */
function dump($var, $echo = true, $label = null, $strict = true)
{
    $label = ($label === null) ? '' : rtrim($label).' ';
    if(!$strict){
        if(ini_get('html_errors')){
            $output = print_r($var, true);
            $output = "\n<pre>\n".$label.htmlspecialchars($output, ENT_QUOTES)."</pre>\n";
        }else{
            $output = $label.print_r($var, true);
        }
    }else{
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        if(!extension_loaded('xdebug')){
            $output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);
            $output = "\n<pre>\n".$label.htmlspecialchars($output, ENT_QUOTES)."</pre>\n";
        }
    }
    if($echo){
        echo($output);
        return null;
    }
    return $output;
}

/** 格式化显示变量内容 */
function P()
{
    $args=func_get_args(); /* 获取多个参数 */
    if(count($args) < 1){
        echo "<font color='red'>P() must have at least one arg!</font>";
        return;
    }
    echo "<div><pre>\n";
    foreach($args as $arg){
        if(is_array($arg)){
            print_r($arg);
        }elseif(is_string($arg)){
            echo $arg."<br />\n";
        }else{
            var_dump($arg);
        }
    }
    echo "</pre></div>\n\n";
}

/** 错误终止输出 */
function halt($error)
{
    if(IS_CLI){
        exit($error);
    }
    /* 在调试模式下输出调用栈跟踪详细信息 */
    if(C('APP_DEBUG')){
        if(!is_array($error)){
            $e = array();
            $traceInfo = '';
            $trace = debug_backtrace();
            $e['message'] = $error;
            $e['file'] = $trace[1]['file'];
            $e['class'] = isset($trace[1]['class']) ? $trace[1]['class'] : '';
            $e['function'] = isset($trace[1]['function']) ? $trace[1]['function'] : '';
            $e['line'] = $trace[1]['line'];
            $time = date('c');
            foreach ($trace as $t){
                $traceInfo .= '['.$time.'] '.$t['file'].' ('.$t['line'].') ';
                $traceInfo .= isset($t['class']) ? $t['class'] : '';
                $traceInfo .= isset($t['type']) ? $t['type'] : '';
                $traceInfo .= $t['function'].'('.array_to_string($t['args']);
                $traceInfo .=")<br/>";
            }
            $e['trace'] = $traceInfo;
        }else{
            $e = $error;
        }
        if(ob_get_length()){
            /* 这句在php7下，回产生乱码，算是个BUG，等PHP官方更新 */
            ob_clean();
        }
        if(is_file(THEME_PATH.D_S.DEBUG_ERR_FILE)){
            include THEME_PATH.D_S.DEBUG_ERR_FILE;
        }elseif(is_file(C('TPL_DEBUG_ERR'))){
            include C('TPL_DEBUG_ERR');
        }else{
            P($e);
        }
        exit;
    }else{
        if(ob_get_length()){
            /* 这句在php7下，回产生乱码，算是个BUG，等PHP官方更新 */
            ob_clean();
        }
        if(is_file(THEME_PATH.D_S.ERR_FILE)){
            include THEME_PATH.D_S.ERR_FILE;
            exit;
        }elseif(is_file(C('TPL_ERR'))){
            include C('TPL_ERR');
            exit;
        }else{
            exit($error);
        }
    }
}

/** Cookie 设置 获取 清除
 * 1 设置: cookie('name', 'value') | cookie('name', 'value', 3600)
 * 2 获取: cookie('name')
 * 3 删除: cookie('name', null) | cookie(null) | cookie(null, 'vpf_')
 * 数组形式$options: cookie('name','value',array('expire'=>1,'prefix'=>COOKIE_PRE))
 * query形式$options: cookie('name','value','prefix=vpf_&expire=10000')
 */
function cookie($name, $value = '', $options = null)
{
    $config = array(
        'prefix' => C('COOKIE_PREFIX'),
        'expire' => C('COOKIE_EXPIRE'),
        'path' => C('COOKIE_PATH'),
        'domain' => C('COOKIE_DOMAIN'),
    );
    /* 参数设置(会覆盖黙认设置) */
    if(!empty($options)){
        if(is_numeric($options)){
            $options = array('expire' => $options);
        }elseif(is_string($options)){
            parse_str($options, $options);
        }
        $config = array_merge($config, array_change_key_case($options));
    }
    /* 清除指定前缀的所有cookie */
    if(is_null($name)){
        if(empty($_COOKIE)){
            return;
        }
        $prefix = empty($value) ? $config['prefix'] : $value;
        if(!empty($prefix)){ /* 前缀为空将不作处理 */
            foreach ($_COOKIE as $key => $val){
                if (0 === stripos($key, $prefix)){
                    setcookie($key, '', time() - 3600, $config['path'], $config['domain']);
                    unset($_COOKIE[$key]);
                }
            }
        }
        return;
    }
    $name = $config['prefix'] . $name;
    if ('' === $value){
        return isset($_COOKIE[$name]) ? $_COOKIE[$name] : NULL; /* 获取指定Cookie */
    }else{
        if (is_null($value)){
            setcookie($name, '', time() - 3600, $config['path'], $config['domain']);
            unset($_COOKIE[$name]); /* 删除指定cookie */
        }else{
            /* 设置cookie */
            $expire = !empty($config['expire']) ? time() + intval($config['expire']) : 0;
            setcookie($name, $value, $expire, $config['path'], $config['domain']);
            $_COOKIE[$name] = $value;
        }
    }
}

/** 检查某权限是否在权限表里，要检查的权限可以用|连接多个权限，但是权限表禁止逻辑或连接！（二进制） */
function check_privilege($oauth=0,$flag=0){
    if(is_numeric($flag) && is_numeric($oauth) && $oauth>0 && $flag>0 && ($flag == ($oauth & $flag))){
        return true;
    }else{
        return false;
    }
}

/** 检查时间间隔 记录时间interval($name), 检查间隔interval($name, $inerval) */
function interval($name = '', $interval = '')
{
    $name = 'interval_'.(empty($name) ? C('INTERVAL_NAME') : $name);
    /* interval为空, 记录当前时间 */
    if(empty($interval)){
        session_start();
        $_SESSION[$name] = time();
        session_write_close();
        return $_SESSION[$name];
    }
    /* 检查时间间隔 */
    if(!isset($_SESSION[$name])){
        session_start();
        $_SESSION[$name] = time();
        session_write_close();
    }elseif((time() - $_SESSION[$name]) < $interval){
        return false;
    }
    return true;
}

/** 字节格式化为 B KB MB GB TB */
function byte_format($size, $dec=2)
{
    $a = array("B", "KB", "MB", "GB", "TB", "PB");
    $pos = 0;
    while ($size >= 1024){
        $size /= 1024;
        $pos++;
    }
    return round($size, $dec)." ".$a[$pos];
}

/** 获取客户端IP地址 */
function get_ip()
{
    $ip=false;
    if(!empty($_SERVER['HTTP_CLIENT_IP'])){
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
        $ips = explode (',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        if($ip){
            array_unshift($ips, $ip);
            $ip = FALSE;
        }
        for($i = 0; $i < count($ips); $i++){
            if(!preg_match("^(10|172\.16|192\.168)\.", $ips[$i])){
                $ip = $ips[$i];
                break;
            }
        }
    }
    $ip = $ip ? $ip : $_SERVER['REMOTE_ADDR'];
    preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/", $ip) or $ip = 'unknown';
    return $ip;
}

/** 发送http状态 */
function send_http_status($code)
{
    static $_status = array(
        200 => 'OK', /** Success 2xx */
        /* Redirection 3xx */
        301 => 'Moved Permanently',
        302 => 'Moved Temporarily ',  /* 1.1 */
        /* Client Error 4xx */
        400 => 'Bad Request',
        403 => 'Forbidden',
        404 => 'Not Found',
        /* Server Error 5xx */
        500 => 'Internal Server Error',
        503 => 'Service Unavailable',
    );
    if(isset($_status[$code])){
        header('HTTP/1.1 '.$code.' '.$_status[$code]);
        header('Status:'.$code.' '.$_status[$code]); /* 确保FastCGI模式下正常 */
    }
}

/** URL重定向 */
function redirect($url, $time = 0, $msg = '')
{
    $url = str_replace(array("\n", "\r"), '', $url); /* 多行URL地址支持 */
    if(empty($msg)){
        $msg = "系统将在 {$time} 秒之后自动跳转到 {$url} !";
    }
    if(!headers_sent()){
        if(0 === $time){
            header("Location: ".$url);
        }else{
            header("refresh: {$time}; url = {$url}");
            echo($msg);
        }
        exit();
    }else{
        $str = "<meta http-equiv='Refresh' content='{$time}; URL={$url}'>";
        if(0 != $time){
            $str .= $msg;
        }
        exit($str);
    }
}

/** 截取字符 */
function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = false)
{
    if(function_exists("mb_substr")){
        if($suffix && strlen($str) > $length){
            return mb_substr($str, $start, $length, $charset)."...";
        }else{
            return mb_substr($str, $start, $length, $charset);
        }
    }elseif(function_exists('iconv_substr')){
        if ($suffix && strlen($str) > $length){
            return iconv_substr($str, $start, $length, $charset)."...";
        }else{
            return iconv_substr($str, $start, $length, $charset);
        }
    }
    $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
    $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
    $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
    $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
    preg_match_all($re[$charset], $str, $match);
    $slice = join("", array_slice($match[0], $start, $length));
    if($suffix){
        return $slice."…";
    }
    return $slice;
}

/** 获取字符长度 */
function mstrlen($string, $charset = 'UTF-8')
{
    if(function_exists('mb_strlen')){
        return mb_strlen($string, $charset);
    }elseif(function_exists('iconv_strlen')){
        return iconv_strlen($string, $charset);
    }
    return 0;
}

/** 自动转换字符集 支持数组转换 */
function auto_charset($fContents, $from = 'gbk', $to = 'utf-8')
{
    $from = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
    $to = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;
    if(strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents))){
        return $fContents; /* 如果编码相同或者非字符串标量则不转换 */
    }
    if (is_string($fContents)){
        if (function_exists('mb_convert_encoding')){
            return mb_convert_encoding($fContents, $to, $from);
        }elseif (function_exists('iconv')){
            return iconv($from, $to, $fContents);
        }else{
            return $fContents;
        }
    }elseif(is_array($fContents)){
        foreach($fContents as $key => $val){
            $_key = auto_charset($key, $from, $to);
            $fContents[$_key] = auto_charset($val, $from, $to);
            if ($key != $_key){
                unset($fContents[$key]);
            }
        }
        return $fContents;
    }else{
        return $fContents;
    }
}

/** 数组转为php可解析的字符串 $level:解析深度 */
function array_to_string($array, $glue = ', ', $level = 1)
{
    if(!is_array($array) || empty($array)){
        return '';
    }
    $string = '';
    foreach($array as $k => $v){
        if(0 != $level){
            if(is_array($v)){
                $string .= 'array(';
                $string .= array_to_string($v, $glue, $level - 1).$glue;
                $string = rtrim($string, $glue).')'.$glue;
            }else{
                $string .= $v.$glue;
            }
        }
    }
    $string = rtrim($string, $glue);
    return $string;
}

/** 隐藏ip后两位 */
function hide_ip($ip)
{
    $pattern = "/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/i";
    $replacement = "\$1.\$2.*.*";
    $ip = preg_replace($pattern, $replacement, $ip);
    return $ip;
}

/** 友好时间显示 */
function get_dateStr($dateline)
{
    $timeDif = intval(time() - $dateline);
    $now = getdate();
    $todayPassTime = $now['seconds'] + $now['minutes'] * 60 + $now['hours'] * 60 * 60;

    if ($timeDif < 0){
        return L('IN_THE_FUTURE');
    }elseif ($timeDif < 60){
        $timeDif = $timeDif == 0 ? 1 : $timeDif;
        return $timeDif.L('SECOND_AGO');
    }elseif ($timeDif < 3600){
        $timeDif = intval($timeDif / 60);
        return $timeDif.L('MINUTE_AGO');
    }elseif ($timeDif < $todayPassTime){
        $timeDif = intval($timeDif / 3600);
        return $timeDif.L('HOUR_AGO');
    }elseif ($timeDif < $todayPassTime + 3600 * 24){
        return L('YESTERDAY').date(' H:i:s', $dateline);
    }elseif ($timeDif < 3600 * 24 * 7){
        $timeDif = intval($timeDif / 3600 / 24);
        return $timeDif.L('DAY_AGO');
    }
    return date('Y-m-d H:i:s', $dateline);
}

/** 过滤路径中敏感字符和重复的分割符，只保留a-zA-Z0-9和分隔符 */
function filter_path($str, $depr = '/'){
	if(empty($depr)){ //如果分隔符为空，则只保留数字和字母
		return preg_replace('/[^a-zA-Z0-9]/us', '', $str);
	}else{ //否则滤掉分隔符之外的其他字符，然后把连续的分隔符变成1个
		$str = preg_replace('/[^a-zA-Z0-9\\'.$depr.']/us', '', $str);
		return preg_replace('/(\\'.$depr.')\1+/u','$1', $str);
	}
	
}

/**
* XML编码
* @param mixed $data 数据
* @param string $root 根节点名
* @param string $item 数字索引的子节点名
* @param string $attr 根节点属性
* @param string $id 数字索引子节点key转换的属性名
* @param string $encoding 数据编码
* @return string
*/
function xml_encode($data, $root='vpf', $item='item', $attr='', $id='id', $encoding='utf-8') {
    if(is_array($attr)){
        $_attr = array();
        foreach ($attr as $key => $value) {
            $_attr[] = "{$key}={$value}";
        }
        $attr = implode(' ', $_attr);
    }
    $attr = trim($attr);
    $attr = empty($attr) ? " {$attr}" : '';
    $xml = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
    $xml .= "<{$root}{$attr}>";
    $xml .= data_to_xml($data, $item, $id);
    $xml .= "</{$root}>";
    return $xml;
}

/**
* 数据XML编码
* @param mixed $data 数据
* @param string $item 数字索引时的节点名称
* @param string $id 数字索引key转换为的属性名
* @return string
*/
function data_to_xml($data, $item='item', $id='id') {
    $xml = $attr = '';
    foreach ($data as $key => $val) {
        if(is_numeric($key)){
            $id && $attr = " {$id}=\"{$key}\"";
            $key = $item;
        }
        $xml .= "<{$key}{$attr}>";
        $xml .= (is_array($val) || is_object($val)) ? data_to_xml($val, $item, $id) : $val;
        $xml .= "</{$key}>";
    }
    return $xml;
}
?>