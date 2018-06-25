<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-05-18
 * Time: 11:33
 */
namespace Pay\Controller;


class AliwapController extends PayController
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
        $notifyurl = $this->_site . 'Pay_Aliwap_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_Aliwap_callbackurl.html'; //返回通知
        $parameter = array(
            'code' => 'Aliwap', // 通道名称
            'title' => '支付宝官方（WAP）',
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
        $paymethod = 'alipayWap';
        switch ($paymethod)
        {
            case 'alipayPage':
                $this->alipayPage($return);
                break;
            case 'alipayWap':
                $this->alipayWap($return);
                break;
            case 'alipayPrecreate':
                $this->alipayPrecreate($return);
                break;
        }
    }

    protected function alipayPage($params)
    {
        $data = array(
            'out_trade_no'=>$params['orderid'],
            'total_amount'=>$params['amount'],
            'subject'=>$params['subject'],
            'product_code' => "FAST_INSTANT_TRADE_PAY"
        );
        $sysParams = json_encode($data,JSON_UNESCAPED_UNICODE);

        vendor('Alipay.aop.AopClient');
        vendor('Alipay.aop.SignData');
        vendor('Alipay.aop.request.AlipayTradePagePayRequest');
        $aop = new \AopClient();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $params['mch_id'];
        $aop->rsaPrivateKey = 'MIIEpAIBAAKCAQEAtvDQk9LG+LDpbk6xiecxqjPg9f4BhNYurJDFrVybxISAqCUM5/ofz/mIMTHQJ6mOdjtHG53ux6ADs7GZ+U7Vg5IZUkdkENxd7I0KIoXryQAQFDkDGrOPbaUoD9icpOzE7fm774J6bDh/0oFfB3OojDkjxYN6zObW/PIqU6DrL3S3Ka5b3qEH8NuuiN/f0SFBwE7gYpyPZ/WcuiHjvMkB0mbzGtWYrNXczU1gQ+9c+0v0pjXrddajC8/IlWDgDJCbIM+hgrWG7VieKnp2RnLdGH+Ok2yPdS9c3pEVxzBTcRkt8kvPo522FtoTPtC3cg0AsJsEvNA6A9/AGN2qHxzK2QIDAQABAoIBAQCvJO8MF4gXIIjb6ste09FgujpuSLj7jHMzE4et6jPXeWQTlyU8EuPSIXyaXK6EynhyCV6SuimZRUFGEIrxfOA+DunfNCpBWjkx9/X0B3MuBLlgIxUtwytWNgCc6y1NWMFRdP7Q14KNiaoWx3VLlReQ6EOvHam78mVx1gdf+Xgw/VQ3CCtA+MzH7OiiC9i/bx1EFewzOCNy3+iAXgJ0OXELe5mL4K1zN/XJKRMbc+b35SL6S54iFw/iplDhY74/J6odqvkXIoCRwteIXDD1Xl7mxv4gNwBd9REP7Z+IB3I9Y0I7JtbAiUl6dQPr/rHYsfvZwHfAQVORsbYM+Ehsn1uBAoGBAPJQUheIv7SnuHeBabkJ8+EdUay2gmsjpja6U6Tzbw00aSjnUZUETIpmTS2WwixvvoOHjLe+nOUB/g+ZDxmtSKOSXd7pKEgbH/R8x3n7HYJr6qI/VMzsYuRXOz03l7Q6HA/c8YgC26UTrtFha4m0eIX7rQKOo9U/wxEbBikMD16tAoGBAMFGAMQeX+olv4+esxzXh97gmfks+2ZRrdiydBz2N14fwFb7bhtnn/hoYFb0TesSDCxp5zekVo5PlFgLWQ4jEVqqAUDP8uwG/Lq8iRHUp7BeBkOMHaIBDprH51wcrEMmDvk0v576B6Xe6VR3QJCUJLtq8tVL7+18Lu5BHu6xr75dAoGBAKN3tCnUQx/olfVpBJ2kLTaMxPCzH0CQCC2bfZol76EE3nyNsOfKwqgLY72BmvTHXcr1wuSiXs3PjkmPhDRaRkqzD0i2GkqqoeAZ3ahY1AuMKfnSp66nOf+5KWme+2TGXvAEqZyL8QloQeNWyWlYqoYYxxqWh8fw//OmO32teSDxAoGAGCThlZ5hxwNeMdfWckTugUY3lewrn7WWbRql7LRJaGW5BmS0dZH1ZvfLCTHNxg7kHGxCaS4Lbg28717DikOROG1CaNFRfHDHA6Dn0qVpKVwlliybyxAsveM5IMWoM18+wZz4TyjW6b62EUowc58+E3ehzEmHOHip+DOEZLcnyDUCgYAqKOvH/UkgdNTR5NoQZmLSXuEuYimBiR6sNtV4ggdlAT9/e7ON+PHCg63nW1sULPZMkvtnGgkUg8p6/KCkPJopHzmt+TXqJBdMBI3rHW70QlOB5O0xFEbpYwF6mQ42uVLZY3GTD2qr3ksp/ts/cgjfgtpbfFTH7hEh42ftApD6IQ==';
        $aop->alipayrsaPublicKey= $params['signkey'];
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='UTF-8';
        $aop->format='json';
        $aop->debugInfo=true;
        $request = new \AlipayTradePagePayRequest();
        $request->setBizContent($sysParams);
        $request->setNotifyUrl($params['notifyurl']);
        $request->setReturnUrl($params['callbackurl']);
        $result = $aop->pageExecute($request,'post');
        echo $result;
    }
    protected function alipayWap($params){

        $data = array(
            'out_trade_no'=>$params['orderid'],
            'total_amount'=>$params['amount'],
            'subject'=>$params['subject'],
            'product_code' => "QUICK_WAP_WAY"
        );
        $sysParams = json_encode($data,JSON_UNESCAPED_UNICODE);

        vendor('Alipay.aop.AopClient');
        vendor('Alipay.aop.SignData');
        vendor('Alipay.aop.request.AlipayTradeWapPayRequest');
        $aop = new \AopClient();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $params['mch_id'];
        $aop->rsaPrivateKey = 'MIIEogIBAAKCAQEAoAZmqD+4ZQFkhLHV6UxBiO1V8eza8SQdk0neju36AvetNvCy/bGZt0S2U13NZlVQ/lNm9F4lF5jm+j+HbTziTn0/zHLx/GrG1Gg/dNQWBKN70szHPWukYTyDScb86d1M94lCYqoUrRN+wb5MX5WGF3f+fYkHeJPjznyKNuQu5p3pwuKc7Mw2Bv53Pg9Xhireue/MkD6qt8i0uwBxP8N/tJFHNYmaqBBHXltOHr21u5/wAw5l90/B3hceN5PIy6km4qwAiHWju8vAtjxMKnIvf1vxJwml1UV/DWDFqMumMcTwvlqH8r+MxZ4HDuxZPg39mQqmkit4DeGNPPv89YTf9wIDAQABAoIBAG1sExQnBiJi8mXd6GRvlTxY9XTIvek4BdUqJLndNiCDVXkE24+nunFb1cRplsLLwec2BmdAXOKm1gH2INdsU17SPCbDzn4IUU76vbFYOHoRg+Dfz7lK+orWwvtWyqfrf+6fd7pZjGvQsdMvGbAeYSN5FJaodWhYz+PouuXP39Ymvduqe46ae/fOthQd+rdGcZ4KFLk4PLa3HPWfxSXMCN5rq3bYBpijyfe4Zs1MaWQe4+IFuP7C+c8VcB/AoWWitnUDIldbI87Y/RicXKw1vTbguG3QgXv6wFiDGpKdOl+7Y5NwofFYHNhcXpX6XXf6/MXYf7fbPnhu5yWe2lbhHVECgYEAy8FmkhZm/fw3cj+SLkDdNb/lS7e9glw1EUQNzO+zLtjrEvlOZsi3/b/gC7CSHrG+Wc14YFAi72DISXr7wX5o49rSdCfJ+ov5QWl2L2dFhS1jdTpPZb9fbu423RykUGyv/ut77TM+KIXpqajBfPI8nVs/sDiYiXIZ6v6O01a9wm8CgYEAyQ6DWmziBnAMCM+lILHRzKqdR3pQn6BRWGhtiIAckkPWuk7HAVidta0deOheLVW1ghKjb2to5Jkh+t+EPTdNb0PedwPq5hPpWRz/HfbpURRn8QCBSbs86QL2rsnohla+Zvqw6x0LL1AcKGsOxlLLzUSRAptvX/LrCY3B935SXvkCgYA+UsPa/3s1SQZ0rbk66KBBJpcuV99hlm6s/1HxU4hNVhBhV1yB1/dfaGdRbArl1JxSv6SIYTquWb4pq7KB9vaCa5Zf7SO8vT/aoDWEPmRnXgDEBLfweV7pgXfKnk4sQ3J52PGpFjl3D67vNC0q2LVttFGAx27w67O9y2tIfYnBhQKBgCNEkHYEM4G9ld7Vzbl2d77XDs/C/PGDRcGkT9Jp5pkhnUEBIJdz2/ZKb6kN2bdKGZS/gDvDM5sl4XgITUIPuV2TIiruXP4O8BfZpkazUSoP0kvMMuGkHoMhKfRvJoKqJKwbvX0Akz2xZ78PSIAxdd6D8IvsiBTkn6YFX8jyN7DBAoGAKiOcAK1/0Se+Llxskb2z3+xOfRDwhN9nU87IiRmRD8RjI1IXGjoIUZhuaGGpLJv72r+r/hf3Dh24yLjSebpMZ0cpJWHh5SZnaeIVDoWCX2hdeSXybG9Ys3vwN5HonQOfv2Bxhw4J+9WFGSobdasFZRLF1HIJ/WiwZYwuZ+Z3hNE=';
        $aop->alipayrsaPublicKey= $params['signkey'];
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

    protected function alipayPrecreate($params)
    {
        //组装系统参数
        $data = array(
            'out_trade_no'=>$params['orderid'],
            'total_amount'=>$params['amount'],
            'subject'=>$params['subject'],
        );
        $sysParams = json_encode($data,JSON_UNESCAPED_UNICODE);

        vendor('Alipay.aop.AopClient');
        vendor('Alipay.aop.SignData');
        vendor('Alipay.aop.request.AlipayTradePrecreateRequest');
        $aop = new \AopClient();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $params['mch_id'];
        $aop->rsaPrivateKey = 'MIIEpAIBAAKCAQEAtvDQk9LG+LDpbk6xiecxqjPg9f4BhNYurJDFrVybxISAqCUM5/ofz/mIMTHQJ6mOdjtHG53ux6ADs7GZ+U7Vg5IZUkdkENxd7I0KIoXryQAQFDkDGrOPbaUoD9icpOzE7fm774J6bDh/0oFfB3OojDkjxYN6zObW/PIqU6DrL3S3Ka5b3qEH8NuuiN/f0SFBwE7gYpyPZ/WcuiHjvMkB0mbzGtWYrNXczU1gQ+9c+0v0pjXrddajC8/IlWDgDJCbIM+hgrWG7VieKnp2RnLdGH+Ok2yPdS9c3pEVxzBTcRkt8kvPo522FtoTPtC3cg0AsJsEvNA6A9/AGN2qHxzK2QIDAQABAoIBAQCvJO8MF4gXIIjb6ste09FgujpuSLj7jHMzE4et6jPXeWQTlyU8EuPSIXyaXK6EynhyCV6SuimZRUFGEIrxfOA+DunfNCpBWjkx9/X0B3MuBLlgIxUtwytWNgCc6y1NWMFRdP7Q14KNiaoWx3VLlReQ6EOvHam78mVx1gdf+Xgw/VQ3CCtA+MzH7OiiC9i/bx1EFewzOCNy3+iAXgJ0OXELe5mL4K1zN/XJKRMbc+b35SL6S54iFw/iplDhY74/J6odqvkXIoCRwteIXDD1Xl7mxv4gNwBd9REP7Z+IB3I9Y0I7JtbAiUl6dQPr/rHYsfvZwHfAQVORsbYM+Ehsn1uBAoGBAPJQUheIv7SnuHeBabkJ8+EdUay2gmsjpja6U6Tzbw00aSjnUZUETIpmTS2WwixvvoOHjLe+nOUB/g+ZDxmtSKOSXd7pKEgbH/R8x3n7HYJr6qI/VMzsYuRXOz03l7Q6HA/c8YgC26UTrtFha4m0eIX7rQKOo9U/wxEbBikMD16tAoGBAMFGAMQeX+olv4+esxzXh97gmfks+2ZRrdiydBz2N14fwFb7bhtnn/hoYFb0TesSDCxp5zekVo5PlFgLWQ4jEVqqAUDP8uwG/Lq8iRHUp7BeBkOMHaIBDprH51wcrEMmDvk0v576B6Xe6VR3QJCUJLtq8tVL7+18Lu5BHu6xr75dAoGBAKN3tCnUQx/olfVpBJ2kLTaMxPCzH0CQCC2bfZol76EE3nyNsOfKwqgLY72BmvTHXcr1wuSiXs3PjkmPhDRaRkqzD0i2GkqqoeAZ3ahY1AuMKfnSp66nOf+5KWme+2TGXvAEqZyL8QloQeNWyWlYqoYYxxqWh8fw//OmO32teSDxAoGAGCThlZ5hxwNeMdfWckTugUY3lewrn7WWbRql7LRJaGW5BmS0dZH1ZvfLCTHNxg7kHGxCaS4Lbg28717DikOROG1CaNFRfHDHA6Dn0qVpKVwlliybyxAsveM5IMWoM18+wZz4TyjW6b62EUowc58+E3ehzEmHOHip+DOEZLcnyDUCgYAqKOvH/UkgdNTR5NoQZmLSXuEuYimBiR6sNtV4ggdlAT9/e7ON+PHCg63nW1sULPZMkvtnGgkUg8p6/KCkPJopHzmt+TXqJBdMBI3rHW70QlOB5O0xFEbpYwF6mQ42uVLZY3GTD2qr3ksp/ts/cgjfgtpbfFTH7hEh42ftApD6IQ==';
        $aop->alipayrsaPublicKey= $params['signkey'];
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='UTF-8';
        $aop->format='json';
        $request = new \AlipayTradePrecreateRequest ();
        $request->setBizContent($sysParams);
        $request->setNotifyUrl($params['notifyurl']);
        $result = $aop->execute ( $request);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;

        if(!empty($resultCode)&&$resultCode == 10000){
            import("Vendor.phpqrcode.phpqrcode",'',".php");
            $url = urldecode($result->$responseNode->qr_code);
            $QR = "Uploads/codepay/". $params["orderid"] . ".png";//已经生成的原始二维码图
            $delqr = $QR;
            \QRcode::png($url, $QR, "L", 20);
            //$this->assign("imgurl", $this->_site.$QR);
            //$this->assign("ddh", $result->$responseNode->out_trade_no);
            //$this->assign("money", $params["amount"] / 100);
            //$this->display("WeiXin/Pay");
            echo json_encode(array('status'=>1,'codeurl'=>$this->_site.$QR));
            exit();
        } else {
            echo "失败";
        }
        exit();
    }
    //同步通知
    public function callbackurl()
    {
        $response = $_GET;
        $sign = $response['sign'];
        $sign_type = $response['sign_type'];
        unset($response['sign']);
        unset($response['sign_type']);
        $publiKey =  $this->getSignkey('Aliwap', $response["app_id"]); // 密钥

        ksort($response);
        $signData = '';
        foreach ($response as $key=>$val){
            $signData .= $key .'='.$val."&";
        }
        $signData = trim($signData,'&');
        //$checkResult = $aop->verify($signData,$sign,$publiKey,$sign_type);
        $res = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($publiKey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
        $result = (bool)openssl_verify($signData, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);

        if($result){
            $this->EditMoney($response['out_trade_no'], 'Aliwap', 1);
        }else{
            exit('error:check sign Fail!');
        }

    }

    //异步通知
    public function notifyurl()
    {
        $response = $_POST;
        $sign = $response['sign'];
        $sign_type = $response['sign_type'];
        unset($response['sign']);
        unset($response['sign_type']);
        $publiKey =  $this->getSignkey('Aliwap', $response["app_id"]); // 密钥

        ksort($response);
        $signData = '';
        foreach ($response as $key=>$val){
            $signData .= $key .'='.$val."&";
        }
        $signData = trim($signData,'&');
        //$checkResult = $aop->verify($signData,$sign,$publiKey,$sign_type);
        $res = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($publiKey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
        $result = (bool)openssl_verify($signData, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);

        if($result){
            if($response['trade_status'] == 'TRADE_SUCCESS' || $response['trade_status'] == 'TRADE_FINISHED'){
                $this->EditMoney($response['out_trade_no'], 'Aliwap', 0);
                exit("success");
            }
        }else{
            exit('error:check sign Fail!');
       }

    }

    /** *利用google api生成二维码图片
     * $content：二维码内容参数
     * $size：生成二维码的尺寸，宽度和高度的值
     * $lev：可选参数，纠错等级
     * $margin：生成的二维码离边框的距离
     */
    function create_erweima($content, $size = '200', $lev = 'L', $margin= '0') {
        $content = urlencode($content);
        $image = 'http://chart.apis.google.com/chart?chs='.$size.'x'.$size.'&amp;cht=qr&chld='.$lev.'|'.$margin.'&amp;chl='.$content;
        return $image;
    }
}