<?php
namespace Common\Model;

/**
 * 通道代付的银行编码-通道
 * Class BanksymbolModel
 * @package Common\Model
 * @author 黄治华
 */
class BanksymbolModel extends BaseModel {
    
    protected $tableName = 'banksymbol';

    protected $fields = array(
        'id', //int 主键
        'code', //string 银行统一编码
        'bank_name', //string 银行名称
        'xfb_num', //string 信付宝代付独有的编码
        'heng_xin_num', //string 恒信智付的代付独有银行编码
    );
}