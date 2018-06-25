<?php
namespace Pay\Controller;

use Common\Model\GPayLogModel;

/**
 * 智宝付-网关
 * Class ZhiBaoFuWangGuanController
 * @package Pay\Controller
 * author 尚军邦
 */
class ZhiBaoFuWangGuanController extends PayController
{
    private $CODE = 'ZhiBaoFuWangGuan';
    private $TITLE = '智宝付-网关';
    private $PRIVATE_KEY='-----BEGIN PRIVATE KEY-----
MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBAKzkLEhL8ITlFzeH
1kTyUdaoxy94cUv0aV5CS1HvEt5+Fs6pa96foftRw/9zEfdJBccc99jGh3BzSycz
S/8m556RAT6b8Gyx05Czo/nxsbt8vasb41XJN9K713zvFuoGNIj+EjeH8GN5cQUt
/a7FxFi5tjNwa66yMmRWCUmNCJDhAgMBAAECgYBhLxVxQjaeDUSis1mPggLcvhzR
I0vUXTwJfwxlrxSVOp1JZ9S53FOqOMkKw70amtKDnVX4ZYhmfniFPYF/mHfjLqKS
5/kPMuTPOf9OmRn+uDk5turWfWJz+p9Eo19mcs38XHv2uuEUOL0bNGwD1mxHOfTB
GuP5wqHiinThZ8QI0QJBANZERP8WMfukDGhhdGjev3paodv0gFsKQWPSRhmsZ9Ur
2iJtQDMykLL/HeAVbKgtPzKrCaPvyJGnt2mFcwrwLW0CQQDOkNnhCa8W6vZmhk53
bSHBcL9LupD3OsviDL0Crr5bHXBjhN3SXqPZSezom508oZ3BI4F4x+Pw2+q4agsq
FozFAkEAjjIua+9p6mt7hIYwgCxbfLLLOjLwP/r1XG6+8OjW28THdhN1CMUk/HWM
eRseyhmFGHYj5rUKMYfRk+jpaTftnQJAZOrbp1fl1JqCOuCO4UXN4gXFT6gcPszY
4t06Ul8w3K7rQ5OcE7Ts87FsLtAn54FF4yAHlwyBTiEC8YnNXoiZyQJBAJHbYkZs
mNtsWAyq46rX+/nr/K5VgEK8Kjod/MMj5LtHX6TVqRBoUxI+LFCr1ht7cjUG33IL
nUnAkDXnAInZIuw=
-----END PRIVATE KEY-----
';
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
            'merchant_code' => $return['mch_id'],
            'service_type' => 'direct_pay',
            'notify_url' => $return["notifyurl"],
            'return_url' => $return['callbackurl'],
            'interface_version' => 'V3.0',
            'input_charset' => 'UTF-8',
            'pay_type' => 'b2c',
            'client_ip' => $_SERVER["REMOTE_ADDR"],
            'sign_type' => 'RSA-S',
            'order_no' => $return['orderid'],
            'order_time' => date("Y-m-d H:i:s"),
            'order_amount' => $pay_amount,
            'product_name' => $body,

        ];
        $signSource = $this->SignParamsToString($data);
        $merchant_private_key= openssl_get_privatekey($this->PRIVATE_KEY);
        openssl_sign($signSource,$sign_info,$merchant_private_key,OPENSSL_ALGO_MD5);
        $sign = base64_encode($sign_info);
        $data['sign'] =$sign;
        $postData = http_build_query($data);
        $payLog['msg'] = $this->TITLE.'-提交的数据: '.$postData;
        $this->payLogModel->add($payLog);
        $result =  createForm($return['gateway'], $data);
       //$result = curlPost($return['gateway'], $postData);

        $res = xmlToArray($result);

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
        echo($result);
        die;
        if ($returnType == 'json') {
            if ($res['response']['result_code'] == '0') {
                $url = $res['response']["qrcode"];
                echo '{"code":"1","qrcode":"' . $url . '","desc":"SUCCESS"}';
            } else {
                echo '{"code":"0","qrcode":"","desc":"' . $res['response']['resp_desc'] . '"}';
            }
        } else {
            if ($res['response']['result_code'] == '0') {
                $url = $res['response']["qrcode"];
                import("Vendor.phpqrcode.phpqrcode", '', ".php");
                $QR = "Uploads/codepay/" . time()."dsc" . ".png";//已经生成的原始二维码图
                \QRcode::png($url, $QR, "L", 20);
                $this->assign("imgurl", $this->_site . $QR);
                $this->assign('params', $return);
                $this->assign('orderid', $return['orderid']);
                $this->assign('money', sprintf('%.2f', $pay_amount));
                $this->display("WeiXin/qq");
            } else {
                $this->showmessage($res['response']['resp_desc']);
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
        /*-notify返回的数据: trade_no=1002500755&sign_type=RSA-S&notify_type=offline_notify&merchant_code=800015000001&order_no=Z2018040510590106951917&trade_status=SUCCESS&sign=P3iA0leIR1RxxZAnB9uERy1xm%2FSpekn9EbDDt4O%2BiDfkzR50%2FxrrQMI9Jv6wBWnmY%2Bado4EpFRvvzG1h2%2BHGpcmQiRlhDuKKfAyaT2z8ovBfI%2F6joSGWXtGVpzkKjG9yS3RCpWnT6%2BEgPbEWZEfjh%2FQHpDoMU9wi3VpqM0RoBjM%3D&order_amount=0.1&interface_version=V3.0&bank_seq_no=Order3834874549864448N&order_time=2018-04-05+10%3A59%3A01&trade_time=2018-04-05+10%3A59%3A01&notify_id=6fbc43b0bdd34ce9b0062dabe1bd0acd*/

        $data = I('post.','');
        $rawData = http_build_query($data);
       // $rawData = json_encode($data);
        $merchantId = null;
        $productCode = null;
        $outTradeId = null;
        $channelMerchantId = $data['merchant_code'];
        $orderId = $data["order_no"];
        $amount =$data['order_amount'];

        //添加日记
        $payLog = [
            'merchantId' => $merchantId,
            'productCode' => $productCode,
            'outTradeId' => $outTradeId,
            'channelMerchantId' => $channelMerchantId,
            'orderId' =>$orderId,
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


        //填充日记
        $payLog['merchantId'] = $order['pay_memberid'];
        $payLog['productCode'] = $order['pay_bankcode'];
        $payLog['outTradeId'] = $order['out_trade_id'];

        //验证是否合法订单
        $orderAmount = $order["pay_amount"];
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


        if($data['trade_status'] == 'SUCCESS'){
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

    private function SignParamsToString($params) {
        $sign_str = '';
        // 排序
        ksort($params);

        $buff = "";
        foreach ($params as $k => $v)
        {
            if($k != "sign" && $k!='sign_type' && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }
    public function post_curl($postdata){
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,"https://api.zhibaopay.com/gateway/api/scanpay");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response=curl_exec($ch);

        //$res=simplexml_load_string($response);



        curl_close($ch);
        return $response;
    }

}
?>