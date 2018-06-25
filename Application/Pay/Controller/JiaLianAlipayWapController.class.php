<?php
/**
 * Created by PhpStorm.
 * author: 尚军邦
 * Date: 2017-05-18
 * Time: 11:33
 */
namespace Pay\Controller;

use Think\Exception;

class JiaLianAlipayWapController extends PayController
{
    private $CODE = 'JiaLianAlipayWap';
    private $TITLE = '嘉联支付宝Wap';
    //private $URL = 'http://test.jingbao.net.cn/middlepaytrx/netpay/gateway';
    private $URL = 'http://m.jialianjinfu.com/Pay_index';
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
        $orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');
        $amount = I('request.pay_amount');
        $notifyurl = $this->_site . 'Pay_'.$this->CODE.'_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_'.$this->CODE.'_callbackurl.html'; //返回通知
        $parameter = array(
            'code' => $this->CODE, // 通道名称
            'title' => $this->TITLE,
            'exchange' => 1, // 金额比例
            'gateway' => "",
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body'=>$body,
            'channel'=>$array
        );

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);
        $data = array(
            "pay_memberid" => $return['mch_id'],
            "pay_orderid" => $return["orderid"],
            "pay_amount" => (string)$amount,//$amount,
            "pay_applydate" => date("Y-m-d H:i:s"),
            "pay_bankcode" => '904',
            "pay_notifyurl" => $notifyurl,
            "pay_callbackurl" => $callbackurl
        );
        $md5str= $this->SignParamsToString($data);
        $sign = strtoupper(md5($md5str . "key=" . $return['signkey']));
        $data["pay_md5sign"]= $sign;
        echo createForm($this->URL, $data);

    }






    //同步通知
    public function callbackurl()
    {
        $Order = M("Order");
        $pay_status = $Order->where("pay_orderid = '".$_REQUEST["orderid"]."'")->getField("pay_status");
        if($pay_status <> 0){
            $this->EditMoney($_REQUEST["orderid"], '', 1);
        }else{
            exit("error");
        }
    }

    //异步通知
    public function notifyurl()
    {
        $backData = $_POST;
        addSyslog($backData, 1, 11);
        $postData = Array
        (
            'amount' => $backData['amount'],
            'datetime' => $backData['datetime'],
            'memberid' => $backData['memberid'],
            'orderid' => $backData['orderid'],
            'returncode' => $backData['returncode'],
            'transaction_id' => $backData['transaction_id'],
        );
        $md5str= $this->SignParamsToString($postData);
        $order_model = M("Order");
        $signkey = $order_model->where("pay_orderid = '".$backData['orderid']."'")->getField("key");
        $signData = strtoupper(md5($md5str."key=".$signkey));
        if ($backData['returncode'] == '00') {
            if ($backData['sign'] == $signData) {
                addSyslog('商户成功提交订单', 1, 11);
                $this->EditMoney($backData["orderid"], $this->CODE, 0);
                exit('OK');
            }
        }else {
            exit('error');
        }
    }
    private function SignParamsToString($requestarray) {
        ksort($requestarray);
        $md5str = "";
        foreach ($requestarray as $key => $val) {
            if(!empty($val)){
                $md5str = $md5str . $key . "=" . $val . "&";
            }
        }
        return $md5str;
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