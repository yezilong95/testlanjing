<?php
/**
 * Created by PhpStorm.
 * author: 尚军邦
 * Date: 2017-05-18
 * Time: 11:33
 */
namespace Pay\Controller;

use Think\Exception;
use Common\Model\GPayLogModel;

class YiBaoFourBankController extends PayController
{
    private $CODE = 'YiBaoFourBank';
    private $TITLE = '易宝第四方网银';
    //private $URL = 'http://test.jingbao.net.cn/middlepaytrx/netpay/gateway';
    private $URL = 'http://api.jingbao.net.cn/middlepaytrx/netpay/gateway';
    public function __construct()
    {
        parent::__construct();
    }

    //支付
    public function Pay($array)
    {

        header("Content-Type:text/html;charset=UTF-8");
        date_default_timezone_set("Asia/Shanghai");
        vendor('YiBaoBank.yeepayCommon');
        $out_trade_id = I("request.pay_orderid");
        $productCode = I('request.pay_bankcode'); //支付产品编号
        $body = I('request.pay_productname');
        $amount = I('request.pay_amount');
        $notifyurl = $this->_site . 'Pay_YiBaoFourBank_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_YiBaoFourBank_callbackurl.html'; //返回通知
        $parameter = array(
            'code' => $this->CODE, // 通道名称
            'title' => $this->TITLE,
            'exchange' => 1, // 金额比例
            'gateway' => "",
            'orderid' => '',
            'out_trade_id' => $out_trade_id,
            'body'=>$body,
            'channel'=>$array
        );

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);
        $data = array(
            'trxType' => 'OnlinePay',
            'r1_merchantNo' => $return['mch_id'],
            'r2_orderNumber' => $return["orderid"],
            'r3_amount' => $amount,
            'r10_callbackUrl' => $callbackurl,
            'r11_serverCallbackUrl' => $notifyurl,
            'r12_orderIp' => $_SERVER['REMOTE_ADDR'],
        );
        $md5str= $this->SignParamsToString($data);
        $hmac = md5("#".$md5str."#".$return['signkey']);
        $data["sign"]= $hmac;

        $postdata = json_encode($data,JSON_UNESCAPED_UNICODE);
        //添加日记
        $payLog = [
            'merchantId' => $return["memberid"],//商户ID
            'productCode' => $productCode,//支付类型
            'outTradeId' => $out_trade_id,//商户订单号
            'channelMerchantId' => $return['mch_id'],//通道商户id
            'orderId' => $return['orderid'],//平台订单号
            'type' => GPayLogModel::$TYPE_REQUEST_CHANNEL,//日记类型
        ];
        $payLog['msg'] = $this->TITLE.'-提交的数据: '.$postdata;
        $this->payLogModel->add($payLog);

        $result = createForm($this->URL, $data);


        //添加日记
        $payLog['msg'] = $this->TITLE.'-浏览器表单提交的html: '.$result;
        $this->payLogModel->add($payLog);


        //保存通道订单号
        $channelOrderId = $resultjsonde['platform_order_no'];
        $orderModel = M("Order");
        $orderModel->where(['pay_orderid' => $return["orderid"]])->save(['channel_order_id' => $channelOrderId]);

        echo $result;
    }






    //同步通知
    public function callbackurl()
    {
        $rawData = file_get_contents("php://input");

        $channelOrderId = $_POST['r2_orderNumber'];
        //$channelMerchantId = $_POST['merId']; //不是通道商户号

        //添加日记
        $payLog = [
            'type' => GPayLogModel::$TYPE_CHANNEL_CALLBACK,
            'productCode' => '912',
        ];
        $payLog['msg'] = $this->TITLE.'-返回数据: '.$rawData;
        $this->payLogModel->add($payLog);

        //查找订单



        $orderModel = M("Order");
        $order = $orderModel->where(['pay_orderid' => $channelOrderId, 'pay_tongdao' => $this->CODE])->find();
        if(empty($order)){
            $payLog['msg'] = $this->TITLE.'-平台订单不存在, 通道订单号='.$channelOrderId.', 返回数据: '.http_build_query($_POST);
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit($payLog['msg']);
        }

        //添加日记
        $payLog['merchantId'] = $order['pay_memberid'];
        $payLog['productCode'] = $order['pay_bankcode'];
        $payLog['outTradeId'] = $order['out_trade_id'];
        $payLog['orderId'] = $order['pay_orderid'];

        //验证通道的签名, 不要验证签名, 因为通道的签名方式与支付不一样
//        $hmac = $this->SignParamsToString($_POST);
//        $bemd5 = $hmac."&key=".$order['key'];
//        $md5str = md5($bemd5);
//        if($md5str != $_POST['signature']){
//            //添加日记
//            $payLog['msg'] = '通道验签失败, 通道签名='.$_POST['signature'].', 平台签名='.$md5str;
//            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
//            $this->payLogModel->add($payLog);
//        }

        if($order['pay_status'] <> 0){
            //添加日记
            $payLog['msg'] = $this->TITLE.'-成功, 当前订单状态='.$order['pay_status'];
            $this->payLogModel->add($payLog);

            $this->EditMoney($order['pay_orderid'], '', 1);
        }else{;
            //添加日记
            $payLog['msg'] = $this->TITLE.'-失败, 当前订单状态='.$order['pay_status'];
            $this->payLogModel->add($payLog);

            exit("error");
        }

        //原来的代码
       /* $Order = M("Order");
        $pay_status = $Order->where("pay_orderid = '".$_REQUEST["r2_orderNumber"]."'")->getField("pay_status");
        if($pay_status <> 0){
            $this->EditMoney($_REQUEST["r2_orderNumber"], '', 1);
        }else{
            exit("error");
        }*/


    }

    public function dd(){
        
    }
    //异步通知
    public function notifyurl()
    {

        $rawData = file_get_contents("php://input");

        $data = $this->object_to_array(json_decode($rawData, true));
        $merchantId = null;
        $productCode = null;
        $outTradeId = null;
        $channelMerchantId = $data['r1_merchantNo'];
        $orderId = $data['r2_orderNumber'];
        $amount = $data['r3_amount'];

        //保存通道订单号
        $channelOrderId = $orderId;
        $orderModel = M("Order");
        $orderModel->where(['pay_orderid' => $orderId])->save(['channel_order_id' => $channelOrderId]);

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
            $payLog['msg'] = $this->TITLE.'-平台订单不存在';
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('fail');
        }

        //验证通道的签名



        $signkey = $order['key'];
        $hmac = $this->SignParamsToString($data);
        $bemd5 = "#".$hmac."#".$signkey;
        $md5str = md5($bemd5);
        if($md5str != $data['sign']){
            $payLog['msg'] = $this->TITLE.'-验签失败, 通道签名='.$data['sign'].', 平台签名='.$md5str;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('fail');
        }

        //填充日记
        $payLog['merchantId'] = $order['pay_memberid'];
        $payLog['productCode'] = $order['pay_bankcode'];
        $payLog['outTradeId'] = $order['out_trade_id'];

        //验证是否合法订单
        if (format2Decimal($order["pay_amount"]) != format2Decimal($amount)) {
            $payLog['msg'] = $this->TITLE.'-订单金额不相等，金额单位为分，通道传的金额='.$amount.', 平台的金额='.$order["pay_amount"];
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            //exit('fail');  //@todo 金额浮点数暂时不比较，有可能不相等，先运行一段时间在打开
        }
        if ($order["memberid"] != $channelMerchantId) {
            $payLog['msg'] = $this->TITLE.'-通道商户号不相等，通道传的通道商户号='.$channelMerchantId.', 平台的通道商户号='.$order["memberid"];
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('fail');
        }

        if ($data['retCode'] == '0000'){
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

        #原来的代码
        /*$data = $_REQUEST;
        addSyslog($data, 1, 11);
        if ($data['retCode'] == '0000'&& $data['r8_orderStatus']=="SUCCESS") {
            addSyslog('商户成功提交订单', 1, 11);
            $this->EditMoney($data["r2_orderNumber"], $this->CODE, 0);
            exit('success');
        } else {
            exit('fail');
        }*/
    }
    private function SignParamsToString($params,$key) {
        $sign_str = '';
        // 排序
       // ksort($params);

        $buff = "";
        foreach ($params as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $v . "#";
            }
        }

        $buff = trim($buff, "#");
        return $buff;
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



    function onlinequery(){

        //$orderid = 'P180202959250344481652736';
        $orderid = I('request.orderid');

        if ($orderid==""){
            //不传订单号，就自己取一个
            $model = M("Order");

            $condition['pay_status']=0;
            $condition['pay_tongdao']='YiBaoFourBank';
            $condition['num']=array('lt',10);

            $list = $model->where($condition)->limit(1)->order('num');

            $orderid=$list->getField("pay_orderid");
        }

        if ($orderid!=""){

            //调用上游查询
            $data = array(
                'trxType' => 'OnlineQuery',
                'r1_merchantNo' => 'KY0000000136',
                'r2_orderNumber' => $orderid
            );
            $md5str= $this->SignParamsToString($data);
            $hmac = md5("#".$md5str."#"."yHPAWkEWGYo9dXKKALLJpFZeSz6fSkkx");//注意:查询密钥和支付密钥不同

            $get_token_url ="http://api.jingbao.net.cn/middlepaytrx/online/query?trxType=OnlineQuery&r1_merchantNo=KY0000000136&r2_orderNumber=".$orderid."&sign=".$hmac;
            
            $ch = curl_init();
            curl_setopt($ch,CURLOPT_URL,$get_token_url);
            curl_setopt($ch,CURLOPT_HEADER,0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1 );
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE );
            $res = curl_exec($ch);
            curl_close($ch);

            dump($res);
            $json_obj = json_decode($res,true);
            if ($json_obj["r8_orderStatus"]=="SUCCESS"){ //已支付
                //$cmd = M('Order')->where(['pay_orderid'=>$orderid])->setField("pay_status",1);因为还要改金额，所以不能只简单的改状态
				$this->EditMoney($orderid, $this->CODE, 0);
            }

            $cmd = M('Order')->where(['pay_orderid'=>$orderid])->setInc("num",1);//查一次后加1，最多查10次
        }
    }



}