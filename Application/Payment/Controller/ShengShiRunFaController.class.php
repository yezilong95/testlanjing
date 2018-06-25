<?php
namespace Payment\Controller;

/**
 * 盛世润发代付
 *  代付要查询，没有异步通知, 异步通知只通知统一下单的那些交易
 * Class XinFuBaoController
 * @package Payment\Controller
 * 尚军邦
 */
class ShengShiRunFaController extends PaymentController
{
    private $TITLE = '盛世润发代付';

    /**
     * 代付
     * @param $wttlList
     * @param $pfaList
     * @return array
     */
    public function PaymentExec($wttlList, $pfaList)
    {
        $logTitle = time() . '-' . $this->TITLE;

        $dfOrderid = $wttlList['orderid']; //传给代付系统的订单号
        $amount = ($wttlList['money']+2)*100; //在提现金额里减少手续费
        $bankName = $wttlList['bankname'];
        $bankCardNumber = $wttlList['banknumber'];//银行卡号/对公账
        $bankCardAccountName = $wttlList['bankfullname'];//账户名称
        $signKey = $pfaList['signkey'];
        $gateway = $pfaList['exec_gateway'];
        $mch_id = $pfaList['mch_id'];
        $batch_no = time().rand(1000,9999);
        $notifyurl = $this->_site . 'Payment_XinFuBao_notifyurl.html'; //异步通知

        $where['bank_name'] = array('like','%'.$bankName.'%');
        $bankSymbol = M('banksymbol')->where($where)->find();

        //是否支持提交的银行卡
        if (!$bankSymbol){
            $supports = M('banksymbol')->where('xfb_num > 0')->select();
            $strSupport = '';
            foreach ($supports as $support){
                $strSupport .= $support['bank_name'] . ', ';
            }
            $strSupport = rtrim($strSupport, ', ');
            return ['status'=>3, 'msg'=>'代付失败，只支持以下银行：'.$strSupport];
        }

        $addbatchNo = M('Wttklist')->where(array("orderid"=>$dfOrderid))->setField('batch_no',$batch_no);
        //提交的参数
        $data = [
            'versionId'         => '1.0',
            'orderAmount'         => $amount,//单位：分
            'orderDate'         => date('YmdHis'),
            'currency'         => 'RMB',
            'transType'         => '008',
            'asynNotifyUrl'         => $notifyurl,
            'signType'         => 'MD5',
            'merId'         => $mch_id,
            'prdOrdNo'         => $dfOrderid,
            'receivableType'         => 'D00',
            'isCompay'         => '0',
            'phoneNo'         => '15846147854',//手机号
            'customerName'         => $bankCardAccountName,//账户名
            'cerdId'         => '632126199603101653',
            'acctNo'         => $bankCardNumber,
            'accBankNo'         => '105',
        ];

        $md5str= $this->SignParamsToString($data);
        $hmac = strtoupper(md5($md5str."&key=".$signKey));
        $data["signData"]= $hmac;
        $dataStr = json_encode($data,JSON_UNESCAPED_UNICODE);

        addSyslog($logTitle.'提交字符串: '.$dataStr);
        //返回异常数据$returnData={"code":"1","retCode":"1518211","serviceName":"提现申请","retMsg":"ICBC联行行号不存在或者通道不支持","desc":""}
        $returnData = curlPost($gateway, http_build_query($data));
        addSyslog($logTitle.'返回字符串: '.$returnData);

        //@todo, 注意重新验签和验证订单号和订单金额

        $returnData = json_decode($returnData, true);

        if($returnData['retCode']=='1'){
            return ['status'=>1, 'daifu_time'=>time()]; //申请成功
        }else{
            return ['status'=>3, 'msg'=>$returnData['retMsg']];
        }
    }

//    public function notifyurl(){
//       $data = I("post.");
//        $logTitle = time() . '-' . $this->TITLE . '-代付回调-';
//        addSyslog($logTitle.'返回字符串:'.json_encode($data,JSON_UNESCAPED_UNICODE));
//    }

    public function PaymentQuery($wttlList, $pfaList)
    {
        //查询代付订单时，务必以五分钟后的查询结果为准，如以五分钟内的查询结果为准进行重复出款或退款等操作所造成的损失，由商户自行承担。
        if($wttlList['daifu_time']+600 > time()){
            return ['status'=>1, 'msg'=>'提交代付与查询代付的时间间隔为10分钟'];
        }

        $logTitle = time() . '-' . $this->TITLE . '-查询-';

        $dfOrderid = $wttlList['orderid']; //传给代付系统的订单号
        $amount = $wttlList['money']*100;
        $signKey = $pfaList['signkey'];
        $gateway = $pfaList['query_gateway'];
        $mch_id = $pfaList['mch_id'];

        //提交的参数
        $data = [
            'signType'         => 'MD5',
            'merId'         => $mch_id,
            'prdOrdNo'         => $dfOrderid
        ];

        //addSyslog($logTitle.'pfaList-'.json_encode($pfaList,JSON_UNESCAPED_UNICODE));
        //签名
        $md5str= $this->SignParamsToString($data);
        $hmac = strtoupper(md5($md5str."&key=".$signKey));
        $data["signData"]= $hmac;
        $dataStr = json_encode($data,JSON_UNESCAPED_UNICODE);

        //通道返回数据：{"prdordno":"H0225934379731898","signData":"6643D0EA6833A329F08FFB80FF5E81C8",
        //  "code":"1","orderstatus":"21","ordamt":"500","signType":"MD5","retCode":"1","serviceName":"订单状态查询",
        //  "prdordtype":"12","retMsg":"订单查询成功","desc":""}
        // orderstatus的状态
        // 交易订单: 00 未支付 01 支付成功 02银行处理中 14 冻结 19 待处理
        // 代付订单: 00未支付 01已完成 14冻结 (02,21)银行处理中 22 退还支付账户
        addSyslog($logTitle.'提交字符串-'.$dataStr."-地址：".$gateway);
        $returnData = curlPost($gateway, http_build_query($data));
        addSyslog($logTitle.'返回字符串:'.$returnData);

        //@todo, 注意重新验签和验证订单号和订单金额
        $returnData = json_decode($returnData, true);
        //状态0：未处理；1：处理中；2：已打款；3：已驳回
        if($returnData['retCode'] == '1'){
            if($returnData['ordamt'] == $amount && $returnData['orderStatus']=='01'){
                return ['status'=>2, 'msg'=>'代付成功'];
            }else{
                return ['status' => 1, 'msg' =>'订单金额不符合'];
            }
        }else{
            return ['status'=>1, 'msg'=>$returnData['retMsg']];
        }
    }

    private function SignParamsToString($params) {
        $sign_str = '';
        // 排序
        ksort($params);

        $buff = "";
        foreach ($params as $k => $v)
        {
            if($k != "signData" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
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