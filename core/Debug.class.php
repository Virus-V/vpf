<?php
/**
 *--------------------------------------------------
 * VPF 调试类
 *--------------------------------------------------
 * @program     : VPF
 * @create      : 2012-3-3 19:32:23
 * @description :
 */
namespace Vpf;

class Debug extends Vpf
{
    private static $marker =  array();
    public static $trace = array();

    /** 标记调试位置 */
    public static function mark($name){
        self::$marker['time'][$name] = microtime(true);
        if(DEBUG_MEMORY){
            self::$marker['mem'][$name] = memory_get_usage();
            self::$marker['peak'][$name] = function_exists('memory_get_peak_usage') ? memory_get_peak_usage() : self::$marker['mem'][$name];
        }
    }

    /** $start 到 $end 时间差 */
    public static function get_time_use($start, $end, $decimals = 6){
        if(!isset(self::$marker['time'][$start])){
            return '';
        }
        if(!isset(self::$marker['time'][$end])){
            self::$marker['time'][$end] = microtime(true);
        }
        return number_format(self::$marker['time'][$end] - self::$marker['time'][$start], $decimals);
    }

    /** $start 到 $end 内存差 */
    public static function get_mem_use($start, $end){
        if(!DEBUG_MEMORY){
            return '';
        }
        if (!isset(self::$marker['mem'][$start])){
            return '';
        }
        if (!isset(self::$marker['mem'][$end])){
            self::$marker['mem'][$end] = memory_get_usage();
        }
        return byte_format((self::$marker['mem'][$end] - self::$marker['mem'][$start]));
    }

    /** $start 到 $end内存峰值 */
    public static function get_mem_peak($start, $end){
        if(!DEBUG_MEMORY){
            return '';
        }
        if(!isset(self::$marker['peak'][$start])){
            return '';
        }
        if(!isset(self::$marker['peak'][$end])){
            self::$marker['peak'][$end] = function_exists('memory_get_peak_usage') ? memory_get_peak_usage() : memory_get_usage();
        }
        return byte_format(max(self::$marker['peak'][$start], self::$marker['peak'][$end]));
    }

    /** 写入页面跟踪信息 */
    public static function trace($title, $value = ''){
        if(is_array($title)){
            self::$trace = array_merge(self::$trace, $title);
        }
        else
        {
            self::$trace[$title] = $value;
        }
    }

    /** 显示运行时间, 数据库操作, 缓存次数, 内存使用信息 */
    public static function show_time(){
        $showTime = '<b>'.L('_TIME_USAGE_').'</b>: '.G('beginTime', 'endTime').'s'; /* 显示运行时间 */
        if(C('SHOW_ADV_TIME')){ /* 显示详细运行时间 */
            $showTime .= '(';
            $showTime .= L('_LOAD_TIME_').': '.G('beginTime', 'loadTime').'s, ';
            $showTime .= L('_INIT_TIME_').': '.G('loadTime','initTime').'s, ';
            $showTime .= L('_EXEC_TIME_').': '.G('initTime','execTime').'s';
            $showTime .= ')';
        }
        if(C('SHOW_DB_TIMES') && class_exists('\Lib\\Db', false)){ /* 显示数据库操作次数 */
            $showTime .= ' | <b>'.L('_DB_TIMES_').'</b>: '.N('db_query').L('_DB_QUERY_').' '.N('db_write').L('_DB_WRITE_');
        }
        if(C('SHOW_CACHE_TIMES') && class_exists('\Lib\\Cache', false)){ /* 显示缓存读写次数 */
            $showTime .= ' | <b>'.L('_CACHE_TIMES_').'</b>: '.N('cache_read').L('_CACHE_GET_').' '.N('cache_write').L('_CACHE_WRITE_');
        }
        if(MEMORY_ANALYSE && C('SHOW_USE_MEM')){ /* 显示内存开销 */
            $showTime .= ' | <b>'.L('_MEMORY_USAGE_').'</b>: '. byte_format((memory_get_usage() - $GLOBALS['_startUseMems']));
        }
        echo "\n".'<div id="vpf_run_time" style="padding:10px; margin: 10px; color:#666; background:#fff; border:1px solid #ddd;  font-size: 12px; line-height: 18px;">'.$showTime.'</div>';;
    }

    /** 显示页面跟踪信息 */
    public static function show_trace(){
        $_trace = array();
        /* 默认信息 */
        self::trace(L('_CURRENT_PAGE_'), $_SERVER['REQUEST_URI']);
        self::trace(L('_REQUEST_METHOD_'), $_SERVER['REQUEST_METHOD']);
        self::trace(L('_SERVER_PROTOCOL_'), $_SERVER['SERVER_PROTOCOL']);
        self::trace(L('_REQUEST_TIME_'), date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']));
        self::trace(L('_USER_AGENT_'), $_SERVER['HTTP_USER_AGENT']);
        self::trace(L('_SESSION_ID_'), session_id());
        $log = Log::$log;
        self::trace(L('_LOG_RECORD_'), count($log) ? count($log)."<br />\n".implode("<br/>\n",$log) : L('_NONE_'));
        $files =  get_included_files();
        self::trace(L('_INCLUDE_FILES_'), count($files).str_replace("\n", "<br />\n", substr(substr(print_r($files, true), 7), 0, -2)));
        $_trace = array_merge($_trace, self::$trace);
        include C('TPL_PAGE_TRACE');
    }

    /** 构造函数私有化 */
    private function __construct(){}
}
?>