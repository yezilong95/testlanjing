<?php
namespace Pay\Controller;

use Common\Model\GPayLogModel;

/**
 * 欣翔-QQ扫码
 * Class XinXiangQQScanController
 * @package Pay\Controller
 * author 尚军邦
 */
class XinXiangQQScanController extends PayController
{
    private $CODE = 'XinXiangQQScan';
    private $TITLE = '欣翔-QQ扫码';
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
        $pay_amount = I("request.pay_amount", 0)*100;

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);

        $memberid = $return["memberid"]; //商户号

        //如果生成错误，自动跳转错误页面
        $return["status"] == "error" && $this->showmessage($return["errorcontent"]);

        //跳转页面，优先取数据库中的跳转页面
        $return["notifyurl"] || $return["notifyurl"] = $this->_site . 'Pay_'.$this->CODE.'_notifyurl.html';
        $return['callbackurl'] || $return['callbackurl'] = $this->_site . 'Pay_'.$this->CODE.'_callbackurl.html';

        $data = [
            'merchantId' => $return['mch_id'],
            'outTradeNo' => $return['orderid'],
            'payType' => '40',
            'payMoney' => $pay_amount,
            'goodsDesc' => $body,
            'ip' => $_SERVER["REMOTE_ADDR"],
            'notifyUrl' => $return['notifyurl'],

        ];
        $signSource = $this->SignParamsToString($data);
        $sign = md5($signSource.$return['signkey']);
        $data['sign'] =strtoupper($sign);
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
            if ($res['retCode'] == '00') {
                $url = $res["qrCodeUrl"];
                echo '{"code":"1","qrcode":"' . $url . '","desc":"SUCCESS"}';
            } else {
                echo '{"code":"0","qrcode":"","desc":"' . $res['retMsg'] . '"}';
            }
        } else {
            if ($res['retCode'] == '00') {
                $url = $res["qrCodeUrl"];
                import("Vendor.phpqrcode.phpqrcode", '', ".php");
                $QR = "Uploads/codepay/" . time()."dsc" . ".png";//已经生成的原始二维码图
                \QRcode::png($url, $QR, "L", 20);
                $this->assign("imgurl", $this->_site . $QR);
                $this->assign('params', $return);
                $this->assign('orderid', $return['orderid']);
                $this->assign('money', sprintf('%.2f', $return['amount']));
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
        /*欣翔-QQ扫码-notify返回的数据: payAt=20180327112749&payMoney=10&chnlTradeNo=DD2018032711272758906554&merchantId=10022396&outTradeNo=H2018032711272707990916&sign=36AA7E914629D795EA9DBA7A9A9AB044&notifyId=1803271127494851685&payId=1803271127274572260&retCode=00&payStatus=1&retMsg=%E6%88%90%E5%8A%9F*/

        $data = I('post.','');
        $rawData = http_build_query($data);
       // $rawData = json_encode($data);
        $merchantId = null;
        $productCode = null;
        $outTradeId = null;
        $channelMerchantId = $data['merchantId'];
        $orderId = $data["outTradeNo"];
        $amount =$data['payMoney'];

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
        #retMsg=%E6%88%90%E5%8A%9F,需要urldecode后才能签签
        $data['retMsg'] = urldecode($data['retMsg']);
        $signSource = $this->SignParamsToString($data);
        $sign = md5($signSource.$channel['signkey']);
        $newSign =strtoupper($sign);


        if($data['sign'] != $newSign){
            $payLog['msg'] = $this->TITLE.'-验签失败, 通道签名='.$data['sign'].', 平台签名='.$newSign;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('fail2');
        }

        //填充日记
        $payLog['merchantId'] = $order['pay_memberid'];
        $payLog['productCode'] = $order['pay_bankcode'];
        $payLog['outTradeId'] = $order['out_trade_id'];

        //验证是否合法订单
        $orderAmount = $order["pay_amount"]*100;
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


        if($data['payStatus'] == '1'){
            $this->EditMoney($orderId, '', 0);

            //添加日记
            $payLog['msg'] = $this->TITLE.'-支付成功, 修改订单状态为: 成功,未返回';
            $this->payLogModel->add($payLog);
            exit('success');
        }else {
            //添加日记
            $payLog['msg'] = $this->TITLE.'-支付失败3';
            $payLog['level'] = GPayLogModel::$LEVEL_WARNING;
            $this->payLogModel->add($payLog);
            exit('fail4');
        }
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

}
?>