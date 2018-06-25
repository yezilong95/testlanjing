<?php
namespace Common\Model;

/**
 * 通道账户模型 - 公共模块
 * Class BaseModel
 * @package Common\Model
 * @author 黄治华
 */
class ChannelAccountModel extends BaseModel
{
    protected $tableName = 'channel_account';

    protected $fields = array(
//        `id` smallint(6) unsigned NOT NULL AUTO_INCREMENT COMMENT '供应商通道账号ID',
//  `channel_id` smallint(6) unsigned NOT NULL COMMENT '通道id',
//  `mch_id` varchar(100) DEFAULT NULL COMMENT '商户号',
//  `signkey` varchar(500) DEFAULT NULL COMMENT '签文密钥',
//  `appid` varchar(100) DEFAULT NULL COMMENT '应用APPID',
//  `appsecret` varchar(100) DEFAULT NULL COMMENT '安全密钥',
//  `defaultrate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '下家费率',
//  `fengding` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '封顶手续费',
//  `rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '银行费率',
//  `updatetime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上次更改时间',
//  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态 1开启 0关闭',
//  `title` varchar(100) DEFAULT NULL COMMENT '账户标题',
//  `weight` tinyint(2) DEFAULT NULL COMMENT '轮询权重',
//  `custom_rate` tinyint(1) DEFAULT NULL COMMENT '是否自定义费率',
    );

    //常量: status
    const STATUS_INACTIVE = 0; //关闭
    const STATUS_ACTIVE = 1; //开启
}