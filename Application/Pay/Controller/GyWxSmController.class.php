<?php
namespace Pay\Controller;

use Org\Util\HttpClient;
use Org\Util\Ysenc;

class GyWxSmController extends PayController{
	
	public function __construct(){
		parent::__construct();
	}

	
	public function Pay($array){


		$orderid = I("request.pay_orderid");
		
		$body = I('request.pay_productname');
		$notifyurl = $this->_site . 'Pay_GyWxSm_notifyurl.html';

		$callbackurl = $this->_site . 'Pay_GyWxSm_callbackurl.html';

		$parameter = array(
			'code' => 'GyWxSm',
			'title' => '国银微信扫码支付',
			'exchange' => 100, // 金额比例
            'gateway' => '',
            'orderid'=>'',
            'out_trade_id' => $orderid, //外部订单号
            'channel'=>$array,
            'body'=>$body
		);

		//支付金额
		$pay_amount = I("request.pay_amount", 0);
		

		 // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);

      
        //如果生成错误，自动跳转错误页面
        $return["status"] == "error" && $this->showmessage($return["errorcontent"]);
        
        //跳转页面，优先取数据库中的跳转页面
        $return["notifyurl"] || $return["notifyurl"] = $notifyurl;
        
        //获取请求的url地址
        $url=$return["gateway"];


	    $arraystr = array(

            'gymchtId' => $return['mch_id'],
            'tradeSn' => $return['orderid'],
            'orderAmount' => $return['amount'],
            'goodsName' => $body, 
            'notifyUrl' => $return['notifyurl'],

        );    

        $arraystr['sign'] = $this->_createSign($arraystr, $return['signkey']);
   
     
   
        list($return_code, $return_content) = $this->httpPostData($url, http_build_query($arraystr));
       

	    $respJson = json_decode($return_content,true);


        if($respJson['resultCode'] == '00000'){


                import("Vendor.phpqrcode.phpqrcode",'',".php");
                $url = $respJson['code_url'];
                $QR = "Uploads/codepay/". $return['orderid'] . ".png";//已经生成的原始二维码图
                \QRcode::png($url, $QR, "L", 20);
                $this->assign("imgurl", '/'.$QR);
                $this->assign('params',$return);
                $this->assign('orderid',$return['orderid']);
                $this->assign('money',sprintf('%.2f', $return['amount']/100));
                $this->display("WeiXin/weixin");
        
        }else{
            var_dump($respJson);
            $this->showmessage($respJson['message']);
        }
           
        return;
        
    }

   
    protected function _createSign($data, $key){
        $sign = '';
        ksort($data);
        foreach( $data as $k => $vo ){
            $sign .= $k . '=' . $vo . '&';
        }
       
        return  strtoupper( md5($sign  . 'key=' . $key) );
    }



    public function httpPostData($url, $jsonStr){

        $ch = curl_init();
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                // 'Content-Type: application/json;charset=utf-8',
                'Content-Type:application/x-www-form-urlencoded; charset=utf-8', 
                // 'Content-Length: ' . strlen($jsonStr)
            )
        );
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return array($httpCode, $response);
    }
	

	public function callbackurl(){
        $Order = M("Order");
        $pay_status = $Order->where("pay_orderid = '".$_REQUEST["orderid"]."'")->getField("pay_status");
        if($pay_status <> 0){
            $this->EditMoney($_REQUEST["orderid"], '', 1);
        }else{
            exit("error");
        }
	}

	 // 服务器点对点返回
    public function notifyurl(){

        $data = file_get_contents('php://input');
        
        $f = fopen('./api_data.txt', 'a+');
        fwrite($f,$data . "\r\n");
        fclose($f);

        $data = json_decode($data, true);
        $sign = $data['sign'];
        unset($data['sign']);

        $signkey = M('Order')->where(['pay_orderid'=>$data['tradeSn']])->getField('key');
  
        $respSign = $this->_createSign($data,$signkey);
        

        if($data['pay_result'] == 0 && $respSign == $sign){
            $this->EditMoney($data["tradeSn"], '', 0);
            exit('success');
        }
        
        exit('fail');
    }

}