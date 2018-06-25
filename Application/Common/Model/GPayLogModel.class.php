<?php
namespace Common\Model;

/**
 * 支付日记类, 记录从商户提交支付-返回给商户通知-通道提交支付-通道返回通知等支付类日记
 * Class PayLog
 * @package Common\Model
 * @author 黄治华
 */
class GPayLogModel extends GBaseModel
{
    protected $tableName = 'pay_log';

    //默认字段(按需), 字段的顺序既是文档字段顺序
    protected $fields = array(
        'requestTime', //请求时间, 可以过滤一次请求的日记
        'merchantId', //商户id
        'productCode', //支付产品编号
        'outTradeId', //商户订单id
        'type', //调用类型
        'msg', //信息
        'orderId', //平台订单id
        'channelMerchantId', //通道商户号
        'level', //日记级别
        'id', //主键
    );

    //常量: $level
    public static $LEVEL_INFO = 'info'; //信息
    public static $LEVEL_WARNING = 'warning'; //警告
    public static $LEVEL_ERROR = 'error'; //错误

    //常量: $type
    public static $TYPE_MERCHANT_REQUEST = '商户请求';
    public static $TYPE_REQUEST_CHANNEL = '请求通道';
    public static $TYPE_CHANNEL_CALLBACK = '通道回调';
    public static $TYPE_CALLBACK_MERCHANT = '回调商户';
    public static $TYPE_CHANNEL_NOTIFY = '通道通知';
    public static $TYPE_NOTIFY_MERCHANT = '通知商户';
    public static $TYPE_CHANNEL_QUERY = '通道查询';

    public function add($data = array(), $options = array(), $replace = false)
    {
        $data['id'] = $this->genId();
        $data['requestTime'] = $this->getRequestTime();
        $data['level'] = empty($data['level']) ? self::$LEVEL_INFO : $data['level'];
        return parent::add($this->sortFields($data), $options, $replace);
    }
}