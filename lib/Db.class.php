<?php
/**
 *--------------------------------------------------
 * VPF 数据库工厂类
 *--------------------------------------------------
 * @program     : VPF
 * @create      : 2012-3-6 9:33:00
 * @eidt        : 2015-8-28 21:13:13
 * @description :
 */
namespace Vpf\Lib;
use Vpf;

class Db extends Vpf\Vpf
{
    public $debug = false; /* 调试开关 */
    public $dbType = ''; /* 数据库类型 */
    public $dbCfg = ''; /* 数据库配置 */
    protected $queryStr = ''; /* 当前SQL指令 */
    protected $lastInsID  = null; /* 最后插入ID */
    protected $numRows = 0; /* 返回或者影响记录数 */
    protected $numCols = 0; /* 返回字段数 */
    protected $transTimes = 0; /*事务指令数 */
    protected $linkID = array(); /* 数据库连接ID 支持多个连接 */
    protected $_linkID = null; /* 当前连接ID */
    protected $queryID = null; /* 当前查询ID */
    protected $connected = false; /* 是否已经连接数据库 */
    protected $comparison = array( /* 数据库表达式 */
        'eq' => '=',
        'neq' => '<>',
        'gt' => '>',
        'egt' => '>=',
        'lt' => '<',
        'elt' => '<=',
        'notlike' => 'NOT LIKE',
        'like' => 'LIKE'
    );
    protected $selectSql = 'SELECT%DISTINCT% %FIELDS% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT%'; /* 查询表达式 */

    public function __construct(){}

    /** DB工厂 */
    public function get_db($dbCfg = ''){
        $dbCfg = $this->parse_cfg($dbCfg);
        if(empty($dbCfg['dbtype'])){
            Vpf\halt(Vpf\L('_NO_DB_CFG_'));
        }
        $this->dbType = ucwords(strtolower($dbCfg['dbtype']));
        $dbClass = 'Db'.$this->dbType;
        Vpf\import('lib.DataBaseEngine.'.$dbClass, VPF_PATH);
		
        $db = Vpf\get_instance('Lib\\DataBase\\'.$dbClass, $dbCfg);
		
        if(Vpf\C('APP_DEBUG')){
            $db->debug = true;
        }
        return $db;
    }

    /** 初始化数据库连接 */
    protected function init_connect($master = true){
        if(1 == Vpf\C('DB_DEPLOY_TYPE')){ /* 采用分布式数据库 */
            $this->_linkID = $this->multi_connect($master);
        }else{
            if(!$this->connected){
                $this->_linkID = $this->connect(); /* 默认单数据库*/
            }
        }
    }

    /** 连接分布式服务器 $master:主服务器 */
    protected function multi_connect($master = false){
        static $_config = array();
        if(empty($_config)){
            /* 缓存分布式数据库配置解析 */
            foreach ($this->dbCfg as $key => $val){
                $_config[$key] = explode(',', $val);
            }
        }
        if(Vpf\C('DB_RW_SEPARATE')){ /* 数据库是否读写分离 */
            if($master){
                $r = 0; /* 默认主服务器 */
            }else{
                $r = floor(mt_rand(1, count($_config['hostname']) - 1)); /* 随机连接读操作数据库 */
            }
        }else{
            $r = floor(mt_rand(0, count($_config['hostname']) - 1)); /* 读写不区分服务器 */
        }
        $dbCfg = array(
            'username' => isset($_config['username'][$r]) ? $_config['username'][$r] : $_config['username'][0],
            'password' => isset($_config['password'][$r]) ? $_config['password'][$r] : $_config['password'][0],
            'hostname' => isset($_config['hostname'][$r]) ? $_config['hostname'][$r] : $_config['hostname'][0],
            'hostport' => isset($_config['hostport'][$r]) ? $_config['hostport'][$r] : $_config['hostport'][0],
            'database' => isset($_config['database'][$r]) ? $_config['database'][$r] : $_config['database'][0],
            'params' => isset($_config['params'][$r]) ? $_config['params'][$r] : $_config['params'][0],
        );
        return $this->connect($dbCfg, $r);
    }

    /** 分析数据库配置信息 支持数组和DSN */
    protected function parse_cfg($dbCfg = ''){

        if(!empty($dbCfg) && is_string($dbCfg)){
            $dbCfg = $this->parse_dsn($dbCfg);
        }elseif(empty($dbCfg)){
            $dbCfg = array (
                'dbtype' => Vpf\C('DB_TYPE'),
                'username' => Vpf\C('DB_USER'),
                'password' => Vpf\C('DB_PWD'),
                'hostname' => Vpf\C('DB_HOST'),
                'hostport' => Vpf\C('DB_PORT'),
                'database' => Vpf\C('DB_NAME'),
                'params' => Vpf\C('DB_PARAMS'),
            );
        }
        return $dbCfg;
    }

    /** DSN解析 mysql://user:pass@host:port/dbName */
    protected function parse_dsn($dsnStr){
        if(empty($dsnStr)){
            return false;
        }
        $info = parse_url($dsnStr);
        if($info['scheme']){
            $dsn = array(
                'dbtype' => $info['scheme'],
                'username' => isset($info['user']) ? $info['user'] : '',
                'password' => isset($info['pass']) ? $info['pass'] : '',
                'hostname' => isset($info['host']) ? $info['host'] : '',
                'hostport' => isset($info['port']) ? $info['port'] : '',
                'database' => isset($info['path']) ? substr($info['path'],1) : ''
            );
        }else{
            preg_match('/^(.*?)\:\/\/(.*?)\:(.*?)\@(.*?)\:([0-9]{1, 6})\/(.*?)$/', trim($dsnStr), $matches);
            $dsn = array(
                'dbtype' => $matches[1],
                'username' => $matches[2],
                'password' => $matches[3],
                'hostname' => $matches[4],
                'hostport' => $matches[5],
                'database' => $matches[6]
            );
        }
        return $dsn;
    }

    /** set分析 */
    protected function parse_set($data){
        foreach ($data as $key => $val){
            $value = $this->parse_value($val);
            if(is_scalar($value)){ /* 过滤非标量 */
                $set[] = $this->parse_key($key).'='.$value;
            }
        }
        return ' SET '.implode(',', $set);
    }

    /** value分析,过滤敏感字符 */
    protected function parse_value($value){
        if(is_string($value)){
            $value = '\''.$this->escape_string($value).'\'';
        }elseif(isset($value[0]) && is_string($value[0]) && strtolower($value[0]) == 'exp'){
            $value = $this->escape_string($value[1]);
        }elseif(is_array($value)){
            $value = array_map(array($this, 'parse_value'), $value);
        }elseif(is_null($value)){
            $value = 'null';
        }
        return $value;
    }

    /** 字段分析 $fields: 'f' | 'f1, f2' | array('f1', 'f2') | array('fld1' => 'f') */
    protected function parse_field($fields){
        if(is_string($fields) && strpos($fields, ',')){
            $fields = explode(',', $fields);
        }
        if(is_array($fields)){
            $array = array();
            foreach($fields as $key=>$field){
                if(is_numeric($key)){
                    $array[] = $this->parse_key($field);
                }else{
                    $array[] = $this->parse_key($key).' AS '.$this->parse_key($field);
                }
            }
            $fieldsStr = implode(',', $array);
        }elseif(is_string($fields) && !empty($fields)){
            $fieldsStr = $this->parse_key($fields);
        }else{
            $fieldsStr = '*';
        }
        return $fieldsStr;
    }

    /** 表名分析 支持别名 */
    protected function parse_table($tables){
        if(is_array($tables)){
            $array = array();
            foreach($tables as $table => $alias){
                if(is_numeric($table)){
                    $array[] = $this->parse_key($alias);
                }else{
                    $array[] = $this->parse_key($table).' '.$this->parse_key($alias);
                }
            }
            $tables = $array;
        }elseif(is_string($tables)){
            $tables = explode(',', $tables);
            array_walk($tables, array(&$this, 'parse_key'));
        }
        return implode(',', $tables);
    }

    /** distinct分析 */
    protected function parse_distinct($distinct){
        return !empty($distinct) ? ' DISTINCT ' : '';
    }

    /** join分析 */
    protected function parse_join($join){
        $joinStr = '';
        if(!empty($join)){
            if(is_array($join)){
                foreach($join as $key => $_join){
                    if(false !== stripos($_join, 'JOIN')){
                        $joinStr .= ' '.$_join;
                    }else{
                        $joinStr .= ' LEFT JOIN ' .$_join;
                    }
                }
            }else{
                $joinStr .= ' LEFT JOIN '.$join;
            }
        }
        /* 将__TABLE_NAME__这样的字符串替换成正规的表名,并且带上前缀和后缀 */
        $joinStr = preg_replace_callback("/__([A-Z0-9_-]+)__/sU", function($matches){return Vpf\C('DB_PREFIX').strtolower($matches[1]).Vpf\C('DB_SUFFIX');}, $joinStr);
        return $joinStr;
    }

    /* $where = array(
        '_logic' => 'or',
        'field1' => array('EQ',123), 
        'field2' => array('NEQ',123),
        'field3' => array('GT',123), 
        'field4' => array('EGT',123),
        'field5' => array('LT',123), 
        'field6' => array('ELT',123),
        'field7' => array('NOTLIKE','123'), 
        'field8' => array('LIKE','123'),
        
        'field9' => array('exp','(select y form x)'), 
        
        'field10' => array('in','1,2,3,4,5,asd'),
        'field11' => array('in','(select y form x)','exp'),
        'field12' => array('BETWEEN',array(1,3)),
        'field13' => array('BETWEEN','4,5'),
        'field14' => array(array('exp','(select y form x)'),'ads',array('EQ',345),'AND'),
    );
    SELECT `id`,
           `field1`
    FROM `table1`
    WHERE (`field1` = 123)
      OR (`field2` <> 123)
      OR (`field3` > 123)
      OR (`field4` >= 123)
      OR (`field5` < 123)
      OR (`field6` <= 123)
      OR (`field7` NOT LIKE '123')
      OR (`field8` LIKE '123')
      OR (`field9` (SELECT y form x))
      OR (`field10` IN ('1','2','3','4','5','asd'))
      OR (`field11` IN (SELECT y form x))
      OR (`field12` BETWEEN 1 AND 3)
      OR (`field13` BETWEEN '4' AND '5')
      OR ((`field14` (SELECT y form x)) AND (`field14` = 'ads') AND (`field14` = 345))
    ORDER BY `id` DESC LIMIT 10,5
    */
    /** where分析 */
    protected function parse_where($where){
        $whereStr = '';
        if(is_string($where)){
            $whereStr = preg_replace_callback("/__([A-Z0-9_-]+)__/sU", function($matches){return Vpf\C('DB_PREFIX').strtolower($matches[1]).Vpf\C('DB_SUFFIX');}, $where);
        }else{ /* 使用数组条件表达式 */
            if(array_key_exists('_logic', $where)){
                /* 逻辑运算规则 如:OR XOR AND NOT */
                $operate = ' '.strtoupper($where['_logic']).' ';
                unset($where['_logic']);
            }else{
                $operate = ' AND '; /* 默认AND运算 */
            }
            foreach ($where as $key => $val){
                $key = trim($key);
                if(!preg_match('/^[A-Z_\-.a-z0-9]+$/', $key)){ /* 查询字段安全过滤 */
                    Vpf\halt(Vpf\L('_EXPRESS_ERROR_').' : '.$key);
                }
                $whereStr .= "(";
                $whereStr .= $this->parse_whereItem($this->parse_key($key), $val);
                $whereStr .= ')'.$operate;
            }
            $whereStr = substr($whereStr, 0, -strlen($operate));
        }
        return empty($whereStr) ? '' : ' WHERE '.$whereStr;
    }

    /** where子单元分析，val=array */
    protected function parse_whereItem($key, $val){
        $key = preg_replace_callback("/__([A-Z0-9_-]+)__/sU", function($matches){return Vpf\C('DB_PREFIX').strtolower($matches[1]).Vpf\C('DB_SUFFIX');}, $key);
        $whereStr = '';
        if(is_array($val)){ // 子单元是数组
            if(is_string($val[0])){ //第一个元素是字符串
                /* 
                'field' => array('EQ',123), field=123
                'field' => array('NEQ',123), field!=123 
                'field' => array('GT',123), field>123 
                'field' => array('EGT',123), field>=123 
                'field' => array('LT',123), field<123 
                'field' => array('ELT',123), field<=123 
                'field' => array('NOTELIKE',123), 
                'field' => array('LIKE',123),
                */
                if(preg_match('/^(EQ|NEQ|GT|EGT|LT|ELT|NOTLIKE|LIKE)$/i', $val[0])){ /* 比较运算 */
                    $whereStr .= $key.' '.$this->comparison[strtolower($val[0])].' '.$this->parse_value($val[1]);
                }
                /* 
                'field' => array('exp','表达式'), 
                */
                elseif('exp'==strtolower($val[0])) /* 使用表达式运算 */
                {
                    //$whereStr .= ' ('.$key.' '.$val[1].') ';
                    $whereStr .= $key.' '.$val[1];
                }
                /* 
                'field' => array('*in*','字符串'), 多个值
                e.g.
                    'field' => array('*in*','1,2,3,4,5,6'),
                
                'field' => array('*in*','表达式','exp'),  不经过处理，直接把表达式追加到in后面
                e.g.
                    'field' => array('*in*','(select y from x)','exp'),
                */
                elseif(preg_match('/IN/i', $val[0])){ /* IN 运算 */
                    if(isset($val[2]) && 'exp'==$val[2]){ //表达式
                        $whereStr .= $key.' '.strtoupper($val[0]).' '.$val[1];
                    }else{ //字符串
                        if(is_string($val[1])){
                             $val[1] =  explode(',', $val[1]);
                        }
                        $zone = implode(',', $this->parse_value($val[1]));
                        $whereStr .= $key.' '.strtoupper($val[0]).' ('.$zone.')';
                    }
                }
                /* BETWEEN运算 
                'field' => array('BETWEEN',array(1,3))
                'field' => array('BETWEEN','1,3');
                */
                elseif(preg_match('/BETWEEN/i',$val[0])){
                    $data = is_string($val[1]) ? explode(',', $val[1]) : $val[1];
                    //$whereStr .= ' ('.$key.' '.strtoupper($val[0]).' '.$this->parse_value($data[0]).' AND '.$this->parse_value($data[1]).' )';
                    $whereStr .= $key.' '.strtoupper($val[0]).' '.$this->parse_value($data[0]).' AND '.$this->parse_value($data[1]);
                }else{
                    Vpf\halt(Vpf\L('_EXPRESS_ERROR_').':'.$val[0]);
                }
            }else{ //第0个元素不为字符串
                $count = count($val);
                /* 判断最后一个元素是不是AND、OR、XOR，并提取出来作为规则*/
                if(in_array(strtoupper(trim($val[$count-1])), array('AND', 'OR', 'XOR'))){
                    $rule = strtoupper(trim($val[$count-1]));
                    $count = $count - 1;
                }else{
                    $rule = 'AND';
                }
                /* 循环这个子单元 */
                for($i=0; $i<$count; $i++){
                    
                    $data = is_array($val[$i]) ? $val[$i][1] : $val[$i]; //表达式
                    /*
                    'field' => array(array('exp','expression'),[...][,'AND'|'OR'|'XOR']),
                    */
                    //是数组且第一个元素是exp
                    if(is_array($val[$i]) && 'exp' == strtolower($val[$i][0])){
                        $whereStr .= '('.$key.' '.$data.') '.$rule.' ';
                    }
                    /*
                    field => array(array(['EQ'|'NEQ'|'GT'|'EGT'|'LT'|'ELT'|'NOTLIKE'|'LIKE'],'expression'),[...][,'AND'|'OR'|'XOR']),
                    field => array(array,'data',[...][,'AND'|'OR'|'XOR']), //第零个元素必须为数组，不然进不到这个循环里
                    */
                    else{
                        $op = is_array($val[$i]) ? $this->comparison[strtolower($val[$i][0])] : '=';
                        $whereStr .= '('.$key.' '.$op.' '.$this->parse_value($data).') '.$rule.' ';
                    }
                }
                $whereStr = substr($whereStr, 0, -4);
            }
        }else{ //子单元是字符串或者其他  'field'=>'str'
            /* 对字符串类型字段采用模糊匹配 */
            if(Vpf\C('DB_LIKE_FIELDS') && preg_match('/('.Vpf\C('DB_LIKE_FIELDS').')/i', $key)){
                $val = '%'.$val.'%';
                $whereStr .= $key." LIKE ".$this->parse_value($val);
            }else{
                $whereStr .= $key." = ".$this->parse_value($val);
            }
        }
        return $whereStr;
    }

    /** group分析 */
    protected function parse_group($group){
        return !empty($group) ? ' GROUP BY '.$group : '';
    }

    /** having分析 */
    protected function parse_having($having){
        return  !empty($having) ? ' HAVING '.$having : '';
    }

    /** order分析 */
    protected function parse_order($order){
        if(is_array($order)){
            $array = array();
            foreach($order as $key => $val){
                if(is_numeric($key)){
                    $array[] = $this->parse_key($val);
                }else{
                    $array[] = $this->parse_key($key).' '.$val;
                }
            }
            $order = implode(',', $array);
        }
        return !empty($order) ? ' ORDER BY '.$order : '';
    }

    /** limit分析 */
    protected function parse_limit($limit){
        return !empty($limit) ? ' LIMIT '.$limit.' ' : '';
    }

    /** 设置锁机制 */
    protected function parse_lock($lock = false){
        if(!$lock){
            return '';
        }
        if('ORACLE' == $this->dbType){
            return ' FOR UPDATE NOWAIT ';
        }
        return ' FOR UPDATE ';
    }

    /** 插入记录 */
    public function insert($data, $options = array(), $replace = false){
        $values = $fields = array();
        foreach($data as $key=>$val){
            $value = $this->parse_value($val);
            if(is_scalar($value)){ /* 过滤非标量 */
                $values[] = $value;
                $fields[] = $this->parse_key($key);
            }
        }
        $sql = ($replace ? 'REPLACE' : 'INSERT').' INTO '.$this->parse_table($options['table']).' ('.implode(',', $fields).') VALUES ('.implode(',', $values).')';
        $sql .= $this->parse_lock(isset($options['lock']) ? $options['lock'] : false);
        return $this->execute($sql);
    }

    /** 更新记录 */
    public function update($data, $options){
        $sql = 'UPDATE '
        .$this->parse_table($options['table'])
        .$this->parse_set($data)
        .$this->parse_where(isset($options['where']) ? $options['where'] : '')
        .$this->parse_order(isset($options['order']) ? $options['order'] : '')
        .$this->parse_limit(isset($options['limit']) ? $options['limit'] : '')
        .$this->parse_lock(isset($options['lock']) ? $options['lock'] : false);
        return $this->execute($sql);
    }

    /** 删除记录 */
    public function delete($options = array()){
        $sql = 'DELETE FROM '
        .$this->parse_table($options['table'])
        .$this->parse_where(isset($options['where']) ? $options['where'] : '')
        .$this->parse_order(isset($options['order']) ? $options['order'] : '')
        .$this->parse_limit(isset($options['limit']) ? $options['limit'] : '')
        .$this->parse_lock(isset($options['lock']) ? $options['lock'] : false);
        return $this->execute($sql);
    }

    /** 查询记录 */
    public function select($options=array()){
        $sql = $this->build_selectSql($options);
        $result = $this->query($sql);
        return $result;
    }

    /** 生成查询SQL */
    protected function build_selectSql($options=array()){
        if(isset($options['page'])){
            /* 根据页数计算limit */
            if(strpos($options['page'], ',')){
                list($page, $listRows) =  explode(',', $options['page']);
            }else{
                $page = $options['page'];
            }
            /* 页数从第一页开始 */
            $page = $page ? $page : 1;
            $listRows = isset($listRows) ? $listRows : (is_numeric($options['limit']) ? $options['limit'] : 20);
            $offset = $listRows * ((int)$page - 1);
            $options['limit'] = $offset.','.$listRows;
        }
        $sql = str_replace(
            array(
                '%TABLE%',
                '%DISTINCT%',
                '%FIELDS%',
                '%JOIN%',
                '%WHERE%',
                '%GROUP%',
                '%HAVING%',
                '%ORDER%',
                '%LIMIT%'),
            array(
                $this->parse_table($options['table']),
                $this->parse_distinct(isset($options['distinct']) ? $options['distinct'] : false),
                $this->parse_field(isset($options['field']) ? $options['field'] : '*'),
                $this->parse_join(isset($options['join']) ? $options['join'] : ''),
                $this->parse_where(isset($options['where']) ? $options['where'] : ''),
                $this->parse_group(isset($options['group']) ? $options['group'] : ''),
                $this->parse_having(isset($options['having']) ? $options['having'] : ''),
                $this->parse_order(isset($options['order']) ? $options['order'] : ''),
                $this->parse_limit(isset($options['limit']) ? $options['limit'] : '')),
            $this->selectSql);
        $sql .= $this->parse_lock(isset($options['lock']) ? $options['lock'] : false);
        return $sql;
    }

    /** 获取最后执行的SQL语句 */
    public function get_lastSql(){
        return $this->queryStr;
    }

    /** 数据库调试 记录当前SQL */
    protected function debug(){
        if($this->debug){
            Vpf\G('queryEndTime'); /* 记录操作结束时间 */
            Vpf\Log::record($this->queryStr." [RunTime:".Vpf\G('queryStartTime','queryEndTime', 6)."s]");
        }
    }

    /** 字段名和表名处理(由驱动类定义) */
    protected function parse_key(&$key){}
    /** 转义SQL特殊字符(由驱动类定义) */
    protected function escape_string($str){}
    /** 查询(由驱动类定义) */
    protected function query($sql){}
    /** 执行(由驱动类定义) */
    protected function execute($sql){}
    /** 关闭数据库(由驱动类定义) */
    protected function close(){}

    public function __destruct(){
        $this->close();
    }
}
?>