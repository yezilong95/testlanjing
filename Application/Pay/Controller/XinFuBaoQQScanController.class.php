<?php
namespace Pay\Controller;

/**
 * 信付宝-QQ扫码支付
 * Class TuBeiQQScanController
 * @package Pay\Controller
 * author 尚军邦
 */
class XinFuBaoQQScanController extends PayController
{
    private $CODE = 'XinFuBaoQQScan';
    private $TITLE = '信付宝-QQ扫码';
    private $KEY = 'SDmETzESv0LX';
    private $URL = 'http://online.atrustpay.com/payment/ScanPayApply.do';
    //private $TRADE_TYPE = 'trade.qqpay.native';

    public function Pay($array)
    {
        header("Content-Type:text/html;charset=UTF-8");
        addSyslog($array, 1, 10);

        $orderid = I('request.pay_orderid');
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
                'transType'      => '008',//交易类别
                'asynNotifyUrl'  => $notifyurl,
                //'asynNotifyUrl'  => 'http://sjb.ypyunedu.com/callback.php',
                'synNotifyUrl'         => $callbackurl,
                //'synNotifyUrl'         => 'http://sjb.ypyunedu.com/callback.html',
                'signType'        => 'MD5',
                'merId'        => $return['mch_id'],
                'prdOrdNo'        => $return["orderid"],
                'payMode'        => '00032',
                //'payMode'        => '0005555',
                'receivableType'        => 'D00',// D00,T01,D01->D00 为 D+0,T01 为 T+1,D01 为 D+1
                'prdAmt'        => '1',
                'prdName'        => '充值卡',
            );
            $datato = $this->arrayToString($data);
            $string=$this->_createSign($datato,$return['signkey']);
            $data['signData']=$string;
            $result = curlPost($this->URL, http_build_query($data));
            $resultde= json_decode($result);

            //dump($resultde->qrcode);
            if($resultde->code=="1"){
                echo '{"code":"1","qrcode":"'.$resultde->qrcode.'","desc":"SUCCESS"}';
            }else{
                echo '{"code":"0","qrcode":"","desc":"'.$resultde->desc.'"}';
            }
            die();


            //生成支付二维码
            if($resultde->code=="1"){
                import("Vendor.phpqrcode.phpqrcode",'',".php");
                $url = urldecode($resultde->qrcode);
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

       //$data = xmlToArray($data);

        /*$sign = $data['signData'];
        unset($data['signData']);

        $Order = M("Order");
        $signkey = $Order->where("pay_orderid = '".$data['prdOrdNo']."'")->getField("key");
        $respSign = strtoupper($this->_createSign($data, $signkey));*/

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