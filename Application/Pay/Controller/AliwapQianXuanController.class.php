<?php
/**
 * Created by PhpStorm.
 * User: 梁朝雄
 * Date: 2018-01-26
 * Time: 10:03
 */
namespace Pay\Controller;


class AliwapQianXuanController extends PayController
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
        $notifyurl = $this->_site . 'Pay_AliwapQianXuan_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_AliwapQianXuan_callbackurl.html'; //返回通知
        $parameter = array(
            'code' => 'AliwapQianXuan', // 通道名称
            'title' => '支付宝-浅萱',
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
        addSyslog('支付宝-浅萱提交字符串:'.$sysParams);
        vendor('Alipay.aop.AopClient');
        vendor('Alipay.aop.SignData');
        vendor('Alipay.aop.request.AlipayTradeWapPayRequest');
        $aop = new \AopClient();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = '2018012202025576';
        $aop->rsaPrivateKey = 'MIIEpAIBAAKCAQEAvaph9pdUSORsGTchK7nZTIcKLrBC0csNtasSbgSUaO7/l9Z4nav2RF2EDkp2cv1iyE2KKQwEnoiOr6ccrf8Yu5qhVxqfSidq3J4R6emCtdjNXfkD6mwayS7iGrOf5ODdv1wBfJNE/rquRSS9VCJn5HYtY8r2ei1tNiIV+jtLpaAIemjYUQZFmtpkid6UMOPG4zE0FtA55WLdBj+TSk9GhicrT/AajbR0qrF1XzPUNloS6BmL7HvQGcrTdUG2fzWqix0vDS6989U9xFkubVRdB6HMs9BD8S9UUEYCBnBYhRXRJizblSsl/6ZFUdWjvi3JNOL7cFUXuL0S1uYrG46nkwIDAQABAoIBAHiNq+bJGVGbnvoICJV6c5wo2VgDwPUIU5Z9PiGf15U7FEq2j8PoYYfiyOUsf01nsToPzxl4AheiRM9xMNGHq3jTOGndChJgK5Q/BLPhMKvVOfPZK5v9SqlFm4HNWnxoUYooOOmt0dT3Y32fdJfdppuYYhFkGHZgGWKnF8ENn2+NyHlVe8uEOHMwZ7tT9EeP7un5XyLVl8hZyflw7GUrfSSaCcy2HYMEDNDZ/iB5vkDzFNphkoFarvATpAcRScBp16yb0DhNYc3zM8Cvh6sSEIVQp8tg4aI4fo6jvneMv4CtCiGO4ARndMVf5mof/ffRdSJpvdtx/pyNu4LA4DcZSwECgYEA5baVxqcZBsAhlB/impIUP5a/UD4YmHITq8LA9UjII8kMsaxdN9Oe7B8H1v3h+SSKyiHiulTDoUlG1QVPtvGo2TgdMiRlEn5E9+6tMIgY67Tvp8tE4tf7mvGsIOhnFjsWvMRC9YQIaqIGWB0VSg3XFHXKPiuJIvvYSptVLlbgrCMCgYEA016bbq1RqhYCWKzf544qyY+Ng4HdE/eml8CAmNr7yf79LtFx4e7tK5goXwp+8o2+0+BhHpEATja2goagbd4obEzuaBAVw3rw3ujG1V4qmzcO4uebLfK+BPDaAfz6UZhWOy6UZE0rOusK4qwh/ZrO4g1sOBliogC20BiVWEOD1dECgYEAuIdxzcDRo9CXqMvsVsreJS7NxIH9dAi/sIykQOYUkFqjLd8OixgeZAORYq1T57XpH/MUlSYeeOPKWLkJjiaWwtgG7A68epygfqJm0cOicUZJ9nqdfbSO06sr4MuQdBVHcKAgQeWsdSxu+D10qqZvjmoI6uRCJ64z08rwJGd52A0CgYAL4bGTEyMYEIypN26OsasuEWe/ELnAuZcfyK5x6T1mKXNKAnQIY0npvW/nh8uDDvy/JEnRUg1WWDUCOVcjVe2nyNtN9jmC95I6tZMXw2dZukqX7rUGbDLKTE+09OeNVqbiRMgKq1vWwAeonkx62QzwIBkJPkQRH0EniXo7r+/lQQKBgQDSTSpm2Xhy1pgXizJzB9puYLibBP7LKlDfCENfS26o1mSkUxaf0rZPIymeSALWcmNal7uaMUUkT2QjY39AZsLPQDMOV/T690CNYK4nyXLUevkTwZU4lUH3wrEoiW3SBDiBbjfMoGPo3FHu+dGFEZ98nkj2RPQlTk09xFp1NSlZyg==';
        $aop->alipayrsaPublicKey= 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvaph9pdUSORsGTchK7nZTIcKLrBC0csNtasSbgSUaO7/l9Z4nav2RF2EDkp2cv1iyE2KKQwEnoiOr6ccrf8Yu5qhVxqfSidq3J4R6emCtdjNXfkD6mwayS7iGrOf5ODdv1wBfJNE/rquRSS9VCJn5HYtY8r2ei1tNiIV+jtLpaAIemjYUQZFmtpkid6UMOPG4zE0FtA55WLdBj+TSk9GhicrT/AajbR0qrF1XzPUNloS6BmL7HvQGcrTdUG2fzWqix0vDS6989U9xFkubVRdB6HMs9BD8S9UUEYCBnBYhRXRJizblSsl/6ZFUdWjvi3JNOL7cFUXuL0S1uYrG46nkwIDAQAB';
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
            $this->EditMoney($response['out_trade_no'], 'AliwapQianXuan', 1);
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
                $this->EditMoney($response['out_trade_no'], 'AliwapQianXuan', 0);
				
				echo "success";
				
				//自动返款
				vendor('Alipay.aop.AopClient');
        		vendor('Alipay.aop.SignData');
        		vendor('Alipay.aop.request.AlipayTradeWapPayRequest');
				vendor('Alipay.aop.request.AlipayFundTransToaccountTransferRequest');
        		$aop = new \AopClient();
        		$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        		$aop->appId = '2018012202025576';
        		$aop->rsaPrivateKey = 'MIIEpAIBAAKCAQEAvaph9pdUSORsGTchK7nZTIcKLrBC0csNtasSbgSUaO7/l9Z4nav2RF2EDkp2cv1iyE2KKQwEnoiOr6ccrf8Yu5qhVxqfSidq3J4R6emCtdjNXfkD6mwayS7iGrOf5ODdv1wBfJNE/rquRSS9VCJn5HYtY8r2ei1tNiIV+jtLpaAIemjYUQZFmtpkid6UMOPG4zE0FtA55WLdBj+TSk9GhicrT/AajbR0qrF1XzPUNloS6BmL7HvQGcrTdUG2fzWqix0vDS6989U9xFkubVRdB6HMs9BD8S9UUEYCBnBYhRXRJizblSsl/6ZFUdWjvi3JNOL7cFUXuL0S1uYrG46nkwIDAQABAoIBAHiNq+bJGVGbnvoICJV6c5wo2VgDwPUIU5Z9PiGf15U7FEq2j8PoYYfiyOUsf01nsToPzxl4AheiRM9xMNGHq3jTOGndChJgK5Q/BLPhMKvVOfPZK5v9SqlFm4HNWnxoUYooOOmt0dT3Y32fdJfdppuYYhFkGHZgGWKnF8ENn2+NyHlVe8uEOHMwZ7tT9EeP7un5XyLVl8hZyflw7GUrfSSaCcy2HYMEDNDZ/iB5vkDzFNphkoFarvATpAcRScBp16yb0DhNYc3zM8Cvh6sSEIVQp8tg4aI4fo6jvneMv4CtCiGO4ARndMVf5mof/ffRdSJpvdtx/pyNu4LA4DcZSwECgYEA5baVxqcZBsAhlB/impIUP5a/UD4YmHITq8LA9UjII8kMsaxdN9Oe7B8H1v3h+SSKyiHiulTDoUlG1QVPtvGo2TgdMiRlEn5E9+6tMIgY67Tvp8tE4tf7mvGsIOhnFjsWvMRC9YQIaqIGWB0VSg3XFHXKPiuJIvvYSptVLlbgrCMCgYEA016bbq1RqhYCWKzf544qyY+Ng4HdE/eml8CAmNr7yf79LtFx4e7tK5goXwp+8o2+0+BhHpEATja2goagbd4obEzuaBAVw3rw3ujG1V4qmzcO4uebLfK+BPDaAfz6UZhWOy6UZE0rOusK4qwh/ZrO4g1sOBliogC20BiVWEOD1dECgYEAuIdxzcDRo9CXqMvsVsreJS7NxIH9dAi/sIykQOYUkFqjLd8OixgeZAORYq1T57XpH/MUlSYeeOPKWLkJjiaWwtgG7A68epygfqJm0cOicUZJ9nqdfbSO06sr4MuQdBVHcKAgQeWsdSxu+D10qqZvjmoI6uRCJ64z08rwJGd52A0CgYAL4bGTEyMYEIypN26OsasuEWe/ELnAuZcfyK5x6T1mKXNKAnQIY0npvW/nh8uDDvy/JEnRUg1WWDUCOVcjVe2nyNtN9jmC95I6tZMXw2dZukqX7rUGbDLKTE+09OeNVqbiRMgKq1vWwAeonkx62QzwIBkJPkQRH0EniXo7r+/lQQKBgQDSTSpm2Xhy1pgXizJzB9puYLibBP7LKlDfCENfS26o1mSkUxaf0rZPIymeSALWcmNal7uaMUUkT2QjY39AZsLPQDMOV/T690CNYK4nyXLUevkTwZU4lUH3wrEoiW3SBDiBbjfMoGPo3FHu+dGFEZ98nkj2RPQlTk09xFp1NSlZyg==';
        		$aop->alipayrsaPublicKey= 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvaph9pdUSORsGTchK7nZTIcKLrBC0csNtasSbgSUaO7/l9Z4nav2RF2EDkp2cv1iyE2KKQwEnoiOr6ccrf8Yu5qhVxqfSidq3J4R6emCtdjNXfkD6mwayS7iGrOf5ODdv1wBfJNE/rquRSS9VCJn5HYtY8r2ei1tNiIV+jtLpaAIemjYUQZFmtpkid6UMOPG4zE0FtA55WLdBj+TSk9GhicrT/AajbR0qrF1XzPUNloS6BmL7HvQGcrTdUG2fzWqix0vDS6989U9xFkubVRdB6HMs9BD8S9UUEYCBnBYhRXRJizblSsl/6ZFUdWjvi3JNOL7cFUXuL0S1uYrG46nkwIDAQAB';
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