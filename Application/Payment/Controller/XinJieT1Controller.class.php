<?php
namespace Payment\Controller;

/**
 * 信捷代付, T1出款
 *  代付要查询，没有异步通知, 异步通知只通知统一下单的那些交易
 * Class XinJieController
 * @package Payment\Controller
 */
class XinJieT1Controller extends PaymentController
{
    private $TITLE = '信捷代付T1';

    public function PaymentExec($wttlList, $pfaList)
    {
        $logTitle = time() . '-' . $this->TITLE . '-代付-';

        $dfOrderid = $wttlList['orderid']; //传给代付系统的订单号
        $amount = $wttlList['money'];
        $bankName = $wttlList['bankname'];
        $bankCardNumber = $wttlList['banknumber'];
        $bankCardAccountName = $wttlList['bankfullname'];
        $branchBankName = $wttlList['bankzhiname'];
        $province = $wttlList['sheng'];
        $city = $wttlList['shi'];
        $batch_no = $wttlList['batch_no']; //打款批次号, 易宝要求必须唯一 必须为 15 位的数字串
        $signKey = $pfaList['signkey'];
        $gateway = $pfaList['exec_gateway'];
        $mch_id = $pfaList['mch_id'];
        $appid = $pfaList['appid']; //大商户号或者应用号

        //提交的参数
        $data = [
            'version'         => 'V001',
            'agre_type'           => 'D',
            'pay_type'           => '035',
            'inst_no'           => $appid,
            'merch_id'         => $mch_id, // 商户订单号
            'is_compay'          => '0',
            'order_datetime'          => date('H-m-d H:m:s'),
            'amount'    => strval(100 * $amount),
            'merch_order_no'            => $dfOrderid,
            'customer_name'          => $bankCardAccountName,
            'customer_cert_type'            => '01', //入账户主证件类型: 01身份证类型
            //'customer_cert_id'         => '441882198504181258', //这个字段不送,送的话就要送对，不能随便填一个
            //'customer_phone'        => '15889639895',
            //'bank_no'           => '888888', //联行号
            'bank_short_name'   => 'ABC', // 支持银行的英文缩写, 农业银行缩写为ABC, 必送的
            'bank_name'         => $bankName,
            'bank_card_no'      => $bankCardNumber,
            'remark'        => 'remark',
            'isT1CZ'        => '1',
        ];

        //签名
        $data['sign'] = $this->_createSign($data, $signKey);

        $dataStr = $this->_arrayToJson($data);
        addSyslog($logTitle.'提交字符串-'.$dataStr);

        list($returnCode, $returnData) = $this->_httpPostData($gateway, $dataStr);
        addSyslog($logTitle.'返回状态:'.$returnCode.',返回字符串:'.$returnData);

        //@todo, 注意重新验签和验证订单号和订单金额

        $returnData = json_decode($returnData, true);

        if($returnData['retcode'] == '00'){
            // 保存交易平台(上游渠道)生成的订单号 platform_order_no
            M('wttklist')->where(['id'=>$wttlList['id']])->save(['platform_order_no'=>$returnData['platform_order_no']]);

            $return = ['status'=>1]; //申请成功
        }else{
            $return = ['status'=>3, 'msg'=>$returnData['retmsg']];
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
        $gateway = $pfaList['exec_gateway'];
        $mch_id = $pfaList['mch_id'];
        $appid = $pfaList['appid']; //大商户号或者应用号

        //提交的参数
        $data = [
            'version'         => 'V001',
            'agre_type'           => 'Q',
            'pay_type'           => '035',
            'inst_no'           => $appid,
            'merch_id'         => $mch_id, // 商户订单号
            'order_datetime'          => date('H-m-d H:m:s'),
            'merch_order_no'            => $dfOrderid,
            'platform_order_no'            => $platform_order_no,
            'query_id'        => '1',
        ];

        //签名
        $data['sign'] = $this->_createSign($data, $signKey);

        $dataStr = $this->_arrayToJson($data);
        addSyslog($logTitle.'提交字符串-'.$dataStr);

        list($returnCode, $returnData) = $this->_httpPostData($gateway, $dataStr);
        addSyslog($logTitle.'返回状态:'.$returnCode.',返回字符串:'.$returnData);

        //@todo, 注意重新验签和验证订单号和订单金额

        $returnData = json_decode($returnData, true);

        if($returnData['retcode'] == '00' && !empty($returnData['amount'])){
            if($returnData['amount'] == 100*$amount){
                $return = ['status'=>2, 'msg'=>'代付成功'];
            }else{
                $return = ['status' => 1, 'msg' =>'订单金额不符合'];
            }
        }else{
            $return = ['status'=>3, 'msg'=>$returnData['retmsg']];
        }
        return $return;
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
}