<?php
namespace Common\Model;

/**
 * 代付订单模型 - 公共模块
 * Class BaseModel
 * @package Common\Model
 * @author 黄治华
 */
class WttklistModel extends BaseModel
{
    protected $tableName = 'wttklist';

    protected $fields = [
        'id', //int 主键
//  `userid` int(11) NOT NULL,
//  `bankname` varchar(300) NOT NULL,
//  `bankzhiname` varchar(300) NOT NULL,
//  `banknumber` varchar(300) NOT NULL,
//  `bankfullname` varchar(300) NOT NULL,
//  `sheng` varchar(300) NOT NULL,
//  `shi` varchar(300) NOT NULL,
//  `sqdatetime` datetime DEFAULT NULL,
//  `daifu_time` int(11) NOT NULL COMMENT '接口调用时的最后代付时间，再次代付会更新这个时间',
//  `cldatetime` datetime DEFAULT NULL, 后台手动操作的为“变更为已处理”或“驳回结算”的时间
//  `status` tinyint(4) NOT NULL DEFAULT '0',
//  `tkmoney` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '提款金额',
//  `sxfmoney` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '手续费',
//  `money` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '实际到账',
//  `t` int(4) NOT NULL DEFAULT '1',
//  `payapiid` int(11) NOT NULL DEFAULT '0',
//  `memo` text COMMENT '备注',
//  `additional` varchar(1000) NOT NULL DEFAULT ' ' COMMENT '额外的参数',
//  `code` varchar(64) NOT NULL DEFAULT ' ' COMMENT '代码控制器名称',
//  `df_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '代付通道id',
//  `df_name` varchar(64) NOT NULL DEFAULT ' ' COMMENT '代付名称',
//  `orderid` varchar(100) NOT NULL DEFAULT ' ' COMMENT '代付订单id',
//  `cost` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '成本',
//  `cost_rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本费率',
//  `rate_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '费率类型：按单笔收费0，按比例收费：1',
//  `batch_no` varchar(30) DEFAULT NULL COMMENT '打款批次号, 易宝要求15纯数字',
//  `platform_order_no` varchar(60) DEFAULT NULL COMMENT '渠道平台的订单号',
//  `idcardnum` varchar(32) DEFAULT NULL COMMENT '身份证号',
    ];

    //常量 status
    const STATUS_SUBMIT = 0; //商户提交代付, 未处理
    const STATUS_PENDING = 1; //接口已申请代付（后台点击“提交代付”成功后修改的为状态），手动操作为变更为处理中
    const STATUS_SUCCESS = 2; //接口确认代付成功（后台点击“代付查询”成功后修改的为状态），手动操作为已处理
    const STATUS_RETURN = 3; //后台手动操作拒绝代付，会把该金额直接退到商户余额里，手动操作为驳回结算，没有调用代付类接口

    /**
     * 是否合法的代付状态
     * @param $status
     * @return bool
     */
    public static function isLegalStatus($status){
        if (in_array($status, [self::STATUS_SUBMIT, self::STATUS_PENDING, self::STATUS_SUCCESS, self::STATUS_RETURN])){
            return true;
        } else {
            return false;
        }
    }
}