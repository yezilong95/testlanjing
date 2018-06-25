<?php
namespace Pay\Controller;

use Common\Model\GPayLogModel;

/**
 * 启付通-网关手机专用
 * Class QiFuTongWangGuanPhoneController
 * @package Pay\Controller
 * @author 叶子龙
 */
class QiFuTongWangGuanPhoneController extends PayController
{
    private $CODE = 'QiFuTongWangGuanPhone';
    private $TITLE = '启付通-网关手机专用';

    public function Pay($array)
    {
        header("Content-Type:text/html;charset=UTF-8");
        $out_datas = I("request.",'');
        $out_trade_id = I("request.pay_orderid", ''); //商户订单号
        $returnType = I('request.return_type', 'html'); //返回值：html，json
        $body = I('request.pay_productname', ''); //商品名称
        $productCode = I('request.pay_bankcode'); //支付产品编号
        $pay_amount = I("request.pay_amount", 0);


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


        $this->assign("out_trade_id",$out_trade_id);
        $this->assign("pay_amount",$pay_amount);
        $this->assign("return",urlencode(json_encode($return,JSON_UNESCAPED_UNICODE)));
        $this->display("CashierWangGuan/indexPhone");
    }

    public function confirmPay(){
        /* $return->array(17) {
             ["memberid"] => int(10002)
             ["mch_id"] => string(12) "ME0000000187"
             ["signkey"] => string(32) "a734f198fc6b7f379df43b40846d0fc5"
             ["appid"] => string(32) "508221b775c603ba506bdaea3f0a5c46"
             ["appsecret"] => string(0) ""
             ["gateway"] => string(33) "http://www.dulpay.com/api/pay/net"
             ["notifyurl"] => string(58) "http://zhifujia2.com/Pay_QiFuTongWangGuanNo_notifyurl.html"
             ["callbackurl"] => string(60) "http://zhifujia2.com/Pay_QiFuTongWangGuanNo_callbackurl.html"
             ["unlockdomain"] => string(0) ""
             ["amount"] => int(10)
             ["bankcode"] => string(3) "907"
             ["code"] => string(5) "DBANK"
             ["orderid"] => string(24) "LJ2018033022324720586625"
             ["out_trade_id"] => string(21) "E20180330143244107388"
             ["subject"] => string(15) "VIP基础服务"
             ["datetime"] => string(21) "LJ2018-03-30 22:32:47"
             ["status"] => string(7) "success"
 }

 array(8)$array-> {
             ["id"] => string(2) "14"
             ["userid"] => string(1) "2"
             ["pid"] => string(3) "907"
             ["polling"] => string(1) "0"
             ["status"] => string(1) "1"
             ["channel"] => string(3) "282"
             ["weight"] => string(0) ""
             ["api"] => string(3) "282"
 }*/
        $return =json_decode(urldecode(I("request.return",'')),true);
        $productCode =$return['bankcode'];//我们平台的产品编号
        $out_trade_id = $return['out_trade_id'];//下游订单号

        $bankCode = I("request.bankCode",'');//上游银行编码

        if($bankCode == ''){
            $this->showmessage('请选择所在银行！');
        }

        $pay_amount = $return['amount'];

        //必要支付金额最小为20元
        /*if($pay_amount < 10){
            exit('支付金额最小为10元');
        }
        if($pay_amount > 20000){
            exit('单笔支付限额20000元');
        }*/

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
            'bankCode'            => $bankCode,
            'callBackUrl'            => $return['callbackurl'],
            'cardType'            => '1',//银行卡类型。1为储蓄卡；2为信用卡
            'goodsTitle'            => 'computer',//商品标题
            'merchantNo'            => $mch_id,
            'notifyUrl'            => $return["notifyurl"],
            'orderNo'            => $pay_orderid,
            'payType'            => 'gateway',
            'appNo'            => $appid,
            'currency'            => 'CNY',
            'timestamp'            => date('YmdHiZ'),
        );


        //签名
        $signstr = $this->_createSign($data);
        $data['sign'] = md5($signstr.$signkey);
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

        $result = createForm($return['gateway'], $data);
        $payLog['msg'] = $this->TITLE.'-PC端返回的数据: '.$result;
        $this->payLogModel->add($payLog);
        echo $result;
    }

    public function callbackurl(){
        $rawData = http_build_query($_REQUEST);
        $orderid = $_REQUEST["orderNo"];


        //添加日记
        $payLog = [
            'type' => GPayLogModel::$TYPE_CHANNEL_CALLBACK,
            'productCode' => '907',
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
        //签名
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

    public function isMobile()
    {

        /*移动端判断*/
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
        if (isset ($_SERVER['HTTP_X_WAP_PROFILE']))
        {
            return true;
        }
        // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
        if (isset ($_SERVER['HTTP_VIA']))
        {
            // 找不到为flase,否则为true
            return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
        }
        // 脑残法，判断手机发送的客户端标志,兼容性有待提高
        if (isset ($_SERVER['HTTP_USER_AGENT']))
        {
            $clientkeywords = array ('nokia',
                'sony',
                'ericsson',
                'mot',
                'samsung',
                'htc',
                'sgh',
                'lg',
                'sharp',
                'sie-',
                'philips',
                'panasonic',
                'alcatel',
                'lenovo',
                'iphone',
                'ipod',
                'blackberry',
                'meizu',
                'android',
                'netfront',
                'symbian',
                'ucweb',
                'windowsce',
                'palm',
                'operamini',
                'operamobi',
                'openwave',
                'nexusone',
                'cldc',
                'midp',
                'wap',
                'mobile'
            );
            // 从HTTP_USER_AGENT中查找手机浏览器的关键字
            if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT'])))
            {
                return true;
            }
        }
        // 协议法，因为有可能不准确，放到最后判断
        if (isset ($_SERVER['HTTP_ACCEPT']))
        {
            // 如果只支持wml并且不支持html那一定是移动设备
            // 如果支持wml和html但是wml在html之前则是移动设备
            if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))))
            {
                return true;
            }
        }
        return false;
    }



}
?>