<?php
namespace User\Controller;

class UserController extends BaseController
{
    public $fans;
    public function __construct()
    {
        parent::__construct();
        //验证登录
        $user_auth = session("user_auth");
        ksort($user_auth); //排序
        $code = http_build_query($user_auth); //url编码并生成query字符串
        $sign = sha1($code);
        if($sign != session('user_auth_sign') || !$user_auth['uid']){
            header("Location: ".U(__MODULE__.'/Login/index'));
        }
        //用户信息
        $this->fans = M('Member')->where(['id'=>$user_auth['uid']])->field('`id` as uid, `username`, `password`, `groupid`, `salt`,`balance`, `blockedbalance`,`ensurebalance`, `email`, `realname`, `authorized`, `apidomain`, `apikey`, `status`, `mobile`, `receiver`, `agent_cate`')->find();
        $this->fans['memberid'] = $user_auth['uid']+10000;
        //将可用余额跟保证金相加展示给商户
        $this->fans['totalmoney'] = $this->fans['balance']+$this->fans['ensurebalance'];

        $groupId = $this->groupId =  C('GROUP_ID');
        //获取用户的代理等级信息
        foreach($groupId as $k => $v){
            if($k>=$this->fans['groupid'])
                unset($groupId[$k]);
        }
        $this->assign('groupId',$groupId);
        $this->assign('fans',$this->fans);
    }
}
?>
