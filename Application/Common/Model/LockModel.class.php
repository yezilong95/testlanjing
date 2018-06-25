<?php
namespace Common\Model;

/**
 * 锁模型，用该对象实现无阻塞分布锁 - 公共模块
 * Class BaseModel
 * @package Common\Model
 * @author 黄治华
 */
class LockModel extends BaseModel
{
    protected $tableName = 'lock';

    protected $fields = [
        'id', //int 主键
        'name', // varchar(255) NOT NULL COMMENT '分布锁名称，一般用对象_方法名表示，唯一值',
        'createTime', // int(11) NOT NULL COMMENT '创建时间',
    ];

    /**
     * 获得对应名称的无阻塞分布锁, 调用该方法前自行启动数据库事务
     * @param $name 分布锁名称
     * @return bool
     */
    public static function lock($name){
        if (empty($name) || !is_string($name)){
            return false;
        }
        $lockModel = new self();
        $lockModel->add(['name'=>$name, 'createTime'=>time()]);
    }

    /**
     * 释放对应名称的分布锁, 调用该方法后自行提交数据库事务
     *  只有调用lock成功后才能调用该方法
     * @param $name
     */
    public static function unlock($name){
        $lockModel = new self();
        $lockModel->where(['name'=>$name])->delete();
    }
}