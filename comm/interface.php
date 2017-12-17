<?php
/**
 *--------------------------------------------------
 * 类接口列表
 *--------------------------------------------------
 * @program     : VPF
 * @create      : 2012-2-29 16:07:46
 * @description :
 */
namespace Vpf;
if(!defined('VPF_PATH')) exit();

return array(
    VPF_PATH.'/interface/CacheInterface.class.php',       /* 缓存类接口 */
    VPF_PATH.'/interface/DataBaseInterface.class.php',    /* 数据库类接口 */
);
?>