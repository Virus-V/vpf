<?php
/**
 *--------------------------------------------------
 * VPF 静态缓存
 *--------------------------------------------------
 * @program     : VPF
 * @create      : 2012-3-17 21:25:48
 * @description :
 */
namespace Vpf\Lib;
use Vpf;

class HtmlCache extends Vpf\Vpf
{
    private static $cacheTime = null; /* 缓存有效期 */
    private static $requireCache = false; /* 是否需要缓存 */

    /* 判断是否需要静态缓存,并定义HTML_FILE_NAME */
    private static function require_htmlCache(){
        $htmls = Vpf\C('htmls'); /* 读取静态规则 */
        if(!empty($htmls)){
            // 静态规则文件定义格式 actionName=>array('静态规则','缓存时间','附加规则')
            // 'read'=>array('{id},{name}',60,'md5') 必须保证静态规则的唯一性 和 可判断性
            // 检测静态规则
            $ctrlName = ucfirst(CTRL_NAME);
            if(isset($htmls[$ctrlName.':'.ACTN_NAME])){
                $html = $htmls[$ctrlName.':'.ACTN_NAME]; /* 某个控制器的操作的静态规则 */
            }elseif(isset($htmls[$ctrlName.':'])){
                $html = $htmls[$ctrlName.':']; /*某个控制器的静态规则 */
            }elseif(isset($htmls[ACTN_NAME])){
                $html = $htmls[ACTN_NAME]; /* 所有操作的静态规则 */
            }elseif(isset($htmls['*'])){
                $html = $htmls['*']; /* 全局静态规则 */
            }
            if(!empty($html)){
                self::$requireCache = true; /* 需要缓存 */
                /* 解读静态规则 */
                $rule = $html[0];
                /* 以$_开头的系统变量 */
                /* {$_VAR.VAR|FUN} => FUN($_VAR[VAR]) */
                $rule = preg_replace_callback('/{\$(_\w+)\.(\w+)\|(\w+)}/', function($matches){return $matches[3](${$matches[1]}[$matches[2]]);}, $rule);
                /* {$_VAR.VAR} => $_VAR[VAR] */
                $rule = preg_replace_callback('/{\$(_\w+)\.(\w+)}/', function($matches){return ${$matches[1]}[$matches[2]];}, $rule);
                /* {ID|FUN} GET变量的简写，FUN($_GET[ID]) */
                $rule = preg_replace_callback('/{(\w+)\|(\w+)}/', function($matches){return $matches[2]($_GET[$matches[1]]);}, $rule);
                /* {ID} GET变量的简写，$_GET[ID] */
                $rule = preg_replace_callback('/{(\w+)}/', function($matches){return $_GET[$matches[1]];}, $rule);
                /* 特殊系统变量 */
                $rule = str_ireplace(
                    array('{:app}', '{:ctrl}', '{:actn}'),
                    array(APP_NAME, CTRL_NAME, ACTN_NAME),
                    $rule);
                /* {|FUN} 单独使用函数 */
                $rule = preg_replace_callback('/{\|(\w+)}/', function($matches){return $matches[1]();}, $rule);
                
                if(!empty($html[2])){
                    $rule = $html[2]($rule); /* 应用附加函数 */
                }
                self::$cacheTime = isset($html[1]) ? $html[1] : Vpf\C('HTML_CACHE_TIME'); /* 设置缓存有效期 */
                define('HTML_FILE_NAME', HTML_PATH.D_S.$rule.Vpf\C('HTML_FILE_SUFFIX')); /* 当前缓存文件 */
                return true;
            }
        }
        return false; /* 无需缓存 */
    }

    /** 读取静态缓存 */
    public static function read_htmlCache(){
        if(self::require_htmlCache() && self::check_htmlCache(HTML_FILE_NAME, self::$cacheTime)){ /* 需要读取且静态页面有效 */
            if(1 == Vpf\C('HTML_READ_TYPE')){
                /* 重定向到静态页面 */
                Vpf\redirect(str_replace(
                    array(realpath($_SERVER["DOCUMENT_ROOT"]), "\\"),
                    array('',"/"),
                    realpath(HTML_FILE_NAME)
                    )
                );
            }else{
                /* 读取静态页面输出 */
                readfile(HTML_FILE_NAME);
                exit();
            }
        }
        return;
    }

    /** 写入静态缓存 */
    public static function write_htmlCache($content){
        if(self::$requireCache){
            if(!is_dir(dirname(HTML_FILE_NAME))){
                Vpf\mk_dir(dirname(HTML_FILE_NAME));
            }
            if(false === file_put_contents(HTML_FILE_NAME, $content)){
                Vpf\halt(Vpf\L('_CACHE_WRITE_ERROR_').':'.HTML_FILE_NAME);
            }
        }
        return;
    }

    /** 删除静态缓存 */
    public static function delete_htmlCache(){
        return @unlink(HTML_FILE_NAME);
    }

    /** 检查静态HTML文件是否有效 */
    public static function check_htmlCache($cacheFile = '', $cacheTime = ''){
        if(!is_file($cacheFile)){
            return false;
        }elseif(is_file(Vpf\C('TPL_FILE_NAME')) && filemtime(Vpf\C('TPL_FILE_NAME')) > filemtime($cacheFile)){
            return false; /* 检查模板文件是否更改 */
        }elseif($cacheTime != -1 && time() > filemtime($cacheFile) + $cacheTime){
            return false; /* 文件是否在有效期 */
        }
        return true; /* 静态文件有效 */
    }
}
?>