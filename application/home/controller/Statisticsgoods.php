<?php
/**
 * 商品分析
 */

namespace app\home\controller;

use think\Loader;
use think\Lang;

/**
 * ============================================================================
 
 * ============================================================================
 * 控制器
 */
class Statisticsgoods extends BaseSeller
{
    private $search_arr;//处理后的参数
    private $gc_arr;//分类数组
    private $choose_gcid;//选择的分类ID
    
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        Lang::load(APP_PATH.'home/lang/'.config('default_lang').'/statisticsgoods.lang.php');
        Loader::import('mall.statistics');
        Loader::import('mall.datehelper');
        $stat_model = model('stat');
        //存储参数
        $this->search_arr = $_REQUEST;
        //处理搜索时间
        if (in_array(request()->action(),array('price','hotgoods'))){
            $this->search_arr = $stat_model->dealwithSearchTime($this->search_arr);
            //获得系统年份
            $year_arr = getSystemYearArr();
            //获得系统月份
            $month_arr = getSystemMonthArr();
            //获得本月的周时间段
            $week_arr = getMonthWeekArr($this->search_arr['week']['current_year'], $this->search_arr['week']['current_month']);
            $this->assign('year_arr', $year_arr);
            $this->assign('month_arr', $month_arr);
            $this->assign('week_arr', $week_arr);
        }
        $this->assign('search_arr', $this->search_arr);
        /**
         * 处理商品分类
         */
        $this->choose_gcid = ($t = intval(input('param.choose_gcid')))>0?$t:0;
        $gccache_arr = model('goodsclass')->getGoodsclassCache($this->choose_gcid,3);
        $this->gc_arr = $gccache_arr['showclass'];
        $this->assign('gc_json',json_encode($gccache_arr['showclass']));
        $this->assign('gc_choose_json',json_encode($gccache_arr['choose_gcid']));
    }
    /**
     * 商品列表
     */
    public function index(){
        $stat_model = model('stat');
        //统计的日期0点
        $stat_time = strtotime(date('Y-m-d',TIMESTAMP)) - 86400;
        /*
         * 近30天
         */
        $stime = $stat_time - (86400*29);//30天前
        $etime = $stat_time + 86400 - 1;//昨天23:59
        //查询订单商品表下单商品数
        $where = array();
        $where['order_isvalid'] = 1;//计入统计的有效订单
        $where['store_id'] = session('store_id');
        $where['order_add_time'] = array('between',array($stime,$etime));
        if($this->choose_gcid > 0){
            $gc_depth = $this->gc_arr[$this->choose_gcid]['depth'];
            $where['gc_parentid_'.$gc_depth] = $this->choose_gcid;
        }
        if(trim(input('param.search_gname'))){
            $where['goods_name'] = array('like',"%".trim(input('param.search_gname'))."%");
        }
        //查询总条数
        $count_arr = $stat_model->statByStatordergoods($where, 'count(DISTINCT goods_id) as countnum');
        $countnum = intval($count_arr[0]['countnum']);

        $field = ' goods_id,goods_name,goods_image,goods_price,SUM(goods_num) as ordergoodsnum,SUM(goods_pay_price) as ordergamount ';
        //排序
        $orderby_arr = array('ordergoodsnum asc','ordergoodsnum desc','ordergamount asc','ordergamount desc');
        if (!isset($this->search_arr['orderby']) || !in_array(trim($this->search_arr['orderby']),$orderby_arr)){
            $this->search_arr['orderby'] = 'ordergoodsnum desc';
        }
        $orderby = trim($this->search_arr['orderby']).',goods_id';
        $stat_ordergoods = $stat_model->statByStatordergoods($where, $field, array(5,$countnum), 0, $this->search_arr['orderby'], 'goods_id');
        $this->assign('goodslist',$stat_ordergoods);
        $this->assign('show_page',$stat_model->page_info->render());
        $this->assign('orderby',$this->search_arr['orderby']);
        $this->setSellerCurMenu('Statisticsgoods');
        $this->setSellerCurItem('goodslist');
        return $this->fetch($this->template_dir.'goodslist');
    }

    /**
     * 商品详细
     */
    public function goodsinfo(){
        $templatesname = 'goodsinfo';
        $goods_id = intval(input('param.gid'));
        if ($goods_id <= 0){
            $this->assign('stat_msg',lang('param_error'));
            echo $this->fetch($this->template_dir.$templatesname);exit;
        }
        //查询商品信息
        $goods_info = model('goods')->getGoodsInfoByID($goods_id);
        if (!$goods_info){
            $this->assign('stat_msg',lang('param_error'));
            echo $this->fetch($this->template_dir.$templatesname);exit;
        }
        $stat_model = model('stat');
        //统计的日期0点
        $stat_time = strtotime(date('Y-m-d',TIMESTAMP)) - 86400;
        /*
         * 近30天
         */
        $stime = $stat_time - (86400*29);//30天前
        $etime = $stat_time + 86400 - 1;//昨天23:59

        $stat_arr = array();
        for($i=$stime; $i<$etime; $i+=86400){
            //当前数据的时间
            $timetext = date('n',$i).'-'.date('j',$i);
            //统计图数据
            $stat_list['ordergoodsnum'][$timetext] = 0;
            $stat_list['ordergamount'][$timetext] = 0;
            $stat_list['ordernum'][$timetext] = 0;
            //横轴
            $stat_arr['ordergoodsnum']['xAxis']['categories'][] = $timetext;
            $stat_arr['ordergamount']['xAxis']['categories'][] = $timetext;
            $stat_arr['ordernum']['xAxis']['categories'][] = $timetext;
        }
        //查询订单商品表下单商品数
        $where = array();
        $where['goods_id'] = $goods_id;
        $where['order_isvalid'] = 1;//计入统计的有效订单
        $where['store_id'] = session('store_id');
        $where['order_add_time'] = array('between',array($stime,$etime));

        $field = ' goods_id,goods_name,COUNT(DISTINCT order_id) as ordernum,SUM(goods_num) as ordergoodsnum,SUM(goods_pay_price) as ordergamount,MONTH(FROM_UNIXTIME(order_add_time)) as monthval,DAY(FROM_UNIXTIME(order_add_time)) as dayval ';
        $stat_ordergoods = $stat_model->statByStatordergoods($where, $field, 0, 0, '','');

        $stat_count = array();
        if($stat_ordergoods){
            foreach($stat_ordergoods as $k => $v){
                $stat_list['ordergoodsnum'][$v['monthval'].'-'.$v['dayval']] = intval($v['ordergoodsnum']);
                $stat_list['ordergamount'][$v['monthval'].'-'.$v['dayval']] = floatval($v['ordergamount']);
                $stat_list['ordernum'][$v['monthval'].'-'.$v['dayval']] = intval($v['ordernum']);
                if(!isset($stat_count['ordergoodsnum'])){
                    $stat_count['ordergoodsnum']=0;
                }
                if(!isset($stat_count['ordergamount'])){
                    $stat_count['ordergamount']=0;
                }
                if(!isset($stat_count['ordernum'])){
                    $stat_count['ordernum']=0;
                }
                $stat_count['ordergoodsnum'] = intval($stat_count['ordergoodsnum']) + $v['ordergoodsnum'];
                $stat_count['ordergamount'] = floatval($stat_count['ordergamount']) + floatval($v['ordergamount']);
                $stat_count['ordernum'] = intval($stat_count['ordernum']) + $v['ordernum'];
            }
        }

        $stat_count['ordergamount'] = ds_price_format($stat_count['ordergamount']);

        $stat_arr['ordergoodsnum']['legend']['enabled'] = false;
        $stat_arr['ordergoodsnum']['series'][0]['name'] = lang('order_quantity');
        $stat_arr['ordergoodsnum']['series'][0]['data'] = array_values($stat_list['ordergoodsnum']);
        $stat_arr['ordergoodsnum']['title'] = lang('recent_single_commodity_trend');
        $stat_arr['ordergoodsnum']['yAxis'] = lang('place_order_amount');
        $stat_json['ordergoodsnum'] = getStatData_LineLabels($stat_arr['ordergoodsnum']);

        $stat_arr['ordergamount']['legend']['enabled'] = false;
        $stat_arr['ordergamount']['series'][0]['name'] = lang('place_order_amount');
        $stat_arr['ordergamount']['series'][0]['data'] = array_values($stat_list['ordergamount']);
        $stat_arr['ordergamount']['title'] = lang('recent_order_amount_trend');
        $stat_arr['ordergamount']['yAxis'] = lang('place_order_amount');
        $stat_json['ordergamount'] = getStatData_LineLabels($stat_arr['ordergamount']);

        $stat_arr['ordernum']['legend']['enabled'] = false;
        $stat_arr['ordernum']['series'][0]['name'] = lang('order_quantity');
        $stat_arr['ordernum']['series'][0]['data'] = array_values($stat_list['ordernum']);
        $stat_arr['ordernum']['title'] = lang('recent_orders_trend');
        $stat_arr['ordernum']['yAxis'] = lang('place_order_amount');
        $stat_json['ordernum'] = getStatData_LineLabels($stat_arr['ordernum']);
        $this->assign('stat_json',$stat_json);
        $this->assign('stat_count',$stat_count);
        $this->assign('goods_info',$goods_info);
        echo $this->fetch($this->template_dir.$templatesname);
    }

    /**
     * 价格销量统计
     */
    public function price(){
        if(!isset($this->search_arr['search_type'])){
            $this->search_arr['search_type'] = 'day';
        }
        $stat_model = model('stat');
        //获得搜索的开始时间和结束时间
        $searchtime_arr = $stat_model->getStarttimeAndEndtime($this->search_arr);
        $where = array();
        $where['store_id'] = session('store_id');
        $where['order_isvalid'] = 1;//计入统计的有效订单
        $where['order_add_time'] = array('between',$searchtime_arr);
        //商品分类
        if ($this->choose_gcid > 0){
            //获得分类深度
            $depth = $this->gc_arr[$this->choose_gcid]['depth'];
            $where['gc_parentid_'.$depth] = $this->choose_gcid;
        }

		$field = '1';
        $pricerange = ds_getvalue_byname('storeextend', 'store_id', session('store_id'), 'pricerange');
        $pricerange_arr = $pricerange?unserialize($pricerange):array();
        if ($pricerange_arr){
            $stat_arr['series'][0]['name'] = lang('order_quantity');
            //设置价格区间最后一项，最后一项只有开始值没有结束值
            $pricerange_count = count($pricerange_arr);
            if ($pricerange_arr[$pricerange_count-1]['e']){
                $pricerange_arr[$pricerange_count]['s'] = $pricerange_arr[$pricerange_count-1]['e'] + 1;
                $pricerange_arr[$pricerange_count]['e'] = '';
            }
            foreach ((array)$pricerange_arr as $k=>$v){
                $v['s'] = intval($v['s']);
                $v['e'] = intval($v['e']);
                //构造查询字段
                if ($v['e']){
                    $field .= " ,SUM(IF(goods_pay_price/goods_num > {$v['s']} and goods_pay_price/goods_num <= {$v['e']},goods_num,0)) as goodsnum_{$k}";
                } else {
                    $field .= " ,SUM(IF(goods_pay_price/goods_num > {$v['s']},goods_num,0)) as goodsnum_{$k}";
                }
            }
            $ordergooods_list = $stat_model->query('SELECT '.$field.' FROM '.config('database.prefix').'statordergoods WHERE store_id='.$where['store_id'].' AND order_isvalid='.$where['order_isvalid'].' AND order_add_time BETWEEN '.$searchtime_arr[0].' AND '.$searchtime_arr[1].($this->choose_gcid > 0?(' AND gc_parentid_'.$depth.'='.$this->choose_gcid):''));
            if($ordergooods_list){
                $ordergooods_list= current($ordergooods_list);
                foreach ((array)$pricerange_arr as $k=>$v){
                    //横轴
                    if ($v['e']){
                        $stat_arr['xAxis']['categories'][] = $v['s'].'-'.$v['e'];
                    } else {
                        $stat_arr['xAxis']['categories'][] = $v['s'].lang('above');
                    }
                    //统计图数据
                    if (isset($ordergooods_list['goodsnum_'.$k])){
                        $stat_arr['series'][0]['data'][] = intval($ordergooods_list['goodsnum_'.$k]);
                    } else {
                        $stat_arr['series'][0]['data'][] = 0;
                    }
                }
            }
            //得到统计图数据
            $stat_arr['title'] = lang('price_distribution');
            $stat_arr['legend']['enabled'] = false;
            $stat_arr['yAxis'] = lang('sales');
            $pricerange_statjson = getStatData_LineLabels($stat_arr);
        } else {
            $pricerange_statjson = '';
        }

        $this->assign('statjson',$pricerange_statjson);
        $this->setSellerCurMenu('Statisticsgoods');
        $this->setSellerCurItem('price');
        return $this->fetch($this->template_dir.'goods_price');
    }

    /**
     * 热卖商品
     */
    public function hotgoods(){
        $topnum = 30;

        if(!isset($this->search_arr['search_type'])){
            $this->search_arr['search_type'] = 'day';
        }
        $stat_model = model('stat');
        //获得搜索的开始时间和结束时间
        $searchtime_arr = $stat_model->getStarttimeAndEndtime($this->search_arr);
        $stat_model = model('stat');
        $where = array();
        $where['store_id'] = session('store_id');
        $where['order_isvalid'] = 1;//计入统计的有效订单
        $where['order_add_time'] = array('between',$searchtime_arr);

        //查询销量top
        //构造横轴数据
        for($i=1; $i<=$topnum; $i++){
            //数据
            $stat_arr['series'][0]['data'][] = array('name'=>'','y'=>0);
            //横轴
            $stat_arr['xAxis']['categories'][] = "$i";
        }
        $field = ' goods_id,goods_name,SUM(goods_num) as goodsnum ';
        $orderby = 'goodsnum desc,goods_id';
        $statlist = array();
        $statlist['goodsnum'] = $stat_model->statByStatordergoods($where, $field, 0, $topnum, $orderby, 'goods_id');
        foreach ((array)$statlist['goodsnum'] as $k=>$v){
            $stat_arr['series'][0]['data'][$k] = array('name'=>strval($v['goods_name']),'y'=>intval($v['goodsnum']));
        }
        $stat_arr['series'][0]['name'] = lang('order_quantity');
        $stat_arr['legend']['enabled'] = false;
        //得到统计图数据
        $stat_arr['title'] = lang('hot_commodity_top').$topnum;
        $stat_arr['yAxis'] = lang('order_quantity');
        $stat_json['goodsnum'] = getStatData_Column2D($stat_arr);
        unset($stat_arr);


        //查询下单金额top
        //构造横轴数据
        for($i=1; $i<=$topnum; $i++){
            //数据
            $stat_arr['series'][0]['data'][] = array('name'=>'','y'=>0);
            //横轴
            $stat_arr['xAxis']['categories'][] = "$i";
        }
        $field = ' goods_id,goods_name,SUM(goods_pay_price) as orderamount ';
        $orderby = 'orderamount desc,goods_id';
        $statlist['orderamount'] = $stat_model->statByStatordergoods($where, $field, 0, $topnum, $orderby, 'goods_id');
        foreach ((array)$statlist['orderamount'] as $k=>$v){
            $stat_arr['series'][0]['data'][$k] = array('name'=>strval($v['goods_name']),'y'=>floatval($v['orderamount']));
        }
        $stat_arr['series'][0]['name'] = lang('place_order_amount');
        $stat_arr['legend']['enabled'] = false;
        //得到统计图数据
        $stat_arr['title'] = lang('hot_commodity_top').$topnum;
        $stat_arr['yAxis'] = lang('place_order_amount');
        $stat_json['orderamount'] = getStatData_Column2D($stat_arr);
        $this->assign('stat_json',$stat_json);
        $this->assign('statlist',$statlist);
        $this->setSellerCurItem('hotgoods');
        $this->setSellerCurMenu('Statisticsgoods');
        return $this->fetch($this->template_dir.'hotgoods');
    }
    /**
     * 用户中心右边，小导航
     *
     * @param string	$menu_type	导航类型
     * @param string 	$name	当前导航的name
     * @return
     */
    protected function getSellerItemList()
    {
        $menu_array	= array(
            array('name'=>'goodslist','text'=>lang('goods_details'),	'url'=>url('Statisticsgoods/index')),
            array('name'=>'price','text'=>lang('sales_price'),	'url'=>url('Statisticsgoods/price')),
            array('name'=>'hotgoods','text'=>lang('selling_goods'),	'url'=>url('Statisticsgoods/hotgoods')),
        );
        return $menu_array;
    }
}