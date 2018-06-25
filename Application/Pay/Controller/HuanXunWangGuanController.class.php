<?php
/**
 * Created by PhpStorm.
 * author: 叶子龙
 * Date: 2017-02-01
 */
namespace Pay\Controller;

use Common\Model\GPayLogModel;

/**
 * 环讯-网关
 *  pay_type=015
 *
 * Class HuanXunWangGuanController
 * @package Pay\Controller
 * @author 叶子龙
 */
class HuanXunWangGuanController extends PayController
{
    private $CODE = 'HuanXunWangGuan';
    private $TITLE = '环讯-网关';
    private $MERCERT = '';
    private $POSTURL = '';

    //支付
    public function Pay($array)
    {

        header("Content-Type:text/html;charset=UTF-8");
        date_default_timezone_set("Asia/Shanghai");

        $out_trade_id = I('request.pay_orderid'); //商户订单号
        $productCode = I('request.pay_bankcode'); //支付产品编号
        $body = I('request.pay_productname','VIP充值');

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
        $return["notifyurl"] || $return["notifyurl"] = $this->_site . 'Pay_'.$this->CODE.'_notifyurl.html';
        $return['callbackurl'] || $return['callbackurl'] = $this->_site . 'Pay_'.$this->CODE.'_callbackurl.html';
        //必要支付金额最小为15元


        $pay_orderid = $return["orderid"]; //系统订单号
        $memberid = $return["memberid"]; //商户号
        $mch_id = $return['mch_id']; //通道商户号
        $app_id = $return['appid']; //交易账户号
        $amount = $return["amount"];
        $gateway = $return["gateway"];
        $signkey = $return['signkey'];
        /*if($return["amount"] < 2){
            exit('支付金额最小为2元');
        }
        if($return["amount"] > 50000){
            exit('单笔支付限额50000元');
        }*/
        $this->MERCERT = $signkey;
        $this->POSTURL = $gateway;
        $data
            = array(
            "Version"       => 'v1.0.0',
            "MerCode"       => $mch_id,
            "Account"       => $app_id,
            "MerCert"       => $signkey,
            "PostUrl"       => $gateway,
            "S2Snotify_url"       => $return["notifyurl"],
            "Return_url"  => $return['callbackurl'],
            "CurrencyType"	=> '156',
            "Lang"	=> 'GB',
            "OrderEncodeType"=>'5',
            "RetType"=>'1',
            "MerBillNo"	=> $pay_orderid,
            "MerName"	=> '',
            "MsgId"	=> '',
            "PayType"	=> '01',
            "FailUrl"   => '',
            "Date"	=> date("Ymd"),
            "ReqDate"	=> date("YmdHis"),
            "Amount"	=> $amount,
            "Attach"	=> '',
            "RetEncodeType"	=> '17',
            "BillEXP"	=> '',
            "GoodsName"	=> $body,
            "BankCode"	=> '',
            "IsCredit"	=> '',
            "ProductType"	=> ''
        );
        //签名

        $html_text = $this->buildRequestForm($data);
        echo $html_text;
        die;
        $prikey = $this->loadPk12Cert($this->PRI_KEY_PATH, $this->CERT_PWD);

        $sign = $this->sign($data, $prikey);

        // step3: 拼接post数据
        $post = array(
            'charset' => 'utf-8',
            'signType' => '01',
            'data' => json_encode($data),
            'sign' => $sign
        );

        $postdata = json_encode($post,JSON_UNESCAPED_UNICODE);

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

        // step4: post请求
        $result = $this->http_post_json($gateway . '/order/pay', $post);
        $arr =$this-> parse_result($result);

        //添加日记
        $payLog['msg'] = $this->TITLE.'-返回的数据: '.json_encode($arr,JSON_UNESCAPED_UNICODE);
        $this->payLogModel->add($payLog);

        //step5: 公钥验签
//step5: 公钥验签
        $pubkey =$this-> loadX509Cert($this->PUB_KEY_PATH);
        try {
           $this->pub_verify($arr['data'], $arr['sign'], $pubkey);
        } catch (\Exception $e) {
            echo $e->getMessage();
            exit;
        }

        // step6： 获取credential
        $data = json_decode($arr['data'], true);
        if ($data['head']['respCode'] == "000000") {
            $credential = $data['body']['credential'];
            $this->jump($credential);
        } else {
            dump($arr['data']);
        }

    }

    /**
     * 到前端利用衫德官方js进行跳转
     */
    public function jump($data){
        $this->assign("credential",$data);
        $this->display("ShanDe/post");
    }

    /**
     * 通道回调, 再回调商户
     */
    public function callbackurl()
    {
        //返回参数$data:
        // resv=&bizType=000000&txnSubType=01&signature=SwwOhA1au%2BMNVcF00iqS4Q%3D%3D&succTime=&settleAmount=&settleCurrency=&txnType=01&settleDate=20180310&version=1.0.0&merResv1=&accessType=0&respMsg=5Lqk5piT5oiQ5Yqf&txnTime=20180310155639&merId=929040095023494&currency=CNY&respCode=1001&channelId=chinaGpay&txnAmt=0000000000000001&signMethod=MD5&merOrderId=NC41803101961377"

        $data = $_POST;
        $rawData = json_encode($data,JSON_UNESCAPED_UNICODE);
        $dataarr = json_decode($data['data'],true);
        $orderId = $dataarr['body']['orderCode'];
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
        $order = $orderModel->where(['pay_orderid' => $orderId, 'pay_tongdao' => $this->CODE])->find();
        if(empty($order)){
            $payLog['msg'] = $this->TITLE.'-平台订单不存在, 通道订单号='.$orderId.', 返回数据: '.http_build_query($_POST);
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit($payLog['msg']);
        }

        //添加日记
        $payLog['merchantId'] = $order['pay_memberid'];
        $payLog['productCode'] = $order['pay_bankcode'];
        $payLog['outTradeId'] = $order['out_trade_id'];
        $payLog['orderId'] = $order['pay_orderid'];


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
        // {"sign":"wfeWcylM4\/Rzeg\/inErUJDaNzeep\/xDkDFCvERHqYA5aAtiJ0yYqhP+B8lLPzB2leww7Z6uFRpMsoIMTWWaiiRnYQJNFkNIW3\/tUF5cRaZuw+piosseWAtWnBHlqG3206vC2OWrLlk1ATKoOFA6U36rK7Pok7j9gsvIUkvUVbUJh6cy+tJpPerCq2uumJjoCekg80jaXkKmLWkhvOFPnU6jiyhbQIEix3Ecw3g28sE3ZdDCEq1XvpmDsLkFPZby\/53bsfaekXLdVqAz5gvsmKw2FzTBOkv\/ItRYN0d1W+gvVHZF142DiPiq5NHzZSm6h4VyvurayNo4lpJZd4bMZDQ==","extend":"","signType":"01","data":"{&quot;body&quot;:{&quot;orderCode&quot;:&quot;TT2018052317214101693846&quot;,&quot;tradeNo&quot;:&quot;2018052317215909390997627848&quot;,&quot;clearDate&quot;:&quot;20180523&quot;,&quot;orderStatus&quot;:&quot;1&quot;,&quot;payTime&quot;:&quot;20180523172159&quot;,&quot;buyerPayAmount&quot;:&quot;000000000001&quot;,&quot;accNo&quot;:&quot;&quot;,&quot;midFee&quot;:&quot;000000000020&quot;,&quot;totalAmount&quot;:&quot;000000000001&quot;,&quot;mid&quot;:&quot;15898373&quot;,&quot;discAmount&quot;:&quot;000000000000&quot;,&quot;bankserial&quot;:&quot;&quot;},&quot;head&quot;:{&quot;respCode&quot;:&quot;000000&quot;,&quot;respTime&quot;:&quot;20180523172242&quot;,&quot;version&quot;:&quot;1.0&quot;}}","charset":"UTF-8"}

        //$data = $GLOBALS['HTTP_RAW_POST_DATA'];


        $post = $_POST;
        $datainpost =stripslashes($post['data']);
        $dataarr = json_decode($datainpost,true);
        $dbody = $dataarr['body'];

        $rawData = json_encode($post,JSON_UNESCAPED_UNICODE);
        $merchantId = null;
        $productCode = null;
        $outTradeId = null;
        $channelMerchantId = $dbody['mid'];
        $orderId =$dbody['orderCode'];
        $amount =null;

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

        $verify_sign =  $post['sign'];
        $verify_pubkey =$this-> loadX509Cert($this->PUB_KEY_PATH);
        $new_sign = $this-> pub_verify($datainpost, $verify_sign, $verify_pubkey);
        if (!$new_sign) {
            $payLog['msg'] = $this->TITLE.'-验签失败, 通道签名='.$verify_sign.', 平台签名='.$new_sign;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('fail');
        }


        //填充日记
        $payLog['merchantId'] = $order['pay_memberid'];
        $payLog['productCode'] = $order['pay_bankcode'];
        $payLog['outTradeId'] = $order['out_trade_id'];

        //验证是否合法订单
        /*$orderAmount = strval($order["pay_amount"]*100);
        if ($orderAmount != $amount) {
            $payLog['msg'] = $this->TITLE.'-订单金额不相等，金额单位为分，通道传的金额='.$amount.', 平台的金额='.$orderAmount;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            //exit('fail'); //@todo 金额浮点数暂时不比较，有可能不相等，先运行一段时间在打开
        }*/
        if ($order["memberid"] != $channelMerchantId) {
            $payLog['msg'] = $this->TITLE.'-通道商户号不相等，通道传的通道商户号='.$channelMerchantId.', 平台的通道商户号='.$order["memberid"];
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('fail');
        }

        if ($dbody['orderStatus'] == '1'){
            $this->EditMoney($orderId, $this->CODE, 0);

            //添加日记
            $payLog['msg'] = $this->TITLE.'-支付成功, 修改订单状态为: 成功,未返回';
            $this->payLogModel->add($payLog);

            exit("respCode=000000");
        } else {
            //添加日记
            $payLog['msg'] = $this->TITLE.'-支付失败';
            $payLog['level'] = GPayLogModel::$LEVEL_WARNING;
            $this->payLogModel->add($payLog);

            exit('fail');
        }
    }

    public function buildRequestForm($para_temp) {
        //待请求参数xml
        $para = $this->buildRequestPara($para_temp);

        $sHtml = "<form id='ipspaysubmit' name='ipspaysubmit' method='post' action='".$this->POSTURL."'>";

        $sHtml.= "<input type='hidden' name='pGateWayReq' value='".$para."'/>";

        $sHtml = $sHtml."<input type='submit' style='display:none;'></form>";

        $sHtml = $sHtml."<script>document.forms['ipspaysubmit'].submit();</script>";

        return $sHtml;
    }
    public function buildRequestPara($para_temp) {
        $sReqXml = "<Ips>";
        $sReqXml .= "<GateWayReq>";
        $sReqXml .= $this->buildHead($para_temp);
        $sReqXml .= $this->buildBody($para_temp);
        $sReqXml .= "</GateWayReq>";
        $sReqXml .= "</Ips>";
        return $sReqXml;
    }
    public function buildHead($para_temp){
        $sReqXmlHead = "<head>";
        $sReqXmlHead .= "<Version>".$para_temp["Version"]."</Version>";
        $sReqXmlHead .= "<MerCode>".$para_temp["MerCode"]."</MerCode>";
        $sReqXmlHead .= "<MerName>".$para_temp["MerName"]."</MerName>";
        $sReqXmlHead .= "<Account>".$para_temp["Account"]."</Account>";
        $sReqXmlHead .= "<MsgId>".$para_temp["MsgId"]."</MsgId>";
        $sReqXmlHead .= "<ReqDate>".$para_temp["ReqDate"]."</ReqDate>";
        $sReqXmlHead .= "<Signature>".$this->md5Sign($this->buildBody($para_temp),$para_temp["MerCode"],$this->MERCERT)."</Signature>";
        $sReqXmlHead .= "</head>";
        return $sReqXmlHead;
    }
    public function buildBody($para_temp){
        $sReqXmlBody = "<body>";
        $sReqXmlBody .= "<MerBillNo>".$para_temp["MerBillNo"]."</MerBillNo>";
        $sReqXmlBody .= "<GatewayType>".$para_temp["PayType"]."</GatewayType>";
        $sReqXmlBody .= "<Date>".$para_temp["Date"]."</Date>";
        $sReqXmlBody .= "<CurrencyType>".$para_temp["CurrencyType"]."</CurrencyType>";
        $sReqXmlBody .= "<Amount>".$para_temp["Amount"]."</Amount>";
        $sReqXmlBody .= "<Lang>".$para_temp["Lang"]."</Lang>";
        $sReqXmlBody .= "<Merchanturl><![CDATA[".$para_temp["Return_url"]."]]></Merchanturl>";
        $sReqXmlBody .= "<FailUrl><![CDATA[".$para_temp["FailUrl"]."]]></FailUrl>";
        $sReqXmlBody .= "<Attach><![CDATA[".$para_temp["Attach"]."]]></Attach>";
        $sReqXmlBody .= "<OrderEncodeType>".$para_temp["OrderEncodeType"]."</OrderEncodeType>";
        $sReqXmlBody .= "<RetEncodeType>".$para_temp["RetEncodeType"]."</RetEncodeType>";
        $sReqXmlBody .= "<RetType>".$para_temp["RetType"]."</RetType>";
        $sReqXmlBody .= "<ServerUrl><![CDATA[".$para_temp["S2Snotify_url"]."]]></ServerUrl>";
        $sReqXmlBody .= "<BillEXP>".$para_temp["BillEXP"]."</BillEXP>";
        $sReqXmlBody .= "<GoodsName>".$para_temp["GoodsName"]."</GoodsName>";
        $sReqXmlBody .= "<IsCredit>".$para_temp["IsCredit"]."</IsCredit>";
        $sReqXmlBody .= "<BankCode>".$para_temp["BankCode"]."</BankCode>";
        $sReqXmlBody .= "<ProductType>".$para_temp["ProductType"]."</ProductType>";
        $sReqXmlBody .= "</body>";
        return $sReqXmlBody;
    }

    public function md5Sign($prestr, $merCode, $key)
    {
        $prestr = $prestr . $merCode . $key;
        return md5($prestr);
    }

}