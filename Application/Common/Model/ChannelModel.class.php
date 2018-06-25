<?php
namespace Common\Model;

/**
 * 通道概要信息模型 - 公共模块
 * Class BaseModel
 * @package Common\Model
 * @author 黄治华
 */
class ChannelModel extends BaseModel
{
    protected $tableName = 'channel';

    protected $fields = array(
//        `id` smallint(6) unsigned NOT NULL AUTO_INCREMENT COMMENT '供应商通道ID',
//  `code` varchar(200) DEFAULT NULL COMMENT '供应商通道英文编码',
//  `title` varchar(200) DEFAULT NULL COMMENT '供应商通道名称',
//  `mch_id` varchar(100) DEFAULT NULL COMMENT '商户号', //无用字段
//  `signkey` varchar(500) DEFAULT NULL COMMENT '签文密钥', //无用字段
//  `appid` varchar(100) DEFAULT NULL COMMENT '应用APPID', //无用字段
//  `appsecret` varchar(100) DEFAULT NULL COMMENT '安全密钥', //无用字段
//  `gateway` varchar(300) DEFAULT NULL COMMENT '网关地址',
//  `pagereturn` varchar(255) DEFAULT NULL COMMENT '页面跳转网址',
//  `serverreturn` varchar(255) DEFAULT NULL COMMENT '服务器通知网址',
//  `defaultrate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '下家费率',
//  `fengding` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '封顶手续费',
//  `rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '银行费率',
//  `updatetime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上次更改时间',
//  `unlockdomain` varchar(100) NOT NULL COMMENT '防封域名',
//  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态 1开启 0关闭',
//  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 微信扫码 2 微信H5 3 支付宝扫码 4 支付宝H5 5网银跳转 6网银直连 7百度钱包 8 QQ钱包 9 京东钱包',
    );

    //常量: status
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;
}