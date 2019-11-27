<?php

namespace app\home\controller;

use think\Lang;

/**
 * ============================================================================
 
 * ============================================================================
 * 控制器
 */
class Sellercomplain extends BaseSeller {

    //定义投诉状态常量
    const STATE_NEW = 10;
    const STATE_APPEAL = 20;
    const STATE_TALK = 30;
    const STATE_HANDLE = 40;
    const STATE_FINISH = 99;
    const STATE_UNACTIVE = 1;
    const STATE_ACTIVE = 2;

    public function _initialize() {
        parent::_initialize(); // TODO: Change the autogenerated stub
        Lang::load(APP_PATH . 'home/lang/'.config('default_lang').'/sellercomplain.lang.php');
    }

    /*
     * 被投诉列表
     */

    public function index() {
        $complain_model = model('complain');
        $condition = array();
        $condition['accused_id'] = session('store_id');
        
        if ((input('param.add_time_to')) != '' && (input('param.add_time_to')) != '') {
            $add_time_from = strtotime((input('param.add_time_from')));
            $add_time_to = strtotime((input('param.add_time_to')));
            $condition['complain_datetime'] = array('between', array($add_time_from, $add_time_to));
        }
        
        switch (intval(input('param.state'))) {
            case 1:
                $condition['complain_state'] = array('between', array(10, 90));
                break;
            case 2:
                $condition['complain_state'] = 99;
                break;
            default :
        }
        $condition['complain_active'] = 2; //投诉是需要平台审核通过之后 卖家才能看的到
        $complain_list = $complain_model->getComplainList($condition,10);
        
        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('seller_complain');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem('complain_accused_list');
        $this->assign('complain_list', $complain_list);
        $this->assign('show_page', $complain_model->page_info->render());
        $goods_list = $complain_model->getComplainGoodsList($complain_list);
        $this->assign('goods_list', $goods_list);
        return $this->fetch($this->template_dir . 'index');
    }

    /*
     * 处理投诉请求
     */

    public function complain_show() {
        $complain_id = intval(input('param.complain_id'));
        //获取投诉详细信息
        $complain_info = $this->get_complain_info($complain_id);
        $member_model = model('member');
        $member = $member_model->getMemberInfoByID($complain_info['accuser_id']);
        $this->assign('member', $member);
        $refundreturn_model = model('refundreturn');
        $condition = array();
        $condition['order_id'] = $complain_info['order_id'];
        $return_info = $refundreturn_model->getRightOrderList($condition, $complain_info['order_goods_id']);
        $this->assign('return_info', $return_info);
        $page_name = '';
        switch (intval($complain_info['complain_state'])) {
            case self::STATE_APPEAL:
                $page_name = 'complain_appeal';
                break;
            default:
                $page_name = 'complain_info';
                break;
        }
        $this->assign('complain_info', $complain_info);
        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('seller_complain');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem('complain_accused_list');
        return $this->fetch($this->template_dir . $page_name);
    }

    /*
     * 保存申诉
     */

    public function appeal_save() {
        $complain_id = intval(input('post.input_complain_id'));
        //获取投诉详细信息
        $complain_info = $this->get_complain_info($complain_id);
        //检查当前是不是投诉状态
        if (intval($complain_info['complain_state']) !== self::STATE_APPEAL) {
            $this->error(lang('param_error'));
        }
        $input = array();
        $input['appeal_message'] = input('post.input_appeal_message');

        $sellercomplain_validate = validate('sellercomplain');
        if (!$sellercomplain_validate->scene('appeal_save')->check($input)) {
            $this->error($sellercomplain_validate->getError());
        }
        
        //上传图片
        $appeal_pic = array();
        $appeal_pic[1] = 'input_appeal_pic1';
        $appeal_pic[2] = 'input_appeal_pic2';
        $appeal_pic[3] = 'input_appeal_pic3';
        
        $pic_name = array();
        $upload_file = BASE_UPLOAD_PATH .DS. 'home'.DS.'complain'.DS;
        $count = 1;
        foreach ($appeal_pic as $pic) {
            if (!empty($_FILES[$pic]['name'])) {
                $file_object = request()->file($pic);
                $file_name = session('member_id') . '_' . date('YmdHis') . rand(10000, 99999);
                $info = $file_object->rule('uniqid')->validate(['ext' => ALLOW_IMG_EXT])->move($upload_file, $file_name);
                if ($info) {
                    $pic_name[$count] = $info->getFilename();
                } else {
                    $pic_name[$count] = '';
                }
            }else{
                $pic_name[$count] = '';
            }
            $count++;
        }
        $input['appeal_pic1'] = $pic_name[1];
        $input['appeal_pic2'] = $pic_name[2];
        $input['appeal_pic3'] = $pic_name[3];



        $input['appeal_datetime'] = TIMESTAMP;
        $input['complain_state'] = self::STATE_TALK;
        $where_array = array();
        $where_array['complain_id'] = $complain_id;
        //保存申诉信息
        $complain_model = model('complain');
        $complain_id = $complain_model->editComplain($input, $where_array);
        $this->recordSellerlog('投诉申诉处理，投诉编号：' . $complain_id);
        $this->success(lang('appeal_submit_success'), url('Sellercomplain/index'));
    }

    /*
     * 申请仲裁
     */

    public function apply_handle() {
        $complain_id = intval(input('post.input_complain_id'));
        //获取投诉详细信息
        $complain_info = $this->get_complain_info($complain_id);
        $complain_state = intval($complain_info['complain_state']);
        //检查当前是不是投诉状态
        if ($complain_state < self::STATE_TALK || $complain_state === 99) {
            $this->error(lang('param_error'));
        }
        $update_array = array();
        $update_array['complain_state'] = self::STATE_HANDLE;
        $where_array = array();
        $where_array['complain_id'] = $complain_id;
        //保存投诉信息
        $complain_model = model('complain');
        $complain_id = $complain_model->editComplain($update_array, $where_array);
        $this->recordSellerlog('投诉申请仲裁，投诉编号：' . $complain_id);
        $this->error(lang('handle_submit_success'), 'Sellercomplain/index');
    }

    /*
     * 根据投诉id获取投诉对话
     */

    public function get_complain_talk() {
        $complain_id = intval(input('post.complain_id'));
        $complain_info = $this->get_complain_info($complain_id);
        $complaintalk_model = model('complaintalk');
        $param = array();
        $param['complain_id'] = $complain_id;
        $complain_talk_list = $complaintalk_model->getComplaintalkList($param);
        $talk_list = array();
        $i = 0;
        if (!empty($complain_talk_list)) {
            foreach ($complain_talk_list as $talk) {
                $talk_list[$i]['css'] = $talk['talk_member_type'];
                $talk_list[$i]['talk'] = date("Y-m-d H:i:s", $talk['talk_datetime']);
                switch ($talk['talk_member_type']) {
                    case 'accuser':
                        $talk_list[$i]['talk'] .= lang('complain_accuser');
                        break;
                    case 'accused':
                        $talk_list[$i]['talk'] .= lang('complain_accused');
                        break;
                    case 'admin':
                        $talk_list[$i]['talk'] .= lang('complain_admin');
                        break;
                    default:
                        $talk_list[$i]['talk'] .= lang('complain_unknow');
                }
                if (intval($talk['talk_state']) === 2) {
                    $talk['talk_content'] = lang('talk_forbit_message');
                }
                $talk_list[$i]['talk'] .= '(' . $talk['talk_member_name'] . ')' . lang('complain_text_say') . ':' . $talk['talk_content'];
                $i++;
            }
        }
        echo json_encode($talk_list);
    }

    /*
     * 根据发布投诉对话
     */

    public function publish_complain_talk() {
        $complain_id = intval(input('post.complain_id'));
        $complain_talk = trim(input('post.complain_talk'));
        $talk_len = strlen($complain_talk);
        if ($talk_len > 0 && $talk_len < 255) {
            $complain_info = $this->get_complain_info($complain_id);
            $complain_state = intval($complain_info['complain_state']);
            //检查投诉是否是可发布对话状态
            if ($complain_state > self::STATE_APPEAL && $complain_state < self::STATE_FINISH) {
                $complaintalk_model = model('complaintalk');
                $param = array();
                $param['complain_id'] = $complain_id;
                $param['talk_member_id'] = $complain_info['accused_id'];
                $param['talk_member_name'] = $complain_info['accused_name'];
                $param['talk_member_type'] = $complain_info['member_status'];
                $param['talk_content'] = $complain_talk;
                $param['talk_state'] = 1;
                $param['talk_admin'] = 0;
                $param['talk_datetime'] = TIMESTAMP;
                if ($complaintalk_model->addComplaintalk($param)) {
                    echo json_encode('success');
                } else {
                    echo json_encode('error2');
                }
            } else {
                echo json_encode('error');
            }
        } else {
            echo json_encode('error1');
        }
    }

    /*
     * 获取投诉信息
     */

    private function get_complain_info($complain_id) {
        if (empty($complain_id)) {
            $this->error(lang('param_error'));
        }
        $complain_model = model('complain');
        $complain_info = $complain_model->getOneComplain($complain_id);
        if ($complain_info['accused_id'] != session('store_id')) {
            $this->error(lang('param_error'));
        }
        $complain_info['member_status'] = 'accused';
        $complain_info['complain_state_text'] = $this->get_complain_state_text($complain_info['complain_state']);
        return $complain_info;
    }

    /*
     * 获得投诉状态文本
     */

    private function get_complain_state_text($complain_state) {
        switch (intval($complain_state)) {
            case self::STATE_NEW:
                return lang('complain_state_new');
                break;
            case self::STATE_APPEAL:
                return lang('complain_state_appeal');
                break;
            case self::STATE_TALK:
                return lang('complain_state_talk');
                break;
            case self::STATE_HANDLE:
                return lang('complain_state_handle');
                break;
            case self::STATE_FINISH:
                return lang('complain_state_finish');
                break;
            default:
                $this->error(lang('param_error'));
        }
    }

    /**
     * 用户中心右边，小导航
     *
     * @param string $menu_type 导航类型
     * @param string
     * @param array $array 附加菜单
     * @return
     */
    protected function getSellerItemList() {
        $menu_array = array(
            array(
                'name' => 'complain_accused_list',
                'text' => lang('complain_manage_title'),
                'url' => url('Sellercomplain/index')
            )
        );
        return $menu_array;
    }

}
