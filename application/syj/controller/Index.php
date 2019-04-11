<?php
namespace app\syj\controller;

use app\syj\model\Syjfankui;
use app\syj\model\Syjlike;
use app\syj\model\Syjthread;
use app\syj\model\Syjuser;
use app\syj\model\Syjpinglun;
use app\syj\model\Syjuid;
use think\Controller;
use think\facade\App;

class Index extends Controller
{
    public $domain    = 'https://api.xf512.com/';
    public $imgpath   = 'syj/img/';
    public $avatarpath   = 'syj/avatar/';
    public $videopath = 'syj/video/';
    // token生成时的加密key
    public $pubkey = 'IQJ3VC4FIwpiBiAAMQUm';

    // appkey，app里签名sign加密盐,生成算法为 md5(/+url+appkey)
    public $appkey = 'IQJ3VC4FIwpiBiAAMQUm';

    /*
    只允许通过
    1、method=post，
    2、api/syj/ce.xf512.com 或 zh.xc8.net 或
    api.duobaokf.com访问接口，
    3、http_auth==，否则非法
    4、http_user_agent 为app(
    不再判断，因为某些容器下禁止重设此值)，
     */
    public function initialize()
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:OPTIONS,GET,POST');
        header('Access-Control-Max-Age:60');
        header('Access-Control-Allow-Headers:x-requested-with,content-type,user-agent,auth,x-agent,Origin');
        header('Content-Type:application/json;charset=utf-8');
        if ($this->request->method() == 'OPTIONS') {
            exit;
        }
        $allow = true;
        // 1、判断访问方法为post
        if ($this->request->method() != 'POST') {
            $allow = false;
        }

        // 2、如果域名不是以下三者之一，则不允许访问
//         if (!in_array(
//             $this->request->host(),
//             array(
//                 'syj.xf512.com',
//                 'api.xf512.com',
//                 'zh.xc8.net',
//                 'api.duobaokf.com',
//                 'ce.xf512.com',
//             )
//         )
//         ) {
//             $allow = false;
//         }
        // 3、判断auth = asdgladsjgs3454adooewauatg454443452
        if ($this->request->server('HTTP_AUTH') != 'asdgladsjgs3454adooewauatg454443452') {
            $allow = false;
        }
        // 4、判断 user-agent
        // if(!preg_match("/zzjxapp/i",$this->request->server('HTTP_USER_AGENT'))){
        //     $allow = false;
        // }

        if (!$allow) {
            echo json_encode(array(
                'code' => 1,
                'msg'  => '当前访问请求非法',
            ));
            exit;
        }
    }
    public function checkupdate()
    {
        $appid = $this->request->param("appid");
        $version = $this->request->param("version"); //客户端版本号
        //默认返回值，不需要升级
        $rsp = array('old'=>array('appid'=>$appid,'version'=>$version),"status" => 0); 
        
        // 返回的是数组array(version=>1.0.3,name=>'xyb-1.0.3.apk')
        $apkurl = getapkurl(App::getRootPath().'public/apk/xyb','^xyb\-(.*?)\.apk$');
        if($apkurl){
            // 存在文件则判断
            $newlast = explode('.',$apkurl['version']);
        }else{
            // 否则不存在则直接返回
            return json($rsp);
        }
        
       
        
        if (isset($appid) && isset($version)) {
            $version = explode('.',$version);
            $isUpdate = 0;
            if($newlast[0] > $version[0]){
                $isUpdate = 1;
            }else if($newlast[1] > $version[1]){
                $isUpdate = 1;
            }else if($newlast[2] > $version[2]){
                $isUpdate = 1;
            }
                if($isUpdate){
                    // 所请求版本号<newlast对应位置的数值
                    $rsp["status"] = 1;
                    $rsp["isUpdate"] = 1;
                    $rsp["note"] = "修复几处bug，优化界面显示，建议立即升级;"; 
                    $rsp["Android"] = "https://api.xf512.com/apk/xyb/xyb-".$apkurl['name']; //应用升级包下载地址
                    $rsp["iOS"] = "http://1"; //应用升级包下载地址
                }
        } 
        return json($rsp);
    }

    /**
     * [fankui description]
     * 接受用户反馈
     * @return [type] [description]
     */
    public function fankui()
    {
        $text   = $this->request->param('text');
        $openid = '';
        $uid = '';
        // app里
        if($this->request->param('shebei')=='app'){
            // 校验后返回结果
            $res = $this->_checksign();
            if(!isset($res['code'])){
                $uid = $res['uid'];
            }
        }else if($this->request->param('shebei')=='wx'){
            // 小程序里
            // 校验后返回结果
            $res = $this->_checksignwx();
            if(!isset($res['code'])){
                $openid = $res['openid'];
            }
        }
        if (!$text) {
            return json(array(
                'code' => 1,
                'msg'  => '请填写建议内容哦！',
            ));
        }
        Syjfankui::create(array(
            'uid'     => 0,
            'addtime' => time(),
            'text'    => $text,
            'openid'  => $openid,
            'uid'  => $uid,
        ));

        return json(array(
            'code' => 0,
            'msg'  => '感谢你的建议！',
        ));
    }
    // 上传投稿时图片
    function upimg(){
        $img   = $this->request->file('image');
        $msg         = 'ok';
        $code = 0;
        $imageurl = '';
        if ($img && $img->checkExt('jpg,jpeg,png,gif')) {
            $imginfo = $img->move($this->imgpath);
            if ($imginfo) {
                $imageurl = $this->domain . $this->imgpath . $imginfo->getSaveName();
            } else {
                $code = 1;
                $msg = '图片上传出错:' . $img->getError();
            }
        }
        return json(array(
            'code'     => $code,
            'msg'      => $msg,
            'imageurl'     => $imageurl,
        ));
    }
    // app里设置头像
    function setavatar(){
        // app里
        $uid = '';
        if($this->request->param('shebei')=='app'){
            // 校验后返回结果
            $res = $this->_checksign();
            if(!isset($res['code'])){
                $uid = $res['uid'];
            }
        }
        $img   = $this->request->file('image');
        $msg         = 'ok';
        $code = 0;
        $imageurl = '';
        if ($img && $img->checkExt('jpg,jpeg,png,gif')) {
            $imginfo = $img->move($this->avatarpath);
            if ($imginfo) {
                // 图片的路径，syj开头，图片名字结尾
                $imgfilepath = $this->avatarpath . $imginfo->getSaveName();
                // 图的url
                $imageurl = $this->domain . $imgfilepath;
                $imageobject = \think\Image::open(App::getRootPath().'public/'.$imgfilepath);
                chmod(App::getRootPath().'public/'.$imgfilepath,0777);
                //将图片裁剪为300x300并保存为crop.png
                $imageobject->crop(100, 100)->save(App::getRootPath().'public/'.$imgfilepath);
                Syjuid::update(array(
                    'id'=>$uid,
                    'avatar'=>$imageurl
                ));
            } else {
                $code = 1;
                $msg = '图片上传出错:' . $img->getError();
            }
        }
        return json(array(
            'code'     => $code,
            'msg'      => $msg,
            'imageurl'     => $imageurl,
        ));
    }
    /**
     * 投稿同时上传单张图片或视频
     * text=文字内容
     * image=图片文件
     * video视频文件
     * openid 微信openid
     *
     */
    public function tougao()
    {
        // 获取表单上传文件 例如上传了001.jpg
        $text  = $this->request->param('text');
        $img   = $this->request->param('image');
        $video = $this->request->file('video');
        if (!$text && !$img && !$video) {
            return json(array(
                'code' => 1,
                'msg'  => '文字、图片、视频三者至少填写其中一项！',
            ));
        }
        $openid = '';
        $uid = '';
        // app里
        if($this->request->param('shebei')=='app'){
            // 校验后返回结果
            $res = $this->_checksign();
            if(!isset($res['code'])){
                $uid = $res['uid'];
            }else{
                return json($res);
            }
        }else if($this->request->param('shebei')=='wx'){
            // 小程序里
            // 校验后返回结果
            $res = $this->_checksignwx();
            if(!isset($res['code'])){
                $openid = $res['openid'];
            }else{
                return json($res);
            }
        }
        $data   = array(
            'addtime' => time(),
            'status'  => 0,
            'img'=>$img?:'',
            'text'=>$text?:'',
            'openid'=>$openid?:'',
            'uid'=>$uid?:'',
        );
        $msg       = '发布成功';  
        $code = 0;      
        if ($video && $video->checkExt('mp4')) {
            $videoinfo = $video->move($this->videopath);
            if ($videoinfo) {
                $data['video'] = $this->domain . $this->videopath . $videoinfo->getSaveName();
            } else {
                $code = 1;
                $msg = '视频上传出错:' . $video->getError();
            }
        }

        Syjthread::create($data);
        $token = '';
        if($uid && !isset($res['code'])){
            $token = $this->gettoken($res);
        }
        return json(array(
            'code'     => $code,
            'msg'      => $msg,
            'data'     => $data,
            'token'     => $token,
        ));
    }
    /**
     * 首页最新和最热数据获取
     * pagesize=每页显示数量
     * page=当前要请求第几页
     * status=0为未审核，1为已审核
     * order=addtime like hate排序字段名
     * by=desc,asc 升降序
     * openid=当前登录用户，用来判断表态
     */
    public function getlist()
    {
        $pagesize = $this->request->param('pagesize');
        $page     = $this->request->param('page') ?: 1;
        $openid   = '';
        $uid = false;
        // app里
        if($this->request->param('shebei')=='app'){
            // 校验后返回结果
            $res = $this->_checksign();
            if(!isset($res['code'])){
                $uid = $res['uid'];
            }
        }else if($this->request->param('shebei')=='wx'){
            // 小程序里
            // 校验后返回结果
            $res = $this->_checksignwx();
            if(!isset($res['code'])){
                $openid = $res['openid'];
            }
        }
        $status   = (int) $this->request->param('status');
        $order    = $this->request->param('order') ?: 'addtime';
        $by       = 'desc';
        $cacheid = 'syj-getlist-'.($openid?$openid:$uid).'-'.$pagesize.'-'.$page.'-'.$status.'-'.$order;
        // if($cache = cache($cacheid)){
            // return $cache;
        // }
        $data     = Syjthread::where(
            array('status' => $status)
        )->order($order,$by)
            ->limit(($page - 1) * $pagesize . ',' . $pagesize)->select();
        // 判断当前用户对每条数据的表态，type=like或hate
        if ($openid || $uid) {

            foreach ($data as $key => $value) {
                $con = $openid?array('tid' => $value->id, 'openid' => $openid):array('tid' => $value->id, 'uid' => $uid);
                if ($liketype = Syjlike::where($con)->field('type')->find()) {
                    $data[$key]['type'] = $liketype['type'];
                }
                $data[$key]['replycount'] = Syjpinglun::where(array('tid'=>$value['id']))->count();
            }
        }
        unset($key);
        unset($value);
        foreach ($data as $key => $value) {
            if(!isset($value['img']) && !isset($value['video'])  && isset($value['text']) && (mb_strlen($value['text'])<20 ||mb_strlen($value['text'])> 500)){
               // unset($data[$key]);
            }
        }
        $token = '';
        // 设置了res且未出错
        if(isset($res) && !isset($res['code'])){
            $token = $this->gettoken($res);
        }
        $data = $data->toArray();
        sort($data);
        $resdata =  json(array('data'=>$data,'code'=>0,'msg'=>'ok','token'=>$token,'page'=>$page,'pagesize'=>$pagesize));
        // cache($cacheid,$resdata,300);
        return $resdata;
    }
    /**
     * 获取最新评论和内容
     * pagesize=每页显示数量
     * page=当前要请求第几页
     * id=当前笑话的id
     * order=addtime like hate排序字段名
     * by=desc,asc 升降序
     * openid=当前登录用户，用来判断表态
     * uid=app当前登录用户，用来判断表态
     */
    public function getpinglun()
    {
        $pagesize = $this->request->param('pagesize');
        $page     = $this->request->param('page') ?: 1;
        // 帖子id
        $id     = $this->request->param('id') ?: 0;
        $openid   = '';
        $order    = $this->request->param('order') ?: 'addtime';
        $by       = 'desc';
        $thread = array();
        $uid = false;
        // app里
        if($this->request->param('shebei')=='app'){
            // 校验后返回结果
            $res = $this->_checksign();
            if(!isset($res['code'])){
                // code不存在说明校验成功
                $uid = $res['uid'];
            }
        }else if($this->request->param('shebei')=='wx'){
            // 小程序里
            // 校验后返回结果
            $res = $this->_checksignwx();
            if(!isset($res['code'])){
                $openid = $res['openid'];
            }
        }
        // 只在第一页获取帖子内容
        if($page<2){
            $thread     = Syjthread::where(
                array('id' => $id)
            )->find();
            // 判断当前用户对数据的表态，type=like或hate
            if ($openid) {
                if ($liketype = Syjlike::where(array('tid' => $id, 'openid' => $openid))->field('type')->find()) {
                    $thread['type'] = $liketype['type'];
                }
            }else if($uid){
                if ($liketype = Syjlike::where(array('tid' => $id, 'uid' => $uid))->field('type')->find()) {
                    $thread['type'] = $liketype['type'];
                }
            }
        }
        if($this->request->param('shebei')=='wx'){
            $pllist=Syjpinglun::where(array('tid' => $id))->alias('p')->leftjoin('syjuser u','p.openid=u.openid')->field('p.*,u.nickName,u.avatar')->order('p.'.$order, $by)->limit(($page - 1) * $pagesize . ',' . $pagesize)->select();
        }elseif($this->request->param('shebei')=='app'){
            $pllist=Syjpinglun::where(array('tid' => $id))->alias('p')->leftjoin('syjuid u','p.uid=u.id')->field('p.*,u.username as nickName,u.avatar')->order('p.'.$order, $by)->limit(($page - 1) * $pagesize . ',' . $pagesize)->select();
        }
        if($pllist){
            foreach ($pllist as $key => $value) {
                $pllist[$key]['pubtime'] = date('Y-m-d H:i',$value['addtime']);
            }
        }
        $token = '';
        if($res && !isset($res['code'])){
            $token = $this->gettoken($res);
        }
        return json(array(
            'thread'=>$thread,
            'replylist'=>$pllist,
            'token'=>$token
        ));
    }
    /**
     * 
     * 发布评论
     * @return [type] [description]
     */
    public function setpinglun()
    {
        $text   = $this->request->param('text');
        $openid = '';
        $uid = false;
        // app里
        if($this->request->param('shebei')=='app'){
            // 校验后返回结果
            $res = $this->_checksign();
            if(isset($res['code'])){
                // code存在说明校验失败，直接返回
                return json($res);
            }else{
                $uid = $res['uid'];
            }
        }else if($this->request->param('shebei')=='wx'){
            // 小程序里
            // 校验后返回结果
            $res = $this->_checksignwx();
            if(!isset($res['code'])){
                $openid = $res['openid'];
            }else{
                return json($res);
            }
        }
        
        // 帖子的id，对应评论表tid
        $id = $this->request->param('id') ?: '';
        if (!$text) {
            return json(array(
                'code' => 1,
                'msg'  => '请填写评论内容哦！',
            ));
        }
        Syjpinglun::create(array(
            'uid'     => 0,
            'tid'=>$id,
            'addtime' => time(),
            'text'    => $text,
            'openid'  => $openid,
            'uid'  => $uid,
            'status' =>0
        ));
        $token = '';
        if($res && !isset($res['code'])){
            $token = $this->gettoken($res);
        }
        return json(array(
            'code' => 0,
            'msg'  => '将在审核后显示！',
            'token'=>$token
        ));
    }
    public function setlike()
    {
        // type = like  hate 喜欢或不喜欢
        // openid = 微信openid
        // id = 文章id
        $type   = $this->request->param('type');
        $openid = '';
        $id     = (int) $this->request->param('id');
        $like   = new Syjlike;
        $thread = new Syjthread;
        $uid = false;
        // app里
        if($this->request->param('shebei')=='app'){
            // 校验后返回结果
            $res = $this->_checksign();
            if(isset($res['code'])){
                // code存在说明校验失败，直接返回
                return json($res);
            }else{
                $uid = $res['uid'];
            }
        }else if($this->request->param('shebei')=='wx'){
            // 小程序里
            // 校验后返回结果
            $res = $this->_checksignwx();
            if(!isset($res['code'])){
                $openid = $res['openid'];
            }else{
                return json($res);
            }
        }
        if($openid){
            $con = array('openid' => $openid, 'tid' => 'id');
        }else if($uid){
            $con = array('uid' => $uid, 'tid' => 'id');
        }
        // 先判断当前用户当前帖子是否已在like表里表态过
        if ($has = $like->where($con)->find()) {
            if ($has['type'] == $type) {
                // 如果已表态的和当前的type相同，则返回
                return json(array(
                    'code' => 0,
                    'msg'  => 'has',
                ));
            } else {
                // 返回更改表态态度
                $like->save(array('type' => $type),$con);
                // 原表态数量减一，新表态加一
                if ($type == 'like') {
                    // 当前是like，原本是hate,则like+1，hate减一
                    $thread->save(
                        array(
                            'like' => array('inc', 1),
                            'hate' => array('dec', 1),
                        ),
                        array('id' => $id)
                    );
                } else {
                    // 当前是hate，原本是like,则hate+1，like减一
                    $thread->save(
                        array(
                            'hate' => array('inc', 1),
                            'like' => array('dec', 1),
                        ),
                        array('id' => $id)
                    );
                }
            }
        } else {
            // 未表态过直接插入
            $like->save(array(
                'addtime' => time(),
                'tid'     => $id,
                'openid'  => $openid,
                'uid'=>$uid,
                'type'    => $type,
            ));
            $thread->save(array($type => array('inc', 1)), array('id' => $id));
        }
        $token = '';
        if($res && !isset($res['code'])){
            $token = $this->gettoken($res);
        }
        return json(array(
            'code' => 0,
            'msg'  => 'ok',
            'token'=>$token
        ));
    }

    /**
     * 我喜欢的
     * pagesize=每页显示数量
     * page=当前要请求第几页
     * openid=当前登录用户，用来判断表态
     */
    public function mylike()
    {
        $pagesize = $this->request->param('pagesize');
        $page     = $this->request->param('page') ?: 1;
        $openid   = '';
        $uid = false;
        // app里
        if($this->request->param('shebei')=='app'){
            // 校验后返回结果
            $res = $this->_checksign();
            if(isset($res['code'])){
                // code存在说明校验失败，直接返回
                return json($res);
            }else{
                $uid = $res['uid'];
            }
        }else if($this->request->param('shebei')=='wx'){
            // 小程序里
            // 校验后返回结果
            $res = $this->_checksignwx();
            if(!isset($res['code'])){
                $openid = $res['openid'];
            }else{
                return json($res);
            }
        }
        if($openid){
            $con = array('la.openid' => $openid, 'type' => 'like');
        }else if($uid){
            $con = array('la.uid' => $uid, 'type' => 'like');
        }
        $data     = Syjlike::where($con)->alias('la')->join('syjthread t', 'la.tid=t.id')->order('la.addtime', 'desc')->field('t.*')
            ->limit(($page - 1) * $pagesize . ',' . $pagesize)->select();
        foreach ($data as $key => $value) {
            $data[$key]['type']='like';
        }
        $token = '';
        if($res && !isset($res['code'])){
            $token = $this->gettoken($res);
        }
        return json(array('data'=>$data,'code'=>0,'msg'=>'ok','token'=>$token));
    }
    /**
     * 我喜欢的
     * pagesize=每页显示数量
     * page=当前要请求第几页
     * openid=当前登录用户，用来判断表态
     * uid app里用户操作11
     */
    public function mytougao()
    {
        $pagesize = $this->request->param('pagesize');
        $page     = $this->request->param('page') ?: 1;
        $openid   = '';
        $uid = false;
        // app里
        if($this->request->param('shebei')=='app'){
            // 校验后返回结果
            $res = $this->_checksign();
            if(isset($res['code'])){
                // code存在说明校验失败，直接返回
                return json($res);
            }else{
                $uid = $res['uid'];
            }
        }else if($this->request->param('shebei')=='wx'){
            // 小程序里
            // 校验后返回结果
            $res = $this->_checksignwx();
            if(!isset($res['code'])){
                $openid = $res['openid'];
            }else{
                return json($res);
            }
        }
        if($openid){
            $con = array('openid' => $openid);
        }else if($uid){
            $con = array('uid' => $uid);
        }
        $data     = Syjthread::where($con)->order('addtime', 'desc')->limit(($page - 1) * $pagesize . ',' . $pagesize)->select();
        $token = '';
        if($res && !isset($res['code'])){
            $token = $this->gettoken($res);
        }
        return json(array('data'=>$data,'code'=>0,'msg'=>'ok','token'=>$token));
    }

    /**
     * 小程序里每次更新换取token
     * openid=微信openid
     * @return [type] [description]
     * 更新微信用户nickname和avatar
     */
    public function login()
    {
        $openid   = '';
        $uid = '';
        // app里
        if($this->request->param('shebei')=='app'){
            // 校验后返回结果
            $res = $this->_checksign();
            if(isset($res['code'])){
                // code存在说明校验失败，直接返回
                return json($res);
            }else{
                return json(array(
                    'code'=>0,
                    'msg'=>'ok',
                    'token'=>$this->gettoken($res)
                ));
            }
        }else if($this->request->param('shebei')=='wx'){
            // 小程序里
            // 校验后返回结果
            $res = $this->_checksignwx();
            if(!isset($res['code'])){
                return json(array(
                    'code'=>0,
                    'msg'=>'ok',
                    'token'=>$this->gettoken($res)
                ));;
            }else{
                return json($res);
            }
        }
    }
    // 小程序里根据code换取openid,并返回小程序里使用的token
    public function getopenid()
    {
        $code      = $this->request->param('code');
        $nickname  = $this->request->param('nickname');
        $avatar = $this->request->param('avatarurl');
        $appid     = 'wxe2c9d8569bb0f917';
        $appsec    = '6206c6063d5acb4c190b86f8ae79ce76';
        $res       = file_get_contents('https://api.weixin.qq.com/sns/jscode2session?appid=' . $appid . '&secret=' . $appsec . '&js_code=' . $code . '&grant_type=authorization_code');
        $suc = json_decode($res, true);
        if (isset($suc['openid'])) {
            if ($has = Syjuser::where(array('openid' => $suc['openid']))->field('id,uid')->find()) {
                $has->save(array(
                    'avatar'   => $avatar,
                    'nickname' => $nickname,
                ),array('id' => $has['id']));
                // 是否绑定，如果绑定，则获取username
                if($has['uid']){
                    $hasuids = Syjuid::where(array('id'=>$has['uid']))->field('username')->find();
                    $suc['username'] = $hasuids['username'];
                }
            } else {
                Syjuser::create(array(
                    'addtime'  => time(),
                    'avatar'   => $avatar,
                    'nickname' => $nickname,
                    'openid'   => $suc['openid'],
                ));
            }
            $suc['token'] = $this->gettoken(array(
                'openid'=>$suc['openid'],
                'exptime'=>time()
            ));
            $suc['code']=0;
        }else{
            $suc['code']=1;
        }
        return json($suc);
    }

    // app或小程序里用户名密码注册,返回的token为app中使用
    // 返回的data中为用户信息
    public function reg()
    {
        $username  = (string) $this->request->param('username');
        $password = (string) $this->request->param('password');
        $openid = (string) $this->request->param('openid');
        if(!$username || !$password){
            return json(array(
                    'msg'=>"必须填写用户名和密码",
                    'code'=>1
            ));
        }
        $has = Syjuid::where(array('username' => $username))->field('id')->count();
        if ($has) {
            return json(array(
                'msg'=>"此用户名已存在",
                'code'=>1
            ));
        } else {
            $zimubiao = 'abcdefghijklmnopqrstuvwxyz0123456789';
            // 生成盐
            $salt = $zimubiao[rand(0,35)].$zimubiao[rand(0,35)].$zimubiao[rand(0,35)].$zimubiao[rand(0,35)];
            // 注册成功返回
            $suc = Syjuid::create(array(
                'addtime'  => time(),
                'avatar'   => '',
                'username' => $username,
                'openid'   => $openid?$openid:'',
                'salt'=>$salt,
                'password'=>md5($salt.$password)
            ));
            // 如果在小程序里注册后，绑定openid
            if($openid){
                $hasuser = Syjuser::where(array('openid'=>$openid))->find();
                if($hasuser){
                    $hasuser->save(array('uid'=>$suc['id']),array('id'=>$hasuser['id']));
                }
            }

        // 注册成功返回token
        // 注册
        $token=$this->gettoken(array(
            'uid'=>$suc['id'],
            'exptime'=>time()
        ));

        return json(array(
                'msg'=>'ok',
                'code'=>0,
                'data'=>array(
                    'username'=>$username,
                    'nickName'=>$username,
                    'password'=>$password,
                    'uid'=>$suc['id'],
                    'token'=>$token
                ),
                'username'=>$username,
                'token'=>$token
            ));
        }
    }
    /**
     * [gettoken description]
     * app可token结构为
     * [
     *     uid=>
     *     expitme
     * ]
     * 小程序中为
     * [
     *     openid=>
     *     exptime=>
     * ]
     * @param  [type] $token [description]
     * @return [type]        [description]
     */
    function gettoken($token){
        // 注册成功返回token
        $token['exptime'] =time()+864000;
        return passport_encrypt(serialize($token),$this->pubkey);
    }
    // 微信中用户名密码绑定或app中使用用户名和密码登录，返回的token为app中使用的，返回的data中为用户信息
    public function loginbyname()
    {
        $username  = (string) $this->request->param('username');
        $password = (string) $this->request->param('password');
        $openid = $this->request->param('openid')?:'';
        if(!$username || !$password){
            return json(array(
                    'msg'=>"必须填写用户名和密码",
                    'code'=>1
            ));
        }
        // 如果不存在，则失败
        $has = Syjuid::where(array(
            'username' => $username)
        )->find();
        if (!$has) {
                return json(array(
                    'msg'=>"此用户名不存在",
                    'code'=>1
                ));
        }
        if($has->password != md5($has->salt.$password)){
            return json(array(
                'msg'=>"用户名或密码错误",
                'code'=>1
            ));
        } 
        
        // 如果在小程序里是绑定用户名
        if($openid){
            // 更新uid表
            $has->save(array(
                'openid'=>$openid
            ),array('id'=>$has['id']));
            // 更新user表
            $openuser = Syjuser::where(array('openid'=>$openid))->find();
            if($openuser){
                $openuser->save(array('uid'=>$has['id']),array('id'=>$openuser['id']));
            }
        }
        
        
        // 登录或绑定成功返回token,app里使用的token
        $token = $this->gettoken(array(
            'uid'=>$has['id'],
            'exptime'=>time()
        ));
        // app和小程序里统一使用nickName显示
        $has['nickName'] = $has['username'];
        return json(array(
                'msg'=>'ok',
                'code'=>0,
                'username'=>$username,
                'token'=>$token,
                'data'=>$has
        ));
    }

    // app里需要鉴权的一个
    public function getinfoapp()
    {
        // 校验后返回结果
        $res = $this->_checksign();
        if(isset($res['code'])){
            // code存在说明校验失败，直接返回
            return json($res);
        }

        
        // 到此校验成功,生成新的token
        // res=>array(
        //      uid,
        //      exptime
        // )
        
        return json(array(
            'msg'=>'ok',
            'code'=>0,
            'token'=>$this->gettoken($res)
        ));
    }

    // app需要登录的接口中请求鉴权
    // app中签名为/ 开头的 url+ $this->appkey 的md5
    public function _checksign()
    {
        $token = $this->request->param('token');
        $sign = $this->request->param('sign');
        $url = $this->request->url();
        // 返回结果，code===0，则校验通过，否则失败
        $result = array();
        // 不存在sign或不对应 , url 以 / 开头
        /*if(!$sign || md5($url.$this->appkey) != $sign){
            $result = array(
                'code'=>1,
                'msg'=>'当前请求无效，请重新登录'
            );
        }else
        */
         if(!$token || strlen($token)<32){
            // 不存在token或太短明显错误
            $result = array(
                'code'=>1,
                'msg'=>'当前登录无效，请重新登录'
            );
        }
        $token = @passport_decrypt($token,$this->pubkey);
        $token = @unserialize($token);
        if(!$token || !isset($token['uid']) || $token['uid']<1){
            // 解密失败或无uid
            $result = array(
                'msg'=>'无效的登录凭证，请重新登录-1',
                'code'=>1
            );
        }else if($token['exptime']<time()){
            // 过期
            $result = array(
                'msg'=>'登录凭证过期，请重新登录-2',
                'code'=>1
            );
        }
        if(isset($result['code'])){
            return $result;
        }
        // 正确
        return $token;
    }
     // 小程序中需要登录的接口中请求鉴权
    // 小程序中中签名为/ 开头的 url+ $this->appkey 的md5
    public function _checksignwx()
    {
        $token = $this->request->param('token');
        $sign = $this->request->param('sign');
        $url = $this->request->url();
        // 返回结果，code===0，则校验通过，否则失败
        $result = array();
        // 不存在sign或不对应 , url 以 / 开头
       /* if(!$sign || md5($url.$this->appkey) != $sign){
            $result = array(
                'code'=>1,
                'msg'=>'当前请求无效，请重新登录'
            );
        }else 
          */
        if(!$token || strlen($token)<32){
            // 不存在token或太短明显错误
            $result = array(
                'code'=>1,
                'msg'=>'当前登录无效，请重新登录'
            );
        }
        $token = @passport_decrypt($token,$this->pubkey);
        $token = @unserialize($token);
        if(!$token || !isset($token['openid']) || strlen($token['openid'])<10){
            // 解密失败或无uid
            $result = array(
                'msg'=>'无效的登录凭证，请重新登录-1',
                'code'=>1
            );
        }
        if(isset($result['code'])){
            return $result;
        }
        // 正确
        return $token;
    }

    // 数组转为字符串
    public function array2string($data, $isformdata = 1)
    {
        if ($data == '') {
            return '';
        }

        if ($isformdata) {
            $data = $this->dstripslashes($data);
        }

        return var_export($data, true);
    }
    // 处理 字符
    public function dstripslashes($string)
    {
        if (!is_array($string)) {
            return stripslashes($string);
        }

        foreach ($string as $key => $val) {
            $string[$key] = $this->dstripslashes($val);
        }

        return $string;
    }
    // 字符串转数组
    public function string2array($data = '')
    {
        if (is_array($data)) {
            return $data;
        }

        if ($data == '' || !isset($data)) {
            return array();
        }

        if (strrchr($data, ')') != ')') {
            return array();
        }

        @eval("\$array = $data;");
        return $array;
    }
}
