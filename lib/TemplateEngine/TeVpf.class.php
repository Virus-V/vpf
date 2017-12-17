<?php
/**
 *--------------------------------------------------
 * VPF 模板引擎 (Template Engine VPF)
 *--------------------------------------------------
 * @program     : VPF
 * @create      : 2015-4-9 17:18
 * @description :
 */
namespace Vpf\Lib\Template;
use Vpf,Vpf\Lib;

class TeVpf extends Lib\Te
{
    public $tplVars = array(); /* 模板变量 */
    private $tplFile = ''; /* 当前模板文件名 */
    private $cacheFile = ''; /* 当前缓存文件 */
    public $stripSpace = true; /* 是否去除模板中空格 */
    public $markL = '<{'; /* 左边界符号 */
    public $markR = '}>'; /* 右边界符号 */
    public $tplPath = ''; /* 模板文件目录路径 */
    public $tplTheme = 'default'; /* 模板主题 */
    public $tplSuffix = '.php'; /* 模板文件后缀名 */
    public $tplAvoidDown = true; /* 模板文件禁止下载 */
    public $tplAvoidDownPrefix = '<?php /* tevpf.tpl'; /* 模板内容前缀 */
    public $tplAvoidDownSuffix = '*/ ?>'; /* 模板内容后缀 */
    public $tplContentType = 'text/html'; /* 默认输出类型 */
    public $cache = true; /* 是否开启缓存 */
    public $cacheTime = 0; /* 缓存时间 0不自动更新 */
    public $cachePath = ''; /* 缓存文件路径 */
    public $cacheSuffix = '.php'; /* 缓存文件后缀 */

    public function __construct($options = ''){
        $this->options = array(
            'teType' => Vpf\C('TE_TYPE'),
            'stripSpace' => Vpf\C('TE_STRIP_SPACE'),
            'markL' => Vpf\C('TE_MARK_L'),
            'markR' => Vpf\C('TE_MARK_R'),
            'tplPath' => Vpf\C('TE_TPL_PATH'),
            'tplTheme' => Vpf\C('TE_TPL_THEME'),
            'tplSuffix' => Vpf\C('TE_TPL_SUFFIX'),
            'tplAvoidDown' => Vpf\C('TE_TPL_AVOID_DOWN'), 
            'tplAvoidDownPrefix' => Vpf\C('TE_TPL_AVOID_DOWN_PREFIX'),
            'tplAvoidDownSuffix' => Vpf\C('TE_TPL_AVOID_DOWN_SUFFIX'),
            'cache' => Vpf\C('TE_CACHE'),
            'cacheTime' => Vpf\C('TE_CACHE_TIME'),
            'cachePath' => Vpf\C('TE_CACHE_PATH'),
            'cacheSuffix' => Vpf\C('TE_CACHE_SUFFIX'),
        );
        if(!empty($options)){
            $this->options = array_merge($this->options, $options);
        }
        if(is_array($this->options)){
            foreach($this->options as $key => $value){
                $this->$key = $value;
            }
        }
    }

    /** 赋值模板变量 */
    /*
    assign(array('a'=>'b'));
    assign(object);
    assign('a',$b);
    */
    public function assign($name, $value = ''){
        if(is_array($name)){
            $this->tplVars = array_merge($this->tplVars, $name);
        }elseif(is_object($name)){
            foreach($name as $key => $val){
                $this->tplVars[$key] = $val;
            }
        }else{
            $this->tplVars[$name] = $value;
        }
    }

    /** 显示模板 */
    public function display($file, $data = array()){
        $this->fetch($file, $data, true);
    }

    /** 获取模板内容 */
    public function fetch($file, $data = array(), $display = false){
        $this->tplVars = array_merge($this->tplVars, $data);
        $this->get_fileName($file);
        if(!$this->check_cache()){
            $this->build_cache();
        }
        /* 模板变量数组导为变量 */
        extract($this->tplVars, EXTR_OVERWRITE);
        ob_start();
        ob_implicit_flush(0);
        //echo App\ada();
        /* 载入模版缓存文件 */
        include($this->cacheFile);
        $content = ob_get_clean();
        if(Vpf\C('HTML_CACHE')){
            Lib\HtmlCache::write_htmlCache($content);
        }
        if(!$display){
            return $content;
        }else{
            echo $content;
        }
    }

    /** 获取模板及缓存文件完整路径 */
    private function get_fileName($tplfile = ''){
        if('' == $tplfile){
            $tplfile = Vpf\C('TPL_FILE_NAME'); /* 默认显示 THEME_PATH/CTRL_NAME/ACTN_NAME.html */
        }
        $this->tplPath = rtrim($this->tplPath, "/\\").D_S;
        $this->cachePath = rtrim($this->cachePath, "/\\").D_S;
        /* 检查tplfile这个路径是否是文件，如果不是文件则组装 */
        if(!is_file($tplfile)){
            $file = str_replace($this->tplSuffix, '', $tplfile).$this->tplSuffix;
            $tplfile = $this->tplPath.$this->tplTheme.D_S.$file;
            /* 组装后如果没有此文件则报错 */
            if(!is_file($tplfile)){
                Vpf\halt(Vpf\L('_TPL_FILE_NOT_EXIST_').':'.$tplfile);
            }
        }
        $this->tplFile = $tplfile;
        $this->cacheFile = $this->cachePath.md5($tplfile).$this->cacheSuffix;
    }

    /** 验证缓存是否有效 */
    public function check_cache(){
        if(!file_exists($this->cacheFile) || !$this->cache){
            return false;
        }
        if(filemtime($this->cacheFile) < filemtime($this->tplFile)){
            return false;
        }
        if(filemtime($this->cacheFile) + $this->cacheTime < time() && 0 != $this->cacheTime){
            return false;
        }
        return true;
    }

    /** 获取模板文件内容 */
    private function get_tplContent($tplFile){
        $content = file_get_contents($tplFile);
        if($this->tplAvoidDown){
            if($this->tplAvoidDownPrefix == substr($content, 0, strlen($this->tplAvoidDownPrefix))){ /* 已经加入前后缀 */
                $content = substr($content, strlen($this->tplAvoidDownPrefix), -strlen($this->tplAvoidDownSuffix));
            }else{
                $contentNew = $this->tplAvoidDownPrefix.$content.$this->tplAvoidDownSuffix;
                file_put_contents($tplFile, $contentNew);
            }
        }
        return $content;
    }
    
    private function parse_include($content){
        /* 处理include */
        preg_match_all($this->markL.'include(.*)'.$this->markR, $content, $include);
        $include = $include[1];
        if(!empty($include)){
            foreach($include as $include){
                $include = trim($include);
                $file = $this->tplPath.D_S.$this->tplTheme.D_S.$include;
                if(file_exists($file)){
                    $includeContent = $this->get_tplContent($file);
                    /* 递归开始 */
                    $includeContent = $this->parse_include($includeContent);
                    $content = str_replace($this->markL.'include '.$include.$this->markR, $includeContent, $content);
                }
            }
        }
        return $content;
    }

    /** 缓存模板文件 */
    public function build_cache(){
        $content = $this->get_tplContent($this->tplFile);
        /* 解析模板文件里的include */
        $content = $this->parse_include($content);
        $patt = $this->markL.'(\S.+?)'.$this->markR;
        $content = preg_replace_callback("/{$patt}/is", array('self','parse_tag'), $content);
        /* 开启短标签的情况要将<?标签用echo方式输出 否则无法正常输出xml标识 */
        if(ini_get('short_open_tag')){ 
            $content = preg_replace('/(<\?(?!php|=|$))/i', '<?php echo \'\\1\'; ?>'."\n", $content);
        }
        $str = "<?php \r\n/* VPF 模板缓存文件 生成时间:".date('Y-m-d H:i:s', time())." */ \r\nnamespace Vpf\\App\\Cache;\r\nuse Vpf,Vpf\\App;?>\r\n";
        /* 去除html空格与换行 */
        if($this->stripSpace){
            $find = array("~>\s+<~","~>(\s+\n|\r)~");
            $replace = array("><",">");
            $content = preg_replace($find, $replace, $content);
        }
        $content = $str.$content;
        return file_put_contents($this->cacheFile, $content);
    }

    /** 解析<{}>中的内容 根据第一个字符决定使用什么函数进行解析 */
    private function parse_tag($label){
        $label = is_array($label) ? $label[1] : $label;
        $label = stripslashes(trim($label));
        $flags = array(
            'var' => '$',
            'language' => '@',
            'config' => '#',
            'cookie' => '+',
            'session' => '-',
            'get' => '%',
            'post' => '&',
            'constant' => '*');
        $flag = substr($label, 0, 1);
        $name = substr($label, 1);
        /* 普通变量 */
        if($flag == $flags['var']){
            return !empty($name) ? $this->parse_var($name) : null;
        }
        /* 输出语言 */
        if($flag == $flags['language']){
            return "<?php echo(Vpf\L('{$name}')); ?>";
        }
        /* 输出配置信息 */
        if($flag == $flags['config']){
            return "<?php echo(Vpf\C('{$name}')); ?>";
        }
        /* 输出Cookie */
        if($flag == $flags['cookie']){
            return "<?php echo(Vpf\cookie('{$name}')); ?>";
        }
        /* 输出SESSION */
        if($flag == $flags['session']){
            return "<?php echo(\$_SESSION['{$name}']); ?>";
        }
        /* 输出GET */
        if($flag == $flags['get']){
            return "<?php echo(\$_GET['{$name}']); ?>";
        }
        /* 输出POST */
        if($flag == $flags['post']){
            return "<?php echo(\$_POST['{$name}']); ?>";
        }
        /* 输出常量 */
        if($flag == $flags['constant']){
            return "<?php echo({$name}); ?>";
        }
        /* 语句结束部分 */
        if($flag == '/'){
            /* foreach结束 */
            if('foreach' == $name){
                return "<?php endforeach; endif; ?>";
            }
            return "<?php end{$name}; ?>";
        }
        /* 导入js或者css */
        if('import' == substr($label, 0, 6)){
            return $this->parse_import(substr($label, 6));
        }
        /* foreach 开始 */
        if('foreach' == substr($label, 0, 7)){
            preg_match_all('/\\$([\S]+)/', $label, $arr);
            $arr = $arr[1];
            if(count($arr) > 2){
                return "<?php if(is_array(\${$arr[0]})) : foreach(\${$arr[0]} as \${$arr[1]} => \${$arr[2]}) : ?>";
            }elseif(count($arr) > 0){
                return "<?php if(is_array(\${$arr[0]})) : foreach(\${$arr[0]} as \${$arr[1]}) : ?>";
            }
        }
        /* if elseif */
        if('if' == substr($label, 0, 2) || 'elseif' == substr($label, 0, 6)){
            $arr = explode(' ', $label);
            array_shift($arr);
            $param = array();
            foreach($arr as $v){
                if(strpos($v, '.') > 0){
                    $args = explode('.', $v);
                    $p = $this->array_handler($args, 1);
                    $param[] = $args[0].$p;
                }else{
                    $param[] = $v;
                }
            }
            $str = join(' ', $param);
            $tag = substr($label, 0, 2) == 'if' ? 'if' : 'elseif';
            return "<?php {$tag}({$str}) :  ?>";
        }
        /* else */
        if(substr('else' == $label, 0, 4)){
            return "<?php else : ?>";
        }
        return trim($this->markL, '\\').$label.trim($this->markR, '\\');
    }

    /** 解析变量 $varStr 变量字符串 */
    private function parse_var($varStr){
        static $tVars = array();
        /* 变量|函数参数 */
        $varArray = explode('|', $varStr);
        /* 弹出第一个元素(变量名) */
        $var = array_shift($varArray);
        /* $var.xxx 方式访问数组或属性 */
        if(strpos($var, '.')){
            $vars = explode('.', $var);
            $name = 'is_array($'.$vars[0].') ? $'.$vars[0].'["'.$vars[1].'"] : $'.$vars[0].'->'.$vars[1];
            $var = $vars[0];
        }elseif(strpos($var, '[')){ /* $var['xxx'] 方式访问数组 */
            $name = "$".$var;
            preg_match('/(.+?)\[(.+?)\]/is', $var, $arr);
            $var = $arr[1];
        }else{
            $name = "$$var";
        }
        /* 如果有使用函数 */
        if(count($varArray) > 0){
            /* 传入变量名, 和函数参数继续解析, 这里的变量名是上面的判断设置的值 */
            $name = $this->parse_function($name, $varArray);
        }
        $code = !empty($name) ? "<?php echo({$name}); ?>" : '';
        $tVars[$varStr] = $code;
        return $code;
    }

    /** 解析函数 $varStr:变量名称, $varArray:函数名称及参数 */
    private function parse_function($varStr, $varArray){
        /*多个函数，弹出第一个，只解析第一个*/
        $varArray = array_shift($varArray);
        /* 获取不允许使用的函数 */
        $not = explode(',', Vpf\C('TE_TPL_DENY_FUNC'));
        /* 以:分割函数参数, 第一个元素就是函数名, 之后的都是参数 */
        $arr = explode(':', $varArray,2); 
        $funcName = array_shift($arr);
        $funcArgs = array_shift($arr);
        /* 函数名如果不在不允许使用的函数中 */
        if(!in_array($funcName, $not)){
            $args = explode(',', $funcArgs);
            $p = array();
            foreach($args as $var){
                $var = trim($var);
                if($var == 'THIS'){
                    $var = $varStr;
                }
                $p[] = $var;
            }
            $param = implode(", ", $p);
            $code = "{$funcName}($param)";
            return $code;
        }else{
            return 'NOT ALLOW functions was called!';
        }
    }

    /** 解析import标签 */
    private function parse_import($label){
        $param = array(); /* 参数数组 */
        $arr = explode(' ', $label);
        foreach($arr as $v){
            if(strpos($v, '=') > 0){
                $args = explode('=', $v);
                $param[$args[0]] = trim($args[1], '"');
            }
        }

        $fileType = !empty($param['type']) ? strtolower($param['type']) : 'js';

        $param['file'] = str_replace(array('.', '#'), array('/', '.'), $param['file']);
        $files = explode(',', $param['file']);

        $fileName = '~'.str_replace(array(',', '/'), array('-', '.'), $param['file']).'.'.$fileType;

        $basePath = preg_replace_callback('/\((.*)\)/', array('self', 'parse_teConstant'), $param['basepath']);
        $basePath = rtrim($basePath, '/\\').D_S;

        $baseUrl = preg_replace_callback('/\((.*)\)/', array('self', 'parse_teConstant'), $param['baseurl']);
        $baseUrl = rtrim($baseUrl, '/\\').'/';

        $fileUrl = $baseUrl.$fileName;

        if(!file_exists($basePath.$fileName)){ /* 创建临时文件 */
            $fileContent = '';
            foreach($files as $file){
                if(file_exists($basePath.$file.'.'.$fileType)){
                    $fileContent .= file_get_contents($basePath.$file.'.'.$fileType);
                }else{
                    $fileContent .= $basePath.$file;
                }
            }
            file_put_contents($basePath.$fileName, $fileContent);
        }

        switch($fileType){
            case 'js':
                $str = '<script type="text/javascript" src="'.$fileUrl.'"></script>';
                break;
            case 'css':
                $str = '<link rel="stylesheet" type="text/css" href="'.$fileUrl.'" />';
                break;
        }

        return "<?php echo('{$str}') ?>";
    }

    /** 解析import标签中的常量 */
    private static function parse_teConstant($matches){
        /* $matches[1] 是第一个括号中的子模式的匹配项 */
        if(defined($matches[1])){
            return constant($matches[1]);
        }
        return null;
    }

    /** 构造数组下标 */
    private function array_handler(&$arr, $go = 2){
        $param = '';
        $len = count($arr);
        for($i = $go; $i < $len; $i++){
            $param .= "['{$arr[$i]}']";
        }
        return $param;
    }
}
?>