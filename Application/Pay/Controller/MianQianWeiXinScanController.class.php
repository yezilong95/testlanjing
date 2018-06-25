<?php
/**
 * Created by PhpStorm.
 * author: 尚军邦
 * Date: 2017-05-18
 * Time: 11:33
 */
namespace Pay\Controller;

use Think\Exception;

class MianQianWeiXinScanController extends PayController
{
    private $CODE = 'MianQianWeiXinScan';
    private $TITLE = '免签系统-微信扫码';
    private $URL = 'https://pay.qpayapi.com';
    public function __construct()
    {
        parent::__construct();
    }

    //支付
    public function Pay($array)
    {
        header("Content-Type:text/html;charset=UTF-8");
        date_default_timezone_set("Asia/Shanghai");
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
        $keydata = array(
            'uid' => $return['mch_id'],
            'price' => $amount,
            'istype' => '2',
            'notify_url' => $notifyurl,
            'return_url' => $callbackurl,
            'orderid' => $return["orderid"],
            'token'=>$return['signkey']
        );
        $md5str= $this->SignParamsToString($keydata);
        $data = array(
            'uid' => $return['mch_id'],
            'price' => $amount,
            'istype' => '2',
            'notify_url' => $notifyurl,
            'return_url' => $callbackurl,
            'orderid' => $return["orderid"]
        );
        $hmac = md5($md5str);
        $data["key"]= $hmac;
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
        $data = $_REQUEST;
        $Order = M("Order");
        $token = $Order->where("pay_orderid = '".$data['orderid']."'")->getField("key");
        addSyslog($data, 1, 11);
        $signstr = $data['orderid'].$data['platform_trade_no'].$data['price'].$data['realprice'].$token;
        $signkey = md5($signstr);
        if($signkey==$data['key']){
            addSyslog('商户成功提交订单', 1, 11);
            $this->EditMoney($data["orderid"], $this->CODE, 0);
            exit('200');
        }else{
            exit('fail');
        }
    }
    private function SignParamsToString($params,$key) {
        $sign_str = '';
        // 排序
        ksort($params);

        $buff = "";
        foreach ($params as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $v;
            }
        }

        $buff = trim($buff);
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
}