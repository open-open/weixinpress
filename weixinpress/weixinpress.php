<?php
/*
Plugin Name: WeixinPress
Plugin URI: http://www.houqun.me/articles/roll-out-weixinpress-plugin-for-wordpress.html
Description: WeixinPress的主要功能就是能够将你的微信公众账号和你的WordPress博客关联，搜索和用户发送关键字匹配的文章，依据命令查看最新文章、热门文章和随机文章。<br />
Version: 0.6.1
Author: Will HQ
Author URI: http://www.houqun.me
*/

//define constants.  
define('WXP_TOKEN'                   , 'wxp_token');
define('WXP_WELCOME'                 , 'wxp_welcome');
define('WXP_WELCOME_CMD'             , 'wxp_welcome_cmd');
define('WXP_HELP'                    , 'wxp_help');
define('WXP_HELP_CMD'                , 'wxp_help_cmd');
define('WXP_KEYWORD_LENGTH'          , 'wxp_keyword_length');
define('WXP_AUTO_REPLY'              , 'wxp_auto_reply');
define('WXP_KEYWORD_LENGTH_WARNING'  , 'wxp_keyword_length_warning');
define('WXP_KEYWORD_ERROR_WARNING'   , 'wxp_keyword_error_warning');
define('WXP_DEFAULT_ARTICLE_ACCOUNT' , 'wxp_default_article_account');
define('WXP_NEW_ARTICLE_CMD'         , 'wxp_new_article_cmd');
define('WXP_RAND_ARTICLE_CMD'        , 'wxp_rand_article_cmd');
define('WXP_HOT_ARTICLE_CMD'         , 'wxp_hot_article_cmd');
define('WXP_CMD_SEPERATOR'           , 'wxp_cmd_seperator');
define('WXP_DEFAULT_THUMB'           , 'wxp_default_thumb');

//$siteurl = get_option('siteurl');     
define('WXP_FOLDER'                  , dirname(plugin_basename(__FILE__)));
//define('WXP_URL'                   , $siteurl.'/wp-content/plugins/' . WXP_FOLDER);
define('WXP_URL'                     , plugins_url('', __FILE__));
define('WXP_FILE_PATH'               , dirname(__FILE__));
define('WXP_DIR_NAME'                , basename(WXP_FILE_PATH));

//定义微信 Token
$wxp_token = get_option(WXP_TOKEN    , 'weixin');
define('WEIXIN_TOKEN'                , $wxp_token);
//定义默认缩略图
//define('WEIXIN_DEFAULT'            , $siteurl.'/wp-content/themes/Metropro/images/random2/tb'.rand(1, 12).'.jpg');
$wxp_thumb = get_option(WXP_DEFAULT_THUMB);
if(empty($wxp_thumb)){$wxp_thumb = WXP_URL.'/images/tb5.jpg';}
define('WEIXIN_DEFAULT'              , $wxp_thumb);

// Verify the connection to Weixin server with token
// Sometimes, these two functions will cause errors in some php enviroments.
// So these two functions should be disabed in formal plugin releases.
function traceHttp(){
    logger('REMOTE_ADDR: '.$_SERVER['REMOTE_ADDR'].((strpos($_SERVER['REMOTE_ADDR'], '101.226')!==false) ? ' From Weixin' : ' Unkown IP'));
    logger('QUERY_STRING: '.$_SERVER['QUERY_STRING']);
    logger('REQUEST URI: '.$_SERVER['REQUEST_URI']);
    logger('HTTP HOST: '.$_SERVER['HTTP_HOST']);
}

function logger($content){
    file_put_contents(WXP_FILE_PATH."/log.html", date('Y-m-d H:i:s ').$content.'<br>', FILE_APPEND);
}

// Add plugin memu    
add_action('admin_menu','weixinpress_menu');  
function weixinpress_menu() {      
    // Call wordpress core api function to add plugin setting menus.     
    add_menu_page( 
        "WeixinPress",
        "WeixinPress", 
        8,
        __FILE__,
        "weixinpress_optionpage",   
        WXP_URL."/images/weixin.png"
    ); 
    // Add sub menu page
    // add_submenu_page(__FILE__,'网站列表','网站列表','8','list-site','pro_admin_list_site'); 
}   

// Get setting options from database
function get_weixinpress_option(){
    $array_weixinpress_option = array();
    $array_weixinpress_option[WXP_TOKEN] = stripslashes(get_option(WXP_TOKEN));
    $array_weixinpress_option[WXP_WELCOME] = stripslashes(get_option(WXP_WELCOME));
    $array_weixinpress_option[WXP_WELCOME_CMD] = stripslashes(get_option(WXP_WELCOME_CMD));
    $array_weixinpress_option[WXP_HELP] = stripslashes(get_option(WXP_HELP));
    $array_weixinpress_option[WXP_HELP_CMD] = stripslashes(get_option(WXP_HELP_CMD));
    $array_weixinpress_option[WXP_KEYWORD_LENGTH] = get_option(WXP_KEYWORD_LENGTH);
    $array_weixinpress_option[WXP_AUTO_REPLY] = get_option(WXP_AUTO_REPLY);
    $array_weixinpress_option[WXP_KEYWORD_LENGTH_WARNING] = stripslashes(get_option(WXP_KEYWORD_LENGTH_WARNING));
    $array_weixinpress_option[WXP_KEYWORD_ERROR_WARNING] = stripslashes(get_option(WXP_KEYWORD_ERROR_WARNING));
    $array_weixinpress_option[WXP_DEFAULT_ARTICLE_ACCOUNT] = get_option(WXP_DEFAULT_ARTICLE_ACCOUNT);
    $array_weixinpress_option[WXP_NEW_ARTICLE_CMD] = stripslashes(get_option(WXP_NEW_ARTICLE_CMD));
    $array_weixinpress_option[WXP_RAND_ARTICLE_CMD] = stripslashes(get_option(WXP_RAND_ARTICLE_CMD));
    $array_weixinpress_option[WXP_HOT_ARTICLE_CMD] = stripslashes(get_option(WXP_HOT_ARTICLE_CMD));
    $array_weixinpress_option[WXP_CMD_SEPERATOR] = stripslashes(get_option(WXP_CMD_SEPERATOR));
    $array_weixinpress_option[WXP_DEFAULT_THUMB] = stripslashes(get_option(WXP_DEFAULT_THUMB));
    
    return $array_weixinpress_option;
}

// Set setting options from database
function update_weixinpress_option(){
    if($_POST['action']=='保存设置'){
        update_option(WXP_TOKEN, $_POST['wxp-token']);
        update_option(WXP_WELCOME, $_POST['wxp-welcome']);
        update_option(WXP_WELCOME_CMD, $_POST['wxp-welcome-cmd']);
        update_option(WXP_HELP, $_POST['wxp-help']);
        update_option(WXP_HELP_CMD, $_POST['wxp-help-cmd']);
        update_option(WXP_KEYWORD_LENGTH, $_POST['wxp-keyword-length']);
        $auto_reply = $_POST['wxp-auto-reply'];
        if($auto_reply != 1 ) {$auto_reply = 0;}
        update_option(WXP_AUTO_REPLY, $auto_reply);
        update_option(WXP_KEYWORD_LENGTH_WARNING, $_POST['wxp-keyword-length-warning']);
        update_option(WXP_KEYWORD_ERROR_WARNING, $_POST['wxp-keyword-error-warning']);
        update_option(WXP_DEFAULT_ARTICLE_ACCOUNT, $_POST['wxp-default-article-account']);
        update_option(WXP_NEW_ARTICLE_CMD, $_POST['wxp-new-article-cmd']);
        update_option(WXP_RAND_ARTICLE_CMD, $_POST['wxp-rand-article-cmd']);
        update_option(WXP_HOT_ARTICLE_CMD, $_POST['wxp-hot-article-cmd']);
        update_option(WXP_CMD_SEPERATOR, $_POST['wxp-cmd-seperator']);
        update_option(WXP_DEFAULT_THUMB, $_POST['wxp-default-thumb']);
    }
    weixinpress_topbarmessage('恭喜，更新配置成功');
}

//添加默认配置
function add_weixinpress_option(){
	$defalut_val = array(
		WXP_TOKEN => uniqid(),
		WXP_URL_STR => 'weixinpress',
		WXP_WELCOME => '欢迎关注小站，更多精彩内容，可通过发送关键字获取！如：
发送“首页”，将获取首页文章
发送“帮助”或“help”，查看帮助信息
发送“最新文章”，将获取最新文章
发送“最热文章”，将获取最热门的文章
发送“随机文章”，将获取随机选取的文章发送',
		WXP_WELCOME_CMD => '欢迎 welcome',
		WXP_HELP => '非常感谢关注小站，可通过发送关键字获取精彩内容！如：
发送“首页”，将获取首页文章
发送“帮助”或“help”，查看帮助信息
发送“最新文章”或“new”，将获取最新文章
发送“最热文章”或“hot”，将获取最热门的文章
发送“随机文章”或“rand”，将获取随机选取的文章发送',
		WXP_HELP_CMD => '帮助 help',
		WXP_KEYWORD_LENGTH => '15',
		WXP_AUTO_REPLY => 0,
		WXP_KEYWORD_LENGTH_WARNING => '',
		WXP_KEYWORD_ERROR_WARNING => '你输入的关键字未匹配到任何内容，可以换其他关键词试试哦，如：
发送“首页”，将获取首页文章
发送“帮助”或“help”，查看帮助信息
发送“最新文章”，将获取最新文章
发送“最热文章”，将获取最热门的文章
发送“随机文章”，将获取随机选取的文章发送',
		WXP_DEFAULT_ARTICLE_ACCOUNT => 10,
		WXP_NEW_ARTICLE_CMD => '最新文章 new',
		WXP_RAND_ARTICLE_CMD => '随机文章 rand',
		WXP_HOT_ARTICLE_CMD => '最热文章 hot',
		WXP_CMD_SEPERATOR => '@',
		WXP_DEFAULT_THUMB => '',
	);
	$options = get_weixinpress_option();
	update_option(WXP_TOKEN, !empty($options[WXP_TOKEN])?$options[WXP_TOKEN]:$defalut_val[WXP_TOKEN]);
//	update_option(WXP_URL_STR, $defalut_val[WXP_URL_STR]);
	update_option(WXP_WELCOME, !empty($options[WXP_WELCOME])?$options[WXP_WELCOME]:$defalut_val[WXP_WELCOME]);
	update_option(WXP_WELCOME_CMD, !empty($options[WXP_WELCOME_CMD])?$options[WXP_WELCOME_CMD]:$defalut_val[WXP_WELCOME_CMD]);
	update_option(WXP_HELP, !empty($options[WXP_HELP])?$options[WXP_HELP]:$defalut_val[WXP_HELP]);
	update_option(WXP_HELP_CMD, !empty($options[WXP_HELP_CMD])?$options[WXP_HELP_CMD]:$defalut_val[WXP_HELP_CMD]);
	update_option(WXP_KEYWORD_LENGTH, !empty($options[WXP_KEYWORD_LENGTH])?$options[WXP_KEYWORD_LENGTH]:$defalut_val[WXP_KEYWORD_LENGTH]);
	update_option(WXP_AUTO_REPLY, !empty($options[WXP_AUTO_REPLY])?$options[WXP_AUTO_REPLY]:$defalut_val[WXP_AUTO_REPLY]);
	update_option(WXP_KEYWORD_LENGTH_WARNING, !empty($options[WXP_KEYWORD_LENGTH_WARNING])?$options[WXP_KEYWORD_LENGTH_WARNING]:$defalut_val[WXP_KEYWORD_LENGTH_WARNING]);
	update_option(WXP_KEYWORD_ERROR_WARNING, !empty($options[WXP_KEYWORD_ERROR_WARNING])?$options[WXP_KEYWORD_ERROR_WARNING]:$defalut_val[WXP_KEYWORD_ERROR_WARNING]);
	update_option(WXP_DEFAULT_ARTICLE_ACCOUNT, !empty($options[WXP_DEFAULT_ARTICLE_ACCOUNT])?$options[WXP_DEFAULT_ARTICLE_ACCOUNT]:$defalut_val[WXP_DEFAULT_ARTICLE_ACCOUNT]);
	update_option(WXP_NEW_ARTICLE_CMD, !empty($options[WXP_NEW_ARTICLE_CMD])?$options[WXP_NEW_ARTICLE_CMD]:$defalut_val[WXP_NEW_ARTICLE_CMD]);
	update_option(WXP_RAND_ARTICLE_CMD, !empty($options[WXP_RAND_ARTICLE_CMD])?$options[WXP_RAND_ARTICLE_CMD]:$defalut_val[WXP_RAND_ARTICLE_CMD]);
	update_option(WXP_HOT_ARTICLE_CMD, !empty($options[WXP_HOT_ARTICLE_CMD])?$options[WXP_HOT_ARTICLE_CMD]:$defalut_val[WXP_HOT_ARTICLE_CMD]);
	update_option(WXP_CMD_SEPERATOR, !empty($options[WXP_CMD_SEPERATOR])?$options[WXP_CMD_SEPERATOR]:$defalut_val[WXP_CMD_SEPERATOR]);
	update_option(WXP_DEFAULT_THUMB, !empty($options[WXP_DEFAULT_THUMB])?$options[WXP_DEFAULT_THUMB]:$defalut_val[WXP_DEFAULT_THUMB]);
}
register_activation_hook(__FILE__,'add_weixinpress_option');
/*register_deactivation_hook(__FILE__,'delete_weixinpress_option');
//清除默认设置
function delete_weixinpress_option(){
	delete_option(WXP_TOKEN);
	delete_option(WXP_URL_STR);
	delete_option(WXP_WELCOME);
	delete_option(WXP_WELCOME_CMD);
	delete_option(WXP_HELP);
	delete_option(WXP_HELP_CMD);
	delete_option(WXP_KEYWORD_LENGTH);
	delete_option(WXP_AUTO_REPLY);
	delete_option(WXP_KEYWORD_LENGTH_WARNING);
	delete_option(WXP_KEYWORD_ERROR_WARNING);
	delete_option(WXP_DEFAULT_ARTICLE_ACCOUNT);
	delete_option(WXP_NEW_ARTICLE_CMD);
	delete_option(WXP_RAND_ARTICLE_CMD);
	delete_option(WXP_HOT_ARTICLE_CMD);
	delete_option(WXP_CMD_SEPERATOR);
	delete_option(WXP_DEFAULT_THUMB);
}*/

// Custom message bar
function weixinpress_topbarmessage($msg) {
     echo '<div class="updated fade" id="message"><p>' . $msg . '</p></div>';
}

// Plugin setting option page
function weixinpress_optionpage(){

?>
    <style type="text/css">
        h2{
            height:36px;
            line-height: 36px;
        }
        label{
            display: inline-block;
            font-weight: bold;
        }
        textarea{
            width:450px;
            height:80px;
        }
        input{
            width: 450px;
            height: 30px;
        }
        table{
            border: 0px solid #ececec;
        }
        tr{
            margin: 20px 0px;
        }
        .right{
            vertical-align: top;
            padding-top: 10px;
            width:120px;
            text-align: right;
        }
        .left{
            width: 500px;
            padding-left:50px;
            text-align: left;
        }
        .wxp-logo{
            background: url(<?php echo WXP_URL; ?>/images/weixin-big.png) 0px 0px no-repeat;
            background-size: 36px 36px;
            height: 36px;
            width: 36px;
            float: left;
        }
        .wxp-notes{
            margin: 10px 0px 30px 0px;
            display: inline-block;
            width: 450px;
        }
        .wxp-submit-btn{
            height: 30px;
            width: 150px;
            background-color: #21759b;
            font-weight: bold;
            color: #ffffff;
            font-family: "Microsoft YaHei";
        }
        .wxp-center{
            text-align: center;
        }
        .wxp-btn-box{
            margin: 15px 0px;
        }
        .wxp-option-main{
            margin: 5px 0px;
            width: 650px;
            float:left;
        }
        .wxp-option-sidebar{
            width: 100px;
            float:left;
        }
        .sidebar-box{
            border:1px solid #dfdfdf;
            width:200px;
            border-radius: 3px;
            box-shadow: inset 0 1px 0 #fff;
            background-color: #f5f5f5;
        }
        .sidebar-box h3{
            font-size: 15px;
            font-weight: bold;
            padding: 7px 10px;
            margin: 0;
            line-height: 1;
            background-color: #f1f1f1;
            border-bottom-color: #dfdfdf;
            text-shadow: #fff 0 1px 0;
            box-shadow: 0 1px 0 #fff;
        }
        .sidebar-box a{
            padding: 4px;
            display: block;
            padding-left: 25px;
            text-decoration: none;
            border: none;
        }
    </style>

    <div class="wxp-option-container">
        <div class="wxp-header">
            <div class="wxp-logo"></div>
            <h2>WeiXinPress设置</h2>
        </div>
        <?php
        if(isset($_POST['action'])){
            if($_POST['action']=='保存设置'){
                update_weixinpress_option();
            }
        }
        $array_weixinpress_option = get_weixinpress_option();
        ?>
        <div class="wxp-option-main">
            <form name="wxp-options" method="post" action="">
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="right"><label>接口TOKEN：</label></td>
                        <td class="left">
                            <input type="text" name="wxp-token" value="<?php echo $array_weixinpress_option[WXP_TOKEN]; ?>"/>
                            <span class="wxp-notes">填写用于微信（易信）接口的TOKEN，与微信（易信）后台设置一致</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="right"><label>欢迎信息：</label></td>
                        <td class="left">
                            <textarea name="wxp-welcome"><?php echo $array_weixinpress_option[WXP_WELCOME]; ?></textarea>
                            <span class="wxp-notes">填写用于用户订阅时发送的欢迎信息</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="right"><label>欢迎命令：</label></td>
                        <td class="left">
                            <input type="text" name="wxp-welcome-cmd" value="<?php echo $array_weixinpress_option[WXP_WELCOME_CMD]; ?>"/>
                            <span class="wxp-notes">填写用于用户查询问候信息的命令，例如“hi”，“你好”</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="right"><label>帮助信息：</label></td>
                        <td class="left">
                            <textarea name="wxp-help"><?php echo $array_weixinpress_option[WXP_HELP]; ?></textarea>
                            <span class="wxp-notes">填写用于用户寻求帮助时的帮助信息</span>
                        </td>
                    </tr>
                     <tr>
                        <td class="right"><label>帮助命令：</label></td>
                        <td class="left">
                            <input type="text" name="wxp-help-cmd" value="<?php echo $array_weixinpress_option[WXP_HELP_CMD]; ?>"/>
                            <span class="wxp-notes">填写用于用户寻求帮助时命令，例如“帮助”、“help”，持多个命令，中间用空格隔开</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="right"><label>关键字长度：</label></td>
                        <td class="left">
                            <input type="text" name="wxp-keyword-length" value="<?php echo $array_weixinpress_option[WXP_KEYWORD_LENGTH]; ?>"/>
                            <span class="wxp-notes">填写用户输入的关键字长度限制，注意：单个中文字长度为2，单个英文字符或数字长度为1，例如“时间管理”长度填为8，“weixin”长度是6</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="right"><label>是否自动回复：</label></td>
                        <td class="left">
                            <input type="checkbox" name="wxp-auto-reply" value="1" <?php if($array_weixinpress_option[WXP_AUTO_REPLY]){ ?> checked<?php } ?>/><br/>
                            <span class="wxp-notes">当用户输入关键字长度超过限定长度时，是否自动回复消息。默认不勾选，即不自动回复消息，系统认为用户要与公共账号进行人工沟通。</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="right"><label>关键字长度提醒：</label></td>
                        <td class="left">
                            <textarea name="wxp-keyword-length-warning"><?php echo $array_weixinpress_option[WXP_KEYWORD_LENGTH_WARNING]; ?></textarea>
                            <span class="wxp-notes">当用户输入的关键字长度超出限制时，自动回复给用户的错误提示信息，结合上面面“是否自动回复”使用</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="right"><label>关键字错误提醒：</label></td>
                        <td class="left">
                            <textarea name="wxp-keyword-error-warning"><?php echo $array_weixinpress_option[WXP_KEYWORD_ERROR_WARNING]; ?></textarea>
                            <span class="wxp-notes">当使用用户输入的关键字没有查找到相关文章时，自动回复给用户的错误提示信息，信息中用户输入的关键词用”{keyword}“表示</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="right"><label>默认文章数：</label></td>
                        <td class="left">
                            <input type="text" name="wxp-default-article-account" value="<?php echo $array_weixinpress_option[WXP_DEFAULT_ARTICLE_ACCOUNT]; ?>"/>
                            <span class="wxp-notes">填写默认返回的文章数目，即用户不用命令分隔符指定返回数目时返回的文章数目，最大数为10</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="right"><label>最新文章命令：</label></td>
                        <td class="left">
                            <input type="text" name="wxp-new-article-cmd" value="<?php echo $array_weixinpress_option[WXP_NEW_ARTICLE_CMD]; ?>"/>
                            <span class="wxp-notes">填写用户查询最新文章的命令，持多个命令，中间用空格隔开</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="right"><label>随机文章命令：</label></td>
                        <td class="left">
                            <input type="text" name="wxp-rand-article-cmd" value="<?php echo $array_weixinpress_option[WXP_RAND_ARTICLE_CMD]; ?>"/>
                            <span class="wxp-notes">填写用户查询随机文章的命令，支持多个命令，中间用空格隔开</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="right"><label>热门文章命令：</label></td>
                        <td class="left">
                            <input type="text" name="wxp-hot-article-cmd" value="<?php echo $array_weixinpress_option[WXP_HOT_ARTICLE_CMD]; ?>"/>
                            <span class="wxp-notes">填写用户查询随机文章的命令，持多个命令，中间用空格隔开</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="right"><label>命令分隔符：</label></td>
                        <td class="left">
                            <input type="text" name="wxp-cmd-seperator" value="<?php echo $array_weixinpress_option[WXP_CMD_SEPERATOR]; ?>"/>
                            <span class="wxp-notes">填写命令分隔符，即支持使用类似“关键@6”的命令，其中“@”为命令分隔符，后面的数字为返回的文章数，最大为10</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="right"><label>默认缩略图地址：</label></td>
                        <td class="left">
                            <input type="text" name="wxp-default-thumb" value="<?php echo $array_weixinpress_option[WXP_DEFAULT_THUMB]; ?>"/>
                            <span class="wxp-notes">填写默认缩略图地址，当文章中没有图片时，使用该地址代表的图片</span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" class="wxp-center wxp-btn-box">
                            <input type="submit" class="wxp-submit-btn" name="action" value="保存设置"/>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="wxp-option-sidebar">
            <div class="sidebar-box">
                <h3>关于Weixinpress</h3>
                <a href="http://www.houqun.me" target="_blank">古侯子博客</a>
                <a href="http://www.houqun.me/articles/roll-out-weixinpress-plugin-for-wordpress-06.html" target="_blank">查看插件主页</a>
                <a href="http://www.houqun.me/articles/roll-out-weixinpress-plugin-for-wordpress-06.html" target="_blank">报告插件BUG</a>
                <a href="http://me.alipay.com/houqun" target="_blank"><b>赞助本插件</b></a>
            </div>
        </div>
    </div>
<?php 
}

//add_action('pre_get_posts', 'weixinpress_interface', 4);
//add_action('parse_query', 'weixinpress_interface', 4);
add_action('parse_request', 'weixinpress_interface', 4);
function weixinpress_interface($wp_query){
    traceHttp();
    if(isset($_GET["signature"])){
        global $weixinpress;
        if(!isset($weixinpress)){
            $weixinpress = new weixinCallback();
            $weixinpress->valid();
            exit;
        }
    }
}

class weixinCallback
{
    private $items        = '';
    private $articleCount = 0;
    private $keyword      = '';
    private $arg          = '';

    public function valid()
    {
        if(isset($_GET['debug'])){
            $this->keyword = $_GET['t'];
            $this->responseMsg();
        }

        $echoStr = $_GET['echostr'];

        //valid signature , option
        if($this->checkSignature()){
            logger($echoStr);
            echo $echoStr;
            $this->responseMsg();
            exit;
        }
    }

    public function responseMsg()
    {
        //get post data, May be due to the different environments
        $postStr = $GLOBALS['HTTP_RAW_POST_DATA'];

        $array_weixinpress_option      = get_weixinpress_option();
        $array_weixinpress_welcome_cmd = explode(' ', $array_weixinpress_option[WXP_WELCOME_CMD]);
        $array_weixinpress_help_cmd    = explode(' ', $array_weixinpress_option[WXP_HELP_CMD]);
        $array_weixinpress_new_cmd     = explode(' ', $array_weixinpress_option[WXP_NEW_ARTICLE_CMD]);
        $array_weixinpress_rand_cmd    = explode(' ', $array_weixinpress_option[WXP_RAND_ARTICLE_CMD]);
        $array_weixinpress_hot_cmd     = explode(' ', $array_weixinpress_option[WXP_HOT_ARTICLE_CMD]);
        $wxp_keyword_length            = $array_weixinpress_option[WXP_KEYWORD_LENGTH];
        $wxp_auto_reply                = $array_weixinpress_option[WXP_AUTO_REPLY];
        $wxp_keyword_length_warning    = $array_weixinpress_option[WXP_KEYWORD_LENGTH_WARNING];
        $wxp_keyword_error_warning     = $array_weixinpress_option[WXP_KEYWORD_ERROR_WARNING];
        $wxp_cmd_seperator             = $array_weixinpress_option[WXP_CMD_SEPERATOR];

        //extract post data
        if (isset($_GET['debug']) || !empty($postStr)){    
            if(!isset($_GET['debug'])){
                $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
                $fromUsername = $postObj->FromUserName;
                $toUsername = $postObj->ToUserName;
                $msgType = strtolower(trim($postObj->MsgType));
                if($msgType == 'event'){
                    $keywords = strtolower(trim($postObj->Event));
                }else{
                    $keywords = strtolower(trim($postObj->Content));
                }

                //add by HQ
                $keywordArray = explode($wxp_cmd_seperator, $keywords, 2);
                if(is_array($keywordArray)){
                    $this->keyword = $keywordArray[0];
                    $this->arg = $keywordArray[1];
                } else {
                    $this->keyword = $keywordArray;
                }


            }

            $time = time();
            $textTpl = '<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%d</CreateTime>
                        <MsgType><![CDATA[text]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                        <FuncFlag>0</FuncFlag>
                        </xml>';     
            $picTpl = ' <xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%d</CreateTime>
                        <MsgType><![CDATA[news]]></MsgType>
                        <Content><![CDATA[]]></Content>
                        <ArticleCount>%d</ArticleCount>
                        <Articles>
                        %s
                        </Articles>
                        <FuncFlag>1</FuncFlag>
                        </xml>';

            $weixin_custom_keywords = apply_filters('weixin_custom_keywords',array());

            if(in_array($this->keyword, $weixin_custom_keywords)){
                do_action('weinxinpress',$this->keyword,$textTpl, $picTpl);
            }elseif((count($array_weixinpress_welcome_cmd)>0)&&(in_array($this->keyword, $array_weixinpress_welcome_cmd) || $this->keyword == 'subscribe' )){
                // welcome
                $weixin_welcome = $array_weixinpress_option[WXP_WELCOME];
                $weixin_welcome = apply_filters('weixin_welcome',$weixin_welcome);
                echo sprintf($textTpl, $fromUsername, $toUsername, $time, $weixin_welcome);
            }elseif((count($array_weixinpress_welcome_cmd)>0)&&in_array($this->keyword, $array_weixinpress_help_cmd)){
                // give help at the same time
                $weixin_help = $array_weixinpress_option[WXP_HELP];
                $weixin_help = apply_filters('weixin_help',$weixin_help);
                echo sprintf($textTpl, $fromUsername, $toUsername, $time, $weixin_help);
            }elseif((count($array_weixinpress_new_cmd)>0)&&in_array($this->keyword, $array_weixinpress_new_cmd)){
                $this->query('new');
                if($this->articleCount == 0){
                        $weixin_not_found = "抱歉，最新文章显示错误，请重试一下 :-) ";
                        $weixin_not_found = apply_filters('weixin_not_found', $weixin_not_found, $this->keyword);
                        echo sprintf($textTpl, $fromUsername, $toUsername, $time, $weixin_not_found);
                    }else{
                        echo sprintf($picTpl, $fromUsername, $toUsername, $time, $this->articleCount,$this->items);
                }
            }elseif((count($array_weixinpress_rand_cmd)>0)&&in_array($this->keyword, $array_weixinpress_rand_cmd)){
                $this->query('rand');
                if($this->articleCount == 0){
                        $weixin_not_found = "抱歉，随机文章显示错误，请重试一下 :-) ";
                        $weixin_not_found = apply_filters('weixin_not_found', $weixin_not_found, $this->keyword);
                        echo sprintf($textTpl, $fromUsername, $toUsername, $time, $weixin_not_found);
                    }else{
                        echo sprintf($picTpl, $fromUsername, $toUsername, $time, $this->articleCount,$this->items);
                }
            }elseif((count($array_weixinpress_hot_cmd)>0)&&in_array($this->keyword, $array_weixinpress_hot_cmd)){
                $this->query('hot');
                if($this->articleCount == 0){
                        $weixin_not_found = "抱歉，热门文章显示错误，请重试一下 :-) ";
                        $weixin_not_found = apply_filters('weixin_not_found', $weixin_not_found, $this->keyword);
                        echo sprintf($textTpl, $fromUsername, $toUsername, $time, $weixin_not_found);
                    }else{
                        echo sprintf($picTpl, $fromUsername, $toUsername, $time, $this->articleCount,$this->items);
                }
            }else {
                $keyword_length = mb_strwidth(preg_replace('/[\x00-\x7F]/','',$this->keyword),'utf-8')+str_word_count($this->keyword)*2;

                $weixin_keyword_allow_length = $wxp_keyword_length;
                $weixin_keyword_allow_length = apply_filters('weixin_keyword_allow_length',$weixin_keyword_allow_length);
        
                if($keyword_length > $weixin_keyword_allow_length){
                    if($wxp_auto_reply){// if auto reply is set to be true, 
                        //$weixin_keyword_too_long = '输入的关键字太长，换个稍短的关键字试下？';
                        $weixin_keyword_too_long = $wxp_keyword_length_warning;
                        $weixin_keyword_too_long = apply_filters('weixin_keywords_too_long',$weixin_keyword_too_long);
                        echo sprintf($textTpl, $fromUsername, $toUsername, $time, $weixin_keyword_too_long);
                    }
                }elseif( !empty( $this->keyword )){
                    $this->query();
                    if($this->articleCount == 0){
                        //$weixin_not_found = "抱歉，没有找到与【{$this->keyword}】相关的文章，换个关键字，可能就有结果了哦 :-) ";
                        $weixin_not_found = str_replace('{keyword}', $this->keyword, $wxp_keyword_error_warning);
                        $weixin_not_found = apply_filters('weixin_not_found', $weixin_not_found, $this->keyword);
                        echo sprintf($textTpl, $fromUsername, $toUsername, $time, $weixin_not_found);
                    }else{
                        echo sprintf($picTpl, $fromUsername, $toUsername, $time, $this->articleCount,$this->items);
                    }
                }
            }
        }else {
            echo "";
            exit;
        }
    }

    private function query($queryArg = NULL){
        global $wp_query;

        $queryKeyword = $this->keyword;

        $weixin_count = get_option(WXP_DEFAULT_ARTICLE_ACCOUNT);
        
        if(!empty($this->arg)) { 
            if (preg_match("/^\d*$/",$this->arg)){ // if the arg is a number or not, is_numeric($fgid)
                $weixin_count = $this->arg;
            } else { // if the arg is not a number, so we consier XXX@YYY the whole as one keyword, and we use "XXX YYY" instead of "XXX@YYY" to query information.
                $queryKeyword = $this->keyword.' '.$this->arg;
                $this->keyword = $this->keyword.'@'.$this->arg;
            }
        } 

        $weixin_count = apply_filters('weixin_count',$weixin_count);

        switch ($queryArg) {
            case 'new':
                $weixin_query_array = array('showposts' => $weixin_count , 'post_status' => 'publish' );
                break;
            case 'rand':
                $weixin_query_array = array('orderby' => 'rand', 'posts_per_page' => $weixin_count , 'post_status' => 'publish' );
                break;
             case 'hot':
                $weixin_query_array = array('orderby' => 'meta_value_num', 'meta_key'=>'views', 'order'=>'DESC', 'posts_per_page' => $weixin_count , 'post_status' => 'publish' );
                break;
            default:
                $weixin_query_array = array('s' => $queryKeyword, 'posts_per_page' => $weixin_count , 'post_status' => 'publish' );
                break;
        }

        
        $weixin_query_array = apply_filters('weixin_query',$weixin_query_array);

        $wp_query->query($weixin_query_array);

        if(have_posts()){
            while (have_posts()) {
                the_post();

                global $post;

                $title =get_the_title(); 
                $excerpt = get_post_excerpt($post);

                $thumbnail_id = get_post_thumbnail_id($post->ID);
                if($thumbnail_id ){
                    $thumb = wp_get_attachment_image_src($thumbnail_id, 'thumbnail');
                    $thumb = $thumb[0];
                }else{
                    $thumb = get_post_first_image($post->post_content);
                }

                if(!$thumb && WEIXIN_DEFAULT){
                    $thumb = WEIXIN_DEFAULT;
                }

                $link = get_permalink();

                $items = $items . $this->get_item($title, $excerpt, $thumb, $link);

            }
        }

        $this->articleCount = count($wp_query->posts);
        if($this->articleCount > $weixin_count) $this->articleCount = $weixin_count;

        $this->items = $items;
    }

    public function get_item($title, $description, $picUrl, $url){
        if(!$description) $description = $title;

        return
        '
        <item>
            <Title><![CDATA['.$title.']]></Title>
            <Description><![CDATA['.$description.']]></Description>
            <PicUrl><![CDATA['.$picUrl.']]></PicUrl>
            <Url><![CDATA['.$url.']]></Url>
        </item>
        ';
    }

    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];    
                
        $weixin_token = apply_filters('weixin_token',WEIXIN_TOKEN);
        if(isset($_GET['debug'])){
            echo "\n".'WEIXIN_TOKEN：'.$weixin_token;
        }
        $tmpArr = array($weixin_token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        
        if( $tmpStr == $signature ){
            return true;
        }else{
            return true;
        }
    }
}

if(!function_exists('get_post_excerpt')){

    function get_post_excerpt($post){
        $post_excerpt = strip_tags($post->post_excerpt); 
        if(!$post_excerpt){
            $post_excerpt = mb_substr(trim(strip_tags($post->post_content)),0,120);
        }
        return $post_excerpt;
    }
}

if(!function_exists('get_post_first_image')){

    function get_post_first_image($post_content){
        preg_match_all('|<img.*?src=[\'"](.*?)[\'"].*?>|i', $post_content, $matches);
        if($matches){
            return $matches[1][0];
        }else{
            return false;
        }
    }
}

if(!function_exists('search_orderby')){

    add_filter('posts_orderby_request', 'search_orderby');
    function search_orderby($orderby = ''){
        global $wpdb,$wp_query;

        $keyword = stripslashes($wp_query->query_vars[s]);

        if($keyword){ 

            $n = !empty($q['exact']) ? '' : '%';

            preg_match_all('/".*?("|$)|((?<=[\r\n\t ",+])|^)[^\r\n\t ",+]+/', $keyword, $matches);
            $search_terms = array_map('_search_terms_tidy', $matches[0]);

            $case_when = "0";

            foreach( (array) $search_terms as $term ){
                $term = esc_sql( like_escape( $term ) );

                $case_when .=" + (CASE WHEN {$wpdb->posts}.post_title LIKE '{$term}' THEN 3 ELSE 0 END) + (CASE WHEN {$wpdb->posts}.post_title LIKE '{$n}{$term}{$n}' THEN 2 ELSE 0 END) + (CASE WHEN {$wpdb->posts}.post_content LIKE '{$n}{$term}{$n}' THEN 1 ELSE 0 END)";
            }

            return "({$case_when}) DESC, {$wpdb->posts}.post_modified DESC";
        }else{
            return $orderby;
        }
    }
}
?>