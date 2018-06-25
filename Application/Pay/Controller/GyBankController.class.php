<?php
namespace Pay\Controller;

use Org\Util\HttpClient;
use Org\Util\Rsa;

class GyBankController extends PayController{
    
    protected $_encrypt_key = 'lgbya';

    protected $_bank_code = array(
        // '银联通道'  =>  '1000',
        '建设银行'  =>  '1004',
        '农业银行'  =>  '1002',
        '工商银行'  =>  '1001',
        '中国银行'  =>  '1003',
        '浦发银行'  =>  '1014',
        '光大银行'  =>  '1008',
        '平安银行'  =>  '1011',
        '兴业银行'  =>  '1013',
        '邮政储蓄银行'    =>  '1006',
        '中信银行'  =>  '1007',
        '华夏银行'  =>  '1009',
        '招商银行'  =>  '1012',
        '广发银行'  =>  '1017',
        '北京银行'  =>  '1016',
        '上海银行'  =>  '1025',
        '民生银行'  =>  '1010',
        '交通银行'  =>  '1005',
        '北京农村商业银行'  =>  '1103',
    );


    protected $_card_type = array(
        '贷记卡' => '00',
        '借记卡' => '01',
        '准贷记卡' => '02',
    );

    public function __construct(){

        parent::__construct();

    }


    public function Pay($array){
        
        $orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');
        $notifyurl = $this->_site ."Pay_GyBank_notifyurl.html"; //异步通知
        $callbackurl = $this->_site . 'Pay_GyBank_callbackurl.html'; //跳转通知

        $parameter = array(
            'code' => 'GyBank',
            'title' => '国银网银支付',
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
        

        $encryp = $this->_encryptDecrypt(serialize($return), 'lgbya');

        $this->assign('url', $url);
        $this->assign('rpay_url', U('GyBank/Rpay'));
        $this->assign('orderid', $return['orderid']);
        $this->assign('money', sprintf('%.2f', $return['amount']/100 ));
        $this->assign('bank_array', $this->_bank_code);
        $this->assign('card_type_array', $this->_card_type);
        $this->assign('encryp', $encryp);
        $this->display('BankPay/carType_bankCode');    


    }




    public function Rpay(){

        //接收传输的数据
        $post_data = I('post.','');
        
        //将数据解密并反序列化
        $return = unserialize( $this->_encryptDecrypt($post_data['encryp'],'lgbya',1) );
        

        //检测数据是否正确
        $return || $this->error('传输数据不正确！');
        $post_data['url'] || $this->error('接口地址错误！');
        ($bank_code = $post_data['bankCode']) || $this->error('请选择银行');
        $channel_type = isMobile()?2:1;

        $arraystr = array(
            'gymchtId' => $return['mch_id'],
            'tradeSn' => $return['orderid'],
            'orderAmount' => $return['amount'],
            'goodsName' => $return['subject'], 
            'bankSegment' => $bank_code,
            'cardType' => '01',
            'notifyUrl' => $return['notifyurl'],
            'callbackUrl' => $return['callbackurl'],
            'channelType' => $channel_type,
        ); 

        $arraystr['sign'] = $this->_createSign($arraystr, $return['signkey']);
        
        list($return_code, $return_content) = $this->httpPostData($post_data['url'], http_build_query($arraystr));
    

        $respJson = json_decode($return_content,true);
        if($respJson['resultCode'] == '00000'){
            redirect($respJson['payUrl']);
            exit;
        }else{
            var_dump($respJson);
            $this->showmessage($respJson['message']);
        }
        
    }


    protected function _createSign($data, $key){
        $sign = '';
        ksort($data);
        foreach( $data as $k => $vo ){
            $sign .= $k . '=' . $vo . '&';
        }
        echo $sign  . 'key=' . $key ;
        return  strtoupper( md5($sign  . 'key=' . $key) );
    }

    protected function _encryptDecrypt($string, $key='',  $decrypt='0'){ 
        if($decrypt){ 
            $decrypted = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($string), MCRYPT_MODE_CBC, md5(md5($key))), "12");
            return $decrypted; 
        }else{ 
            $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $string, MCRYPT_MODE_CBC, md5(md5($key)))); 
            
            return $encrypted; 
        } 
    }


    protected function _createRandomStr( $length = 32 ) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ ){
            $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }



    protected function _getNeedBetween($search_str, $start_str,$end_str){
        $start_num =stripos($search_str,$start_str);
        $end_num =stripos($search_str,$end_str);
        if( $start_num === false || $end_num === false  || $start_num >= $end_num)
            return 0;

        $start_num += strlen( $start_str );
        $end_num -= $start_num ;
        return substr($search_str, $start_num, $end_num);
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
        curl_setopt ( $ch, CURLOPT_HTTPHEADER, array(
            // 'Content-Type: application/json;charset=utf-8',
            // 'Content-Type:application/x-www-form-urlencoded; charset=utf-8', 
          
        ) );
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
    
     /**
     * 建立请求，以表单HTML形式构造（默认）
     * @param $params array 请求参数数组
     * @return string 提交表单HTML文本
     */
    private function _buildRequestForm($url, $params) {
        $sHtml = "<form id='Form' name='Form' action='".$url."' method='POST'>";
        reset($params);
        while (list ($key, $val) = each ($params)) {
            $val = str_replace("'","&apos;",$val);
            $sHtml .= "<input type='hidden' name='".$key."' value='".$val."'/>\n";
        }
        //submit按钮控件请不要含有name属性
        $sHtml .= "<input type='submit' style='display:none;'/></form>\n";
        $sHtml = $sHtml."<script>document.getElementById('Form').submit();</script>\n";
        return $sHtml;
    }




    public function callbackurl(){
        
        $Order = M("Order");
        $pay_status = $Order->where("pay_orderid = '".$_REQUEST["tradeSn"]."'")->getField("pay_status");
        if($pay_status <> 0){
            $this->EditMoney($_REQUEST["orderid"], 'GyBank', 1);
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



        $channel_model = M('Channel');
        $channel_where = array('code'=>'GyBank');
        $signkey = $channel_model->where($channel_where)->getField('signkey');
  
        $respSign = $this->_createSign($data,$signkey);
        

        if($data['pay_result'] == 0 && $respSign == $sign){
            $this->EditMoney($data["tradeSn"], 'GyBank', 0);
            exit('success');
        }
        
        exit('fail');
    }
}