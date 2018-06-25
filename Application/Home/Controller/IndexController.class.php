<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-08-22
 * Time: 14:34
 */
namespace Home\Controller;

/**
 * 网站入口控制器
 * Class IndexController
 * @package Home\Controller
 * @author 22691513@qq.com
 */
class IndexController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $diffModel = D('Diff');
        $web_name = $diffModel->where(['key' => 'web_name'])->getField('value');
        $logo_img = $diffModel->where(['key' => 'logo_img'])->getField('value');
        $logo_big_img = $diffModel->where(['key' => 'logo_big_img'])->getField('value');
        $body_bg_img = $diffModel->where(['key' => 'body_bg_img'])->getField('value');
        $banner_img1 = $diffModel->where(['key' => 'banner_img1'])->getField('value');
        $banner_img2 = $diffModel->where(['key' => 'banner_img2'])->getField('value');

        $this->assign('web_name', $web_name);
        $this->assign('logo_img', $logo_img);
        $this->assign('logo_big_img', $logo_big_img);
        $this->assign('body_bg_img', $body_bg_img);
        $this->assign('banner_img1', $banner_img1);
        $this->assign('banner_img2', $banner_img2);
        $this->display();
    }

    public function introduce()
    {
        $diffModel = D('Diff');
        $web_name = $diffModel->where(['key' => 'web_name'])->getField('value');
        $logo_img = $diffModel->where(['key' => 'logo_img'])->getField('value');

        $this->assign('web_name', $web_name);
        $this->assign('logo_img', $logo_img);
        $this->display();
    }

    public function demo()
    {
        $diffModel = D('Diff');
        $web_name = $diffModel->where(['key' => 'web_name'])->getField('value');
        $logo_img = $diffModel->where(['key' => 'logo_img'])->getField('value');
        $demo_dir = $diffModel->where(['key' => 'demo_dir'])->getField('value');

        $this->assign('web_name', $web_name);
        $this->assign('logo_img', $logo_img);
        $this->assign('demo_dir', $demo_dir);
        $this->display();
    }

    public function daifu()
    {
        $diffModel = D('Diff');
        $web_name = $diffModel->where(['key' => 'web_name'])->getField('value');
        $logo_img = $diffModel->where(['key' => 'logo_img'])->getField('value');
        $demo_dir = $diffModel->where(['key' => 'demo_dir'])->getField('value');

        $this->assign('web_name', $web_name);
        $this->assign('logo_img', $logo_img);
        $this->assign('demo_dir', $demo_dir);
        $this->display();
    }

    /**
     * 生成二维码
     */
    public function generateQrcode()
    {
        $str     =html_entity_decode(urldecode(I('str','')));
        if(!$str){
            exit('请输入要生成二维码的字符串！');
        }
        import("Vendor.phpqrcode.phpqrcode",'',".php");
        header('Content-type: image/png');
        \QRcode::png($str, false, "L", 10, 1);
        die;
    }
}