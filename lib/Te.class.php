<?php
/**
 *--------------------------------------------------
 * VPF 模板引擎基类
 *--------------------------------------------------
 * @program     : VPF
 * @create      : 2012-3-14 11:48:27
 * @description :
 */
namespace Vpf\Lib;
use Vpf;

class Te extends Vpf\Vpf
{
    protected $options = array(); /* 模板引擎参数 */

    /** 模板引擎工厂 */
    public function get_te($options = array()){
        if(!isset($options['teType'])){
            $options['teType'] = Vpf\C('TE_TYPE');
        }
        $teClass = 'Te'.ucwords(strtolower($options['teType']));
        Vpf\import('lib.TemplateEngine.'.$teClass, VPF_PATH);
        $te = Vpf\get_instance('Lib\\Template\\'.$teClass, $this->options);
        return $te;
    }

    /** 设置参数 */
    public function set_option($name, $value){
        $this->options[$name] = $value;
    }

    /** 获取参数 */
    public function get_option($name){
        return $this->options[$name];
    }
}
?>