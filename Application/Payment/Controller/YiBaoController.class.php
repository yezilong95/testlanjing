<?php
namespace Payment\Controller;

class YiBaoController extends PaymentController{
	
	public function __construct(){
		parent::__construct();
	}

    public function PaymentExec($wttlList, $pfaList)
    {
        $logTitle = time() . '-' . '易宝代付-代付请求-';

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
        $merchantPrivateKey = $pfaList['signkey'];

        //如果打款批次号为空则保存到数据库
        if(empty($batch_no)) {
            $batch_no = date('Ym') . substr(str_shuffle("1234567890"), 0, 9);
            M('wttklist')->where(['id'=>$wttlList['id']])->save(['batch_no'=>$batch_no]);
        }

        $notifyUrl = "http://";
        $cmd = 'TransferSingle';//命令 固定值: TransferSingle
        $version = '1.1';//接口版本 固定值:1.1
        $group_Id = $appid;//总公司商户编号 总公司在易宝支付的客户编号
        $mer_Id = $mch_id;//实际发起付款的交易商户编号  发起付款的总(分)公司在易宝 支付的客户编号
        $product = '';//产品类型  为空走代付、代发出款 值为“RJT”走日结通出款
        $batch_No = $batch_no; //打款批次号,不区分产品,必须唯一 必须为 15 位的数字串
        $order_Id = $dfOrderid; //订单号
        $amount = $amount; //打款金额
        $account_Name = $bankCardAccountName; //账户名称
        $account_Number = $bankCardNumber; //账户号
        #cmd、mer_Id、batch_No、order_Id、amount 、account_Number
        $Harr = array(
            'cmd' => $cmd,
            'mer_Id' => $mer_Id,
            'batch_No' => $batch_No,
            'order_Id' => $order_Id,
            'amount' => $amount,
            'account_Number' => $account_Number
        );
        $hmac = $this->Hmac($Harr, $merchantPrivateKey); //签名信息
        $bank_Code = '308584001547'; //收款银行编号, @todo
        $bank_Name = $bankName; //收款银行 全称
        $branch_Bank_Name = $branchBankName;//非直联银行需添写支行信息
        $province = $province;
        $city = $city;
        $account_Type = 'pr';//对私
        //“SOURCE” 商户承担 “TARGET”用户承担
        $fee_Type = 'SOURCE'; //手续费收 取方式
        //只能填写 0 或者 1,最终是 否实时出款取决于商户是否 开通该银行的实时出款。
        $urgency = '1'; //加急
        $str = '<?xml version="1.0" encoding="GBK"?>
                    <data>
                        <cmd>%s</cmd>
                        <version>%s</version>
                        <group_Id>%s</group_Id>
                        <mer_Id>%s</mer_Id>
                        <batch_No>%s</batch_No>
                        <bank_Code>%s</bank_Code>
                        <order_Id>%s</order_Id>
                        <bank_Name>%s</bank_Name>
                        <branch_Bank_Name>%s</branch_Bank_Name>
                        <amount>%s</amount>
                        <account_Name>%s</account_Name>
                        <account_Number>%s</account_Number>
                        <account_Type>%s</account_Type>
                        <province>%s</province>
                        <city>%s</city>
                        <fee_Type>%s</fee_Type>
                        <urgency>%s</urgency>
                        <hmac>%s</hmac>
                    </data>';
        $resultStr = sprintf($str, $cmd, $version, $group_Id, $mer_Id, $batch_No, $bank_Code, $order_Id, $bank_Name, $branch_Bank_Name,$amount, $account_Name, $account_Number, $account_Type,$province,$city, $fee_Type, $urgency, $hmac);

        addSyslog($logTitle.'提交字符串-'.$resultStr);

        $resultStr = mb_convert_encoding($resultStr, 'gbk', 'utf-8');
        $url = 'https://cha.yeepay.com/app-merchant-proxy/groupTransferController.action';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_HTTPPROXYTUNNEL, false);
        if (!empty($resultStr)) {
            curl_setopt($curl, CURLOPT_POST, TRUE);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $resultStr);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $returnData = curl_exec($curl);
        $returnCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        addSyslog($logTitle.'返回状态:'.$returnCode.',返回字符串:'.$returnData);
        $returnData = json_decode(json_encode((array) simplexml_load_string($returnData)), true);
//        $returnData = mb_convert_encoding($returnData, 'utf-8', 'gbk');

        if($returnData['ret_Code'] == '1'){
            //if($returnData['r1_Code'] == '0025'){ //已接受
            $return = ['status'=>1, 'msg'=>'受理中！'];
        }else{
            $return = ['status'=>3, 'msg'=>$returnData['error_Msg']];
        }
        return $return;
    }


    public function PaymentQuery($wttlList, $pfaList)
    {
        $logTitle = time() . '-' . '易宝代付-查询请求-';

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
        $merchantPrivateKey = $pfaList['signkey'];

        $cmd = 'BatchDetailQuery';//命令 固定值: TransferSingle
        $version = '1.0';//接口版本 固定值:1.1
        $group_Id = $appid;//总公司商户编号 总公司在易宝支付的客户编号
        $mer_Id = $mch_id;//实际发起付款的交易商户编号  发起付款的总(分)公司在易宝 支付的客户编号
        $query_Mode = '1';
        $product='';
        $batch_No = $batch_no; //打款批次号
        $order_Id = $dfOrderid; //订单号
        $page_No = '1';
        #cmd、mer_Id、batch_No、order_Id 、page_No
        $Harr = array(
            'cmd' => $cmd,
            'mer_Id' => $mer_Id,
            'batch_No' => $batch_No,
            'order_Id' => $order_Id,
            'page_No' => $page_No
        );
        $hmac = $this->HmacForQuery($Harr, $merchantPrivateKey); //签名信息
        $str = '<?xml version="1.0" encoding="GBK"?>
                    <data>
                    <cmd>%s</cmd>
                    <version>%s</version>
                    <group_Id>%s</group_Id>
                    <mer_Id>%s</mer_Id>
                    <query_Mode>%s</query_Mode>
                    <product>%s</product>
                    <batch_No>%s</batch_No>
                    <order_Id>%s</order_Id>
                    <page_No>%s</page_No>
                    <hmac>%s</hmac>
                    </data>';
        $resultStr = sprintf($str, $cmd, $version, $group_Id, $mer_Id, $query_Mode,$product,$batch_No, $order_Id,$page_No, $hmac);
        $resultStr = mb_convert_encoding($resultStr, 'gbk', 'utf-8');
        $url = 'https://cha.yeepay.com/app-merchant-proxy/groupTransferController.action';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_HTTPPROXYTUNNEL, false);
        if (!empty($resultStr)) {
            curl_setopt($curl, CURLOPT_POST, TRUE);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $resultStr);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $returnData = curl_exec($curl);
        $returnCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);


        addSyslog($logTitle.'返回状态:'.$returnCode.',返回字符串:'.$returnData);
        $returnData = json_decode(json_encode((array) simplexml_load_string($returnData)), true);

        /**
         * 成功返回字符串:<?xml version="1.0" encoding="GBK"?>
        <data>
        <cmd>BatchDetailQuery</cmd><ret_Code>1</ret_Code>
        <batch_No>201801619407253</batch_No>
        <total_Num>1</total_Num><end_Flag>Y</end_Flag>
        <list>
          <items>
             <item>
               <order_Id>H0122204098449698</order_Id>
               <payee_Bank_Account>6214836557805468</payee_Bank_Account><remarksInfo></remarksInfo>
               <refund_Date></refund_Date><real_pay_amount>10.0</real_pay_amount><payee_BankName>
         */
        if($returnData['ret_Code'] == '1' && !empty($returnData['list']['items']['item'])){
            $item = $returnData['list']['items']['item'];
            if($item['order_Id'] == $order_Id  && $item['real_pay_amount'] == $amount){
                $return = ['status'=>2, 'msg'=>'代付成功！'];
            }else{
                $return = ['status' => 1, 'msg' =>'受理中！'];
            }
        }else{
            $return = ['status'=>3, 'msg'=>$returnData['error_Msg']];
        }
        return $return;
    }

    /**
     * 代付用: 按顺序将 cmd、mer_Id、 batch_No 、 order_Id 、 amount、account_Number
     * 参数值 +商户密钥组成字符串,并采用商户证书进行签名(签名方式参考样例代码)
     * @param type $arr
     * @param $merchantPrivateKey 商户私钥
     */
    #cmd、mer_Id、batch_No、order_Id、amount 、account_Number
    protected function Hmac($arr, $merchantPrivateKey) {
        $cmd = trim($arr['cmd']);
        $mer_Id = trim($arr['mer_Id']);
        $batch_No = trim($arr['batch_No']);
        $order_Id = trim($arr['order_Id']);
        $amount = trim($arr['amount']);
        $account_Number = trim($arr['account_Number']);
        //拼接加密字符串
        $str = $cmd . $mer_Id . $batch_No . $order_Id . $amount . $account_Number . $merchantPrivateKey;
        $data = $this->scurl($str);
        return $data;
    }

    /**
     * 查询用: 按顺序将 cmd、mer_Id、batch_No、order_Id 、page_No
     * 参数值 +商户密钥组成字符串,并采用商户证书进行签名(签名方式参考样例代码)
     */
    #cmd、mer_Id、batch_No、order_Id 、page_No
    protected function HmacForQuery($arr, $merchantPrivateKey) {
        $cmd = trim($arr['cmd']);
        $mer_Id = trim($arr['mer_Id']);
        $batch_No = trim($arr['batch_No']);
        $order_Id = trim($arr['order_Id']);
        $page_No= trim($arr['page_No']);
        //拼接加密字符串
        $str = $cmd . $mer_Id . $batch_No . $order_Id  . $page_No . $merchantPrivateKey;
        $data = $this->scurl($str);
        return $data;
    }

    #生成HMAC签名
    protected function scurl($str) {
        $url = "http://127.0.0.1:8088/sign?req=" . $str;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 0);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
        //echo $data;
    }
    protected function scurl1($str, $hmac) {
        $url = "http://127.0.0.1:8088/verify?req=" . $str . "&sign=" . $hmac;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 0);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
        //echo $data;
    }
    #验签
    public function Hmacsafe($str, $merchantPrivateKey) {

        $data = json_decode(json_encode((array) simplexml_load_string($str)), true);
        $cmd = $data['cmd'];
        $ret_Code = $data['ret_Code'];
        $mer_Id=$data['mer_Id'];
        $batch_No=$data['batch_No'];
        $total_Amt=$data['total_Amt'];
        $total_Num=$data['total_Num'];
        $r1_Code = $data['r1_Code'];
        $hmac = urlencode($data['hmac']);
        //拼接加密字符串
        $arr = $cmd . $ret_Code . $mer_Id.$batch_No.$total_Amt.$total_Num.$r1_Code .$merchantPrivateKey;
        $hmactrue = $this->scurl1($arr, $hmac);
        return $hmactrue;
    }


}