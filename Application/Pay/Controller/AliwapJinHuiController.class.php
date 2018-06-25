<?php
/**
 * Created by PhpStorm.
 * User: 梁朝雄
 * Date: 2018-01-26
 * Time: 10:03
 */
namespace Pay\Controller;


class AliwapJinHuiController extends PayController
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
        $notifyurl = $this->_site . 'Pay_AliwapJinHui_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_AliwapJinHui_callbackurl.html'; //返回通知
        $parameter = array(
            'code' => 'AliwapJinHui', // 通道名称
            'title' => '支付宝-金辉',
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
        addSyslog('支付宝-金辉提交字符串:'.$sysParams);
        vendor('Alipay.aop.AopClient');
        vendor('Alipay.aop.SignData');
        vendor('Alipay.aop.request.AlipayTradeWapPayRequest');
        $aop = new \AopClient();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = '2018012302048474';
        $aop->rsaPrivateKey = 'MIIEowIBAAKCAQEAtzm4dChV25Xey/gpmKgS7jFPwUmzN6IKA8tUyTrDzhAfK+G+wQrQ3VYqDZlE4iohWsWSFqwVUMH/ptXyekj93jf7zhQ1+OC1T1t0a2WQE4xH+WEWXeYLXnVVWdEY2ZO8EYdPUykm3XEtC71VAzumgz8zLnkbuVhu8b2RBqaW1TUgJX01Qcfcl/h/ECMTz7SR59S+r3ThAlrzDAR7d3LXaxS7HIfnlEpqAYSpo0uXwcacawq7t2rQrozRW4ywzCUHg0awuiA3TcZamnAyy8KVEa8failmArdaVyZgQ2txhNHboyMQBCH+P/9b1n2Xf8LEYYsQ1VSMislRhN2Y9PLK7wIDAQABAoIBAGGRsCLS892J8mmljekH34TN542jRHdkp3abyVubGnfZ5++DOAQuUUj8QEqbiq3xB3AB86h1HKizlgLuvT/XLLEwGLwF0TPl3UcUCWvDWrS+J/mpdLKt9PHpA3lfup7qZ+ThrCE5scOMKBOvT9z339TWS9f8kAHkELhUXS0wevOvfut7QfEX1DP77mAwPAXwHbzxFGuENv5rwMzEaRVCIvZAPxV4iYvVA6gVnlB1roHbfRssHI905FvT3qHLq5kbVtVmyPDLagN0fBxJFeQRm1fxgUCT62xj4nfIWdBUwClUjp6Q1OS9p1i64lDHeB9wJzx5Yy88fL8u5MT9LSM2XOECgYEA3F70kB9cYKsvmKef4HvxXml4q2nJZacBMX/CHvQpyqVisSKGG5PFjI4IZ+U0JPMKM/v4zzQA0fnyG2Rwxvk7toxgoPWmvGUgIv9NpGGxx0mE8vIHJyMXGIUd7Fqo4hAP49XKRSFLUCvfTN//vJsvY+sMaoN36ABNoz96gVQXWTECgYEA1NlVTvZZon8COzZqCdCJnOV0FsJrVF8fdabGfRlu4nGfTyFGx4Rxakik8ZKi6u3S2s2se92uShRO7qQ1d5rrmvlHNbeEfBVa78dnK7yV+dlpWF8ROFIZ0F9s2yh22tlqto9JHQCWUW3ULD+bDQYnxoJUXuHuvyvK2kb8kjGPXh8CgYASd5KfEUG4iB/w9vsa4MgVVVlbY/4QmrIq573owiVOahWfKnl6jiOb57skmbyI7CUKvi7XjlATSJLVYNFimzg50AG4+10BpKfQxvxGrjkrrimKAmY/DV2+HEmpqN9GMnpiaWQN7wBUs5h+5LaVo5uRD/12X5YIxx26dHNbqyRZwQKBgG1jWF1hbAWAApSNzYdHvkF9BuFff6t3y4I6eM34ES2dOUOAlZCaN3No7CQeuU3FddTvWNK7xgPaVgp9J+FVI/qcXAV2UEc47mhGcXZf0C/8lRBOo1nvj8awFBC5xgOJMzepgei+0YH90MoA0l2qKSzy9AtjT8C9792oF8vGXqj/AoGBANCXGhASSl8EOJ0JnlYK4tA5Uxjqamw62AqCdh2+Td5mRQ0WCel8EaD3IOd++WBIZBF481OCAX31wYlPMsIivpmyHirX8wq8DHX9pkBNJnGb57FfDBCSPVom5mPwrW2Sk4UoChYtn9R3O02AmBQt9pmZl2fehGEy7j01DYD+tIkE';
        $aop->alipayrsaPublicKey= 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAtzm4dChV25Xey/gpmKgS7jFPwUmzN6IKA8tUyTrDzhAfK+G+wQrQ3VYqDZlE4iohWsWSFqwVUMH/ptXyekj93jf7zhQ1+OC1T1t0a2WQE4xH+WEWXeYLXnVVWdEY2ZO8EYdPUykm3XEtC71VAzumgz8zLnkbuVhu8b2RBqaW1TUgJX01Qcfcl/h/ECMTz7SR59S+r3ThAlrzDAR7d3LXaxS7HIfnlEpqAYSpo0uXwcacawq7t2rQrozRW4ywzCUHg0awuiA3TcZamnAyy8KVEa8failmArdaVyZgQ2txhNHboyMQBCH+P/9b1n2Xf8LEYYsQ1VSMislRhN2Y9PLK7wIDAQAB';
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
            $this->EditMoney($response['out_trade_no'], 'AliwapJinHui', 1);
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
                $this->EditMoney($response['out_trade_no'], 'AliwapJinHui', 0);
				
				echo "success";
				
				//自动返款
				vendor('Alipay.aop.AopClient');
        		vendor('Alipay.aop.SignData');
        		vendor('Alipay.aop.request.AlipayTradeWapPayRequest');
				vendor('Alipay.aop.request.AlipayFundTransToaccountTransferRequest');
        		$aop = new \AopClient();
        		$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        		$aop->appId = '2018012302048474';
        		$aop->rsaPrivateKey = 'MIIEowIBAAKCAQEAtzm4dChV25Xey/gpmKgS7jFPwUmzN6IKA8tUyTrDzhAfK+G+wQrQ3VYqDZlE4iohWsWSFqwVUMH/ptXyekj93jf7zhQ1+OC1T1t0a2WQE4xH+WEWXeYLXnVVWdEY2ZO8EYdPUykm3XEtC71VAzumgz8zLnkbuVhu8b2RBqaW1TUgJX01Qcfcl/h/ECMTz7SR59S+r3ThAlrzDAR7d3LXaxS7HIfnlEpqAYSpo0uXwcacawq7t2rQrozRW4ywzCUHg0awuiA3TcZamnAyy8KVEa8failmArdaVyZgQ2txhNHboyMQBCH+P/9b1n2Xf8LEYYsQ1VSMislRhN2Y9PLK7wIDAQABAoIBAGGRsCLS892J8mmljekH34TN542jRHdkp3abyVubGnfZ5++DOAQuUUj8QEqbiq3xB3AB86h1HKizlgLuvT/XLLEwGLwF0TPl3UcUCWvDWrS+J/mpdLKt9PHpA3lfup7qZ+ThrCE5scOMKBOvT9z339TWS9f8kAHkELhUXS0wevOvfut7QfEX1DP77mAwPAXwHbzxFGuENv5rwMzEaRVCIvZAPxV4iYvVA6gVnlB1roHbfRssHI905FvT3qHLq5kbVtVmyPDLagN0fBxJFeQRm1fxgUCT62xj4nfIWdBUwClUjp6Q1OS9p1i64lDHeB9wJzx5Yy88fL8u5MT9LSM2XOECgYEA3F70kB9cYKsvmKef4HvxXml4q2nJZacBMX/CHvQpyqVisSKGG5PFjI4IZ+U0JPMKM/v4zzQA0fnyG2Rwxvk7toxgoPWmvGUgIv9NpGGxx0mE8vIHJyMXGIUd7Fqo4hAP49XKRSFLUCvfTN//vJsvY+sMaoN36ABNoz96gVQXWTECgYEA1NlVTvZZon8COzZqCdCJnOV0FsJrVF8fdabGfRlu4nGfTyFGx4Rxakik8ZKi6u3S2s2se92uShRO7qQ1d5rrmvlHNbeEfBVa78dnK7yV+dlpWF8ROFIZ0F9s2yh22tlqto9JHQCWUW3ULD+bDQYnxoJUXuHuvyvK2kb8kjGPXh8CgYASd5KfEUG4iB/w9vsa4MgVVVlbY/4QmrIq573owiVOahWfKnl6jiOb57skmbyI7CUKvi7XjlATSJLVYNFimzg50AG4+10BpKfQxvxGrjkrrimKAmY/DV2+HEmpqN9GMnpiaWQN7wBUs5h+5LaVo5uRD/12X5YIxx26dHNbqyRZwQKBgG1jWF1hbAWAApSNzYdHvkF9BuFff6t3y4I6eM34ES2dOUOAlZCaN3No7CQeuU3FddTvWNK7xgPaVgp9J+FVI/qcXAV2UEc47mhGcXZf0C/8lRBOo1nvj8awFBC5xgOJMzepgei+0YH90MoA0l2qKSzy9AtjT8C9792oF8vGXqj/AoGBANCXGhASSl8EOJ0JnlYK4tA5Uxjqamw62AqCdh2+Td5mRQ0WCel8EaD3IOd++WBIZBF481OCAX31wYlPMsIivpmyHirX8wq8DHX9pkBNJnGb57FfDBCSPVom5mPwrW2Sk4UoChYtn9R3O02AmBQt9pmZl2fehGEy7j01DYD+tIkE';
        		$aop->alipayrsaPublicKey= 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAtzm4dChV25Xey/gpmKgS7jFPwUmzN6IKA8tUyTrDzhAfK+G+wQrQ3VYqDZlE4iohWsWSFqwVUMH/ptXyekj93jf7zhQ1+OC1T1t0a2WQE4xH+WEWXeYLXnVVWdEY2ZO8EYdPUykm3XEtC71VAzumgz8zLnkbuVhu8b2RBqaW1TUgJX01Qcfcl/h/ECMTz7SR59S+r3ThAlrzDAR7d3LXaxS7HIfnlEpqAYSpo0uXwcacawq7t2rQrozRW4ywzCUHg0awuiA3TcZamnAyy8KVEa8failmArdaVyZgQ2txhNHboyMQBCH+P/9b1n2Xf8LEYYsQ1VSMislRhN2Y9PLK7wIDAQAB';
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