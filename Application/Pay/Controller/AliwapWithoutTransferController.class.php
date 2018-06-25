<?php
namespace Pay\Controller;
use Common\Model\GPayLogModel;

/**
 * 支付宝H5, 没有自动返款
 * Class AliwapWithoutTransferController
 * @package Pay\Controller
 */
class AliwapWithoutTransferController extends PayController
{
    private $CODE = 'AliwapWithoutTransfer';
    private $TITLE = '支付宝H5';

    public function __construct()
    {
        parent::__construct();
    }

    //支付
    public function Pay($array)
    {
        $out_trade_id = I("request.pay_orderid");
        $body = I('request.pay_productname');
        $productCode = I('request.pay_bankcode'); //支付产品编号
        $notifyurl = $this->_site . 'Pay_AliwapWithoutTransfer_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_AliwapWithoutTransfer_callbackurl.html'; //返回通知
        $parameter = array(
            'code' => 'AliwapWithoutTransfer', // 通道名称
            'title' => $this->TITLE,
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $out_trade_id,
            'body'=>$body,
            'channel'=>$array
        );
        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);

        $pay_orderid = $return["orderid"]; //平台订单号
        $memberid = $return["memberid"]; //商户号
        $mch_id = $return['mch_id']; //通道商户号
        $amount = $return["amount"];
        $appid = $return['appid'];
        $gateway = $return["gateway"];
        $signKey = $return['signkey'];

        //添加支付日记
        $this->payLogModel->add([
            'msg' => $this->TITLE,
            'merchantId' => $memberid,
            'productCode' => $productCode,
            'outTradeId' => $out_trade_id,
            'channelMerchantId' => $mch_id,
            'orderId' => $pay_orderid,
            'type' => GPayLogModel::$TYPE_REQUEST_CHANNEL,
        ]);

        $return['subject'] = $body;
        $paymethod = 'alipayWap';
        switch ($paymethod)
        {
            case 'alipayPage':
                $this->alipayPage($return);
                break;
            case 'alipayWap':
                $this->alipayWap($return);
                break;
            case 'alipayPrecreate':
                $this->alipayPrecreate($return);
                break;
        }
    }

    protected function alipayPage($params)
    {
        $data = array(
            'out_trade_no'=>$params['orderid'],
            'total_amount'=>$params['amount'],
            'subject'=>$params['subject'],
            'product_code' => "FAST_INSTANT_TRADE_PAY"
        );
        $sysParams = json_encode($data,JSON_UNESCAPED_UNICODE);

        vendor('Alipay.aop.AopClient');
        vendor('Alipay.aop.SignData');
        vendor('Alipay.aop.request.AlipayTradePagePayRequest');
        $aop = new \AopClient();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $params['mch_id'];
        $aop->rsaPrivateKey = 'MIIEpAIBAAKCAQEAtvDQk9LG+LDpbk6xiecxqjPg9f4BhNYurJDFrVybxISAqCUM5/ofz/mIMTHQJ6mOdjtHG53ux6ADs7GZ+U7Vg5IZUkdkENxd7I0KIoXryQAQFDkDGrOPbaUoD9icpOzE7fm774J6bDh/0oFfB3OojDkjxYN6zObW/PIqU6DrL3S3Ka5b3qEH8NuuiN/f0SFBwE7gYpyPZ/WcuiHjvMkB0mbzGtWYrNXczU1gQ+9c+0v0pjXrddajC8/IlWDgDJCbIM+hgrWG7VieKnp2RnLdGH+Ok2yPdS9c3pEVxzBTcRkt8kvPo522FtoTPtC3cg0AsJsEvNA6A9/AGN2qHxzK2QIDAQABAoIBAQCvJO8MF4gXIIjb6ste09FgujpuSLj7jHMzE4et6jPXeWQTlyU8EuPSIXyaXK6EynhyCV6SuimZRUFGEIrxfOA+DunfNCpBWjkx9/X0B3MuBLlgIxUtwytWNgCc6y1NWMFRdP7Q14KNiaoWx3VLlReQ6EOvHam78mVx1gdf+Xgw/VQ3CCtA+MzH7OiiC9i/bx1EFewzOCNy3+iAXgJ0OXELe5mL4K1zN/XJKRMbc+b35SL6S54iFw/iplDhY74/J6odqvkXIoCRwteIXDD1Xl7mxv4gNwBd9REP7Z+IB3I9Y0I7JtbAiUl6dQPr/rHYsfvZwHfAQVORsbYM+Ehsn1uBAoGBAPJQUheIv7SnuHeBabkJ8+EdUay2gmsjpja6U6Tzbw00aSjnUZUETIpmTS2WwixvvoOHjLe+nOUB/g+ZDxmtSKOSXd7pKEgbH/R8x3n7HYJr6qI/VMzsYuRXOz03l7Q6HA/c8YgC26UTrtFha4m0eIX7rQKOo9U/wxEbBikMD16tAoGBAMFGAMQeX+olv4+esxzXh97gmfks+2ZRrdiydBz2N14fwFb7bhtnn/hoYFb0TesSDCxp5zekVo5PlFgLWQ4jEVqqAUDP8uwG/Lq8iRHUp7BeBkOMHaIBDprH51wcrEMmDvk0v576B6Xe6VR3QJCUJLtq8tVL7+18Lu5BHu6xr75dAoGBAKN3tCnUQx/olfVpBJ2kLTaMxPCzH0CQCC2bfZol76EE3nyNsOfKwqgLY72BmvTHXcr1wuSiXs3PjkmPhDRaRkqzD0i2GkqqoeAZ3ahY1AuMKfnSp66nOf+5KWme+2TGXvAEqZyL8QloQeNWyWlYqoYYxxqWh8fw//OmO32teSDxAoGAGCThlZ5hxwNeMdfWckTugUY3lewrn7WWbRql7LRJaGW5BmS0dZH1ZvfLCTHNxg7kHGxCaS4Lbg28717DikOROG1CaNFRfHDHA6Dn0qVpKVwlliybyxAsveM5IMWoM18+wZz4TyjW6b62EUowc58+E3ehzEmHOHip+DOEZLcnyDUCgYAqKOvH/UkgdNTR5NoQZmLSXuEuYimBiR6sNtV4ggdlAT9/e7ON+PHCg63nW1sULPZMkvtnGgkUg8p6/KCkPJopHzmt+TXqJBdMBI3rHW70QlOB5O0xFEbpYwF6mQ42uVLZY3GTD2qr3ksp/ts/cgjfgtpbfFTH7hEh42ftApD6IQ==';
        $aop->alipayrsaPublicKey= $params['signkey'];
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='UTF-8';
        $aop->format='json';
        $aop->debugInfo=true;
        $request = new \AlipayTradePagePayRequest();
        $request->setBizContent($sysParams);
        $request->setNotifyUrl($params['notifyurl']);
        $request->setReturnUrl($params['callbackurl']);
        $result = $aop->pageExecute($request,'post');
        echo $result;
    }
    protected function alipayWap($params){

        $data = array(
            'out_trade_no'=>$params['orderid'],
            'total_amount'=>$params['amount'],
            'subject'=>$params['subject'],
            'product_code' => "QUICK_WAP_WAY"
        );
        $sysParams = json_encode($data,JSON_UNESCAPED_UNICODE);

        vendor('Alipay.aop.AopClient');
        vendor('Alipay.aop.SignData');
        vendor('Alipay.aop.request.AlipayTradeWapPayRequest');
        $aop = new \AopClient();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $params['mch_id'];
        $aop->rsaPrivateKey = $params['appsecret'];
        $aop->alipayrsaPublicKey= $params['signkey'];
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='UTF-8';
        $aop->format='json';
        $request = new \AlipayTradeWapPayRequest ();
        $request->setBizContent($sysParams);
        $request->setNotifyUrl($params['notifyurl']);
        $request->setReturnUrl($params['callbackurl']);
        $result = $aop->pageExecute ( $request,"post");
        echo $result;
    }

    protected function alipayPrecreate($params)
    {
        //组装系统参数
        $data = array(
            'out_trade_no'=>$params['orderid'],
            'total_amount'=>$params['amount'],
            'subject'=>$params['subject'],
        );
        $sysParams = json_encode($data,JSON_UNESCAPED_UNICODE);

        vendor('Alipay.aop.AopClient');
        vendor('Alipay.aop.SignData');
        vendor('Alipay.aop.request.AlipayTradePrecreateRequest');
        $aop = new \AopClient();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $params['mch_id'];
        $aop->rsaPrivateKey = 'MIIEpAIBAAKCAQEAtvDQk9LG+LDpbk6xiecxqjPg9f4BhNYurJDFrVybxISAqCUM5/ofz/mIMTHQJ6mOdjtHG53ux6ADs7GZ+U7Vg5IZUkdkENxd7I0KIoXryQAQFDkDGrOPbaUoD9icpOzE7fm774J6bDh/0oFfB3OojDkjxYN6zObW/PIqU6DrL3S3Ka5b3qEH8NuuiN/f0SFBwE7gYpyPZ/WcuiHjvMkB0mbzGtWYrNXczU1gQ+9c+0v0pjXrddajC8/IlWDgDJCbIM+hgrWG7VieKnp2RnLdGH+Ok2yPdS9c3pEVxzBTcRkt8kvPo522FtoTPtC3cg0AsJsEvNA6A9/AGN2qHxzK2QIDAQABAoIBAQCvJO8MF4gXIIjb6ste09FgujpuSLj7jHMzE4et6jPXeWQTlyU8EuPSIXyaXK6EynhyCV6SuimZRUFGEIrxfOA+DunfNCpBWjkx9/X0B3MuBLlgIxUtwytWNgCc6y1NWMFRdP7Q14KNiaoWx3VLlReQ6EOvHam78mVx1gdf+Xgw/VQ3CCtA+MzH7OiiC9i/bx1EFewzOCNy3+iAXgJ0OXELe5mL4K1zN/XJKRMbc+b35SL6S54iFw/iplDhY74/J6odqvkXIoCRwteIXDD1Xl7mxv4gNwBd9REP7Z+IB3I9Y0I7JtbAiUl6dQPr/rHYsfvZwHfAQVORsbYM+Ehsn1uBAoGBAPJQUheIv7SnuHeBabkJ8+EdUay2gmsjpja6U6Tzbw00aSjnUZUETIpmTS2WwixvvoOHjLe+nOUB/g+ZDxmtSKOSXd7pKEgbH/R8x3n7HYJr6qI/VMzsYuRXOz03l7Q6HA/c8YgC26UTrtFha4m0eIX7rQKOo9U/wxEbBikMD16tAoGBAMFGAMQeX+olv4+esxzXh97gmfks+2ZRrdiydBz2N14fwFb7bhtnn/hoYFb0TesSDCxp5zekVo5PlFgLWQ4jEVqqAUDP8uwG/Lq8iRHUp7BeBkOMHaIBDprH51wcrEMmDvk0v576B6Xe6VR3QJCUJLtq8tVL7+18Lu5BHu6xr75dAoGBAKN3tCnUQx/olfVpBJ2kLTaMxPCzH0CQCC2bfZol76EE3nyNsOfKwqgLY72BmvTHXcr1wuSiXs3PjkmPhDRaRkqzD0i2GkqqoeAZ3ahY1AuMKfnSp66nOf+5KWme+2TGXvAEqZyL8QloQeNWyWlYqoYYxxqWh8fw//OmO32teSDxAoGAGCThlZ5hxwNeMdfWckTugUY3lewrn7WWbRql7LRJaGW5BmS0dZH1ZvfLCTHNxg7kHGxCaS4Lbg28717DikOROG1CaNFRfHDHA6Dn0qVpKVwlliybyxAsveM5IMWoM18+wZz4TyjW6b62EUowc58+E3ehzEmHOHip+DOEZLcnyDUCgYAqKOvH/UkgdNTR5NoQZmLSXuEuYimBiR6sNtV4ggdlAT9/e7ON+PHCg63nW1sULPZMkvtnGgkUg8p6/KCkPJopHzmt+TXqJBdMBI3rHW70QlOB5O0xFEbpYwF6mQ42uVLZY3GTD2qr3ksp/ts/cgjfgtpbfFTH7hEh42ftApD6IQ==';
        $aop->alipayrsaPublicKey= $params['signkey'];
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='UTF-8';
        $aop->format='json';
        $request = new \AlipayTradePrecreateRequest ();
        $request->setBizContent($sysParams);
        $request->setNotifyUrl($params['notifyurl']);
        $result = $aop->execute ( $request);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;

        if(!empty($resultCode)&&$resultCode == 10000){
            import("Vendor.phpqrcode.phpqrcode",'',".php");
            $url = urldecode($result->$responseNode->qr_code);
            $QR = "Uploads/codepay/". $params["orderid"] . ".png";//已经生成的原始二维码图
            $delqr = $QR;
            \QRcode::png($url, $QR, "L", 20);
            //$this->assign("imgurl", $this->_site.$QR);
            //$this->assign("ddh", $result->$responseNode->out_trade_no);
            //$this->assign("money", $params["amount"] / 100);
            //$this->display("WeiXin/Pay");
            echo json_encode(array('status'=>1,'codeurl'=>$this->_site.$QR));
            exit();
        } else {
            echo "失败";
        }
        exit();
    }
    //同步通知
    public function callbackurl()
    {
        //http://local.zhifujia.cc/Pay_AliwapWithoutTransfer_callbackurl.html?
        //total_amount=0.01&timestamp=2018-03-30+16%3A14%3A26&
        //sign=QAjC4FGf6gFyGxwCItkGuTR253KZOP1Az4ca6ldAv5xHV9Xl0551%2FpOHRFaWX9nqQW1rzhApUOu6EauqiK7lLH4nlpOaZ0DFCL1npkkCP19tjqK0XtrKp8n9wjhictSYOAby9XKvjt4p3h2YIvzZN%2FvzccalvOHqNGvec0i6pfJldTPuXYrnTLAXF3bkAhbKBo1ruCW5VwCK9hxFSmG%2FWAbF3HJxLCcki%2BfO1c7gBQdTRQkE9XSy5iAz2hVqqLME4p9TRqeYC4l8xc%2FPGgE438RqICtzn0Ui0Oa8R%2FFbPkoXqnANXfBbxEN5yMd789R%2BCPg7ohfIdC47U06KupUAfg%3D%3D&
        //trade_no=2018033021001004800572257704&sign_type=RSA2&auth_app_id=2018012202025576&charset=UTF-8&
        //seller_id=2088921824551094&method=alipay.trade.wap.pay.return&app_id=2018012202025576&
        //out_trade_no=Z2018033016140506711019&version=1.0

        $response = $_GET;
        $sign = $response['sign'];
        $sign_type = $response['sign_type'];
        $channelMerchantId = $response['app_id'];
        $channelAmount = $response['total_amount'];
        $orderId = $response['out_trade_no'];

//        unset($response['sign']);
//        unset($response['sign_type']);
//        $publiKey =  $this->getAccountSignkey($response["app_id"]); // 密钥
//
//        ksort($response);
//        $signData = '';
//        foreach ($response as $key=>$val){
//            $signData .= $key .'='.$val."&";
//        }
//        $signData = trim($signData,'&');
////        $aop = new \AopClient();
////        $result = $aop->verify($signData,$sign,$publiKey,$sign_type);
//        $res = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($publiKey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
//        $result = (bool)openssl_verify($signData, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);

        //添加日记
        $payLog = [
            'merchantId' => null,
            'productCode' => null,
            'outTradeId' => null,
            'channelMerchantId' => $channelMerchantId,
            'orderId' => $orderId,
            'level' => GPayLogModel::$LEVEL_INFO,
            'type' => GPayLogModel::$TYPE_CHANNEL_CALLBACK,
        ];
        $payLog['msg'] = $this->TITLE.'-返回的数据: '.http_build_query($response);
        $this->payLogModel->add($payLog);

        //查找订单
        $orderModel = M("Order");
        $order = $orderModel->where(['pay_orderid' => $orderId])->find();

        if(empty($order)){
            $payLog['msg'] = $this->TITLE.'-平台订单不存在, 平台订单号='.$orderId.', 返回数据: '.http_build_query($response);
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('签名验证失败');
        }

        $merchantId = $order['pay_memberid'];
        $productCode = $order['pay_bankcode'];
        $outTradeId = $order['out_trade_id'];
        $signKey = $order['key'];
        $amount = $order["pay_amount"];

        $payLog['merchantId'] = $merchantId;
        $payLog['productCode'] = $productCode;
        $payLog['outTradeId'] = $outTradeId;

        //验证是否合法订单
        $channelAmountStr = format2Decimal($channelAmount);
        $amountStr = format2Decimal($amount);
        if ($channelAmountStr != $amountStr) {
            $payLog['msg'] = $this->TITLE.'-订单金额不相等，金额单位为元，通道传的金额='.$channelAmount.', 平台的金额='.$amount;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit("签名验证失败");
        }
        if ($order["memberid"] != $channelMerchantId) {
            $payLog['msg'] = $this->TITLE.'-通道商户号不相等，通道传的通道商户号='.$channelMerchantId.', 平台的通道商户号='.$order["memberid"];
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit("签名验证失败");
        }

//        if($result){
            $this->EditMoney($response['out_trade_no'], 'AliwapWithoutTransfer', 1);
//        }else{
//            exit('签名验证失败');
//        }

    }

    //异步通知
    public function notifyurl()
    {
        $response = $_POST;
        $sign = $response['sign'];
        $sign_type = $response['sign_type'];
        $channelMerchantId = $response['app_id'];
        $channelAmount = $response['total_amount'];
        $orderId = $response['out_trade_no'];

        //添加日记
        $payLog = [
            'merchantId' => null,
            'productCode' => null,
            'outTradeId' => null,
            'channelMerchantId' => $channelMerchantId,
            'orderId' => $orderId,
            'level' => GPayLogModel::$LEVEL_INFO,
            'type' => GPayLogModel::$TYPE_CHANNEL_NOTIFY,
        ];
        $payLog['msg'] = $this->TITLE.'-返回的数据: '.http_build_query($response);
        $this->payLogModel->add($payLog);

        //查找订单
        $orderModel = M("Order");
        $order = $orderModel->where(['pay_orderid' => $orderId])->find();

        if(empty($order)){
            $payLog['msg'] = $this->TITLE.'-平台订单不存在, 平台订单号='.$orderId.', 返回数据: '.http_build_query($response);
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('fail');
        }

        $merchantId = $order['pay_memberid'];
        $productCode = $order['pay_bankcode'];
        $outTradeId = $order['out_trade_id'];
        $signKey = $order['key'];
        $amount = $order["pay_amount"];

        $payLog['merchantId'] = $merchantId;
        $payLog['productCode'] = $productCode;
        $payLog['outTradeId'] = $outTradeId;

        //验证是否合法订单
        $channelAmountStr = format2Decimal($channelAmount);
        $amountStr = format2Decimal($amount);
        if ($channelAmountStr != $amountStr) {
            $payLog['msg'] = $this->TITLE.'-订单金额不相等，金额单位为元，通道传的金额='.$channelAmount.', 平台的金额='.$amount;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit("fail");
        }
        if ($order["memberid"] != $channelMerchantId) {
            $payLog['msg'] = $this->TITLE.'-通道商户号不相等，通道传的通道商户号='.$channelMerchantId.', 平台的通道商户号='.$order["memberid"];
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit("fail");
        }

//        unset($response['sign']);
//        unset($response['sign_type']);
//        $publiKey =  $this->getAccountSignkey($response["app_id"]); // 密钥
//
//        ksort($response);
//        $signData = '';
//        foreach ($response as $key=>$val){
//            $signData .= $key .'='.$val."&";
//        }
//        $signData = trim($signData,'&');
////        $aop = new \AopClient();
////        $result = $aop->verify($signData,$sign,$publiKey,$sign_type);
//        $res = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($publiKey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
//        $result = (bool)openssl_verify($signData, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);

//        if($result){
            if($response['trade_status'] == 'TRADE_SUCCESS' || $response['trade_status'] == 'TRADE_FINISHED'){
                $this->EditMoney($response['out_trade_no'], 'AliwapWithoutTransfer', 0);
                exit("success");
            }else{
                exit('fail');
            }
//        }else{
//            exit('error:check sign Fail!');
//        }

    }

    /** *利用google api生成二维码图片
     * $content：二维码内容参数
     * $size：生成二维码的尺寸，宽度和高度的值
     * $lev：可选参数，纠错等级
     * $margin：生成的二维码离边框的距离
     */
    function create_erweima($content, $size = '200', $lev = 'L', $margin= '0') {
        $content = urlencode($content);
        $image = 'http://chart.apis.google.com/chart?chs='.$size.'x'.$size.'&amp;cht=qr&chld='.$lev.'|'.$margin.'&amp;chl='.$content;
        return $image;
    }

}