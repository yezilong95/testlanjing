<?php
namespace Pay\Controller;

class MobaoKjController extends PayController
{


    public function Pay($array)
    {

        $orderid = I('request.pay_orderid');
        $body = I('request.pay_productname');

        
        $parameter = array(
            'code' => 'MobaoKj', // 通道名称
            'title' => '摩宝快捷', //通道名称
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid'=>'',
            'out_trade_id' => $orderid, //外部订单号
            'channel'=>$array,
            'body'=>$body
        );
        $return = $this->orderadd($parameter);
        
        //跳转页面，优先取数据库中的跳转页面
        $return["notifyurl"] || $return["notifyurl"] = $this->_site . 'Pay_MobaoKj_notifyurl.html';
        
        $return['callbackurl'] || $return['callbackurl'] = $this->_site . 'Pay_MobaoKj_callbackurl.html';

        $arraystr = [
                'versionId'     => '001',
                'businessType'      => '1100',
                'merId'     => $return['mch_id'],
                'orderId'       => $return['orderid'],
                'transDate'     => date('YmdHis', time()),
                'transAmount'       => sprintf('%.2f', $return['amount']),
                'transCurrency'     => '156',
                'transChanlName'        => 'UNIONPAY',
                'pageNotifyUrl'     => $return['callbackurl'],
                'backNotifyUrl'     => $return['notifyurl'],
                'dev'       => '支付产品',
            ];
    
        $arraystr['signData'] = $this->_createSign($arraystr, $return['signkey']);
        echo createForm($return['gateway'], $arraystr);
    }

    public function _createSign($array, $key){
        $string = '';
        foreach($array as $k => $v){
            
                $string .= $k . '=' . $v . '&';
            
        }

        $string = rtrim($string, '&');
        return strtoupper(md5($string . $key));
    }

    // 页面通知返回
    public function callbackurl()
    {
        $Order = M("Order");
        $pay_status = $Order->where("pay_orderid = '".$_REQUEST["orderId"]."'")->getField("pay_status");
        if($pay_status <> 0){
            $this->EditMoney($_REQUEST["orderId"], '', 1);
        }else{
            exit("error");
        }
    }

    // 服务器点对点返回
    public function notifyurl()
    {
 
        $data = I('request.','');
        if($data['payStatus'] == '00'){
            $sign = $data['signData'];
            unset($data['signData']);
            $key = getKey($data['orderId']);
            $newSign = $this->_createSign($data, $key);
            if($newSign == $sign){
                $this->EditMoney($data["orderId"], '', 0);
                echo "OK";
            }
        }
    }
    
   
}
?>