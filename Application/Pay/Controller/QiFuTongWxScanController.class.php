<?php
namespace Pay\Controller;

/**
 * 启付通-微信扫码支付
 *  注意: 支付金额最小1元, 最大200元
 *  正式网关:http://www.dulpay.com/api/pay/code
 *  测试网关:http://39.108.113.220/api/pay/code
 * Class QiFuTongWxScanController
 * @package Pay\Controller
 * @author 尚军邦
 */
class QiFuTongWxScanController extends PayController
{
    private $CODE = 'QiFuTongWxScan';
    private $TITLE = '启付通-微信扫码';
    private $TRADE_TYPE = 'wechat';

    public function Pay($array)
    {
        header("Content-Type:text/html;charset=UTF-8");
        $logTitle = time() . ': ' . $this->TITLE . '-';
        addSyslog($logTitle.'商户提交的参数: '.json_encode($array), 1, 10);

        $out_trade_id = I('request.pay_orderid'); //商户订单号
        $body = I('request.pay_productname');
        $amount = I('request.pay_amount')*100;
        $parameter = array(
            'code' => $this->CODE, // 通道名称
            'title' => $this->TITLE, //通道名称
            'exchange' => 1, // 金额比例
            'gateway' => 'http://39.108.113.220/api/pay/code',
            'orderid'=>'', //系统订单号
            'out_trade_id' => $out_trade_id, //外部商户订单号
            'channel'=>$array,
            'body'=>$body
        );
        $return = $this->orderadd($parameter);
        $pay_orderid = $return["orderid"]; //系统订单号

        if ($return) {
            if($return["amount"] < 0.01){
                exit('支付金额最小为1元');
            }
            if($return["amount"] > 200){
                exit('单笔支付限额200元');
            }

            //提交到通道接口的参数
            $notifyurl = $this->_site . 'Pay_' . $this->CODE . '_notifyurl.html'; //异步通知
            $callbackurl = $this->_site . 'Pay_' . $this->CODE . '_callbackurl.html?orderid=' . $pay_orderid; //页面跳转通知
            $data = array(
                'amount'            => $amount, // 订单总金额，整形，此处单位为分
                'orderNo'       => $return["orderid"],
                'payTime'        => date("Y-m-d H:i:s"),
                'merchantNo'        => $return['mch_id'], // 商户号
                //'callBackUrl'       => $callbackurl,
                'notifyUrl'         => $notifyurl, //接收支付结果异步通知回调地址，PC 网站必填
                'currency'          => 'CNY',
                'goodsTitle'          => '电脑',
                'goodsDesc'          => '电脑',
                'randomStr'          => 'sds'.rand(100,999)."wer",
                'remark'          => 'v=1',
                'payType'           => "wechat", //交易类型: 微信H5 wechat_h5
                'appNo'             => '0a48cee90aeb89b56833c73bb93fbad5', //商户应用编号,
                'timestamp'         => date('YmdHiZ')
            );
            //签名
            $data['sign'] = $this->_createSign($data, $return['signkey']);
            $result = curlPost($return['gateway'], http_build_query($data));
            $resultde = json_decode($result);
            $this->logger("启付通微信扫码backmsg：".$result);
            $url =$resultde->data->codeUrl;
            if($resultde->code=="000000"){
                import("Vendor.phpqrcode.phpqrcode",'',".php");
                $url = urldecode($url);
                $QR = "Uploads/codepay/". $return["orderid"] . ".png";//已经生成的原始二维码图
                \QRcode::png($url, $QR, "L", 20);
                $this->assign("imgurl", $this->_site.$QR);
                $this->assign('params',$return);
                $this->assign('orderid',$return['orderid']);
                $this->assign('money',$return['amount']);
                $this->display("WeiXin/weixin");

            }

        }
    }

    // 通道页面通知返回
    public function callbackurl()
    {
        $pay_orderid = $_REQUEST["orderid"]; //系统订单号

        $logTitle = time() . ': ' . $this->TITLE . '-通道页面通知返回: ';
        addSyslog($logTitle.'系统订单号pay_orderid='.$pay_orderid, 1, 10);

        $Order = M("Order");
        $pay_status = $Order->where("pay_orderid = '".$pay_orderid."'")->getField("pay_status");
        if($pay_status <> 0){
            $this->EditMoney($pay_orderid, '', 1);
            addSyslog($logTitle.'修改订单状态成功, 系统订单号pay_orderid='.$pay_orderid, 1, 10);
        }else{
            addSyslog($logTitle.'未修改订单状态, 系统订单号pay_orderid='.$pay_orderid, 3, 10);
            exit("error");
        }
    }

    // 通道服务器通知
    public function notifyurl()
    {
        $data = $_REQUEST;
        $logTitle = time() . ': ' . $this->TITLE . '-通道服务器通知: ';
        addSyslog($logTitle.'返回数据: '.$data, 1, 10);
        //$data = xmlToArray($data);
        $pay_orderid = $data['orderNo']; //系统订单号
        if ($data['status'] == '1') {
            $this->EditMoney($pay_orderid, $this->CODE, 0);
            addSyslog('确认成功支付, 修改订单状态成功, 系统订单号pay_orderid='.$pay_orderid, 1, 11);
            $this->callbackurl($pay_orderid);
            exit('success');
        } else {
            addSyslog('未确认成功支付, 未修改订单状态, 系统订单号pay_orderid='.$pay_orderid, 3, 11);
            exit('fail');
        }
    }

    /**
     * 生成签名, 将参数按键值对key=value的形式组装成一个数组，将该数组按自然排序后使用&进行拼接，最后加入密钥进行md5
     * @param $data
     * @param $key
     * @return string
     */
    protected function _createSign($data, $key){
        $sign = '';
        ksort($data);

        foreach( $data as $k => $vo ){
            $sign .= $k . '=' . $vo . '&';
        } 

        $sign = trim($sign, '&') . $key;

        $logTitle = time() . ': ' . $this->TITLE . '-';
        addSyslog($logTitle.'md5前的字符串: '.$sign, 1, 10);

        return md5($sign);
    }
    // 交易记录日志
    private function logger($content){
        $logSize=100000;
        $log="log.txt";
        if(file_exists($log) && filesize($log)  > $logSize){
            unlink($log);
        }
        file_put_contents($log,date('Y-m-d H:i:s')." ".$content."\n",FILE_APPEND);
    }
}
?>