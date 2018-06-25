<?php
namespace Payment\Controller;

/**
 * 杉德代付
 * Class ShanDeT1Controller
 * @package Payment\Controller
 * @author 尚军邦
 */
class ShanDeT0Controller extends PaymentController{
	
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
        $logTitle = time() . '-' . '杉德T1代付-代付请求-';

        $dfrderid = $wttlList['orderid']; //传给代付系统的订单号
        $amount = $wttlList['money'];
        $bankName = $wttlList['bankname'];
        $bankCardNumber = $wttlList['banknumber'];
        $bankCardAccountName = $wttlList['bankfullname'];
        $bankzhiname = $wttlList['bankzhiname'];
        $idcardnum = $wttlList['idcardnum'];
        //$bankCardAccountName = urlencode(iconv('UTF-8','GBK',$bankCardAccountName));
        $notifyUrl = "http://";
        $signKey = $pfaList['signkey'];
        $gateway = $pfaList['exec_gateway'];
        $mch_id = $pfaList['mch_id'];

        $where['bank_name'] = array('like','%'.$bankName.'%');
        $bankSymbol = M('banksymbol')->where($where)->field("code")->find();
        //提交的参数
        $data = [
            'transcode'         => '024',
            'version'           => '0100',
            //'idcard'           => $idcardnum,
            'idcard'           => '440527193607280057',
            'ordersn'           => time().rand(1000,9999), //流水号
            'merchno'           => $mch_id,
            'dsorderid'         => $dfrderid, // 商户订单号
            'username'          => $bankCardAccountName,
            'bankcard'          => $bankCardNumber,
            'accountProperty'   => '00',
            'amount'            => $amount,
            'bankcode'          => '308584001547', //开户行联行号, @todo
            'bankname'          => $bankName,
            'bankid'          => '308584001547',
            'bankcodename'          => $bankzhiname,
            'mobile'            => '15889639895',
            'upChannel'         => 'sandpay',
            'settleType'        => '1',
            'bankabbr'        => $bankSymbol['code'],
            'bankprov'        => '110000',
            'bankcity'        => '110101',
            'remark'        => '耀益T1'//t1的就传耀益T1,T0的就传耀益T0

            //'notifyUrl'         => $notifyUrl,
        ];

        //签名
        $data['sign'] =md5($this->_createSign($data, $signKey));

        $dataStr = json_encode($data,JSON_UNESCAPED_UNICODE);
        addSyslog($logTitle.'提交字符串-'.$dataStr);

        list($returnCode, $returnData) = $this->_httpPostData($gateway, $dataStr);
        addSyslog($logTitle.'返回状态:'.$returnCode.',返回字符串:'.$returnData);

        //$returnData: {"returncode":"0097","errtext":"交易异常,请重试"}
        $returnData = json_decode($returnData, true);

		
        if($returnData['returncode'] == '0003' || $returnData['returncode'] =='0000'){
            $return = ['status'=>1];
        }else{
            $msg = '返回状态:'.$returnData['returncode'].', 错误信息:'.$returnData['errtext'];
            $return = ['status'=>3, 'msg'=>$msg];
        }
        return $return;
    }


    public function PaymentQuery($wttlList, $pfaList){
        /*1520948139-杉德代付-查询-返回状态:{"transcode":"902","merchno":"21110001122","dsorderid":"H0313366641184814","sign":"ecd8c89403a90199a5f94a43a300f8f2","ordersn":"15209481407539","returncode":"0000","errtext":"订单号不存在","status":"99"}*/

        $logTitle = time() . '-' . '杉德T1代付-查询-';
        $key = file_get_contents($pfaList['private_key']);
        /*openssl_pkcs12_read($key, $keyInfo, $pfaList['appid']);
        openssl_private_encrypt( json_encode($transBody),$transBody, $keyInfo['pkey']);*/
        $amount = $wttlList['money'];
        $arraystr = [
            'transcode' => '902',
            'version' => '0100',
            'ordersn' => time().rand(1000,9999),
            'merchno' => $pfaList['mch_id'],
            'dsorderid' => $wttlList['orderid'],
            'transtype' => '24'
        ];
        $md5string = $this->_createSign($arraystr, $pfaList['signkey']);
        $arraystr['sign'] =md5($md5string);
        addSyslog($logTitle.'提交数据:'.$this->arrayToJson($arraystr));
        $returnData = curlPost($pfaList['query_gateway'], $this->arrayToJson($arraystr), ['Content-Type: application/json; charset=gbk',]);
        $returnDataarr = json_decode($returnData, true);
        addSyslog($logTitle.'返回状态:'.$returnData);

        //验签
        $md5string = $this->_createSign($returnDataarr, $pfaList['signkey']);
        $md5sign = md5($md5string);
        if($md5sign !=$returnDataarr['sign'] ){
            addSyslog($logTitle.'返回状态:'."验签失败");
            $return = ['status' => 1, 'msg' =>'验签失败'];
        }else{
            if($returnDataarr['status'] == '00' && !empty($returnDataarr['amount']) ){
                if($returnDataarr['amount'] == $amount){
                    $return = ['status'=>2, 'msg'=>'代付成功'];
                }else{
                    $return = ['status' => 1, 'msg' =>'订单金额不符合'];
                }
            }else if($returnDataarr['status'] == '02'){
                $return = ['status'=>1, 'msg'=>'代付失败'];
            }else if($returnDataarr['status'] == '04'){
                $return = ['status'=>1, 'msg'=>'订单关闭'];
            }else if($returnDataarr['status'] == '01'){
                $return = ['status'=>1, 'msg'=>'处理中,半个小时后再次发起对账'];
            }else{
                $return = ['status'=>1, 'msg'=>'代付失败'];
            }
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
            if($k != "sign" && $vo != "" && !is_array($vo)){
                $sign .= $k . '=' . $vo;
            }

        }
        return $sign . $key;

        //return md5($sign . $key);
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