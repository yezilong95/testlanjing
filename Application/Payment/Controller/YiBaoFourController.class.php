<?php
namespace Payment\Controller;

/**
 * 信捷代付
 *  代付要查询，没有异步通知, 异步通知只通知统一下单的那些交易
 * Class XinJieController
 * @package Payment\Controller
 */
class YiBaoFourController extends PaymentController
{
    private $TITLE = '易宝四方代付';

    public function PaymentExec($wttlList, $pfaList)
    {
        $logTitle = time() . '-' . $this->TITLE . '-代付-';

        $dfOrderid = $wttlList['orderid']; //传给代付系统的订单号
        $amount = $wttlList['money'];
        $bankName = $wttlList['bankname'];
        $bankCardNumber = $wttlList['banknumber'];//银行卡号/对公账
        $bankCardAccountName = $wttlList['bankfullname'];//账户名称
        $signKey = $pfaList['signkey'];
        $gateway = $pfaList['exec_gateway'];
        $mch_id = $pfaList['mch_id'];
        $batch_no = time().rand(1000,9999);

        $where['bank_name'] = array('like','%'.$bankName.'%');
        $bankSymbol = M('banksymbol')->where($where)->field("code")->find();
        $addbatchNo = M('Wttklist')->where(array("orderid"=>$dfOrderid))->setField('batch_no',$batch_no);
        //提交的参数
        $data = [
            'trxType'         => 'withdrawSignal',
            'r1_merchantNo'         => $mch_id,
            'r2_orderNum'         => $dfOrderid,
            'r3_batchNo'         => $batch_no,
            'r4_accountName'         => $bankCardAccountName,
            'r5_accountNo'         => $bankCardNumber,
            'r7_isT0'         => '1',
            'r8_amount'         => $amount,
            'r13_bankSymbol'         => $bankSymbol['code']
        ];

        $md5str= $this->SignParamsToString($data);
        $hmac = md5("#".$md5str."#".$signKey);
        $data["sign"]= $hmac;
        $dataStr = json_encode($data,JSON_UNESCAPED_UNICODE);
        addSyslog($logTitle.'提交字符串-'.$dataStr);
        $returnData = curlPost($gateway, http_build_query($data));
        addSyslog($logTitle.'返回字符串:'.$returnData);

        //@todo, 注意重新验签和验证订单号和订单金额

        $returnData = json_decode($returnData, true);

        if(!empty($returnData['r4_withdrawStatus'])){
            switch ($returnData['r4_withdrawStatus'])
            {
                case 'INIT':
                    $return = ['status'=>1, 'msg'=>'平台处理中'];
                    break;
                case 'DOING':
                    $return = ['status'=>1, 'msg'=>'银行处理中'];
                    break;
                case 'COMPLETE':
                    $return = ['status'=>2, 'msg'=>'已到账'];
                    break;
                case 'FAILED':
                    $return = ['status'=>3, 'msg'=>'银行打款失败'];
                    break;
                case 'RETURNBACK':
                    $return = ['status'=>3, 'msg'=>'退回账户'];
                    break;
                case 'REPEAL':
                    $return = ['status'=>3, 'msg'=>'冲正'];
                    break;
                default:
                    $return = ['status'=>3, 'msg'=>'未确定'];
            }
        }
        return $return;
    }


    public function PaymentQuery($wttlList, $pfaList)
    {
        $logTitle = time() . '-' . $this->TITLE . '-查询-';

        $dfOrderid = $wttlList['orderid']; //传给代付系统的订单号
        $amount = $wttlList['money'];
        $signKey = $pfaList['signkey'];
        $gateway = $pfaList['query_gateway'];
        $mch_id = $pfaList['mch_id'];
        $batchNo = M('Wttklist')->where(array("orderid"=>$dfOrderid))->getField('batch_no');
        //提交的参数
        $data = [
            'trxType'         => 'transferQuery',
            'r1_merchantNo'         => $mch_id,
            'r2_orderNum'         => $dfOrderid,
            'r3_batchNo'         => $batchNo
        ];

        //addSyslog($logTitle.'pfaList-'.json_encode($pfaList,JSON_UNESCAPED_UNICODE));
        //签名
        $md5str= $this->SignParamsToString($data);
        $hmac = md5("#".$md5str."#".$signKey);
        $data["sign"]= $hmac;
        $dataStr = json_encode($data,JSON_UNESCAPED_UNICODE);
        addSyslog($logTitle.'提交字符串-'.$dataStr."-地址：".$gateway);
        $returnData = curlPost($gateway, http_build_query($data));
        addSyslog($logTitle.'返回字符串:'.$returnData);
        //@todo, 注意重新验签和验证订单号和订单金额
        $returnData = json_decode($returnData, true);
        //状态0：未处理；1：处理中；2：已打款；3：已驳回
        if($returnData['retCode'] == '0000' && !empty($returnData['r5_amount'])){
            if($returnData['r5_amount'] == $amount){
                $return = ['status'=>2, 'msg'=>'代付成功！'];
            }else{
                $return = ['status' => 1, 'msg' =>'订单金额不符合'];
            }
        }else{
            $return = ['status'=>1, 'msg'=>$returnData['retMsg']];
        }
        return $return;
    }

    private function SignParamsToString($params,$key) {
        $sign_str = '';
        // 排序
        // ksort($params);

        $buff = "";
        foreach ($params as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $v . "#";
            }
        }

        $buff = trim($buff, "#");
        return $buff;
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