<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/16
 * Time: 20:15
 */

namespace app\mobile\controller;


use think\Validate;

class Memberaddress extends MobileMember
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
    }

    /**
     * 地址列表
     */
    public function address_list()
    {
        $model_address = Model('address');
        $address_list = $model_address->getAddressList(array('member_id' => $this->member_info['member_id']));
        $numn = [];
        foreach ($address_list as $k => $v) {
            foreach ($v as $key => $value) {
                if (is_numeric($value)) {
                    $address_list[$k][$key] = (string)$value;
                }
            }
        }
        output_data(array('address_list' => $address_list));
    }

    /**
     * 地址详细信息
     */
    public function address_info()
    {
        $address_id = intval(input('param.address_id'));

        $model_address = Model('address');

        $condition = array();
        $condition['address_id'] = $address_id;
        $address_info = $model_address->getAddressInfo($condition);
        if (!empty($address_id) && $address_info['member_id'] == $this->member_info['member_id']) {
            output_data(array('address_info' => $address_info));
        }
        else {
            output_error('地址不存在');
        }
    }

    /**
     * 删除地址
     */
    public function address_del()
    {
        $address_id = intval(input('param.address_id'));
        $model_address = Model('address');

        $condition = array();
        $condition['address_id'] = $address_id;
        $condition['member_id'] = $this->member_info['member_id'];
        $model_address->delAddress($condition);
        output_data(['state'=>true]);
    }

    /**
     * 新增地址
     */
    public function address_add()
    {   
        // p(input('param.address_id'));exit;
        $model_address = Model('address');

        $address_info = $this->_address_valid();

        $result = $model_address->addAddress($address_info);
        if ($result) {
            output_data(array('address_id' => $result));
        }
        else {
            output_error('保存失败');
        }
    }

    /**
     * 编辑地址
     */
    public function address_edit()
    {      
        // p($this->member_info);exit;
        $address_id = intval(input('param.address_id'));

        $model_address = Model('address');

        //验证地址是否为本人
        $address_info = $model_address->getOneAddress($address_id);
        if ($address_info['member_id'] != $this->member_info['member_id']) {
            output_error('参数错误');
        }

        $address_info = $this->_address_valid();
        $result = $model_address->editAddress($address_info, array('member_id' => $this->member_info['member_id'],'address_id' => $address_id));
        if ($result) {
            output_data(['state'=>True]);
        }
        else {
            output_error('保存失败');
        }
    }

    /**
     * 验证地址数据
     */
    private function _address_valid()
    {
        $obj_validate = new Validate();
        $data=[
            'true_name' =>input('param.true_name'),
            'area_info' =>input('param.area_info'),
            'address'   =>input('param.address'),
        ];

        $rule=[
            ['true_name','require','姓名不能为空'],
            ['area_info','require','地区不能为空'],
            ['address','require','地址不能为空'],
        ];
        if (empty(input('param.mob_phone'))&& empty(input('param.tel_phone'))){
            $data['mob_phone']='';
            $rule[]=['mob_phone','require','联系方式不能为空'];
        }
        $error = $obj_validate->check($data,$rule);
        if (!$error) {
            output_error($obj_validate->getError());
        }

        $data = array();
        $data['member_id']          = $this->member_info['member_id'];
        $data['address_realname']   = input('param.true_name');
        $data['area_id']            = intval(input('param.area_id'));
        $data['city_id']            = intval(input('param.city_id'));
        $data['area_info']          = input('param.area_info');
        $data['address_detail']     = input('param.address');
        $data['address_longitude']  = input('param.longitude');
        $data['address_latitude']   = input('param.latitude');
        $data['address_tel_phone']  = !empty(input('param.tel_phone'))?input('param.tel_phone'):'';
        $data['address_mob_phone']  = !empty(input('param.mob_phone'))?input('param.mob_phone'):'';
        $data['address_is_default'] = input('param.is_default');
        return $data;
    }

    /**
     * 地区列表
     */
    public function area_list()
    {
        $area_id = intval($_POST['area_id']);

        $model_area = Model('area');

        $condition = array();
        if ($area_id > 0) {
            $condition['area_parent_id'] = $area_id;
        }
        else {
            $condition['area_deep'] = 1;
        }
        $area_list = $model_area->getAreaList($condition, 'area_id,area_name');
        output_data(array('area_list' => $area_list));
    }
}