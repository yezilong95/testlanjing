<?php
namespace Payment\Controller;

/**
 * 启付通代付, T1出款
 *  代付要查询，没有异步通知, 异步通知只通知统一下单的那些交易
 * Class XinJieController
 * @package Payment\Controller
 */
class QiFuTongController extends PaymentController
{
    private $TITLE = '启付通代付';

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

        $where['bank_name'] = array('like','%'.$bankName.'%');
        $bankSymbol = M('banksymbol')->where($where)->field("qifutong,qftlhh")->find();


        //提交的参数
        $data = [
            'merchantNo'         => $mch_id,
            'appNo'         => $appid,
            'orderNo'         => $dfOrderid,
            'cashAmount'         => strval(($amount+2)*100),
            'receiveName'         => $bankCardAccountName,//收款人姓名
            'province'         => $province,//所在省份
            'city'         => $city,//所在市
            'bankCode'         => $bankSymbol['qifutong'],//银行编号
            'bankLinked'         => $bankSymbol['qftlhh'],//银行编号
            'bankBranch'         => $branchBankName,//开户支行名称
            'cardNo'         => $bankCardNumber,//卡号
            'accountType'         => '01',//对公对私
            'timestamp'         => date('YmdHiZ'),
        ];

        //签名
        $signstr = $this->_createSign($data);
        $data['sign'] = md5($signstr.$signKey);

        $dataStr = json_encode($data,JSON_UNESCAPED_UNICODE);
        addSyslog($logTitle.'提交字符串-'.$dataStr);

        $returnData = $this->send_post($gateway, $data);
        addSyslog($logTitle.'返回字符串:'.$returnData);

        //@todo, 注意重新验签和验证订单号和订单金额

        $returnData = json_decode($returnData, true);

        if($returnData['code'] == '000000'){
            // 保存交易平台(上游渠道)生成的订单号
            M('wttklist')->where(['id'=>$wttlList['id']])->save(['platform_order_no'=>$returnData['serialNo']]);

            $return = ['status'=>1]; //申请成功
        }else{
            $return = ['status'=>3, 'msg'=>$returnData['errMsg']];
        }
        return $return;
    }


    public function PaymentQuery($wttlList, $pfaList)
    {
        $logTitle = time() . '-' . $this->TITLE . '-查询-';

        $dfOrderid = $wttlList['orderid']; //传给代付系统的订单号
        $amount = ($wttlList['money']+2)*100;
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
            'merchantNo'         => $mch_id,
            'appNo'         => $appid,
            'orderNo'         => $dfOrderid,
            'timestamp'         => date('YmdHiZ')
        ];

        //签名
        //签名
        $signstr = $this->_createSign($data);
        $data['sign'] = md5($signstr.$signKey);

        $dataStr = $this->_arrayToJson($data);
        addSyslog($logTitle.'提交字符串-'.$dataStr);

        /*list($returnCode, $returnData) = $this->_httpPostData($gateway, $dataStr);
        addSyslog($logTitle.'返回状态:'.$returnCode.',返回字符串:'.$returnData);*/

        $returnData = $this->send_post($gateway, $data);
        addSyslog($logTitle.'返回字符串:'.$returnData);

        //@todo, 注意重新验签和验证订单号和订单金额

        $returnData = json_decode($returnData, true);

        $returnamount =strval($returnData['data']['amount']);

        if($returnData['code']=='000000' && $returnData['data']['defrayStatus'] == '1' && !empty($returnamount)){
            if($returnamount == strval($amount)){
                $return = ['status'=>2, 'msg'=>'代付成功'];
            }else{
                $return = ['status' => 1, 'msg' =>'订单金额不符合'];
            }
        }else{
            $return = ['status'=>3, 'msg'=>$returnData['data']['defrayMessage']];
        }
        return $return;
    }

    protected function _createSign($params){
        $sign_str = '';
        // 排序
        ksort($params);

        $buff = "";
        foreach ($params as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }

    public function send_post($url, $post_data) {

        $postdata = json_encode($post_data);

        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded\r\n',
                'content' => $postdata,
                'timeout' => 60 * 60 // 超时时间（单位:s）
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        return $result;
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