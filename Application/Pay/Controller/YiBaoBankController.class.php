<?php
/**
 * Created by PhpStorm.
 * User: 尚军邦
 * Date: 2017-05-18
 * Time: 11:33
 */
namespace Pay\Controller;

use Think\Exception;

class YiBaoBankController extends PayController
{
    public function __construct()
    {
        parent::__construct();
    }

    //支付
    public function Pay($array)
    {
        header("Content-Type:text/html;charset=UTF-8");
        vendor('YiBaoBank.yeepayCommon');
        $orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');
        $amount = I('request.pay_amount');
        $notifyurl = $this->_site . 'Pay_YiBaoBank_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_YiBaoBank_callbackurl.html'; //返回通知
        $parameter = array(
            'code' => 'YiBaoBank', // 通道名称
            'title' => '易宝网银支付',
            'exchange' => 1, // 金额比例
            'gateway' => "",
            'orderid' => $orderid,
            'out_trade_id' => "",
            'body'=>$body,
            'channel'=>$array
        );

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);
        $data = array(
            'p0_Cmd' => 'Buy', // 通道名称
            'p1_MerId' => $return['mch_id'],//商户编号
            'p2_Order' => $orderid, // 订单号
            'p3_Amt' => $amount,//支付金额
            'p4_Cur' => 'CNY',
            'p5_Pid' => "foods",//商品名称
            'p6_Pcat'=>"foods",//商品种类
            'p7_Pdesc'=>"foods",//商品描述
            'p8_Url'=>$callbackurl,//回调地址
            'p9_SAF'=>"0",//送货地址
            'pb_ServerNotifyUrl'=>$notifyurl,//服务器通知地址
            'pa_MP'=>"",//商户扩展信息
            'pd_FrpId'=>"",//支付通道编码
            'pm_Period'=>"7",//订单有效期
            'pn_Unit'=>"day",//订单有效期
            'pr_NeedResponse'=>"1",//应答机制
            'pt_Address'=>"深圳",//地区
            'pt_Email'=>"731068061@qq.com",//email
        );

        $md5str= implode($data);
        $hmac = HmacMd5($md5str,$return['signkey']);
        $data["hmac"]= $hmac;
        echo createForm('https://www.yeepay.com/app-merchant-proxy/node', $data);

    }






    //同步通知
    public function callbackurl()
    {
        $Order = M("Order");
        $pay_status = $Order->where("pay_orderid = '".$_REQUEST["r6_Order"]."'")->getField("pay_status");
        if($pay_status <> 0){
            $this->EditMoney($_REQUEST["r6_Order"], '', 1);
        }else{
            exit("error");
        }
    }

    //异步通知
    public function notifyurl()
    {
        $data = $_REQUEST;
        addSyslog($data, 1, 11);
        if ($data['r1_Code'] == '1') {
            addSyslog('商户成功提交订单', 1, 11);
            $this->EditMoney($data["r6_Order"], YiBaoBank, 0);
            exit('success');
        } else {
            exit('fail');
        }
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