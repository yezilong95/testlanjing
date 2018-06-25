<?php
namespace Common\Model;

use Think\Model\MongoModel;

/**
 * Mongodb基类
 * Class GBaseModel
 * @package Common\Model
 * @author 黄治华
 */
class GBaseModel extends MongoModel
{
    protected $whichDb; //标记调用哪个数据库

    /**
     * GBaseModel constructor.
     * @param string $name
     * @param string $whichDb
     */
    public function __construct($name, $whichDb='')
    {
        $this->whichDb = $whichDb;
        //主库
        if(empty($whichDb) || $whichDb == 'Main'){
            $this->connection = [
                'db_type' => C('MONGODB_TYPE'),
                'db_user' => C('MONGODB_USER'),
                'db_pwd'  => C('MONGODB_PWD'),
                'db_host' => C('MONGODB_HOST'),
                'db_port' => C('MONGODB_PORT'),
                'db_name' => C('MONGODB_NAME'),
            ];
            $this->dbName = C('MONGODB_NAME');
            $this->tablePrefix = C('MONGODB_PREFIX');
        }
        //从库
        else {
            $this->connection = [
                'db_type' => C('MONGODB_TYPE_'.$whichDb),
                'db_user' => C('MONGODB_USER_'.$whichDb),
                'db_pwd'  => C('MONGODB_PWD_'.$whichDb),
                'db_host' => C('MONGODB_HOST_'.$whichDb),
                'db_port' => C('MONGODB_PORT_'.$whichDb),
                'db_name' => C('MONGODB_NAME_'.$whichDb),
            ];
            $this->dbName = C('MONGODB_NAME_'.$whichDb);
            $this->tablePrefix = C('MONGODB_PREFIX_'.$whichDb);
        }
        parent::__construct($name, $this->tablePrefix, $this->connection);
    }

    /**
     * 生成id
     * @return string
     */
    public function genId()
    {
        return uniqid();
    }

    /**
     * 获取请求时间
     * @return bool|string
     */
    public function getRequestTime()
    {
        return date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']);
    }

    /**
     * 按照$fields的顺序排列文档, 只保存$fields的字段, 所有字段以string的方式保存
     * @param array $data
     * @return array
     */
    public function sortFields(array $data)
    {
        $saveData = array();
        foreach($this->fields as $key => $value){
            if(is_numeric($key) && isset($data[$value])){
                $saveData[$value] = strval($data[$value]);
            }
        }
        return $saveData;
    }

}