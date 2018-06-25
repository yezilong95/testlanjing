<?php
namespace Pay\Controller;

use Org\Util\HttpClient;
use Org\Util\Ysenc;

class XfbQQSmController extends PayController{
	
	public function __construct(){
		parent::__construct();
	}

	
	public function Pay($array){

		$orderid = I("request.pay_orderid");
		
		$body = I('request.pay_productname');
		$notifyurl = $this->_site . 'Pay_XfbQQSm_notifyurl.html';

		$callbackurl = $this->_site . 'Pay_XfbQQSm_callbackurl.html';

		$parameter = array(
			'code' => 'XfbQQSm',
			'title' => '信付宝QQ扫码支付',
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

        //获取toke值
	   $arraystr = array(
            'versionId'  => '1.0',
            'orderAmount' => $return['amount'],
            'orderDate' => date('YmdHis', time()),
            'currency'   =>'RMB',
            'transType'  => '008',
            'asynNotifyUrl' => $return['notifyurl'],
            'synNotifyUrl'=> $callbackurl,
            'signType'  =>'MD5',
            'merId' => $return['mch_id'],
            'prdOrdNo' => $return['orderid'],
            'payMode'   => '00032',
            'receivableType' => 'D00',
            'prdAmt' => $return['amount'],
            'prdName' => $body,
        );
            
        $arraystr['signData'] = $this->_createSign($arraystr, $return['signkey']);
        list($return_code, $return_content) = $this->httpPostData($url, http_build_query($arraystr));

        $return_data = json_decode($return_content, true);

        if($return_data['retCode'] == '1' && $return_data['qrcode']){
            import("Vendor.phpqrcode.phpqrcode",'',".php");
            $url = $return_data['qrcode'];
            $QR = "Uploads/codepay/". $return['orderid'] . ".png";//已经生成的原始二维码图
            \QRcode::png($url, $QR, "L", 20);
            $this->assign("imgurl", '/'.$QR);
            $this->assign('params',$return);
            $this->assign('orderid',$return['orderid']);
            $this->assign('money',sprintf('%.2f',$return['amount']/100));
            $this->display("WeiXin/qq");
        }else{
            $this->showmessage($return_data['retMsg']);
        }
           
        return;

    }



   
    protected function _createSign($data, $key){
        $sign = '';
        ksort($data);
        foreach( $data as $k => $vo ){
            $sign .= $k . '=' . $vo . '&';
        }
        return strtoupper(md5($sign . 'key=' .$key));
    }




    public function httpPostData($url, $data_string){

        $cacert = ''; //CA根证书  (目前暂不提供)
        $CA = false ;   //HTTPS时是否进行严格认证
        $TIMEOUT = 30;  //超时时间(秒)
        $SSL = substr($url, 0, 8) == "https://" ? true : false;
        
        $ch = curl_init ();
        if ($SSL && $CA) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);   //  只信任CA颁布的证书
            curl_setopt($ch, CURLOPT_CAINFO, $cacert);      //  CA根证书（用来验证的网站证书是否是CA颁布）
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);    //  检查证书中是否设置域名，并且是否与提供的主机名匹配
        } else if ($SSL && !$CA) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  //  信任任何证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);    //  检查证书中是否设置域名
        }


        curl_setopt ( $ch, CURLOPT_TIMEOUT, $TIMEOUT);
        curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, $TIMEOUT-2);
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data_string );
        curl_setopt ( $ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded') );
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
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
	

	public function callbackurl(){
        $Order = M("Order");
        $pay_status = $Order->where("pay_orderid = '".$_REQUEST["orderid"]."'")->getField("pay_status");
        if($pay_status <> 0){
            $this->EditMoney($_REQUEST["orderid"], 'WftQQSm', 1);

        }else{
            exit("error");
        }
	}

	 // 服务器点对点返回
    public function notifyurl(){
     
        $data = I('post.','');
        // $f = fopen('./api_data.txt', 'a+');
        // fwrite($f,serialize($data). "\r\n");
        // fwrite($f, "======================================================\r\n");
        // fclose($f);
   
        if($data['orderStatus'] == 01){
            $sign = $data['signData'];
            unset($data['signData']);
            
            $order_model = M("Order");
            $signkey = $order_model->where("pay_orderid = '".$data['prdOrdNo']."'")->getField("key");

            $resp_sign = strtoupper($this->_createSign($data,$signkey));
    
            if($resp_sign == $sign){
                $this->EditMoney($data["prdOrdNo"], '', 0);
                exit('success');
            }
        }
    }

}