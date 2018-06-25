<?php
namespace Common\Model;

/**
 * 代付日记类
 * Class GDaiFuLogModel
 * @package Common\Model
 * @author 黄治华
 */
class GDaiFuLogModel extends GBaseModel
{
    protected $tableName = 'dai_fu_log';

    //默认字段(按需), 字段的顺序既是文档字段顺序
    protected $fields = array(
        'requestTime', //请求时间, 可以过滤一次请求的日记
        'type', //调用类型
        'code', //代付通道
        'msg', //信息
        'orderId', //平台的代付订单id
        'channelMerchantId', //通道商户号
        'merchantId', //商户号
        'level', //日记级别
        'id', //主键
    );

    //常量: level
    const LEVEL_INFO = 'info'; //信息
    const LEVEL_WARNING = 'warning'; //警告
    const LEVEL_ERROR = 'error'; //错误

    //常量: type
    const TYPE_SUBMIT = '代付提交';
    const TYPE_QUERY = '代付查询';

    public function add($data = array(), $options = array(), $replace = false)
    {
        $data['id'] = $this->genId();
        $data['requestTime'] = $this->getRequestTime();
        $data['level'] = empty($data['level']) ? self::LEVEL_INFO : $data['level'];
        return parent::add($this->sortFields($data), $options, $replace);
    }
}