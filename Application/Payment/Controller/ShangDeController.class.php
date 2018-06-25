<?php
namespace Payment\Controller;

/**
 * 杉德代付
 * Class ShangDeController
 * @package Payment\Controller
 * @author 黄治华
 */
class ShangDeController extends PaymentController{
	
	public function __construct(){
		parent::__construct();
	}

    /**
     * 代付接口, 由Payment\Controller\IndexController::index()调用, 并返回结果给其更改订单状态
     * @param $wttlList
     * @param $pfaList
     * @return array
     */
    public function PaymentExec($wttlList, $pfaList){
        $logTitle = time() . '-' . '杉德代付-代付请求-';

        $dfrderid = $wttlList['orderid']; //传给代付系统的订单号
        $amount = $wttlList['money'];
        $bankName = $wttlList['bankname'];
        $bankCardNumber = $wttlList['banknumber'];
        $bankCardAccountName = $wttlList['bankfullname'];
        //$bankCardAccountName = urlencode(iconv('UTF-8','GBK',$bankCardAccountName));
        $notifyUrl = "http://";
        $signKey = $pfaList['signkey'];
        $gateway = $pfaList['exec_gateway'];
        $mch_id = $pfaList['mch_id'];

        //提交的参数
        $data = [
            'transcode'         => '024',
            'version'           => '0100',
            'ordersn'           => $dfrderid, //流水号
            'merchno'           => $mch_id,
            'dsorderid'         => $dfrderid, // 商户订单号
            'username'          => $bankCardAccountName,
            'bankcard'          => $bankCardNumber,
            'accountProperty'   => '00',
            'amount'            => $amount,
            'bankcode'          => '308584001547', //开户行联行号, @todo
            'bankname'          => $bankName,
            'mobile'            => '15889639895',
            'upChannel'         => 'daxtech',
            'settleType'        => '1',
            //'notifyUrl'         => $notifyUrl,
        ];

        //签名
        $data['sign'] = $this->_createSign($data, $signKey);

        $dataStr = $this->arrayToJson($data);
        addSyslog($logTitle.'提交字符串-'.$dataStr);

        list($returnCode, $returnData) = $this->_httpPostData($gateway, $dataStr);
        addSyslog($logTitle.'返回状态:'.$returnCode.',返回字符串:'.$returnData);

        //$returnData: {"returncode":"0097","errtext":"交易异常,请重试"}
        $returnData = json_decode($returnData, true);

		
        if($returnData['returncode'] == '00'){
            if($returnData['refCode'] == '00'){
                $return = ['status'=>1, 'msg'=>'受理中！'];
            }else{
                $msg = '返回状态:'.$returnData['returncode'].', 错误信息:'.$returnData['errtext'];
                $return = ['status' => 3, 'msg' =>$msg];
            }        
        }else{
            $msg = '返回状态:'.$returnData['returncode'].', 错误信息:'.$returnData['errtext'];
            $return = ['status'=>3, 'msg'=>$msg];
        }
        return $return;
    }


    public function PaymentQuery($wttlList, $pfaList){

        $transBody = [
            'orderId'   => $wttlList['orderid'],
            'transDate' => date('YmdHis', time()),
        ];
        $key = file_get_contents($pfaList['private_key']);
        openssl_pkcs12_read($key, $keyInfo, $pfaList['appid']);
        openssl_private_encrypt( json_encode($transBody),$transBody, $keyInfo['pkey']);

        $arraystr = [
            'versionId' => '001',
            'businessType'  => '460000',
            'merId' => $pfaList['mch_id'],
            'transBody' => base64_encode($transBody),
        ];

        $arraystr['signData'] = strtoupper( md5Sign($arraystr, $pfaList['signkey'], '&key=') );
        $arraystr['signType'] = 'MD5';

       
        $returnData = curlPost($pfaList['query_gateway'], $this->arrayToJson($arraystr), ['Content-Type: application/json; charset=gbk',]);
        $returnData = json_decode($returnData, true);

        $returnData['refMsg'] =  iconv('GBK', 'UTF-8',  urldecode($returnData['refMsg']));
        
        
        if($returnData['status'] == '00'){
			openssl_public_decrypt(base64_decode($returnData['resBody']), $info, $keyInfo['cert']);
            $info = json_decode($info, true);
            $info['refMsg'] =  iconv('GBK', 'UTF-8',  urldecode($info['refMsg']));
			
            if($info['refCode'] == '1'){
                $return = ['status'=>2, 'msg'=>'代付成功！'];
            }elseif($info['refCode'] == '2'){
                $return = ['status' => 3, 'msg' =>'代付失败'];
            }elseif($info['refCode'] == '3'){
                $return = ['status' => 1, 'msg' =>'受理中！'];
            }elseif($info['refCode'] == '4'){
                $return = ['status' => 3, 'msg' =>'交易不存在'];
            }
        }else{
            $return = ['status'=>3, 'msg'=>$returnData['refMsg']];
        }
        return $return;
    }

	function arrayToJson( $array ){
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
                    $value = $this->arrayToJson( $value );
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
                    $value = $this->arrayToJson( $value );
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

    /**
     * 生成签名
     * @param $data
     * @param $key
     * @return string
     */
    protected function _createSign($data, $key){
        $sign = '';
        ksort($data);
        foreach( $data as $k => $vo ){
            $sign .= $k . '=' . $vo;
        }

        return md5($sign . $key);
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
}