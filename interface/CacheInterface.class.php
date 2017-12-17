<?php
/*
* VPF
* Cache接口
*/
namespace Vpf\Interfaces;

interface CacheInterface{
    public function get($name);/*获得缓存*/
    public function set($name, $value, $expire = null); /*设置缓存*/
    public function del($name, $timeout = false);/*删除缓存，如果参数timeout指定，该元素会在timeout秒后失效。*/
    public function clear();/*清空缓存*/
}
?>