<?php
namespace Pay\Controller;
use Common\Model\GPayLogModel;

/**
 * 盛世润发-微信H5
 * Class ShengShiRunFaWxH5Controller
 * @package Pay\Controller
 * @author 黄治华
 */
class ShengShiRunFaWxH5Controller extends PayController
{
    const CODE = 'ShengShiRunFaWxH5';
    const TITLE = '盛世润发-微信H5';
    const TRADE_TYPE = '00016';

    public function Pay($array)
    {
        $out_trade_id = I('request.pay_orderid'); //商户订单号
        $productCode = I('request.pay_bankcode'); //支付产品编号
        $body = I('request.pay_productname', '购买商品'); //商品名称

        $notifyurl = $this->_site . 'Pay_'.self::CODE.'_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_'.self::CODE.'_callbackurl.html'; //返回通知
        $parameter = array(
            'code' => self::CODE, // 通道名称
            'title' => self::TITLE,
            'exchange' => 1, // 金额比例
            'gateway' => "",
            'orderid' => "", //系统订单号
            'out_trade_id' => $out_trade_id, //外部商户订单号
            'body'=>$body,
            'channel'=>$array
        );
        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);

        //必要支付金额最小为15元
//        if($return["amount"] < 15){
//            exit('支付金额最小为15元');
//        }
//        if($return["amount"] > 200000){
//            exit('单笔支付限额200000元');
//        }

        $pay_orderid = $return["orderid"]; //系统订单号
        $memberid = $return["memberid"]; //商户号
        $mch_id = $return['mch_id']; //通道商户号
        $amount = $return["amount"];
        $appid = $return['appid']; //通道账号
        $gateway = $return["gateway"];
        $signkey = $return['signkey'];

        $data = array(
            'versionId' => '1.0',
            'orderAmount' => strval($amount*100),
            'orderDate' => date('YmdHis'),
            'currency' => 'RMB',
            'transType'=> '0008',
            'asynNotifyUrl' => $notifyurl,
            'synNotifyUrl' => $callbackurl,
            'signType' => 'MD5',
            'merId' => $mch_id,
            'prdOrdNo' => $pay_orderid,
            'payMode' => self::TRADE_TYPE,
            'receivableType' => 'D00',
            'prdName' => 'ipds87',
            'prdDesc' => 'iphi1',
            'pnum' => '1',
            'merParam' => 'remark', //扩展参数
        );

        //签名
        $data["signData"]= $this->sign($data, $signkey);
        $postdata = http_build_query($data);

        //添加日记
        $payLog = [
            'merchantId' => $return["memberid"], //商户id
            'productCode' => $productCode, //支付类型
            'outTradeId' => $out_trade_id, //商户订单号
            'channelMerchantId' => $return['mch_id'], //通道商户id
            'orderId' => $return['orderid'], //平台订单号
            'type' => GPayLogModel::$TYPE_REQUEST_CHANNEL, //日记类型
        ];
        $payLog['msg'] = self::TITLE.'-提交的数据: '.$postdata.', 网关: '.$gateway;
        $this->payLogModel->add($payLog);

        list($return_code, $return_content) = $this->http_post_data($gateway, $postdata);

        //添加日记
        $payLog['msg'] = self::TITLE.'-返回的数据: '.$return_content;
        $this->payLogModel->add($payLog);

        echo $return_content;
    }

    // 通道页面通知返回
    public function callbackurl()
    {
        //返回参数$data:

        $rawData = file_get_contents("php://input");
        $channelOrderId = $_POST['prdOrdNo'];
        //$channelMerchantId = $_POST['merId']; //不是通道商户号

        //添加日记
        $payLog = [
            'type' => GPayLogModel::$TYPE_CHANNEL_CALLBACK,
            'productCode' => '912',
        ];
        $payLog['msg'] = self::TITLE.'-返回数据: '.$rawData;
        $this->payLogModel->add($payLog);

        //查找订单
        $orderModel = M("Order");
        $order = $orderModel->where(['channel_order_id' => $channelOrderId, 'pay_tongdao' => self::CODE])->find();
        if(empty($order)){
            $payLog['msg'] = self::TITLE.'-平台订单不存在, 通道订单号='.$channelOrderId.', 返回数据: '.http_build_query($_POST);
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('平台订单不存在, 通道订单号='.$channelOrderId);
        }

        //页面回调商户地址，不能作为支付成功的依据
        $this->callbackMerchant(self::CODE_SUCCESS, $order);
    }

    /**
     * 通道服务器通知
     *  响应报文字段:
     *      YUL1=http%3A%2F%2Fhuapay.cc%2FPay_HeFuBaoWxH5_callbackurl.html%3Forderid%3D20180210122023555649
     *      &YUL2=null&YUL3=null&channelNo=05&ext1=null
     *      &ext2=.%2Fcert%2FHeFuBao%2FS20170907011890.cer
     *      &merchantNo=S20170907011890
     *      &qrCodeURL=http%3A%2F%2Fpaygate.hefupal.cn%2Fpaygate%2Fredirect%2FODAyMTA4NzM3MTA1MzcxNDcxODcy&rtnCode=0000&rtnMsg=null&sign=eLX6C9F%2B7ExZxj3LvY6f%2F2F3qEp8x8jHJrqMmo%2F6McxWK9HsuUN14JZVXNsYOlAy51RZ3CmuCSwXLlpb5sRnuGlwKR%2F5LOfI1hFaeOCIGqPtx9mjwf02mWLycQIk6UwTATO6%2FkPpxJ9e5m6FRKzdxZfLNd1UF%2F%2FhgZMzoa%2Fs%2FOk%3D
     *      &tranCode=YS1003&tranFlow=20180210122023555649&version=v1
     */
    public function notifyurl()
    {
        //返回参数$data:
        $rawData = file_get_contents("php://input");
        $data = $_POST;
        $merchantId = null;
        $productCode = null;
        $outTradeId = null;
        $channelMerchantId = $data['merId'];
        $orderId = $data['prdOrdNo'];
        $amount =strval($data['orderAmount']);

        //添加日记
        $payLog = [
            'merchantId' => $merchantId,
            'productCode' => $productCode,
            'outTradeId' => $outTradeId,
            'channelMerchantId' => $channelMerchantId,
            //'orderId' => $orderId,
            'orderId' => '67899j',
            'type' => GPayLogModel::$TYPE_CHANNEL_NOTIFY,
        ];
        $payLog['msg'] = self::TITLE.'-notify返回的数据: '.$rawData;
        $this->payLogModel->add($payLog);

        //查找订单
        $orderModel = M("Order");
        $order = $orderModel->where(['pay_orderid' => $orderId])->find();
        if(empty($order)){
            $payLog['msg'] = self::TITLE.'-平台订单不存在';
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('fail');
        }

        //验证通道的签名
        $signkey = $order['key'];
        $md5str= $this->sign($data, $signkey);

        if($md5str != $data['signData']){
            $payLog['msg'] = self::TITLE.'-验签失败, 通道签名='.$data['sign'].', 平台签名='.$md5str;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('fail');
        }

        //填充日记
        $payLog['merchantId'] = $order['pay_memberid'];
        $payLog['productCode'] = $order['pay_bankcode'];
        $payLog['outTradeId'] = $order['out_trade_id'];

        //验证是否合法订单
        if (format2Decimal($order["pay_amount"]*100) != format2Decimal($amount)) {
            $payLog['msg'] = self::TITLE.'-订单金额不相等，金额单位为分，通道传的金额='.$amount.', 平台的金额='.$order["pay_amount"]*100;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('fail'); //@todo 金额浮点数暂时不比较，有可能不相等，先运行一段时间在打开
        }
        if ($order["memberid"] != $channelMerchantId) {
            $payLog['msg'] = self::TITLE.'-通道商户号不相等，通道传的通道商户号='.$channelMerchantId.', 平台的通道商户号='.$order["memberid"];
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('fail');
        }

        if ($data['orderStatus'] == '01'){
            $this->EditMoney($orderId, self::CODE, 0);
            exit('success');
        } else {
            //添加日记
            $payLog['msg'] = self::TITLE.'-支付失败';
            $payLog['level'] = GPayLogModel::$LEVEL_WARNING;
            $this->payLogModel->add($payLog);

            exit('fail');
        }
    }

    private function sign(array $data, $signKey){
        ksort($data);
        $string = '';
        foreach ($data as $key => $value){
            if($key != "signData" && $value != ''){
                $string .= $key . '=' . $value . '&';
            }
        }
        $string .= 'key='. $signKey;
        $sign = strtoupper(md5($string));
        return $sign;
    }

    private function http_post_data($url, $data_string) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded;"));
        ob_start();
        curl_exec($ch);
        $return_content = ob_get_contents();
        ob_end_clean();
        $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return array($return_code, $return_content);
    }
}