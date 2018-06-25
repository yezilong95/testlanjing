<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-08-22
 * Time: 14:34
 */
namespace User\Controller;

use Intervention\Image\ImageManagerStatic;
use Think\Page;
use Think\Upload;

/**
 *  商家账号相关控制器
 * Class AccountController
 * @package User\Controller
 */

class AccountController extends UserController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 编辑个人资料
     */
    public function profile()
    {

        $list = M("Member")->where(['id' => $this->fans['uid']])->find();
        //查询是否开启短信验证
        $sms_is_open = smsStatus();
        if ($sms_is_open) {
            $this->assign('sendUrl', U('User/Account/profileSend'));
        }
        //查询是否开启代付API
        $df_api = M('websiteconfig')->getField('df_api');
        $this->assign('df_api', $df_api);
        $this->assign('sms_is_open', $sms_is_open);
    
        $this->assign("p", $list);
        $this->display();
    }

    /**
     * 发送编辑个人资料验证码信息 
     */
    public function profileSend()
    {
        $res = $this->send('saveProfile', $this->fans['mobile'],'编辑个人资料验');
        $this->ajaxReturn(['status'=>$res['code']]);
    }

    /**
     * 保存个人资料
     */
    public function saveProfile()
    {
        if (IS_POST) {
            //验证验证码
            $code = I('request.code');
            $sms_is_open = smsStatus();
            
            if ($sms_is_open && session('send.saveProfile') != $code && !($this->checkSessionTime('saveProfile', $code))) {
                $this->ajaxReturn(['status' => 0, 'info'=>'验证码不正确']);
            } else if ($sms_is_open) {
                session('send.saveProfile', null);
            }

            $uid           = I('post.id', 0, 'intval');
            $p             = I('post.p');
            $p['birthday'] = strtotime($p['birthday']);
            $res           = M('Member')->where(['id' => $uid])->save($p);
            $this->ajaxReturn(['status' => 1, 'info'=>'保存成功']);
        }
    }

    /**
     *  银行卡列表
     */
    public function bankcard()
    {
        $list = M('Bankcard')
            ->where(['userid' => $this->fans['uid']])
            ->order('id desc')
            ->select();
        $this->assign("list", $list);
        $this->display();
    }

    /**
     *  添加银行卡
     */
    public function addBankcard()
    {

        $banklist = M("Systembank")->select();
        $this->assign("banklist", $banklist);

        if (IS_POST) {
            $id   = I('post.id', 0, 'intval');
            $rows = I('post.b');
            if ($id) {
                $res = M('Bankcard')->where(['id' => $id])->save($rows);
            } else {
                $rows['userid'] = $this->fans['uid'];
                $res            = M('Bankcard')->add($rows);
            }
            $this->ajaxReturn(['status' => $res]);
        } else {
            $id = I('get.id', 0, 'intval');
            if ($id) {
                //查询是否开启短信验证
                $sms_is_open = smsStatus();

                if ($sms_is_open) {
                    $this->assign('sendUrl', U('User/Account/addBankcardSend'));
                }
                $this->assign('sms_is_open', $sms_is_open);

                $data = M('Bankcard')->where(['id' => $id])->find();
                $this->assign('b', $data);
            }
            $this->display();
        }

    }

    /**
     * 发送申请结算验证码信息
     */
    public function addBankcardSend()
    {
        $res = $this->send('addBankcardSend', $this->fans['mobile'], '申请结算');
        $this->ajaxReturn(['status' => $res['code']]);
    }

    /**
     *绑定手机
     */
    public function bindMobileShow()
    {
        if (IS_POST) {
            //验证验证码
            $code   = I('request.code');
            $mobile = I('request.mobile');

            if (session('send.bindMobile') == $code && $this->checkSessionTime('bindMobile', $code)) {
                $res = M('Member')->where(['id' => $this->fans['uid']])->save(['mobile' => $mobile]);
                $this->ajaxReturn(['status' => $res]);
            }
        } else {
            $sms_is_open = smsStatus();
            if ($sms_is_open) {
                $id = I('request.id', '');
                $this->assign('sendUrl', U('User/Account/bindMobile'));
                $this->assign('first_bind_mobile', 1);
                $this->assign('sms_is_open', $sms_is_open);
                $this->display();
            }
        }
    }

    /**
     *修改手机新手机
     */
    public function editMobileShow()
    {
        $sms_is_open = smsStatus();
        if (IS_POST) {
            $code = I('request.code');
            if (session('send.editMobile') == $code && $this->checkSessionTime('editMobile', $code)) {
                //判断是验证码新手机还是旧手机后的处理
                if (session('editmobile') == '1') {
                    $mobile           = I('request.mobile');
                    $return['status'] = M('Member')->where(['id' => $this->fans['uid']])->save(['mobile' => $mobile]);
                    $return['data']   = 'editNewMobile';
                } else {
                    session('editmobile', 1);
                    $return['status'] = 1;
                }

                $this->ajaxReturn($return);
            }
        } else {
            if ($sms_is_open) {
                //判断是否是获取新手机验证码还是旧手机验证码的视图
                !I('request.editnewmobile', 0) && session('editmobile', 0);
                $this->assign('editmobile', session('editmobile'));
                $this->assign('sms_is_open', $sms_is_open);
                $this->assign('sendUrl', U('User/Account/editMobile'));
                $this->assign('mobile', $this->fans['mobile']);
                $this->display();
            }
        }
    }

    /**
     *  修改默认
     */
    public function editBankStatus()
    {
        if (IS_POST) {
            $id        = I('post.id');
            $isdefault = I('post.isopen');
            if ($id) {
                if ($isdefault) {
                    M('Bankcard')->where(['userid' => $this->fans['uid']])->save(['isdefault' => 0]);
                }
                $res = M('Bankcard')->where(['id' => $id])->save(['isdefault' => $isdefault]);
                $this->ajaxReturn(['status' => $res]);
            }
        }
    }
    /**
     *  删除银行卡
     */
    public function delBankcard()
    {
        if (IS_POST) {
            $id = I('post.id', 0, 'intval');
            if ($id) {
                $res = M('Bankcard')->where(['id' => $id])->delete();
                $this->ajaxReturn(['status' => $res]);
            }
        }
    }
    public function bankcardedit()
    {
        if (IS_POST) {
            $id       = I('post.id');
            $Ip       = new \Org\Net\IpLocation('UTFWry.dat'); // 实例化类 参数表示IP地址库文件
            $location = $Ip->getlocation(); // 获取某个IP地址所在的位置

            $Bankcard  = M("Bankcard");
            $_formdata = array(
                'userid'       => session("userid"),
                'bankname'     => I('post.bankname'),
                'bankfenname'  => I('post.bankfenname'),
                'bankzhiname'  => I('post.bankzhiname'),
                'banknumber'   => I('post.banknumber'),
                'bankfullname' => I('post.bankfullname'),
                'sheng'        => I('post.sheng'),
                'shi'          => I('post.shi'),
                'kdatetime'    => date("Y-m-d H:i:s"),
                'jdatetime'    => date("Y-m-d H:i:s", time() + 40 * 3600 * 24),
                'ip'           => $location['ip'],
                'ipaddress'    => $location['country'] . "-" . $location['area'],
                'disabled'     => 1,
            );
            if ($id) {
                $result = $Bankcard->where(array('id' => $id))->save($_formdata);
            } else {
                $result = $Bankcard->add($_formdata);
            }
            $Bankcard->getLastSql();
            if ($result) {
                $this->success("银行卡信息修改成功！");
            } else {
                $this->error("银行卡息修改失败！");
            }
        }
    }

    public function loginrecord()
    {
        $maps['userid'] = $this->fans['uid'];
        $count          = M('Loginrecord')->where($maps)->count();
        $page           = new Page($count, 5);
        $list           = M('Loginrecord')
            ->where($maps)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order('id desc')
            ->select();
        $this->assign("list", $list);
        $this->assign('page', $page->show());
        $this->display();
    }

    /**
     *  商户认证
     */
    public function authorized()
    {
        $authorized = M('Member')->where(['id' => $this->fans['uid']])->getField('authorized');
        $list       = [];
        $list       = M('Attachment')->where(['userid' => $this->fans['uid']])->select();
        $this->assign('list', $list);
        $this->assign('authorized', $authorized);
        $this->display();
    }

    public function upload()
    {
        if (IS_POST) {
            $upload           = new Upload();
            $upload->maxSize  = 2097152;
            $upload->exts     = array('jpg', 'gif', 'png');
            $upload->savePath = '/verifyinfo/';
            $info             = $upload->uploadOne($_FILES['auth']);
            if (!$info) {
                // 上传错误提示错误信息
                $this->error($upload->getError());
            } else {
                $data = [
                    'userid'   => $this->fans['uid'],
                    'filename' => $info['name'],
                    'path'     => 'Uploads' . $info['savepath'] . $info['savename'],
                ];
                $res = M("Attachment")->add($data);
                $this->ajaxReturn($res);
            }
        }
    }

    public function certification()
    {
        M('Member')->where(['id' => $this->fans['uid']])->save(['authorized' => 2]);
        $this->success('已申请认证，请等待审核！');
    }

    /**
     *  修改支付密码
     */
    public function editPaypassword()
    {
        $data = M('Member')->where(['id' => $this->fans['uid']])->find();
        $this->assign('p', $data);
        //查询是否开启短信验证
        $sms_is_open = smsStatus();

        if (IS_POST) {
            //验证验证码
            $code = I('request.code');
            if ($sms_is_open && session('send.editPayPassword') != $code && !($this->checkSessionTime('editPayPassword', $code))) {
                $this->ajaxReturn(['status' => 0]);
            } else if ($sms_is_open) {
                session('send.editPayPassword', null);
            }

            $id = I('post.id');
            $p  = I('post.p');
            if (!$p['oldpwd'] || !$p['newpwd'] || !$p['secondpwd'] || $p['newpwd'] != $p['secondpwd'] ||
                $data['paypassword'] != md5($p['oldpwd'])) {
                $this->ajaxReturn(['status' => 0, 'msg' => '输入错误']);
            }
            $res = M('Member')->where(['id' => $id])->save(['paypassword' => md5($p['newpwd'])]);
            $this->ajaxReturn(['status' => $res]);
        } else {
            if ($sms_is_open) {
                $this->assign('sendUrl', U('User/Account/editPayPasswordSend'));
            }
            $this->assign('sms_is_open', $sms_is_open);
            $this->display();
        }
    }

    /**
     * 修改密码
     */
    public function editPassword()
    {
        $data = M('Member')->where(['id' => $this->fans['uid']])->find();
        $this->assign('p', $data);
        //查询是否开启短信验证
        $sms_is_open = smsStatus();

        if (IS_POST) {
            //验证验证码
            $code = I('request.code');
            if (!$sms_is_open && session('send.editPassword') != $code && !($this->checkSessionTime('editPassword', $code))) {
                $this->ajaxReturn(['status' => 0]);
            } else if ($sms_is_open) {
                session('send.editPassword', null);
            }

            $salt = $data['salt'];
            $id   = I('post.id');
            $p    = I('post.p');
            if (!$p['oldpwd'] || !$p['newpwd'] || !$p['secondpwd'] || $p['newpwd'] != $p['secondpwd'] || $data['password'] != md5
                ($p['oldpwd'] . $salt)) {
                $this->ajaxReturn(['status' => 0, 'msg' => '输入错误']);
            }
            $res = M('Member')->where(['id' => $id])->save(['password' => md5($p['newpwd'] . $salt)]);
            $this->ajaxReturn(['status' => $res]);
        } else {
            if ($sms_is_open) {
                $this->assign('sendUrl', U('User/Account/editPasswordSend'));
            }
            $this->assign('sms_is_open', $sms_is_open);
            $this->display();
        }
    }

    /**
     *  资金变动记录
     */
    public function changeRecord()
    {
        //商户支付通道
        $products = M('ProductUser')
            ->join('LEFT JOIN __PRODUCT__ ON __PRODUCT__.id = __PRODUCT_USER__.pid')
            ->where(['pay_product_user.status' => 1, 'pay_product_user.userid' => $this->fans['uid']])
            ->field('pay_product.name,pay_product.id,pay_product.code')
            ->select();
        $this->assign("products", $products);

        $where   = array();
        $orderid = I("get.orderid");
        if ($orderid) {
            $where['transid'] = array('eq', $orderid);
        }
        $tongdao = I("request.tongdao");
        if ($tongdao) {
            $where['tongdao'] = array('eq', $tongdao);
        }
        $bank = I("request.bank", '', 'strip_tags');
        if ($bank) {
            $where['lx'] = array('eq', $bank);
        }
        $createtime = urldecode(I("request.createtime"));
        if ($createtime) {
            list($cstime, $cetime) = explode('|', $createtime);
            $where['datetime']     = ['between', [$cstime, $cetime ? $cetime : date('Y-m-d')]];
        }
        $where['userid'] = $this->fans['uid'];
        $count           = M('Moneychange')->where($where)->count();
        $size            = 15;
        $rows            = I('get.rows', $size);
        if (!$rows) {
            $rows = $size;
        }
        $page = new Page($count, $rows);
        $list = M('Moneychange')
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order('id desc')
            ->select();
        $this->assign('rows', $rows);
        $this->assign('list', $list);
        $this->assign('page', $page->show());
        C('TOKEN_ON', false);
        $this->display();
    }

    /**
     * 资金变动记录导出
     */
    public function exceldownload()
    {
        $where = array();

        $orderid = I("request.orderid");
        if ($orderid) {
            $where['orderid'] = $orderid;
        }
        $tongdao = I("request.tongdao");
        if ($tongdao) {
            $where['tongdao'] = array('eq', $tongdao);
        }
        $bank = I("request.bank", '', 'strip_tags');
        if ($bank) {
            $where['lx'] = array('eq', $bank);
        }
        $createtime = urldecode(I("request.createtime"));
        if ($createtime) {
            list($cstime, $cetime) = explode('|', $createtime);
            $where['datetime']     = ['between', [$cstime, $cetime ? $cetime : date('Y-m-d')]];
        }
        $where['userid'] = $this->fans['uid'];
        $list            = M("Moneychange")->where($where)->select();
        $title           = array('订单号', '用户名', '类型', '提成用户名', '提成级别', '原金额', '变动金额', '变动后金额', '变动时间', '通道', '备注');

        foreach ($list as $key => $value) {
            $data[$key]['transid']    = "\t" . $value["transid"];
            $data[$key]['parentname'] = getParentName($value["tcuserid"], 1);
            switch ($value["lx"]) {
                case 1:
                    $data[$key]['lxstr'] = "付款";
                    break;
                case 3:
                    $data[$key]['lxstr'] = "手动增加";
                    break;
                case 4:
                    $data[$key]['lxstr'] = "手动减少";
                    break;
                case 6:
                    $data[$key]['lxstr'] = "结算";
                    break;
                case 7:
                    $data[$key]['lxstr'] = "冻结";
                    break;
                case 8:
                    $data[$key]['lxstr'] = "解冻";
                    break;
                case 9:
                    $data[$key]['lxstr'] = "提成";
                    break;
                default:
                    $data[$key]['lxstr'] = "未知";
            }
            $data[$key]['tcuserid']   = getParentName($value["tcuserid"], 1);
            $data[$key]['tcdengji']   = $value["tcdengji"];
            $data[$key]['ymoney']     = $value["ymoney"];
            $data[$key]['money']      = $value["money"];
            $data[$key]['gmoney']     = $value["gmoney"];
            $data[$key]['datetime']   = "\t" . $value["datetime"];
            $data[$key]['tongdao']    = getProduct($value["tongdao"]);
            $data[$key]['contentstr'] = $value["contentstr"];
        }
        exportCsv($data, $title);
    }

    /**
     * 收款二维码
     */
    public function qrcode()
    {
        $list = M("Member")->where(['id' => $this->fans['uid']])->find();
        $this->assign("p", $list);

        //生成二维码
        import("Vendor.phpqrcode.phpqrcode", '', ".php");
        $site = ((is_https()) ? 'https' : 'http') . '://' . C("DOMAIN");
        $url  = U('Pay/Charges/index', array('mid' => ($this->fans['uid'] + 10000)));

        $url = urldecode($site . $url);
        $QR  = "Uploads/charges/" . ($this->fans['uid'] + 10000) . ".png"; //已经生成的原始二维码图

        \QRcode::png($url, $QR, "L", 20, 1);

        //生成背景图
        vendor('image.autoLoad');
        ImageManagerStatic::configure(array('driver' => C('imageDriver')));
        $imageQr = ImageManagerStatic::make($QR)->resize(244, 244);
        $image   = ImageManagerStatic::make("Public/images/qrcode_bg.png");
        $image->text($this->fans['receiver'], 320, 560, function ($font) {
            $font->file('Public/Front/fonts/msyh.ttf');
            $font->size(24);
            $font->color('#333');
            $font->align('center');
            $font->valign('top');
        });
        $image->insert($imageQr, "left-top", 198, 300);
        $image->save($QR);

        $this->assign("imageurl", $QR);

        $this->display();
    }

    /**
     * 收款链接
     */
    public function link()
    {

        $site = ((is_https()) ? 'https' : 'http') . '://' . C("DOMAIN");
        $url  = U('Pay/Charges/index', array('mid' => ($this->fans['uid'] + 10000)));
        $url = urldecode($site . $url);
        $this->assign("url", $url);
        $this->display();
    }

    /**
     * 下载二维码
     */
    public function downQrcode()
    {
        $QR       = "Uploads/charges/" . ($this->fans['uid'] + 10000) . ".png";
        $filename = ($this->fans['uid'] + 10000) . ".png";
        header("Content-type: octet/stream");
        header("Content-disposition:attachment;filename=" . $filename . ";");
        header("Content-Length:" . filesize($QR));
        readfile($QR);
    }
    /**
     * 保存二维码背景
     */
    public function uploadQrcode()
    {
        $config = array(
            'maxSize'  => 3145728,
            'rootPath' => 'Public/images/',
            'savePath' => '',
            'saveName' => 'qrcode_bg',
            'replace'  => true,
            'exts'     => array('jpg', 'gif', 'png', 'jpeg'),
        );
        $upload = new \Think\Upload($config); // 实例化上传类
        // 上传文件
        $info = $upload->upload();
        if (!$info) {
// 上传错误提示错误信息
            $response = ['code' => 1, 'msg' => $upload->getError(), 'data' => ['url' => '']];
            $this->ajaxReturn($response);
        } else {
// 上传成功
            $response = ['code' => 0, 'msg' => '上传成功', 'data' => ['url' => '']];
            $this->ajaxReturn($response);
        }
    }

    /**
     * 保存台卡收款人
     */
    public function saveReceiver()
    {
        $p = I('request.p');
        M("Member")->where(['id' => $this->fans['uid']])->save($p);
        $this->redirect('User/Account/qrcode');
    }

    /**
     * 发送修改登录密码的验证码信息
     */
    public function editPasswordSend()
    {
        $res = $this->send('editPassword', $this->fans['mobile'], '登录密码');
        $this->ajaxReturn(['status' => $res['code']]);
    }
    /**
     * 发送修改支付密码的验证码信息
     */
    public function editPayPasswordSend()
    {
        $res = $this->send('editPayPassword', $this->fans['mobile'], '支付密码');
        $this->ajaxReturn(['status' => $res['code']]);
    }

    /**
     * 绑定手机验证码
     */
    public function bindMobile()
    {
        $mobile = I('request.mobile');
        $res    = $this->send('bindMobile', $mobile, '绑定手机');
        $this->ajaxReturn(['status' => $res['code']]);
    }

    public function editMobile()
    {
        $mobile = I('request.mobile', '');
        if (!$mobile) {
            $mobile = $this->fans['mobile'];
        }
        $res = $this->send('editMobile', $mobile, '修改手机');
        $this->ajaxReturn(['status' => $res['code']]);
    }

    /**
     * 发送申请结算的验证码信息
     */
    public function clearingSend()
    {
        $res = $this->send('clearing', $this->fans['mobile'], '申请结算');
        $this->ajaxReturn(['status'=>$res['code']]);
    }

    /**
     * 发送委托结算的验证码信息
     */
    public function entrustedSend()
    {
        $res = $this->send('entrusted', $this->fans['mobile'], '委托结算');
        $this->ajaxReturn(['status'=>$res['code']]);
    }
}
