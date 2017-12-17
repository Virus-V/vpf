<?php
/**
 *--------------------------------------------------
 * VPF MySQLi 数据库类
 *--------------------------------------------------
 * @program     : VPF
 * @create      : 2012-3-6 9:51:16
 * @description :
 */
namespace Vpf\Lib\DataBase;
use Vpf,Vpf\Lib,Vpf\Interfaces;

class DbMysqli extends Lib\Db implements Interfaces\DataBaseInterface
{
    /** 构造函数 读取数据库配置信息 */
    public function __construct($dbCfg='')
    {
        if(!extension_loaded('mysqli'))
        {
            Vpf\halt(Vpf\L('_NOT_SUPPERT_').':mysqli');
        }
        if(!empty($dbCfg))
        {
            $this->dbCfg = $dbCfg;
        }
        $this->init_connect();
    }

    /** 连接数据库 */
    public function connect($dbCfg = '', $linkNum = 0)
    {
        if(!isset($this->linkID[$linkNum]))
        {
            if(empty($dbCfg))
            {
                $dbCfg = $this->dbCfg;
            }
            $this->linkID[$linkNum] = new \mysqli($dbCfg['hostname'], $dbCfg['username'], $dbCfg['password'], $dbCfg['database'], $dbCfg['hostport']);
            if(mysqli_connect_errno())
            {
                Vpf\halt(mysqli_connect_error());
            }
            $dbVersion = $this->linkID[$linkNum]->server_version;
            if($dbVersion >= "4.1")
            {
                $this->linkID[$linkNum]->query("SET NAMES '".Vpf\C('DB_CHARSET')."'");
            }
            if($dbVersion >'5.0.1')
            {
                $this->linkID[$linkNum]->query("SET sql_mode=''"); /* 设置 sql_model */
            }
            $this->connected = true; /* 标记连接成功 */
            if(1 != Vpf\C('DB_DEPLOY_TYPE'))
            {
                unset($this->dbCfg); /* 注销数据库安全信息*/
            }
        }
        return $this->linkID[$linkNum];
    }

    /** 查询 */
    public function query($sql)
    {
        $this->init_connect(false);
        if(!$this->_linkID)
        {
            return false;
        }
        $this->queryStr = $sql;
        if($this->queryID)
        {
            $this->free(); /* 释放前次查询结果 */
        }
        Vpf\N('db_query', 1); /* 记录数据库操作次数 */
        Vpf\G('queryStartTime'); /* 记录开始执行时间 */
        $this->queryID = $this->_linkID->query($sql);
        $this->debug();
        if (false === $this->queryID)
        {
            $this->error();
            return false;
        }
        else
        {
            $this->numRows = $this->queryID->num_rows;
            $this->numCols = $this->queryID->field_count;
            return $this->get_all();
        }
    }

    /** 执行 */
    public function execute($sql)
    {
        $this->init_connect(true);
        if(!$this->_linkID)
        {
            return false;
        }
        $this->queryStr = $sql;
        if($this->queryID)
        {
            $this->free(); /* 释放前次查询结果*/
        }
        Vpf\N('db_write', 1); /* 记录数据库操作次数 */
        Vpf\G('queryStartTime'); /* 记录开始执行时间 */
        $result = $this->_linkID->query($sql);
        $this->debug();
        if (false === $result)
        {
            $this->error();
            return false;
        }
        else
        {
            $this->numRows = $this->_linkID->affected_rows;
            $this->lastInsID = $this->_linkID->insert_id;
            return $this->numRows;
        }
    }

    /** 替换记录 */
    public function replace($data, $options = array())
    {
        foreach($data as $key => $val)
        {
            $value = $this->parse_value($val);
            if(is_scalar($value)) /* 过滤非标量数据 */
            {
                $values[] = $value;
                $fields[] = $this->parse_key($key);
            }
        }
        $sql = 'REPLACE INTO '.$this->parse_table($options['table']).' ('.implode(',', $fields).') VALUES ('.implode(',', $values).')';
        return $this->execute($sql);
    }

    /** 获得所有的查询数据 */
    private function get_all()
    {
        $result = array();
        if($this->numRows > 0)
        {
            for($i = 0; $i < $this->numRows; $i++)
            {
                $result[$i] = $this->queryID->fetch_assoc();
            }
            $this->queryID->data_seek(0);
        }
        return $result;
    }
    /** 取得数据库的表信息 */
    public function get_tables($dbName = '')
    {
        $sql = !empty($dbName) ? 'SHOW TABLES FROM '.$dbName : 'SHOW TABLES ';
        $result = $this->query($sql);
        $info = array();
        if($result)
        {
            foreach ($result as $key => $val)
            {
                $info[$key] = current($val);
            }
        }
        return $info;
    }

    /** 取得数据表的字段信息 */
    public function get_fields($tableName)
    {
        $result = $this->query('SHOW COLUMNS FROM '.$this->parse_key($tableName));
        $info = array();
        if($result)
        {
            foreach ($result as $key => $val)
            {
                $info[$val['Field']] = array(
                    'name'    => $val['Field'],
                    'type'    => $val['Type'],
                    'notnull' => (bool)($val['Null'] === ''), /* not null is empty, null is yes */
                    'default' => $val['Default'],
                    'primary' => (strtolower($val['Key']) == 'pri'),
                    'autoinc' => (strtolower($val['Extra']) == 'auto_increment'),
                );
            }
        }
        return $info;
    }

    /** 启动事务 */
    public function start_trans()
    {
        $this->initConnect(true);
        //数据rollback 支持
        if($this->transTimes == 0)
        {
            $this->_linkID->autocommit(false);
        }
        $this->transTimes++;
        return ;
    }

    /** 用于非自动提交状态下面的查询提交 */
    public function commit()
    {
        if($this->transTimes > 0)
        {
            $result = $this->_linkID->commit();
            $this->_linkID->autocommit(true);
            $this->transTimes = 0;
            if(!$result)
            {
                Vpf\halt($this->error());
            }
        }
        return true;
    }

    /** 事务回滚 */
    public function rollback()
    {
        if ($this->transTimes > 0)
        {
            $result = $this->_linkID->rollback();
            $this->transTimes = 0;
            if(!$result)
            {
                Vpf\halt($this->error());
            }
        }
        return true;
    }

    /** 字段名和表名处理 */
    protected function parse_key(&$key)
    {
        $key = trim($key);
        if(false !== strpos($key, ' ')
            || false !== strpos($key, ',')
            || false !== strpos($key,'*')
            || false !== strpos($key, '(')
            || false !== strpos($key, '.')
            || false !== strpos($key, '`'))
        {
            /* 如果包含* 或者 使用了sql方法 则不作处理 */
        }
        else
        {
            $key = '`'.$key.'`';
        }
        return $key;
    }

    /** 转义SQL特殊字符 */
    protected function escape_string($str)
    {
        if($this->_linkID)
        {
            return $this->_linkID->real_escape_string($str);
        }
        else
        {
            return addslashes($str);
        }
    }

    /** 释放查询结果 */
    public function free()
    {
        mysqli_free_result($this->queryID);
        $this->queryID = 0;
    }

    /** 关闭数据库 */
    public function close()
    {
        if(!empty($this->queryID))
        {
            $this->queryID->free_result();
        }
        if($this->_linkID && !$this->_linkID->close())
        {
            Vpf\halt($this->error());
        }
        $this->_linkID = 0;
    }

    /** 数据库错误信息 */
    public function error()
    {
        $this->error = $this->_linkID->error;
        if($this->debug && '' != $this->queryStr)
        {
            $this->error .= "\n [ SQL语句 ] : ".$this->queryStr;
        }
        return $this->error;
    }
}
?>