<?php
namespace Common\Model;

/**
 * 分配通道给商户模型
 * Class BaseModel
 * @package Common\Model
 * @author 黄治华
 */
class ProductUserModel extends BaseModel
{
    protected $tableName = 'product_user';

    protected $fields = array(
//        `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT ' ',
//      `userid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户编号',
//      `pid` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '商户通道ID',
//      `polling` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '接口模式：0 单独 1 轮询',
//      `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '通道状态 0 关闭 1 启用',
//      `channel` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '指定单独通道ID',
//      `weight` varchar(255) DEFAULT NULL COMMENT '通道权重',
    );

    //常量: $status
    const STATUS_INACTIVE = 0; //关闭
    const STATUS_ACTIVE = 1; //启用
}