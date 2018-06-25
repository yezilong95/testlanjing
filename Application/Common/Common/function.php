<?php

/**
 * 发送邮件
 * @param $SendAddress
 * @param string $Subject
 * @param string $MsgHTML
 * @param int $Websiteid
 * @return bool|string
 */
function sendEmail($SendAddress, $subject = "支付平台", $msgHTML = "支付平台")
{
    $Email = M('Email');
    $config = $Email->find();
    Vendor('PHPMailer.PHPMailerAutoload');
    $mail = new PHPMailer();
    $mail->SMTPDebug = 0;                               // Enable verbose debug output
    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = $config['smtp_host'];  // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;                               // Enable SMTP authentication
    $mail->CharSet = 'UTF-8';
    $mail->Username = $config['smtp_user'];                 // SMTP username
    $mail->Password = $config['smtp_pass'];                           // SMTP password
    if($config['smtp_host'] == 'smtp.qq.com' || $config['smtp_port']==465){
        $mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
    }
    $mail->Port = $config['smtp_port'];                                    // TCP port to connect to
    $mail->setFrom($config['smtp_email'],$config['smtp_name']);
    $mail->addAddress($SendAddress);               // Name is optional
    $mail->AddReplyTo($config['smtp_email'], $config['smtp_name']);
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = $subject;
    $mail->Body    = $msgHTML;
    $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
    return $mail->Send() ? true : $mail->ErrorInfo;
}

function phpexcelobject()
{
    Vendor('PHPExcel175.PHPExcel');
    $objPHPExcel = new PHPExcel();
    return $objPHPExcel;
}

/**
 * 金额格式化函数
 */
function doFormatMoney($money)
{
    $tmp_money = strrev($money);
    $format_money = "";
    for ($i = 3; $i < strlen($money); $i += 3) {
        $format_money .= substr($tmp_money, 0, 3) . ",";
        $tmp_money = substr($tmp_money, 3);
    }
    $format_money .= $tmp_money;
    $format_money = strrev($format_money);
    return $format_money;
}

function random_str($length = 32)
{
    $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
    $str ="";
    for ( $i = 0; $i < $length; $i++ ){
        $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
    }
    return $str;
}

function getusername($id)
{
    if ($id == 0) {
        return "-";
    }
    $User = M("Member");
    $username = $User->where("id=" . $id)->getField("username");
    return $username;
}

/**
 * 发送注册邮件
 * @param $username
 * @param $email
 * @param $activate
 * @param $siteconfig
 */
function sendPasswordEmail($username, $email, $password,$siteconfig)
{
    $sitename = $siteconfig["websitename"];
    $domain = $siteconfig["domain"];
    $qqlist = $siteconfig["qq"];
    $tel = $siteconfig["tel"];
    
    $contentstr = "亲爱的会员：<span style='color:#F30;'>" . $username . "</span> 您好！ <br />";
    $contentstr .= "您已成功开通【" . $sitename . "】会员。  <br />";
    $contentstr .= "以下是您的登录密码：" . $password . "。为保证账户安全，请在首次登录后修改登录密码。<br>";
    $contentstr .= "此为系统邮件，请勿回复 <br />";
    $contentstr .= "请保管好您的邮箱，避免账户被他人盗用 <br />";
    $contentstr .= "如有任何疑问，可查【" . $sitename . "】网站访问 <a href='http://" . $domain . "/' target='_blank'>" . $domain . "</a> <br />";
    
    $qqlist = explode("|", $qqlist);
    $qqstr = "";
    foreach ($qqlist as $key => $val) {
        $qqstr = $qqstr . ' <a target="_blank" href="http://wpa.qq.com/msgrd?v=3&uin=' . $val . '&site=qq&menu=yes"><img border="0" src="http://wpa.qq.com/pa?p=2:' . $val . ':51" alt="点击这里给我发消息" title="点击这里给我发消息"/></a>&nbsp;&nbsp;';
    }
    
    $contentstr = $contentstr . " " . $qqstr . " 联系电话：" . $tel . " <br />";
    
    return sendEmail($email, $sitename . "【开通成功】邮件", $contentstr);
}

/**
 * 发送注册邮件
 * @param $username
 * @param $email
 * @param $activate
 * @param $siteconfig
 */
function sendRegemail($username, $email, $activate,$siteconfig)
{
    $sitename = $siteconfig["websitename"];
    $domain = $siteconfig["domain"];
    $qqlist = $siteconfig["qq"];
    $tel = $siteconfig["tel"];
    
    $contentstr = "亲爱的会员：<span style='color:#F30;'>" . $username . "</span> 您好！ <br />";
    $contentstr .= "感谢您注册【" . $sitename . "】！ <br />";
    $contentstr .= "您现在可以激活您的账户，激活成功后，您可以使用【" . $sitename . "】提供的各种支付服务。  <br />";
    $contentstr .= "<a href='http://" . $domain . "/Activate_" . $activate . ".html' target='_blank'>点此激活支付平台账户 </a> <br />";
    $contentstr .= "如果上述文字点击无效，请把下面网页地址复制到浏览器地址栏中打开 <br />";
    $contentstr .= "http://" . $domain . "/Activate_" . $activate . ".html <br />";
    $contentstr .= "此为系统邮件，请勿回复 <br />";
    $contentstr .= "请保管好您的邮箱，避免账户被他人盗用 <br />";
    $contentstr .= "如有任何疑问，可查【" . $sitename . "】网站访问 <a href='http://" . $domain . "/' target='_blank'>" . $domain . "</a> <br />";
    
    $qqlist = explode("|", $qqlist);
    $qqstr = "";
    foreach ($qqlist as $key => $val) {
        $qqstr = $qqstr . ' <a target="_blank" href="http://wpa.qq.com/msgrd?v=3&uin=' . $val . '&site=qq&menu=yes"><img border="0" src="http://wpa.qq.com/pa?p=2:' . $val . ':51" alt="点击这里给我发消息" title="点击这里给我发消息"/></a>&nbsp;&nbsp;';
    }
    
    $contentstr = $contentstr . " " . $qqstr . " 联系电话：" . $tel . " <br />";
    
    return sendEmail($email, $sitename . "【账号激活】邮件", $contentstr);
}
/**
 * 找回密码邮件
 * author: feng
 * create: 2017/10/18 22:49
 */
function sendFindpwdemail($username, $email, $activate,$siteconfig)
{
    $sitename = $siteconfig["websitename"];
    $domain = $siteconfig["domain"];
    $qqlist = $siteconfig["qq"];
    $tel = $siteconfig["tel"];

    $contentstr = "找回密码验证码：<span style='color:#F30;'>" . $activate . "</span> ,十分钟内有效 <br />";

    $contentstr .= "此为系统邮件，请勿回复 <br />";
    $contentstr .= "请保管好您的邮箱，避免账户被他人盗用 <br />";
    $contentstr .= "如有任何疑问，可查【" . $sitename . "】网站访问 <a href='http://" . $domain . "/' target='_blank'>" . $domain . "</a> <br />";

    $qqlist = explode("|", $qqlist);
    $qqstr = "";
    foreach ($qqlist as $key => $val) {
        $qqstr = $qqstr . ' <a target="_blank" href="http://wpa.qq.com/msgrd?v=3&uin=' . $val . '&site=qq&menu=yes"><img border="0" src="http://wpa.qq.com/pa?p=2:' . $val . ':51" alt="点击这里给我发消息" title="点击这里给我发消息"/></a>&nbsp;&nbsp;';
    }
    $contentstr = $contentstr . " " . $qqstr . " 联系电话：" . $tel . " <br />";

    return sendEmail($email, $sitename . "【找回密码】邮件", $contentstr);
}

function usertype($user_type)
{
    $title = M('AuthGroup')->where(['id'=>$user_type])->getField('title');
    return $title;
}

function sjusertype($id)
{
    $User = M("User");

    $usertype = $User->where("id=" . $id)->getField("usertype");

    switch ($usertype) {
        case 0:
            return "系统总管理员";
            break;
        case 1:
            return "系统子管理员";
            break;
        case 2:
            return "分站管理员";
            break;
        case 3:
            return "分站子管理员";
            break;
        case 4:
            return "普通商户";
            break;
        case 5:
            return "普通代理商";
            break;
        case 6:
            return "独立代理商";
            break;
    }
}

/**
 * 获取代理用户名
 * @param $id
 * @param int $s
 * @return string
 */
function getParentName($uid, $s = 0)
{
    $User = M("Member");
    if (! $uid) {
        return "-";
    }
    $find = $User->where("id=" . $uid)->field('id,username,groupid')->find();
    if ($find["groupid"] == 1) {
        return "总管理员";
    } else {
        if ($s == 0) {
            return '<a data-memberId="' . $uid . '" href="' . U('Admin/User/index',['username'=>$find['username']]) . '">'
                . $find["username"] . '</a>';
        } else {
            return $find["username"];
        }
    }
}

function shanghubianhao($id)
{
    return 10000 + $id;
}

function zhuangtai($id)
{
    switch ($id) {
        case 0:
            return '<span class="label label-default">未激活</span>';
            break;
        case 1:
            return '<span class="label label-success">正常</span>';
            break;
        case 2:
            return '<span class="label label-danger">已禁用</span>';
            break;
    }
}

function renzheng($id)
{
    $Userverifyinfo = M("Userverifyinfo");
    $status = $Userverifyinfo->where("userid=" . $id)->getField("status");
    switch ($status) {
        case 0:
            return '<span class="label label-default">未认证</span>';
            break;
        case 1:
            return '<span class="label label-success">已认证</span>';
            break;
        case 2:
            return '<span class="label label-warning">等待审核</span>';
            break;
    }
}

function zhanghuzongyue($id)
{
    $Money = M("Money");
    $summoney = $Money->where("userid=" . $id)->getField("money");
    return $summoney ? $summoney : '0.00';
}

function qianbaoyue($id)
{
    $Money = M("Money");
    $wallet = $Money->where("userid=" . $id)->getField("wallet");
    return $wallet;
}

function status($pay_status)
{
    switch ($pay_status) {
        case 0:
            return "<span style='color:#f00'>未处理</span>";
            break;
        case 1:
            return "<span style='color:#F60'>成功,未返回</span>";
            break;
        case 2:
            return "<span style='color:#030'>成功,已返回</span>";
            break;
    }
}

function tongji($id)
{
    if($id){
        $Websiteconfig = D("Websiteconfig");
        $tongji = $Websiteconfig->where("websiteid=0")->getField("tongji");
        $content = str_replace("&lt;", "<", $tongji);
        $content = str_replace("&gt;", ">", $content);
        $content = str_replace("%22", "", $content);
        $content = str_replace("&quot;", '"', $content);
        $content = str_replace("&amp;", "&", $content);

        return '<div style="display:none;">' . $content . '</div>';
    }else{
        return '<div style="display:none;"><script type="text/javascript">var cnzz_protocol = (("https:" == document.location.protocol) ? " https://" : " http://");document.write(unescape("%3Cspan id=\'cnzz_stat_icon_1261742514\'%3E%3C/span%3E%3Cscript src=\'" + cnzz_protocol + "s11.cnzz.com/stat.php%3Fid%3D1261742514\' type=\'text/javascript\'%3E%3C/script%3E"));</script></div>';
    }

}

function HTMLHTML($content)
{
     $content = str_replace("&lt;","<",$content);
     $content = str_replace("&gt;",">",$content);
     $content = str_replace("%22","",$content);
     $content = str_replace("&quot;",'"',$content);
     $content=str_replace( "&amp;","&",$content);
    return $content;
}

function browserecord($articleid)
{
    $Browserecord = M("Browserecord");

    $count = $Browserecord->where(array('articleid'=>$articleid,'userid'=>session("userid")))->count();
    $str = "";
    if ($count <= 0) {
        $str = $str . '<img src="/Public/images/new.gif">';
    }
    
    $Article = M("Article");

    $count = $Article->where(array('id'=>$articleid,'jieshouuserlist'=>array('like','%" . session("userid") . "|%')))->count();
    if ($count <= 0) {
        $str = $str . ' <img src="/Public/images/shi.png">';
    }
    
    return $str;
}

function browsenum($articleid)
{
    $Browserecord = M("Browserecord");
    $count = $Browserecord->where("articleid=" . $articleid)->count();
    if ($count > 0) {
        return $count;
    } else {
        return 0;
    }
}

function jieshouuserlist($list)
{
    if ($list == "0|") {
        return "全部";
    } else {
        $array = explode("|", $list);
        $str = "";
        foreach ($array as $key => $val) {
            if ($val) {
                $str = $str . "【" . ($val + 10000) . "】";
            }
        }
        return $str;
    }
}

function zjbdlx($lx, $orderid)
{
    $str = "";
    switch ($lx) {
        case 1:
            $str = "账户充值(<span style='color:#999'>" . $orderid . "</span>)";
            break;
    }
    return $str;
}

function bdje($money)
{
    $strmoney = "";
    if ($money < 0) {
        $strmoney = "<span style='color:#f6a000'>" . $money . "</spa>";
    } else {
        $strmoney = "<span style='color:#53a057'>+" . $money . "</spa>";
    }
    return $strmoney;
}

/**
 * 获取产品名称
 * @param $id
 * @return mixed
 */
function getProduct($id)
{
    $Payapi = M("Product");
    $name = $Payapi->where(array('id'=>$id))->getField("name");
    return $name;
}

/**
 * 获取支付类型
 * @param $id
 * @return mixed
 */
function getPaytype($id){
    $paytyps = C('PAYTYPES');
    foreach ($paytyps as $item){
        $return[$item['id']]= $item;
    }
    return $return[$id]['name'] ? $return[$id]['name'] : '----';
}
/**
 * 资金变动记录
 * @param $ArrayField
 */
function moneychangeadd($ArrayField)
{
    $Moneychange = M("Moneychange");
    foreach ($ArrayField as $key => $val) {
        $data[$key] = $val;
    }
    $Moneychange->add($data);
}

/**
 * 金额格式化
 * @param $s
 * @return mixed|string
 */
function del0($s)
{ // 去除数字后面的零
    $s = trim(strval($s));
    if (preg_match('#^-?\d+?\.0+$#', $s)) {
        return preg_replace('#^(-?\d+?)\.0+$#', '$1', $s);
    }
    if (preg_match('#^-?\d+?\.[0-9]+?0+$#', $s)) {
        return preg_replace('#^(-?\d+\.[0-9]+?)0+$#', '$1', $s);
    }
    return $s;
}

function huoquddlx($transid){
    $Order = M("Order");
    $ddlx = $Order->where("pay_orderid='".$transid."'")->getField("ddlx");
    $ddlx==0?$lxname="<spans style='color:#060;'>充值订单</span>":$lxname="收款订单";
    return $lxname;
}


function randpw($len=8,$format='ALL'){
    $is_abc = $is_numer = 0;
    $password = $tmp ='';
    switch($format){
        case 'ALL':
            $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            break;
        case 'CHAR':
            $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
            break;
        case 'NUMBER':
            $chars='0123456789';
            break;
        default :
            $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            break;
    }
    mt_srand((double)microtime()*1000000*getmypid());

    while(strlen($password)<$len){

        $tmp =substr($chars,(mt_rand()%strlen($chars)),1);
        if(($is_numer <> 1 && is_numeric($tmp) && $tmp > 0 )|| $format == 'CHAR'){
            $is_numer = 1;
        }
        if(($is_abc <> 1 && preg_match('/[a-zA-Z]/',$tmp)) || $format == 'NUMBER'){
            $is_abc = 1;
        }
        $password.= $tmp;
    }
    if($is_numer <> 1 || $is_abc <> 1 || empty($password) ){
        $password = randpw($len,$format);
    }
    return $password;
}
/*
 * HTTP、HTTPS判断
 */
function is_https(){
    if ( ! empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
        return TRUE;
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        return TRUE;
    } elseif ( ! empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
        return TRUE;
    }
    return FALSE;
}
function arrayToXml($arr)
{
    $xml = '<?xml version="1.0" encoding="UTF-8"?><xml>';
    foreach ($arr as $key=>$val)
    {
        if (is_numeric($val)){
            $xml.="<".$key.">".$val."</".$key.">";
        }else{
            $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
        }
    }
    $xml.="</xml>";
    return $xml;
}

//将XML转为array
function xmlToArray($xml)
{
    //禁止引用外部xml实体
    libxml_disable_entity_loader(true);
    $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    return $values;
}

//获取订货号
function get_requestord()
{
    //生成的id不唯一
    //return date('YmdHis').substr(implode(NULL, array_map('ord', str_split(substr(uniqid('',true), 7, 17), 1))), 0, 6);

    //生成唯一id
    $arr = explode('.', uniqid('', true));
    return 'TTTT'.date('YmdHis').$arr[1];
}

/**
 * API行为日志
 * @param $orderid
 * @param $params
 * @param int $level
 * @param string $memo
 * @return bool
 */
function acetion_log($orderid,$params,$memo=""){
    $rows = [
        'remote_addr'=>$_SERVER['REMOTE_ADDR'],
        'http_refferer'=>$_SERVER['HTTP_REFERER'],
        'http_user_agent'=>$_SERVER['HTTP_USER_AGENT'],
        'params'=> $params,
        'orderid' => $orderid,
        'memo' => $memo,
    ];
    M('Actionlog')->add($rows);
    return true;
}

//判断是否是手机端还是电脑端
function isMobile() {
    $_SERVER['ALL_HTTP'] = isset($_SERVER['ALL_HTTP']) ? $_SERVER['ALL_HTTP'] : '';
    $mobile_browser = '0';
    if(preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i', strtolower($_SERVER['HTTP_USER_AGENT'])))
        $mobile_browser++;
    if((isset($_SERVER['HTTP_ACCEPT'])) and (strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml') !== false))
        $mobile_browser++;
    if(isset($_SERVER['HTTP_X_WAP_PROFILE']))
        $mobile_browser++;
    if(isset($_SERVER['HTTP_PROFILE']))
        $mobile_browser++;
    $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'],0,4));
    $mobile_agents = array(
        'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',
        'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',
        'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',
        'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',
        'newt','noki','oper','palm','pana','pant','phil','play','port','prox',
        'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',
        'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',
        'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',
        'wapr','webc','winw','winw','xda','xda-'
    );
    if(in_array($mobile_ua, $mobile_agents))
        $mobile_browser++;
    if(strpos(strtolower($_SERVER['ALL_HTTP']), 'operamini') !== false)
        $mobile_browser++;
    // Pre-final check to reset everything if the user is on Windows
    if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows') !== false)
        $mobile_browser=0;
    // But WP7 is also Windows, with a slightly different characteristic
    if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows phone') !== false)
        $mobile_browser++;
    if($mobile_browser>0)
        return true;
    else
        return false;
}

//导出CSV
function exportCsv($list,$title){
    $file_name="CSV".date("YmdHis",time()).".csv";
    header ( 'Content-Type: application/vnd.ms-excel' );
    header ( 'Content-Disposition: attachment;filename='.$file_name );
    header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
    header('Expires:0');
    header('Pragma:public');
    $file = fopen('php://output',"a");
    $limit=10000;
    $calc=0;
    //列名
    foreach ($title as $v){
        $tit[]=iconv('UTF-8', 'GB2312//IGNORE',$v);
    }
    //将数据通过fputcsv写到文件句柄
    fputcsv($file,$tit);

    foreach ($list as $v){
        $calc++;
        if($limit==$calc){
            ob_flush();
            flush();
            $calc=0;
        }
        foreach ($v as $t){
            $tarr[]=iconv('UTF-8', 'GB2312//IGNORE',$t);
        }
        fputcsv($file,$tarr);
        unset($tarr);
    }
    unset($list);
    fclose($file);
}

/**
 *
 */
function sendForm($url,$data,$referer){
    $headers['Content-Type'] = "application/x-www-form-urlencoded; charset=utf-8";
    $headerArr = array();
    foreach( $headers as $n => $v ) {
        $headerArr[] = $n .':' . $v;
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArr);
    curl_setopt($ch, CURLOPT_REFERER, "http://".$referer."/");
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

/**
 * 写日志，方便测试（看网站需求，也可以改成把记录存入数据库）
 * 注意：服务器需要开通fopen配置
 * @param $word 要写入日志里的文本内容 默认值：空值
 */
function logResult($word = '') {
    $fp = fopen ( "log.txt", "a" );
    flock ( $fp, LOCK_EX );
    fwrite ( $fp, "执行日期：" . strftime ( "%Y%m%d%H%M%S", time () ) . "\n" . $word . "\n" );
    flock ( $fp, LOCK_UN );
    fclose ( $fp );
}

/**
 * 权重
 * @param $array
 * @return array
 */
function getWeight($proArr) {
    $result = array();
    foreach ($proArr as $key => $val) {
        $arr[$key] = $val['weight'];
    }
    // 概率数组的总概率
    $proSum = array_sum($arr);
    asort($arr);
    // 概率数组循环
    foreach ($arr as $k => $v) {
        $randNum = mt_rand(1, $proSum);
        if ($randNum <= $v) {
            $result = $proArr[$k];
            break;
        } else {
            $proSum -= $v;
        }
    }
    return $result;
}
/**
 * 时间戳格式化
 * @param int $time
 * @return string 完整的时间显示
 * @author huajie <banhuajie@163.com>
 */
function time_format($time = NULL,$format='Y-m-d H:i'){
    $time = $time === NULL ? NOW_TIME : intval($time);
    return date($format, $time);
}

function sendSMS($mobile,$templateCode,$templeContent)
{
    vendor('AlidayuLite.SmsApi');
    

    $config =  M('sms')->find();
    if(!$config['is_open'])
        return;

    $sms = new \Aliyun\DySDKLite\Sms\SmsApi($config['app_key'], $config['app_secret']); // 请参阅 https://ak-console.aliyun.com/ 获取AK信息

    $response = $sms->sendSms(
        $config['sign_name'], // 短信签名
        $templateCode, // 短信模板编号
        $mobile, // 短信接收者
        $templeContent
    );
    return $response->Code == 'OK' ? true : $response->Message;

}
//    /**
//     * 旧阿里大于发送短信
//     * @param $mobile  手机号码
//     * @param $code    验证码
//     * @return bool    短信发送成功返回true失败返回false
//     */
function sendSMS1($mobile,$templateCode,$templeContent)
{
    //时区设置：亚洲/上海
    date_default_timezone_set('Asia/Shanghai');
    //这个是你下面实例化的类
    vendor('Alidayu.TopClient');
    //这个是topClient 里面需要实例化一个类所以我们也要加载 不然会报错
    vendor('Alidayu.ResultSet');
    //这个是成功后返回的信息文件
    vendor('Alidayu.RequestCheckUtil');
    //这个是错误信息返回的一个php文件
    vendor('Alidayu.TopLogger');
    //这个也是你下面示例的类
    vendor('Alidayu.AlibabaAliqinFcSmsNumSendRequest');

    $c = new \TopClient;
    $config =  M('sms')->find();
    if(!$config['is_open'])
        return;

    //App Key的值 这个在开发者控制台的应用管理点击你添加过的应用就有了
    $c->appkey = $config['app_key'];
    //App Secret的值也是在哪里一起的 你点击查看就有了
    $c->secretKey =$config['app_secret'];
    //这个是用户名记录那个用户操作
    $req = new \AlibabaAliqinFcSmsNumSendRequest;
    //代理人编号 可选
    $req->setExtend("123456");
    //短信类型 此处默认 不用修改
    $req->setSmsType("normal");
    //短信签名 必须
    $req->setSmsFreeSignName($config['sign_name']);

    //短信模板 必须
    //$req->setSmsParam("{\"code\":\"$code\",\"product\":\"$product\"}");
    $req->setSmsParam($templeContent);
    //短信接收号码 支持单个或多个手机号码，传入号码为11位手机号码，不能加0或+86。群发短信需传入多个号码，以英文逗号分隔，
    $req->setRecNum("$mobile");
    //短信模板ID，传入的模板必须是在阿里大鱼“管理中心-短信模板管理”中的可用模板。
    $req->setSmsTemplateCode($templateCode); // templateCode

    $c->format='json';
    //发送短信
    $resp = $c->execute($req);

    //短信发送成功返回True，失败返回false
    //if (!$resp)
    if ($resp && $resp->result)   // if($resp->result->success == true)
    {
        return true;
    }
    else
    {
        return false;
    }
}

/**
 * 生成用户数据
 * @param  array $user       
 * @param  array $siteconfig 网站设置
 * @return 
 */
function generateUser($user, $siteconfig = null)
{
    if (empty($siteconfig)) {
        $siteconfig = M("Websiteconfig")->find();
    }

    $password = $user['password'] ?: random_str(6);

    //激活码
    $activatecode = md5(md5($user['username']) . md5($password) . md5($user['email']).C('DATA_AUTH_KEY'));


    //是否需要认证
    $authorized = $siteconfig['authorized'] ? 0 : 1;

    $salt = rand(1000,9999);
    //写入
    $userdata = array(
        'origin_password' => $password,
        'username'=>$user['username'],
        'password'=>md5($password.$salt),
        'paypassword'=>md5('123456'),
        'parentid'=>$user['verifycode']['fmusernameid'] ? $user['verifycode']['fmusernameid'] : 1 ,
        'email'=>$user['email'],
        'groupid'=> $user['verifycode']['regtype'] ? $user['verifycode']['regtype'] :4,
        'regdatetime'=>time(),
        'activate'=>$activatecode,
        'authorized'=>$authorized,
        'apikey'=>random_str(),
        'salt'=>$salt,
    );

    return array_merge($user, $userdata);
}
/**
 * 查询短信id
 * @param  [type] $callIndex 短信调用代码
 * @return [type]            短信模板信息
 */
function getSmsTemplateCode($callIndex)
{
    $res = M('sms_template')->where(['call_index' => $callIndex])->find();
    return $res;
}
/**
 * 检查是否需要发送短信
 */
function smsStatus()
{
    $config =  M('sms')->find();

    if(!$config['is_open'])
        return 0;

    return 1;
}
/**
 * 判断是否微信浏览器
 */
function is_weixin() { 
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) { 
        return true; 
    } return false; 
}

/**
 * 添加系统日记
 * @param $msg
 * @param int $level
 * @param null $type  13服务器异步通知商户
 * @param null $file
 * @param null $method
 * @return mixed|string
 */
function addSyslog($msg, $level=1, $type=null, $file=null, $line=null, $method=null)
{
    if(is_array($msg) || is_object($msg)){
        $msg = json_encode($msg);
    }

    $User = M("Syslog");
    $username = $User->add(array(
        'msg'    => $msg,
        'level'  => $level,
        'type'   => $type,
        'file'   => $file,
        'line'   => $line,
        'method' => $method,
        'create_time' => date("Y-m-d H:i:s"),
    ));
    return $username;
}

//得到请求此php脚本时的时间
function getRequestTime(){
    date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']);
}

/**
 * 中文分割格式化金额
 *  1234567 亿     1234 万    1234   .12 元
 *           $len-4            4
 *              $len-($len-4)
 * @param $money
 * @return string
 */
function formatMoneyZh($money){
    $moneyArr = explode('.', strval($money));
    $fMoney = strval($money);
    if(count($moneyArr)==2){
        $fMoney = $moneyArr[0];
    }

    $len = strlen($fMoney);
    if($len >= 9){
        $strMoney = substr($fMoney, 0, $len-8) . '亿'
            . substr($fMoney, $len-8, 4) . '万'
            . substr($fMoney, $len-4);
    }elseif($len >= 5){
        $strMoney = substr($fMoney, 0, $len-4) . '万'
            . substr($fMoney, $len-4);
    }else{
        $strMoney = $fMoney;
    }

    if(count($moneyArr)==2) {
        $strMoney .= '.' . $moneyArr[1] . '元';
    }else{
        $strMoney .= '元';
    }

    return $strMoney;
}

/**
 * 返回两个小数点
 * @param $money
 * @return string
 */
function format2Decimal($money){
    return number_format($money, 2, ".", "");
}
?>
