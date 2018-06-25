<?php
namespace Payment\Controller;
use Common\Model\GDaiFuLogModel;

/**
 * 稳付宝代付
 *  代付要查询，没有异步通知, 异步通知只通知统一下单的那些交易
 * Class WinFuBaoController
 * @package Payment\Controller
 */
class WinFuBaoController extends PaymentController
{
    private $TITLE = '稳付宝代付';

    public function PaymentExec($wttlList, $pfaList)
    {
        $logTitle = time() . '-' . $this->TITLE . '-代付-';

        $dfOrderid = $wttlList['orderid']; //传给代付系统的订单号
        $merchantId = $wttlList['userid']; //商户号
        $code = $wttlList['code']; //代付通道编码
        $amount = $wttlList['money'];
        $bankName = $wttlList['bankname'];
        $bankAccount = $wttlList['banknumber']; //开户账号
        $bankUsername = $wttlList['bankfullname']; //开户人姓名
        $branchBankName = $wttlList['bankzhiname'];
        $province = $wttlList['sheng'];
        $city = $wttlList['shi'];
        $batch_no = $wttlList['batch_no']; //打款批次号, 易宝要求必须唯一 必须为 15 位的数字串
        $signKey = $pfaList['signkey'];
        $gateway = $pfaList['exec_gateway'];
        $channelMerchantId = $pfaList['mch_id']; //通道的商户号
        $appid = $pfaList['appid']; //大商户号或者应用号

        //如果打款批次号为空则保存到数据库
        if(empty($batch_no)) {
            $batch_no = date('Ym') . substr(str_shuffle("1234567890"), 0, 9);
            M('wttklist')->where(['id'=>$wttlList['id']])->save(['batch_no'=>$batch_no]);
        }

        $bankSymbol = M('banksymbol')->where(['bank_name'=>['like','%'.$bankName.'%']])->field("code")->find();

        //提交的参数
        $data = [
            'trxType' => 'withdrawSignal',
            'r1_merchantNo' => $channelMerchantId,
            'r2_orderNum' => $dfOrderid,
            'r3_batchNo' => $batch_no,
            'r4_accountName' => $bankUsername,
            'r5_accountNo' => $bankAccount,
            'r8_amount' => $amount,
            'r9_feeType' => 'SOURCE', //手续费收取方式 默认为收取商户   取值:“SOURCE”  商户承担 “TARGET”用户承担
            'r11_bankName' => $bankName, //(对公、对私 除去:招商、深圳 发展、北京、平安、 工商、交通、 中国、建设、兴业、 农业、民生、 中信、华夏、 上海浦东发展、 广州、广发、 邮政储蓄,其他银 行支行必填)
            'r13_bankSymbol' => $bankSymbol['code'], //银行号码
        ];

        //签名
        $data['sign'] = $this->_createSign($data, $signKey);

        $dataStr = http_build_query($data);

        //添加日记
        $logTitle = $this->TITLE . '-代付提交-';
        $log = [
            'merchantId' => $merchantId,
            'code' => $code,
            'channelMerchantId' => $channelMerchantId,
            'orderId' => $dfOrderid,
            'type' => GDaiFuLogModel::TYPE_SUBMIT,
            'level' => GDaiFuLogModel::LEVEL_INFO,
            'msg' => '',
        ];
        $log['msg'] = $logTitle . '提交字符串: ' . urldecode($dataStr) . ', 提交地址: ' . $gateway;
        $this->logModel->add($log);

        list($returnCode, $returnData) = $this->_httpPostData($gateway, $dataStr);

        //添加日记
        $log['msg'] = $logTitle . '返回状态: ' . $returnCode . ', 返回字符串: ' . $returnData;
        $this->logModel->add($log);

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
        $merchantId = $wttlList['userid']; //商户号
        $code = $wttlList['code']; //代付通道编码
        $amount = $wttlList['money'];
        $bankName = $wttlList['bankname'];
        $bankAccount = $wttlList['banknumber']; //开户账号
        $bankUsername = $wttlList['bankfullname']; //开户人姓名
        $branchBankName = $wttlList['bankzhiname'];
        $province = $wttlList['sheng'];
        $city = $wttlList['shi'];
        $batch_no = $wttlList['batch_no']; //打款批次号, 易宝要求必须唯一 必须为 15 位的数字串
        $signKey = $pfaList['signkey'];
        $gateway = $pfaList['query_gateway'];
        $channelMerchantId = $pfaList['mch_id']; //通道的商户号
        $appid = $pfaList['appid']; //大商户号或者应用号

        //提交的参数
        $data = [
            'trxType'         => 'transferQuery',
            'r1_merchantNo'           => $channelMerchantId,
            'r2_orderNum'            => $dfOrderid,
            'r3_batchNo'        => $batch_no,
        ];

        //签名
        $data['sign'] = $this->_createSign($data, $signKey);
        $dataStr = http_build_query($data);

        //添加日记
        $logTitle = $this->TITLE . '-代付查询-';
        $log = [
            'merchantId' => $merchantId,
            'code' => $code,
            'channelMerchantId' => $channelMerchantId,
            'orderId' => $dfOrderid,
            'type' => GDaiFuLogModel::TYPE_QUERY,
            'level' => GDaiFuLogModel::LEVEL_INFO,
            'msg' => '',
        ];
        $log['msg'] = $logTitle . '提交字符串: ' . urldecode($dataStr) . ', 提交地址: ' . $gateway;
        $this->logModel->add($log);

        list($returnCode, $returnData) = $this->_httpPostData($gateway, $dataStr);

        //添加日记
        $log['msg'] = $logTitle . '返回状态: ' . $returnCode . ', 返回字符串: ' . $returnData;
        $this->logModel->add($log);

        //@todo, 注意重新验签和验证订单号和订单金额

        $returnData = json_decode($returnData, true);

        if($returnData['retCode'] == '0000' && !empty($returnData['r5_amount'])){
            if(format2Decimal($returnData['r5_amount']) != format2Decimal($amount)){
                $return = ['status' => 1, 'msg' =>'订单金额不符合'];
            }elseif($returnData['r2_requestId'] != $dfOrderid){
                $return = ['status' => 1, 'msg' =>'订单号不符合'];
            }else{
                $return = ['status'=>2, 'msg'=>'代付成功'];
            }
        }else{
            $return = ['status'=>3, 'msg'=>$returnData['retMsg']];
        }
        return $return;
    }

    protected function _createSign($data, $key){
        //#withdrawSignal#KY0000000316#H0424646899360683#201804843297016#黄治华#6214836557805468#7.00#SOURCE#招商银行#CMBCHINA#代付密钥
        //#withdrawSignal#KY0000000316#H0424646899360683#201804843297016#黄治华#6214836557805468#7.00#SOURCE#招商银行#CMBCHINA#3p8BwbH2fMA5y09p53cxvonuy9wtJDuE
        $buff = "#";
        foreach ($data as $k => $v)
        {
            $buff .= $v . '#';
        }

        $buff .= $key;

        //添加日记
//        $logTitle = $this->TITLE . '-签名-';
//        $log = [
//            'type' => GDaiFuLogModel::TYPE_QUERY,
//            'level' => GDaiFuLogModel::LEVEL_INFO,
//        ];
//        $log['msg'] = $logTitle . '签名原串: ' . $buff;
//        $this->logModel->add($log);

        $sign = md5($buff);

        //添加日记
//        $log['msg'] = $logTitle . 'md5签名后字符串: ' . $sign;
//        $this->logModel->add($log);

        return $sign;
    }

    protected function _httpPostData($url, $jsonStr){

        $ch = curl_init();
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                //'Content-Type: application/json;charset=utf-8',
                'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
                //'Content-Length: ' . strlen($jsonStr)
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