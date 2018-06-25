<?php
namespace Pay\Controller;

use Common\Model\GPayLogModel;

/**
 * 米付-QQ扫码
 * 单笔限额 10-5000
 * Class MiFuQQScanController
 * @package Pay\Controller
 * @author 叶子龙
 */
class MiFuQQScanController extends PayController
{
    private $CODE = 'MiFuQQScan';
    private $TITLE = '米付-QQ扫码';

    public function Pay($array)
    {
        header("Content-Type:text/html;charset=UTF-8");
        $out_trade_id = I("request.pay_orderid", ''); //商户订单号
        $returnType = I('request.return_type', 'html'); //返回值：html，json
        $body = I('request.pay_productname', 'VIP充值'); //商品名称
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

        /*if($pay_amount < 2){
            exit('支付金额最小为2元');
        }*/
        if($pay_amount > 1000){
            exit('单笔支付限额1000元');
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
        $amount = $return["amount"]*100;
        $appid = $return['appid']; //通道账号
        $gateway = $return["gateway"];
        $signkey = $return['signkey'];

        $data = array(
            'agentCode'            => $appid,
            'merchantCode'            => $mch_id,
            'downOrderNum'            => $pay_orderid,
            'totalAmount'            => $amount,
            'channelCode'            => 'M005',
            'interfaceType'            => 'P006',
            'payType'            => 'T003',
            'goodsName'            => $body,
            'ip'            => $_SERVER["REMOTE_ADDR"],
            'callBackUrl'            => $return["notifyurl"],
            'successUrl'            => $return['callbackurl'],
            'describe'            => $body.'001',
            'extendedField'            => $body.'002',

        );

        //签名
        $data['sign'] = md5($signkey.$amount.$pay_orderid);
        $postData = json_encode($data,JSON_UNESCAPED_UNICODE);

        $result = $this->http_post_data($gateway, $postData);
        $resultjsonde = json_decode($result['1'],true);
        $url =$resultjsonde['data']['payUrl'];

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

        $payLog['msg'] = $this->TITLE.'-返回的数据: '.$result['1'];
        $this->payLogModel->add($payLog);


       if($returnType == 'json') {
           if ($resultjsonde['code'] == '20000') {
               echo '{"code":"1","qrcode":"' . $url . '","desc":"SUCCESS"}';
           } else {
               echo '{"code":"0","qrcode":"","desc":"' . $resultjsonde['desc'] . '"}';
           }
       } else {
           if ($resultjsonde['code'] == '20000') {
               import("Vendor.phpqrcode.phpqrcode", '', ".php");
               $QR = "Uploads/codepay/" . $return["orderid"] . ".png";//已经生成的原始二维码图
               \QRcode::png($url, $QR, "L", 20);
               $this->assign("imgurl", $this->_site . $QR);
               $this->assign('params', $return);
               $this->assign('orderid', $return['orderid']);
               $this->assign('money', sprintf('%.2f', $return['amount']));
               $this->display("WeiXin/qq");
           } else {
               $this->showmessage($resultjsonde['desc']);
           }
       }
    }


	public function callbackurl(){
        $rawData = http_build_query($_REQUEST);
        $orderid = $_REQUEST["orderid"];

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
        /*米付-QQ扫码-notify返回的数据: {"totalAmount":"0.01","downOrderNum":"LJCZ2018062318084382700565","flowNumber":null,"orderNum":"201806230000000040","sign":"a4af80e3268da4c6cc5fd8e4cb05ff26","responseCode":"S","responseMsg":"成功","merchantCode":"2018062214454","agentCode":"201806130001","time":1529748537311}*/

        $rawData = file_get_contents("php://input");
        $data = json_decode($rawData,true);
//        $rawData = json_encode($data);
        $merchantId = null;
        $productCode = null;
        $outTradeId = null;
        $channelMerchantId = $data['merchantCode'];
        $orderId = $data["downOrderNum"];
        $amount =strval($data['totalAmount']);

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
        $newSign = md5($channel['signkey'].$data['totalAmount'].$data['downOrderNum']);

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

        if($data['responseCode'] == 'S'){
            $this->EditMoney($orderId, '', 0);
            //添加日记
            $payLog['msg'] = $this->TITLE.'-支付成功, 修改订单状态为: 成功,未返回';
            $this->payLogModel->add($payLog);
            exit('000000');
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
    
}
?>