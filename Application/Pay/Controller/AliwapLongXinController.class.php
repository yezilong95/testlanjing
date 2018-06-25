<?php
/**
 * Created by PhpStorm.
 * User: 梁朝雄
 * Date: 2018-01-26
 * Time: 10:03
 */
namespace Pay\Controller;


class AliwapLongXinController extends PayController
{
    public function __construct()
    {
        parent::__construct();
    }

    //支付
    public function Pay($array)
    {
        $orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');
        $notifyurl = $this->_site . 'Pay_AliwapLongXin_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_AliwapLongXin_callbackurl.html'; //返回通知
        $parameter = array(
            'code' => 'AliwapLongXin', // 通道名称
            'title' => '支付宝-龙讯',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body'=>$body,
            'channel'=>$array
        );
        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);
		
        $return['subject'] = $body;
        
        $this->alipayWap($return);
                
    }
	
    protected function alipayWap($params){

        $data = array(
            'out_trade_no'=>$params['orderid'],
            'total_amount'=>$params['amount'],
            //'subject'=>$params['subject'],
            'subject'=>"购买钻石点卡",
            'product_code' => "QUICK_WAP_WAY"
        );
        $sysParams = json_encode($data,JSON_UNESCAPED_UNICODE);
        addSyslog('支付宝-龙讯提交字符串:'.$sysParams);
        vendor('Alipay.aop.AopClient');
        vendor('Alipay.aop.SignData');
        vendor('Alipay.aop.request.AlipayTradeWapPayRequest');
        $aop = new \AopClient();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = '2018012402057871';
        $aop->rsaPrivateKey = 'MIIEowIBAAKCAQEArJvBMxC+FgnOFLamA/iQczzUCH9kaehpfjqFKvOwKTvWhK+gZ6ckqST8IZJkLAbtHa8yytQrcR2mH0wy1Jc3BfJpKWUsyt3jQWTdgVWtQ+BeTkq/8+Yfi3xY3GiaU8YkCj63/awAXpmRA5B+YSn4aO1c5KuHp1emwkVBgARcpCxVbqSOE6Sv0i/y/jDWLlLSVBijQnnlbzksfa/KA3T5K7AkjgRLQjpJzq20q/BBEsIoqoaydrywqIvEBDYvKCppEPoV19LiUi7e8IZx4bPpfHyxhK3zVd4YgJaobDTDyvlDH9F8ZyXYwN3QbtgMbKkphb0AvjifFhhFa4352ILUBwIDAQABAoIBAHWuc5hnT9IiuThyFtoaPBH1ZPKuOD3k0RfYujZAkthqDaXFP+2rjVks+CHDdzx1NHDKpCyZX0zt0/b+sqEyv7b0QTKR4molwe0x4btuOIVQkbt7R0f4m4U0MDmFr8JyEtxRSoxPGqlktfPiLef8/3o6at0U/ZXgMre9FB093BOpbp4nm0T6FLpzl2O1X8wFMHCWLZ0WgVpCmknCiVZiZEyI3l8eAXwqw19GF6Rjx62MNZo6PJyTawoJmaMQqVpLhf4aS9VL596VeTUKXhE+8vC1wEtqWa44yqHcc7yEfu+VaR5ht5sA19u01sNjfLtBhdVhQS/tFzPL6eXXEL4ot/ECgYEA5PjUowOu1QKJv3C888remG1eZk4hi0/iawcPXB96rudQtR2O6RY8dNKYI1WsWnlk9a38sAdsJJa71I/dtVwrBNn0fIsLkuF182krpwERBijuXSzsjoyvj+B7PXHGMpLI7cFG9ki3VB5AorKBjsZsw/SyvtRk/3P3gYzS70I5W+MCgYEAwPu02C+6TibiBFi0IuqMC9mOzuuXB2W1FL5XoCHDzMEJjue1JeLn+5bKvKAY6DrmFC7PP/yRBxr20Rivcf/a2/vknvOP7Gk1i+/Wh3q0cRWm3kyrz4Jo6UDbEV2AwWRB1qL9rEiqhc0E0gS+iR2FJGJOFw0Gvv2nc/skj8m9aI0CgYEAhMf733DCUUqAgSEqoiISRciexqsmbrhrr+9PK/ghWA5SUkWK24aGlqSNj2geY/Uj5Aj6kUYso2c6E4E+a+7AD7cpPZQQqSPQF9D0fIt0yOKxoBzuLZhOMxxVu4MgEbHnjWDY+veeQMyuZOspkJdm+ZgMk/dtfmpMrLbyl8cKpksCgYBI8duVq9S8Ha5o/i541DdDc2Srihl4TlV4FcqBWMHt7zlxrtumCnKtgn33dnxzq2+0SU1FXm5jRSnuN2p5qMBNTpVID8BjGBGJZ4qrgxIZfJmqhUicEyscn8sucS1t7DuGqe1A5eau1KPxzqFGqsXcztu9ksrt/msBR/i18QeLMQKBgBUWBYNncL8KThv/+4wPIM6QBe6BqIWvA4DhFwECv/XbcOWXOuFdjUuFz1QGwU61f56u00SQ3lDyb5dWoaf0TjGa//VAB89h3SVHBCXuD9tbFWsNMgf/+2kJ5jqUaVwBb2qUSsQMn6qOOdAklhOU1RgGs7vASs0+5lPUJDfxL2WJ';
        $aop->alipayrsaPublicKey= 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEArJvBMxC+FgnOFLamA/iQczzUCH9kaehpfjqFKvOwKTvWhK+gZ6ckqST8IZJkLAbtHa8yytQrcR2mH0wy1Jc3BfJpKWUsyt3jQWTdgVWtQ+BeTkq/8+Yfi3xY3GiaU8YkCj63/awAXpmRA5B+YSn4aO1c5KuHp1emwkVBgARcpCxVbqSOE6Sv0i/y/jDWLlLSVBijQnnlbzksfa/KA3T5K7AkjgRLQjpJzq20q/BBEsIoqoaydrywqIvEBDYvKCppEPoV19LiUi7e8IZx4bPpfHyxhK3zVd4YgJaobDTDyvlDH9F8ZyXYwN3QbtgMbKkphb0AvjifFhhFa4352ILUBwIDAQAB';
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='UTF-8';
        $aop->format='json';
        $request = new \AlipayTradeWapPayRequest ();
        $request->setBizContent($sysParams);
        $request->setNotifyUrl($params['notifyurl']);
        $request->setReturnUrl($params['callbackurl']);
        $result = $aop->pageExecute ( $request,"post");
        echo $result;
    }

    //同步通知
    public function callbackurl()
    {
        $response = $_GET;
        //$sign = $response['sign'];
        //$sign_type = $response['sign_type'];
        //unset($response['sign']);
        //unset($response['sign_type']);
        //$publiKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvpsMHtSOn32R4pU8DOG5TJ3sz0O9LVum1UgjFQOSZLan8WOgyckE5zzgyPTLvzc0hDQMh2teBnOXqhW7sSuCGc+fE9hROvg/pFNGRa/sQlYRTV16+FizkgS2+80Zl6oelZLM0YHx/IVF2RosJzR8FXzE+Mpw8soKDRKYtfgLWXFfLU0lxZwD6wcgoKpzVzmlz4eFxTGjMOX/1A8gtSuXEPwg/HQBb5veLvaT0C8LrtyEBeE4zP8A5Gipx/fksJBq9uTAHMN0G39FYLkz46hTYwsiepwcZIkbLLpy3hYGIpePUyRg4gUdp5zr5KhLoidCICKQWLpId401rwMdno0uVwIDAQAB';

        //ksort($response);
        //$signData = '';
        //foreach ($response as $key=>$val){
        //    $signData .= $key .'='.$val."&";
        //}
        //$signData = trim($signData,'&');
        ////$checkResult = $aop->verify($signData,$sign,$publiKey,$sign_type);
        //$res = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($publiKey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
        //$result = (bool)openssl_verify($signData, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);

        //if($result){
            $this->EditMoney($response['out_trade_no'], 'AliwapLongXin', 1);
        //}else{
          //  exit('error:check sign Fail!');
        //}

    }

    //异步通知
    public function notifyurl()
    {
        $response = $_POST;
        //$sign = $response['sign'];
        //$sign_type = $response['sign_type'];
        //unset($response['sign']);
        //unset($response['sign_type']);
        //$publiKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvpsMHtSOn32R4pU8DOG5TJ3sz0O9LVum1UgjFQOSZLan8WOgyckE5zzgyPTLvzc0hDQMh2teBnOXqhW7sSuCGc+fE9hROvg/pFNGRa/sQlYRTV16+FizkgS2+80Zl6oelZLM0YHx/IVF2RosJzR8FXzE+Mpw8soKDRKYtfgLWXFfLU0lxZwD6wcgoKpzVzmlz4eFxTGjMOX/1A8gtSuXEPwg/HQBb5veLvaT0C8LrtyEBeE4zP8A5Gipx/fksJBq9uTAHMN0G39FYLkz46hTYwsiepwcZIkbLLpy3hYGIpePUyRg4gUdp5zr5KhLoidCICKQWLpId401rwMdno0uVwIDAQAB';

        //ksort($response);
        //$signData = '';
        //foreach ($response as $key=>$val){
          //  $signData .= $key .'='.$val."&";
        //}
        //$signData = trim($signData,'&');
        ////$checkResult = $aop->verify($signData,$sign,$publiKey,$sign_type);
        //$res = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($publiKey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
        //$result = (bool)openssl_verify($signData, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);

        //if($result){
            if($response['trade_status'] == 'TRADE_SUCCESS' || $response['trade_status'] == 'TRADE_FINISHED'){
                $this->EditMoney($response['out_trade_no'], 'AliwapLongXin', 0);
				
				echo "success";
				
				//自动返款
				vendor('Alipay.aop.AopClient');
        		vendor('Alipay.aop.SignData');
        		vendor('Alipay.aop.request.AlipayTradeWapPayRequest');
				vendor('Alipay.aop.request.AlipayFundTransToaccountTransferRequest');
        		$aop = new \AopClient();
        		$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        		$aop->appId = '2018012402057871';
        		$aop->rsaPrivateKey = 'MIIEowIBAAKCAQEArJvBMxC+FgnOFLamA/iQczzUCH9kaehpfjqFKvOwKTvWhK+gZ6ckqST8IZJkLAbtHa8yytQrcR2mH0wy1Jc3BfJpKWUsyt3jQWTdgVWtQ+BeTkq/8+Yfi3xY3GiaU8YkCj63/awAXpmRA5B+YSn4aO1c5KuHp1emwkVBgARcpCxVbqSOE6Sv0i/y/jDWLlLSVBijQnnlbzksfa/KA3T5K7AkjgRLQjpJzq20q/BBEsIoqoaydrywqIvEBDYvKCppEPoV19LiUi7e8IZx4bPpfHyxhK3zVd4YgJaobDTDyvlDH9F8ZyXYwN3QbtgMbKkphb0AvjifFhhFa4352ILUBwIDAQABAoIBAHWuc5hnT9IiuThyFtoaPBH1ZPKuOD3k0RfYujZAkthqDaXFP+2rjVks+CHDdzx1NHDKpCyZX0zt0/b+sqEyv7b0QTKR4molwe0x4btuOIVQkbt7R0f4m4U0MDmFr8JyEtxRSoxPGqlktfPiLef8/3o6at0U/ZXgMre9FB093BOpbp4nm0T6FLpzl2O1X8wFMHCWLZ0WgVpCmknCiVZiZEyI3l8eAXwqw19GF6Rjx62MNZo6PJyTawoJmaMQqVpLhf4aS9VL596VeTUKXhE+8vC1wEtqWa44yqHcc7yEfu+VaR5ht5sA19u01sNjfLtBhdVhQS/tFzPL6eXXEL4ot/ECgYEA5PjUowOu1QKJv3C888remG1eZk4hi0/iawcPXB96rudQtR2O6RY8dNKYI1WsWnlk9a38sAdsJJa71I/dtVwrBNn0fIsLkuF182krpwERBijuXSzsjoyvj+B7PXHGMpLI7cFG9ki3VB5AorKBjsZsw/SyvtRk/3P3gYzS70I5W+MCgYEAwPu02C+6TibiBFi0IuqMC9mOzuuXB2W1FL5XoCHDzMEJjue1JeLn+5bKvKAY6DrmFC7PP/yRBxr20Rivcf/a2/vknvOP7Gk1i+/Wh3q0cRWm3kyrz4Jo6UDbEV2AwWRB1qL9rEiqhc0E0gS+iR2FJGJOFw0Gvv2nc/skj8m9aI0CgYEAhMf733DCUUqAgSEqoiISRciexqsmbrhrr+9PK/ghWA5SUkWK24aGlqSNj2geY/Uj5Aj6kUYso2c6E4E+a+7AD7cpPZQQqSPQF9D0fIt0yOKxoBzuLZhOMxxVu4MgEbHnjWDY+veeQMyuZOspkJdm+ZgMk/dtfmpMrLbyl8cKpksCgYBI8duVq9S8Ha5o/i541DdDc2Srihl4TlV4FcqBWMHt7zlxrtumCnKtgn33dnxzq2+0SU1FXm5jRSnuN2p5qMBNTpVID8BjGBGJZ4qrgxIZfJmqhUicEyscn8sucS1t7DuGqe1A5eau1KPxzqFGqsXcztu9ksrt/msBR/i18QeLMQKBgBUWBYNncL8KThv/+4wPIM6QBe6BqIWvA4DhFwECv/XbcOWXOuFdjUuFz1QGwU61f56u00SQ3lDyb5dWoaf0TjGa//VAB89h3SVHBCXuD9tbFWsNMgf/+2kJ5jqUaVwBb2qUSsQMn6qOOdAklhOU1RgGs7vASs0+5lPUJDfxL2WJ';
        		$aop->alipayrsaPublicKey= 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEArJvBMxC+FgnOFLamA/iQczzUCH9kaehpfjqFKvOwKTvWhK+gZ6ckqST8IZJkLAbtHa8yytQrcR2mH0wy1Jc3BfJpKWUsyt3jQWTdgVWtQ+BeTkq/8+Yfi3xY3GiaU8YkCj63/awAXpmRA5B+YSn4aO1c5KuHp1emwkVBgARcpCxVbqSOE6Sv0i/y/jDWLlLSVBijQnnlbzksfa/KA3T5K7AkjgRLQjpJzq20q/BBEsIoqoaydrywqIvEBDYvKCppEPoV19LiUi7e8IZx4bPpfHyxhK3zVd4YgJaobDTDyvlDH9F8ZyXYwN3QbtgMbKkphb0AvjifFhhFa4352ILUBwIDAQAB';
        		$aop->apiVersion = '1.0';
        		$aop->signType = 'RSA2';
        		$aop->postCharset='UTF-8';
        		$aop->format='json';
				
				$log = M("Log");
				$data = array();
                $data["content"] = json_encode($response);
                $data["addTime"] = date("Y-m-d H:i:s");
                $log->add($data);
				
				$list = M('Order')->where(['pay_orderid'=>$response['out_trade_no'],'xx'=>0])->find();
                if($list){
					//$amount=(float)$list['pay_amount'];
					$amount=(float)$response['total_amount'];//total_amount才对，并非文档上说的total_fee字段
					$amount=$amount*(1-0.008);
					$amountStr=sprintf("%.2f",$amount);
					//echo $amountStr;
					
					$cmd = M('Order')->where(['pay_orderid'=>$response['out_trade_no'],'xx'=>0])->setField("xx",1);
				
				
					$account="13427955188";//收款方帐号617659255@qq.com
				
        			$request = new \AlipayFundTransToaccountTransferRequest();
$request->setBizContent("{" .
"\"out_biz_no\":\"".$response['out_trade_no']."B\"," .
"\"payee_type\":\"ALIPAY_LOGONID\"," .
"\"payee_account\":\"".$account."\"," .
"\"amount\":\"".$amountStr."\"," .
"\"payer_show_name\":\"\"," .
"\"payee_real_name\":\"\"," .
"\"remark\":\"\"" .
"}");
$result = $aop->execute ( $request); 

$log = M("Log");
				$data = array();
                $data["content"] = json_encode($result);
                $data["addTime"] = date("Y-m-d H:i:s");
                $log->add($data);
				
				}
                //exit("success");
            }
        //}else{
          //  exit('error:check sign Fail!');
       //}

    }
	

}