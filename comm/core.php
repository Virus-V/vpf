<?php
/**
 *--------------------------------------------------
 * 核心文件列表
 *--------------------------------------------------
 * @program     : VPF
 * @create      : 2012-2-29 16:05:46
 * @description :
 */
if(!defined('VPF_PATH')) exit();

return array(
    VPF_PATH.'/core/Vpf.class.php',
    VPF_PATH.'/core/Log.class.php',   /* 日志处理类 */
    VPF_PATH.'/core/Debug.class.php', /* 调试类 */
    VPF_PATH.'/core/App.class.php',   /* 应用类 */
    VPF_PATH.'/core/Ctrl.class.php',  /* 控制器类 */
    VPF_PATH.'/core/Modl.class.php',  /* 模型类 */
    VPF_PATH.'/core/Disp.class.php',  /* URL调度类 */
);
?>