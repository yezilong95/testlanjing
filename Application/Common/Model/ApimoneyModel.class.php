<?php
namespace Common\Model;

/**
 * 商户在每条通道的交易金额模型 - 公共模块
 * Class BaseModel
 * @package Common\Model
 * @author 黄治华
 */
class ApimoneyModel extends BaseModel
{
    protected $tableName = 'apimoney';

    protected $fields = array(
        'id', //int 主键
        'userid', //int 商户id，没有加10000
        'payapiid', //int 通道id，通道账户id（ChannelAccount.id）
        'money', //float '0.000' 交易金额
        'freezemoney', //float '0.000' 冻结金额
        'status' //int 总是为1，没有使用到
    );
}