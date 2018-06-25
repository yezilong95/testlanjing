<?php
namespace Payment\Controller;
use Common\Model\GDaiFuLogModel;

/**
 * 恒信智付代付
 *  代付要查询确认交易是否成功，没有异步通知
 * Class HengXinController
 * @package Payment\Controller
 * @author 黄治华
 */
class HengXinController extends PaymentController
{
    private $TITLE = '恒信智付';

    public function PaymentExec($wttlList, $pfaList)
    {
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

        //是否支持提交的银行卡
        $bankModel = DM('Banksymbol', 'Slave');
        $bank = $bankModel->where(['bank_name' => ['like', '%'.$bankName.'%']])->find();
        if (!$bank){
            //支持提交的银行卡列表
            $supports = $bankModel->where(['heng_xin_num' => ['gt', 0]])->select();
            $strSupport = '';
            foreach ($supports as $support){
                $strSupport .= $support['bank_name'] . ', ';
            }
            $strSupport = rtrim($strSupport, ', ');
            return ['status'=>3, 'msg'=>'代付失败-只支持以下银行：'.$strSupport];
        }

        //提交的参数
        $data = [
            'version'           => '3.0',
            'method'            => 'hxapp.online.pay',
            'partner'           => $channelMerchantId,
            'bankcode'          => $bank['heng_xin_num'],
            'banksubname'       => $bankName, //支行名称
            'paymoney'          => $amount,
            'batchnumber'       => $dfOrderid, //商户系统代付批次号，该批次号将作为恒信智付接口的返回数据。该值需唯一
            'bankprovince'      => $province,
            'bankcity'          => $city,
            'bankaccount'       => $bankAccount,
            'bankusername'      => $bankUsername,
            'bankaccounttype'   => 2,
            'subtime'           => date('YmdHis', time()),
            'txtattach'         => '备注',
        ];

        //签名
        $data['sign'] = $this->_createSign($data, $signKey);
        $dataKV = http_build_query($data);

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
        $log['msg'] = $logTitle . '提交字符串: ' . urldecode($dataKV);
        $this->logModel->add($log);

        /*
         * 成功返回格式示例：
         *   {"version":"3.0","status":"1","message":"提交成功","batchnumber":"H0306074074756416","paymoney":"1","sign":"57e2d6d199de8373793446f49969846a"}
         * 失败返回格式示例：
         *   {"version":"3.0","status":"0","message":"提交失败","batchnumber":"","paymoney":"","sign":""}
         */
        list($returnCode, $returnData) = $this->_httpPostData($gateway, $dataKV);

        //添加日记
        $log['msg'] = $logTitle . '返回状态: ' . $returnCode . ', 返回字符串: ' . $returnData;
        $this->logModel->add($log);

        $returnArr = json_decode($returnData, true);
        if($returnArr['status'] == '1'){
            //验证签名，通道说同步不用验签
//            if ($returnArr['sign'] != $data['sign']){
//                return ['status' => 3, 'msg' => '返回验证签名失败，通道签名='.$returnArr['sign'].', 平台签名='.$data['sign']];
//            }
            //比较订单号
            if ($returnArr['batchnumber'] != $data['batchnumber']){
                return ['status' => 3, 'msg' => '返回订单号不正确，通道订单号='.$returnArr['batchnumber'].', 平台订单号='.$data['batchnumber']];
            }
            //比较金额
            if (format2Decimal($returnArr['paymoney']) != format2Decimal($data['paymoney'])){
                return ['status' => 3, 'msg' => '返回金额不正确，通道金额='.$returnArr['paymoney'].', 平台金额='.$data['paymoney']];
            }

            return ['status' => 1]; //申请成功
        }else{
            return ['status' => 3, 'msg' => $returnArr['message']];
        }
    }

    public function PaymentQuery($wttlList, $pfaList)
    {
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

        //提交的参数
        $data = [
            'version'           => '3.0',
            'method'            => 'hxapp.online.payquery',
            'partner'           => $channelMerchantId,
            'batchnumber'       => $dfOrderid, //商户系统代付批次号，该批次号将作为恒信智付接口的返回数据。该值需唯一
        ];

        //签名
        $data['sign'] = $this->_createSignQuery($data, $signKey);
        $dataKV = http_build_query($data);

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
        $log['msg'] = $logTitle . '提交字符串: ' . urldecode($dataKV);
        $this->logModel->add($log);

        /*
         * 成功返回格式示例：
         *      {"version":"3.0","status":"1","message":"付款成功","batchnumber":"","paymoney":"100.00","sign":"680f58e3834fa0f6c6262628a760822c"}
         * 查询同步失败返回格式示例：
         *      {"version":"3.0","status":"0","message":"签名校验失败","batchnumber":"","paymoney":"","sign":""}
         * 签名源串及格式如下：
         *      version={0}&partner={1}&batchnumber={2}&paymoney={3}&status={4}&key={5}
         */
        list($returnCode, $returnData) = $this->_httpPostData($gateway, $dataKV);

        //添加日记
        $log['msg'] = $logTitle . '返回状态: ' . $returnCode . ', 返回字符串: ' . $returnData;
        $this->logModel->add($log);

        $returnArr = json_decode($returnData, true);
        if($returnArr['status'] == '1'){
            //验证签名，通道说同步不用验签
//            $mySignStr = "version={$data['version']}&partner={$data['partner']}&batchnumber={$data['batchnumber']}"
//                . "&paymoney={$amount}&status={$returnArr['status']}&key={$signKey}";
//            $mySign = md5($mySignStr);
//            if ($returnArr['sign'] != $mySign){
//                return ['status' => 3, 'msg' => '返回验证签名失败，通道签名='.$returnArr['sign'].', 平台签名='.$mySign];
//            }
            //比较订单号
            if ($returnArr['batchnumber'] != $data['batchnumber']){
                return ['status' => 3, 'msg' => '返回订单号不正确，通道订单号='.$returnArr['batchnumber'].', 平台订单号='.$data['batchnumber']];
            }
            //比较金额
            if (format2Decimal($returnArr['paymoney']) != format2Decimal($amount)){
                return ['status' => 3, 'msg' => '返回金额不正确，通道金额='.$returnArr['paymoney'].', 平台金额='.$amount];
            }
            return ['status'=>2, 'msg'=>'代付成功'];
        }else{
            return ['status'=>3, 'msg'=>$returnArr['message']];
        }
    }

    /**
     * 代付提交的签名
     * MD5 签名源串及格式如下：
     *      version={0}&method={1}&partner={2}&batchnumber={3}&bankcode={4}&bankaccount={5}&paymoney={6}&subtime={7}&key={8}
     * @param $data
     * @param $signKey
     * @return string
     */
    protected function _createSign($data, $signKey){
        $signStr = "version={$data['version']}&method={$data['method']}&partner={$data['partner']}"
            . "&batchnumber={$data['batchnumber']}&bankcode={$data['bankcode']}&bankaccount={$data['bankaccount']}"
            . "&paymoney={$data['paymoney']}&subtime={$data['subtime']}&key={$signKey}";

        return md5($signStr);
    }

    /**
     * 代付查询的签名
     * MD5 签名源串及格式如下：
     *      version={0}&method={1}&partner={2}&batchnumber={3}&key={4}
     * @param $data
     * @param $signKey
     * @return string
     */
    protected function _createSignQuery($data, $signKey){
        $signStr = "version={$data['version']}&method={$data['method']}&partner={$data['partner']}"
            . "&batchnumber={$data['batchnumber']}&key={$signKey}";

        return md5($signStr);
    }

    protected function _httpPostData($url, $dataKV){
        $ch = curl_init();
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataKV);
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