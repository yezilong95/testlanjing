<?php
namespace Payment\Controller;

class MopaySHJQController extends PaymentController{
	
	public function __construct(){
		parent::__construct();
	}

    public function PaymentExec($wttlList, $pfaList){
       
        $transBody = [
            'orderId'   => $wttlList['orderid'],
            'transDate' => date('YmdHis', time()),
            'transAmount'   => $wttlList['money'],
            'accNo' => $wttlList['banknumber'],
            'accName'   => urlencode(iconv('UTF-8','GBK',$wttlList['bankfullname'])),
        ];
        $key = file_get_contents($pfaList['private_key']);
        openssl_pkcs12_read($key, $keyInfo, $pfaList['appid']);
        openssl_private_encrypt( json_encode($transBody),$transBody, $keyInfo['pkey']);
        $arraystr = [
            'versionId' => '001',
            'businessType'  => '470000',
            'merId' => $pfaList['mch_id'],
            'transBody' => base64_encode($transBody),
        ];
        $arraystr['signData'] = strtoupper( md5Sign($arraystr, $pfaList['signkey'], '&key=') );
        $arraystr['signType'] = 'MD5';
        $returnData = curlPost($pfaList['exec_gateway'], $this->arrayToJson($arraystr), ['Content-Type: application/json; charset=gbk',]);
        $returnData = json_decode($returnData, true); 
        $returnData['refMsg'] =  iconv('GBK', 'UTF-8',  urldecode($returnData['refMsg']));
		
        if($returnData['status'] == '00'){
            openssl_public_decrypt(base64_decode($returnData['resBody']), $info, $keyInfo['cert']);
            $info = json_decode($info, true);
            $info['refMsg'] =  iconv('GBK', 'UTF-8',  urldecode($info['refMsg']));
            if($info['refCode'] == '00'){
                $return = ['status'=>1, 'msg'=>'受理中！'];
            }else{
                $return = ['status' => 3, 'msg' =>$info['refMsg']];
            }        
        }else{
            $return = ['status'=>3, 'msg'=>$returnData['refMsg']];
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
                $key = "'".addslashes($key)."'";
                // Format the value:
                if( is_array( $value )){
                    $value = array_to_json( $value );
                } else if( !is_numeric( $value ) || is_string( $value ) ){
                    $value = "'".addslashes($value)."'";
                }
                // Add to staging array:
                $construct[] = "$key: $value";
            }
            // Then we collapse the staging array into the JSON form:
            $result = "{ " . implode( ", ", $construct ) . " }";
        } else { // If the array is a vector (not associative):
            $construct = array();
            foreach( $array as $value ){
                // Format the value:
                if( is_array( $value )){
                    $value = array_to_json( $value );
                } else if( !is_numeric( $value ) || is_string( $value ) ){
                    $value = "'".addslashes($value)."'";
                }
                // Add to staging array:
                $construct[] = $value;
            }
            // Then we collapse the staging array into the JSON form:
            $result = "[ " . implode( ", ", $construct ) . " ]";
        }

        return $result;
    }


}