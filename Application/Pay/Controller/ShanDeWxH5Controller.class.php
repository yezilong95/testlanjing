<?php
namespace Pay\Controller;

class ShanDeWxH5Controller extends PayController
{

   
    public function Pay($array)
    {
      
        $orderid = I("request.pay_orderid", '');
        
        $body = I('request.pay_productname', '');

        $parameter = [
            'code' => 'ShanDeWxH5',
            'title' => '杉德支付（微信H5）',
            'exchange' => 100, // 金额比例
            'gateway' => '',
            'orderid'=>'',
            'out_trade_id' => $orderid, //外部订单号
            'channel'=>$array,
            'body'=>$body
        ];

        //支付金额
        $pay_amount = I("request.pay_amount", 0);

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);
      
        //如果生成错误，自动跳转错误页面
        $return["status"] == "error" && $this->showmessage($return["errorcontent"]);
        
        //跳转页面，优先取数据库中的跳转页面
        $return["notifyurl"] || $return["notifyurl"] = $this->_site . 'Pay_ShanDeSnSm_notifyurl.html';
        
        $return['callbackurl'] || $return['callbackurl'] = $this->_site . 'Pay_ShanDeSnSm_callbackurl.html';
        
        $data = [
            'app_id' => $return['appid'],
            'timestamp' => date('Y-m-d H:i:s', time()),
            'method' => 'charge.create',
            'order_no'  => $return['orderid'],
            'amount'  => $return['amount'],
            'subject'  => $orderid,
            'mer_id'  => $return['mch_id'],
            'notify_url'  => $return['notifyurl'],
			'return_url' => $return['callbackurl'],
			'client_ip' => getIP(),
            'channel' => 'wx_h5',
        ];
        
        $data['sign'] = strtoupper( md5Sign($data, $return['appsecret'], '') );
        $data['sign_type'] = 'md5';
      
        $res = curlPost($return['gateway'], http_build_query($data));

        $res = json_decode($res, true);
        
        if($res['code'] == '0' && $res['data']['credential']['wx_h5']['pay_url']){
            header('Location:'.$res['data']['credential']['wx_h5']['pay_url']);
        }else{
            $this->showmessage($res['message']);
        }

    }


	public function callbackurl(){
        $Order = M("Order");
		$data = json_decode($_REQUEST['data'], true);
        $pay_status = $Order->where("pay_orderid = '".$data["order_no"]."'")->getField("pay_status");
        if($pay_status <> 0){
            $this->EditMoney($data["order_no"], '', 1);
        }else{
            exit("error");
        }
	}

	 // 服务器点对点返回
    public function notifyurl(){
        $content = I('post.','');
        // $f = fopen('./api_data.txt', 'a+');
        // fwrite($f, serialize($content). "\r\n");
        // fwrite($f, "======================================================\r\n");
        // fclose($f);
        $data = json_decode(htmlspecialchars_decode($content['data']) , true);      
        if($data['status'] == '20'){
            $account = M("Order")->where("pay_orderid = '".$data["order_no"]."'")->getField("memberid");
            $channel = M('ChannelAccount')->where(['mch_id'=>$account])->find();
        
            $newSign = strtoupper( md5($channel['appsecret'] . htmlspecialchars_decode($content['data']) . $channel['appsecret']) );
            if($content['sign'] == $newSign){
                 $this->EditMoney($data["order_no"], '', 0);
                 echo '{"code": 0,"message": "接收成功"}';
            }
        }
    }
    
}
?>