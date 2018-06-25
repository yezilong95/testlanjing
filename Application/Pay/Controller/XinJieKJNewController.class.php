<?php
namespace Pay\Controller;

use Common\Model\GPayLogModel;

/**
 * 信捷-聚合支付-快捷支付
 *  pay_type=034
 *  支持在线支付的银行和限额：https://static.95516.com/static/help/detail_38.html
 * @package Pay\Controller
 */
class XinJieKJNewController extends PayController
{
    private $CODE = 'XinJieWangGuan';
    private $TITLE = '信捷-聚合支付-快捷支付';
    private $URL = 'http://online.esoonpay.com:28888/gateway/payment';

    //支付
    public function Pay($array)
    {
        header("Content-Type:text/html;charset=UTF-8");
        date_default_timezone_set("Asia/Shanghai");

        $out_trade_id = I('request.pay_orderid'); //商户订单号
        $productCode = I('request.pay_bankcode'); //支付产品编号
        $body = I('request.pay_productname');

        $notifyurl = $this->_site . 'Pay_'.$this->CODE.'_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_'.$this->CODE.'_callbackurl.html'; //返回通知
        $parameter = array(
            'code' => $this->CODE, // 通道名称
            'title' => $this->TITLE,
            'exchange' => 1, // 金额比例
            'gateway' => "",
            'orderid' => "", //系统订单号
            'out_trade_id' => $out_trade_id, //外部商户订单号
            'body'=>$body,
            'channel'=>$array
        );
        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);

        //必要支付金额最小为20元
        if($return["amount"] < 20){
            exit('支付金额最小为15元');
        }
        if($return["amount"] > 200000){
            exit('单笔支付限额200000元');
        }

        $pay_orderid = $return["orderid"]; //系统订单号
        $memberid = $return["memberid"]; //商户号
        $mch_id = $return['mch_id']; //通道商户号
        $amount = $return["amount"]*100;
        $appid = $return['appid']; //通道账号
        $gateway = $return["gateway"];
        $signkey = $return['signkey'];

        $data = array(
            'version' => 'V001',
            'agre_type' => 'T',
            'inst_no' => $appid,
            'merch_id' => $mch_id,
            'pay_type' => '034',
            'commodity_name' => $body,
            'amount' => (string)$amount,
            'back_end_url' => $notifyurl,
            'return_url' => $callbackurl,
            'merch_order_no' => $pay_orderid
        );

        //签名
        $hmac = $this->SignParamsToString($data);
        $bemd5= $hmac."&key=".$signkey;
        $md5str = md5($bemd5);
        $data["sign"]= $md5str;

        $postdata = json_encode($data,JSON_UNESCAPED_UNICODE);

        //添加日记
        $payLog = [
            'merchantId' => $return["memberid"], //商户id
            'productCode' => $productCode, //支付类型
            'outTradeId' => $out_trade_id, //商户订单号
            'channelMerchantId' => $return['mch_id'], //通道商户id
            'orderId' => $return['orderid'], //平台订单号
            'type' => GPayLogModel::$TYPE_REQUEST_CHANNEL, //日记类型
        ];
        $payLog['msg'] = $this->TITLE.'-提交的数据: '.$postdata;
        $this->payLogModel->add($payLog);

        $result = $this->http_post_data($this->URL, $postdata);

        //添加日记
        $payLog['msg'] = $this->TITLE.'-返回的数据: '.urldecode(http_build_query($result));
        $this->payLogModel->add($payLog);

        $resultjsonde = json_decode($result['1'], true);

        //验证通道的签名
        $hmac2 = $this->SignParamsToString($resultjsonde);
        $bemd52 = $hmac2."&key=".$signkey;
        $md5str2 = md5($bemd52);
        if($md5str2 != $resultjsonde['sign']){
            //添加日记
            $payLog['msg'] = $this->TITLE.'-通道验签失败, 通道签名='.$resultjsonde['sign'].', 平台签名='.$md5str2;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('通道验签失败, 商户号='.$memberid.', 商户订单号='.$out_trade_id);
        }

        //保存通道订单号
        $channelOrderId = $resultjsonde['platform_order_no'];
        $orderModel = DM("Order");
        $orderModel->where(['pay_orderid' => $pay_orderid])->save(['channel_order_id' => $channelOrderId]);

        //通道提供收银台
        echo $resultjsonde['html'];
    }

    /**
     * 通道回调, 再回调商户
     */
    public function callbackurl()
    {
        //返回参数$data:
        // resv=&bizType=000000&txnSubType=01&signature=2g7EHnlbeH8R70pDZ2kgGg==
        //&succTime=&settleAmount=&settleCurrency=&txnType=01&settleDate=20180201&version=1.0.0
        //&merResv1=&accessType=0&respMsg=5Lqk5piT5oiQ5Yqf&txnTime=20180201152057&merId=929010095023148
        //&currency=CNY&respCode=1001&channelId=chinaGpay&txnAmt=0000000000001500&signMethod=MD5
        //&merOrderId=NC01802010230124

        $rawData = file_get_contents("php://input");

        $channelOrderId = $_POST['merOrderId'];
        //$channelMerchantId = $_POST['merId']; //不是通道商户号

        //添加日记
        $payLog = [
            'type' => GPayLogModel::$TYPE_CHANNEL_CALLBACK,
            'productCode' => '912',
        ];
        $payLog['msg'] = $this->TITLE.'-返回数据: '.$rawData;
        $this->payLogModel->add($payLog);

        //查找订单
        $orderModel = M("Order");
        $order = $orderModel->where(['channel_order_id' => $channelOrderId, 'pay_tongdao' => $this->CODE])->find();
        if(empty($order)){
            $payLog['msg'] = $this->TITLE.'-平台订单不存在, 通道订单号='.$channelOrderId.', 返回数据: '.http_build_query($_POST);
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('平台订单不存在, 通道订单号='.$channelOrderId);
        }

        //页面回调商户地址，不能作为支付成功的依据
        $this->callbackMerchant(self::CODE_SUCCESS, $order);
    }

    //异步通知
    public function notifyurl()
    {
        //返回参数$data:
        // {"retmsg":"成功","order_datetime":"2018-02-01 15:20:57","version":"V001",
        //"pay_time":"2018-02-01 15:22:12","merch_id":"100000560000057","is_credit":"",
        //"up_channel_order_no":"","inst_no":"10000056","wallet_id":"","remark":"","pay_type":"034",
        //"retcode":"00","platform_order_no":"NC01802010230124","agre_type":"",
        //"sign":"eec4dce4d74d0369e220602db8720e98","amount":"1500","merch_order_no":"20180201152055555554"}

        //$data = $GLOBALS['HTTP_RAW_POST_DATA'];
        $rawData = file_get_contents("php://input");

        $data = $this->object_to_array(json_decode($rawData, true));
        $merchantId = null;
        $productCode = null;
        $outTradeId = null;
        $channelMerchantId = $data['merch_id'];
        $orderId = $data['merch_order_no'];
        $amount = $data['amount'];

        //添加日记
        $payLog = [
            'merchantId' => $merchantId,
            'productCode' => $productCode,
            'outTradeId' => $outTradeId,
            'channelMerchantId' => $channelMerchantId,
            'orderId' => $orderId,
            'type' => GPayLogModel::$TYPE_CHANNEL_NOTIFY,
        ];
        $payLog['msg'] = $this->TITLE.'-返回的数据: '.$rawData;
        $this->payLogModel->add($payLog);

        //查找订单
        $orderModel = M("Order");
        $order = $orderModel->where(['pay_orderid' => $orderId])->find();
        if(empty($order)){
            $payLog['msg'] = $this->TITLE.'-平台订单不存在';
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('fail');
        }

        //验证通道的签名
        $signkey = $order['key'];
        $hmac = $this->SignParamsToString($data);
        $bemd5 = $hmac."&key=".$signkey;
        $md5str = md5($bemd5);
        if($md5str != $data['sign']){
            $payLog['msg'] = $this->TITLE.'-验签失败, 通道签名='.$data['sign'].', 平台签名='.$md5str;
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
            $payLog['msg'] = $this->TITLE.'-订单金额不相等，金额单位为分，通道传的金额='.$amount.', 平台的金额='.$order["pay_amount"]*100;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('fail'); //@todo 金额浮点数暂时不比较，有可能不相等，先运行一段时间在打开
        }
        if ($order["memberid"] != $channelMerchantId) {
            $payLog['msg'] = $this->TITLE.'-通道商户号不相等，通道传的通道商户号='.$channelMerchantId.', 平台的通道商户号='.$order["memberid"];
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('fail');
        }

        if ($data['retcode'] == '00'){
            $this->EditMoney($orderId, $this->CODE, 0);
            exit('success');
        } else {
            //添加日记
            $payLog['msg'] = $this->TITLE.'-支付失败';
            $payLog['level'] = GPayLogModel::$LEVEL_WARNING;
            $this->payLogModel->add($payLog);

            exit('fail');
        }
    }

    /** *利用google api生成二维码图片
     * $content：二维码内容参数
     * $size：生成二维码的尺寸，宽度和高度的值
     * $lev：可选参数，纠错等级
     * $margin：生成的二维码离边框的距离
     */
    private function object_to_array($obj) {
        $obj = (array)$obj;
        foreach ($obj as $k => $v) {
            if (gettype($v) == 'resource') {
                return;
            }
            if (gettype($v) == 'object' || gettype($v) == 'array') {
                $obj[$k] = (array)object_to_array($v);
            }
        }
        return $obj;
    }
    private function SignParamsToString($params) {
        $sign_str = '';
        // 排序
        ksort($params);

        $buff = "";
        foreach ($params as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }

    private function getBytes($string) {
        $bytes = array();
        for($i = 0; $i < strlen($string); $i++){
            $bytes[] = ord($string[$i]);
        }
        return $bytes;
    }
    private function http_post_data($url, $data_string) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Content-Type: application/json; charset=utf-8",
                "Content-Length: " . strlen($data_string))
        );
        ob_start();
        curl_exec($ch);
        $return_content = ob_get_contents();
        ob_end_clean();
        $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return array($return_code, $return_content);
    }

    function create_erweima($content, $size = '200', $lev = 'L', $margin= '0') {
        $content = urlencode($content);
        $image = 'http://chart.apis.google.com/chart?chs='.$size.'x'.$size.'&amp;cht=qr&chld='.$lev.'|'.$margin.'&amp;chl='.$content;
        return $image;
    }

}