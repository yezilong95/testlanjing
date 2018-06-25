<?php
namespace Common\Model;

/**
 * 资金冻结待解冻记录 - 公共模块
 * Class BaseModel
 * @package Common\Model
 * @author 黄治华
 */
class BblockedlogModel extends BaseModel
{
    protected $tableName = 'blockedlog';

    protected $fields = array(
//        `id` int(11) NOT NULL AUTO_INCREMENT,
//        `orderid` varchar(100) NOT NULL COMMENT '订单号',
//        `userid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户编号',
//        `amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '冻结金额',
//        `thawtime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '解冻时间',
//        `pid` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '商户支付通道',
//        `createtime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
//        `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态 1 解冻 0 冻结',
    );
}