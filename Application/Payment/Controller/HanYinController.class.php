<?php
namespace Payment\Controller;

/**
 * 瀚银代付, T1出款
 *  代付要查询，没有异步通知, 异步通知只通知统一下单的那些交易
 * Class HanYinController
 * @package Payment\Controller
 */
class HanYinController extends PaymentController
{
    private $TITLE = '瀚银代付';

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
        $appsecret = $pfaList['appsecret'];

        $where['bank_name'] = array('like','%'.$bankName.'%');
        $bankSymbol = M('banksymbol')->where($where)->field("qifutong,qftlhh")->find();


        //提交的参数
        $data = [
            'insCode'         => $appid,
            'insMerchantCode'         => $appsecret,
            'hpMerCode'         => $mch_id,
            'orderNo'         => $dfOrderid,
            'orderDate'         => date("Ymd"),
            'orderTime'         => date("YmdHis"),
            'currencyCode'         => '156',
            'orderAmount'         => strval(($amount)*100),
            'orderType'         => 'D0',
            'accountType'         => '01',
            'accountName'         => $bankCardAccountName,
            'accountNumber'         => $bankCardNumber,
            'nonceStr'         => "LJDF".rand(100,999),
            'mainBankName'         => $bankName,
            'mainBankCode'         => $bankSymbol['qftlhh'],
            'openBranchBankName'         => $branchBankName,

        ];
        /*insCode|insMerchantCode|hpMerCode|orderNo|orderDate|orderTime|currencyCode|orderAmount|orderType|accountType|accountName|accountNumber|nonceStr|sign*/
        //签名
        $hmac = $data['insCode'].'|'.$data['insMerchantCode'].'|'.$data['hpMerCode'].'|'.$data['orderNo'].'|'.$data['orderDate'].'|'.$data['orderTime'].'|'.$data['currencyCode'].'|'.$data['orderAmount'].'|'.$data['orderType'].'|'.$data['accountType'].'|'.$data['accountName'].'|'.$data['accountNumber'].'|'.$data['nonceStr'].'|'.$signKey;
        $data['signature'] = md5($hmac);

        $dataStr = json_encode($data,JSON_UNESCAPED_UNICODE);
        addSyslog($logTitle.'提交字符串-'.$dataStr);

        $returnData = $this->file_get_contents_post($gateway, $data);
        addSyslog($logTitle.'返回字符串:'.$returnData);

        //@todo, 注意重新验签和验证订单号和订单金额

        $returnData = json_decode($returnData, true);
        //将返回的订单时间保存到数据库，在查询接口用
        $addorderTime= M('wttklist')->save(array("hanyin_order_date"=>$returnData['orderTime']));

        if($returnData['statusCode'] == 'Z5' && $returnData['transStatus'] == '02'){
            // 保存交易平台(上游渠道)生成的订单号
            M('wttklist')->where(['id'=>$wttlList['id']])->save(['platform_order_no'=>$returnData['transSeq']]);

            $return = ['status'=>1]; //申请成功
        }else{
            $return = ['status'=>3, 'msg'=>$returnData['statusMsg']];
        }
        return $return;
    }


    public function PaymentQuery($wttlList, $pfaList)
    {
        $logTitle = time() . '-' . $this->TITLE . '-查询-';

        $dfOrderid = $wttlList['orderid']; //传给代付系统的订单号
        $amount = ($wttlList['money'])*100;
        $bankName = $wttlList['bankname'];
        $bankCardNumber = $wttlList['banknumber'];
        $bankCardAccountName = $wttlList['bankfullname'];
        $branchBankName = $wttlList['bankzhiname'];
        $province = $wttlList['sheng'];
        $city = $wttlList['shi'];
        $platform_order_no = $wttlList['platform_order_no']; //交易平台(上游渠道)生成的订单号 platform_order_no
        $dfpaydata =$wttlList['hanyin_order_date'];
        $batch_no = $wttlList['batch_no']; //打款批次号, 易宝要求必须唯一 必须为 15 位的数字串
        $signKey = $pfaList['signkey'];
        $gateway = $pfaList['query_gateway'];
        $mch_id = $pfaList['mch_id'];
        $appid = $pfaList['appid']; //大商户号或者应用号
        $appsecret = $pfaList['appsecret'];

        //提交的参数
        $data = [
            'insCode'         => $appid,
            'insMerchantCode'         => $appsecret,
            'hpMerCode'         => $mch_id,
            'orderNo'         => $dfOrderid,
            'transDate'         => $dfpaydata,
            'productType'         => '100002',
            'transSeq'         => $platform_order_no,
            'paymentType'         => '2007',
            'nonceStr'         => 'DFQ'.rand(100,999),
        ];

        //签名
        //insCode|insMerchantCode|hpMerCode|orderNo|transDate|transSeq|productType|paymentType|nonceStr|signKey
        $hmac = $data['insCode'].'|'.$data['insMerchantCode'].'|'.$data['hpMerCode'].'|'.$data['orderNo'].'|'.$data['transDate'].'|'.$data['transSeq'].'|'.$data['productType'].'|'.$data['paymentType'].'|'.$data['nonceStr'].'|'.$signKey;
        $data['signature'] = md5($hmac);

        $dataStr = $this->_arrayToJson($data);
        addSyslog($logTitle.'提交字符串-'.$dataStr);

        /*list($returnCode, $returnData) = $this->_httpPostData($gateway, $dataStr);
        addSyslog($logTitle.'返回状态:'.$returnCode.',返回字符串:'.$returnData);*/
        //$returnData = curlPost($gateway, http_build_query($data));
        $returnData = $this->file_get_contents_post($gateway, $data);
        addSyslog($logTitle.'返回字符串:'.$returnData);

        //@todo, 注意重新验签和验证订单号和订单金额

        $returnData = json_decode($returnData, true);

        $returnamount =strval($returnData['transAmount']);

        if($returnData['statusCode']=='00'&& $returnData['transStatus']=='00'&& !empty($returnamount)){
            if($returnamount == strval($amount)){
                $return = ['status'=>2, 'msg'=>'代付成功'];
            }else{
                $return = ['status' => 1, 'msg' =>'订单金额不符合'];
                addSyslog($logTitle.'订单金额不符合:'.$returnamount.'--'.$amount);
            }
        }else{
            $return = ['status'=>3, 'msg'=>$returnData['data']['defrayMessage']];
        }
        return $return;
    }

    public function yuequery(){

        $url='https://gateway.handpay.cn/hpayTransGatewayWeb/trans/df/queryAccount.htm';
        $key = 'EAA96E423B326141F0AFE42E5E8B88E8';
        $data = [
            'insCode'         => '80000303',
            'insMerchantCode'         => '887581298600767',
            'hpMerCode'         => 'HBMSDTDIRWK4P@20180508095606',
            'accountType'         => 'T1',
            'nonceStr'         => "LJDF".rand(100,999),
        ];
        //insCode|insMerchantCode|hpMerCode |accountType|nonceStr|signKey

        $hmac = $data['insCode'].'|'.$data['insMerchantCode'].'|'.$data['hpMerCode'].'|'.$data['accountType'].'|'.$data['nonceStr'].'|'.$key;
        $data['signature'] = md5($hmac);
        $returnData = $this->file_get_contents_post('https://gateway.handpay.cn/hpayTransGatewayWeb/trans/query.htm', $data);
        dump($returnData);
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

        $postdata = http_build_query($post_data);

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

    public function file_get_contents_post($url, $post){
        $options = array(
            'http'=> array(
                'method'=>'POST',
                'header' => "Content-type: application/x-www-form-urlencoded ",
                'content'=> http_build_query($post),
            ),
        );
        $result = file_get_contents($url,false, stream_context_create($options));
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