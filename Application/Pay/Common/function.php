<?php
function createForm($url, $data)
{
    $str = '<!doctype html>
            <html>
                <head>
                    <meta charset="utf8">
                    <title>正在跳转付款页</title>
                </head>
                <body onLoad="document.pay.submit()">
                <form method="post" action="' . $url . '" name="pay">';

                    foreach($data as $k => $vo){
                        $str .= '<input type="hidden" name="' . $k . '" value="' . $vo . '">';
                    }

                $str .= '</form>
                <body>
            </html>';
    return $str;
}



function encryptDecrypt($string, $key='',  $decrypt='0')
{ 
    if($decrypt){ 
        $decrypted = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($string), MCRYPT_MODE_CBC, md5(md5($key))), "12");
        return $decrypted; 
    }else{ 
        $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $string, MCRYPT_MODE_CBC, md5(md5($key)))); 
        return $encrypted; 
    } 
}

function getKey($orderid){
    $key = M('Order')->where(['pay_orderid'=>$orderid])->getField('key');
    return $key;
}   

function curlPost( $url, $data='', $headers=array(), $agent='')
{
    $ch = curl_init();   
    curl_setopt ($ch, CURLOPT_URL, $url);
    curl_setopt ($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers  );
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    $file_contents = curl_exec($ch);
  
    //这里解析
    return $file_contents;
}

function md5Sign($data, $key, $connect='',$is_md5 = true)
{
    ksort($data);
    $string = '';
    foreach( $data as $k => $vo ){
        if($vo != '')
            $string .=  $k . '=' . $vo . '&' ;
    }
    $string = rtrim($string, '&');
    $result = $string . $connect . $key;
    
    return $is_md5 ? md5($result) : $result;
    
}



function rsaEncryptVerify($string, $file_path, $sign = '', $type= OPENSSL_PKCS1_PADDING)
{   
    $content =is_file($file_path) ? file_get_contents($file_path) : $file_path;
    if($sign ==''){
        $key = openssl_get_privatekey($content);
        openssl_sign($string, $result, $key , $type);
        return base64_encode($result);   
    }else{
        $key = openssl_get_publickey($content);    
        return (bool)openssl_verify($string, $sign, $key, $type);
    }
}

function getLocalIP() {
    $preg = "/\A((([0-9]?[0-9])|(1[0-9]{2})|(2[0-4][0-9])|(25[0-5]))\.){3}(([0-9]?[0-9])|(1[0-9]{2})|(2[0-4][0-9])|(25[0-5]))\Z/";
    //获取操作系统为win2000/xp、win7的本机IP真实地址
    exec("ipconfig", $out, $stats);
    if (!empty($out)) {
        foreach ($out AS $row) {
            if (strstr($row, "IP") && strstr($row, ":") && !strstr($row, "IPv6")) {
                $tmpIp = explode(":", $row);
                if (preg_match($preg, trim($tmpIp[1]))) {
                    return trim($tmpIp[1]);
                }
            }
        }
    }
    //获取操作系统为linux类型的本机IP真实地址
    exec("ifconfig", $out, $stats);
    if (!empty($out)) {
        if (isset($out[1]) && strstr($out[1], 'addr:')) {
            $tmpArray = explode(":", $out[1]);
            $tmpIp = explode(" ", $tmpArray[1]);
            if (preg_match($preg, trim($tmpIp[0]))) {
                return trim($tmpIp[0]);
            }
        }
    }
    return '127.0.0.1';
}
function getIP() { 
    if (getenv('HTTP_CLIENT_IP')) { 
        $ip = getenv('HTTP_CLIENT_IP'); 
    } 
    elseif (getenv('HTTP_X_FORWARDED_FOR')) { 
        $ip = getenv('HTTP_X_FORWARDED_FOR'); 
    } 
    elseif (getenv('HTTP_X_FORWARDED')) { 
        $ip = getenv('HTTP_X_FORWARDED'); 
    } 
    elseif (getenv('HTTP_FORWARDED_FOR')) { 
        $ip = getenv('HTTP_FORWARDED_FOR'); 
    } 
    elseif (getenv('HTTP_FORWARDED')) { 
        $ip = getenv('HTTP_FORWARDED'); 
    } 
    else { 
        $ip = $_SERVER['REMOTE_ADDR']; 
    } 
    return $ip; 
} 

function nonceStr($length = 32)
 { 
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; 
    $randomString = ''; 
    for ($i = 0; $i < $length; $i++) 
    { 
        $randomString .= $characters[rand(0, strlen($characters) - 1)]; 
    } 
    return $randomString; 
}