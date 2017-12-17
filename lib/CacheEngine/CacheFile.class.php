<?php
/**
 *--------------------------------------------------
 * VPF 缓存到文件
 *--------------------------------------------------
 * @program     : VPF
 * @create      : 2012-3-11 14:18:16
 * @description :
 */
namespace Vpf\Lib\Cache;
use Vpf,Vpf\Lib,Vpf\Interfaces;

class CacheFile extends Lib\Cache implements Interfaces\CacheInterface
{

    protected $prefix='~@'; /* 缓存存储前缀 */

    /** 构造函数 */
    public function __construct($options = array())
    {
        $this->options = Vpf\C('DATA_CACHE_FILE_CFG');
        $this->options['expire'] = is_null($this->options['expire']) ? Vpf\C('DATA_CACHE_EXPIRE') : $this->options['expire'];
        if(!empty($options))
        {
            $this->options = array_merge($this->options, $options);
        }
        
        $this->options['temp'] = rtrim($this->options['temp'], '/\\').D_S;

        $this->connected = is_dir($this->options['temp']) && is_writeable($this->options['temp']);
        $this->init();
    }

    /** 初始化检查 */
    private function init()
    {
        $stat = stat($this->options['temp']);
        $dir_perms = $stat['mode'] & 0007777; /* 获取权限位 */
        $file_perms = $dir_perms & 0000666; /* 删除执行权限 */
        /* 创建缓存目录 */
        if(!is_dir($this->options['temp']))
        {
            if(!@mkdir($this->options['temp']))
            {
                return false;
            }
            chmod($this->options['temp'], $dir_perms);
        }
    }

    /** 是否连接 */
    private function is_connected()
    {
        return $this->connected;
    }

    /** 取得变量的存储文件名 */
    private function get_filename($name)
    {
        $name = md5($name);
        if($this->options['subdir'])
        {
            $dir = ''; /* 使用子目录 */
            for($i = 0; $i < $this->options['path_level']; $i++)
            {
                $dir .= $name{$i}.D_S;
            }
            if(!is_dir($this->options['temp'].$dir))
            {
                Vpf\mk_dir($this->options['temp'].$dir);
            }
            $filename = $dir.$this->prefix.$name.'.php';
        }
        else
        {
            $filename = $this->prefix.$name.'.php';
        }
        return $this->options['temp'].$filename;
    }

    /** 写入缓存 */
    public function set($name, $value, $expire = null)
    {
        Vpf\N('cache_write', 1);
        if(is_null($expire))
        {
            $expire = $this->options['expire'];
        }
        $filename = $this->get_filename($name);
        $data = serialize($value);
        if($this->options['compress']>0 && function_exists('gzcompress')) /* 数据压缩 */
        {
            $data = gzcompress($data, $this->options['compress']);
        }
        $check = '';
        if($this->options['check']){ /* 数据校验 */
            $check = md5($data);
        }
        $data = "<?php\n//".sprintf('%012d', $expire).$check.$data."\n?>";
        $result = file_put_contents($filename, $data);
        if($result){
            clearstatcache();
            return true;
        }
        return false;
    }

    /** 读取缓存 */
    public function get($name)
    {
        $filename = $this->get_filename($name);
        if(!$this->is_connected() || !is_file($filename))
        {
            return false;
        }
        Vpf\N('cache_read', 1);
        $content = file_get_contents($filename);
        if(false !== $content)
        {
            $expire = (int)substr($content, 8, 12);
            if($expire != 0 && time() > filemtime($filename) + $expire)
            {
                @unlink($filename); /* 删除过期缓存文件 */
                return false;
            }
            if($this->options['check']) /* 开启数据校验 */
            {
                $check = substr($content, 20, 32);
                $content = substr($content,52, -3);
                if($check != md5($content)) /* 校验失败 */
                {
                    return false;
                }
            }
            else
            {
                $content = substr($content, 20, -3);
            }
            if($this->options['compress']>0 && function_exists('gzcompress')) /* 数据解压 */
            {
                $content = gzuncompress($content);
            }
            $content = unserialize($content);
            return $content;
        }
        return false;
    }

    /** 删除缓存 */
    public function del($name, $timeout = false)
    {
        return @unlink($this->get_filename($name));
    }

    /** 清除缓存 */
    public function clear()
    {
        $path = $this->options['temp'];
        if($dir = opendir($path))
        {
            while($file = readdir($dir))
            {
                $check = is_dir($file);
                if (!$check )
                {
                    @unlink($path.$file );
                }
            }
            closedir($dir);
            return true;
        }
    }
}
?>