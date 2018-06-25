<?php
/**
 * Created by PhpStorm.
 * author: 尚军邦
 * Date: 2017-02-01
 */
namespace Pay\Controller;

use Common\Model\GPayLogModel;

/**
 * 蓝创-网关不分PC/Web
 *  pay_type=015
 *  支持在线支付的银行和限额：https://static.95516.com/static/help/detail_38.html
 * Class LanChuangWangGuanController
 * @package Pay\Controller
 * @author 叶子龙
 */
class LanChuangWangGuanNoController extends PayController
{
    private $CODE = 'LanChuangWangGuanNo';
    private $TITLE = '蓝创-网关不分PC/Web';

    //支付
    public function Pay($array)
    {
        header("Content-Type:text/html;charset=UTF-8");
        date_default_timezone_set("Asia/Shanghai");

        $out_trade_id = I('request.pay_orderid'); //商户订单号
        $productCode = I('request.pay_bankcode'); //支付产品编号
        $body = I('request.pay_productname');

        $notifyurl = $this->_site . 'Pay_'.$this->CODE.'_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_'.$this->CODE.'_callbackurl.html'; //返回通知
        $parameter = array(
            'code' => $this->CODE, // 通道名称
            'title' => $this->TITLE,
            'exchange' => 1, // 金额比例
            'gateway' => "",
            'orderid' => "", //系统订单号
            'out_trade_id' => $out_trade_id, //外部商户订单号
            'body'=>$body,
            'channel'=>$array
        );
        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);

        //必要支付金额最小为15元
        /*if($return["amount"] < 15){
            exit('支付金额最小为15元');
        }
        if($return["amount"] > 200000){
            exit('单笔支付限额200000元');
        }*/

        $pay_orderid = $return["orderid"]; //系统订单号
        $memberid = $return["memberid"]; //商户号
        $mch_id = $return['mch_id']; //通道商户号
        $amount = $return["amount"]*100;
        $appid = $return['appid']; //通道账号
        $gateway = $return["gateway"];
        $signkey = $return['signkey'];

        $data = array(
            'version' => 'V001',
            'agre_type' => 'T',
            'inst_no' => $appid,
            'merch_id' => $mch_id,
            'pay_type' => '016',
            'commodity_name' => $body,
            'amount' => (string)$amount,
            'back_end_url' => $notifyurl,
            'return_url' => $callbackurl,
            'merch_order_no' => $pay_orderid
        );

        //签名
        $hmac = $this->SignParamsToString($data);
        $bemd5= $hmac."&key=".$signkey;
        $md5str = md5($bemd5);
        $data["sign"]= $md5str;

        $postdata = json_encode($data,JSON_UNESCAPED_UNICODE);

        //添加日记
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

        $result = $this->http_post_data($return['gateway'], $postdata);

        //添加日记
        $payLog['msg'] = $this->TITLE.'-返回的数据: '.urldecode(http_build_query($result));
        $this->payLogModel->add($payLog);

        $resultjsonde = json_decode($result['1'], true);

        //验证通道的签名
        $hmac2 = $this->SignParamsToString($resultjsonde);
        $bemd52 = $hmac2."&key=".$signkey;
        $md5str2 = md5($bemd52);
        if($md5str2 != $resultjsonde['sign']){
            //添加日记
            $payLog['msg'] = $this->TITLE.'-通道验签失败, 通道签名='.$resultjsonde['sign'].', 平台签名='.$md5str2;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('通道验签失败, 商户号='.$memberid.', 商户订单号='.$out_trade_id);
        }

        //保存通道订单号
        $channelOrderId = $resultjsonde['platform_order_no'];
        $orderModel = M("Order");
        $orderModel->where(['pay_orderid' => $pay_orderid])->save(['channel_order_id' => $channelOrderId]);

        //通道提供收银台
        echo $resultjsonde['html'];


    }

    /**
     * 通道回调, 再回调商户
     */
    public function callbackurl()
    {
        //返回参数$data:
        // resv=&bizType=000000&txnSubType=01&signature=SwwOhA1au%2BMNVcF00iqS4Q%3D%3D&succTime=&settleAmount=&settleCurrency=&txnType=01&settleDate=20180310&version=1.0.0&merResv1=&accessType=0&respMsg=5Lqk5piT5oiQ5Yqf&txnTime=20180310155639&merId=929040095023494&currency=CNY&respCode=1001&channelId=chinaGpay&txnAmt=0000000000000001&signMethod=MD5&merOrderId=NC41803101961377"

        $rawData = file_get_contents("php://input");

        $channelOrderId = $_POST['merOrderId'];
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
        $order = $orderModel->where(['channel_order_id' => $channelOrderId, 'pay_tongdao' => $this->CODE])->find();
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
    }

    //异步通知
    public function notifyurl()
    {
        //返回参数$data:
        // 九派-快捷-返回的数据: {"retmsg":"成功","order_datetime":"2018-03-10 15:56:39","version":"V001","pay_time":"2018-03-10 15:58:44","merch_id":"100000620000066","is_credit":"","up_channel_order_no":"","inst_no":"10000062","wallet_id":"","remark":"","pay_type":"015","retcode":"00","platform_order_no":"NC41803101961377","agre_type":"","sign":"9b0161cc722d351681522fb91099202c","amount":"1","merch_order_no":"WP2018031015563911375841"}

        //$data = $GLOBALS['HTTP_RAW_POST_DATA'];
        $rawData = file_get_contents("php://input");

        $data = json_decode($rawData, true);
        $merchantId = null;
        $productCode = null;
        $outTradeId = null;
        $channelMerchantId = $data['merch_id'];
        $orderId = $data['merch_order_no'];
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
        $payLog['msg'] = $this->TITLE.'-notify返回的数据: '.$rawData;
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
        $bemd5 = $hmac."&key=".$signkey;
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
        $orderAmount = strval($order["pay_amount"]*100);
        if ($orderAmount != $amount) {
            $payLog['msg'] = $this->TITLE.'-订单金额不相等，金额单位为分，通道传的金额='.$amount.', 平台的金额='.$orderAmount;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            //exit('fail'); //@todo 金额浮点数暂时不比较，有可能不相等，先运行一段时间在打开
        }
        if ($order["memberid"] != $channelMerchantId) {
            $payLog['msg'] = $this->TITLE.'-通道商户号不相等，通道传的通道商户号='.$channelMerchantId.', 平台的通道商户号='.$order["memberid"];
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('fail');
        }

        if ($data['retcode'] == '00'){
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

    /** *利用google api生成二维码图片
     * $content：二维码内容参数
     * $size：生成二维码的尺寸，宽度和高度的值
     * $lev：可选参数，纠错等级
     * $margin：生成的二维码离边框的距离
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

    private function getBytes($string) {
        $bytes = array();
        for($i = 0; $i < strlen($string); $i++){
            $bytes[] = ord($string[$i]);
        }
        return $bytes;
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

    function create_erweima($content, $size = '200', $lev = 'L', $margin= '0') {
        $content = urlencode($content);
        $image = 'http://chart.apis.google.com/chart?chs='.$size.'x'.$size.'&amp;cht=qr&chld='.$lev.'|'.$margin.'&amp;chl='.$content;
        return $image;
    }

    public function onlinequery(){

        //$orderid = 'P180202959250344481652736';
        $orderid = I('request.orderid');

        if ($orderid==""){
            //不传订单号，就自己取一个
            $model = M("Order");

            $condition['pay_status']=0;
            $condition['pay_tongdao']='XinJieWangGuan';
            $condition['num']=array('lt',10);

            $list = $model->where($condition)->limit(1)->order('num');

            $orderid=$list->getField("pay_orderid");
        }
        $dbnum = M("channel_account")->where(array("id"=>"274"))->field("appid,mch_id,signkey")->find();
        if ($orderid!=""){

            //调用上游查询
            $data = array(
                'version' => 'V001',
                'agre_type' => 'Q',
                'inst_no' => $dbnum['appid'],//机构号
                'merch_id' => $dbnum['mch_id'],//商户号
                'merch_order_no' => $orderid,//订单号
                'query_id' => '0',
                'order_datetime' => date("Y-m-d H:i:s")
            );

            $hmac = $this->SignParamsToString($data);
            $bemd5= $hmac."&key=".$dbnum['signkey'];
            $md5str = md5($bemd5);
            $data["sign"]= $md5str;
            $postdata = json_encode($data,JSON_UNESCAPED_UNICODE);
            $result = $this->http_post_data('http://online.esoonpay.com:28888/gateway/payment', $postdata);
            $json_obj = json_decode($result[1],true);

            if ($json_obj["retcode"]=="00"){ //已支付
                //$cmd = M('Order')->where(['pay_orderid'=>$orderid])->setField("pay_status",1);因为还要改金额，所以不能只简单的改状态
                $this->EditMoney($orderid, $this->CODE, 0);
            }

            $cmd = M('Order')->where(['pay_orderid'=>$orderid])->setInc("num",1);//查一次后加1，最多查10次
        }
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