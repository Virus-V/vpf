<?php
/**
 *--------------------------------------------------
 * VPF 控制器基类
 *--------------------------------------------------
 * @program     : VPF
 * @create      : 2015-4-9 17:22
 * @description :
 */
namespace Vpf;
use Vpf\Lib;

class Ctrl extends Vpf{
    protected $te = null;
    public function __construct(){
        $this->te = get_instance('Lib\Te', '', 'get_te');
    }

    /** 模板变量赋值 */
    public function assign($var, $value = ''){
        $this->te->assign($var, $value);
    }

    /** 显示内容 */
    public function display($file = '', $charset = '', $contentType = ''){
        /* 网页字符编码 */
        if(empty($charset)){
            $charset = C('DEFAULT_CHARSET');
        }
        if(empty($contentType)){
            $contentType = C('TE_TPL_CONTENT_TYPE');
        }
        header('Content-Type:'.$contentType.'; charset='.$charset);
        header('Cache-control: private'); /*支持页面回跳 */
        header('X-Powered-By: Vpf');

        $this->te->display($file);
    }
    
    /** 获取内容 */
    public function fetch($file = ''){
        return $this->te->fetch($file);
    }
    
    /** 操作错误跳转 */
    public function error($message = '', $jumpUrl = ''){
        $this->disp_jump($message, $jumpUrl, 0);
    }

    /** 操作成功跳转 */
    public function success($message = '', $jumpUrl = ''){
        $this->disp_jump($message, $jumpUrl, 1);
    }

    /** 默认跳转操作 $status 1:success, 0:error */
    protected function disp_jump($message, $jumpUrl, $status = 1){
        if($status){
            $this->assign('status', true);
        }
        else{
            $this->assign('status', false);
        }
        $this->assign('message', $message);
        $this->assign('jumpUrl', $jumpUrl);
        if(is_file(THEME_PATH.D_S.DISP_JUMP_FILE)){
            $this->display(THEME_PATH.D_S.DISP_JUMP_FILE);
        }else{
            $this->display(C('TPL_DISP_JUMP'));
        }
    }

    /** 判断是否AJAX请求 */
    protected function is_ajax()
    {
        if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 'xmlhttprequest' == strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])){
            return true;
        }
        if(!empty($_POST[C('VAR_AJAX_SUBMIT')]) || !empty($_GET[C('VAR_AJAX_SUBMIT')]))
        {
            return true; /* 判断Ajax方式提交 */
        }
        return false;
    }

    /** Ajax方式返回数据到客户端 $data:数据, $info:提示信息, $status:ajax类型*/
    public function ajax_return($data, $info = '', $type = '')
    {
        if(!$this->is_ajax()){
            exit();
        }
        $result = array();
        $result['info'] = $info;
        $result['data'] = $data;
        if(empty($type)){
            $type = C('AJAX_DATA_TYPE');
        }
        if('JSON' == strtoupper($type)){
            /* 返回JSON数据格式到客户端 */
            header("Content-Type:text/html; charset=utf-8");
            exit(json_encode($result));
        }
        elseif('XML' == strtoupper($type)){
            /* 返回xml格式数据 */
            header("Content-Type:text/xml; charset=utf-8");
            exit(xml_encode($result));
        }
        elseif('EVAL' == strtoupper($type)){
            /* 返回可执行的js脚本 */
            header("Content-Type:text/html; charset=utf-8");
            exit($data);
        }
        else{
            /* TODO 增加其它格式 */
        }
    }

    public function __call($method, $args){
    }
}
?>