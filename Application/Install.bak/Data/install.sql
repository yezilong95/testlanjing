-- phpMyAdmin SQL Dump
-- version 4.5.1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: 2017-12-16 08:10:43
-- 服务器版本： 10.1.19-MariaDB
-- PHP Version: 5.5.38

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pay4.9`
--

-- --------------------------------------------------------

--
-- 表的结构 `pay_admin`
--

CREATE TABLE `pay_admin` (
  `id` int(11) UNSIGNED NOT NULL COMMENT '管理员ID',
  `username` varchar(50) NOT NULL COMMENT '后台用户名',
  `password` varchar(32) NOT NULL COMMENT '后台用户密码',
  `groupid` tinyint(1) UNSIGNED DEFAULT '0' COMMENT '用户组',
  `createtime` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `pay_admin`
--

INSERT INTO `pay_admin` (`id`, `username`, `password`, `groupid`, `createtime`) VALUES
(1, 'adminroot', '81b5234976a55e5181f24d9eab8fb697', 1, 0);

-- --------------------------------------------------------

--
-- 表的结构 `pay_apimoney`
--

CREATE TABLE `pay_apimoney` (
  `id` int(10) UNSIGNED NOT NULL,
  `userid` int(11) NOT NULL DEFAULT '0',
  `payapiid` int(11) DEFAULT NULL,
  `money` decimal(15,3) NOT NULL DEFAULT '0.000',
  `freezemoney` decimal(15,3) NOT NULL DEFAULT '0.000' COMMENT '冻结金额',
  `status` smallint(6) NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `pay_article`
--

CREATE TABLE `pay_article` (
  `id` int(11) UNSIGNED NOT NULL,
  `catid` smallint(6) UNSIGNED NOT NULL DEFAULT '0' COMMENT '分类ID',
  `title` varchar(300) NOT NULL COMMENT '标题',
  `content` text COMMENT '内容',
  `createtime` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `description` varchar(255) NOT NULL COMMENT '描述',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '1显示 0 不显示',
  `updatetime` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '更新时间'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `pay_article`
--

INSERT INTO `pay_article` (`id`, `catid`, `title`, `content`, `createtime`, `description`, `status`, `updatetime`) VALUES
(1, 1, '八月央行严打升级！大开罚单，环迅支付、汇付等机构躺枪', '&amp;nbsp; &amp;nbsp; &amp;nbsp; &amp;nbsp; 8月以来，人民银行对第三方支付机构的罚单不断。截止目前，多家支付机构违反支付业务规定，已有易联支付、汇付、环迅支付等近10多家支付机构被罚。就在今日公布消息，迅付信息科技有限公司，被中国人民银行上海分行发布“上海银罚字〔2017〕28号”处罚决定。罚款金额包括没收违法所得285696.89元及罚款150万元，罚款合计1785696.89元。这是截止今年，人行对第三方支付机构开出的一张最高金额大罚单了。同时，汇付、易联支付在内其它几家支付机构罚款明细如下：上海汇付数据服务有限公司因违反支付业务规定 罚款人民币6万元。汇潮支付有限公司违反支付业务规定被罚处以罚款人民币4万元；上海德颐网络技术有限公司违反支付业务规定被罚处以罚款人民币6万元；上海富友支付服务有限公司违反支付业务规定被罚处以罚款人民币3万元。上海电银信息技术有限公司、卡友支付服务有限公司因违反支付业务规定，均被罚限期改正，并各处6万元罚款。上海巾帼三六五企业服务有限公司、上海银联电子支付服务有限公司、上海乐易信息技术有限公司因为违反支付业务规定，分别被处以2万元、6万元、4万元罚款。易联支付有限公司因违反银行卡收单业务管理规定，被广州分行罚款601435.92元。', 1503808262, '8月以来，人民银行对第三方支付机构的罚单不断。截止目前，多家支付机构违反支付业务规定，已有易联支付、汇付、环迅支付等近10多家支付机构被罚。', 1, 1503812262),
(2, 1, '华尔街上再掀投资热潮，支付与金融科技比翼双飞', '华尔街上赫赫有名的几大投行高盛、花旗、摩根大通，面对势不可挡的金融科技，再也无法淡定了。他们一起纷纷重金砸向了金融科技领域，在华尔街上跳起了一支支投资热舞。几组数据可以让你看到这场热舞的火辣与动感。&amp;nbsp;● 花旗、高盛和摩根大通是三大最活跃的投资者。花旗(包括花旗风险投资公司)参加了30轮投资，涉22家公司;高盛参与31轮投资，涉25家公司;摩根大通参与14轮投资，涉13家公司。（来自管理咨询公司Opimas的报告，按银行投资金融科技公司的数量排序）● 根据摩根大通透露，近期将投入96亿美元并聘用专业技术团队专攻大数据、机器人和云基础设施。&amp;nbsp;● “华尔街大银行及其他成熟的金融公司可能会在2017年通过44笔交易向金融科技领域投入创纪录的17亿美元”。（来自管理咨询公司Opimas的报告）● 自2012年以来，美国前十大银行(按管理资产)已向56家金融科技公司进行了72次总计36亿美元的投资。（来自CB Insights年度数据）重磅投资金融科技领域同时，高盛、摩根大通在企业内部更是深植科技基因。高盛CEO劳尔德•贝兰克梵(Lloyd Blankfein)最常挂在嘴边的一句话是：其实，我们是一家科技公司。高盛雇用的3.6万名员工中，9000名是程序员和技术工程师。&amp;nbsp;摩根大通于去年7月投入使用的一款金融合同解析软件COIN，通过机器学习和加密云网络技术，负责处理智能合约以及解析商业贷款协议，在几秒内就能将原先律师和贷款人员每年需要36万小时做完的工作完成，在大大降低错误率的同时保证全年无休。&amp;nbsp;乐在其中的不止投行大咖，麦肯锡、普华永道等世界著名的咨询公司也为这场热舞，演绎着优美的背景音乐。&amp;nbsp;麦肯锡咨询公司(McKinsey &amp;amp; Company)早在2015年便呼吁金融机构，紧抓金融科技发展机遇，否则将失去逾四成传统金融业务营收，失去超六成传统金融业务利润。&amp;nbsp;普华永道在4月份公布的报告中称，全球将近50%的金融服务公司计划在未来3到5年收购金融科技类创业公司。&amp;nbsp;当然金融科技的内涵极为丰富，包含支付、保险、规划、借贷/众筹、区块链、交易&amp;amp;投资、数据&amp;amp;分析以及安全在内八大主题。其中支付作为构建金融科技的基层设施，最受到青睐。&amp;nbsp;据某些记者了解，高盛从2012年至今，在支付领域共投资了6家公司，参加了八轮融资，总额约5.7亿美元。包括对新晋的越南移动钱包应用MoMo的B轮融资。&amp;nbsp;摩根大通也创新发布了自己在支付领域的新产品Chase Pay。并动用资源，收购MCX（美国最大的商户网络），同时与波士顿软件公司LevelUp展开深入合作，提升Chase Pay的含金量，壮大Chase Pay的竞争实力。&amp;nbsp;看着世界金融中心华尔街，上演着热情洋溢的金融科技投资热舞。回看我们大洋彼岸的中国，中国金融科技市场更是一片欣欣向荣。从2016年全球与中国金融科技投融资情况统计图表，可以看出。', 1503808362, '华尔街上赫赫有名的几大投行高盛、花旗、摩根大通，面对势不可挡的金融科技，再也无法淡定了。他们一起纷纷重金砸向了金融科技领域，在华尔街上跳起了一支支投资热舞。几组数据可以让你看到这场热舞的火辣与动感。', 1, 0),
(3, 1, '爆料：12家支付机构吃了工商税务的罚单 最高被罚8万元', '以下为中国支付网根据“国家企业信用信息公示系统”的数据进行统计，部分信息来自于“天眼查”。本次统计截至日期为2017年8月22日，统计范围为当前持有《支付业务许可证》的247家支付机构。&amp;nbsp;这12家机构分别是通联支付网络服务股份有限公司、上海银联电子支付服务有限公司、天津城市一卡通有限公司、厦门易通卡运营有限责任公司、安易联融电子商务有限公司、广西支付通商务服务有限公司、海南海岛一卡通支付网络有限公司、广东汇卡商务服务有限公司、供销中百支付有限公司、银盈通支付有限公司、北京国华汇银科技有限公司。', 1503808474, '《企业信息公示暂行条例》规定，有关部门应当建立健全信用约束机制，在政府采购、工程招投标、国有土地出让、授予荣誉称号等工作中，将企业信息作为重要考量因素，对被列入经营异常名录或者严重违法企业名单的企业依法予以限制或者禁入。', 1, 0),
(4, 1, '银联ChinaPay、巾帼三六五、乐易三家支付机构被上海人行处罚', '上海巾帼三六五企业服务有限公司由上海巾帼社会服务有限公司组建，是一支隶属于上海市妇联的社会专业团队。2017年6月26日，央行正式公布第四批支付牌照续展名单，上海巾帼三六五企业服务有限公司成功续展《支付业务许可证》，支付业务类型包括预付卡发行与受理。► 中国人民银行上海分行于2017年8月15日作出行政处罚决定，上海银联电子支付服务有限公司因“违反支付业务规定”，被罚款6万元，行政处罚决定书文号是上海银罚〔2017〕25号。据中国支付网统计，这是上海银联首次被公开处罚。&amp;nbsp;上海银联电子支付服务有限公司(ChinaPay)是由中国银联控股，支付业务类型包括互联网支付、移动电话支付。&quot;银联在线&quot;是中国银联倾力打造的互联网业务综合商务门户网站，依托具有中国自主知识产权、国内领先的银联CUPSecure互联网安全认证支付系统和银联EBPP互联网收单系统，构建了银联便民支付网上平台、银联理财平台、银联网上商城三大业务平台，为广大持卡人提供公共事业缴费、通信缴费充值、信用卡还款、跨行转账、账单号支付、机票预订、基金理财和商城购物等全方位的互联网金融支付服务。► 中国人民银行上海分行于2017年8月15日作出行政处罚决定，上海乐易信息技术有限公司因“违反支付业务规定”，被罚款4万元，行政处罚决定书文号是上海银罚〔2017〕26号。据中国支付网统计，这是上海乐易首次被公开处罚。&amp;nbsp;上海乐易信息技术有限公司是容大集团控股的从事第三方支付和电子商务的专业化服务公司。2017年6月26日，央行正式公布第四批支付牌照续展名单，上海乐易成功续展《支付业务许可证》，支付业务类型包括预付卡发行与受理。', 1503808535, '中国人民银行上海分行于2017年8月15日作出行政处罚决定，上海巾帼三六五企业服务有限公司因“违反支付业务规定”，被罚款2万元，行政处罚决定书文号是上海银罚〔2017〕24号。据中国支付网统计，这是上海巾帼首次被公开处罚。', 1, 1503809486),
(5, 1, '支付宝被列入“经营异常名录”！ 从此，支付宝被刻上“不良记录”烙印', '8月16日，网上曝出支付宝（中国）信息技术有限公司被监管部门列入 “经营异常名录”。什么？没搞错吧？小编马上到国家企业信用信息公示系统上查询求证，是真的！支付宝确实上了“经营异常名录”。有图有真相。&amp;nbsp;&amp;nbsp;&amp;nbsp;图中清晰看到，支付宝因未依照《企业信息公示暂行条例》第八条规定的限期公示年度报告，2017年7月7日，由中国（上海）自由贸易试验区市场监督管理局将其列入经营异常名录。&amp;nbsp;特意翻阅了下《企业信息公示暂行条例》第八条规定：也就是说，支付宝迟迟未上报自己企业的年度报告，从而被列入了“异常经营名录”。是支付宝太忙了，忘了上报？还是支付宝企业有什么不可告人的秘密、藏有猫腻，那就不得而知了！不管是何种原因，但这个结果，就像是在支付宝的信任大楼里砸出了一道裂缝。对支付宝的信任感降级了！众所周知，“企业信用”对一家企业来说是项非常重要的无形资产，特别是像支付宝这样的大型支付企业，它的信用值好坏牵动着中国上亿用户的资金安全，更直接影响着广大中小型企业与支付宝的合作发展。国家企业信用信息公示系统自2014年上线以来，以一个社会中介机构的角色，客观、公正的对各企业评定资信情况，取得良好信用等级的企业，能吸引广大企业放心大胆的与之合作；相反，如果信用等级不佳或有问题，就犹如在双方合作面前出现了拦路虎，阻挡了合作的可能。信用，是一个企业发展之根本。企业一旦被载入经营异常名录，这个“不良记录”的烙印就将与企业并存。也就是说，即使之后支付宝被移出了经营异常名录，但曾经被载入不良名单的记录并不会消失，这不但影响着企业声誉，就连企业日常经营也会受限，例如：企业在需要申请办理各类登记备案事项、行政许可审批事项和资质审核等，都会因为这个曾有的“不良记录”遭遇行政管理部门审慎审查，被限制或者禁入。&amp;nbsp;支付宝树大招风，坏消息也不止这一个。前不久，据说，蚂蚁金服被央行约谈不得用“无现金”宣传，上周末，余额宝宣布限额10万，年化利率下跌4%以下；这周，支付宝“亲密付”出现网络诈骗，有人被骗上万元….. 坏消息接踵而至，支付宝再次被推到风口浪尖上，支付宝是见过大场面的，相信也是没有在怕的。但这次被监管部门载入经营异常名录，无凝是对支付宝永久的伤害。当初以解决人与人之间信任为初衷而成立的支付宝，如今支付宝的企业信任值却出现瑕疵。这不免让人觉得有点讽刺。看来，支付宝，这次是真的失策了。马云爸爸或许马上就要来霸屏了。', 1503808590, '8月16日，网上曝出支付宝（中国）信息技术有限公司被监管部门列入 “经营异常名录”。什么？没搞错吧？', 1, 0),
(11, 4, '银联通道维护！', '银联通道维护！', 1503810697, '银联通道维护！', 1, 0),
(12, 4, '支付宝扫码通道维护！', '支付宝扫码通道维护！', 1503810718, '支付宝扫码通道维护！', 1, 0);

-- --------------------------------------------------------

--
-- 表的结构 `pay_attachment`
--

CREATE TABLE `pay_attachment` (
  `id` int(11) UNSIGNED NOT NULL,
  `userid` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '商户编号',
  `filename` varchar(100) NOT NULL,
  `path` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `pay_attachment`
--

INSERT INTO `pay_attachment` (`id`, `userid`, `filename`, `path`) VALUES
(48, 2, '242dd42a2834349b88359f1eccea15ce36d3be7e.jpg', 'Uploads/verifyinfo/59a2b65d0816c.jpg'),
(46, 2, '6-140F316125V44.jpg', 'Uploads/verifyinfo/59a2b65cd9877.jpg'),
(47, 2, '6-140F316132J02.jpg', 'Uploads/verifyinfo/59a2b65cea2ec.jpg');

-- --------------------------------------------------------

--
-- 表的结构 `pay_auth_group`
--

CREATE TABLE `pay_auth_group` (
  `id` mediumint(8) UNSIGNED NOT NULL,
  `title` char(100) NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `is_manager` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '1需要验证权限 0 不需要验证权限',
  `rules` varchar(500) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `pay_auth_group`
--

INSERT INTO `pay_auth_group` (`id`, `title`, `status`, `is_manager`, `rules`) VALUES
(1, '超级管理员', 1, 0, '1,77,2,8,9,89,3,16,27,28,29,17,30,31,32,18,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,92,93,94,98,99,19,50,51,52,53,88,95,96,97,4,14,54,55,56,57,58,15,59,60,61,62,63,64,5,23,65,66,24,67,6,13,68,69,70,71,73,76,25,72,26,74,75,7,12,78,79,80,81,82,22,83,84,85,86,87,101,102,103'),
(2, '运营管理员', 1, 0, '1,77,3,18,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,49,92,93,94,98,99,19,50,51,52,4,14,54,55,56,57,15,59,60,61,62,63,5,23,65,66,24,67,6,13,68,69,70,71,73,76,7,12,78,79,80,81,82,22,83,84,85,86,87'),
(3, '财务管理员', 1, 1, '1,77,5,23,65,66,24,67,6,13,68,69,70,71,73,76,25,72,26,74,75'),
(4, '普通商户', 1, 1, ''),
(5, '普通代理商', 1, 1, '');

-- --------------------------------------------------------

--
-- 表的结构 `pay_auth_group_access`
--

CREATE TABLE `pay_auth_group_access` (
  `uid` mediumint(8) UNSIGNED NOT NULL,
  `group_id` mediumint(8) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `pay_auth_group_access`
--

INSERT INTO `pay_auth_group_access` (`uid`, `group_id`) VALUES
(1, 1),
(2, 4),
(7, 2);

-- --------------------------------------------------------

--
-- 表的结构 `pay_auth_rule`
--

CREATE TABLE `pay_auth_rule` (
  `id` mediumint(8) UNSIGNED NOT NULL,
  `icon` varchar(100) DEFAULT '' COMMENT '图标',
  `menu_name` varchar(100) NOT NULL DEFAULT '' COMMENT '规则唯一标识Controller/action',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '菜单名称',
  `pid` tinyint(5) NOT NULL DEFAULT '0' COMMENT '菜单ID ',
  `is_menu` tinyint(1) UNSIGNED DEFAULT '0' COMMENT '1:是主菜单 0否',
  `is_race_menu` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '1:是 0:不是',
  `type` tinyint(1) NOT NULL DEFAULT '1',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `condition` char(100) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--
-- 转存表中的数据 `pay_auth_rule`
--

INSERT INTO `pay_auth_rule` (`id`, `icon`, `menu_name`, `title`, `pid`, `is_menu`, `is_race_menu`, `type`, `status`, `condition`) VALUES
(1, 'fa fa-home', 'Index/index', '管理首页', 0, 1, 0, 1, 1, ''),
(2, 'fa fa-asterisk', 'System/#', '系统设置', 0, 1, 0, 1, 1, ''),
(3, 'fa fa-user', 'User/#', '用户管理', 0, 1, 0, 1, 1, ''),
(4, 'fa fa-bank', 'Channel/#', '通道管理', 0, 1, 0, 1, 1, ''),
(5, 'fa fa fa-sellsy', 'Order/#', '订单管理', 0, 1, 0, 1, 1, ''),
(6, 'fa fa fa-cubes', 'Withdrawal/#', '提款管理', 0, 1, 0, 1, 1, ''),
(7, 'fa fa-book', 'Content/#', '文章管理', 0, 1, 0, 1, 1, ''),
(8, 'fa fa-cog', 'System/base', '基本设置', 2, 1, 0, 1, 1, ''),
(9, 'fa fa-envelope-o', 'System/email', '邮件设置', 2, 1, 0, 1, 1, ''),
(12, 'fa fa-file-pdf-o', 'Content/category', '栏目管理', 7, 1, 0, 1, 1, ''),
(13, 'fa fa-money', 'Withdrawal/setting', '提款设置', 6, 1, 0, 1, 1, ''),
(14, 'fa fa-product-hunt', 'Channel/index', '供应商通道管理', 4, 1, 0, 1, 1, ''),
(15, 'fa fa-product-hunt', 'Channel/product', '支付产品管理', 4, 1, 0, 1, 1, ''),
(16, 'fa fa-users', 'Menu/index', '权限管理', 3, 1, 0, 1, 1, ''),
(17, 'fa fa-user', 'Auth/index', '角色管理', 3, 1, 0, 1, 1, ''),
(18, ' fa fa-user', 'User/index', '用户管理', 3, 1, 0, 1, 1, ''),
(19, 'fa fa-code-fork', 'User/invitecode', '邀请码管理', 3, 1, 0, 1, 1, ''),
(79, '', 'Content/saveAddCategory', '保存添加栏目', 12, 0, 0, 1, 1, ''),
(78, '', 'Content/addCategory', '添加栏目', 12, 0, 0, 1, 1, ''),
(22, 'fa fa-file-pdf-o', 'Content/article', '文章管理', 7, 1, 0, 1, 1, ''),
(23, 'fa fa fa-sellsy', 'Order/index', '交易管理', 5, 1, 0, 1, 1, ''),
(24, 'fa fa-first-order', 'Order/changeRecord', '资金变动管理', 5, 1, 0, 1, 1, ''),
(25, 'fa fa-money', 'Withdrawal/index', '提款管理', 6, 1, 0, 1, 1, ''),
(26, 'fa fa-money', 'Withdrawal/payment', '代付管理', 6, 1, 0, 1, 1, ''),
(27, '', 'Menu/addMenu', '添加菜单', 16, 0, 0, 1, 1, ''),
(28, '', 'Menu/editMenu', '编辑菜单', 16, 0, 0, 1, 1, ''),
(29, '', 'Menu/delMenu', '删除菜单', 16, 0, 0, 1, 1, ''),
(30, '', 'Auth/ruleGroup', '分配权限', 17, 2, 0, 1, 1, ''),
(31, '', 'Auth/editGroup', '编辑用户组', 17, 2, 0, 1, 1, ''),
(32, '', 'Auth/deleteGroup', '删除用户组', 17, 2, 0, 1, 1, ''),
(33, '', 'User/editStatus', '编辑状态', 18, 2, 0, 1, 1, ''),
(34, '', 'User/authorize', '用户认证', 18, 2, 0, 1, 1, ''),
(35, '', 'User/getRandstr', '获取APIKEY', 18, 2, 0, 1, 1, ''),
(36, '', 'User/usermoney', '资金管理', 18, 2, 0, 1, 1, ''),
(37, '', 'User/incrMoney', '加减余额', 18, 2, 0, 1, 1, ''),
(38, '', 'User/frozenMoney', '冻结/解冻资金', 18, 2, 0, 1, 1, ''),
(39, '', 'User/userWithdrawal', '提现设置', 18, 2, 0, 1, 1, ''),
(40, '', 'User/saveWithdrawal', '保存提现', 18, 2, 0, 1, 1, ''),
(41, '', 'User/editUserProduct', '通道管理', 18, 2, 0, 1, 1, ''),
(42, '', 'User/saveUserProduct', '保存通道设置', 18, 2, 0, 1, 1, ''),
(43, '', 'User/userRateEdit', '编辑费率', 18, 2, 0, 1, 1, ''),
(44, '', 'User/saveUserRate', '保存费率', 18, 2, 0, 1, 1, ''),
(45, '', 'User/editPassword', '修改密码', 18, 2, 0, 1, 1, ''),
(46, '', 'User/editUser', '编辑用户', 18, 2, 0, 1, 1, ''),
(47, '', 'User/saveUser', '保存用户编辑', 18, 2, 0, 1, 1, ''),
(48, '', 'User/delUser', '删除用户', 18, 2, 0, 1, 1, ''),
(49, '', 'User/exportuser', '导出用户', 18, 2, 0, 1, 1, ''),
(50, '', 'User/setInvite', '设置邀请码', 19, 2, 0, 1, 1, ''),
(51, '', 'User/saveInviteConfig', '保存邀请码设置', 19, 2, 0, 1, 1, ''),
(52, '', 'User/addInvite', '添加邀请码', 19, 2, 0, 1, 1, ''),
(53, '', 'User/delInvitecode', '删除邀请码', 19, 2, 0, 1, 1, ''),
(54, '', 'Channel/addSupplier', '添加供应商', 14, 2, 0, 1, 1, ''),
(55, '', 'Channel/saveEditSupplier', '保存添加/编辑', 14, 2, 0, 1, 1, ''),
(56, '', 'Channel/editRate', '编辑费率', 14, 2, 0, 1, 1, ''),
(57, '', 'Channel/editSupplier', '编辑供应商', 14, 2, 0, 1, 1, ''),
(58, '', 'Channel/delSupplier', '删除供应商', 14, 2, 0, 1, 1, ''),
(59, '', 'Channel/addProduct', '添加产品', 15, 2, 0, 1, 1, ''),
(60, '', 'Channel/saveProduct', '保存添加', 15, 2, 0, 1, 1, ''),
(61, '', 'Channel/prodStatus', '编辑状态', 15, 2, 0, 1, 1, ''),
(62, '', 'Channel/prodDisplay', '编辑前端显示', 15, 2, 0, 1, 1, ''),
(63, '', 'Channel/editProduct', '编辑产品', 15, 2, 0, 1, 1, ''),
(64, '', 'Channel/delProduct', '删除产品', 15, 2, 0, 1, 1, ''),
(65, '', 'Order/exportorder', '导出记录', 23, 2, 0, 1, 1, ''),
(66, '', 'Order/show', '查看详情', 23, 2, 0, 1, 1, ''),
(67, '', 'Order/exceldownload', '导出记录', 24, 2, 0, 1, 1, ''),
(68, '', 'Withdrawal/saveWithdrawal', '保存设置', 13, 2, 0, 1, 1, ''),
(69, '', 'Withdrawal/settimeEdit', '编辑时间', 13, 2, 0, 1, 1, ''),
(70, '', 'Withdrawal/addHoliday', '编辑节假日', 13, 2, 0, 1, 1, ''),
(71, '', 'Withdrawal/delHoliday', '删除节假日', 13, 2, 0, 1, 1, ''),
(72, '', 'Withdrawal/exportorder', '导出提款记录', 25, 2, 0, 1, 1, ''),
(73, '', 'Withdrawal/editStatus', '编辑记录', 13, 2, 0, 1, 1, ''),
(74, '', 'Withdrawal/exportweituo', '导出代付记录', 26, 2, 0, 1, 1, ''),
(75, '', 'Withdrawal/editwtStatus', '编辑代付', 26, 2, 0, 1, 1, ''),
(76, '', 'Withdrawal/checkNotice', '语音提醒', 13, 2, 0, 1, 1, ''),
(77, '', 'Index/main', 'Dashboard', 1, 1, 0, 1, 1, ''),
(80, '', 'Content/editCategory', '编辑栏目', 12, 0, 0, 1, 1, ''),
(81, '', 'Content/saveEditCategory', '保存编辑栏目', 12, 0, 0, 1, 1, ''),
(82, '', 'Content/delCategory', '删除栏目', 12, 0, 0, 1, 1, ''),
(83, '', 'Content/addArticle', '发表文章', 22, 0, 0, 1, 1, ''),
(84, '', 'Content/saveAddArticle', ' 保存发表文章', 22, 0, 0, 1, 1, ''),
(85, '', 'Content/editArticle', '编辑文章', 22, 0, 0, 1, 1, ''),
(86, '', 'Content/saveEditArticle', '保存编辑文章', 22, 0, 0, 1, 1, ''),
(87, '', 'Content/delArticle', '删除文章', 22, 0, 0, 1, 1, ''),
(88, ' fa fa-user', 'Admin/index', '管理员管理', 3, 1, 0, 1, 1, ''),
(89, 'fa fa-cog', 'System/planning', '计划任务', 2, 1, 0, 1, 1, ''),
(90, 'fa fa-envelope-o', 'System/smssz', '短信设置', 2, 1, 0, 1, 0, ''),
(91, 'fa fa-envelope-o', 'System/smsTemplateList', '短信模板', 2, 1, 0, 1, 0, ''),
(92, '', 'User/frozenTiming', 'T1冻结资金管理', 18, 2, 0, 1, 1, ''),
(93, '', 'User/frozenHandle', '单条数据解冻', 18, 2, 0, 1, 1, ''),
(94, '', 'User/FrozenHandles', '批量解冻', 18, 2, 0, 1, 1, ''),
(95, '', 'Admin/addAdmin', '添加管理员', 88, 2, 0, 1, 1, ''),
(96, '', 'Admin/deleteAdmin', '删除管理员', 88, 2, 0, 1, 1, ''),
(97, '', 'Admin/editAdmin', '编辑管理员', 88, 2, 0, 1, 1, ''),
(98, '', 'User/changeuser', '切换用户', 18, 2, 0, 1, 1, ''),
(99, '', 'User/editAuthoize', '保存用户认证', 18, 2, 0, 1, 1, ''),
(100, 'fa fa-cog', 'Update/update', '系统更新', 2, 1, 0, 1, 0, ''),
(101, 'fa fa-telegram', 'System/smssz', '短信管理', 2, 1, 0, 1, 1, ''),
(102, 'fa fa-sellsy', 'Statistics/#', '数据统计', 0, 1, 0, 1, 1, ''),
(103, 'fa fa-sellsy', 'Statistics/index', '财务分析', 102, 1, 0, 1, 1, '');

-- --------------------------------------------------------

--
-- 表的结构 `pay_bankcard`
--

CREATE TABLE `pay_bankcard` (
  `id` int(10) UNSIGNED NOT NULL,
  `userid` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '商户编号',
  `bankname` varchar(100) NOT NULL COMMENT '银行名称',
  `subbranch` varchar(100) NOT NULL COMMENT '支行名称',
  `accountname` varchar(100) NOT NULL COMMENT '开户名',
  `cardnumber` varchar(100) NOT NULL COMMENT '银行卡号',
  `province` varchar(100) NOT NULL COMMENT '所属省',
  `city` varchar(100) NOT NULL COMMENT '所属市',
  `ip` varchar(100) DEFAULT NULL COMMENT '上次修改IP',
  `ipaddress` varchar(300) DEFAULT NULL COMMENT 'IP地址',
  `isdefault` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '是否默认 1是 0 否',
  `updatetime` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '更新时间'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `pay_blockedlog`
--

CREATE TABLE `pay_blockedlog` (
  `id` int(11) NOT NULL,
  `orderid` varchar(100) NOT NULL COMMENT '订单号',
  `userid` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '商户编号',
  `amount` decimal(10,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '冻结金额',
  `thawtime` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '解冻时间',
  `pid` smallint(6) UNSIGNED NOT NULL DEFAULT '0' COMMENT '商户支付通道',
  `createtime` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '状态 1 解冻 0 冻结'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='资金冻结待解冻记录';

-- --------------------------------------------------------

--
-- 表的结构 `pay_browserecord`
--

CREATE TABLE `pay_browserecord` (
  `id` int(10) UNSIGNED NOT NULL,
  `articleid` int(11) NOT NULL DEFAULT '0',
  `userid` int(11) NOT NULL DEFAULT '0',
  `datetime` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `pay_category`
--

CREATE TABLE `pay_category` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `pid` smallint(6) UNSIGNED NOT NULL DEFAULT '0' COMMENT '父级ID',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '状态 1开启 0关闭'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='文章栏目';

--
-- 转存表中的数据 `pay_category`
--

INSERT INTO `pay_category` (`id`, `name`, `pid`, `status`) VALUES
(1, '最新资讯', 0, 1),
(2, '公司新闻', 0, 1),
(3, '公告通知', 0, 1),
(4, '站内公告', 3, 1),
(5, '公司新闻', 3, 1);

-- --------------------------------------------------------

--
-- 表的结构 `pay_channel`
--

CREATE TABLE `pay_channel` (
  `id` smallint(6) UNSIGNED NOT NULL COMMENT '供应商通道ID',
  `code` varchar(200) DEFAULT NULL COMMENT '供应商通道英文编码',
  `title` varchar(200) DEFAULT NULL COMMENT '供应商通道名称',
  `mch_id` varchar(100) DEFAULT NULL COMMENT '商户号',
  `signkey` varchar(500) DEFAULT NULL COMMENT '签文密钥',
  `appid` varchar(100) DEFAULT NULL COMMENT '应用APPID',
  `appsecret` varchar(100) DEFAULT NULL COMMENT '安全密钥',
  `gateway` varchar(300) DEFAULT NULL COMMENT '网关地址',
  `pagereturn` varchar(255) DEFAULT NULL COMMENT '页面跳转网址',
  `serverreturn` varchar(255) DEFAULT NULL COMMENT '服务器通知网址',
  `defaultrate` decimal(10,4) UNSIGNED NOT NULL DEFAULT '0.0000' COMMENT '下家费率',
  `fengding` decimal(10,4) UNSIGNED NOT NULL DEFAULT '0.0000' COMMENT '封顶手续费',
  `rate` decimal(10,4) UNSIGNED NOT NULL DEFAULT '0.0000' COMMENT '银行费率',
  `updatetime` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '上次更改时间',
  `unlockdomain` varchar(100) NOT NULL COMMENT '防封域名',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '状态 1开启 0关闭',
  `paytype` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 微信扫码 2 微信H5 3 支付宝扫码 4 支付宝H5 5网银跳转 6网银直连 7百度钱包 8 QQ钱包 9 京东钱包'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='供应商列表';

--
-- 转存表中的数据 `pay_channel`
--

INSERT INTO `pay_channel` (`id`, `code`, `title`, `mch_id`, `signkey`, `appid`, `appsecret`, `gateway`, `pagereturn`, `serverreturn`, `defaultrate`, `fengding`, `rate`, `updatetime`, `unlockdomain`, `status`, `paytype`) VALUES
(199, 'WxSm', '微信扫码支付', '', '', '', '', '', '', '', '0.0400', '0.0900', '0.0000', 1503846107, '', 1, 1),
(200, 'WxGzh', '微信H5', '', '', 'wxf33668d58442ff6e', '', '', '', '', '0.0000', '0.0000', '0.0000', 1502378687, '', 1, 2),
(201, 'Aliscan', '支付宝扫码', '', '', '', '', '', '', '', '0.0000', '0.0000', '0.0000', 1503857975, '', 1, 3),
(202, 'Aliwap', '支付宝H5', '', '', '', '', '', '', '', '0.0000', '0.0000', '0.0000', 1503857966, '', 1, 4),
(203, 'QQSCAN', 'QQ扫码', '', '', '', '', '', '', '', '0.0050', '0.0000', '0.0000', 1503280494, '', 1, 8);

-- --------------------------------------------------------

--
-- 表的结构 `pay_channel_account`
--

CREATE TABLE `pay_channel_account` (
  `id` smallint(6) UNSIGNED NOT NULL COMMENT '供应商通道账号ID',
  `channel_id` smallint(6) UNSIGNED NOT NULL COMMENT '通道id',
  `mch_id` varchar(100) DEFAULT NULL COMMENT '商户号',
  `signkey` varchar(500) DEFAULT NULL COMMENT '签文密钥',
  `appid` varchar(100) DEFAULT NULL COMMENT '应用APPID',
  `appsecret` varchar(100) DEFAULT NULL COMMENT '安全密钥',
  `defaultrate` decimal(10,4) UNSIGNED NOT NULL DEFAULT '0.0000' COMMENT '下家费率',
  `fengding` decimal(10,4) UNSIGNED NOT NULL DEFAULT '0.0000' COMMENT '封顶手续费',
  `rate` decimal(10,4) UNSIGNED NOT NULL DEFAULT '0.0000' COMMENT '银行费率',
  `updatetime` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '上次更改时间',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '状态 1开启 0关闭',
  `title` varchar(100) DEFAULT NULL COMMENT '账户标题',
  `weight` tinyint(2) DEFAULT NULL COMMENT '轮询权重',
  `custom_rate` tinyint(1) DEFAULT NULL COMMENT '是否自定义费率'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='供应商账号列表';

--
-- 转存表中的数据 `pay_channel_account`
--

INSERT INTO `pay_channel_account` (`id`, `channel_id`, `mch_id`, `signkey`, `appid`, `appsecret`, `defaultrate`, `fengding`, `rate`, `updatetime`, `status`, `title`, `weight`, `custom_rate`) VALUES
(218, 199, '', '', '', '', '0.0400', '0.0900', '0.0000', 1513408073, 1, '微信扫码支付', 1, 0),
(219, 200, '', '', 'wxf33668d58442ff6e', '', '0.0000', '0.0000', '0.0000', 1513408073, 1, '微信H5', 1, 0),
(220, 201, '', '', '', '', '0.0000', '0.0000', '0.0000', 1513408073, 1, '支付宝扫码', 1, 0),
(221, 202, '', '', '', '', '0.0000', '0.0000', '0.0000', 1513408073, 1, '支付宝H5', 1, 0),
(222, 203, '', '', '', '', '0.0050', '0.0000', '0.0000', 1513408073, 1, 'QQ扫码', 1, 0);

-- --------------------------------------------------------

--
-- 表的结构 `pay_email`
--

CREATE TABLE `pay_email` (
  `id` int(11) UNSIGNED NOT NULL,
  `smtp_host` varchar(300) DEFAULT NULL,
  `smtp_port` varchar(300) DEFAULT NULL,
  `smtp_user` varchar(300) DEFAULT NULL,
  `smtp_pass` varchar(300) DEFAULT NULL,
  `smtp_email` varchar(300) DEFAULT NULL,
  `smtp_name` varchar(300) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `pay_email`
--

INSERT INTO `pay_email` (`id`, `smtp_host`, `smtp_port`, `smtp_user`, `smtp_pass`, `smtp_email`, `smtp_name`) VALUES
(1, 'smtpdm.aliyun.com', '465', 'info@mail.tianniu.cc', 'Mailpush123', 'info@mail.tianniu.cc', '知宇聚合API支付系统');

-- --------------------------------------------------------

--
-- 表的结构 `pay_invitecode`
--

CREATE TABLE `pay_invitecode` (
  `id` int(10) UNSIGNED NOT NULL,
  `invitecode` varchar(32) NOT NULL,
  `fmusernameid` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `syusernameid` int(11) NOT NULL DEFAULT '0',
  `regtype` tinyint(1) UNSIGNED NOT NULL DEFAULT '4' COMMENT '用户组 4 普通用户 5 代理商',
  `fbdatetime` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `yxdatetime` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `sydatetime` int(11) UNSIGNED DEFAULT '0',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT '邀请码状态 0 禁用 1 未使用 2 已使用'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `pay_invitecode`
--

INSERT INTO `pay_invitecode` (`id`, `invitecode`, `fmusernameid`, `syusernameid`, `regtype`, `fbdatetime`, `yxdatetime`, `sydatetime`, `status`) VALUES
(12, '97xThB1Cz4OoV7y9OrdKW7HbmMDdeC', 1, 17, 4, 1491146221, 1491148800, 1491146782, 2),
(13, 'XSR1xQqTuBAOflNTaGVJsShJe9ihpj', 1, 18, 4, 1491147225, 1491148800, 1491147268, 2),
(5, 'Icrt3mdyaiwKtR9sEqqL9a43jU4qHI', 1, 7, 4, 1491064074, 1491148800, 1491069586, 2),
(6, 'f2yOFZJcqjxzVYBITX1WKEVQbYycM5', 1, 8, 4, 1491069805, 1491148800, 1491069829, 2),
(7, 'kqkPFdRhF4mHxbuGKnTrTUBOhh8BUR', 1, 9, 4, 1491100066, 1491148800, 1491100145, 2),
(14, '8dCbyzAO1GasJ5Ycqtc4apoLIszGVs', 1, 19, 4, 1491149144, 1491235200, 1491149186, 2),
(61, '9ucguw5j42hmpp9alrw83obs6ew070', 1, 0, 4, 1503653302, 1503739701, 0, 1),
(60, '6fbm325wa702pywe6d0voou70t5lz4', 1, 0, 4, 1503653299, 1503739698, 0, 1),
(28, 'GJhipszCqCRrFQTH5vQvOB3GZnBBb7', 19, 20, 4, 1491235513, 1491321600, 1491235668, 2),
(29, 'gElczzSWWpznTeXFPdyDbxgPNCvZb8', 1, 21, 4, 1491317272, 1491321600, 1491317286, 2),
(30, 'um7KnpqaAapwrMjjqg4R5qh88zvLjZ', 1, 22, 4, 1491319745, 1491321600, 1491319789, 2),
(31, 'ALP9duzS6BpSJgzRevRPY2iqFSxi4v', 1, 23, 4, 1491320343, 1491321600, 1491320387, 2),
(32, 'k7IuU11htdy9AVSNZZFOzRIlSqUVkz', 1, 24, 4, 1491539877, 1491580800, 1491540040, 2),
(33, 'SYMhPSCV2wLORE6ZrClUigccU6LsTp', 24, 25, 4, 1492017189, 1492099200, 1492017227, 2),
(58, 'y93ctdv8p27pq39788fjt6z78724iv', 1, 37, 4, 1503652737, 1503739136, 1503653097, 2),
(43, '1swx1xq9b34kickbmwn3lfbowe1hj5', 1, 26, 4, 1499962964, 1500048000, 1499963193, 2),
(44, 'nolpvni01tv174cupsn24lacuosge9', 1, 27, 4, 1500630828, 1500652800, 1500630876, 2),
(59, '5pzj0lpb9o8nsf557f9ryl83dcgx42', 1, 0, 4, 1503653296, 1503739695, 0, 1),
(57, '6jym8c5eozmjahiky10jt6upb5l0px', 1, 38, 4, 1503652613, 1503739012, 1503653199, 2),
(62, '9q2vrirdz3rym272vs8n5dfnv8m0qa', 1, 39, 4, 1503716035, 1503802434, 1503716302, 2),
(63, 'd4tzgycfnpyktlenpbp7xk4tv22tyq', 1, 2, 4, 1503828843, 1503915242, 1503828960, 2);

-- --------------------------------------------------------

--
-- 表的结构 `pay_inviteconfig`
--

CREATE TABLE `pay_inviteconfig` (
  `id` int(10) UNSIGNED NOT NULL,
  `invitezt` tinyint(1) UNSIGNED DEFAULT '1',
  `invitetype2number` int(11) NOT NULL DEFAULT '20',
  `invitetype2ff` smallint(6) NOT NULL DEFAULT '1',
  `invitetype5number` int(11) NOT NULL DEFAULT '20',
  `invitetype5ff` smallint(6) NOT NULL DEFAULT '1',
  `invitetype6number` int(11) NOT NULL DEFAULT '20',
  `invitetype6ff` smallint(6) NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `pay_inviteconfig`
--

INSERT INTO `pay_inviteconfig` (`id`, `invitezt`, `invitetype2number`, `invitetype2ff`, `invitetype5number`, `invitetype5ff`, `invitetype6number`, `invitetype6ff`) VALUES
(1, 1, 0, 0, 100, 0, 0, 0);

-- --------------------------------------------------------

--
-- 表的结构 `pay_loginrecord`
--

CREATE TABLE `pay_loginrecord` (
  `id` int(10) UNSIGNED NOT NULL,
  `userid` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `logindatetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `loginip` varchar(100) NOT NULL,
  `loginaddress` varchar(300) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `pay_loginrecord`
--

INSERT INTO `pay_loginrecord` (`id`, `userid`, `logindatetime`, `loginip`, `loginaddress`) VALUES
(71, 1, '2017-12-16 07:08:11', '127.0.0.1', '本机地址-');

-- --------------------------------------------------------

--
-- 表的结构 `pay_member`
--

CREATE TABLE `pay_member` (
  `id` int(11) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `password` varchar(32) NOT NULL COMMENT '密码',
  `groupid` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户组',
  `salt` varchar(10) NOT NULL COMMENT '密码随机字符',
  `parentid` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '代理ID',
  `balance` decimal(10,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '可用余额',
  `blockedbalance` decimal(10,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '冻结可用余额',
  `email` varchar(100) NOT NULL,
  `activate` varchar(200) NOT NULL,
  `regdatetime` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `activatedatetime` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `realname` varchar(50) DEFAULT NULL COMMENT '姓名',
  `sex` tinyint(1) NOT NULL DEFAULT '1' COMMENT '性别',
  `birthday` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `sfznumber` varchar(20) DEFAULT NULL,
  `mobile` varchar(15) DEFAULT NULL COMMENT '联系电话',
  `qq` varchar(15) DEFAULT NULL COMMENT 'QQ',
  `address` varchar(200) DEFAULT NULL COMMENT '联系地址',
  `paypassword` varchar(32) DEFAULT NULL COMMENT '支付密码',
  `authorized` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '1 已认证 0 未认证 2 待审核',
  `apidomain` varchar(500) DEFAULT NULL COMMENT '授权访问域名',
  `apikey` varchar(32) NOT NULL COMMENT 'APIKEY',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '状态 1激活 0未激活',
  `receiver` varchar(255) DEFAULT NULL COMMENT '台卡显示的收款人信息'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `pay_member`
--

INSERT INTO `pay_member` (`id`, `username`, `password`, `groupid`, `salt`, `parentid`, `balance`, `blockedbalance`, `email`, `activate`, `regdatetime`, `activatedatetime`, `realname`, `sex`, `birthday`, `sfznumber`, `mobile`, `qq`, `address`, `paypassword`, `authorized`, `apidomain`, `apikey`, `status`, `receiver`) VALUES
(1, 'adminroot', '81b5234976a55e5181f24d9eab8fb697', 1, '', 0, '0.00', '0.00', '', '', 0, 0, '', 1, 0, '', '', '', '', '', 0, '', '', 0, NULL),
(2, 'demouser', 'cd5d02871a843f6534cd72c7eaa15762', 4, '6450', 1, '0.00', '0.00', '', '8f9a2804950df5c2ff9ba1f8a9b64937', 1503828960, 2017, '曹军', 1, 1282838400, '', '', '', '中国', 'e10adc3949ba59abbe56e057f20f883e', 1, NULL, 't4ig5acnpx4fet4zapshjacjd9o4bhbi', 1, NULL);

-- --------------------------------------------------------

--
-- 表的结构 `pay_moneychange`
--

CREATE TABLE `pay_moneychange` (
  `id` int(10) UNSIGNED NOT NULL,
  `userid` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '商户编号',
  `ymoney` decimal(10,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '原金额',
  `money` decimal(15,3) NOT NULL DEFAULT '0.000' COMMENT '变动金额',
  `gmoney` decimal(10,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '变动后金额',
  `datetime` datetime DEFAULT NULL COMMENT '修改时间',
  `transid` varchar(50) DEFAULT NULL COMMENT '交易流水号',
  `tongdao` smallint(6) UNSIGNED DEFAULT '0' COMMENT '支付通道ID',
  `lx` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '类型',
  `tcuserid` int(11) DEFAULT NULL,
  `tcdengji` int(11) DEFAULT NULL,
  `orderid` varchar(50) DEFAULT NULL COMMENT '订单号',
  `contentstr` varchar(255) DEFAULT NULL COMMENT '备注'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `pay_order`
--

CREATE TABLE `pay_order` (
  `id` int(10) UNSIGNED NOT NULL,
  `pay_memberid` varchar(100) NOT NULL COMMENT '商户编号',
  `pay_orderid` varchar(100) NOT NULL COMMENT '系统订单号',
  `pay_amount` decimal(10,2) UNSIGNED NOT NULL DEFAULT '0.00',
  `pay_poundage` decimal(10,2) UNSIGNED NOT NULL DEFAULT '0.00',
  `pay_actualamount` decimal(10,2) UNSIGNED NOT NULL DEFAULT '0.00',
  `pay_applydate` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '订单创建日期',
  `pay_successdate` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '订单支付成功时间',
  `pay_bankcode` varchar(100) DEFAULT NULL COMMENT '银行编码',
  `pay_notifyurl` varchar(500) NOT NULL COMMENT '商家异步通知地址',
  `pay_callbackurl` varchar(500) NOT NULL COMMENT '商家页面通知地址',
  `pay_bankname` varchar(300) DEFAULT NULL,
  `pay_status` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '订单状态: 0 未支付 1 已支付未返回 2 已支付已返回',
  `pay_productname` varchar(300) DEFAULT NULL COMMENT '商品名称',
  `pay_productnum` varchar(300) DEFAULT NULL COMMENT '商品数量',
  `pay_productdesc` varchar(1000) DEFAULT NULL COMMENT '商品描述',
  `pay_producturl` varchar(500) DEFAULT NULL,
  `pay_tongdao` varchar(50) DEFAULT NULL,
  `pay_zh_tongdao` varchar(50) DEFAULT NULL,
  `pay_tjurl` varchar(1000) DEFAULT NULL,
  `out_trade_id` varchar(50) NOT NULL COMMENT '商户订单号',
  `num` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '已补发次数',
  `memberid` varchar(100) DEFAULT NULL COMMENT '支付渠道商家号',
  `key` varchar(500) DEFAULT NULL COMMENT '支付渠道密钥',
  `account` varchar(100) DEFAULT NULL COMMENT '渠道账号',
  `isdel` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '伪删除订单 1 删除 0 未删',
  `ddlx` int(11) DEFAULT '0',
  `pay_ytongdao` varchar(50) DEFAULT NULL,
  `pay_yzh_tongdao` varchar(50) DEFAULT NULL,
  `xx` smallint(6) UNSIGNED NOT NULL DEFAULT '0',
  `attach` text CHARACTER SET utf8mb4 COMMENT '商家附加字段,原样返回',
  `pay_channel_account` varchar(255) DEFAULT NULL COMMENT '通道账户'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `pay_paylog`
--

CREATE TABLE `pay_paylog` (
  `id` int(11) UNSIGNED NOT NULL,
  `out_trade_no` varchar(50) NOT NULL,
  `result_code` varchar(50) NOT NULL,
  `transaction_id` varchar(50) NOT NULL,
  `fromuser` varchar(50) NOT NULL,
  `time_end` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `total_fee` smallint(6) UNSIGNED NOT NULL DEFAULT '0',
  `payname` varchar(50) NOT NULL,
  `bank_type` varchar(20) DEFAULT NULL,
  `trade_type` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `pay_product`
--

CREATE TABLE `pay_product` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL COMMENT '通道名称',
  `code` varchar(50) NOT NULL COMMENT '通道代码',
  `polling` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '接口模式 0 单独 1 轮询',
  `paytype` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '支付类型 1 微信扫码 2 微信H5 3 支付宝扫码 4 支付宝H5 5 网银跳转 6网银直连  7 百度钱包  8 QQ钱包 9 京东钱包',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '状态',
  `isdisplay` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户端显示 1 显示 0 不显示',
  `channel` smallint(6) UNSIGNED NOT NULL DEFAULT '0' COMMENT '通道ID',
  `weight` text COMMENT '平台默认通道权重'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `pay_product`
--

INSERT INTO `pay_product` (`id`, `name`, `code`, `polling`, `paytype`, `status`, `isdisplay`, `channel`, `weight`) VALUES
(901, '微信公众号', 'WXJSAPI', 0, 2, 1, 1, 0, ''),
(902, '微信扫码支付', 'WXSCAN', 0, 1, 1, 1, 199, ''),
(903, '支付宝扫码支付', 'ALISCAN', 0, 3, 1, 1, 201, ''),
(904, '支付宝手机', 'ALIWAP', 0, 4, 1, 1, 202, ''),
(905, 'QQ手机支付', 'QQWAP', 1, 2, 0, 1, 0, '200:7'),
(907, '网银支付', 'DBANK', 0, 5, 1, 1, 205, ''),
(908, 'QQ扫码支付', 'QSCAN', 0, 8, 0, 0, 203, ''),
(909, '百度钱包', 'BAIDU', 0, 7, 0, 0, 0, ''),
(910, '京东支付', 'JDPAY', 0, 9, 0, 0, 0, '');

-- --------------------------------------------------------

--
-- 表的结构 `pay_product_user`
--

CREATE TABLE `pay_product_user` (
  `id` int(11) UNSIGNED NOT NULL COMMENT ' ',
  `userid` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '商户编号',
  `pid` smallint(6) UNSIGNED NOT NULL DEFAULT '0' COMMENT '商户通道ID',
  `polling` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '接口模式：0 单独 1 轮询',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '通道状态 0 关闭 1 启用',
  `channel` smallint(6) UNSIGNED NOT NULL DEFAULT '0' COMMENT '指定单独通道ID',
  `weight` varchar(255) DEFAULT NULL COMMENT '通道权重'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `pay_product_user`
--

INSERT INTO `pay_product_user` (`id`, `userid`, `pid`, `polling`, `status`, `channel`, `weight`) VALUES
(14, 2, 907, 0, 1, 205, ''),
(15, 2, 901, 0, 1, 200, ''),
(16, 2, 902, 0, 1, 199, ''),
(17, 2, 903, 0, 1, 201, ''),
(18, 2, 904, 0, 1, 202, ''),
(19, 2, 905, 0, 0, 0, '');

-- --------------------------------------------------------

--
-- 表的结构 `pay_route`
--

CREATE TABLE `pay_route` (
  `id` int(10) UNSIGNED NOT NULL,
  `userid` int(11) NOT NULL,
  `urlstr` varchar(100) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `pay_sms`
--

CREATE TABLE `pay_sms` (
  `id` int(10) UNSIGNED NOT NULL,
  `app_key` varchar(255) DEFAULT NULL COMMENT 'App Key',
  `app_secret` varchar(255) DEFAULT NULL COMMENT 'App Secret',
  `sign_name` varchar(255) DEFAULT NULL COMMENT '默认签名',
  `is_open` int(11) DEFAULT '0' COMMENT '是否开启，0关闭，1开启',
  `admin_mobile` varchar(255) DEFAULT NULL COMMENT '管理员接收手机',
  `is_receive` int(11) DEFAULT '0' COMMENT '是否开启，0关闭，1开启'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `pay_sms_template`
--

CREATE TABLE `pay_sms_template` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `template_code` varchar(255) DEFAULT NULL COMMENT '模板代码',
  `call_index` varchar(255) DEFAULT NULL COMMENT '调用字符串',
  `template_content` text COMMENT '模板内容',
  `ctime` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `pay_sms_template`
--

INSERT INTO `pay_sms_template` (`id`, `title`, `template_code`, `call_index`, `template_content`, `ctime`) VALUES
(3, '修改支付密码', 'SMS_111795375', 'editPayPassword', '您的验证码为：${code} ，你正在进行${opration}操作，该验证码 5 分钟内有效，请勿泄露于他人。', 1512202260),
(4, '修改登录密码', 'SMS_111795375', 'editPassword', '您的验证码为：${code} ，你正在进行${opration}操作，该验证码 5 分钟内有效，请勿泄露于他人。', 1512190115),
(5, '异地登录', 'SMS_111795375', 'loginWarning', '您的账号于${time}登录异常，异常登录地址：${address}，如非本人操纵，请及时修改账号密码。', 1512202260),
(6, '申请结算', 'SMS_111795375', 'clearing', '您的验证码为：${code} ，你正在进行${opration}操作，该验证码 5 分钟内有效，请勿泄露于他人。', 1512202260),
(7, '委托结算', 'SMS_111795375', 'entrusted', '您的验证码为：${code} ，你正在进行${opration}操作，该验证码 5 分钟内有效，请勿泄露于他人。', 1512202260);

-- --------------------------------------------------------

--
-- 表的结构 `pay_systembank`
--

CREATE TABLE `pay_systembank` (
  `id` int(10) UNSIGNED NOT NULL,
  `bankcode` varchar(100) DEFAULT NULL,
  `bankname` varchar(300) DEFAULT NULL,
  `images` varchar(300) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='结算银行';

--
-- 转存表中的数据 `pay_systembank`
--

INSERT INTO `pay_systembank` (`id`, `bankcode`, `bankname`, `images`) VALUES
(162, 'BOB', '北京银行', 'BOB.gif'),
(164, 'BEA', '东亚银行', 'BEA.gif'),
(165, 'ICBC', '中国工商银行', 'ICBC.gif'),
(166, 'CEB', '中国光大银行', 'CEB.gif'),
(167, 'GDB', '广发银行', 'GDB.gif'),
(168, 'HXB', '华夏银行', 'HXB.gif'),
(169, 'CCB', '中国建设银行', 'CCB.gif'),
(170, 'BCM', '交通银行', 'BCM.gif'),
(171, 'CMSB', '中国民生银行', 'CMSB.gif'),
(172, 'NJCB', '南京银行', 'NJCB.gif'),
(173, 'NBCB', '宁波银行', 'NBCB.gif'),
(174, 'ABC', '中国农业银行', '5414c87492ad8.gif'),
(175, 'PAB', '平安银行', '5414c0929a632.gif'),
(176, 'BOS', '上海银行', 'BOS.gif'),
(177, 'SPDB', '上海浦东发展银行', 'SPDB.gif'),
(178, 'SDB', '深圳发展银行', 'SDB.gif'),
(179, 'CIB', '兴业银行', 'CIB.gif'),
(180, 'PSBC', '中国邮政储蓄银行', 'PSBC.gif'),
(181, 'CMBC', '招商银行', 'CMBC.gif'),
(182, 'CZB', '浙商银行', 'CZB.gif'),
(183, 'BOC', '中国银行', 'BOC.gif'),
(184, 'CNCB', '中信银行', 'CNCB.gif'),
(193, 'ALIPAY', '支付宝', '58b83a5820644.jpg'),
(194, 'WXZF', '微信支付', '58b83a757a298.jpg');

-- --------------------------------------------------------

--
-- 表的结构 `pay_tikuanconfig`
--

CREATE TABLE `pay_tikuanconfig` (
  `id` int(10) UNSIGNED NOT NULL,
  `userid` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '商户编号',
  `tkzxmoney` decimal(10,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '单笔最小提款金额',
  `tkzdmoney` decimal(10,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '单笔最大提款金额',
  `dayzdmoney` decimal(10,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '当日提款最大总金额',
  `dayzdnum` int(11) NOT NULL DEFAULT '0' COMMENT '当日提款最大次数',
  `t1zt` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'T+1 ：1开启 0 关闭',
  `t0zt` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'T+0 ：1开启 0 关闭',
  `gmt0` decimal(10,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '购买T0',
  `tkzt` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '提款设置 1 开启 0 关闭',
  `tktype` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '提款手续费类型 1 每笔 0 比例 ',
  `systemxz` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '0 系统规则 1 用户规则',
  `sxfrate` varchar(20) DEFAULT NULL COMMENT '单笔提款比例',
  `sxffixed` varchar(20) DEFAULT NULL COMMENT '单笔提款手续费',
  `issystem` tinyint(1) UNSIGNED DEFAULT '0' COMMENT '平台规则 1 是 0 否',
  `allowstart` tinyint(2) UNSIGNED NOT NULL DEFAULT '0' COMMENT '提款允许开始时间',
  `allowend` tinyint(2) UNSIGNED NOT NULL DEFAULT '0' COMMENT '提款允许结束时间'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `pay_tikuanconfig`
--

INSERT INTO `pay_tikuanconfig` (`id`, `userid`, `tkzxmoney`, `tkzdmoney`, `dayzdmoney`, `dayzdnum`, `t1zt`, `t0zt`, `gmt0`, `tkzt`, `tktype`, `systemxz`, `sxfrate`, `sxffixed`, `issystem`, `allowstart`, `allowend`) VALUES
(28, 1, '50000.00', '100000.00', '0.00', 3, 1, 0, '200.00', 1, 1, 0, '2', '5', 1, 0, 0);

-- --------------------------------------------------------

--
-- 表的结构 `pay_tikuanholiday`
--

CREATE TABLE `pay_tikuanholiday` (
  `id` int(10) UNSIGNED NOT NULL,
  `datetime` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '排除日期'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='排除节假日';

--
-- 转存表中的数据 `pay_tikuanholiday`
--

INSERT INTO `pay_tikuanholiday` (`id`, `datetime`) VALUES
(5, 1503676800),
(6, 1503763200),
(8, 1504281600),
(9, 1504368000);

-- --------------------------------------------------------

--
-- 表的结构 `pay_tikuanmoney`
--

CREATE TABLE `pay_tikuanmoney` (
  `id` int(10) UNSIGNED NOT NULL,
  `userid` int(11) NOT NULL DEFAULT '0' COMMENT '结算用户ID',
  `websiteid` int(11) NOT NULL DEFAULT '0',
  `payapiid` smallint(6) UNSIGNED NOT NULL DEFAULT '0' COMMENT '结算通道ID',
  `t` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '结算方式: 1 T+1 ,0 T+0',
  `money` decimal(10,2) UNSIGNED NOT NULL DEFAULT '0.00',
  `datetype` varchar(2) NOT NULL,
  `createtime` int(11) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `pay_tikuantime`
--

CREATE TABLE `pay_tikuantime` (
  `id` int(10) UNSIGNED NOT NULL,
  `baiks` tinyint(2) UNSIGNED DEFAULT '0' COMMENT '白天提款开始时间',
  `baijs` tinyint(2) UNSIGNED DEFAULT '0' COMMENT '白天提款结束时间',
  `wanks` tinyint(2) UNSIGNED DEFAULT '0' COMMENT '晚间提款开始时间',
  `wanjs` int(11) DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='提款时间';

--
-- 转存表中的数据 `pay_tikuantime`
--

INSERT INTO `pay_tikuantime` (`id`, `baiks`, `baijs`, `wanks`, `wanjs`) VALUES
(1, 24, 17, 18, 24);

-- --------------------------------------------------------

--
-- 表的结构 `pay_tklist`
--

CREATE TABLE `pay_tklist` (
  `id` int(10) UNSIGNED NOT NULL,
  `userid` int(11) NOT NULL,
  `bankname` varchar(300) NOT NULL,
  `bankzhiname` varchar(300) NOT NULL,
  `banknumber` varchar(300) NOT NULL,
  `bankfullname` varchar(300) NOT NULL,
  `sheng` varchar(300) NOT NULL,
  `shi` varchar(300) NOT NULL,
  `sqdatetime` datetime DEFAULT NULL,
  `cldatetime` datetime DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `tkmoney` decimal(10,2) NOT NULL DEFAULT '0.00',
  `sxfmoney` decimal(10,2) UNSIGNED NOT NULL DEFAULT '0.00',
  `money` decimal(10,2) UNSIGNED NOT NULL DEFAULT '0.00',
  `t` int(4) NOT NULL DEFAULT '1',
  `payapiid` int(11) NOT NULL DEFAULT '0',
  `memo` text COMMENT '备注'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `pay_updatelog`
--

CREATE TABLE `pay_updatelog` (
  `version` varchar(20) NOT NULL,
  `lastupdate` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `pay_userrate`
--

CREATE TABLE `pay_userrate` (
  `id` int(10) UNSIGNED NOT NULL,
  `userid` int(11) NOT NULL,
  `payapiid` smallint(6) UNSIGNED NOT NULL DEFAULT '0' COMMENT '通道ID',
  `feilv` decimal(10,4) UNSIGNED NOT NULL DEFAULT '0.0000' COMMENT '运营费率',
  `fengding` decimal(10,4) UNSIGNED NOT NULL DEFAULT '0.0000' COMMENT '封顶费率'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='商户通道费率';

--
-- 转存表中的数据 `pay_userrate`
--

INSERT INTO `pay_userrate` (`id`, `userid`, `payapiid`, `feilv`, `fengding`) VALUES
(21, 2, 907, '0.0080', '0.0150'),
(22, 2, 901, '0.0050', '0.0080'),
(23, 2, 902, '0.0050', '0.0080');

-- --------------------------------------------------------

--
-- 表的结构 `pay_user_code`
--

CREATE TABLE `pay_user_code` (
  `id` int(10) UNSIGNED NOT NULL,
  `type` int(11) DEFAULT '0' COMMENT '0找回密码',
  `code` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `status` int(11) DEFAULT '0',
  `ctime` int(11) DEFAULT NULL,
  `uptime` int(11) DEFAULT NULL COMMENT '更新时间',
  `endtime` int(11) DEFAULT NULL COMMENT '有效时间'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `pay_websiteconfig`
--

CREATE TABLE `pay_websiteconfig` (
  `id` int(10) UNSIGNED NOT NULL,
  `websitename` varchar(300) DEFAULT NULL COMMENT '网站名称',
  `domain` varchar(300) DEFAULT NULL COMMENT '网址',
  `email` varchar(100) DEFAULT NULL,
  `tel` varchar(30) DEFAULT NULL,
  `qq` varchar(30) DEFAULT NULL,
  `directory` varchar(100) DEFAULT NULL COMMENT '后台目录名称',
  `icp` varchar(100) DEFAULT NULL,
  `tongji` varchar(1000) DEFAULT NULL COMMENT '统计',
  `login` varchar(100) DEFAULT NULL COMMENT '登录地址',
  `payingservice` tinyint(1) UNSIGNED DEFAULT '0' COMMENT '商户代付 1 开启 0 关闭',
  `authorized` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '商户认证 1 开启 0 关闭',
  `invitecode` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '邀请码注册',
  `company` varchar(200) DEFAULT NULL COMMENT '公司名称',
  `serverkey` varchar(50) DEFAULT NULL COMMENT '授权服务key',
  `withdraw` tinyint(1) DEFAULT '0' COMMENT '提现通知：0关闭，1开启'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `pay_websiteconfig`
--

INSERT INTO `pay_websiteconfig` (`id`, `websitename`, `domain`, `email`, `tel`, `qq`, `directory`, `icp`, `tongji`, `login`, `payingservice`, `authorized`, `invitecode`, `company`, `serverkey`, `withdraw`) VALUES
(1, '知宇聚合API支付系统', 'www.payv49.loc', 'support@pay.com', '4001234456', '', 'manage', '沪ICP备12031756号', '', 'pay9', 0, 1, 1, '', '0d6de302cbc615de3b09463acea87662', 0);

-- --------------------------------------------------------

--
-- 表的结构 `pay_wttklist`
--

CREATE TABLE `pay_wttklist` (
  `id` int(10) UNSIGNED NOT NULL,
  `userid` int(11) NOT NULL,
  `bankname` varchar(300) NOT NULL,
  `bankzhiname` varchar(300) NOT NULL,
  `banknumber` varchar(300) NOT NULL,
  `bankfullname` varchar(300) NOT NULL,
  `sheng` varchar(300) NOT NULL,
  `shi` varchar(300) NOT NULL,
  `sqdatetime` datetime DEFAULT NULL,
  `cldatetime` datetime DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `tkmoney` decimal(10,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '提款金额',
  `sxfmoney` decimal(10,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '手续费',
  `money` decimal(10,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '实际到账',
  `t` int(4) NOT NULL DEFAULT '1',
  `payapiid` int(11) NOT NULL DEFAULT '0',
  `memo` text COMMENT '备注'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pay_admin`
--
ALTER TABLE `pay_admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay_apimoney`
--
ALTER TABLE `pay_apimoney`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay_article`
--
ALTER TABLE `pay_article`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay_attachment`
--
ALTER TABLE `pay_attachment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay_auth_group`
--
ALTER TABLE `pay_auth_group`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay_auth_group_access`
--
ALTER TABLE `pay_auth_group_access`
  ADD UNIQUE KEY `uid_group_id` (`uid`,`group_id`),
  ADD KEY `uid` (`uid`),
  ADD KEY `group_id` (`group_id`);

--
-- Indexes for table `pay_auth_rule`
--
ALTER TABLE `pay_auth_rule`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay_bankcard`
--
ALTER TABLE `pay_bankcard`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IND_UID` (`userid`);

--
-- Indexes for table `pay_blockedlog`
--
ALTER TABLE `pay_blockedlog`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay_browserecord`
--
ALTER TABLE `pay_browserecord`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay_category`
--
ALTER TABLE `pay_category`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay_channel`
--
ALTER TABLE `pay_channel`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay_channel_account`
--
ALTER TABLE `pay_channel_account`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay_email`
--
ALTER TABLE `pay_email`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay_invitecode`
--
ALTER TABLE `pay_invitecode`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invitecode` (`invitecode`) USING BTREE;

--
-- Indexes for table `pay_inviteconfig`
--
ALTER TABLE `pay_inviteconfig`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay_loginrecord`
--
ALTER TABLE `pay_loginrecord`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay_member`
--
ALTER TABLE `pay_member`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay_moneychange`
--
ALTER TABLE `pay_moneychange`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay_order`
--
ALTER TABLE `pay_order`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `IND_ORD` (`pay_orderid`);

--
-- Indexes for table `pay_paylog`
--
ALTER TABLE `pay_paylog`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `IND_TRD` (`transaction_id`),
  ADD UNIQUE KEY `IND_ORD` (`out_trade_no`);

--
-- Indexes for table `pay_product`
--
ALTER TABLE `pay_product`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay_product_user`
--
ALTER TABLE `pay_product_user`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay_route`
--
ALTER TABLE `pay_route`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay_sms`
--
ALTER TABLE `pay_sms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay_sms_template`
--
ALTER TABLE `pay_sms_template`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay_systembank`
--
ALTER TABLE `pay_systembank`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay_tikuanconfig`
--
ALTER TABLE `pay_tikuanconfig`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `IND_UID` (`userid`);

--
-- Indexes for table `pay_tikuanholiday`
--
ALTER TABLE `pay_tikuanholiday`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay_tikuanmoney`
--
ALTER TABLE `pay_tikuanmoney`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay_tikuantime`
--
ALTER TABLE `pay_tikuantime`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay_tklist`
--
ALTER TABLE `pay_tklist`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay_userrate`
--
ALTER TABLE `pay_userrate`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay_user_code`
--
ALTER TABLE `pay_user_code`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay_websiteconfig`
--
ALTER TABLE `pay_websiteconfig`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay_wttklist`
--
ALTER TABLE `pay_wttklist`
  ADD PRIMARY KEY (`id`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `pay_admin`
--
ALTER TABLE `pay_admin`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '管理员ID', AUTO_INCREMENT=2;
--
-- 使用表AUTO_INCREMENT `pay_apimoney`
--
ALTER TABLE `pay_apimoney`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
--
-- 使用表AUTO_INCREMENT `pay_article`
--
ALTER TABLE `pay_article`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
--
-- 使用表AUTO_INCREMENT `pay_attachment`
--
ALTER TABLE `pay_attachment`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;
--
-- 使用表AUTO_INCREMENT `pay_auth_group`
--
ALTER TABLE `pay_auth_group`
  MODIFY `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- 使用表AUTO_INCREMENT `pay_auth_rule`
--
ALTER TABLE `pay_auth_rule`
  MODIFY `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;
--
-- 使用表AUTO_INCREMENT `pay_bankcard`
--
ALTER TABLE `pay_bankcard`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;
--
-- 使用表AUTO_INCREMENT `pay_blockedlog`
--
ALTER TABLE `pay_blockedlog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;
--
-- 使用表AUTO_INCREMENT `pay_browserecord`
--
ALTER TABLE `pay_browserecord`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;
--
-- 使用表AUTO_INCREMENT `pay_category`
--
ALTER TABLE `pay_category`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- 使用表AUTO_INCREMENT `pay_channel`
--
ALTER TABLE `pay_channel`
  MODIFY `id` smallint(6) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '供应商通道ID', AUTO_INCREMENT=207;
--
-- 使用表AUTO_INCREMENT `pay_channel_account`
--
ALTER TABLE `pay_channel_account`
  MODIFY `id` smallint(6) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '供应商通道账号ID', AUTO_INCREMENT=223;
--
-- 使用表AUTO_INCREMENT `pay_email`
--
ALTER TABLE `pay_email`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- 使用表AUTO_INCREMENT `pay_invitecode`
--
ALTER TABLE `pay_invitecode`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;
--
-- 使用表AUTO_INCREMENT `pay_inviteconfig`
--
ALTER TABLE `pay_inviteconfig`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- 使用表AUTO_INCREMENT `pay_loginrecord`
--
ALTER TABLE `pay_loginrecord`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;
--
-- 使用表AUTO_INCREMENT `pay_member`
--
ALTER TABLE `pay_member`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- 使用表AUTO_INCREMENT `pay_moneychange`
--
ALTER TABLE `pay_moneychange`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;
--
-- 使用表AUTO_INCREMENT `pay_order`
--
ALTER TABLE `pay_order`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=167;
--
-- 使用表AUTO_INCREMENT `pay_paylog`
--
ALTER TABLE `pay_paylog`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- 使用表AUTO_INCREMENT `pay_product`
--
ALTER TABLE `pay_product`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=911;
--
-- 使用表AUTO_INCREMENT `pay_product_user`
--
ALTER TABLE `pay_product_user`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT ' ', AUTO_INCREMENT=20;
--
-- 使用表AUTO_INCREMENT `pay_route`
--
ALTER TABLE `pay_route`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- 使用表AUTO_INCREMENT `pay_sms`
--
ALTER TABLE `pay_sms`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- 使用表AUTO_INCREMENT `pay_sms_template`
--
ALTER TABLE `pay_sms_template`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
--
-- 使用表AUTO_INCREMENT `pay_systembank`
--
ALTER TABLE `pay_systembank`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=198;
--
-- 使用表AUTO_INCREMENT `pay_tikuanconfig`
--
ALTER TABLE `pay_tikuanconfig`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;
--
-- 使用表AUTO_INCREMENT `pay_tikuanholiday`
--
ALTER TABLE `pay_tikuanholiday`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
--
-- 使用表AUTO_INCREMENT `pay_tikuanmoney`
--
ALTER TABLE `pay_tikuanmoney`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=691;
--
-- 使用表AUTO_INCREMENT `pay_tikuantime`
--
ALTER TABLE `pay_tikuantime`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- 使用表AUTO_INCREMENT `pay_tklist`
--
ALTER TABLE `pay_tklist`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;
--
-- 使用表AUTO_INCREMENT `pay_userrate`
--
ALTER TABLE `pay_userrate`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;
--
-- 使用表AUTO_INCREMENT `pay_user_code`
--
ALTER TABLE `pay_user_code`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- 使用表AUTO_INCREMENT `pay_websiteconfig`
--
ALTER TABLE `pay_websiteconfig`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- 使用表AUTO_INCREMENT `pay_wttklist`
--
ALTER TABLE `pay_wttklist`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
