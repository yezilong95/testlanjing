<?php
namespace Pay\Controller;

use Common\Model\GPayLogModel;

/**
 * 信付宝-快捷
 * Class XinFuBaoKJController
 * @package Pay\Controller
 * @author 尚军邦
 */

class XinFuBaoKJController extends PayController
{
    private $CODE = 'XinFuBaoKJ';
    private $TITLE = '信付宝-快捷';
    private $PRODUCT_CODE = '912'; //平台的支付类型

	public function Pay($array)
    {
        $out_trade_id = I('request.pay_orderid'); //商户订单号
        $productCode = I('request.pay_bankcode'); //支付产品编号
        $body = trim(I('request.pay_productname')); //商品名称，要给默认名称，为空时上游签名失败
        if(empty($body))
            $body = '会员充值';

        $pay_amount = I("request.pay_amount", 0);

        $parameter = array(
            'code' => $this->CODE,
            'title' => $this->TITLE,
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid'=>'',
            'out_trade_id' => $out_trade_id, //外部订单号
            'channel'=>$array,
            'body'=>$body
        );
        //支付金额
        $pay_amount = I("request.pay_amount", 0);

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);

        //如果生成错误，自动跳转错误页面
        $return["status"] == "error" && $this->showmessage($return["errorcontent"]);

        //跳转页面，优先取数据库中的跳转页面
        $notifyurl = $this->_site . 'Pay_'.$this->CODE.'_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_'.$this->CODE.'_callbackurl.html'; //返回通知

        //获取请求的url地址
        $url = $return["gateway"];

        //获取toke值
        $arraystr = array(
            'versionId'  => '1.0',
            'orderAmount' => $return['amount']*100,
            'orderDate' => date('YmdHis', time()),
            'currency'   =>'RMB',
            'accountType' =>'0',
            'transType'  => '008',
            'asynNotifyUrl' => $return['notifyurl'],
            'synNotifyUrl'=> $callbackurl,
            'signType'  =>'MD5',
            'merId' => $return['mch_id'],
            'prdOrdNo' => $return['orderid'],
            'payMode'   => '00023',
            'receivableType' => 'T01',
            'prdAmt' => $return['amount']*100,
            'prdName' => $body,
        );

        $this->assign("out_trade_id",$out_trade_id);
        $this->assign("pay_amount",$pay_amount);
        $this->assign("arraystr",urlencode(http_build_query($arraystr)));
        $this->assign("return",urlencode(http_build_query($return)));
        $this->display('XinFuBaoKJCashier/index');

    }

    public function PayAgain(){
        $out_trade_id = I('request.pay_orderid'); //商户订单号
        $flag = I('request.flag'); //商户订单号
        $arraystr =urldecode(trim(I('request.arraystr')));
        $returnStr =urldecode(trim(I('request.ret')));

        $bankCardNo = I('request.bankCardNo');
        $tranChannel = I('request.tranChannel');
        $userName = I('request.userName');
        $idNo = I('request.idNo');
        $phone = I('request.phone');
        parse_str($arraystr,$array);
        parse_str($returnStr,$return);

        //获取请求的url地址
        $url = $return["gateway"];

        //获取toke值
        $array['orderAmount'] =(float)$array['orderAmount'];
        $array['prdAmt'] =(float)$array['prdAmt'];
        $array['bankCardNo'] =$bankCardNo;
        $array['tranChannel'] =$tranChannel;
        $array['userName'] =$userName;
        $array['idNo'] =$idNo;
        $array['phone'] =$phone;

        //添加数据库
        $addCardData['accountname']=$userName;
        $addCardData['cardnumber']=$bankCardNo;
        $addCardData['idcard']=$idNo;
        $addCardData['phone']=$phone;
        if($flag=='0'){
            dump($addCardData);
            $addCard = M('bankcard')
                ->add($addCardData);
        }

        $array['signData'] = $this->_createSign($array, $return['signkey']);
        $postdata = http_build_query($array);

        //添加日记
        $payLog = [
            'merchantId' => $return["memberid"],
            'productCode' => $return['bankcode'],
            'outTradeId' => $out_trade_id,
            'channelMerchantId' => $return['mch_id'],
            'orderId' => $return['orderid'],
            'type' => GPayLogModel::$TYPE_REQUEST_CHANNEL,
        ];
        $payLog['msg'] = $this->TITLE.'-提交的数据: '.$postdata;
        $this->payLogModel->add($payLog);

        // list($return_code, $return_content) = $this->httpPostData($url, $postdata);
        $result = createForm($url, $array);

        //添加日记
        $payLog['msg'] = $this->TITLE.'-返回的数据: '.$result;
        $this->payLogModel->add($payLog);


        echo $result;

        //验证通道的签名
        //同一http请求，通道没有签名，无需再验证签名

//        $return_data = json_decode($return_content, true);
//        $retCode = $return_data['retCode'];
//        $retMsg = $return_data['retMsg'];
//        $htmlText = $return_data['htmlText'];
//
//        //保存通道订单号
////        $channelOrderId = $resultjsonde['platform_order_no'];
////        $orderModel = M("Order");
////        $orderModel->where(['pay_orderid' => $pay_orderid])->save(['channel_order_id' => $channelOrderId]);
//
//        if($retCode == '1' && !empty($htmlText)){
//            echo $htmlText;
//        }else{
//            $this->showmessage($retMsg);
//        }

    }

    public function CardSearch(){
        $data = I('post.');
       $cardNum = M('bankcard')
           ->where(array('cardnumber'=>$data['bankCardNo']))
           ->field('accountname,idcard,phone')
           ->select();
       if($cardNum){
           echo json_encode($cardNum['0']);
       }else{
           $return = array(
               'status'=>'0',
               'info'=>'查无此人'
           );
           echo json_encode($return,JSON_UNESCAPED_UNICODE);
       }
    }

    protected function _createSign($data, $key){
        $sign = '';
        ksort($data);
        foreach( $data as $k => $vo ){
            $sign .= $k . '=' . $vo . '&';
        }
        return strtoupper(md5($sign . 'key=' .$key));
    }

    public function httpPostData($url, $data_string){

        $cacert = ''; //CA根证书  (目前暂不提供)
        $CA = false ;   //HTTPS时是否进行严格认证
        $TIMEOUT = 30;  //超时时间(秒)
        $SSL = substr($url, 0, 8) == "https://" ? true : false;
        
        $ch = curl_init ();
        if ($SSL && $CA) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);   //  只信任CA颁布的证书
            curl_setopt($ch, CURLOPT_CAINFO, $cacert);      //  CA根证书（用来验证的网站证书是否是CA颁布）
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);    //  检查证书中是否设置域名，并且是否与提供的主机名匹配
        } else if ($SSL && !$CA) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  //  信任任何证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);    //  检查证书中是否设置域名
        }


        curl_setopt ( $ch, CURLOPT_TIMEOUT, $TIMEOUT);
        curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, $TIMEOUT-2);
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data_string );
        curl_setopt ( $ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded') );
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        ob_start();
        curl_exec($ch);
        $return_content = ob_get_contents();
        ob_end_clean();

        $return_code = curl_getinfo ( $ch, CURLINFO_HTTP_CODE );
       
        curl_close($ch);
        return array (
            $return_code,
            $return_content
        );
    }

    /**
     * 通道回调
     *  返回数据：signType=MD5&merId=100520426&orderStatus=01&orderAmount=100&payTime=20180224203857
     *      &payId=967378094627233792&transType=008&prdOrdNo=2018022420380563268097&versionId=1.0
     *      &synNotifyUrl=http%3A%2F%2Fzhifujia.cc%2FPay_XinFuBaoKJ_callbackurl.html
     *      &asynNotifyUrl=http%3A%2F%2Fzhifujia.cc%2FPay_XinFuBaoKJ_notifyurl.html
     *      &merParam=null&signData=647B0036F7D97D5887B21A2013C11736
     *      &UM_distinctid=161c1f34a6768f-03e59009008f058-42564130-15f900-161c1f34a68661
     *      &CNZZDATA1261742514=1383242067-1519448456-%7C1519470092&PHPSESSID=cjalvgrhgdddo6p0q1f5n4pfd6&think_language=zh-CN
     */
	public function callbackurl()
    {
        $rawData = http_build_query($_REQUEST);
        $orderid = $_REQUEST["prdOrdNo"];

        //添加日记
        $payLog = [
            'type' => GPayLogModel::$TYPE_CHANNEL_CALLBACK,
            'productCode' => $this->PRODUCT_CODE,
        ];
        $payLog['msg'] = $this->TITLE.'-返回数据: '.$rawData;
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

    /**
     * 通道通知
     *  返回数据：signData=647B0036F7D97D5887B21A2013C11736&versionId=1.0&orderAmount=100&transType=008
     *      &asynNotifyUrl=http%3A%2F%2Fzhifujia.cc%2FPay_XinFuBaoKJ_notifyurl.html&payTime=20180224203857
     *      &synNotifyUrl=http%3A%2F%2Fzhifujia.cc%2FPay_XinFuBaoKJ_callbackurl.html&orderStatus=01
     *      &signType=MD5&merId=100520426&payId=967378094627233792&prdOrdNo=2018022420380563268097
     */
    public function notifyurl()
    {
        $rawData = file_get_contents("php://input");
        $data = I('post.', '');

        $merchantId = null;
        $productCode = $this->PRODUCT_CODE;
        $outTradeId = null;
        $channelMerchantId = $data['merId'];
        $orderId = $data["prdOrdNo"];

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
            $payLog['msg'] = $this->TITLE.'-平台订单不存在, 平台订单号='.$orderId.', 返回数据: '.$rawData;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('fail');
        }

        //保存通道订单号
        $channelOrderId = $data['payId'];
        $orderModel->where(['pay_orderid' => $orderId])->save(['channel_order_id' => $channelOrderId]);

        $payLog['outTradeId'] = $order['out_trade_id'];
        //验证通道的签名
        $sign = $data['signData'];
        unset($data['signData']);
        $signkey = $order['key'];
        $resp_sign = strtoupper($this->_createSign($data,$signkey));
        if($resp_sign != $sign){
            $payLog['msg'] = $this->TITLE.'-验签失败, 通道签名='.$sign.', 平台签名='.$resp_sign;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('fail');
        }

        if($data['orderStatus'] == 01){
            $this->EditMoney($orderId, $this->CODE, 0);
            //添加日记
            $payLog['msg'] = $this->TITLE.'-支付成功, 修改订单状态为: 成功,未返回';
            $this->payLogModel->add($payLog);
            exit('success');
        }else{
            //添加日记
            $payLog['msg'] = $this->TITLE.'-支付失败';
            $payLog['level'] = GPayLogModel::$LEVEL_WARNING;
            $this->payLogModel->add($payLog);
            exit('fail');
        }
    }

}