<?php
namespace Payment\Controller;
use Common\Model\GDaiFuLogModel;
/**
 * 环迅代付
 *  代付要查询，没有异步通知, 异步通知只通知统一下单的那些交易
 * Class XinJieController
 * @package Payment\Controller
 */
class HuanXunController extends PaymentController
{
    private $TITLE = '环迅代付';
    private $KEY = '4kBxdVPRdSoZYUOCuEZtAGUM';
    private $IV = 'e9qKcECN';
    private $YUMING = 'http://www.qiqi95.com';

    /**
     * 易收付转账接口
     */
    public function PaymentExec($wttlList, $pfaList)
    {

        $logTitle = time() . '-' . $this->TITLE . '-转账-';

        $merBillNo = $wttlList['orderid']; //传给代付系统的订单号
        $merBillNoZz = 'Zz'.$wttlList['orderid']; //传给代付系统的订单号
        $transferAmount = $wttlList['money']+1;
        $bankName = $wttlList['bankname'];
        $bankCardNumber = $wttlList['banknumber'];
        $code = $wttlList['code']; //代付通道编码
        $merchantId = $wttlList['userid']; //商户号
        $bankCardAccountName = $wttlList['bankfullname'];
        $branchBankName = $wttlList['bankzhiname'];
        $province = $wttlList['sheng'];
        $city = $wttlList['shi'];
        $batch_no = $wttlList['batch_no']; //打款批次号, 易宝要求必须唯一 必须为 15 位的数字串
        $signKey = $pfaList['signkey'];
        $gateway = $pfaList['exec_gateway'];
        $merchantId = $wttlList['userid'];
        $argMerCode = $pfaList['mch_id'];
        $merAcctNo = $pfaList['appid']; //环迅账户号

        session('merBillNo',$merBillNo);
        session('bankCard',$bankCardNumber);

        $transferType = '2';//转账类型
        $customerCode = ($merchantId+10000).substr($bankCardNumber,-4);//客户号
        $collectionItemName ='客户收款' ;//付款项目

        $reqDate = date("Y-m-d H:i:s");
        $reqIp = $_SERVER["REMOTE_ADDR"];


        $body="<body><merBillNo>$merBillNoZz</merBillNo><transferType>$transferType</transferType><merAcctNo>$merAcctNo</merAcctNo><customerCode>$customerCode</customerCode><transferAmount>$transferAmount</transferAmount><collectionItemName>$collectionItemName</collectionItemName></body>";


        $head ="<head><version>V1.0.1</version><reqIp>$reqIp</reqIp><reqDate>$reqDate</reqDate><signature>".md5($body.$signKey)."</signature></head>";

        $transferReqXml="<?xml version=\"1.0\" encoding=\"utf-8\"?><transferReqXml>". $head.$body."</transferReqXml>";

        $transferReq=$this->encrypt($transferReqXml);

        $ipsRequest = "<ipsRequest><argMerCode>$argMerCode</argMerCode><arg3DesXmlPara>".$transferReq."</arg3DesXmlPara></ipsRequest>";

        //添加日记
        $logTitle = $this->TITLE . '-转账-';
        $log = [
            'merchantId' => $merchantId,
            'code' => $code,
            'channelMerchantId' => $argMerCode,
            'orderId' => $merBillNo,
            'type' => GDaiFuLogModel::TYPE_SUBMIT,
            'level' => GDaiFuLogModel::LEVEL_INFO,
            'msg' => '',
        ];
        $log['msg'] = $logTitle . '提交字符串: ' . $transferReqXml;
        $this->logModel->add($log);

        $post_data['ipsRequest']  = $ipsRequest;
        $responsexml = $this->request_post($gateway, $post_data);

        $log['msg'] = $logTitle . '返回字符串: ' . $responsexml;
        $this->logModel->add($log);
        $data = $this->xmlToarr($responsexml);
        if($data['argMerCode']=='210754' && $data['rspCode']=='M000000'){
            $return = ['status'=>1]; //申请成功
        }else{
            $return = ['status'=>3, 'msg'=>$data['rspMsg']];
        }
        return $return;
    }

    /**
     * 易收付提现输入客户号接口
     */

    public function tixianOne(){
        $this->display();
    }
    /**
     * 易收付提现接口
     */
    public function tixian(){
        header("Content-Type:text/html;charset=UTF-8");
        //$dfOrderid = 'TX'.time();
        $dfOrderid = session('merBillNo');
        //$bankCard = session('bankCard');
        if(IS_POST){
            $customerCode = I("post.customerCode",'');
            if(strlen($customerCode)<9){
                die('客户号不正确！');
            }elseif ($dfOrderid==''){
                die('订单号为空');
            }
        }else{
            die('非法提交！');
        }

        $pageUrl = $this->YUMING.'/Payment_HuanXun_pageUrl.html';
        $s2sUrl  = $this->YUMING.'/Payment_HuanXun_s2sUrl.html';
        $reqDate = date("Y-m-d H:i:s");
        $reqIp = $_SERVER["REMOTE_ADDR"];
        $signKey = 'UCHpWKnpdb1MjljwVVltZf8d9mUn217EyWdfVnKcVF4pGmwF5fw0M9DcJrR9uVvABE0O1EB8RvDhAnWt5gJk2BExvbS6dGF0rUvE4OpufkrG1WZfSfje55NEE3gvpF0T';
        $mch_id = '210754';

        $body="<body><merBillNo>$dfOrderid</merBillNo><customerCode>$customerCode</customerCode><pageUrl>$pageUrl</pageUrl><s2sUrl>$s2sUrl</s2sUrl><bankCard></bankCard><bankCode></bankCode></body>";


        $head ="<head><version>V1.0.1</version><reqIp>$reqIp</reqIp><reqDate>$reqDate</reqDate><signature>".md5($body.$signKey)."</signature></head>";

        $withdrawalReqXml="<?xml version=\"1.0\" encoding=\"utf-8\"?><withdrawalReqXml>". $head.$body."</withdrawalReqXml>";

        //添加日记
        $logTitle = $this->TITLE . '-用户提现-';
        $log = [
            'merchantId' => null,
            'code' => null,
            'channelMerchantId' => null,
            'orderId' => $dfOrderid,
            'type' => GDaiFuLogModel::TYPE_SUBMIT,
            'level' => GDaiFuLogModel::LEVEL_INFO,
            'msg' => '',
        ];
        $log['msg'] = $logTitle . '提交字符串: ' . $withdrawalReqXml;
        $this->logModel->add($log);

        $transferReq=$this->encrypt($withdrawalReqXml);
        $ipsRequest = "<ipsRequest><argMerCode>$mch_id</argMerCode><arg3DesXmlPara>".$transferReq."</arg3DesXmlPara></ipsRequest>";
        $this->assign('code',$ipsRequest);
        $this->assign('orderid',$dfOrderid);
        $this->display();

    }
    public function test(){
        header("Content-Type:text/html;charset=UTF-8");
        $data = 'TdsOcZwbA5POYw+hxMYD832UQ8EzqldjNUFM19V6DGqXQ0bWmoNRXb+9TR9Cvm/l4I+qIZiP41V2OThzNc4U+3gFbAhg08f82SwDEi7MrFcoccWFlRyoqpLs3R5wqXSlihvFQ/ySs5t+NhJm3zpAjf4l2ELvbVqaaJvel61TQyzdlDcLMozBdeIILzmVnlV8gip+mvatLZ7KaLQFKwsl58eOfK/6Lj0/qVPvpP6886PbSeGxbAKoYupLiauJD0yr77OytJWi6kgpsX3K3T4D2AwDi3Rw3qlifbhKKBKHgEVgMCyPGMMuQ42VqIUW5DK4jBW4udTeGkKrRithrGr7q6R41gkOMBylyUkKS/0Ztgi4EyXo9XHA33ljDN36gOsXoCKwpWlf1VE4FUVQauQoK+bthzdIHY6qafNw/IhlQMC8pbgVQqzEYSqbreP0p8BpQBN6KWCWEb9IVBcmKkS3MvVPyQHxl9bm';
        $dereturn = $this->decrypt($data);
        dump($dereturn);
    }

    /**
     * 易收付免密提现接口
     */
    public function mmtixian(){
        header("Content-Type:text/html;charset=UTF-8");
        $dfOrderid = 'TX'.time();
        //$dfOrderid = session('merBillNo');
        if($dfOrderid==''){
            die('订单号为空');
        }
        $pageUrl = $this->YUMING.'/Payment_HuanXun_pageUrl.html';
        $s2sUrl  = $this->YUMING.'/Payment_HuanXun_s2sUrl.html';
        $reqDate = date("Y-m-d H:i:s");
        $reqIp = $_SERVER["REMOTE_ADDR"];
        $merAcctNo = '2107540011';
        $signKey = 'UCHpWKnpdb1MjljwVVltZf8d9mUn217EyWdfVnKcVF4pGmwF5fw0M9DcJrR9uVvABE0O1EB8RvDhAnWt5gJk2BExvbS6dGF0rUvE4OpufkrG1WZfSfje55NEE3gvpF0T';
        $mch_id = '210754';

        $body="<body><merBillNo>$dfOrderid</merBillNo><merAcctNo>$merAcctNo</merAcctNo><amount>13</amount><realName>尚军邦</realName><customerCode>10002</customerCode><s2sUrl>$s2sUrl</s2sUrl></body>";


        $head ="<head><version>V1.0.1</version><reqIp>$reqIp</reqIp><reqDate>$reqDate</reqDate><signature>".md5($body.$signKey)."</signature></head>";

        $withdrawalReqXml="<?xml version=\"1.0\" encoding=\"utf-8\"?><easyWithdrawalReqXml>". $head.$body."</easyWithdrawalReqXml>";

        //添加日记
        $logTitle = $this->TITLE . '-用户提现-';
        $log = [
            'merchantId' => null,
            'code' => null,
            'channelMerchantId' => null,
            'orderId' => $dfOrderid,
            'type' => GDaiFuLogModel::TYPE_SUBMIT,
            'level' => GDaiFuLogModel::LEVEL_INFO,
            'msg' => '',
        ];
        $log['msg'] = $logTitle . '提交字符串: ' . $withdrawalReqXml;
        $this->logModel->add($log);

        $transferReq=$this->encrypt($withdrawalReqXml);
        $ipsRequest = "<ipsRequest><argMerCode>$mch_id</argMerCode><arg3DesXmlPara>".$transferReq."</arg3DesXmlPara></ipsRequest>";
        $this->assign('code',$ipsRequest);
        $this->assign('orderid',$dfOrderid);
        $this->display();

    }

    /**
     * 银行卡管理接口
     */
    public function bangCard(){
        header("Content-Type:text/html;charset=UTF-8");
        $dfOrderid = 'BK'.time();
        //$dfOrderid = session('merBillNo');
        if($dfOrderid==''){
            die('订单号为空');
        }
        $pageUrl = $this->YUMING.'/Payment_HuanXun_pageUrl.html';
        $s2sUrl  = $this->YUMING.'/Payment_HuanXun_s2sUrl.html';
        $reqDate = date("Y-m-d H:i:s");
        $reqIp = $_SERVER["REMOTE_ADDR"];
        $merAcctNo = '2107540011';
        $signKey = 'UCHpWKnpdb1MjljwVVltZf8d9mUn217EyWdfVnKcVF4pGmwF5fw0M9DcJrR9uVvABE0O1EB8RvDhAnWt5gJk2BExvbS6dGF0rUvE4OpufkrG1WZfSfje55NEE3gvpF0T';
        $mch_id = '210754';
        $gateway = 'https://ebp.ips.com.cn/fpms-access/action/withoutCode/bankCard.html';

        $body="<body><customerCode>10002</customerCode><merAcctNo>$merAcctNo</merAcctNo><pageUrl>$pageUrl</pageUrl><s2sUrl>$s2sUrl</s2sUrl></body>";


        $head ="<head><version>V1.0.1</version><reqIp>$reqIp</reqIp><reqDate>$reqDate</reqDate><signature>".md5($body.$signKey)."</signature></head>";

        $withdrawalReqXml="<?xml version=\"1.0\" encoding=\"utf-8\"?><withoutBankReqXml>". $head.$body."</withoutBankReqXml>";

        //添加日记
        $logTitle = $this->TITLE . '-绑卡-';
        $log = [
            'merchantId' => null,
            'code' => null,
            'channelMerchantId' => null,
            'orderId' => $dfOrderid,
            'type' => GDaiFuLogModel::TYPE_SUBMIT,
            'level' => GDaiFuLogModel::LEVEL_INFO,
            'msg' => '',
        ];
        $log['msg'] = $logTitle . '提交字符串: ' . $withdrawalReqXml;
        $this->logModel->add($log);

        $transferReq=$this->encrypt($withdrawalReqXml);
        $ipsRequest = "<ipsRequest><argMerCode>$mch_id</argMerCode><arg3DesXmlPara>".$transferReq."</arg3DesXmlPara></ipsRequest>";
        $this->assign('code',$ipsRequest);
        $this->assign('orderid',$dfOrderid);
        $this->display();
    }

    /**
     * 易收付测试查询接口
     */
    public function query(){
        header("Content-Type:text/html;charset=UTF-8");
        $dfOrderid = 'LJCX'.time().rand(1000,9999);
        $pageUrl = $this->YUMING.'/Payment_HuanXun_pageUrl.html';
        $s2sUrl  = $this->YUMING.'/Payment_HuanXun_s2sUrl.html';
        $reqDate = date("Y-m-d H:i:s");
        $reqIp = $_SERVER["REMOTE_ADDR"];
        $gateway = 'https://ebp.ips.com.cn/fpms-access/action/trade/queryOrdersList';
        $signKey = 'UCHpWKnpdb1MjljwVVltZf8d9mUn217EyWdfVnKcVF4pGmwF5fw0M9DcJrR9uVvABE0O1EB8RvDhAnWt5gJk2BExvbS6dGF0rUvE4OpufkrG1WZfSfje55NEE3gvpF0T';
        $mch_id = '210754';
        $merAcctNo = '2107540011';
//meivillno  LJTX15288097966986
        $body="<body><merAcctNo>$merAcctNo</merAcctNo><customerCode>10005</customerCode><ordersType>4</ordersType><merBillNo></merBillNo><ipsBillNo></ipsBillNo><startTime></startTime><endTime></endTime><currrentPage></currrentPage><pageSize></pageSize></body>";


        $head ="<head><version>V1.0.1</version><reqIp>$reqIp</reqIp><reqDate>$reqDate</reqDate><signature>".md5($body.$signKey)."</signature></head>";

        $withdrawalReqXml="<?xml version=\"1.0\" encoding=\"utf-8\"?><queryOrderReqXml>". $head.$body."</queryOrderReqXml>";

        $transferReq=$this->encrypt($withdrawalReqXml);
        $ipsRequest = "<ipsRequest><argMerCode>$mch_id</argMerCode><arg3DesXmlPara>".$transferReq."</arg3DesXmlPara></ipsRequest>";

        $post_data['ipsRequest']  = $ipsRequest;
        $responsexml = $this->request_post($gateway, $post_data);
        $data = $this->xml_to_array($responsexml);
        $dereturn = $this->decrypt($data['3']['val']);
        $retArr = $data = $this->xmlToarr($dereturn);
        dump($retArr);
        $retmerBillNo = $retArr['body']['orderDetails']['orderDetail']['merBillNo'];//平台订单号
        $retipsBillNo = $retArr['body']['orderDetails']['orderDetail']['ipsBillNo'];//环迅订单号
        $retorderAmount = $retArr['body']['orderDetails']['orderDetail']['orderAmount'];//订单金额
        $retorderState = $retArr['body']['orderDetails']['orderDetail']['orderState'];//订单状态 8、处理中，9、失败，10、成功，4、退票,
        dump($retmerBillNo);
        dump($retipsBillNo);
        dump($retorderAmount);
        dump($retorderState);
    }

    /**
     * 易收付平台查询接口
     */
    public function PaymentQuery($wttlList, $pfaList)
    {

        $logTitle = time() . '-' . $this->TITLE . '-查询-';

        $dfOrderid = $wttlList['orderid']; //传给代付系统的订单号
        $amount = $wttlList['money'];
        $bankName = $wttlList['bankname'];
        $bankCardNumber = $wttlList['banknumber'];
        $bankCardAccountName = $wttlList['bankfullname'];
        $branchBankName = $wttlList['bankzhiname'];
        $province = $wttlList['sheng'];
        $city = $wttlList['shi'];
        $platform_order_no = $wttlList['platform_order_no']; //交易平台(上游渠道)生成的订单号 platform_order_no
        $batch_no = $wttlList['batch_no']; //打款批次号, 易宝要求必须唯一 必须为 15 位的数字串
        $signKey = $pfaList['signkey'];
        $gateway = $pfaList['query_gateway'];
        $mch_id = $pfaList['mch_id'];
        $merAcctNo = $pfaList['appid']; //大商户号或者应用号
        $reqDate = date("Y-m-d H:i:s");
        $reqIp = $_SERVER["REMOTE_ADDR"];
        $merchantId = $wttlList['userid'];
        $customerCode = ($merchantId+10000).substr($bankCardNumber,-4);//客户号



        $body="<body><merAcctNo>$merAcctNo</merAcctNo><customerCode>$customerCode</customerCode><ordersType>4</ordersType><merBillNo>$dfOrderid</merBillNo><ipsBillNo></ipsBillNo><startTime></startTime><endTime></endTime><currrentPage></currrentPage><pageSize></pageSize></body>";


        $head ="<head><version>V1.0.1</version><reqIp>$reqIp</reqIp><reqDate>$reqDate</reqDate><signature>".md5($body.$signKey)."</signature></head>";

        $withdrawalReqXml="<?xml version=\"1.0\" encoding=\"utf-8\"?><queryOrderReqXml>". $head.$body."</queryOrderReqXml>";

        $transferReq=$this->encrypt($withdrawalReqXml);
        $ipsRequest = "<ipsRequest><argMerCode>$mch_id</argMerCode><arg3DesXmlPara>".$transferReq."</arg3DesXmlPara></ipsRequest>";

        //添加日记
        $logTitle = $this->TITLE . '-代付查询-';
        $log = [
            'merchantId' => null,
            'code' => null,
            'channelMerchantId' => null,
            'orderId' => $dfOrderid,
            'type' => GDaiFuLogModel::TYPE_SUBMIT,
            'level' => GDaiFuLogModel::LEVEL_INFO,
            'msg' => '',
        ];
        $log['msg'] = $logTitle . '提交字符串: ' . $withdrawalReqXml;
        $this->logModel->add($log);

        $post_data['ipsRequest']  = $ipsRequest;
        $responsexml = $this->request_post($gateway, $post_data);
        $data = $this->xml_to_array($responsexml);
        $dereturn = $this->decrypt($data['3']['val']);
        $retArr = $data = $this->xmlToarr($dereturn);

        $log['msg'] = $logTitle . '返回字符串: ' . $dereturn;
        $this->logModel->add($log);

        $retmerBillNo = $retArr['body']['orderDetails']['orderDetail']['merBillNo'];//平台订单号
        $retipsBillNo = $retArr['body']['orderDetails']['orderDetail']['ipsBillNo'];//环迅订单号
        $retorderAmount = $retArr['body']['orderDetails']['orderDetail']['orderAmount'];//订单金额
        $retorderState = $retArr['body']['orderDetails']['orderDetail']['orderState'];//订单状态 8、处理中，9、失败，10、成功，4、退票,

        if($retorderAmount == $amount+1){

            if($retorderState == '10'){
                $return = ['status'=>2, 'msg'=>'代付成功'];
            }elseif ($retorderState == '8'){
                $return = ['status'=>3, 'msg'=>'处理中'];
            }elseif ($retorderState == '9'){
                $return = ['status'=>3, 'msg'=>'代付失败'];
            }elseif ($retorderState == '4'){
                $return = ['status'=>3, 'msg'=>'退票'];
            }

        }else{
            $return = ['status' => 1, 'msg' =>'订单金额不符合'];
        }

        return $return;
    }

    /**
     * 简单的html
     * <html>
    <head>
    <meta charset="UTF-8">
    </head>
    <body>
    <form name="MerOrder" id="MerOrder" method="post" action="https://ebp.ips.com.cn/fpms-access/action/withdrawal/withdrawal.html">
    <input type="hidden" name="ipsRequest" value="<ipsRequest><argMerCode>178767</argMerCode><arg3DesXmlPara>R+nYymHzyova9I8kDRMUYd2LxqK4unbdI4mYnWNs5CTkh9+3ZsjXL3ZIu3RZ00fF/ZKG8QkmxQl89vd/6g2H5io410lyqFEHGc3/oKjglQL20zboiDDj3Y5q57JaV1YuOQzAvlKlY+H+2VG8nK4mdGmBzKti7+MY0qQwNFsRlTcjCP9bTAZ7JIYsU2KZFVGjjf9omQ2cJGoEi9DDWxEdovzOkzc4BxBo6nSsVrrjbCWPsGCdwKmVS6XoOTqQS8X4MWgjpdt8HxHzyWpKCJz8rsbdK+LRfLV4pDUugc6Q6ZxEVkjUeQj1xnnp9e2j4sw6z1Su007Orjex/xIFKJFvKJBO/Eg9BOmX7R43doqtGbQDX73e08nRyL/CRhIX3+MyxSuuuNxpkRCkehHzBhWUvQDwUxU/Tfw64/+4s8qRtqnNjWTQsbYu6mHzJrHbi1FAYXbVosapVWHrU9pAkHpONHPV0nmdnmy+TtrVwxf7fUE8WwmYalowoFWVNkfz2b/wbse70F4ylpws/vjauzK1Pp99XAX1Q4zrR1DCg4a+UsXTuJFyQKWF/xcw27Ls1NSU1686++MV7Is=</arg3DesXmlPara></ipsRequest>"/>
    <input type="submit">
    </form>
    </body>
    </html>
     */

    public function openAccount(){
        $this->display();
    }
    /**
     * 易收付用户开户接口
     */
    public function openhu(){
        header("Content-Type:text/html;charset=UTF-8");
        if(IS_POST){
            $customerCode = I("post.customerCode",'');
            $userName = I("post.userName",'');
            $identityNo = I("post.identityNo",'');
            $mobiePhoneNo = I("post.mobiePhoneNo",'');
            if(strlen($customerCode) < 9){
                die('客户号不正确！');
            }elseif ($userName ==''){
                die('持卡人姓名不能为空！');
            }elseif (strlen($identityNo) < 18){
                die('持卡人姓名身份证不正确！');
            }elseif (strlen($mobiePhoneNo) < 11){
                die('银行卡绑定手机号不正确！');
            }
        }else{
            die("非法操作！");
        }

        $argMerCode = '210754';
        $reqIp = $_SERVER["REMOTE_ADDR"];
        $reqDate = date("Y-m-d H:i:s");
        $merAcctNo = '2107540011';//商户账户号
        $userType = '2';//用户类型
        //$customerCode = '006';//客户号10002,10004是个人 100059231是自己的
        $identityType = '1';//证件类型
        //$identityNo = '632126199602101634';//证件号
        //$userName = '尚军邦';//用户姓名，个人用户名为个人姓名,企业为企业名称
        $legalName = '';//法人姓名
        $legalCardNo = '';//法人身份证
        //$mobiePhoneNo = '15846147692';//手机号
        $email = '2575711838@qq.com';//邮箱地址
        $pageUrl = $this->YUMING.'/Payment_HuanXun_pageUrl.html';
        $s2sUrl  = $this->YUMING.'/Payment_HuanXun_s2sUrl.html';
        $signKey = 'UCHpWKnpdb1MjljwVVltZf8d9mUn217EyWdfVnKcVF4pGmwF5fw0M9DcJrR9uVvABE0O1EB8RvDhAnWt5gJk2BExvbS6dGF0rUvE4OpufkrG1WZfSfje55NEE3gvpF0T';//MD5秘钥


        $body="<body><merAcctNo>$merAcctNo</merAcctNo><userType>$userType</userType><customerCode>$customerCode</customerCode><identityType>$identityType</identityType><identityNo>$identityNo</identityNo><userName>$userName</userName><legalName>$legalName</legalName><legalCardNo>$legalCardNo</legalCardNo><mobiePhoneNo>$mobiePhoneNo</mobiePhoneNo><email>$email</email><pageUrl>$pageUrl</pageUrl><s2sUrl>$s2sUrl</s2sUrl></body>";


        $head ="<head><version>V1.0.1</version><reqIp>$reqIp</reqIp><reqDate>$reqDate</reqDate><signature>".md5($body.$signKey)."</signature></head>";

        $openUserReqXml="<?xml version=\"1.0\" encoding=\"utf-8\"?><openUserReqXml>". $head.$body."</openUserReqXml>";

        $transferReq=$this->encrypt($openUserReqXml);

        $ipsRequest = "<ipsRequest><argMerCode>$argMerCode</argMerCode><arg3DesXmlPara>".$transferReq."</arg3DesXmlPara></ipsRequest>";


        $this->assign('code',$ipsRequest);
        $this->display();

        /*
         * pageUrl.html返回
         * array(2) {
            ["ipsRequest"] => string(0) ""
            ["ipsResponse"] => string(1094) "<ipsResponse><argMerCode>210754</argMerCode><rspCode>M000000</rspCode><rspMsg>处理成功</rspMsg><p3DesXmlPara>WlR1ir3BhaUKbnUxIXm8Hdtv5F3vmeV2Y8/TM7yRCOzreZqFoKk2N+ykkedWGdsorz2STBZ1CDN3isddsu9OW6aRZ0PCgecBQfAB+sLBuO3RkuioZJ3nwrs0UxBW5l6N64YeM4vrVYkaq6Z+7dUZWdUWSPvFZ4fJhCGzyT/2aCZxQN/D3ILfhHMiAax86RkwpGgXU1tQfeLv5gRZeab/4EHq8W4FjtNsNMXPwoxxOgadiiNVb/+WGwMZz8OAA7yOlT+YpBIckCKTZd02Ch5UofGCtn/ZdtmWoqiWCw448S6+0r3UhDoOs+WUg0hH9Wwq4idHJEztOuUtlm0ElNnBpaV491lwIEsUFQogE190RKpndzO1CT0n5/b6uhmfxTuE+IVFvzVHJFKlti3OvsyvnK4KXqby6d8RZ/11fMxIaSahU3P69hMoPjwXgL6RhXvZ4dqqC/sU0mTLxu9PUMpXX3NJZXtXD62kqpBR5nCcNnlRCMK0fi+Hh4ME7z2Sz7rg5QqeV5Hl4pce2xanT/4T+GN8Zz+LIXNs1BeFPHkca6x9DH7D60NCL+GStRNza0p4RK4h0F2eqAUaKqISef/2hWOJuQAv7S8NRjy7rEZCo0g3ho5LqN2UvFecEP+F7Ocn+iR6bRRA+0CxmWIkg6VXPp+pCDeotdQn9vdkjqaB68nNkJg6nI+/KJNzRgmSG2d1GD2nia/jwvYRGdHA0NjWOLWiyxhpaQG69PeOIUvnFHicylJ479kuLf96GIl/E3QIfDAl+PiNO1orhV/aHVJ6QnTjS+lxcZZhC3TGA8kJ8yth2pAhMqiY1zbo41R2uPLtcIAcRy1TqmzZStTmCBOv1NA39xvdD5IYXG7nA8Gp5bz58OvnUwudsPRj2yDMVe5Hq27UVDWO/MNDnpR/WRN4H9v/EduKyw/N0EoV/+ByLQdQ7h4sl8RdiQ==</p3DesXmlPara></ipsResponse>"
}*/
    }

    /**
     * 开户结果查询接口
     */
    public function openhuQuery(){

        $body="<body><merAcctNo>$merAcctNo</merAcctNo><userType>$userType</userType><customerCode>$customerCode</customerCode><identityType>$identityType</identityType><identityNo>$identityNo</identityNo><userName>$userName</userName><legalName>$legalName</legalName><legalCardNo>$legalCardNo</legalCardNo><mobiePhoneNo>$mobiePhoneNo</mobiePhoneNo><email>$email</email><pageUrl>$pageUrl</pageUrl><s2sUrl>$s2sUrl</s2sUrl></body>";


        $head ="<head><version>V1.0.1</version><reqIp>$reqIp</reqIp><reqDate>$reqDate</reqDate><signature>".md5($body.$signKey)."</signature></head>";

        $openUserReqXml="<?xml version=\"1.0\" encoding=\"utf-8\"?><openUserReqXml>". $head.$body."</openUserReqXml>";

        $transferReq=$this->encrypt($openUserReqXml);

        $ipsRequest = "<ipsRequest><argMerCode>$argMerCode</argMerCode><arg3DesXmlPara>".$transferReq."</arg3DesXmlPara></ipsRequest>";
        $post_data['ipsRequest']  = $ipsRequest;
        $responsexml = $this->request_post($gateway, $post_data);
    }

    /**
     * 易收付同步返回接口
     */
    public function pageUrl(){
        header("Content-Type:text/html;charset=UTF-8");
        $data = $_POST;
        dump($data);
    }
    /**
     * 易收付异步返回接口
     */
    public function s2sUrl(){
        $rawData = $_POST;
        $data = $this->xmlToarr($rawData['paymentResult']);
        //添加日记
        $logTitle = $this->TITLE . '-代付提交-';
        $log = [
            'merchantId' => null,
            'code' => null,
            'channelMerchantId' => null,
            'orderId' => null,
            'type' => GDaiFuLogModel::TYPE_SUBMIT,
            'level' => GDaiFuLogModel::LEVEL_INFO,
            'msg' => '',
        ];
        $log['msg'] = '环迅代付异步通知参数: ' .json_encode($rawData,JSON_UNESCAPED_UNICODE);
        $this->logModel->add($log);
    }
    public function xmlToarr($xml){
        $obj  = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $json = json_encode($obj);
        $arr  = json_decode($json, true);
        return $arr;
    }

    public  function encrypt($input){//数据加密
        $size = mcrypt_get_block_size(MCRYPT_3DES,MCRYPT_MODE_CBC);
        $input = $this->pkcs5_pad($input, $size);
        $key = str_pad($this->KEY,24,'0');
        $td = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');
        $iv = $this->IV;
        @mcrypt_generic_init($td, $key, $iv);
        $data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        //    $data = base64_encode($this->PaddingPKCS7($data));
        $data = base64_encode($data);
        return $data;
    }
    public  function decrypt($encrypted){//数据解密
        $encrypted = base64_decode($encrypted);
        $key = str_pad($this->KEY,24,'0');
        $td = mcrypt_module_open(MCRYPT_3DES,'',MCRYPT_MODE_CBC,'');
        $iv = $this->IV;
        $ks = mcrypt_enc_get_key_size($td);
        @mcrypt_generic_init($td, $key, $iv);
        $decrypted = mdecrypt_generic($td, $encrypted);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $y=$this->pkcs5_unpad($decrypted);
        return $y;
    }

    public  function pkcs5_unpad($text){
        $pad = ord($text{strlen($text)-1});
        if ($pad > strlen($text)) {
            return false;
        }
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad){
            return false;
        }
        return substr($text, 0, -1 * $pad);
    }

    public  function pkcs5_pad ($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    protected function request_post($url = '', $post_data = array()) {
        if (empty($url) || empty($post_data)) {
            return false;
        }

        $o = "";
        foreach ( $post_data as $k => $v )
        {
            $o.= "$k=" . urlencode( $v ). "&" ;
        }
        $post_data = substr($o,0,-1);

        $postUrl = $url;
        $curlPost = $post_data;
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);

        return $data;
    }

    protected function _arrayToJson( $array ){
        global $result;
        if( !is_array( $array ) ){
            return false;
        }
        $associative = count( array_diff( array_keys($array), array_keys( array_keys( $array )) ));
        if( $associative ){
            $construct = array();
            foreach( $array as $key => $value ){
                // We first copy each key/value pair into a staging array,
                // formatting each key and value properly as we go.
                // Format the key:
                if( is_numeric($key) ){
                    $key = "key_$key";
                }
                $key = '"'.addslashes($key).'"';
                // Format the value:
                if( is_array( $value )){
                    $value = $this->_arrayToJson( $value );
                } else if( !is_numeric( $value ) || is_string( $value ) ){
                    $value = '"'.addslashes($value).'"';
                }
                // Add to staging array:
                $construct[] = "$key:$value";
            }
            // Then we collapse the staging array into the JSON form:
            $result = "{" . implode( ",", $construct ) . "}";
        } else { // If the array is a vector (not associative):
            $construct = array();
            foreach( $array as $value ){
                // Format the value:
                if( is_array( $value )){
                    $value = $this->_arrayToJson( $value );
                } else if( !is_numeric( $value ) || is_string( $value ) ){
                    $value = '"'.addslashes($value).'"';
                }
                // Add to staging array:
                $construct[] = $value;
            }
            // Then we collapse the staging array into the JSON form:
            $result = "[" . implode( ",", $construct ) . "]";
        }

        return $result;
    }

    function xml_to_array($xml){
        // 创建解析器
        $parser = xml_parser_create();
        // 将 XML 数据解析到数组中
        xml_parse_into_struct($parser, $xml, $vals, $index);
        // 释放解析器
        xml_parser_free($parser);
        // 数组处理
        $arr = array();
        $t=0;
        foreach($vals as $value) {
            $type = $value['type'];
            $tag = $value['tag'];
            $level = $value['level'];
            $attributes = isset($value['attributes'])?$value['attributes']:"";
            $val = isset($value['value'])?$value['value']:"";
            switch ($type) {
                case 'open':
                    if ($attributes != "" || $val != "") {
                        $arr[$t]['tag'] = $tag;
                        $arr[$t]['attributes'] = $attributes;
                        $arr[$t]['level'] = $level;
                        $t++;
                    }
                    break;
                case "complete":
                    if ($attributes != "" || $val != "") {
                        $arr[$t]['tag'] = $tag;
                        $arr[$t]['attributes'] = $attributes;
                        $arr[$t]['val'] = $val;
                        $arr[$t]['level'] = $level;
                        $t++;
                    }
                    break;
            }
        }
        return $arr;
    }
}