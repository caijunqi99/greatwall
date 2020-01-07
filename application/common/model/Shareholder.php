<?php

namespace app\common\model;

use think\Db;
use think\Model;

/**
 * ============================================================================

 * ============================================================================
 * 数据层模型
 */
class Shareholder extends Model
{

    public $page_info;



    /**
     * 会员申请为股东
     * @access public
     * @author bayi-shop
     * @param  array $data 会员信息
     * @return array 数组格式的返回结果
     */
    public function addShareholder($data)
    {
        if (empty($data)) {
            return false;
        }
        try {
            $this->startTrans();
            $shareholder_info = array();
            if (isset($data['c_id'])) {
                $shareholder_info['c_id'] = $data['c_id'];
            }
            if (isset($data['m_id'])) {
                $shareholder_info['m_id'] = $data['m_id'];
            }
            if (isset($data['s_addtime'])) {
                $shareholder_info['s_addtime'] = $data['s_addtime'];
            }
            if (isset($data['s_del'])) {
                $shareholder_info['s_del'] = $data['s_del'];
            }
            $insert_id = db('shareholder')->insertGetId($shareholder_info);
            $this->commit();
            return $insert_id;
        } catch (Exception $e) {
            $this->rollback();
            return false;
        }
    }

    /**
     * 会员详细信息（查库）
     * @access public
     * @author bayi-shop
     * @param array $condition 条件
     * @param string $field 字段
     * @return array
     */
    public function getShareholder($condition, $field = '*')
    {
        $res = db('shareholder')->field($field)->where($condition)->find();
        return $res;
    }
    /**
     * 股东列表
     * @return array
     */
    public function getShareList($condition = array(), $field = '*', $pagesize = 0, $order = 's_id desc')
    {
        if ($pagesize) {
            $share_list = db('shareholder')->where($condition)->order($order)->paginate($pagesize,false,['query' => request()->param()]
            );
            $this->page_info = $share_list;
            return $share_list->items();
        }
        else {
            return db('shareholder')->field($field)->where($condition)->order($order)->select();
        }
    }
    /**
     * 编辑股东
     * @access public
     * @author bayi-shop
     * @param array $condition 检索条件
     * @param array $data 数据
     * @return bool
     */
    public function editShare($condition, $data)
    {
        $update = db('shareholder')->where($condition)->update($data);
        return $update;
    }

}
