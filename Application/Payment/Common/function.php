<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 14-12-29
 * Time: 上午1:31
 */

/**
 *[验证传输的数据]
 * @param array $datas [键是要验证的变量名，值是错误信息]
 *
 * 例子：
 * $datas = ['a'=>'错误！'];
 * verifyData($datas)
*/
function verifyData($datas){
    if(is_array($datas)){
        foreach($datas as $k => $v){
            $return[$k] = isset($_REQUEST[$k]) ? trim($_REQUEST[$k]) : '';
            if($return[$k] === ''){
                showError($v);
            }
        }
        return $return;
    }
}

/**
 *[验证用户是否登录]
*/
function isLogin(){
    $user = session('admin_auth');
    !is_array($user) && showError('访问错误！');
    ksort($user); //排序
    session('admin_auth_sign') != sha1( http_build_query($user) ) && showError('访问错误！');
}

/**
 *[返回错误信息]
 *@param string $msg [错误信息]
 *@param array  $fields [返回的错误数据]
 *
 *例子：
 *showError('错误了');
*/
function showError($msg='操作失败', $fields=array()){
    header('Content-Type:application/json; charset=utf-8');
    $data = array('status'=>'error', 'msg'=>$msg, 'data'=>$fields);
    echo json_encode($data,320);
    exit;
}

/**
 *[返回成功信息]
 *@param string $msg [成功信息]
 *@param array  $fields [返回的成功数据]
 *
 *例子：
 *showSuccess('ok');
*/
function showSuccess($msg='操作成功',$fields=array()){
    header('Content-Type:application/json; charset=utf-8');
    $data = array('status'=>'success', 'msg'=>$msg, 'data'=>$fields);
    echo json_encode($data,320);
    exit; 
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

function curlPost( $url, $data='', $headers=array()){
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
   
    $file_contents = curl_exec($ch);
  
    //这里解析
    return $file_contents;
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