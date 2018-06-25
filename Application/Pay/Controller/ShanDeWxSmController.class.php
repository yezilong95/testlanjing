<?php
namespace Pay\Controller;

use Common\Model\GPayLogModel;

class ShanDeWxSmController extends PayController
{
    private $CODE = 'ShanDeWxSm';
    private $TITLE = '杉德-微信扫码/即京东扫码';
    private $TRADE_TYPE = '';
   
    public function Pay($array)
    {
        $out_trade_id = I('request.pay_orderid'); //商户订单号
        $productCode = I('request.pay_bankcode'); //支付产品编号
        $returnType = I('request.return_type', 'html'); //返回值：html，json
        $orderid = I("request.pay_orderid", '');
        $body = I('request.pay_productname', '');

        $parameter = [
            'code' => 'ShanDeWxSm',
            'title' => '杉德支付（微信扫码）',
            'exchange' => 100, // 金额比例
            'gateway' => '',
            'orderid'=>'',
            'out_trade_id' => $orderid, //外部订单号
            'channel'=>$array,
            'body'=>$body
        ];

        //支付金额
        $pay_amount = I("request.pay_amount", 0);

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);
      
        //如果生成错误，自动跳转错误页面
        $return["status"] == "error" && $this->showmessage($return["errorcontent"]);
        
        //跳转页面，优先取数据库中的跳转页面
        $return["notifyurl"] || $return["notifyurl"] = $this->_site . 'Pay_ShanDeWxSm_notifyurl.html';
        
        $return['callbackurl'] || $return['callbackurl'] = $this->_site . 'Pay_ShanDeWxSm_callbackurl.html';
        
        $data = [
            'app_id' => $return['appid'],
            'timestamp' => date('Y-m-d H:i:s', time()),
            'method' => 'charge.create',
            'order_no'  => $return['orderid'],
            'amount'  => $return['amount'],
            'subject'  => $orderid,
            'mer_id'  => $return['mch_id'],
            'notify_url'  => $return['notifyurl'],
            'channel' => 'jdpay_qr', //京东扫码
        ];
        
        $data['sign'] = strtoupper( md5Sign($data, $return['appsecret'], '') );
        $data['sign_type'] = 'md5';
        $httpQuery = http_build_query($data);

        //添加支付日记
        $payLog = [
            'merchantId' => $return["memberid"],
            'productCode' => $productCode,
            'outTradeId' => $out_trade_id,
            'channelMerchantId' => $return['mch_id'],
            'orderId' => $return['orderid'],
            'type' => GPayLogModel::$TYPE_REQUEST_CHANNEL,
        ];
        $payLog['msg'] = $this->TITLE.'-提交的数据: '.$httpQuery;
        $this->payLogModel->add($payLog);

        $res = curlPost($return['gateway'], $httpQuery);

        //添加支付日记
        $payLog['msg'] = $this->TITLE.'-返回的数据: '.$res;
        $this->payLogModel->add($payLog);

        $res = json_decode($res, true);


       
//        if($res['code'] == '0' && $res['data']['credential']['jdpay_qr']['qr_code']){
//            $url = $res['data']['credential']['jdpay_qr']['qr_code'];
//            echo '{"code":"1","qrcode":"'.$url.'","desc":"SUCCESS"}';
//        }else{
//            echo '{"code":"0","qrcode":"","desc":"'.$res['message'].'"}';
//        }
//die();
        if($returnType == 'json') {

        if($res['code'] == '0' && $res['data']['credential']['jdpay_qr']['qr_code']){
            $url = $res['data']['credential']['jdpay_qr']['qr_code'];
            echo '{"code":"1","qrcode":"'.$url.'","desc":"SUCCESS"}';
        }else{
            echo '{"code":"0","qrcode":"","desc":"'.$res['message'].'"}';
        }
        }else{
            if($res['code'] == '0' && $res['data']['credential']['jdpay_qr']['qr_code']){
                $url = $res['data']['credential']['jdpay_qr']['qr_code'];
                import("Vendor.phpqrcode.phpqrcode",'',".php");
                $QR = "Uploads/codepay/". $return["orderid"] . ".png";//已经生成的原始二维码图
                \QRcode::png($url, $QR, "L", 20);
                $this->assign("imgurl", $this->_site.$QR);
                $this->assign('params',$return);
                $this->assign('orderid',$return['orderid']);
                $this->assign('money',sprintf('%.2f', $return['amount']/100));
                $this->display("WeiXin/ShanDeweixin");
            }else{
                $this->showmessage($res['message']);
            }
        }



    }


	public function callbackurl(){
        $rawData = http_build_query($_REQUEST);
        $orderid = $_REQUEST["orderid"];

        //添加日记
        $payLog = [
            'type' => GPayLogModel::$TYPE_CHANNEL_CALLBACK,
            'productCode' => '912',
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

        die();
        #原来的
        $Order = M("Order");
        $pay_status = $Order->where("pay_orderid = '".$_REQUEST["orderid"]."'")->getField("pay_status");
        if($pay_status <> 0){
            $this->EditMoney($_REQUEST["orderid"], '', 1);
        }else{
            exit("error");
        }
	}

	 // 服务器点对点返回
    public function notifyurl(){
        /*
         * sign=67EA2941648D3634D32E57DB0ED7961D&sign_type=md5&
         * data={"status":20,"fee_rate":"0000","time_created":"2018-03-04 17:35:03",
         *      "charge_id":"ch_ade8740961f7373d259ad51ab8f31e3e","order_no":"2018030417350297612009",
         *      "failure_msg":"成功","failure_code":"00","amount":10000,"fee":0,"paid":1,"mer_id":"110102742845",
         *      "app_id":"21110001122","transaction_no":"00000000000000000001140937","channel":"jdpay_qr",
         *      "amount_settle":0}
         * &method=payment.result
         */

        $rawData = urldecode(file_get_contents("php://input"));
        $content = I('post.','');
        $data = json_decode(htmlspecialchars_decode($content['data']) , true);

        $merchantId = null;
        $productCode = null;
        $outTradeId = null;
        $channelMerchantId = $data['mer_id'];
        $orderId = $data["order_no"];
        $amount = strval($data['amount']);

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
            $payLog['msg'] = $this->TITLE.'-平台订单不存在, 平台订单号='.$orderId.', 返回数据: '.$rawData;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('{"code": 999,"message": "接收失败"}');
        }

        //验证通道的签名
        $channel = M('ChannelAccount')->where(['mch_id'=>$order['memberid']])->find();
        $newSign = strtoupper( md5($channel['appsecret'] . htmlspecialchars_decode($content['data']) . $channel['appsecret']) );
        if($content['sign'] != $newSign){
            $payLog['msg'] = $this->TITLE.'-验签失败, 通道签名='.$content['sign'].', 平台签名='.$newSign;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('{"code": 999,"message": "接收失败"}');
        }

        //填充日记
        $payLog['merchantId'] = $order['pay_memberid'];
        $payLog['productCode'] = $order['pay_bankcode'];
        $payLog['outTradeId'] = $order['out_trade_id'];

        //验证是否合法订单
        $orderAmount = $order["pay_amount"]*100;
        if (format2Decimal($orderAmount) != format2Decimal($amount)) {
            $payLog['msg'] = $this->TITLE.'-订单金额不相等，金额单位为分，通道传的金额='.$amount.', 平台的金额='.$orderAmount;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            //exit('{"code": 999,"message": "接收失败"}'); //@todo 金额浮点数暂时不比较，有可能不相等，先运行一段时间在打开
        }
        if ($order["memberid"] != $channelMerchantId) {
            $payLog['msg'] = $this->TITLE.'-通道商户号不相等，通道传的通道商户号='.$channelMerchantId.', 平台的通道商户号='.$order["memberid"];
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('{"code": 999,"message": "接收失败"}');
        }

        if($data['status'] == '20'){
            $this->EditMoney($orderId, $this->CODE, 0);

            //添加日记
            $payLog['msg'] = $this->TITLE.'-支付成功, 修改订单状态为: 成功,未返回';
            $this->payLogModel->add($payLog);
            exit('{"code": 0,"message": "接收成功"}');
        }else {
            //添加日记
            $payLog['msg'] = $this->TITLE.'-支付失败3';
            $payLog['level'] = GPayLogModel::$LEVEL_WARNING;
            $this->payLogModel->add($payLog);
            exit('{"code": 999,"message": "接收失败"}');
        }
    }
    
}
?>