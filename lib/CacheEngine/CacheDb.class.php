<?php
/**
 *--------------------------------------------------
 * VPF 缓存到数据库
 *--------------------------------------------------
 * @program     : VPF
 * @create      : 2012-3-11 15:47:17
 * @description :
 */

/* 缓存表的结构
CREATE TABLE vpf_cache (
    cachekey varchar(255) NOT NULL,
    expire int(11) NOT NULL,
    data blob,
    datacrc int(32),
    UNIQUE KEY `cachekey` (`cachekey`)
);
*/
namespace Vpf\Lib\Cache;
use Vpf,Vpf\Lib,Vpf\Interfaces;

class CacheDb extends Lib\Cache implements Interfaces\CacheInterface
{
    private $db; /* 缓存数据库对象 */

    /** 构造函数 */
    function __construct($options = array())
    {
        $this->options = Vpf\C('DATA_CACHE_DB_CFG');
        $this->options['db'] = is_null($this->options['db']) ? Vpf\C('DB_NAME') : $this->options['db'];
        $this->options['expire'] = is_null($this->options['expire']) ? Vpf\C('DATA_CACHE_EXPIRE') : $this->options['expire'];
        if(!empty($options))
        {
            $this->options = array_merge($this->options, $options);
        }
        $this->db = get_instance('Lib\\Db');
        $this->connected = is_resource($this->db);
    }

    /** 是否连接 */
    private function is_connected()
    {
        return $this->connected;
    }

    /** 读取缓存 */
    public function get($name)
    {
        $name = addslashes($name);
        Vpf\N('cache_read',1);
        $result = $this->db->query('SELECT `data`, `datacrc` FROM `'.$this->options['table'].'` WHERE `cachekey`=\''.$name.'\' AND (`expire` = 0 OR `expire` > '.time().') LIMIT 0, 1');
        if(false !== $result )
        {
            $result = $result[0];
            if($this->options['check']) /* 开启数据校验 */
            {
                if($result['datacrc'] != md5($result['data'])) /* 校验失败 */
                {
                    return false;
                }
            }
            $content = $result['data'];
            if($this->options['compress']>0 && function_exists('gzcompress')) /* 解压数据 */
            {
                $content = gzuncompress($content);
            }
            $content = unserialize($content);
            return $content;
        }
        return false;
    }

    /** 写入缓存 */
    public function set($name, $value, $expire = null)
    {
        $data = serialize($value);
        $name = addslashes($name);
        Vpf\N('cache_write',1);
        if( $this->options['compress']>0 && function_exists('gzcompress')) /* 压缩数据 */
        {
            $data = gzcompress($data,$this->options['compress']);
        }
        $crc = ''; /* 数据校验 */
        if($this->options['check'])
        {
            $crc = md5($data);
        }
        $expire = !empty($expire) ? $expire : $this->options['expire'];
        $expire = ($expire == 0) ? 0 : (time()+$expire);
        $result = $this->db->query('select `cachekey` from `'.$this->options['table'].'` where `cachekey`=\''.$name.'\' limit 0,1');
        if(!empty($result)) /* 更新记录 */
        {
            $result = $this->db->execute('UPDATE '.$this->options['table'].' SET `data` = \''.$data.'\', `datacrc` = \''.$crc.'\', `expire` = '.$expire.' WHERE `cachekey`=\''.$name.'\'');
        }
        else /* 新增记录 */
        {
             $result = $this->db->execute('INSERT INTO '.$this->options['table'].' (`cachekey`, `data`, `datacrc`, `expire`) VALUES (\''.$name.'\',\''.$data.'\',\''.$crc.'\','.$expire.')');
        }
        if($result)
        {
            return true;
        }
        return false;
    }

    /** 删除缓存 */
    public function del($name, $timeout = false)
    {
        $name = addslashes($name);
        return $this->db->execute('DELETE FROM `'.$this->options['table'].'` WHERE `cachekey`=\''.$name.'\'');
    }

    /** 清除缓存 */
    public function clear()
    {
        return $this->db->execute('TRUNCATE TABLE `'.$this->options['table'].'`');
    }
}
?>