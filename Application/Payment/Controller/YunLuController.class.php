<?php
namespace Payment\Controller;

/**
 * 云路代付
 *  代付要查询，没有异步通知, 异步通知只通知统一下单的那些交易
 * Class YunLuController
 * @package Payment\Controller
 */
class YunLuController extends PaymentController
{
    private $TITLE = '云路代付';
    private $CODE = 'YunLu';

    public function PaymentExec($wttlList, $pfaList)
    {
        $notifyurl = $this->_site . 'Payment_'.$this->CODE.'_notifyurl.html'; //异步通知
        $logTitle = time() . '-' . $this->TITLE . '-代付-';

        $dfOrderid = $wttlList['orderid']; //传给代付系统的订单号
        $amount = $wttlList['money'];
        $bankName = $wttlList['bankname'];//银行名称
        $bankCardNumber = $wttlList['banknumber'];//银行卡号
        $bankCardAccountName = $wttlList['bankfullname'];//持卡人姓名
        $branchBankName = $wttlList['bankzhiname'];//支行名称
        $province = $wttlList['sheng'];
        $city = $wttlList['shi'];
        $batch_no = $wttlList['batch_no']; //打款批次号, 易宝要求必须唯一 必须为 15 位的数字串
        $idcardnum = $wttlList['idcardnum'];//身份证号
        $signKey = $pfaList['signkey'];
        $gateway = $pfaList['exec_gateway'];
        $mch_id = $pfaList['mch_id'];
        $appid = $pfaList['appid']; //大商户号或者应用号

        //提交的参数
        $data = [
            'merchantCode'         => $mch_id,#商户编号
            'orderNumber'         => $dfOrderid,#订单号
            'tranCode'         => "041",#调用接口类型：041
            'amount'         => $amount,#金额
            'cardNo'         => $bankCardNumber,#卡号
            'idCardNo'         => "632126199502101643",#身份证号
            'payerName'         => $bankCardAccountName,#入帐卡对应姓名
            'bankName'         => $bankName,#银行名称
            'branchName'         => $branchBankName,#支行名称
            'unionpayNo'         => "CDFG",#联行号
            'callback'         => $notifyurl,#异步通知地址
        ];

        $json_data = json_encode($data,JSON_UNESCAPED_UNICODE);

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

        $http_data = array(
            "context"=>$context,
            "encrtpKey"=>$encrtpKey,
            "signData"=>$signData,
            "orgNo"=>$appid,
        );
        $postData = http_build_query($http_data);
        $result = curlPost($gateway, $postData);
        $resultArr = json_decode($result,true);
        /**
         *使用RSA私钥解密encrtpKey会得到AES秘钥
         * 使用得到的AES秘钥解密context会得到json明文
         * 使用RSA公钥进行验证签名
         */
        openssl_private_decrypt(base64_decode($resultArr["encrtpKey"]),$decrypted,$pr_key);//私钥解密
        $returnCon =json_decode($this->aesdecrypt($resultArr["context"],$decrypted),true);

        addSyslog($logTitle.'返回状态:'.$returnCon["respCode"].',返回字符串:'.$this->aesdecrypt($resultArr["context"],$decrypted));

        //@todo, 注意重新验签和验证订单号和订单金额

        if($returnCon['respCode'] == '000000'){
            // 保存交易平台(上游渠道)生成的订单号 platform_order_no
            /**
            注：因改通道没有返回渠道订单号，故不做保存
             */
            /*M('wttklist')->where(['id'=>$wttlList['id']])->save(['platform_order_no'=>$returnCon['orderNumber']]);*/

            $return = ['status'=>1]; //申请成功
        }else{
            $return = ['status'=>3, 'msg'=>$returnCon['respMsg']];
        }
        return $return;
    }


    public function PaymentQuery($wttlList, $pfaList)
    {
        $logTitle = time() . '-' . $this->TITLE . '-查询-';

        $dfOrderid = $wttlList['orderid']; //传给代付系统的订单号
        $amount = $wttlList['money'];
        $bankName = $wttlList['bankname'];
        $bankCardNumber = $wttlList['banknumber'];
        $bankCardAccountName = $wttlList['bankfullname'];
        $branchBankName = $wttlList['bankzhiname'];
        $province = $wttlList['sheng'];
        $city = $wttlList['shi'];
        $platform_order_no = $wttlList['platform_order_no']; //交易平台(上游渠道)生成的订单号 platform_order_no
        $batch_no = $wttlList['batch_no']; //打款批次号, 易宝要求必须唯一 必须为 15 位的数字串
        $signKey = $pfaList['signkey'];
        $gateway = $pfaList['query_gateway'];
        $mch_id = $pfaList['mch_id'];
        $appid = $pfaList['appid']; //大商户号或者应用号

        //提交的参数
        $data = [
            'orderNumber'         => $dfOrderid,
            'merchantCode'         => $mch_id,
            'tranCode'         => '042'
        ];

        //签名
        $json_data = json_encode($data,JSON_UNESCAPED_UNICODE);

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

        $http_data = array(
            "context"=>$context,
            "encrtpKey"=>$encrtpKey,
            "signData"=>$signData,
            "orgNo"=>$appid,
        );
        $postData = http_build_query($http_data);
        $result = curlPost($gateway, $postData);
        $resultArr = json_decode($result,true);
        /**
         *使用RSA私钥解密encrtpKey会得到AES秘钥
         * 使用得到的AES秘钥解密context会得到json明文
         * 使用RSA公钥进行验证签名
         */
        openssl_private_decrypt(base64_decode($resultArr["encrtpKey"]),$decrypted,$pr_key);//私钥解密
        $returnCon =json_decode($this->aesdecrypt($resultArr["context"],$decrypted),true);

        addSyslog($logTitle.'返回状态:'.$returnCon["respCode"].',返回字符串:'.$this->aesdecrypt($resultArr["context"],$decrypted));
        //@todo, 注意重新验签和验证订单号和订单金额


        if($returnCon['oriRespCode'] == '000000' && !empty($returnCon['amount']) && $returnCon['respCode'] == '000000' ){
            if($returnCon['amount'] == $amount){
                $return = ['status'=>2, 'msg'=>'代付成功'];
            }else{
                $return = ['status' => 1, 'msg' =>'订单金额不符合'];
            }
        }else{
            $return = ['status'=>3, 'msg'=>$returnCon['respMsg']];
        }
        return $return;
    }

    public function notifyurl(){
        $logTitle = time() . '-' . $this->TITLE . '-代付-';
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
        addSyslog($logTitle.'notify字符串-'.$rawData);
        addSyslog($logTitle.'notify字符串-'.$this->aesdecrypt($arr["context"],$decrypted));


        //查找订单
        $orderModel = M("wttklist");
        $order = $orderModel->where(['orderid' => $orderId])->find();
        if(empty($order)){
            addSyslog($logTitle.'-平台订单不存在, 平台订单号='.$orderId.', 返回数据: '.$rawData);
            exit('fail');
        }


        if($data['oriRespCode'] == '000000'){
            //添加日记
            addSyslog($logTitle.'代付notify成功');
            exit('000000');
        }else{
            //添加日记
            addSyslog($logTitle.'代付notify失败');
            exit('fail');
        }
    }

    protected function _createSign($data, $key){
        $sign = '';
        ksort($data);
        foreach( $data as $k => $vo ){
            $sign .= $k . '=' . $vo . '&';
        }

        return  strtolower( md5($sign  . 'key=' . $key) );
    }

    protected function _httpPostData($url, $jsonStr){

        $ch = curl_init();
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json;charset=utf-8',
                //'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
                'Content-Length: ' . strlen($jsonStr)
            )
        );
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return array($httpCode, $response);
    }

    protected function _arrayToJson( $array ){
        global $result;
        if( !is_array( $array ) ){
            return false;
        }
        $associative = count( array_diff( array_keys($array), array_keys( array_keys( $array )) ));
        if( $associative ){
            $construct = array();
            foreach( $array as $key => $value ){
                // We first copy each key/value pair into a staging array,
                // formatting each key and value properly as we go.
                // Format the key:
                if( is_numeric($key) ){
                    $key = "key_$key";
                }
                $key = '"'.addslashes($key).'"';
                // Format the value:
                if( is_array( $value )){
                    $value = $this->_arrayToJson( $value );
                } else if( !is_numeric( $value ) || is_string( $value ) ){
                    $value = '"'.addslashes($value).'"';
                }
                // Add to staging array:
                $construct[] = "$key:$value";
            }
            // Then we collapse the staging array into the JSON form:
            $result = "{" . implode( ",", $construct ) . "}";
        } else { // If the array is a vector (not associative):
            $construct = array();
            foreach( $array as $value ){
                // Format the value:
                if( is_array( $value )){
                    $value = $this->_arrayToJson( $value );
                } else if( !is_numeric( $value ) || is_string( $value ) ){
                    $value = '"'.addslashes($value).'"';
                }
                // Add to staging array:
                $construct[] = $value;
            }
            // Then we collapse the staging array into the JSON form:
            $result = "[" . implode( ",", $construct ) . "]";
        }

        return $result;
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