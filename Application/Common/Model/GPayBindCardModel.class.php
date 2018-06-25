<?php
namespace Common\Model;

/**
 * 用户支付绑卡, 用于快捷支付，如银生宝快捷
 * Class GPayBindCardModel
 * @package Common\Model
 * @author 黄治华
 */
class GPayBindCardModel extends GBaseModel
{
    protected $tableName = 'pay_bind_card';

    //默认字段(按需), 字段的顺序既是文档字段顺序
    protected $fields = array(
        'channelMerchantId',    //通道商户id
        'memberId',             //商户id
        'customerId',           //商户的用户id，小于等于16位, 平台生成的唯一id, 不同的银行卡号与银行卡号一一对应
        'token',                //通道授权码
        'channelCode',          //通道编码
        'firstOrderId',         //首次绑卡的平台订单id
        'bankCardNo',           //银行四要素-银行卡号
        'idCardNo',             //银行四要素-身份证号
        'mobile',               //银行四要素-手机号
        'fullname',             //银行四要素-开户名
        'status',               //状态，1已绑卡，0解绑卡，目前不做解绑功能，解绑卡不可逆，即解绑卡后要重新绑卡
        'createTime',           //创建时间
        'updateTime',           //更新时间
        'id',                   //主键
    );

    //常量: status
    const STATUS_UNBIND = '0'; //0解绑卡
    const STATUS_BIND = '1'; //1已绑卡

    public function add($data = array(), $options = array(), $replace = false)
    {
        $data['createTime'] = $this->getRequestTime();
        $data['updateTime'] = $data['createTime'];
        $data['status'] = self::STATUS_BIND;
        return parent::add($this->sortFields($data), $options, $replace);
    }
}