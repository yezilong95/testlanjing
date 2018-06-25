<?php
namespace Common\Model;

/**
 * 商户模型-公共模块
 * Class BaseModel
 * @package Common\Model
 * @author 黄治华
 */
class MemberModel extends BaseModel {

    protected $tableName = 'member';

    protected $fields = array(
        'id', //主键
        'username', //用户名
        'password', //密码
        'groupid', //用户组, tinyint
        'salt', //密码随机字符
        'parentid', //代理ID
        'balance', //可用余额, 可提现
        'blockedbalance', //冻结可用余额
        'email', //
        'activate', //
        'regdatetime', //
        'activatedatetime', //
        'realname', //姓名
        'sex', //性别
        'birthday', //
        'sfznumber', //
        'mobile', //联系电话
        'qq', //
        'address', //联系地址
        'paypassword', //支付密码
        'authorized', //1 已认证 0 未认证 2 待审核
        'apidomain', //授权访问域名
        'apikey', //APIKEY
        'status', //状态 1激活 0未激活
        'receiver', //台卡显示的收款人信息
    );

    //常量 groupid
    const GROUPID_MERCHANT = 4; //普通商户
    const GROUPID_AGENT_L1 = 5; //普通代理商
    const GROUPID_AGENT_L2 = 6; //中级代理商
    const GROUPID_AGENT_L3 = 7; //高级代理商

    /**
     *  获取会员信息
     * @param $uid
     * @return mixed
     */
    public function get_Userinfo($uid){
        $return = $this->where(array('id'=>$uid))->find();
        return $return;
    }
}