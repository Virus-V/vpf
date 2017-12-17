<?php
/**
 *--------------------------------------------------
 * runtime
 *--------------------------------------------------
 * @program     : VPF
 * @create      : 2012-2-29 15:54:26
 * @description :
 */
namespace Vpf;

if(!defined('VPF_PATH')) exit();

/* 检查应用目录 */
if(!is_dir(RUNTIME_PATH)){
    /* 部署应用目录 */
    build_app_dir();
}else{
    /* 检查缓存目录 */
    check_runtime_dir();
}

/** 部署应用目录 */
function build_app_dir(){
    if(!is_dir(APP_PATH)) mk_dir(APP_PATH, 0777);
    if(!is_writeable(APP_PATH)){
        halt(L('_DIR_READONLY_').': '.APP_PATH);
    }
    $dirs = array(
        HTML_PATH,
        CFG_PATH,
        COMM_PATH,
        LIB_PATH,
        LIB_CTRL_PATH,
        LIB_MODL_PATH,
        LIB_CLASS_PATH,
        LANG_PATH,
        LANG_PATH.D_S.C('DEFAULT_LANG'),
        TPL_PATH,
        TPL_PATH.D_S.C('TE_TPL_THEME'),
        RUNTIME_PATH,
        CACHE_PATH,
        LOG_PATH,
        TEMP_PATH,
        DATA_PATH,
        PUBLIC_PATH);
    mkdirs($dirs);

    /* 创建默认应用配置 */
    if(!is_file(CFG_PATH.D_S.CONFIG_FILE)){
        copy(VPF_PATH.'/data/cfg/config.php', CFG_PATH.D_S.CONFIG_FILE);
    }
    /* 创建默认应用常量 */
    if(!is_file(CFG_PATH.D_S.DEFINE_FILE)){
        copy(VPF_PATH.'/data/cfg/defines.php', CFG_PATH.D_S.DEFINE_FILE);
    }
    /* 创建默认跳转提示页面 */
    if(!is_file(TPL_PATH.D_S.C('TE_TPL_THEME').D_S.DISP_JUMP_FILE)){
        copy(VPF_PATH.'/data/disp_jump.php', TPL_PATH.D_S.C('TE_TPL_THEME').D_S.DISP_JUMP_FILE);
    }
    /* 创建默认错误提示页面 */
    if(!is_file(TPL_PATH.D_S.C('TE_TPL_THEME').D_S.ERR_FILE)){
        copy(VPF_PATH.'/data/err_no_msg.php', TPL_PATH.D_S.C('TE_TPL_THEME').D_S.ERR_FILE);
    }
    /* 创建默认控制器 */
    if(!is_file(LIB_CTRL_PATH.D_S.ucwords(strtolower(C('DEFAULT_CTRL'))).'Ctrl'.'.class.php')){
        build_first_ctrl();
    }
    
    /* ====创建其他配置文件==== */
    /* 创建默认路由规则 */
    if(!is_file(CFG_PATH.D_S.'routes.php')){
        copy(VPF_PATH.'/data/cfg/routes.php', CFG_PATH.D_S.'routes.php');
    }
    /* 创建默认静态规则 */
    if(!is_file(CFG_PATH.D_S.'htmls.php')){
        copy(VPF_PATH.'/data/cfg/htmls.php', CFG_PATH.D_S.'htmls.php');
    }
}

/** 检查缓存目录 */
function check_runtime_dir(){
    if(!is_writeable(RUNTIME_PATH)){
        halt(L('_DIR_READONLY_').': '.RUNTIME_PATH);
    }
    if(!is_dir(CACHE_PATH)) mk_dir(CACHE_PATH);
    if(!is_dir(LOG_PATH)) mk_dir(LOG_PATH);
    if(!is_dir(TEMP_PATH)) mk_dir(TEMP_PATH);
    if(!is_dir(DATA_PATH)) mk_dir(DATA_PATH);
    return true;
}

/** 创建默认控制器 */
function build_first_ctrl(){
    $defaultCtrl = ucwords(strtolower(C('DEFAULT_CTRL')))."Ctrl";
    $content = file_get_contents(VPF_PATH.'/data/IndexCtrl.tpl.php');
    $content = str_replace('IndexCtrl', $defaultCtrl, $content);
    $content = str_replace('index', C('DEFAULT_ACTN'), $content);
    file_put_contents(LIB_CTRL_PATH.D_S.$defaultCtrl.'.class.php', $content);
    $content = file_get_contents(VPF_PATH.'/data/index_ctrl.html');
    file_put_contents(TPL_PATH.D_S.C('DEFAULT_TPL').C('TE_TPL_THEME').'/index.php', $content);
}

/** 创建编译缓存 */
function build_runtime_cache($append = ''){
    $core_list = include VPF_PATH.'/comm/core.php'; /* 读取框架核心文件列表 */
    $interface_list = include VPF_PATH.'/comm/interface.php'; /* 读取类接口文件列表 */
    $list = array_merge($core_list,$interface_list); /* 合并框架核心和接口列表 */
    $list[] = VPF_PATH.'/comm/func.php'; /* 加载框架函数库 */
    /* 生成编译文件 */
    $defs = get_defined_constants(TRUE);
    $content = 'namespace Vpf;';
    $content .= array_define($defs['user']);
    foreach ($list as $file){
        $content .= compile($file);
    }
    $content .= $append."\n\\Vpf\\C(".var_export(C(),true).');';
    $runtime = '~runtime.php';
    file_put_contents(RUNTIME_PATH.D_S.$runtime, strip_whitespace('<?php '.$content)); //'<?php '.
}

?>