<?php
namespace Pay\Controller;

use Common\Model\GPayLogModel;

/**
 * 启付通-支付宝H5
 * 单笔限额 10-5000
 * Class QiFuTongAlipayH5Controller
 * @package Pay\Controller
 * @author 叶子龙
 * d1结算 单笔1-300元,成功率5成左右
 */
class QiFuTongAlipayH5Controller extends PayController
{
    private $CODE = 'QiFuTongAlipayH5';
    private $TITLE = '启付通-支付宝H5';

    public function Pay($array)
    {
        header("Content-Type:text/html;charset=UTF-8");
        $out_trade_id = I("request.pay_orderid", ''); //商户订单号
        $returnType = I('request.return_type', 'html'); //返回值：html，json
        $body = I('request.pay_productname', ''); //商品名称
        $productCode = I('request.pay_bankcode'); //支付产品编号

        $parameter = [
            'code' => $this->CODE,
            'title' => $this->TITLE,
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid'=>'',
            'out_trade_id' => $out_trade_id, //外部订单号
            'channel'=>$array,
            'body'=>$body
        ];

        //支付金额
        $pay_amount = I("request.pay_amount", 0);

        if($pay_amount < 10){
            exit('支付金额最小为10元');
        }
        if(floor($pay_amount) != $pay_amount){
            exit('支付金额必须为整数！');
        }
        $str = substr($pay_amount,-1);
        if($str != 0){
            exit('支付金额必须为10的整数倍！');
        }
        if($pay_amount >= 2999){
            exit('单笔支付限额2999元');
        }
        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);
        $memberid = $return["memberid"]; //商户号
        //如果生成错误，自动跳转错误页面
        $return["status"] == "error" && $this->showmessage($return["errorcontent"]);
        
        //跳转页面，优先取数据库中的跳转页面
        $return["notifyurl"] || $return["notifyurl"] = $this->_site . 'Pay_'.$this->CODE.'_notifyurl.html';
        $return['callbackurl'] || $return['callbackurl'] = $this->_site . 'Pay_'.$this->CODE.'_callbackurl.html';

        $pay_orderid = $return["orderid"]; //系统订单号
        $memberid = $return["memberid"]; //商户号
        $mch_id = $return['mch_id']; //通道商户号
        $amount = $return["amount"];
        $appid = $return['appid']; //通道账号
        $gateway = $return["gateway"];
        $signkey = $return['signkey'];

        $data = array(
            'amount'            => $pay_amount*100, // 订单总金额，整形，此处单位为分
            'orderNo'       => $pay_orderid,
            'merchantNo'        => $mch_id, // 商户号
            'callBackUrl'       => $return["callbackurl"],
            'notifyUrl'         => $return["notifyurl"], //接收支付结果异步通知回调地址，PC 网站必填
            'currency'          => 'CNY',
            'goodsTitle'          => 'iphone',
            'payType'           => "alipay_h5", //交易类型: 微信H5 wechat_h5
            'appNo'             => $appid, //商户应用编号,
            'timestamp'         => date('YmdHiZ'),
            'clientIp'         => $_SERVER["REMOTE_ADDR"],
        );

        //签名
        $signstr = $this->_createSign($data);
        $data['sign'] = md5($signstr.$signkey);
        $postData = http_build_query($data);
        $result = createForm($gateway, $data);

        /*array(3) {
        ["code"] => string(6) "000000"
        ["errMsg"] => NULL
        ["data"] => array(6) {
            ["orderNo"] => string(26) "LJCZ2018040912072178958962"
            ["createTime"] => string(14) "20180409120721"
            ["payNo"] => string(21) "PAY201804090003274976"
            ["codeUrl"] => string(31) "https://qpay.qq.com/qr/65ca895f"
            ["remark"] => NULL
            ["sign"] => string(32) "1d8e08fea4d791f3d99885b754943ef6"
  }
}*/
        //添加日记
        $payLog = [
            'merchantId' => $return["memberid"],
            'productCode' => $productCode,
            'outTradeId' => $out_trade_id,
            'channelMerchantId' => $return['mch_id'],
            'orderId' => $return['orderid'],
            'type' => GPayLogModel::$TYPE_REQUEST_CHANNEL,
        ];
        $payLog['msg'] = $this->TITLE.'-提交的数据: '.$postData;
        $this->payLogModel->add($payLog);
        //$res = curlPost($return['gateway'], $postData);
        //添加日记
        //通道返回数据"{"version":"3.0","status":"1","message":"","ordernumber":"1520229546","paymoney":"10","qrurl":"https%3a%2f%2fqpay.qq.com%2fqr%2f5366e068"}"
        //{"status":"error","msg":null,"data":[]}
        $payLog['msg'] = $this->TITLE.'-返回的数据: '.$result;
        $this->payLogModel->add($payLog);

        echo $result;
    }


	public function callbackurl(){
        $rawData = http_build_query($_REQUEST);
        $orderid = $_REQUEST["orderNo"];

        //添加日记
        $payLog = [
            'type' => GPayLogModel::$TYPE_CHANNEL_CALLBACK,
            'productCode' => '908',
        ];
        $payLog['msg'] = $this->TITLE.'-返回数据: '.$rawData;
        $this->payLogModel->add($payLog);

        //查找订单
        $orderModel = M("Order");
        $order = $orderModel->where(['pay_orderid'=>$orderid])->find();
        if(empty($order)){
            $payLog['msg'] = $this->TITLE.'-平台订单不存在, 平台订单号='.$orderid.', 返回数据: '.$rawData;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit($payLog['msg']);
        }

        if($order['pay_status'] <> 0){
            //添加日记
            $payLog['msg'] = $this->TITLE.'-成功, 当前订单状态='.$order['pay_status'];
            $this->payLogModel->add($payLog);

            $this->EditMoney($orderid, '', 1);
        }else{
            //添加日记
            $payLog['msg'] = $this->TITLE.'-失败, 当前订单状态='.$order['pay_status'];
            $this->payLogModel->add($payLog);
            exit("error");
        }
	}

	 // 服务器点对点返回
    public function notifyurl(){
        /*启付通-QQ扫码-notify返回的数据: amount=1000&orderNo=WP2018031712214977747241&transTime=20180317122207&sign=1c03d8bea365a359dce7e68c380c773e&merchantNo=ME0000000002&status=1*/

        $data = I('post.','');
        $rawData = http_build_query($data);
//        $rawData = json_encode($data);
        $merchantId = null;
        $productCode = null;
        $outTradeId = null;
        $channelMerchantId = $data['merchantNo'];
        $orderId = $data["orderNo"];
        $amount =strval($data['amount']/100);

        //添加日记
        $payLog = [
            'merchantId' => $merchantId,
            'productCode' => $productCode,
            'outTradeId' => $outTradeId,
            'channelMerchantId' => $channelMerchantId,
            'orderId' => $orderId,
            'type' => GPayLogModel::$TYPE_CHANNEL_NOTIFY,
        ];
        $payLog['msg'] = $this->TITLE.'-notify返回的数据: '.$rawData;
        $this->payLogModel->add($payLog);

        //查找订单
        $orderModel = M("Order");
        $order = $orderModel->where(['pay_orderid' => $orderId])->find();
        if(empty($order)){
            $payLog['msg'] = $this->TITLE.'-平台订单不存在, 平台订单号='.$orderId.', 返回数据: '.$rawData;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('fail1');
        }

        //验证通道的签名
        $channel = M('ChannelAccount')->where(['mch_id'=>$order['memberid']])->find();
        $signstr = $this->_createSign($data);
        $newSign = md5($signstr.$channel['signkey']);

        if($data['sign'] != $newSign){
            $payLog['msg'] = $this->TITLE.'-验签失败, 通道签名='.$data['sign'].', 平台签名='.$newSign;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('{"code": 999,"message": "接收失败"}');
        }

        //填充日记
        $payLog['merchantId'] = $order['pay_memberid'];
        $payLog['productCode'] = $order['pay_bankcode'];
        $payLog['outTradeId'] = $order['out_trade_id'];

        //验证是否合法订单
        $orderAmount =strval($order["pay_amount"]);
        if ($orderAmount != $amount) {
            $payLog['msg'] = $this->TITLE.'-订单金额不相等，金额单位为分，通道传的金额='.$amount.', 平台的金额='.$orderAmount;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            //die("fail"); //@todo 金额浮点数暂时不比较，有可能不相等，先运行一段时间在打开
        }
        if ($order["memberid"] != $channelMerchantId) {
            $payLog['msg'] = $this->TITLE.'-通道商户号不相等，通道传的通道商户号='.$channelMerchantId.', 平台的通道商户号='.$order["memberid"];

            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            die("fail2");
        }

        if($data['status'] == '1'){
            $this->EditMoney($orderId, '', 0);
            //添加日记
            $payLog['msg'] = $this->TITLE.'-支付成功, 修改订单状态为: 成功,未返回';
            $this->payLogModel->add($payLog);
            exit('SUCCESS');
        }else {
            //添加日记
            $payLog['msg'] = $this->TITLE.'-支付失败3';
            $payLog['level'] = GPayLogModel::$LEVEL_WARNING;
            $this->payLogModel->add($payLog);
            exit('fail3');
        }
    }

    protected function _createSign($params){
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
    public function send_post($url, $post_data) {

        $postdata = json_encode($post_data);

        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded\r\n',
                'content' => $postdata,
                'timeout' => 60 * 60 // 超时时间（单位:s）
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        return $result;
    }
    
}
?>