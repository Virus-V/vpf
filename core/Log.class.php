<?php
/**
 *--------------------------------------------------
 * VPF 日志类
 *--------------------------------------------------
 * @program     : VPF
 * @create      : 2012-3-2 22:11:50
 * @description :
 */
namespace Vpf;

class Log extends Vpf {
    public static $log = array();
    public static $format = '[c]';

    /** 记录日志 */
    public static function record($message){
        $now = date(self::$format);
        self::$log[] = "<?php exit; ?>{$now} | ".(IS_CLI ? 'CLI' : "URI:".$_SERVER['REQUEST_URI'])." | {$message}\r\n";
    }

    /** 日志保存 参考error_log() */
    public static function save($type = 3, $destination = '', $extra = ''){
        if(empty($destination)){
            $destination = LOG_PATH.D_S.date('y_m_d').".php";
        }
        if(3 == $type){
            /* 日志超过配置大小时备份 */
            if(is_file($destination) && floor(C('LOG_FILE_SIZE')) <= filesize($destination)){
                rename($destination, dirname($destination).D_S.time().'-'.basename($destination));
            }
        }
        if(!empty(self::$log)){
            error_log(implode('', self::$log), $type, $destination , $extra);
            self::$log = array();
            clearstatcache(); /* 保存后清空日志缓存 */
        }
    }

    /** 日志直接写入 参考error_log() */
    public static function write($message, $type = 3, $destination = '', $extra = ''){
        $now = date(self::$format);
        if(empty($destination)){
            $destination = LOG_PATH.D_S.date('y_m_d').".php";
        }
        if(3 == $type){
            /* 日志超过配置大小时备份 */
            if(is_file($destination) && floor(C('LOG_FILE_SIZE')) <= filesize($destination)){
                rename($destination,dirname($destination).D_S.time().'-'.basename($destination));
            }
        }
        error_log("<?php exit; ?>{$now} | URI:".$_SERVER['REQUEST_URI']." | {$message}\r\n", $type, $destination, $extra);
        clearstatcache(); /* 保存后清空日志缓存 */
    }

    /** 构造函数私有化 */
    private function __construct(){}
}
?>