<?php
namespace Pay\Controller;

use Common\Model\GPayLogModel;

/**
 * 稳付宝-QQ扫码
 * Class WenFuBaoQQScanController
 * @package Pay\Controller
 * author 尚军邦
 */
class WenFuBaoQQScanController extends PayController
{
    //注：该接口支付跟异步通知使用的秘钥不同，异步通知的秘钥用后台appid ，order表中的参数account
    private $CODE = 'WenFuBaoQQScan';
    private $TITLE = '稳付宝-QQ扫码';
    public function Pay($array)
    {

        header("Content-Type:text/html;charset=UTF-8");
        $out_trade_id = I("request.pay_orderid", ''); //商户订单号
        $returnType = I('request.return_type', 'html'); //返回值：html，json
        $body = I('request.pay_productname', '红苹果'); //商品名称
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

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);

        $memberid = $return["memberid"]; //商户号

        //如果生成错误，自动跳转错误页面
        $return["status"] == "error" && $this->showmessage($return["errorcontent"]);

        //跳转页面，优先取数据库中的跳转页面
        $return["notifyurl"] || $return["notifyurl"] = $this->_site . 'Pay_'.$this->CODE.'_notifyurl.html';
        $return['callbackurl'] || $return['callbackurl'] = $this->_site . 'Pay_'.$this->CODE.'_callbackurl.html';

        $data = [
            'trxType' => 'PROXY_SCANCODE',
            'r1_paymentType' => 'QQPAY_SCANCODE',
            'r2_merchantNo' => $return['mch_id'],
            'r3_orderNum' => $return['orderid'],
            'r4_amount' => $pay_amount,
            'r8_callbackUrl' => $return['callbackurl'],
            'r9_serverCallbackUrl' =>  $return["notifyurl"],

        ];
        $md5str= $this->SignParamsToString($data);
        $hmac = md5("#".$data['trxType']."#".$md5str."#".$return['signkey']);
        $data["sign"]= $hmac;
        $postData = http_build_query($data);

        $payLog['msg'] = $this->TITLE.'-提交的数据: '.$postData;
        $this->payLogModel->add($payLog);
        $result = curlPost($return['gateway'], $postData);
        $res = json_decode($result,JSON_UNESCAPED_UNICODE);

        //添加日记;
        $payLog = [
            'merchantId' => $return["memberid"],
            'productCode' => $productCode,
            'outTradeId' => $out_trade_id,
            'channelMerchantId' => $return['mch_id'],
            'orderId' => $return['orderid'],
            'type' => GPayLogModel::$TYPE_REQUEST_CHANNEL,
        ];

        //添加日记
        $payLog['msg'] = $this->TITLE.'-返回的数据: '.$result;
        $this->payLogModel->add($payLog);

        if ($returnType == 'json') {
            if ($res['retCode'] == '0000') {
                $url = $res["r9_payinfo"];
                echo '{"code":"1","qrcode":"' . $url . '","desc":"SUCCESS"}';
            } else {
                echo '{"code":"0","qrcode":"","desc":"' . $res['retMsg'] . '"}';
            }
        } else {
            if ($res['retCode'] == '0000') {
                $url = $res["r9_payinfo"];
                import("Vendor.phpqrcode.phpqrcode", '', ".php");
                $QR = "Uploads/codepay/" . time()."dsc" . ".png";//已经生成的原始二维码图
                \QRcode::png($url, $QR, "L", 20);
                $this->assign("imgurl", $this->_site . $QR);
                $this->assign('params', $return);
                $this->assign('orderid', $return['orderid']);
                $this->assign('money', sprintf('%.2f', $pay_amount));
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
            'productCode' => '905',
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
        /*r7_completeDate=2018-04-24+18%3A24%3A26&r2_orderNumber=Z2018042418240669789891&r8_orderStatus=SUCCESS&r1_merchantNo=KY0000000316&sign=87a7abf395d28c6731e2b9dcef35474f&r3_amount=0.10&trxType=OnlineQuery&retCode=0000*/

        $data = I('request.','');
        $rawData = http_build_query($data);
       // $rawData = json_encode($data);
        $merchantId = null;
        $productCode = null;
        $outTradeId = null;
        $channelMerchantId = $data['r1_merchantNo'];
       $orderId = $data["r2_orderNumber"];
        $amount =strval($data['r3_amount']);

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

        $channel = M('ChannelAccount')->where(['mch_id'=>$order['memberid']])->find();
        //验证通道的签名

        $hmac = $this->SignParamsToString($data);
        $bemd5 = "#".$data['trxType']."#".$data['retCode']."#".$hmac."#".$order['account'];
        $md5str = md5($bemd5);
        if($md5str != $data['sign']){
            $payLog['msg'] = $this->TITLE.'-验签失败, 通道签名='.$data['sign'].', 平台签名='.$md5str.'--'.$bemd5;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('fail');
        }

        //填充日记
        $payLog['merchantId'] = $order['pay_memberid'];
        $payLog['productCode'] = $order['pay_bankcode'];
        $payLog['outTradeId'] = $order['out_trade_id'];

        //验证是否合法订单
        $orderAmount = strval($order["pay_amount"]);
        if ($orderAmount != $amount) {
            $payLog['msg'] = $this->TITLE.'-订单金额不相等，金额单位为分，通道传的金额='.$amount.', 平台的金额='.$orderAmount;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            die("fail"); //@todo 金额浮点数暂时不比较，有可能不相等，先运行一段时间在打开
        }
        if ($order["memberid"] != $channelMerchantId) {
            $payLog['msg'] = $this->TITLE.'-通道商户号不相等，通道传的通道商户号='.$channelMerchantId.', 平台的通道商户号='.$order["memberid"];
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            die("fail3");
        }


        if($data['r8_orderStatus'] == 'SUCCESS'){
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
            exit('fail4');
        }
    }

    private function SignParamsToString($params,$key) {
        $sign_str = '';
        // 排序
         ksort($params);

        $buff = "";
        foreach ($params as $k => $v)
        {
            if($k!='trxType' && $k!='retCode' && $k!='retMsg' && $k != "sign" && $v != "" && !is_array($v)){
                $buff .= $v . "#";
            }
        }

        $buff = trim($buff, "#");
        return $buff;
    }

}
?>