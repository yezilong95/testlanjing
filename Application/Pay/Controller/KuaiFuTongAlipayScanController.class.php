<?php
namespace Pay\Controller;

use Common\Model\GPayLogModel;

/**
 * 快付通-支付宝扫码
 * Class KuaiFuTongAlipayScanController
 * @package Pay\Controller
 * author 尚军邦
 */
class KuaiFuTongAlipayScanController extends PayController
{
    private $CODE = 'KuaiFuTongAlipayScan';
    private $TITLE = '快付通-支付宝扫码';
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
            'src_code' => $return['appid'],
            'out_trade_no' => $return['orderid'],
            'total_fee' => $pay_amount*100,
            'time_start' => date("YmdHis"),
            'goods_name' => $body,
            'trade_type' => '60104',
            'mchid' => $return['mch_id'],
            'finish_url' => $return['callbackurl']
        ];

        $string = $this->SignParamsToString($data);
        $sign=md5($string.'&key='.$return['signkey']);
        $data['sign']= strtoupper($sign);
        $postData = http_build_query($data);
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
        $result = curlPost($return['gateway'], $postData);

        //添加日记
        //通道返回数据"{"respcd":"0000","respmsg":"","data":{"trade_no":"201803311214689728","trade_type":"60104","time_start":"20180331121559","pay_time":"","goods_name":"VIP\u57fa\u7840\u670d\u52a1","src_code":"KFTxLY01522465255WJWus","sign":"22EB4A74D2BB51E4E2411F1E7C060818","out_trade_no":"K2018033112155911725410","total_fee":"1000","order_status":2,"pay_params":"http://auth.coincard.cc/js/12/AB3A896"}}"ay.qq.com%2fqr%2f5366e068"}"
        //{"status":"error","msg":null,"data":[]}
        $payLog['msg'] = $this->TITLE.'-返回的数据: '.$result;
        $this->payLogModel->add($payLog);

        $res = json_decode($result,true);

        if($returnType == 'json') {
            $url =$res["data"]['pay_params'];
            if ($res['respcd'] == '0000') {
                echo '{"code":"1","qrcode":"' . $url . '","desc":"SUCCESS"}';
            } else {
                echo '{"code":"0","qrcode":"","desc":"' . $res['respmsg'] . '"}';
            }
        } else {
            if ($res['respcd'] == '0000') {
                $url =$res["data"]['pay_params'];
                import("Vendor.phpqrcode.phpqrcode", '', ".php");
                $QR = "Uploads/codepay/" . $return["orderid"] . ".png";//已经生成的原始二维码图
                \QRcode::png($url, $QR, "L", 20);
                $this->assign("imgurl", $this->_site . $QR);
                $this->assign('params', $return);
                $this->assign('orderid', $return['orderid']);
                $this->assign('money', sprintf('%.2f', $return['amount']));
                $this->display("WeiXin/alipay");
            } else {
                $this->showmessage($res['respmsg']);
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
        /*{"partner":"17044","ordernumber":"2018030514413452135676","orderstatus":"1","paymoney":"1.0000","sysnumber":"180305144116600","attach":"8876","sign":"0432a34ad5b0cbba7872620cb09fc922"}*/
die('dd');
        $data = I('post.','');
        $rawData = http_build_query($data);
       // $rawData = json_encode($data);
        $merchantId = null;
        $productCode = null;
        $outTradeId = null;
        $channelMerchantId = $data['partner'];
        $orderId = $data["ordernumber"];
        $amount =strval($data['paymoney']);

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
            exit('{"code": 999,"message": "接收失败"}');
        }

        //验证通道的签名
        $channel = M('ChannelAccount')->where(['mch_id'=>$order['memberid']])->find();
        $signSource = sprintf("partner=%s&ordernumber=%s&orderstatus=%s&paymoney=%s%s", $data['partner'], $data['ordernumber'], $data['orderstatus'], $data['paymoney'],$channel['signkey']);
        $newSign = md5($signSource);


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
        $orderAmount = $order["pay_amount"];
        if (format2Decimal($orderAmount) != format2Decimal($amount)) {
            $payLog['msg'] = $this->TITLE.'-订单金额不相等，金额单位为分，通道传的金额='.$amount.', 平台的金额='.$orderAmount;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            //die("fail"); //@todo 金额浮点数暂时不比较，有可能不相等，先运行一段时间在打开
        }
        if ($order["memberid"] != $channelMerchantId) {
            $payLog['msg'] = $this->TITLE.'-通道商户号不相等，通道传的通道商户号='.$channelMerchantId.', 平台的通道商户号='.$order["memberid"];
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            die("fail");
        }
        if($data['orderstatus'] == '1'){
            $this->EditMoney($data["ordernumber"], '', 0);

            //添加日记
            $payLog['msg'] = $this->TITLE.'-支付成功, 修改订单状态为: 成功,未返回';
            $this->payLogModel->add($payLog);
            exit('ok');
        }else {
            //添加日记
            $payLog['msg'] = $this->TITLE.'-支付失败3';
            $payLog['level'] = GPayLogModel::$LEVEL_WARNING;
            $this->payLogModel->add($payLog);
            exit('fail');
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