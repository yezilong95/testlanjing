<?php
namespace Pay\Controller;

use Common\Model\GPayLogModel;

/**
 * 云路-快捷
 * Class YunLuKJController
 * @package Pay\Controller
 * @author 尚军邦
 */
class YunLuKJController extends PayController
{
    private $CODE = 'YunLuKJ';
    private $TITLE = '云路支付-快捷';
    private $PRODUCT_CODE = '912'; //平台的支付类型


	public function Pay($array)
    {

        header("Content-Type:text/html;charset=UTF-8");
        $out_trade_id = I('request.pay_orderid'); //商户订单号
        $productCode = I('request.pay_bankcode'); //支付产品编号
        $body = trim(I('request.pay_productname')); //商品名称，要给默认名称，为空时上游签名失败
        if(empty($body)){
            $body = '会员充值';
        };


		$notifyurl = $this->_site . 'Pay_'.$this->CODE.'_notifyurl.html';
		$callbackurl = $this->_site . 'Pay_'.$this->CODE.'_callbackurl.html';

		$parameter = array(
			'code' => $this->CODE,
			'title' => $this->TITLE,
			'exchange' => 100, // 金额比例
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
        $return["notifyurl"] || $return["notifyurl"] = $notifyurl;
        
        //获取请求的url地址
        $url = $return["gateway"];

        $orgNo = $return["appid"];//机构号

        //获取toke值
	   $arraystr = array(
            'merchantCode'  => $return['mch_id'],//商户编号
            'orderNumber'  => $return['orderid'],#订单号
            'tranCode'  => '051',#调用接口类型
            'totalAmount'  => $pay_amount,#订单金额
            'payType'  => '5',#支付方式
            'callback'  => $notifyurl,#异步通知地址
            'finishUrl'  => $callbackurl#完成跳转地址
        );
        $json_data = json_encode($arraystr,JSON_UNESCAPED_UNICODE);

	   #AES加密
        $privateKey = mt_rand(10000000,99999999).mt_rand(10000000,99999999);
        $context = $this->aesencrypt($json_data,$privateKey);

        #rsa公钥加密 encrtpKey
        $fp1=fopen("./cert/JianQin/merchant_public_key.txt","r");
        $public_key=fread($fp1,8192);
        fclose($fp1);
        $pu_key=openssl_pkey_get_public($public_key );
        openssl_public_encrypt($privateKey,$encrypted,$pu_key);
        $encrtpKey  = base64_encode($encrypted);

        #rsa私钥加密 signData
        $fp2=fopen("./cert/JianQin/merchant_private_key.txt","r");
        $private_key=fread($fp2,8192);
        fclose($fp2);
        $pr_key=openssl_pkey_get_private($private_key);
        openssl_sign($json_data,$encrypted2,$pr_key,OPENSSL_ALGO_SHA1);
        $signData  = base64_encode($encrypted2);

        $postdata = http_build_query($arraystr);

        //添加日记
        $payLog = [
            'merchantId' => $return["memberid"],
            'productCode' => $productCode,
            'outTradeId' => $out_trade_id,
            'channelMerchantId' => $return['mch_id'],
            'orderId' => $return['orderid'],
            'type' => GPayLogModel::$TYPE_REQUEST_CHANNEL,
        ];
        $payLog['msg'] = $this->TITLE.'-提交的数据: '.$postdata;
        $this->payLogModel->add($payLog);

        $http_data = array(
            "context"=>$context,
            "encrtpKey"=>$encrtpKey,
            "signData"=>$signData,
            "orgNo"=>$orgNo,
        );
        $postData = http_build_query($http_data);
        $result = curlPost($url, $postData);
        $resultArr = json_decode($result,true);
        /**
         *使用RSA私钥解密encrtpKey会得到AES秘钥
         * 使用得到的AES秘钥解密context会得到json明文
         * 使用RSA公钥进行验证签名
         */
        openssl_private_decrypt(base64_decode($resultArr["encrtpKey"]),$decrypted,$pr_key);//私钥解密
        $returnCon =json_decode($this->aesdecrypt($resultArr["context"],$decrypted),true);

        //添加日记
        $payLog['msg'] = $this->TITLE.'-返回的数据: '.$result;
        $this->payLogModel->add($payLog);

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
        echo($returnCon['payParams']);

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
        /*resv=&bizType=000000&txnSubType=01&signature=kTsH61xI5HRcw9qgAW0vEg%3D%3D&succTime=&settleAmount=&settleCurrency=&txnType=01&settleDate=20180307&version=1.0.0&merResv1=&accessType=0&respMsg=5Lqk5piT5oiQ5Yqf&txnTime=20180307205537&merId=929010095023465&currency=CNY&respCode=1001&channelId=chinaGpay&txnAmt=0000000000000160&signMethod=MD5&merOrderId=1520427337144577649"*/
        //该通道没有同步返回参数


        die("支付成功！");
        $rawData = http_build_query($_REQUEST);
        $data = file_get_contents("php://input");
        $orderid = $_REQUEST["orderNumber"];

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
        parse_str($rawData,$arr);
        //私钥解密
        $fp2=fopen("./cert/JianQin/merchant_private_key.txt","r");
        $private_key=fread($fp2,8192);
        fclose($fp2);
        $pr_key=openssl_pkey_get_private($private_key);
        openssl_private_decrypt(base64_decode($arr["encrtpKey"]),$decrypted,$pr_key);
        $data =json_decode($this->aesdecrypt($arr["context"],$decrypted),true);

        $merchantId = null;
        $productCode = $this->PRODUCT_CODE;
        $outTradeId = null;
        $channelMerchantId = $data['merchantCode'];
        $orderId = $data["orderNumber"];

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

        if($data['oriRespCode'] == '000000'){
            $this->EditMoney($orderId, $this->CODE, 0);
            exit('000000');
        }else{
            //添加日记
            $payLog['msg'] = $this->TITLE.'-支付失败';
            $payLog['level'] = GPayLogModel::$LEVEL_WARNING;
            $this->payLogModel->add($payLog);
            exit('fail');
        }
    }

    public function aesencrypt($input, $key) {
        $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $input = $this->pkcs5_pad($input, $size);
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
        $iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $key, $iv);
        $data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $data = base64_encode($data);
        return $data;
    }

    private function pkcs5_pad ($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    public static function aesdecrypt($sStr, $sKey) {
        $decrypted= mcrypt_decrypt(
            MCRYPT_RIJNDAEL_128,
            $sKey,
            base64_decode($sStr),
            MCRYPT_MODE_ECB
        );

        $dec_s = strlen($decrypted);
        $padding = ord($decrypted[$dec_s-1]);
        $decrypted = substr($decrypted, 0, -$padding);
        return $decrypted;
    }

}