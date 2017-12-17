<?php
/**
 *--------------------------------------------------
 * VPF 数据库接口
 *--------------------------------------------------
 */
namespace Vpf\Interfaces;

interface DataBaseInterface{
    /** 连接数据库 */
    public function connect($dbCfg = '', $linkNum = 0);

    /** 查询 */
    public function query($sql);

    /** 执行 */
    public function execute($sql);

    /** 替换记录 */
    public function replace($data, $options = array());

    /** 取得数据库的表信息 */
    public function get_tables($dbName = '');
    
    /** 取得数据表的字段信息 */
    public function get_fields($tableName);

    /** 启动事务 */
    public function start_trans();

    /** 用于非自动提交状态下面的查询提交 */
    public function commit();

    /** 事务回滚 */
    public function rollback();

    /** 释放查询结果 */
    public function free();

    /** 关闭数据库 */
    public function close();

    /** 数据库错误信息 */
    public function error();
}
?>