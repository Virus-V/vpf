<?php
/**
 *--------------------------------------------------
 * VPF 默认选项
 *--------------------------------------------------
 * @program     : VPF
 * @create      : 2012-3-3 22:51:46
 * @description :
 */
namespace Vpf;
if(!defined('VPF_PATH')) exit();

return array(
    /* 目录部署相关 */
    'DIR_INDEX' => true, /* 目录安全开关 */
    'DIR_INDEX_FILENAME' => 'index.html', /* 目录安全文件名 */
    'DIR_INDEX_CONTENT' => 'Access Denied!', /* 目录安全文件内容 */

    /* 默认设定 */
    'APP_AUTOLOAD_PATH' => '', /* 应用类自动加载路径 用','分割 */
    'APP_CFG_LIST' => 'routes,htmls', /* 应用动态配置文件列表 */
    'DEFAULT_CTRL' => 'index', /* 默认控制器 */
    'DEFAULT_ACTN' => 'index', /* 默认操作 */
    'LANG_DETECT' => true, /* 检测语言 */
    'TE_DETECT_THEME' => true, /* 检测模板主题 */
    'DEFAULT_LANG' => 'zh-cn', /* 默认语言 */
    'DEFAULT_TIMEZONE' => 'PRC', /* 默认时区 */
    'DEFAULT_CHARSET' => 'utf-8', /* 默认输出编码 */

    /* 操作间隔 */
    'INTERVAL_NAME' => 'actn_dtln', /* 间隔名称 */
    'INTERVAL' => 10, /* 默认间隔时间 */
    
    'PARAM_FILTER' => true, /* 敏感字符自动过滤 */
    'TO_FILTER' => array('_GET','_POST','_REQUEST','_COOKIE'), /* 进行过滤的系统变量 */
    'PARAM_FILTER_ACTION' => 'addslashes', /* 默认过滤函数 */

    /* 数据库设置 */
    'DB_TYPE' => 'mysqli', /* 数据库类型 */
    'DB_USER' => 'root', /* 用户名 */
    'DB_PWD' => '', /* 密码 */
    'DB_HOST' => 'localhost', /* 服务器地址 */
    'DB_PORT' => '3306', /* 端口 */
    'DB_NAME' => '', /* 数据库名 */
    'DB_PARAMS' => '', /* 数据库参数 */
    'DB_CHARSET' => 'utf8', /* 数据库默认编码 */
    'DB_PREFIX' => 'vpf_', /* 数据库表前缀 */
    'DB_SUFFIX' => '', /* 数据库表后缀 */
    'DB_DEPLOY_TYPE' => 0, /* 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器) */
    'DB_RW_SEPARATE' => false, /* 主从式数据库读写是否分离 */
    'DB_FIELDTYPE_CHECK' => true, /* 是否进行字段类型检查 */
    'DB_FIELDS_CACHE' => true, /* 启用字段缓存 */
    'DB_LIKE_FIELDS' => '', /* 默认可以进行模糊匹配的字段（正则） */

    /* URL设置 */
    'URL_TYPE' => 1, /* 1:普通模式 2:PATHINFO模式 3:REWRITE模式 4:兼容模式 */
    'URL_CASE_IGNORE' => true, /* true:不区分大小写 */
    'URL_ROUTER' => false, /* 是否开启URL路由 */
    'URL_ROUTE_RULES' => array(), /* 默认路由规则 */
    'URL_PATHINFO_DEPR' => '/', /* PATHINFO模式各参数之间的分割符号 */
    'URL_HTML_SUFFIX' => '.html', /* URL伪静态后缀设置 */

    /* 系统变量名称设置 */
    'VAR_GROUP_DEPR' => '@', /* 控制组名与控制器名之间的分隔符 */
    'VAR_GROUP' => 'vpf_group', /* 控制组 */
    'VAR_CTRL' => 'vpf_ctrl', /* 控制器 */
    'VAR_ACTN' => 'vpf_actn', /* 操作 */
    'VAR_PAGE' => 'vpf_page', /* 分页 */
    'VAR_TPL' => 'vpf_tpl', /* 模板 */
    'VAR_LANG' => 'vpf_lang', /* 语言 */
    'VAR_PATHINFO' => 'vpf', /* 兼容模式获取变量 如 ?s=/ctrl/actn/id/1 */
    'VAR_AJAX_SUBMIT' => 'ajax', /*ajax提交标志符,在表单内填写,*/

    /* Cookie设置 */
    'COOKIE_EXPIRE' => 3600, /* Coodie有效期 */
    'COOKIE_DOMAIN' => '', /* Cookie有效域名 */
    'COOKIE_PATH' => '/', /* Cookie路径 */
    'COOKIE_PREFIX' => 'vpf_', /* Cookie前缀 */

    /* 数据缓存设置 */
    'DATA_CACHE_EXPIRE' => 0, /* 有效期 0表示永久缓存 */
    'DATA_CACHE_TYPE' => 'File', /* 数据缓存类型,支持: File|Memcache|Db */
    /* *Memcache缓存参数 */
    'DATA_CACHE_MEMCACHE_CFG' => array(
        'host' => '', /* memcached服务端监听主机地址。这个参数也可以指定为其他传输方式比如unix:///path/to/memcached.sock 来使用Unix域socket，在这种方式下，port参数必须设置为0。 */
        'port' => '', /* memcached服务端监听端口。当使用Unix域socket的时候要设置此参数为0。 */
        'timeout' => 1, /* 连接持续（超时）时间，单位秒。默认值1秒，修改此值之前请三思，过长的连接持续时间可能会导致失去所有的缓存优势。 */
        'compress' => 0, /* 是否压缩缓存，0-9 ,0表示不压缩，9表示最大压缩，默认3，-1采用zlib默认6*/
        'persistent' => false, /* 是否长连接 */
        'expire' => null, /* 缓存有效期 0表示永久缓存，null使用全局默认缓存 */
    ),
    /* *File缓存参数 */
    'DATA_CACHE_FILE_CFG' => array(
        'temp' => TEMP_PATH, /* File缓存路径 */
        'subdir' => false, /* File根据缓存标识的哈希值创建子目录 */
        'path_level' => 1, /* File缓存子目录层级 */
        'compress' => 3, /* 是否压缩缓存，0-9 ,0表示不压缩，9表示最大压缩，默认3，-1采用zlib默认6*/
        'check' => false, /* 是否校验缓存 */
        'expire' => null, /* 缓存有效期 0表示永久缓存，null使用全局默认缓存 */
    ),
    /* *DB缓存参数 */
    'DATA_CACHE_DB_CFG' => array (
        'db' => null, /* 数据库名，null则为系统所在的数据库 */
        'table' => 'vpf_cache', /* 缓存表名 */
        'compress' => 0, /* 是否压缩缓存，0-9 ,0表示不压缩，9表示最大压缩，默认3，-1采用zlib默认6*/
        'check' => false, /* 是否校验缓存 */
        'expire' => null, /* 缓存有效期 0表示永久缓存，null使用全局默认缓存 */
    ),

    /* 模板引擎设置 */
    'TE_TYPE' => 'Vpf', /* 默认模板引擎 */
    'TE_STRIP_SPACE' => true, /* 是否去除模板文件里面的html空格与换行 */
    'TE_MARK_L' => '<{', /* 模板引擎标签开始标记 */
    'TE_MARK_R' => '}>', /* 模板引擎标签结束标记 */
    'TE_TPL_DENY_FUNC' => 'echo,exit', /* 模板引擎禁用函数 */
    'TE_TPL_PATH' => TPL_PATH, /* 模板文件目录路径 */
    'TE_TPL_THEME' => 'default', /* 默认主题 */
    'TE_TPL_CONTENT_TYPE' => 'text/html', /* 默认输出类型 */
    'TE_TPL_SUFFIX' => '.php', /* 默认模板文件后缀 */
    'TE_TPL_AVOID_DOWN' => false, /* 防止模板文件被下载 */
    'TE_TPL_AVOID_DOWN_PREFIX' => '<?php /* VPF', /* 模板内容前缀 */
    'TE_TPL_AVOID_DOWN_SUFFIX' => '*/ ?>', /* 模板内容后缀 */
    'TE_CACHE' => false, /* 是否开启模板编译缓存 */
    'TE_CACHE_TIME' => 0, /* 缓存有效期 0:永久 单位:秒 */
    'TE_CACHE_PATH' => CACHE_PATH, /* 缓存文件路径 */
    'TE_CACHE_SUFFIX' => '.php', /* 默认缓存文件后缀 */
    'TPL_PAGE_TRACE' => VPF_PATH.D_S.'data'.D_S.PAGE_TRACE_FILE, /* 页面跟踪模板 */
    'TPL_DEBUG_ERR' => VPF_PATH.D_S.'data'.D_S.DEBUG_ERR_FILE, /* 调试错误模板 */
    'TPL_ERR' => VPF_PATH.D_S.'data'.D_S.ERR_FILE, /* 默认错误模板 */
    'TPL_DISP_JUMP' => VPF_PATH.D_S.'data'.D_S.DISP_JUMP_FILE, /* 默认跳转模板 */

    'AJAX_DATA_TYPE' => 'JSON', /* AJAX 数据返回格式 可选JSON XML EVAL */
    

    /* 静态缓存设置 */
    'HTML_CACHE' => true, /* 静态缓存开关 */
    'HTML_CACHE_TIME' => 3600, /* 静态缓存有效期 */
    'HTML_READ_TYPE' => 0, /* 静态缓存读取方式 0 readfile 1 redirect */
    'HTML_FILE_SUFFIX' => '.html', /* 默认静态文件后缀 */
    
    /* 调试 */
    'APP_DEBUG' => true, /* 调试开关 */

    /* 显示额外信息 */
    'SHOW_RUN_TIME' => true, /* 运行时间 */
    'SHOW_ADV_TIME' => true, /* 详细的运行时间 */
    'SHOW_DB_TIMES' => true, /* 数据库查询和写入次数 */
    'SHOW_CACHE_TIMES' => true, /* 缓存操作次数 */
    'SHOW_USE_MEM' => true, /* 内存开销 */
    'SHOW_PAGE_TRACE' => true, /* 页面跟踪信息 */

    /* 日志 */
    'LOG_RECORD' => true, /* 日志记录 */
    'LOG_FILE_SIZE' => 2097152, /* 日志大小 超出时新建 */

);
?>