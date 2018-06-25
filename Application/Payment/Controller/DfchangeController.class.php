<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-08-22
 * Time: 14:34
 */
namespace Payment\Controller;
use Think\Controller;
use Think\Verify;
/**
 * 用户中心首页控制器
 * Class IndexController
 * @package User\Controller
 */
class DfchangeController extends PaymentController{

    public function index(){
        header("Content-Type:text/html;charset=UTF-8");
        $pwd = I("post.pwd",'');
        if($pwd=='we9588'){
            $wttklist = M("wttklist");
            $save["status"] = '0';
            $save["orderid"] = I("post.norderid",'');
            $re = $wttklist->where(array('orderid'=>I("post.orderid",'')))->save($save);
            if ($re){
                exit("修改成功！");
            }else{
                exit("修改失败！");
            }
        }else{
            exit("未知操作！");
        }

    }
    public function ipQuery(){
        header("Content-Type:text/html;charset=UTF-8");
        $Ip  = new \Org\Net\IpLocation('UTFWry.dat');
        $location              = $Ip->getlocation(); // 获取某个IP地址所在的位置
        echo $location['ip'];
        dump('e');

    }


    
}