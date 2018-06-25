<?php
/**
 * 信捷银联扫码
 * Class XinJieYinLianScanController
 * @package Payment\Controller
 * @author 尚军邦
 */
namespace Pay\Controller;

use Think\Exception;
use Common\Model\GPayLogModel;

class XinJieQQScanController extends PayController
{
    private $CODE = 'XinJieQQScan';
    private $TITLE = '信捷QQ扫码';


    //支付
    public function Pay($array)
    {

        header("Content-Type:text/html;charset=UTF-8");
        date_default_timezone_set("Asia/Shanghai");
        $orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');
        $returnType = I('request.return_type', 'html'); //返回值：html，json
        $amount = I('request.pay_amount')*100;
        $notifyurl = $this->_site . 'Pay_'.$this->CODE.'_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_'.$this->CODE.'_callbackurl.html'; //返回通知
        $parameter = array(
            'code' => $this->CODE, // 通道名称
            'title' => $this->TITLE,
            'exchange' => 1, // 金额比例
            'gateway' => "",
            'orderid' => "",
            'out_trade_id' => $orderid,
            'body'=>$body,
            'channel'=>$array
        );

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);
        $appid = $return['appid'];
        $data = array(
            'version' => 'V001',
            'agre_type' => 'T',
            'inst_no' => $return['appid'],
            'merch_id' => $return['mch_id'],
            'pay_type' => '020',
            'commodity_name' =>  $body,
            'amount' => (string)$amount,
            'back_end_url' => $notifyurl,
            'return_url' => $callbackurl,
            'merch_order_no' => $return["orderid"]
        );

        //$md5str= implode($data);
        $hmac = $this->SignParamsToString($data);
        $bemd5= $hmac."&key=".$return['signkey'];
        $md5str = md5($bemd5);
        $data["sign"]= $md5str;
        $postdata = json_encode($data,JSON_UNESCAPED_UNICODE);
        $result = $this->http_post_data($return['gateway'], $postdata);
        $resultjsonde = json_decode($result['1']);

        if($returnType =="json"){
            if($resultjsonde->retcode=="00"){
                echo '{"code":"1","qrcode":"'.$resultjsonde->payInfo.'","desc":"SUCCESS"}';
            }else{
                echo '{"code":"0","qrcode":"","desc":"'.$resultjsonde->retmsg.'"}';
            }
        }else{
            //生成支付二维码
            if($resultjsonde->retcode=="00"){
                import("Vendor.phpqrcode.phpqrcode",'',".php");
                $url = urldecode($resultjsonde->payInfo);
                $QR = "Uploads/codepay/". $return["orderid"] . ".png";//已经生成的原始二维码图
                \QRcode::png($url, $QR, "L", 20);
                $this->assign("imgurl", $this->_site.$QR);
                $this->assign('params',$return);
                $this->assign('orderid',$return['orderid']);
                $this->assign('money',$return['amount']);
                $this->display("WeiXin/qq");

            }else{
                echo $resultjsonde->retmsg;
            }
        }


    }


    public function test(){
        $postdata='{"retmsg":"成功","order_datetime":"2018-01-27 19:18:26","version":"V001","sign":"50348ebf5b559172857f93c21d109bf2","merch_id":"100000560000056","pay_type":"020","retcode":"00","platform_order_no":"NC11801270174196","channel":"C110","amount":"300","html":"","agre_type":"T","payInfo":"https://qpay.qq.com/qr/65f55a41","inst_no":"10000056","merch_order_no":"E20180127200855720668"}';
        $result = $this->http_post_data('http://zhifujia.cc/Pay_XinJieQQScan_notifyurl.html', $postdata);
        dump($result);
    }



    //同步通知
    public function callbackurl()
    {
        $rawData=json_encode($_REQUEST);
        $orderid = $_REQUEST["orderid"];
        //添加日记
        $payLog = [
            'type' => GPayLogModel::$TYPE_CHANNEL_CALLBACK,
            'productCode' => '908',
        ];
        $payLog['msg'] = $this->TITLE.'-callback返回数据: '.$rawData;
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

    //异步通知
    public function notifyurl(){
        //回调数据
        /*{"retmsg":"成功","order_datetime":"2018-03-09 17:19:54","version":"V001","pay_time":"2018-03-09 17:20:29","merch_id":"100000560000065","is_credit":"","up_channel_order_no":"","inst_no":"10000056","wallet_id":"","remark":"","pay_type":"026","retcode":"00","platform_order_no":"NC01803091947247","agre_type":"","sign":"a780cd416bda3fe82f03a6694e69726b","amount":"200","merch_order_no":"2018030917195482541952"}*/

        $rawData = file_get_contents("php://input");
        $data = json_decode($rawData,true);

        $merchantId = null;
        $productCode = null;
        $outTradeId = null;
        $channelMerchantId = $data['merch_id'];
        $orderId = $data["merch_order_no"];
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
            $payLog['msg'] = $this->TITLE.'-平台订单不存在, 平台订单号='.$orderId.', 返回数据: '.$rawData;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('fail1');
        }

        //验证通道的签名
        $channel = M('ChannelAccount')->where(['mch_id'=>$order['memberid']])->find();
        $hmac = $this->SignParamsToString($data);
        $bemd5= $hmac."&key=".$channel['signkey'];
        $newSign = md5($bemd5);

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
        $orderAmount =strval($order["pay_amount"]*100);
        if ($orderAmount != $amount) {
            $payLog['msg'] = $this->TITLE.'-订单金额不相等，金额单位为分，通道传的金额='.$amount.', 平台的金额='.$orderAmount;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            die("fail3"); //@todo 金额浮点数暂时不比较，有可能不相等，先运行一段时间在打开
        }
        if ($order["memberid"] != $channelMerchantId) {
            $payLog['msg'] = $this->TITLE.'-通道商户号不相等，通道传的通道商户号='.$channelMerchantId.', 平台的通道商户号='.$order["memberid"];

            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            die("fail4");
        }
        if($data['retcode'] == '00'){
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
            exit('fail5');
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
}