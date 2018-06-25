<?php
namespace Pay\Controller;

use Common\Model\GPayLogModel;

/**
 * 杉德-QQ扫码
 * Class ShanDeQQSmController
 * @package Pay\Controller
 */
class ShanDeQQSmController extends PayController
{
    private $CODE = 'ShanDeQQSm';
    private $TITLE = '杉德-QQ扫码';

    public function Pay($array)
    {
        $orderid = I("request.pay_orderid", ''); //商户订单号
        $returnType = I('request.return_type', 'html'); //返回值：html，json
        $body = I('request.pay_productname', ''); //商品名称
        $productCode = I('request.pay_bankcode'); //支付产品编号

        $parameter = [
            'code' => 'ShanDeQQSm',
            'title' => '杉德-QQ扫码',
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

        $memberid = $return["memberid"]; //商户号
      
        //如果生成错误，自动跳转错误页面
        $return["status"] == "error" && $this->showmessage($return["errorcontent"]);
        
        //跳转页面，优先取数据库中的跳转页面
        $return["notifyurl"] || $return["notifyurl"] = $this->_site . 'Pay_ShanDeQQSm_notifyurl.html';
        
        $return['callbackurl'] || $return['callbackurl'] = $this->_site . 'Pay_ShanDeQQSm_callbackurl.html';
        
        $data = [
            'app_id' => $return['appid'],
            'timestamp' => date('Y-m-d H:i:s', time()),
            'method' => 'charge.create',
            'order_no'  => $return['orderid'],
            'amount'  => $return['amount'],
            'subject'  => $orderid,
            'mer_id'  => $return['mch_id'],
            'notify_url'  => $return['notifyurl'],
            'channel' => 'qpay_qr',
        ];
        
        $data['sign'] = strtoupper( md5Sign($data, $return['appsecret'], '') );
        $data['sign_type'] = 'md5';
        $postData = http_build_query($data);

        //添加日记
        $payLog = [
            'merchantId' => $return["memberid"],
            'productCode' => $productCode,
            'outTradeId' => $orderid,
            'channelMerchantId' => $return['mch_id'],
            'orderId' => $return['orderid'],
            'type' => GPayLogModel::$TYPE_REQUEST_CHANNEL,
        ];
        $payLog['msg'] = $this->TITLE.'-提交的数据: '.$postData;
        $this->payLogModel->add($payLog);

        $res = curlPost($return['gateway'], $postData);

        //添加日记
        //通道返回数据$res = {"code":0,
        //"data":{"status":10,"time_created":"2018-02-24 18:09:06",
        //  "charge_id":"ch_ee0ffb4b40a3d2cee817ec609fdfa0c4","order_no":"2018022418073071008179",
        //  "credential":{
        //      "qpay_qr":{"qr_imgurl":"https://gateway.qinyan.cc/qrimg?data=https%3A%2F%2Fqpay.qq.com%2Fqr%2F51d09bab",
        //      "qr_code":"https://qpay.qq.com/qr/51d09bab"}},"amount":1000,"failure_code":"0","failure_msg":"00,成功",
        //  "paid":0,"mer_id":"110102742845","settle_account_name":"李见平","settle_account_numb":"50131000576259954",
        //  "app_id":"21110001122","transaction_no":"00000000000000000000937535","channel":"qpay_qr"},
        //  "message":"success"}
        $payLog['msg'] = $this->TITLE.'-返回的数据: '.$res;
        $this->payLogModel->add($payLog);

        $res = json_decode($res, true);

        //验证通道签名
        //返回时通道没有再次签名，此处不要验证
//        $channelSign = $res['sign'];
//        $channelSignData = $res;
//        unset($channelSignData['sign']);
//        unset($channelSignData['sign_type']);
//        $mySign = strtoupper( md5Sign($channelSignData, $return['appsecret'], '') );
//        if ($channelSign != $mySign){
//            //添加日记
//            $payLog['msg'] = $this->TITLE.'-通道验签失败, 通道签名='.$res['sign'].', 平台签名='.$mySign;
//            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
//            $this->payLogModel->add($payLog);
//
//            $msg = '通道验签失败, 商户号='.$memberid.', 商户订单号='.$orderid;
//            $this->showmessage($msg);
//        }

       if($returnType == 'json') {
           if ($res['code'] == '0' && $res['data']['credential']['qpay_qr']['qr_code']) {
               $url = $res['data']['credential']['qpay_qr']['qr_code'];
               echo '{"code":"1","qrcode":"' . $url . '","desc":"SUCCESS"}';
           } else {
               echo '{"code":"0","qrcode":"","desc":"' . $res['message'] . '"}';
           }
       } else {
           if ($res['code'] == '0' && $res['data']['credential']['qpay_qr']['qr_code']) {
               $url = $res['data']['credential']['qpay_qr']['qr_code'];
               import("Vendor.phpqrcode.phpqrcode", '', ".php");
               $QR = "Uploads/codepay/" . $return["orderid"] . ".png";//已经生成的原始二维码图
               \QRcode::png($url, $QR, "L", 20);
               $this->assign("imgurl", $this->_site . $QR);
               $this->assign('params', $return);
               $this->assign('orderid', $return['orderid']);
               $this->assign('money', sprintf('%.2f', $return['amount'] / 100));
               $this->display("WeiXin/qq");
           } else {
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
	}

	 // 服务器点对点返回
    public function notifyurl(){
        /*sign=133FE63D96FBE88234232898DCD0DD85&sign_type=md5&data=%7B%22status%22%3A20%2C%22fee_rate%22%3A%220000%22%2C%22time_created%22%3A%222018-03-01+20%3A56%3A49%22%2C%22charge_id%22%3A%22ch_9ada49fedb685d23c2366f69e47ad579%22%2C%22order_no%22%3A%222018030120564943469030%22%2C%22failure_msg%22%3A%22%E6%88%90%E5%8A%9F%22%2C%22failure_code%22%3A%2200%22%2C%22amount%22%3A4000%2C%22fee%22%3A0%2C%22paid%22%3A1%2C%22mer_id%22%3A%22110102742845%22%2C%22app_id%22%3A%2221110001122%22%2C%22transaction_no%22%3A%2200000000000000000001076133%22%2C%22channel%22%3A%22jdpay_qr%22%2C%22amount_settle%22%3A0%7D&method=payment.result*/

        $rawData = urldecode(file_get_contents("php://input"));
        $content = I('post.','');
        $data = json_decode(htmlspecialchars_decode($content['data']) , true);

        $merchantId = null;
        $productCode = null;
        $outTradeId = null;
        $channelMerchantId = $data['mer_id'];
        $orderId = $data["order_no"];
        $amount =strval($data['amount']);

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
            $this->EditMoney($data["order_no"], '', 0);

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