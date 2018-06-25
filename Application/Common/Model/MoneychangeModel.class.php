<?php
namespace Common\Model;

/**
 * 资金变动流水表模型 - 公共模块
 *  添加一条记录后，用户余额对应改变
 *  添加：交易订单成功、后台手动添加用户余额、订单金额解冻、驳回代付
 *  减少：用户提交代付、后台手动冻结金额
 * Class BaseModel
 * @package Common\Model
 * @author 黄治华
 */
class MoneychangeModel extends BaseModel
{
    protected $tableName = 'moneychange';

    protected $fields = [
        'id', //int 主键
//        `userid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户编号',
//  `ymoney` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '原金额',
//  `money` decimal(15,3) NOT NULL DEFAULT '0.000' COMMENT '变动金额',
//  `gmoney` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '变动后金额',
//  `datetime` datetime DEFAULT NULL COMMENT '修改时间',
//  `transid` varchar(50) DEFAULT NULL COMMENT '交易流水号', 系统独立生成，但是订单金额解冻用的是订单号
//  `tongdao` smallint(6) unsigned DEFAULT '0' COMMENT '支付通道ID',
//  `lx` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '类型',
//  `tcuserid` int(11) DEFAULT NULL,
//  `tcdengji` int(11) DEFAULT NULL,
//  `orderid` varchar(50) DEFAULT NULL COMMENT '订单号', 如果lx=20，则为WttklistModel.orderid；如果lx=8，则为orderModel.orderid；
//  `contentstr` varchar(255) DEFAULT NULL COMMENT '备注',
    ];

    //流水类型 lx
    const LX_ORDER_SUCCESS = 1; //交易订单成功
    const LX_USER_SUBMIT_DAIFU = 6; //用户提交代付
    const LX_ORDER_AMOUNT_UNFREEZE = 8; //订单金额解冻
    const LX_ORDER_AMOUNT_XXX = 9; //xxx
    const LX_ORDER_AMOUNT_XXX3 = 3; //xxx
    const LX_ORDER_AMOUNT_XXX4 = 4; //xxx
    const LX_DAIFU_TI_XIAN = 11; //提现驳回
    const LX_DAIFU_RETURN = 12; //代付驳回

    /**
     * 生成唯一流水号
     * @return string
     */
    public static function genTransId() {
        $arr = explode('.', uniqid('', true));
        return date('YmdHis').$arr[1];
    }
}