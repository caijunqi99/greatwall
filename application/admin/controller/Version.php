<?php

namespace app\admin\controller;

use think\Lang;

/**
 * ============================================================================

 * ============================================================================
 * 控制器
 */
class Version extends AdminControl
{
    public function _initialize()
    {
        parent::_initialize();
        Lang::load(APP_PATH . 'admin/lang/' . config('default_lang') . '/config.lang.php');
    }
    /**
     * @desc 版本管理
     * @author langzhiyao
     * @time 20181120
     */
    public function index(){

        if(session('admin_is_super') !=1 && !in_array(4,$this->action )){
            $this->error(lang('ds_assign_right'));
        }
        $list_count = db('version_update')->where('type=1')->count();
        $list_count2 = db('version_update')->where('type=2')->count();
        $this->assign('list_count',$list_count);
        $this->assign('list_count2',$list_count2);
        $this->setAdminCurItem('index');
        return $this->fetch();

    }

    /**
     * @desc 获取分页数据
     * @author langzhiyao
     * @time 20190929
     */
    public function get_version_list(){
        $type = intval(input('get.type'));
        $where = ' type="'.$type.'" ';
        $page_count = intval(input('post.page_count')) ? intval(input('post.page_count')) : 1000;//每页的条数
        $start = intval(input('post.page')) ? (intval(input('post.page'))-1)*$page_count : 0;//开始页数

//        halt($start);
        //查询
        $list = db('version_update')->where($where)->limit($start,$page_count)->order('time DESC')->select();
        $list_count = db('version_update')->where($where)->count();

        $html = '';
        if(!empty($list)){
            if($type == 1){
                foreach($list as $key=>$value){
                    $html .= '<tr class="hover">';
                    $html .= '<td class="align-center" >'.($key+1).'</td>';
                    $html .= '<td class="align-center">'.$value["content"].'</td>';
                    if($value['mode'] == 1){
                        $html .= '<td class="align-center">建议更新</td>';
                    }else if($value['mode'] == 2){
                        $html .= '<td class="align-center">强制更新</td>';
                    }
                    $html .= '<td class="align-center">'.$value["version_num"].'</td>';
                    $html .= '<td class="align-center">'.$value["channel"].'</td>';
                    $html .= '<td class="align-center">'.$value["package_name"].'</td>';
                    $html .= '<td class="align-center">'.date('Y-m-d H:i',$value["time"]).'</td>';
                    $html .= '</tr>';
                }
            }else{
                foreach($list as $key=>$value){
                    $html .= '<tr class="hover">';
                    $html .= '<td class="align-center" >'.($key+1).'</td>';
                    $html .= '<td class="align-center">'.$value["content"].'</td>';
                    if($value['mode'] == 1){
                        $html .= '<td class="align-center">建议更新</td>';
                    }else if($value['mode'] == 2){
                        $html .= '<td class="align-center">强制更新</td>';
                    }
                    $html .= '<td class="align-center">'.$value["version_num"].'</td>';
                    $html .= '<td class="align-center">'.$value["url"].'</td>';
                    $html .= '<td class="align-center">'.date('Y-m-d H;i',$value["time"]).'</td>';
                    $html .= '</tr>';
                }
            }

        }else{
            $html .= '<tr class="no_data">
                    <td colspan="15">没有符合条件的记录</td>
                </tr>';
        }

        exit(json_encode(array('html'=>$html,'count'=>$list_count)));

    }


    /**
     * @desc Android版本号添加
     * @time 20181121
     * @author langzhiyao
     */
    public function android_version(){
        $channel = db('channel')->select();
        $this->assign('channel',$channel);
        $this->setAdminCurItem('android_version');
        return $this->fetch();
    }

    /**
     * @desc IOS版本号添加
     * @time 20181121
     * @author langzhiyao
     */
    public function ios_version(){
        $this->setAdminCurItem('ios_version');
        return $this->fetch();
    }

    /**
     * @desc 更新版本
     * @author langzhiyao
     * @time 20181121
     */
    public function updateVersion(){
        $type = intval(input('post.type'));
        $version = trim(input('post.version_num'));
        $res = $this->is_version($version,$type);
        if($res){
            if($type == 1){
                $data = array(
                    'type'=>$type,
                    'version_num' => trim(input('post.version_num')),
                    'mode' => intval(input('post.mode')),
                    'url' => trim(input('post.url')),
                    'channel'=>trim(input('post.channel')),
                    'package_name'=>trim(input('post.package_name')),
                    'content'=>trim(input('post.description')),
                    'time'=> time()
                );
                $result = db('version_update')->insert($data);
                if($result){
                    exit(json_encode(array('code'=>200,'msg'=>'Android版本更新成功')));
                }else{
                    exit(json_encode(array('code'=>-1,'msg'=>'Android版本更新失败')));
                }
            }else{
                $data = array(
                    'type'=>$type,
                    'version_num' => trim(input('post.version_num')),
                    'mode' => intval(input('post.mode')),
                    'url'=>trim(input('post.url')),
                    'content'=>trim(input('post.description')),
                    'time'=>time()
                );
                $result = db('version_update')->insert($data);
                if($result){
                    exit(json_encode(array('code'=>200,'msg'=>'IOS版本更新成功')));
                }else{
                    exit(json_encode(array('code'=>-1,'msg'=>'IOS版本更新失败')));
                }
            }
        }else{
            exit(json_encode(array('code'=>-1,'msg'=>'版本号低于原来版本号，无法进行更新')));
        }
    }
    /**
     * @desc 判断版本号
     * @author langzhiyao
     * @time 20181121
     */
    public function is_version($version,$type){
        $ver = db('version_update')->where('type="'.$type.'"')->order('id DESC')->find();
        if($type == 1){
            $android_version = explode('.',$ver['version_num']);
            $android_num = $android_version[0]*100+$android_version[1]*10+$android_version[2];
            //得到传过来的版本号
            $new_android_version = explode('.',$version);
            $new_android_num = $new_android_version[0]*100+$new_android_version[1]*10+$new_android_version[2];
            if($android_num >= $new_android_num){
                return false;
            }else{
                return true;
            }
        }else{
            $ios_version = explode('.',$ver['version_num']);
            $ios_num = $ios_version[0]*100+$ios_version[1]*10+$ios_version[2];
            //得到传过来的版本号
            $new_ios_version = explode('.',$version);
            $new_ios_num = $new_ios_version[0]*100+$new_ios_version[1]*10+$new_ios_version[2];
            //判断
            if($ios_num >$new_ios_num){
                return false;
            }else{
                return true;
            }
        }
    }
    /**
     * @desc  apk上传
     * @author langzhiyao
     * @time 20181121
     */
    public function apk_file_upload()
    {
        $file = request()->file('file'); // 获取上传的文件
        if($file==null){
            exit(json_encode(array('code'=>1,'msg'=>'未上传文件')));
        }
        // 获取文件后缀
        $temp = explode(".", $_FILES["file"]["name"]);
        $extension = end($temp);
        // 判断文件是否合法
        if(!in_array($extension,array("apk"))){
            exit(json_encode(array('code'=>1,'msg'=>'上传文件类型不合法')));
        }
        $info = $file->move(ROOT_PATH.'public'.DS.'uploads'.DS.'apk');
        // 移动文件到指定目录 没有则创建
        $file = UPLOAD_SITE_URL.DS.'apk'.DS.$info->getSaveName();

        exit(json_encode(array('code'=>0,'msg'=>$file)));
    }
    /**
     * 获取卖家栏目列表,针对控制器下的栏目
     */
    protected function getAdminItemList() {
        $menu_array = array(
            array(
                'name' => 'index',
                'text' => lang('version'),
                'url' => url('Version/index')
            ),
        );
        return $menu_array;
    }
}