<?php
namespace Common\Model;

/**
 * 每个网站自己的变化信息 - 公共模块
 * Class BaseModel
 * @package Common\Model
 * @author 黄治华
 */
class DiffModel extends BaseModel
{
    protected $tableName = 'diff';

    protected $fields = array(
        'id',
        'key',
        'value'
    );

    /*
     * type的值
     *  公用: web_name, logo_url
     *  首页: home_
     */
}