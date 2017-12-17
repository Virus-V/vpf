<?php
/**
 *--------------------------------------------------
 * VPF 模型基类
 *--------------------------------------------------
 * @program     : VPF
 * @create      : 2012-3-2 21:47:41
 * @description :
 */
namespace Vpf;
use Vpf\Lib;

class Modl extends Vpf
{
    protected $name = ''; /* 模型名称 */
    protected $db = null; /* 当前数据库操作对象 */
    protected $connCfg = ''; /* 数据库配置信息 */
    protected $dbName  = ''; /* 数据库名称 */
    protected $tablePrefix = ''; /* 数据表前缀 */
    protected $tableSuffix = ''; /* 数据表后缀 */
    protected $tableName = ''; /* 数据表名(不包含表前缀) */
    protected $trueTableName =''; /* 实际数据表名(包含表前缀) */
    protected $pk = 'id'; /* 默认主键名称 */
    protected $fields = array(); /* 字段信息 */
    protected $data = array(); /* 数据信息 */
    protected $options = array(); /* 参数 */
    protected $error = ''; /* 最近错误信息 */
    protected $_validate = array(); /* 自动验证数据 array(field 键值, rule 规则, message 错误信息, type 验证类型 in between equal length regex)*/
    protected $autoCheckFields = true; /* 是否自动检测字段信息 */

    public function __construct($name = '', $connCfg = ''){
        /* 获取模型名称 */
        if(!empty($name)){
            $this->name = $name;
        }elseif(empty($this->name)){
            $this->name = substr(get_class($this), 0, -4);
        }
        /* 过滤掉命名空间 */
        if(strpos($this->name, '\\') !== false){
            list($classname, $group_name) = explode('\\', strrev($this->name), 2);
            $this->name = strrev($classname);
        }
        /* 设置表前缀及后缀 */
        $this->tablePrefix = $this->tablePrefix ? $this->tablePrefix : C('DB_PREFIX');
        $this->tableSuffix = $this->tableSuffix ? $this->tableSuffix : C('DB_SUFFIX');
		
        /* 获取数据库操作对象 */
        $this->db(0, empty($this->connCfg) ? $connCfg : $this->connCfg);

        if(!empty($this->name) && $this->autoCheckFields){
            $this->check_tableInfo(); /* 字段检测 */
        }
    }

    /** 得到完整的数据表名 */
    public function get_tableName(){
        if(empty($this->trueTableName)){
            $tableName = !empty($this->tablePrefix) ? $this->tablePrefix : '';
            if(empty($this->tableName)){
                $this->tableName = parse_name($this->name,0);
            }
            $tableName .= $this->tableName.(!empty($this->tableSuffix) ? $this->tableSuffix : '');
            $this->trueTableName = strtolower($tableName);
        }
        return (!empty($this->dbName) ? $this->dbName.'.' : '').$this->trueTableName;
    }

    /** 切换当前的数据库连接.config：可以是option里的参数，也可以是DSN，也可以是配置数组，为NULL断开数据库连接 */
    protected function db($linkNum, $config = ''){
        static $_db = array();
        if(!isset($_db[$linkNum])){
            /* 如果不为空，是字符串，并且没有找到/,则说明这是个配置键名，否则是DSN */
            if(!empty($config) && is_string($config) && false === strpos($config, '/')){
                $config = C($config); 
            }
            $_db[$linkNum] = get_instance('Lib\\Db', '', 'get_db', $config);
        }elseif(NULL === $config){
            $_db[$linkNum]->close(); /* 关闭数据库连接 */
            unset($_db[$linkNum]);
            return ;
        }
        $this->db = $_db[$linkNum]; /* 切换数据库连接 */
        return $this;
    }

    /** 检测数据表信息 */
    protected function check_tableInfo(){
        if(empty($this->fields)){
            if(C('DB_FIELDS_CACHE')){
                $this->fields = F('_fields/'.$this->name);
                if(!$this->fields){
                    $this->flush();
                }
            }else{
                $this->flush();
            }
        }
    }

    /** 获取字段信息并缓存 */
    public function flush(){
        $fields = $this->db->get_fields($this->get_tableName());
        if(!$fields){
            return false; /* 无法获取字段信息 */
        }
        $this->fields = array_keys($fields);
        $this->fields['_autoinc'] = false;
        foreach($fields as $key => $val){
            $type[$key] = $val['type']; /* 记录字段类型 */
            if($val['primary']){
                $this->fields['_pk'] = $key;
                if($val['autoinc']){
                    $this->fields['_autoinc'] = true;
                }
            }
        }
        if(C('DB_FIELDTYPE_CHECK')){
            $this->fields['_type'] =  $type; /* 记录字段类型信息 */
        }
        if(C('DB_FIELDS_CACHE')){
            F('_fields/'.$this->name, $this->fields); /* 缓存数据表字段信息*/
        }
    }

    /** 获取数据表字段信息 */
    public function get_dbFields(){
        return $this->fields;
    }

    /** 获取主键名称 */
    public function get_pk(){
        return isset($this->fields['_pk']) ? $this->fields['_pk'] : $this->pk;
    }

    /** 返回模型的错误信息 */
    public function get_error(){
        return $this->error;
    }

    /** 创建并验证数据对象$data 但不保存到数据库 */
    public function create($data = ''){
        if(empty($data)){
            $data = $_POST; /* 如果没有传值默认取POST数据 */
        }elseif(is_object($data)){
            $data = get_object_vars($data);
        }

        if(empty($data) || !is_array($data)){
            $this->error = L('_DATA_TYPE_INVALID_');
            return false;
        }

        /* 数据自动验证 */
        if(!$this->verify_data($data)){
            return false;
        }

        /* 根据字段验证数据*/
        if($this->autoCheckFields){ /* 开启字段检测 则过滤非法字段数据 */
            $vo = array();
            foreach($this->fields as $key => $name){
                if(substr($key, 0, 1)=='_'){
                    continue;
                }
                $val = isset($data[$name]) ? $data[$name] : null;
                /* 保证赋值有效 */
                if(!is_null($val)){
                    $vo[$name] = $val;
                }
            }
            $data = $vo;
        }
        return $this->data = $data;
     }

    /** 查询一条数据 */
    public function find($options=array()){
        if(is_numeric($options) || is_string($options)){
            $where[$this->get_pk()] = $options;
            $options = array();
            $options['where'] = $where;
        }
        $options['limit'] = 1; /* 总是查找一条记录 */
        $options = $this->parse_options($options); /* 分析表达式 */
        $resultSet = $this->db->select($options);
        if(false === $resultSet){
            return false;
        }
        if(empty($resultSet)){
            return null;
        }
        $this->data = $resultSet[0];
        return $this->data;
    }

    /** 字段值增长 */
    public function field_inc($field, $step = 1, $condition = ''){
        return $this->set_field($field, array('exp', $field.'+'.$step), $condition);
    }

    /** 字段值减少 */
    public function field_dec($field, $step = 1, $condition = ''){
        return $this->set_field($field, array('exp', $field.'-'.$step), $condition);
    }

    /** 设置记录的某个字段值 支持使用数据库字段和方法 */
    public function set_field($field, $value, $condition=''){
        if(empty($condition) && isset($this->options['where'])){
            $condition = $this->options['where'];
        }
        $options['where'] = $condition;
        if(is_array($field)){
            foreach ($field as $key => $val){
                $data[$val] = $value[$key];
            }
        }
        else
        {
            $data[$field] = $value;
        }
        return $this->update($data, $options);
    }

    /** 获取一条记录的某个字段值 $spea:字段数据间隔符号 */
    public function get_field($field, $sepa = ' ', $condition = ''){
        if(empty($condition) && isset($this->options['where'])){
            $condition = $this->options['where'];
        }
        $options['where'] = $condition;
        $options['field'] = $field;
        $options = $this->parse_options($options);
        if(strpos($field, ',')){ /* 多字段 */
            $resultSet = $this->db->select($options);
            if(!empty($resultSet)){
                $_field = explode(',', $field);
                $field = array_keys($resultSet[0]);
                if($_field[0] == $_field[1]){
                    $field = array_merge(array($field[0]), $field);
                }
                $key = array_shift($field);
                $cols =   array();
                foreach($resultSet as $result){
                    $name = $result[$key];
                    $cols[$name] = '';
                    foreach ($field as $val){
                        $cols[$name] .= $result[$val].$sepa;
                    }
                    $cols[$name] = substr($cols[$name], 0, -strlen($sepa));
                }
                return $cols;
            }
        }else{
            $options['limit'] = 1;
            $result = $this->db->select($options);
            if(!empty($result)){
                return reset($result[0]);
            }
        }
        return null;
    }

    /** 返回最后插入的ID */
    public function get_lastInsID(){
        return $this->db->lastInsID;
    }

    /** 返回最后执行的sql语句 */
    public function get_lastSql(){
        return $this->db->get_lastSql();
    }

    /** 插入数据,replace 替换插入，如果存在则更新，不存在则插入 */
    public function insert($replace=false, $data='', $options=array()){
        if(empty($data)){
            if(!empty($this->data)){
                $data = $this->data;
                $this->data = array(); /* 重置数据 */
            }else{
                $this->error = L('_DATA_TYPE_INVALID_');
                return false;
            }
        }
        $data = $this->facade($data); /* 数据处理 */
        $options = $this->parse_options($options); /* 分析数据操作参数 */
        $result = $this->db->insert($data, $options, $replace); /* 写入数据到数据库 */
        if(false !== $result){
            $insertId = $this->get_lastInsID();
            if($insertId){
                $data[$this->get_pk()] = $insertId; /* 自增主键返回插入ID */
                return $insertId;
            }
        }
        return $result;
    }
	/* 
	$this->create($update);
	$this->where($where)->update();
	*/
    /** 更新数据 */
    public function update($data='', $options=array()){
        if(empty($data)){
            if(!empty($this->data)){
                $data = $this->data;
                $this->data = array(); /* 重置数据 */
            }else{
                $this->error = L('_DATA_TYPE_INVALID_');
                return false;
            }
        }
        $data = $this->facade($data); /* 数据处理 */
        $options = $this->parse_options($options); /* 分析数据操作参数 */
        if(!isset($options['where'])){
            /* 如果存在主键数据 则自动作为更新条件 */
            if(isset($data[$this->get_pk()])){
                $pk = $this->get_pk();
                $where[$pk] = $data[$pk];
                $options['where'] = $where;
                $pkValue = $data[$pk];
                unset($data[$pk]);
            }else{
                $this->error = L('_OPERATION_WRONG_'); /* 如果没有任何更新条件则不执行 */
                return false;
            }
        }
        $result = $this->db->update($data, $options);
        if(false !== $result){
            if(isset($pkValue)){
                $data[$pk] =$pkValue;
            }
        }
        return $result;
    }

    /** 删除数据 */
    public function delete($options=array()){
        if(empty($options) && empty($this->options)){
            /* 如果删除条件为空 则删除当前数据对象所对应的记录 */
            if(!empty($this->data) && isset($this->data[$this->get_pk()])){
                return $this->delete($this->data[$this->getPk()]);
            }
            return false;
        }
        if(is_numeric($options)  || is_string($options)){
            /* 根据主键删除记录 */
            $pk = $this->get_pk();
            if(strpos($options, ',')){
                $where[$pk] = array('IN', $options);
            }else{
                $where[$pk] = $options;
                $pkValue = $options;
            }
            $options = array();
            $options['where'] = $where;
        }
        $options = $this->parse_options($options); /* 分析数据操作参数 */
        $result = $this->db->delete($options);
        if(false !== $result){
            $data = array();
            if(isset($pkValue)){
                $data[$pk] = $pkValue;
            }
        }
        return $result; /* 返回删除记录个数 */
    }

    /** 查询数据集 */
    public function select($options = array()){
        if(is_string($options) || is_numeric($options)){
            /* 根据主键查询 */
            $pk = $this->get_pk();
            if(strpos($options, ',')){
                $where[$pk] = array('IN', $options);
            }else{
                $where[$pk] = $options;
            }
            $options = array();
            $options['where'] = $where;
        }
        $options = $this->parse_options($options); /* 分析数据操作参数 */
        $resultSet = $this->db->select($options);
        if(false === $resultSet){
            return false;
        }
        if(empty($resultSet)){
            return null; /* 查询结果为空 */
        }
        return $resultSet;
    }

    /** SQL查询 */
    public function query($sql){
        if(!empty($sql)){
            if(strpos($sql, '__TABLE__')){
                $sql = str_replace('__TABLE__', $this->get_tableName(), $sql);
            }
            return $this->db->query($sql);
        }
        return false;
    }

    /** 执行SQL语句 */
    public function execute($sql){
        if(!empty($sql)){
            if(strpos($sql,'__TABLE__')){
                $sql = str_replace('__TABLE__', $this->get_tableName(), $sql);
            }
            return $this->db->execute($sql);
        }
        return false;
    }

    /** 启动事务 */
    public function start_trans(){
        $this->commit();
        $this->db->startTrans();
        return;
    }

    /** 提交事务 */
    public function commit(){
        return $this->db->commit();
    }

    /** 事务回滚 */
    public function rollback(){
        return $this->db->rollback();
    }

    /** 分析数据库操作参数 */
    protected function parse_options($options=array()){
        if(is_array($options)){
            $options = array_merge($this->options, $options);
        }
        $this->options = array(); /* 查询过后清空sql表达式组装 避免影响下次查询 */
        if(!isset($options['table'])){
            $options['table'] = $this->get_tableName(); /* 自动获取表名 */
        }
        if(!empty($options['alias'])){
            $options['table'] .= ' '.$options['alias'];
        }
        /* 字段类型验证 */
        if(C('DB_FIELDTYPE_CHECK')){
            if(isset($options['where']) && is_array($options['where'])){
                /* 对数组查询条件进行字段类型检查 */
                foreach ($options['where'] as $key => $val){
                    if(in_array($key, $this->fields, true) && is_scalar($val)){
                        $this->parse_type($options['where'], $key);
                    }
                }
            }
        }
        return $options;
    }

    /** 对保存到数据库的数据进行处理 */
    protected function facade($data){
        if(!empty($this->fields)){
            foreach ($data as $key => $val){
                if(!in_array($key, $this->fields, true)) /* 检查非数据字段 */{
                    unset($data[$key]);
                }elseif(C('DB_FIELDTYPE_CHECK') && is_scalar($val)){
                    $this->parse_type($data, $key); /* 字段类型检查 */
                }
            }
        }
        return $data;
    }

    /** 数据类型检测 */
    protected function parse_type(&$data, $key){
        $fieldType = strtolower($this->fields['_type'][$key]);
        if(false === strpos($fieldType, 'bigint') && false !== strpos($fieldType, 'int')){
            $data[$key] = intval($data[$key]);
        }elseif(false !== strpos($fieldType, 'float') || false !== strpos($fieldType,'double')){
            $data[$key] = floatval($data[$key]);
        }elseif(false !== strpos($fieldType, 'bool')){
            $data[$key] = (bool)$data[$key];
        }
    }

    /** 数据验证 */
    protected function verify_data($data){
        $this->error = array();
        if(!empty($this->_validate)){
            foreach($this->_validate as $val){
                /*  array(field, rule, message, type) */
                $val[2] = isset($val[2]) ? $val[2] : L('_DATA_TYPE_INVALID_');
                $val[3] = isset($val[3]) ? $val[3] : 'regex';
                if(isset($data[$val[0]])){
                    if(false === $this->check($data[$val[0]], $val[1], $val[3])){
                        $this->error = $val[2];
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /** 验证数据 支持 in between equal regex  */
    public function check($value, $rule, $type='regex'){
        switch(strtolower($type)){
            case 'in': /* 验证是否在某个指定范围之内 逗号分隔字符串或者数组 */
                $range = is_array($rule) ? $rule : explode(',', $rule);
                return in_array($value, $range);
            case 'between': /* 验证是否在某个范围 */
                list($min, $max) = explode(',', $rule);
                return $value >= $min && $value <= $max;
            case 'equal': /* 验证是否等于某个值 */
                return $value == $rule;
            case 'length':
                list($min, $max) = explode(',', $rule);
                $str_len = strlen($value);
                return $str_len >= $min && $str_len <= $max;
            case 'regex':
            default:
                return $this->regex($value, $rule); /* 正则方式验证 */
        }
    }

    /** 使用正则验证数据 */
    public function regex($value, $rule){
        $validate = array(
            'require'=> '/.+/',
            'email' => '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
            'url' => '/^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/',
            'currency' => '/^\d+(\.\d+)?$/',
            'number' => '/^[0-9]+([.]{1}[0-9]+){0,1}$/',
            'zip' => '/^[1-9]\d{5}$/',
            'integer' => '/^[-\+]?\d+$/',
            'double' => '/^[-\+]?\d+(\.\d+)?$/',
            'english' => '/^[A-Za-z]+$/',
        );
        // 检查是否有内置的正则表达式
        if(isset($validate[strtolower($rule)])){
            $rule = $validate[strtolower($rule)];
        }
        return preg_match($rule, $value) === 1;
    }
    
    /** 利用__call方法实现一些特殊的Model方法 */
    public function __call($method, $args){
        if(in_array(strtolower($method), array('field', 'table', 'where', 'order', 'limit', 'page', 'alias', 'having', 'group', 'lock', 'distinct'), true)){
            /* 连贯操作的实现 */
            $this->options[strtolower($method)] = $args[0];
            return $this;
        }elseif(in_array(strtolower($method), array('count', 'sum', 'min', 'max', 'avg'), true)){
            /* 统计查询的实现 */
            $field = isset($args[0]) ? $args[0] : '*';
            return $this->get_field(strtoupper($method).'('.$field.') AS vpf_'.$method);
        }elseif(strtolower(substr($method, 0, 5)) == 'getby'){
            /* 根据某个字段获取记录 */
            $field = parse_name(substr($method, 5));
            $where[$field] = $args[0];
            return $this->where($where)->find();
        }else{
            echo (__CLASS__.':'.$method.L('_METHOD_NOT_EXIST_'));
            return;
        }
    }

    /** 设置数据对象值 */
    public function data($data){
        if(is_object($data)){
            $data = get_object_vars($data);
        }elseif(is_string($data)){
            parse_str($data, $data);
        }elseif(!is_array($data)){
            halt(L('_DATA_TYPE_INVALID_'));
        }
        $this->data = $data;
        return $this;
    }

    /** 查询SQL组装 join */
    public function join($join){
        if(is_array($join)){
            $this->options['join'] = $join;
        }else{
            $this->options['join'][] = $join;
        }
        return $this;
    }

}
?>