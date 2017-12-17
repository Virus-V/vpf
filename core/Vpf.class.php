<?php
/**
 *--------------------------------------------------
 * VPF 框架基类
 *--------------------------------------------------
 * @program     : VPF
 * @create      : 2012-2-29 18:00:09
 * @description :
 */
namespace Vpf;

class Vpf
{
    /* 自动变量获取 */
    public function __get($name){
        if(isset($this->$name)){
            return($this->$name);
        }else{
            return(NULL);
        }
    }
    
    /* 自动变量设置 */
    public function __set($name, $value){
        if(property_exists($this, $name)){
            $this->$name = $value;
        }
    } 
}
?>
