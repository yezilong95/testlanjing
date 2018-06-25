<?php
namespace Pay\Controller;

/**
 * 信付宝-QQH5支付
 * Class XinFuBaoQQH5
 * @package Pay\Controller
 * author 尚军邦
 */
class XinFuBaoQQH5Controller extends PayController
{
    private $CODE = 'XinFuBaoQQH5';
    private $TITLE = '信付宝-QQH5';
    private $merId = '100520158';
    private $KEY = 'SDmETzESv0LX';
    private $payMode = '00033';
    private $URL = 'http://online.atrustpay.com/payment/PayApply.do';
    //private $TRADE_TYPE = 'trade.qqpay.native';

    public function Pay($array)
    {
        header("Content-Type:text/html;charset=UTF-8");

        $logTitle = time() . '-' . $this->TITLE . '-';
        addSyslog($logTitle.'商户提交的参数: '.http_build_query($_POST), 1, 10);

        $orderid = I('request.pay_orderid');
        $body = I('request.pay_productname');
        $amount = I('request.pay_amount');
        $tranChannel = "103";//银行编码固定103
        //$tranChannel = I('request.tranChannel');
        $accNoType="A";// 对公、对私-A:对私，B:对公
        //$accNoType=I('request.accNoType');
        $notifyurl = $this->_site . 'Pay_' . $this->CODE . '_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_' . $this->CODE . '_callbackurl.html'; //跳转通知
        $parameter = array(
            'code' => $this->CODE, // 通道名称
            'title' => $this->TITLE, //通道名称
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid'=>'',
            'out_trade_id' => $orderid, //外部订单号
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
                'orderAmount'            => $amount*100,
                'orderDate'         => date("YmdHis"), //订单日期
                'currency'        => 'RMB', //货币类型
                'accountType'        => '0', //银行卡类型-0：借记卡1：贷记卡
                'transType'      => '008',//交易类别
                'accNoType'        => $accNoType, //银行卡对公对私
                'asynNotifyUrl'  => $notifyurl,
                'synNotifyUrl'         => $callbackurl,
                'signType'        => 'MD5',
                'merId'        => $return['mch_id'],
                'prdOrdNo'        => $return["orderid"],
                'payMode'        => $this->payMode,
                'tranChannel'        => $tranChannel, //银行编码
                'receivableType'        => 'D00',// D00,T01,D01->D00 为 D+0,T01 为 T+1,D01 为 D+1
                'prdAmt'        => '1',
                'prdName'        => '充值卡',
                'prdDesc'        => '充值卡',
                'prdShortName'        => '卡',
                'pnum'        => '2',
                'merParam'        => 'fdss'

            );
            $datato = $this->arrayToString($data);
            $string=$this->_createSign($datato,$return['signkey']);
            $data['signData']=$string;
            $result = curlPost($this->URL, http_build_query($data));

            addSyslog($logTitle.'通道返回的参数: '.$result, 1, 10);

            echo $result;

//            $sVid = $this->get_between($result, "body>", "</body");
//            echo '<!doctype html>
//            <html>
//                <head>
//                    <meta charset="utf8">
//                    <title>正在跳转付款页</title>
//                </head>
//                <body>
//                <a href="' . $sVid . '" id="loaction"></a>
//                 <script>document.getElementById("loaction").click();</script>
//                </body>
//            </html>';


//dump($result);

           //echo $return = createForm($this->URL, $data);


        }
    }

    public function get_between($input, $start, $end) {
        $substr = substr($input, strlen($start)+strpos($input, $start),(strlen($input) - strpos($input, $end))*(-1));
        return $substr;
    }

    // 页面通知返回
    public function callbackurl()
    {
        $Order = M("Order");
        $pay_status = $Order->where("pay_orderid = '".$_REQUEST["prdOrdNo"]."'")->getField("pay_status");
        if($pay_status <> 0){
            $this->EditMoney($_REQUEST["prdOrdNo"], '', 1);
        }else{
            exit("error");
        }
    }

    // 服务器点对点返回
    public function notifyurl()
    {

        $data = $_REQUEST;

        addSyslog($data, 1, 11);


        if ($data['orderStatus'] == '01') {
            addSyslog('商户成功提交订单', 1, 11);
            $this->EditMoney($data["prdOrdNo"], $this->CODE, 0);
            exit('success');
        } else {
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
    protected function _createSign($input, $key){
        $pieces = explode("&", $input);
        sort($pieces);
        $string='';
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

}
?>