<?php
namespace Common\Model;

/**
 * 订单模型
 * Class BaseModel
 * @package Common\Model
 * @author 黄治华
 */
class OrderModel extends BaseModel
{
    protected $tableName = 'order';

    protected $fields = array(
//        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
//      `pay_memberid` varchar(100) NOT NULL COMMENT '下游商户号',
//      `pay_orderid` varchar(100) NOT NULL COMMENT '系统订单号',
//      `pay_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '支付金额',
//      `pay_poundage` decimal(10,2) unsigned NOT NULL DEFAULT '0.00',
//      `pay_actualamount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00',
//      `pay_applydate` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单创建日期',
//      `pay_successdate` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单支付成功时间',
//      `pay_bankcode` varchar(100) DEFAULT NULL COMMENT '银行编码',
//      `pay_notifyurl` varchar(500) NOT NULL COMMENT '商家异步通知地址',
//      `pay_callbackurl` varchar(500) NOT NULL COMMENT '商家页面通知地址',
//      `pay_bankname` varchar(300) DEFAULT NULL,
//      `pay_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '订单状态: 0 未支付 1 已支付未返回 2 已支付已返回',
//      `pay_productname` varchar(300) DEFAULT NULL COMMENT '商品名称',
//      `pay_productnum` varchar(300) DEFAULT NULL COMMENT '商品数量',
//      `pay_productdesc` varchar(1000) DEFAULT NULL COMMENT '商品描述',
//      `pay_producturl` varchar(500) DEFAULT NULL,
//      `pay_tongdao` varchar(50) DEFAULT NULL,
//      `pay_zh_tongdao` varchar(50) DEFAULT NULL,
//      `pay_tjurl` varchar(1000) DEFAULT NULL,
//      `out_trade_id` varchar(50) NOT NULL COMMENT '商户订单号',
//      `num` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '已补发次数',
//      `memberid` varchar(100) DEFAULT NULL COMMENT '通道商户号',
//      `key` varchar(500) DEFAULT NULL COMMENT '渠道密钥',
//      `account` varchar(100) DEFAULT NULL COMMENT '通道账号 appid',
//      `isdel` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '伪删除订单 1 删除 0 未删',
//      `ddlx` int(11) DEFAULT '0',
//      `pay_ytongdao` varchar(50) DEFAULT NULL,
//      `pay_yzh_tongdao` varchar(50) DEFAULT NULL,
//      `xx` smallint(6) unsigned NOT NULL DEFAULT '0',
//      `attach` text CHARACTER SET utf8mb4 COMMENT '商家附加字段,原样返回',
//      `pay_channel_account` varchar(255) DEFAULT NULL COMMENT '通道商户名称',
//      `cost` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本',
//      `cost_rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本费率',
//      `channel_order_id` varchar(255) DEFAULT NULL COMMENT '通道订单id，信捷快捷支付的tn',
    );

    //常量
    const PAY_STATUS_UNPAY = 0; //0未支付
    const PAY_STATUS_PAID = 1; //1已支付未返回, 通道通知后,设置订单已支付成功,但是未正确通知商户
    const PAY_STATUS_PAID_RETURN = 2; //0未支付, 已支付已正确通知商户
}