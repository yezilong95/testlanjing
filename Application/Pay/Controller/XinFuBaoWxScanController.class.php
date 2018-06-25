<?php
namespace Pay\Controller;
use Common\Model\GPayLogModel;
/**
 * 信付宝-微信扫码支付
 * Class TuBeiQQScanController
 * @package Pay\Controller
 * author 尚军邦
 */
class XinFuBaoWxScanController extends PayController
{
    private $CODE = 'XinFuBaoWxScan';
    private $TITLE = '信付宝-微信扫码';
    private $URL = 'http://online.atrustpay.com/payment/ScanPayApply.do';
    //private $TRADE_TYPE = 'trade.qqpay.native';

    public function Pay($array)
    {
        header("Content-Type:text/html;charset=UTF-8");
        addSyslog($array, 1, 10);

        $out_trade_id = I('request.pay_orderid');
        $returnType = I('request.return_type', 'html'); //返回值：html，json
        $productCode = I('request.pay_bankcode'); //支付产品编号
        $body = I('request.pay_productname');
        $amount = I('request.pay_amount');
        $notifyurl = $this->_site . 'Pay_' . $this->CODE . '_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_' . $this->CODE . '_callbackurl.html'; //跳转通知

        $parameter = array(
            'code' => $this->CODE, // 通道名称
            'title' => $this->TITLE, //通道名称
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid'=>'',
            'out_trade_id' => $out_trade_id, //外部订单号
            'channel'=>$array,
            'body'=>$body
        );
        $return = $this->orderadd($parameter);
        if ($return) {
        	if($return["amount"] > 5000){
        		exit('单笔支付限额5000元');
        	}

            //提交到通道接口的参数
            $data = array(
                'versionId'              => '1.0',
                //'orderAmount'            => 1100,
                'orderAmount'            => $amount*100,
                'orderDate'         => date("YmdHis"), //订单日期
                'currency'        => 'RMB', //货币类型
                'transType'      => '008',//交易类别
                'asynNotifyUrl'  => $notifyurl,
                //'asynNotifyUrl'  => 'http://sjb.ypyunedu.com/callback.php',
                'synNotifyUrl'         => $callbackurl,
                //'synNotifyUrl'         => 'http://sjb.ypyunedu.com/callback.html',
                'signType'        => 'MD5',
                'merId'        => $return['mch_id'],
                'prdOrdNo'        => $return["orderid"],
                'payMode'        => '00022',
                //'payMode'        => '0005555',
                'receivableType'        => 'D00',// D00,T01,D01->D00 为 D+0,T01 为 T+1,D01 为 D+1
                'prdAmt'        => '1',
                'prdName'        => '充值卡',
            );

            $datato = $this->arrayToString($data);
            $string=$this->_createSign($datato,$return['signkey']);
            $data['signData']=$string;

            //添加日记
            $postdata = json_encode($data,JSON_UNESCAPED_UNICODE);
            $payLog = [
                'merchantId' => $return["memberid"], //商户id
                'productCode' => $productCode, //支付类型
                'outTradeId' => $out_trade_id, //商户订单号
                'channelMerchantId' => $return['mch_id'], //通道商户id
                'orderId' => $return['orderid'], //平台订单号
                'type' => GPayLogModel::$TYPE_REQUEST_CHANNEL, //日记类型
            ];
            $payLog['msg'] = $this->TITLE.'-提交的数据: '.$postdata;
            $this->payLogModel->add($payLog);
dump($data);
            $result = curlPost($this->URL, http_build_query($data));
            $resultjsonde = json_decode($result, true);
            //添加日记
            dump($result);
            $payLog['msg'] = $this->TITLE.'-返回的数据: '.urldecode(http_build_query($resultjsonde));
            $this->payLogModel->add($payLog);


            $hmacarray = "";
            foreach ($resultjsonde as $k => $v){
                if($k=="platmerord" || $k=="qrcode" || $k=="retCode" || $k=="retMsg"){
                    $hmacarray[$k]=$v;
                }
            }
            //dump($hmacarray);
            //验证通道的签名
            $hmac2 = $this->arrayToString($hmacarray);
            $bemd52 = $this->_createSign($hmac2,$return['signkey']);
            if($bemd52 != $resultjsonde['signData']){
                //添加日记
                $payLog['msg'] = $this->TITLE.'-通道验签失败, 通道签名='.$resultjsonde['signData'].', 平台签名='.$bemd52;
                $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
                $this->payLogModel->add($payLog);
                exit('通道验签失败, 商户号='.$return["memberid"].', 商户订单号='.$out_trade_id);
            }

//保存通道订单号
            $channelOrderId = $resultjsonde['platmerord'];
            $orderModel = M("Order");
            $orderModel->where(['pay_orderid' => $return["orderid"]])->save(['channel_order_id' => $channelOrderId]);

            if($returnType == 'json') {
                if($resultjsonde['code']=="1"){
                    echo '{"code":"1","qrcode":"'.$resultjsonde['qrcode'].'","desc":"SUCCESS"}';
                }else{
                    echo '{"code":"0","qrcode":"","desc":"'.$resultjsonde['desc'].'"}';
                }
            } else {
                //生成支付二维码
                if($resultjsonde['code']=="1"){
                    import("Vendor.phpqrcode.phpqrcode",'',".php");
                    $url = urldecode($resultjsonde['qrcode']);
                    $QR = "Uploads/codepay/". $return["orderid"] . ".png";//已经生成的原始二维码图
                    \QRcode::png($url, $QR, "L", 20);
                    $this->assign("imgurl", $this->_site.$QR);
                    $this->assign('params',$return);
                    $this->assign('orderid',$return['orderid']);
                    $this->assign('money',$return['amount']);
                    $this->display("WeiXin/qq");

                }
            }


        }
    }


    // 页面通知返回
    public function callbackurl()
    {
        die('支付成功');
        $rawData = file_get_contents("php://input");
        $data = $this->object_to_array(json_decode($rawData, true));
        $payLog = [
            'type' => GPayLogModel::$TYPE_CHANNEL_CALLBACK,
            'productCode' => '123',
        ];
        //2018030109474114569026
        $payLog['msg'] = $this->TITLE.'-callback返回数据: '.$rawData;
        $this->payLogModel->add($payLog);
        //die();
        $Order = M("Order");
        $pay_status = $Order->where("pay_orderid = '".$data["prdOrdNo"]."'")->getField("pay_status");
        if($pay_status <> 0){
            $this->EditMoney($_REQUEST["prdOrdNo"], '', 1);
        }else{
            exit("error");
        }
    }

    // 服务器点对点返回
    public function notifyurl()
    {

        /*'{"signData":"08B537E94F80A15E4DCA9AA19EB65738","versionId":"1.0","orderAmount":"1100","transType":"008","asynNotifyUrl":"http:\\/\\/huapay.cc\\/Pay_XinFuBaoWxScan_notifyurl.html","payTime":"20180228105705","synNotifyUrl":"http:\\/\\/huapay.cc\\/Pay_XinFuBaoWxScan_callbackurl.html","orderStatus":"01","signType":"MD5","merId":"100520455","payId":"968681024579969024","prdOrdNo":"2018022810552877104924"}'*/

        //$rawData = file_get_contents("php://input");
        $data = $_REQUEST;
        //$data =json_decode($rawData, true);


        //添加日志
        $merchantId = null;
        $productCode = null;
        $outTradeId = null;
        $channelMerchantId = $data['merId'];
        $orderId = $data['prdOrdNo'];
        $amount =strval($data['orderAmount']);
        $payLog = [
            'merchantId' => $merchantId,
            'productCode' => $productCode,
            'outTradeId' => $outTradeId,
            'channelMerchantId' => $channelMerchantId,
            'orderId' => $orderId,
            'type' => GPayLogModel::$TYPE_CHANNEL_NOTIFY,
        ];
        $payLog['msg'] = $this->TITLE.'-notify返回的数据: '.json_encode($data,JSON_UNESCAPED_UNICODE);
        $this->payLogModel->add($payLog);

        //查找订单
        $orderModel = M("Order");
        $order = $orderModel->where(['pay_orderid' => $orderId])->find();
        if(empty($order)){
            $payLog['msg'] = $this->TITLE.'-平台订单不存在';
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('fail');
        }

        //验证通道的签名
        $signkey = $order['key'];

        foreach ($data as $k => $v){
            if($k<>"signData"){
                $hmacarray[$k]=$v;
            }
        }


        $hmac2 = $this->arrayToString($hmacarray);
        $md5str = $this->_createSign($hmac2,$signkey);

        if($md5str != $data['signData']){
            $payLog['msg'] = $this->TITLE.'-验签失败, 通道签名='.$data['signData'].', 平台签名='.$md5str;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            //exit('fail');
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
            //exit('fail'); //@todo 金额浮点数暂时不比较，有可能不相等，先运行一段时间在打开
        }
        if ($order["memberid"] != $channelMerchantId) {
            $payLog['msg'] = $this->TITLE.'-通道商户号不相等，通道传的通道商户号='.$channelMerchantId.', 平台的通道商户号='.$order["memberid"];
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            //exit('fail');//@todo
        }

        if ($data['orderStatus'] == '01'){
            $this->EditMoney($orderId, $this->CODE, 0);

            //添加日记
            $payLog['msg'] = $this->TITLE.'-支付成功, 修改订单状态为: 成功,未返回';
            $this->payLogModel->add($payLog);

            exit('success');
        } else {
            //添加日记
            $payLog['msg'] = $this->TITLE.'-支付失败';
            $payLog['level'] = GPayLogModel::$LEVEL_WARNING;
            $this->payLogModel->add($payLog);

            exit('fail');
        }


    }
    public function arrayToString($params){
        $sign_str = '';
        // 排序
        ksort ( $params );
        foreach ( $params as $key => $val ) {

            $sign_str .= sprintf ( "%s=%s&", $key, $val );
        }
        return substr ( $sign_str, 0, strlen ( $sign_str ) - 1 );

    }
    /**
     * 生成签名
     * @param $data
     * @param $key
     * @return string
     */

    private function object_to_array($obj) {
        $obj = (array)$obj;
        foreach ($obj as $k => $v) {
            if (gettype($v) == 'resource') {
                return;
            }
            if (gettype($v) == 'object' || gettype($v) == 'array') {
                $obj[$k] = (array)object_to_array($v);
            }
        }
        return $obj;
    }
    protected function _createSign($input, $key){
        $pieces = explode("&", $input);
        sort($pieces);
        $string='';

       /* foreach ($params as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $v . "#";
            }
        }*/

        foreach ($pieces as $value){
            if($value!=''){
                $vlaue1= explode("=", $value);
                if($vlaue1[1]!=''&&$value[1]!=null){
                    $string=$string.$value.'&';
                }
            }
        }
        $string=$string.'key='. $key;
        $sign=strtoupper(md5($string));
        $string=$sign;
        //$string=$string.'&signData='.$sign;
        return $string;
    }

    public function _postCurl($string,$TRANS_URL)
    {
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_URL,$TRANS_URL);
        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,0);
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$string);
        $data = curl_exec($ch);
        $returnData=json_decode($data,true);
        return  $returnData['qrcode'];
        curl_close($ch);

    }
    public function getSSLHttp($url){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        $data = curl_exec($curl);
        $httpCode = curl_getinfo($curl,CURLINFO_HTTP_CODE);

        if ( $httpCode != 200 ){
            $data="https connect timeout";
        }
        curl_close($curl);
        return $data;
    }

    public function onlinequery(){

        //$orderid = 'P180202959250344481652736';
        $orderid = I('request.orderid');

        if ($orderid==""){
            //不传订单号，就自己取一个
            $model = M("Order");

            $condition['pay_status']=0;
            $condition['pay_tongdao']='XinFuBaoQQScan';
            $condition['num']=array('lt',10);

            $list = $model->where($condition)->limit(1)->order('num');

            $orderid=$list->getField("pay_orderid");
        }
        $dbnum = M("channel_account")->where(array("id"=>"257"))->field("appid,mch_id,signkey")->find();

        if ($orderid!=""){

            //调用上游查询
            $data = [
                'signType'         => 'MD5',
                'merId'         => $dbnum['mch_id'],
                'prdOrdNo'         => $orderid
            ];

dump($data);
            $datato = $this->arrayToString($data);
            $string=$this->_createSign($datato,$dbnum['signkey']);
            $data['signData']=$string;
            $result = curlPost('http://online.atrustpay.com/payment/OrderStatusQuery.do', http_build_query($data));
            $resultde= json_decode($result,true);
dump($resultde);
            if ($resultde['retCode'] == '1'){ //已支付
                //$cmd = M('Order')->where(['pay_orderid'=>$orderid])->setField("pay_status",1);因为还要改金额，所以不能只简单的改状态
                $this->EditMoney($orderid, $this->CODE, 0);
            }

            $cmd = M('Order')->where(['pay_orderid'=>$orderid])->setInc("num",1);//查一次后加1，最多查10次
        }
    }

}
?>