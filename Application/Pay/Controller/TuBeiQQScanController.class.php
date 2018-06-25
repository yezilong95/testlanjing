<?php
namespace Pay\Controller;

/**
 * 途贝-QQ扫码支付
 * Class TuBeiQQScanController
 * @package Pay\Controller
 */
class TuBeiQQScanController extends PayController
{
    private $CODE = 'TuBeiQQScan';
    private $TITLE = '途贝-QQ扫码';
    private $TRADE_TYPE = 'trade.qqpay.native';

    public function Pay($array)
    {
        addSyslog($array, 1, 10);

        $orderid = I('request.pay_orderid');
        $body = I('request.pay_productname');
        $notifyurl = $this->_site . 'Pay_' . $this->CODE . '_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_' . $this->TITLE . '_callbackurl.html'; //跳转通知
        $parameter = array(
            'code' => $this->CODE, // 通道名称
            'title' => $this->TITLE, //通道名称
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid'=>'',
            'out_trade_id' => $orderid, //外部订单号
            'channel'=>$array,
            'body'=>$body
        );
        $return = $this->orderadd($parameter);
        if ($return) {
        	if($return["amount"] > 5000){
        		exit('单笔支付限额5000元');
        	}

            //提交到通道接口的参数
            $data = array(
                'body'              => $body, // 订单描述，可以对交易或商品进行一个详细地描述，比如填写"购买商品2件共15.00元"
                'mch_id'            => $return['mch_id'], // 商户号
                'nonce_str'         => randpw(32, 'NUMBER'), //随机字符串，不长于 32 位
                'notify_url'        => $notifyurl, //接收支付结果异步通知回调地址，PC 网站必填
                'out_trade_no'      => $return["orderid"], // 商户订单号
                'spbill_create_ip'  => get_client_ip(), //调用微信支付 API 的机器 IP
                'total_fee'         => 100 * $return["amount"], // 订单总金额，整形，此处单位为分
                'trade_type'        => $this->TRADE_TYPE, //交易类型   trade.weixin.native 为微信H5支付，更多支付类型请查看技术文档编写
            );
            //签名
            $data['sign'] = $this->_createSign($data, $return['signkey']);
            //转换成XML格式
            $xml = arrayToXml($data);
            //提交到通道
            $resultxml = $this->_postXmlCurl($xml, $return['gateway']);
            //解析通道结果
            $result = xmlToArray($resultxml);

            //生成支付二维码
            if ($result) {
            	if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
            		//import("Vendor.phpqrcode.phpqrcode",'',".php");
            		//$url = urldecode($result['code_img_url']);
            		//$QR = "Uploads/codepay/". $return["orderid"] . ".png";//已经生成的原始二维码图
            		//\QRcode::png($url, $QR, "L", 20);
            		$this->assign("imgurl", $result['code_img_url']);
            		$this->assign('params',$return);
            		$this->assign('orderid',$return['orderid']);
            		$this->assign('money',$return['amount']);
            		$this->display("WeiXin/weixin");
            	} else {
            		$this->showmessage($result['return_msg']);
            	}
            }
        }
    }

    // 页面通知返回
    public function callbackurl()
    {
        $Order = M("Order");
        $pay_status = $Order->where("pay_orderid = '".$_REQUEST["orderid"]."'")->getField("pay_status");
        if($pay_status <> 0){
            $this->EditMoney($_REQUEST["orderid"], '', 1);
        }else{
            exit("error");
        }
    }

    // 服务器点对点返回
    public function notifyurl()
    {
        $data = $GLOBALS['HTTP_RAW_POST_DATA'];

        addSyslog($data, 1, 11);

        $data = xmlToArray($data);

        $sign = $data['sign'];
        unset($data['sign']);

        $Order = M("Order");
        $signkey = $Order->where("pay_orderid = '".$data['out_trade_no']."'")->getField("key");
        $respSign = strtoupper($this->_createSign($data, $signkey));

        if ($data['return_code'] == 'SUCCESS' && $data['result_code'] == 'SUCCESS' && $respSign == $sign) {
            addSyslog('商户成功提交订单', 1, 11);
            $this->EditMoney($data["out_trade_no"], $this->CODE, 0);
            exit('success');
        } else {
            exit('fail');
        }
    }

    /**
     * 生成签名
     * @param $data
     * @param $key
     * @return string
     */
    protected function _createSign($data, $key){
        $sign = '';
        ksort($data);
        foreach( $data as $k => $vo ){
            $sign .= $k . '=' . $vo . '&';
        }

        return  strtoupper( md5($sign  . 'key=' . $key) );
    }

    protected function _postXmlCurl($xml, $url, $second = 30)
    {
        $header[] = "Content-type: text/xml";//定义content-type为xml
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);//严格校验
        //设置header
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);//定义请求类型

        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            return false;
        }
    }
}
?>