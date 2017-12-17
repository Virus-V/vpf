<?php
/**
 *--------------------------------------------------
 * VPF 缓存到memcache
 *--------------------------------------------------
 * @program     : VPF
 * @create      : 2012-3-11 15:23:54
 * @description :
 */
namespace Vpf\Lib\Cache;
use Vpf,Vpf\Lib,Vpf\Interfaces;

class CacheMemcache extends Lib\Cache implements Interfaces\CacheInterface
{
    /** 构造函数 */
    function __construct($options = array())
    {
        if(!extension_loaded('memcache'))
        {
            Vpf\halt(Vpf\L('_NOT_SUPPERT_').':memcache');
        }
        /* 加载默认配置 */
        $this->options = Vpf\C('DATA_CACHE_MEMCACHE_CFG');
        $this->options['expire'] = is_null($this->options['expire']) ? Vpf\C('DATA_CACHE_EXPIRE') : $this->options['expire'];
        
        if(!empty($options))
        {
            $this->options = array_merge($this->options, $options);
        }
        $func = $options['persistent'] ? 'pconnect' : 'connect';
        $this->handler = new Memcache;
        $this->connected = $options['timeout'] === false ?
            $this->handler->$func($options['host'], $options['port']) :
            $this->handler->$func($options['host'], $options['port'], $options['timeout']);
    }

    /** 是否连接 */
    private function is_connected()
    {
        return $this->connected;
    }

    /** 读取缓存 */
    public function get($name)
    {
        Vpf\N('cache_read', 1);
        return $this->handler->get($name);
    }

    /** 写入缓存 */
    public function set($name, $value, $expire = null)
    {
        Vpf\N('cache_write', 1);
        if(is_null($expire))
        {
            $expire = $this->options['expire'];
        }
        if($this->handler->set($name, $value, $this->options['compress'], $expire))
        {
            return true;
        }
        return false;
    }

    /** 删除缓存 */
    public function del($name, $timeout = false)
    {
        return $timeout === false ?
            $this->handler->delete($name) :
            $this->handler->delete($name, $timeout);
    }

    /** 清除缓存 */
    public function clear()
    {
        return $this->handler->flush();
    }
}
?>