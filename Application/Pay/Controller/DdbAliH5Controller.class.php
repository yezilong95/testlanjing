<?php
namespace Pay\Controller;

class DdbAliH5Controller extends PayController
{
	private $merchant_code;
	
	private $merchant_private_key;
	
	private $dinpay_public_key;
	
	private $gateways;
	
    public function __construct()
    {
        parent::__construct();
        $this->merchant_private_key = file_get_contents('./cert/ddb/merchant_private_key.txt');
        $this->dinpay_public_key = file_get_contents('./cert/ddb/merchant_public_key.txt');
        
    }

    public function Pay($array)
    {

        $orderid = I('request.pay_orderid');
        $body = I('request.pay_productname');
        $notifyurl = $this->_site . 'Pay_DdbAliH5_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_DdbAliH5_callbackurl.html'; //跳转通知
        $parameter = array(
            'code' => 'DdbAliH5', // 通道名称
            'title' => '支付宝H5支付-多得宝付', //通道名称
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid'=>'',
            'out_trade_id' => $orderid, //外部订单号
            'channel'=>$array,
            'body'=>$body
        );
        $return = $this->orderadd($parameter);
        if ($return) {
        	if($return["amount"] > 5000){
        		exit('单笔支付限额5000元');
        	}

            $data['merchant_code'] = $return['mch_id'];
            $data['service_type'] = 'alipay_h5';
            $data['notify_url'] = $notifyurl;
            $data['interface_version'] = 'V3.3';
            $data['client_ip'] = get_client_ip();          
            $data['order_no'] = $return["orderid"];
            $data['order_time'] = date('Y-m-d H:i:s');
            $data['order_amount'] = $return["amount"];
            $data['product_name'] = '基础支付服务';
            $data['sign'] = $this->paySign($data);
            $data['sign_type'] = 'RSA-S';
            $merchant_private_key = openssl_get_privatekey($this->merchant_private_key);
            openssl_sign($data['sign'], $sign_info, $merchant_private_key, OPENSSL_ALGO_MD5);
            $data['sign'] = base64_encode($sign_info);   
            echo createForm($return['gateway'], $data);

        }
    }

    // 页面通知返回
    public function callbackurl()
    {
        $Order = M("Order");
        $pay_status = $Order->where("pay_orderid = '".$_REQUEST["orderid"]."'")->getField("pay_status");
        if($pay_status <> 0){
            $this->EditMoney($_REQUEST["orderid"], '', 1);
        }else{
            exit("error");
        }
    }

    // 服务器点对点返回
    public function notifyurl()
    {
    	if(empty($_POST)) exit;
    	$merchant_code	= $_POST["merchant_code"];    	
    	$notify_type = $_POST["notify_type"];	
    	$notify_id = $_POST["notify_id"];   	
    	$interface_version = $_POST["interface_version"];
    	$sign_type = $_POST["sign_type"];   	
    	$dinpaySign = base64_decode($_POST["sign"]);
    	$order_no = $_POST["order_no"];
    	$order_time = $_POST["order_time"];
    	$order_amount = $_POST["order_amount"];
    	$extra_return_param = $_POST["extra_return_param"];
    	$trade_no = $_POST["trade_no"];
    	$trade_time = $_POST["trade_time"];
    	$trade_status = $_POST["trade_status"];   	
    	$bank_seq_no = $_POST["bank_seq_no"];
    		
    	/////////////////////////////   参数组装  /////////////////////////////////
    	$signStr = "";   	
    	if($bank_seq_no != ""){
    		$signStr = $signStr."bank_seq_no=".$bank_seq_no."&";
    	}    	
    	if($extra_return_param != ""){
    		$signStr = $signStr."extra_return_param=".$extra_return_param."&";
    	}    	
    	$signStr = $signStr."interface_version=".$interface_version."&";   	
    	$signStr = $signStr."merchant_code=".$merchant_code."&";
    	$signStr = $signStr."notify_id=".$notify_id."&";  	
    	$signStr = $signStr."notify_type=".$notify_type."&";    	
    	$signStr = $signStr."order_amount=".$order_amount."&";   	
    	$signStr = $signStr."order_no=".$order_no."&";
    	$signStr = $signStr."order_time=".$order_time."&";
    	$signStr = $signStr."trade_no=".$trade_no."&";	
    	$signStr = $signStr."trade_status=".$trade_status."&";
    	$signStr = $signStr."trade_time=".$trade_time;  	
    	/////////////////////////////   RSA-S验签  /////////////////////////////////	
    	$dinpay_public_key = openssl_get_publickey($this->dinpay_public_key);    	
    	$flag = openssl_verify($signStr, $dinpaySign, $dinpay_public_key, OPENSSL_ALGO_MD5);
    	
    	//////////////////////   异步通知必须响应“SUCCESS” /////////////////////////   	
    	if($flag){
    		$this->EditMoney($order_no, '', 0);
    		exit("success");
    	}else{
    		exit("fail");
    	}
    }
    
    /**
     * 创建签名
     * @param $list
     * @param $key
     * @return string
     */
    protected function paySign($list)
    {
    	ksort($list);
    	$str= "";
    	foreach ($list as $key => $val) {
    		if(!empty($val)){
    			$str = $str. $key . "=" . $val . "&";
    		}
    	}
    	$str = trim($str, "&");    	
    	return $str;
    }
}
?>