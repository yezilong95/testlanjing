<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-08-22
 * Time: 14:34
 */
namespace User\Controller;

/**
 * 用户中心首页控制器
 * Class IndexController
 * @package User\Controller
 */

class IndexController extends UserController
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 首页
     */
    public function index()
    {
        $loginout = U(__MODULE__ . "/Login/loginout");

	$user_auth = session('user_auth');
        if($user_auth['groupid'] == 4){
            $user_type_label = '我是商户';
        }else if($user_auth['groupid'] == 5){
            $user_type_label = '我是代理商';
        }else{
            $user_type_label = '';
        }

        $this->assign('user_type_label', $user_type_label);
        $this->assign('loginout', $loginout);
        $this->display();
    }

    public function main()
    {
        $firstday = date('Y-m-01', time());
        $lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day"));

        //成交金额
        $sql = "SELECT SUM( pay_actualamount ) AS total, FROM_UNIXTIME( pay_successdate,  '%Y-%m-%d' ) AS DATETIME
FROM pay_order WHERE pay_successdate >= UNIX_TIMESTAMP(  '".$firstday."' ) AND pay_successdate < UNIX_TIMESTAMP(  '".
            $lastday."' ) AND pay_status>=1 AND pay_memberid=".($this->fans['memberid'])."  GROUP BY DATETIME";
        $ordertotal = M('Order')->query($sql);

        //成交订单数
        $sql = "SELECT COUNT( id ) AS num, FROM_UNIXTIME( pay_successdate,  '%Y-%m-%d' ) AS DATETIME
FROM pay_order WHERE pay_successdate >= UNIX_TIMESTAMP(  '".$firstday."' ) AND pay_successdate < UNIX_TIMESTAMP(  '".
            $lastday."' ) AND pay_status>=1 AND pay_memberid=".($this->fans['memberid'])."  GROUP BY DATETIME";
        $ordernum = M('Order')->query($sql);
        foreach ($ordernum as $key=>$item){
            $category[] = date('Ymd',strtotime($item['datetime']));
            $dataone[] = $item['num'];
            $datatwo[] = $ordertotal[$key]['total'];
        }
        $this->assign('category','['.implode(',',$category).']');
        $this->assign('dataone','['.implode(',',$dataone).']');
        $this->assign('datatwo','['.implode(',',$datatwo).']');

        //文章默认最新2条
        $Article = M("Article");
        $gglist = $Article->where(['status'=> 1])->limit(2)->order("id desc")->select();

        //获取最近两次登录记录
        $loginlog = M("Loginrecord")->where(['userid' => $this->fans['uid']])->order('id desc')->limit(2)->select();
        $lastlogin = '';
        if(isset($loginlog[1])) {
            $lastlogin = $loginlog[1];
        }

        $this->assign('lastlogin', $lastlogin);
        $this->assign("gglist", $gglist);
        $this->display();
    }

    public function showcontent()
    {
        $id = I("get.id");
        $Article = M("Article");
        $find = $Article->where("id=" . $id)->find();
        $this->assign("find", $find);
        $this->display();
    }

    public function gonggao()
    {
        $list = M('Article')->where(['status'=> 1])->select();
        $this->assign("list", $list);
        $this->display();
    }
}
