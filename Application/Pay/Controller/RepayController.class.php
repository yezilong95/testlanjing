<?php
namespace Pay\Controller;

use Think\Controller;

/**
 * 防封域名重跳地址
 * Class RepayController
 * @package Pay\Controller
 */
class RepayController extends Controller
{
    public function index()
    {
        header("Content-Type:text/html;charset=UTF-8");
        $url = 'Pay_Index.html';
        $str = '<!doctype html>
            <html>
                <head>
                    <meta charset="utf8">
                    <title>正在跳转付款页</title>
                </head>
                <body onLoad="document.pay.submit()">
                <form method="post" action="' . $url . '" name="pay">';

                foreach($_GET as $k => $vo){
                    $str .= '<input type="hidden" name="' . $k . '" value="' . $vo . '">';
                }

        $str .= '</form>
                <body>
            </html>';

        echo $str;
        die;
    }
}