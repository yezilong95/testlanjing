<?php
namespace Pay\Controller;

use Org\Util\HttpClient;
use Org\Util\Ysenc;

class GopayBankMobileController extends PayController{
	
	public function __construct(){

		parent::__construct();
        // $this->ysenc=new Ysenc();
	}


	public function Pay($array){


		$orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');
        $notifyurl = $this->_site ."Pay_GopayBank_notifyurl.html"; //异步通知
        $callbackurl = $this->_site . 'Pay_GopayBank_callbackurl.html'; //跳转通知
		

		$parameter = array(
			'code' => 'GopayBankMobile',
			'title' => '国付宝银行手机支付',
			'exchange' => 1, // 金额比例
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
        $get_time_url = 'https://gateway.gopay.com.cn/time.do';
        $gopayServerTime = $this->http_get_data($get_time_url);
       
       
		$arraystr = array(
            "version" => "2.2",
            'charset' => '2',
            'language' => '1',
			"tranCode" => "8888",
            'signType' => 1,
            'merchantID' => $return['mch_id'],  
            'merOrderNum' => $return['orderid'],
            'tranAmt' => sprintf('%.2f', $return['amount']),
            'feeAmt' => '0.00',
            'currencyType' => '156',
            'frontMerUrl' => '',
            'backgroundMerUrl' => $return['notifyurl'],
            'tranDateTime' => date('YmdHis',time()),
            'virCardNoIn' => $return['appid'],
            'tranIP' => '127.0.0.1',
            'buyerName' => 'MWEB',
            'bankCode' => I('post.bankCode', ''),
            'userType' => 1,
		);
        
        $sign_array = array(
            'version' => '2.2',
            'tranCode' => $arraystr['tranCode'],
            'merchantID' => $return['mch_id'],
            'merOrderNum' => $return['orderid'],
            'tranAmt' => sprintf('%.2f', $return['amount']),
            'feeAmt' => $arraystr['feeAmt'],
            'tranDateTime' => $arraystr['tranDateTime'],
            'frontMerUrl' => '',
            'backgroundMerUrl' => $arraystr['backgroundMerUrl'],
            'orderId' => '',
            'gopayOutOrderId' => '',
            'tranIP' => $arraystr['tranIP'],
            'respCode' => '',
            'gopayServerTime' => '',
            'VerficationCode' => $return['signkey'],
        );
      

        $sign_str = '';
        foreach($sign_array as $k => $vo){
            $sign_str .= $k . '=[' . $vo . ']';
        }

        $arraystr['signValue'] = md5($sign_str);

       
        $request['data'] = base64_encode(json_encode($arraystr));
        $request['url'] = $return["gateway"];
        $request['sign'] = md5($return['orderid'] . 'lgbyageek'); 
     
        echo $this->_createForm($url,$arraystr);


	}

    protected function _createForm($url, $data){
        $str = '<!doctype html>
                <html>
                    <head>
                        <meta charset="utf8">
                        <title>正在跳转付款页</title>
                    </head>
                    <body onLoad="document.pay.submit()">
                    <form method="post" action="' . $url . '" name="pay">';

                        foreach($data as $k => $vo){
                            $str .= '<input type="hidden" name="' . $k . '" value="' . $vo . '">';
                        }

                    $str .= '</form>
                    <body>
                </html>';
        return $str;
    }

	public function http_post_data($url, $data_string){

		$cacert = '';	//CA根证书  (目前暂不提供)
        $CA = false ; 	//HTTPS时是否进行严格认证
        $TIMEOUT = 30;	//超时时间(秒)
        $SSL = substr($url, 0, 8) == "https://" ? true : false;
        
        $ch = curl_init ();
        if ($SSL && $CA) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); 	// 	只信任CA颁布的证书
            curl_setopt($ch, CURLOPT_CAINFO, $cacert); 			// 	CA根证书（用来验证的网站证书是否是CA颁布）
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); 		//	检查证书中是否设置域名，并且是否与提供的主机名匹配
        } else if ($SSL && !$CA) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 	// 	信任任何证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1); 		// 	检查证书中是否设置域名
        }



        curl_setopt ( $ch, CURLOPT_TIMEOUT, $TIMEOUT);
        curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, $TIMEOUT-2);
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data_string );
        curl_setopt ( $ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded;')  );

        ob_start();
        curl_exec($ch);
        $return_content = ob_get_contents();
        ob_end_clean();

        $return_code = curl_getinfo ( $ch, CURLINFO_HTTP_CODE );
       
        curl_close($ch);
        return array (
            $return_code,
            $return_content
        );

	}

	public function http_get_data($url){

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);   //  信任任何证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);        //  检查证书中是否设置域名
        curl_setopt ( $ch, CURLOPT_TIMEOUT, $TIMEOUT);
        curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, $TIMEOUT-2);
        curl_setopt( $ch, CURLOPT_HEADER, 0);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
        $return_content = curl_exec( $ch );
        curl_close($ch);
        return $return_content;
    }


	public function callbackurl(){

	}

	 // 服务器点对点返回
    public function notifyurl(){
     
        $data = I('post.');
        
		$Order = M("Order");
		$pkey = $Order->where("pay_orderid = '".$data['merOrderNum']."'")->getField("key");

        $sign_array = array(
            'version' => '2.2',
            'tranCode' => $data['tranCode'],
            'merchantID' => $data['merchantID'],
            'merOrderNum' => $data['merOrderNum'],
            'tranAmt' =>  $data['tranAmt'],
            'feeAmt' => $data['feeAmt'],
            'tranDateTime' => $data['tranDateTime'],
            'frontMerUrl' => $data['frontMerUrl'],
            'backgroundMerUrl' => $data['backgroundMerUrl'],
            'orderId' => $data['orderId'],
            'gopayOutOrderId' => $data['gopayOutOrderId'],
            'tranIP' => $data['tranIP'],
            'respCode' => $data['respCode'],
            'gopayServerTime' => $data['gopayServerTime'],
            'VerficationCode' => $pkey,
        );
        $sign_str = '';
        foreach($sign_array as $k => $vo){
            $sign_str .= $k . '=[' . $vo . ']';
        }

        $sign_value = md5($sign_str);

      
       if($sign_value == $data['signValue'] && $data['respCode'] == '000'){
            $this->EditMoney($data["merOrderNum"], 'GopayBank', 0);
            exit( "SUCCESS");
        }
        
    }

}