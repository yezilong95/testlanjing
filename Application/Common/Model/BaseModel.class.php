<?php
namespace Common\Model;

use Think\Model;

/**
 * MySQL基类
 * Class BaseModel
 * @package Common\Model
 * @author 黄治华
 */
class BaseModel extends Model
{
    /**
     * BaseModel constructor.
     * @param string $name
     * @param string $whichDb
     */
    public function __construct($name, $whichDb='')
    {
        //主库
        if(empty($whichDb) || $whichDb == 'Main'){
            $this->connection = [
                'db_type' => C('DB_TYPE'),
                'db_user' => C('DB_USER'),
                'db_pwd'  => C('DB_PWD'),
                'db_host' => C('DB_HOST'),
                'db_port' => C('DB_PORT'),
                'db_name' => C('DB_NAME'),
            ];
            $this->dbName = C('DB_NAME');
            $this->tablePrefix = C('DB_PREFIX');
        }
        //从库
        else {
            $this->connection = [
                'db_type' => C('DB_TYPE_'.$whichDb),
                'db_user' => C('DB_USER_'.$whichDb),
                'db_pwd'  => C('DB_PWD_'.$whichDb),
                'db_host' => C('DB_HOST_'.$whichDb),
                'db_port' => C('DB_PORT_'.$whichDb),
                'db_name' => C('DB_NAME_'.$whichDb),
            ];
            $this->dbName = C('DB_NAME_'.$whichDb);
            $this->tablePrefix = C('DB_PREFIX_'.$whichDb);
        }
        parent::__construct($name, $this->tablePrefix, $this->connection);
    }

    /**
     * 获取请求时间
     * @return bool|string
     */
    protected function getRequestTime()
    {
        return date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']);
    }

}