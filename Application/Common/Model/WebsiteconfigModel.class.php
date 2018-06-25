<?php
namespace Common\Model;

/**
 * 商户模型 - 公共模块
 * Class BaseModel
 * @package Common\Model
 * @author 黄治华
 */
class WebsiteconfigModel extends BaseModel {

    protected $tableName = 'websiteconfig';

    protected $fields = array(
        'id', //主键
        'websitename', //网站名称
        'domain', //网址
        'email', //
        'tel', //
        'qq', //
        'directory', //后台目录名称
        'icp', //
        'tongji', //统计
        'login', //登录地址
        'payingservice', //商户代付 1 开启 0 关闭, int
        'authorized', //商户认证 1 开启 0 关闭, int
        'invitecode', //邀请码注册, int
        'company', //公司名称
        'serverkey', //授权服务
        'withdraw', //提现通知：0关闭，1开启, int
    );

    protected $_validate = array(
        array(
            "websitename",
            "require",
            "网站名称不能为空",
            0,
            "regex",
            3
        ),
        array(
            "websitename",
            "3,10",
            "网站名称最少 3 个字符，最多 10 个字符",
            2,
            "length",
            3
        ),
        array(
            "domain",
            "require",
            "域名不能为空",
            0,
            "regex",
            3
        )
    );

}