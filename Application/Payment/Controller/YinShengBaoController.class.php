<?php
namespace Payment\Controller;
use Common\Model\GDaiFuLogModel;

/**
 * 银生宝代付
 *  代付要查询，或者异步通知, 确定代付成功
 * Class XinJieController
 * @package Payment\Controller
 * @author 黄治华
 */
class YinShengBaoController extends PaymentController
{
    const TITLE = '银生宝代付';
    const CODE = 'YinShengBao';

    public function PaymentExec($wttlList, $pfaList)
    {
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
        $mch_id = $pfaList['mch_id']; //通道商户号
        $appid = $pfaList['appid']; //大商户号或者应用号

        //提交的参数
        $data = [
            'accountId'         => $mch_id,
            'name'          => $bankCardAccountName,
            'cardNo'      => $bankCardNumber,
            'orderId'            => $dfOrderid,
            'purpose' => '代付',
            'amount'    => $amount,
            //'responseUrl' => '' //暂时不用，只用查询确认支付
        ];

        //签名 accountId=112014&name=张成&cardNo=623625&orderId=20170&purpose=学费&amount=0.01&responseUrl=http://IP:PORT&key=123456
        $signSource = "accountId={$data['accountId']}&name={$data['name']}&cardNo={$data['cardNo']}"
            . "&orderId={$data['orderId']}&purpose={$data['purpose']}&amount={$data['amount']}";
        $sign = strtoupper(md5($signSource."&key=".$signKey));
        $data['sign'] = $sign;
        $postData = json_encode($data);

        //添加日记
        $logTitle = self::TITLE . '-提交代付-';
        $payLog = [
            'channelMerchantId' => $mch_id,
            'orderId' => $dfOrderid,
            'code' => self::CODE,
            'type' => GDaiFuLogModel::TYPE_SUBMIT,
            'level' => GDaiFuLogModel::LEVEL_INFO,
        ];
        $payLog['msg'] = $logTitle . '提交给通道的数据: ' . $postData . '，网关地址=' . $gateway;
        $this->logModel->add($payLog);

        list($returnCode, $returnData) = $this->http_post_data($gateway, $postData);

        $payLog['msg'] = $logTitle . '返回http状态: '.$returnCode.', 数据: '.$returnData;
        $this->logModel->add($payLog);

        $returnData = json_decode($returnData, true);
        if($returnData['result_code'] == '0000'){
            return ['status'=>1, 'daifu_time'=>time()]; //申请成功
        }else{
            return ['status'=>3, 'msg'=>$returnData['result_msg']];
        }
    }

    public function PaymentQuery($wttlList, $pfaList)
    {
        //查询代付订单时，务必以五分钟后的查询结果为准，如以五分钟内的查询结果为准进行重复出款或退款等操作所造成的损失，由商户自行承担。
        if($wttlList['daifu_time']+360 > time()){
            return ['status'=>1, 'msg'=>'提交代付与查询代付的时间间隔为6分钟'];
        }

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
            'accountId'         => $mch_id,
            'orderId'            => $dfOrderid,
        ];

        //签名 accountId=1120140210111812001&orderId=20150408162102&key=123456
        $signSource = "accountId={$data['accountId']}&orderId={$data['orderId']}";
        $sign = strtoupper(md5($signSource."&key=".$signKey));
        $data['sign'] = $sign;
        $postData = json_encode($data);

        //添加日记
        $logTitle = self::TITLE . '-代付查询-';
        $payLog = [
            'channelMerchantId' => $mch_id,
            'orderId' => $dfOrderid,
            'code' => self::CODE,
            'type' => GDaiFuLogModel::TYPE_QUERY,
            'level' => GDaiFuLogModel::LEVEL_INFO,
        ];
        $payLog['msg'] = $logTitle . '提交给通道的数据: ' . $postData . '，网关地址=' . $gateway;
        $this->logModel->add($payLog);

        list($returnCode, $returnData) = $this->http_post_data($gateway, $postData);

        $payLog['msg'] = $logTitle . '返回http状态: '.$returnCode.', 数据: '.$returnData;
        $this->logModel->add($payLog);

        $returnData = json_decode($returnData, true);
        if($returnData['result_code'] == '0000'){
            if ($returnData['status'] == '00') {
                return ['status' => 2, 'daifu_time' => time()]; //代付成功
            }elseif ($returnData['status'] == '10'){
                return ['status'=>1, 'msg'=>$returnData['desc']]; //处理中
            }else{
                return ['status'=>3, 'msg'=>$returnData['desc']]; //失败
            }
        }else{
            return ['status'=>3, 'msg'=>$returnData['result_msg']];
        }
    }

    private function http_post_data($url, $data_string) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Content-Type: application/json; charset=utf-8",
                "Content-Length: " . strlen($data_string))
        );
        ob_start();
        curl_exec($ch);
        $return_content = ob_get_contents();
        ob_end_clean();
        $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return array($return_code, $return_content);
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