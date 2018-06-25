<?php
/**
 * Created by PhpStorm.
 * User: 梁朝雄
 * Date: 2018-01-26
 * Time: 10:03
 */
namespace Pay\Controller;


class AutoAliwapController extends PayController
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
        $notifyurl = $this->_site . 'Pay_AutoAliwap_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_AutoAliwap_callbackurl.html'; //返回通知
        $parameter = array(
            'code' => 'AutoAliwap', // 通道名称
            'title' => '自动返-支付宝wap',
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
            'subject'=>"太阳贴膏药",
            'product_code' => "QUICK_WAP_WAY"
        );
        $sysParams = json_encode($data,JSON_UNESCAPED_UNICODE);
        addSyslog('官方直清支付宝h5提交字符串:'.$sysParams);
        vendor('Alipay.aop.AopClient');
        vendor('Alipay.aop.SignData');
        vendor('Alipay.aop.request.AlipayTradeWapPayRequest');
        $aop = new \AopClient();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = '2018012202024663';
        $aop->rsaPrivateKey = 'MIIEowIBAAKCAQEAvpsMHtSOn32R4pU8DOG5TJ3sz0O9LVum1UgjFQOSZLan8WOgyckE5zzgyPTLvzc0hDQMh2teBnOXqhW7sSuCGc+fE9hROvg/pFNGRa/sQlYRTV16+FizkgS2+80Zl6oelZLM0YHx/IVF2RosJzR8FXzE+Mpw8soKDRKYtfgLWXFfLU0lxZwD6wcgoKpzVzmlz4eFxTGjMOX/1A8gtSuXEPwg/HQBb5veLvaT0C8LrtyEBeE4zP8A5Gipx/fksJBq9uTAHMN0G39FYLkz46hTYwsiepwcZIkbLLpy3hYGIpePUyRg4gUdp5zr5KhLoidCICKQWLpId401rwMdno0uVwIDAQABAoIBAF07yDT4cf8v1AFKUEJY02PXx/MmRk3+i9+91nM7+QrMkd+whQDPFFfr3mRN4a/Z2BVmxE4lRavQNPqcgN/L0WrAtSZm5Gz861x8413JDL7yDKqUNEdwU/3z0nHexEr8itNNKGS+tFh59E2a0Mgokx2Ll2m+1HreOwZj91DjUOHhi7DLqpJjZcsT9aEf+0ILXP44P3aJEXhduKfy0vk8PYWgclg/DNi4wWdA8a8FsugFK2eOx4Rn96kx3oal9QuNmhxTlQzpjViuxQo121fInCtkG10v6MTBc0Ru6GwRM5OFBGks6ZhmSUZgZqDQ0SJmOLvh9CLcULC5QM2j0wX/wTkCgYEA4UNCuSt3PBW7o55RmsNXjcFZdYB5jB5ZlLZ/Jjj/b4LeYdzXXoe8s+x6blq25xE0ZfQPT3roT3S1ZiUI51SV8eyONtmk0cN29q5HeLldZH4bok84Ei6HJ+REEOWn5wr29b3gGnVIAKhjR7cgTWXBX6vAVmr4bl3uAT6h5gqN7KMCgYEA2J0qsyXlHdYQQI5hd7O3rmQUvDY+oF5Q5ndmB6EQp0fbi3MyIRtV0yB4g2dfEXnnXpCd3Anw97/x8FUsFVQZaZKP1MOEpHNDVR4mNIkwJCrZS08uS8TU2r5jEPaCM1aldr1WNHptYsmQNw75XhhvaRoZWRMikj5norclCfsSPr0CgYBcpHXng7nI60M9WlpDZP04HoG1Mn5KoxzCbX/Db4OWD+N3qgSlKvvvn10Gz6YTR98d4w786BZsvxnvhWGxCfeVBG3EnyQK5PGjKC/atZl0P+0LhrsPtzT8sgNQU7MG8Vp53HozR3KyWo5iKy1Mx4GM4Cz9HYdIlLbSqjiZTFPsMwKBgQDVPhvcEvEz5xlGxCP1mEm59zJmeqw6ab8QsvgRiKEBXP8nj/cImoVp+6xICAqSBMUd1hZhmLPM4fwGUYK9WHZP9QV9OiEpV4MwlyabT3bCFCCoP8Heu12kHgselt5kNedcNlZYATIQL0e2vBoHZNzAxf2wL+M3vxF9IPjok29JrQKBgGj/JIsaalSCvM9YO5bjLO3ndSngr2iGQ+jZAMEkod4Z3zD8axL2Tci4OduUUP2QRYd6vhOW9suBcmHsqKWqlWBKgwJ63jBA4zHrw2WfNom+xFNimzmk66MSKk0iXwm25ZYi25EaL7/dpIdo+GvjWLnmEJTfFhsGBQtFsXXRcU+M';
        $aop->alipayrsaPublicKey= 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvpsMHtSOn32R4pU8DOG5TJ3sz0O9LVum1UgjFQOSZLan8WOgyckE5zzgyPTLvzc0hDQMh2teBnOXqhW7sSuCGc+fE9hROvg/pFNGRa/sQlYRTV16+FizkgS2+80Zl6oelZLM0YHx/IVF2RosJzR8FXzE+Mpw8soKDRKYtfgLWXFfLU0lxZwD6wcgoKpzVzmlz4eFxTGjMOX/1A8gtSuXEPwg/HQBb5veLvaT0C8LrtyEBeE4zP8A5Gipx/fksJBq9uTAHMN0G39FYLkz46hTYwsiepwcZIkbLLpy3hYGIpePUyRg4gUdp5zr5KhLoidCICKQWLpId401rwMdno0uVwIDAQAB';
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
            $this->EditMoney($response['out_trade_no'], 'AutoAliwap', 1);
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
                $this->EditMoney($response['out_trade_no'], 'AutoAliwap', 0);
				
				echo "success";
				
                //exit("success");
            }
        //}else{
          //  exit('error:check sign Fail!');
       //}

    }
	

}