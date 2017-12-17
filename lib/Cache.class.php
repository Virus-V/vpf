<?php
/**
 *--------------------------------------------------
 * VPF 缓存管理
 *--------------------------------------------------
 * @program     : VPF
 * @create      : 2012-3-11 13:55:39
 * @description :
 */
namespace Vpf\Lib;
use Vpf;

class Cache extends Vpf\Vpf
{
    /* 已实例化的缓存类 */
    public static $_cacheObj = array();
    
    protected $handler; /* 操作句柄 */
    protected $connected; /* 是否连接 */
    protected $options = array(); /* 缓存连接参数 */

    /** 连接缓存 $options:配置数组 */
    public static function connect($options = array()){
        if(!isset($options['cacheType'])){
            $options['cacheType'] = Vpf\C('DATA_CACHE_TYPE');
        }
        $cacheClass = 'Cache'.ucwords(strtolower($options['cacheType']));
        if(isset(self::$_cacheObj[$cacheClass])){
            $cache = self::$_cacheObj[$cacheClass];
        }else{
            Vpf\import('lib.CacheEngine.'.$cacheClass, VPF_PATH);
            $cache = Vpf\get_instance('Lib\\Cache\\'.$cacheClass, $options);
            self::$_cacheObj[$cacheClass] = $cache;
        }
        return $cache;
    }

    public function __get($name){
        return $this->get($name);
    }

    public function __set($name, $value){
        return $this->set($name, $value);
    }

    public function __unset($name){
        return $this->del($name);
    }

    public function set_option($name, $value){
        $this->options[$name] = $value;
    }

    public function get_option($name){
        return $this->options[$name];
    }
}
?>