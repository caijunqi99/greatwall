<?php


namespace app\mobile\controller;


class Area extends MobileMall
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
    }
    public function index() {
        $this->area_list();
    }

    /**
     * 地区列表
     */
    public function area_list() {
        $area_id = intval(input('param.area_id'));

        $model_area = Model('area');

        $condition = array();
        if($area_id > 0) {
            $condition['area_parent_id'] = $area_id;
        } else {
            $condition['area_deep'] = 1;
        }
        $area_list = $model_area->getAreaList($condition, 'area_id,area_name');
        output_data(array('area_list' => $area_list));
    }

    /*地区列表mobile*/

    public function area_app(){
        $model_area = Model('area');
        $lev1=$model_area->getTopLevelAreas();
        foreach ($lev1 as $k=>$v){
            $lev3=$lev2[$k]['area_id']= $model_area->GetChildName($k);
            foreach ($lev3 as $val){
                $lev4[$k]['area_name']=$v;
                $lev4[$k]['area_id']=$k;
                $lev4[$k]['child'][$val['area_id']]['area_name']=$val['area_name'];
                $lev4[$k]['child'][$val['area_id']]['area_id']=$val['area_id'];
                $lev4[$k]['child'][$val['area_id']]['child']=$model_area->GetChildName($val['area_id']);

            }
        }
        $area_list=array();
        foreach ($lev4 as $key=>$val){
            $val['child']=array_values($val['child']);
            $area_list[]=$val;
        }
        output_data(array('area_list' => $area_list));
    }
}