<?php
namespace app\home\controller;
use Think\Lang;
/**
 * ============================================================================
 
 * ============================================================================
 * 控制器
 */
class Sellerwaybill extends BaseSeller
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        Lang::load(APP_PATH . 'home/lang/'.config('default_lang').'/sellerdeliver.lang.php');
    }

    /**
     * 模板管理
     */
    public function index() {
        $storeextend_model = model('storeextend');
        $express_model = model('express');
        $storewaybill_model = model('storewaybill');

        $store_extend_info = $storeextend_model->getStoreextendInfo(array('store_id' => session('store_id')), 'express');
        $store_express = $store_extend_info['express'];

        $express_list = $express_model->getExpressListByID($store_express);

        $storewaybill_list = $storewaybill_model->getStorewaybillListWithWaybillInfo(session('store_id'), $store_express);

        $storewaybill_list = ds_change_arraykey($storewaybill_list, 'express_id');

        if(!empty($express_list)) {
            foreach ($express_list as $key => $value) {
                if(!empty($storewaybill_list[$value['express_id']])) {
                    $express_list[$key]['waybill_name'] = $storewaybill_list[$value['express_id']]['waybill_name'];
                    $express_list[$key]['storewaybill_id'] = $storewaybill_list[$value['express_id']]['storewaybill_id'];
                    $express_list[$key]['is_default_text'] =  $storewaybill_list[$value['express_id']]['storewaybill_isdefault'] ? lang('ds_yes') : lang('ds_no');
                    $express_list[$key]['waybill_image_url'] = get_waybill_imageurl($storewaybill_list[$value['express_id']]['waybill_image']);
                    $express_list[$key]['waybill_width'] = $storewaybill_list[$value['express_id']]['waybill_width'];
                    $express_list[$key]['waybill_height'] = $storewaybill_list[$value['express_id']]['waybill_height'];
                    $express_list[$key]['bind'] = true;
                } else {
                    $express_list[$key]['waybill_name'] = lang('unbounded');
                    $express_list[$key]['bind'] = false;
                }
            }
        }
        $this->assign('express_list', $express_list);
        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('sellerwaybill');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem('sellerwaybill');
         return $this->fetch($this->template_dir.'index');
    }

    /**
     * 绑定运单打印模板
     */
    public function waybill_bind() {
        $express_id = intval(input('param.express_id'));

        $express_model = model('express');
        $waybill_model = model('waybill');

        $express_info = $express_model->getExpressInfo($express_id);

        if(empty($express_info)) {
            $this->error(lang('express_companies_not_exist'));
        }
        $this->assign('express_info', $express_info);

        $waybill_list = $waybill_model->getWaybillUsableList($express_id, session('store_id'));
        $this->assign('waybill_list', $waybill_list);

        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('sellerwaybill');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem('waybill_bind');
         return $this->fetch($this->template_dir.'waybill_bind');
    }

    /**
     * 绑定运单打印模板保存
     */
    public function waybill_bind_save() {
        $express_id = intval(input('param.express_id'));
        $waybill_id = intval(input('param.waybill_id'));
        $waybill_model = model('waybill');
        $storewaybill_model = model('storewaybill');

        $waybill_info = $waybill_model->getWaybillInfoByID($waybill_id);
        if(!$waybill_info) {
            $this->error(lang('waybill_template_not_exist'));
        }

        $param = array();
        $param['store_id'] = session('store_id');
        $param['express_id'] = $express_id;

        //删除已有绑定
        $storewaybill_model->delStorewaybill($param);

        //保存绑定
        $param['waybill_id'] = $waybill_info['waybill_id'];
        $param['waybill_name'] = $waybill_info['waybill_name'];
        $param['storewaybill_left'] = $waybill_info['waybill_left'];
        $param['storewaybill_top'] = $waybill_info['waybill_top'];
        $result = $storewaybill_model->addStorewaybill($param);
        if($result) {
            $this->success(lang('binding_success'), 'Sellerwaybill/index');
        } else {
            $this->error(lang('binding_failure'));
        }
    }

    /**
     * 解绑运单打印模板
     */
    public function waybill_unbind() {
        $storewaybill_id = intval(input('param.storewaybill_id'));

        $storewaybill_model = model('storewaybill');

        $condition = array();
        $condition['storewaybill_id'] = $storewaybill_id;
        $condition['store_id'] = session('store_id');

        $result = $storewaybill_model->delStorewaybill($condition);
        if($result) {
            $this->success(lang('unbundling_success'), 'Sellerwaybill/index');
            ds_json_encode(10000,lang('unbundling_success'));
        } else {
            ds_json_encode(10001,lang('unbundling_failure'));
            $this->error(lang('unbundling_failure'));
        }
    }

    /**
     * 运单模板设置
     */
    public function waybill_setting() {
        $storewaybill_id = intval(input('param.storewaybill_id'));

        $storewaybill_model = model('storewaybill');

        $storewaybill_info = $storewaybill_model->getStorewaybillInfo(array('storewaybill_id' => $storewaybill_id));
        $this->assign('storewaybill_id', $storewaybill_info['storewaybill_id']);
        $this->assign('storewaybill_left', $storewaybill_info['storewaybill_left']);
        $this->assign('storewaybill_top', $storewaybill_info['storewaybill_top']);
        $this->assign('storewaybill_data', $storewaybill_info['storewaybill_data']);

        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('sellerwaybill');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem('sellerwaybil');
         return $this->fetch($this->template_dir.'waybill_setting');
    }

    /**
     * 运单模板设置保存
     */
    public function waybill_setting_save() {
        $storewaybill_id = intval(input('param.storewaybill_id'));
        if($storewaybill_id <= 0) {
            ds_json_encode(10001,lang('param_error'));
        }

        $storewaybill_model = model('storewaybill');

        $condition = array();
        $condition['storewaybill_id'] = $storewaybill_id;
        $condition['store_id'] = session('store_id');

        $update = array();
        $update['storewaybill_left'] = input('param.storewaybill_left');
        $update['storewaybill_top'] = input('param.storewaybill_top');

        $result = $storewaybill_model->editStorewaybill($update, $condition, input('post.data/a'));
        if($result) {
            ds_json_encode(10000,lang('ds_common_save_succ'));
        } else {
            ds_json_encode(10001,lang('ds_common_save_fail'));
        }
    }

    /**
     * 运单打印测试
     */
    public function waybill_test() {
        $waybill_model = model('waybill');

        $waybill_info = $waybill_model->getWaybillInfoByID(input('param.waybill_id'));
        if(!$waybill_info) {
            $this->error(lang('waybill_template_not_exist'));
        }
        $waybill_info['waybill_image_url']=$this->base64EncodeImage(str_replace(UPLOAD_SITE_URL,BASE_UPLOAD_PATH,$waybill_info['waybill_image_url']));
        $this->assign('waybill_info', $waybill_info);
         return $this->fetch($this->template_dir.'waybill_test');
    }
    
    function base64EncodeImage($image_file) {
        $base64_image = '';
        $image_info = getimagesize($image_file);
        $image_data = fread(fopen($image_file, 'r'), filesize($image_file));
        $base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));
        return $base64_image;
    }

    /**
     * 设置默认打印模板
     */
    public function waybill_set_default() {
        $storewaybill_id = intval(input('post.storewaybill_id'));

        $storewaybill_model = model('storewaybill');

        $result = $storewaybill_model->editStorewaybillDefault($storewaybill_id, session('store_id'));

        if($result) {
            ds_json_encode(10000,lang('ds_common_save_succ'));
        } else {
            ds_json_encode(10001,lang('ds_common_save_fail'));
        }
    }

    /**
     * 模板列表
     */
    public function waybill_list() {
        $waybill_model = model('waybill');

        $waybill_list = $waybill_model->getWaybillSellerList(session('store_id'));
        $this->assign('waybill_list', $waybill_list);

        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('sellerwaybill');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem('waybill_list');
         return $this->fetch($this->template_dir.'waybill_list');
    }

    /**
     * 添加运单模板
     */
    public function waybill_add() {
        $express_model = model('express');

        $this->assign('express_list', $express_model->getExpressList());
        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('sellerwaybill');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem('waybill_add');
         return $this->fetch($this->template_dir.'waybill_add');
    }

    /**
     * 保存运单模板
     */
    public function waybill_save() {
        $waybill_model = model('waybill');
        $result = $waybill_model->saveWaybill(input('post.'), session('store_id'));

        if(!isset($result['error'])) {
            $this->success(lang('ds_common_save_succ'), url('Sellerwaybill/waybill_list'));
        } else {
            $this->error(lang('ds_common_save_fail'),url('Sellerwaybill/waybill_list'));
        }
    }

    /**
     * 删除运单模板
     */
    public function waybill_del() {
        $waybill_id = intval(input('param.waybill_id'));
        if($waybill_id <= 0) {
            ds_json_encode(10001,lang('param_error'));
        }

        $waybill_model = model('waybill');

        $condition = array();
        $condition['waybill_id'] = $waybill_id;
        $condition['store_id'] = session('store_id');
        $result = $waybill_model->delWaybill($condition);
        if($result) {
            ds_json_encode(10000,lang('ds_common_del_succ'));
        } else {
            ds_json_encode(10001,lang('ds_common_del_fail'));
        }
    }

    /**
     * 编辑运单模板
     */
    public function waybill_edit() {
        $express_model = model('express');
        $waybill_model = model('waybill');
        $waybill_id=input('param.waybill_id');
        $waybill_info = $waybill_model->getWaybillInfoByID($waybill_id);
        if(!$waybill_info || $waybill_info['store_id'] != session('store_id')) {
            $this->error(lang('waybill_template_not_exist'));
        }
        $this->assign('waybill_info', $waybill_info);

        $express_list = $express_model->getExpressList();
        foreach ($express_list as $key => $value) {
            if($value['express_id'] == $waybill_info['express_id']) {
                $express_list[$key]['selected'] = true;
            }
        }
        $this->assign('express_list', $express_list);
        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('sellerwaybill');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem('waybill_edit');
         return $this->fetch($this->template_dir.'waybill_add');
    }

    /**
     * 设计运单模板
     */
    public function waybill_design() {
        $waybill_model = model('waybill');
        $waybill_id=input('param.waybill_id');
        $result = $waybill_model->getWaybillDesignInfo($waybill_id);
        if(isset($result['error'])) {
            $this->error($result['error']);
        }

        $this->assign('waybill_info', $result['waybill_info']);
        $this->assign('waybill_info_data', $result['waybill_info_data']);
        $this->assign('waybill_item_list', $result['waybill_item_list']);

        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('sellerwaybill');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem('waybill_design');
         return $this->fetch($this->template_dir.'waybill_design');
    }

    /**
     * 设计运单模板保存
     */
    public function waybill_design_save() {
        $waybill_model = model('waybill');

        $result = $waybill_model->editWaybillDataByID(input('post.waybill_data/a'), input('post.waybill_id'), session('store_id'));

        if($result) {
            ds_json_encode(10000,lang('ds_common_save_succ'));
        } else {
            ds_json_encode(10001,lang('ds_common_save_fail'));
        }
    }

    /**
     * 用户中心右边，小导航
     *
     * @param string $menu_type 导航类型
     * @param string $menu_key 当前导航的menu_key
     * @return
     */
     function getSellerItemList() {
        $menu_array = array();
        $menu_array[] = array(
            'name' => 'waybill_manage',
            'text' => lang('template'),
            'url' => url('index')
        );
        $menu_array[] = array(
            'name' => 'waybill_list',
            'text' => lang('self_built_template'),
            'url' => url('waybill_list')
        );
        if(request()->action() == 'waybill_bind') {
            $menu_array[] = array(
                'name' => 'waybill_bind',
                'text' => lang('select_template'),
                'url' => 'javascript:void(0)'
            );
        }
        if(request()->action() == 'waybill_setting') {
            $menu_array[] = array(
                'name' => 'waybill_setting',
                'text' => lang('template_settings'),
                'url' => url('waybill_setting')
            );
        }
        if(request()->action() == 'waybill_add') {
            $menu_array[] = array(
                'name' => 'waybill_add',
                'text' => lang('add_template'),
                'url' => url('waybill_add')
            );
        }
        if(request()->action() == 'waybill_edit') {
            $menu_array[] = array(
                'name' => 'waybill_edit',
                'text' => lang('edit_template'),
                'url' => url('waybill_edit')
            );
        }
        if(request()->action() == 'waybill_design') {
            $menu_array[] = array(
                'name' => 'waybill_design',
                'text' => lang('design_templates'),
                'url' => url('waybill_design')
            );
        }
        return $menu_array;
    }
}