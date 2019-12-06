<?php


/**
 * 把数字转换为大写汉字
 * @DateTime 2019-12-05
 * @param    [type]     $num [description]
 * @return   [type]          [description]
 */
function number2chinese($num)
{
    if (is_int($num) && $num <= 100) {
        $char = array('零', '一', '二', '三', '四', '五', '六', '七', '八', '九');
        $unit = ['', '十', '百'];
        $return = '';
        if ($num < 10) {
            $return = $char[$num];
        } elseif ($num%10 == 0) {
            $firstNum = substr($num, 0, 1);
            if ($num != 10) $return .= $char[$firstNum];
            $return .= $unit[strlen($num) - 1];
        } elseif ($num < 20) {
            $return = $unit[substr($num, 0, -1)]. $char[substr($num, -1)];
        } else {
            $numData = str_split($num);
            $numLength = count($numData) - 1;
            foreach ($numData as $k => $v) {
                if ($k == $numLength) continue;
                $return .= $char[$v];
                if ($v != 0) $return .= $unit[$numLength - $k];
            }
            $return .= $char[substr($num, -1)];
        }
        return $return;
    }
}


function output_data($datas, $extend_data = array()) {
    $data = array();
    $data['code'] = isset($datas['error'])?'100':'200';
    $data['result']=isset($datas['error'])?array():$datas;
    $data['message'] = isset($datas['error'])?$datas['error']:'ok';
    //$data['datas']=$datas;
    if(!empty($extend_data)) {
        $data = array_merge($data, $extend_data);
    }

    if(!empty($_GET['callback'])) {
        echo $_GET['callback'].'('.json_encode($data).')';die;
    } else {
        echo json_encode($data);die;
    }
}

function output_error($message, $extend_data = array()) {
    $datas = array('error' => $message);
    output_data($datas, $extend_data);
}

function mobile_page($page_info) {
    //输出是否有下一页
    $extend_data = array();
    if($page_info==''){
        $extend_data['page_total']=1;
        $extend_data['hasmore'] = false;
    }else {
        $current_page = $page_info->currentPage();
        if ($current_page <= 0) {
            $current_page = 1;
        }
        if ($current_page >= $page_info->lastPage()) {
            $extend_data['hasmore'] = false;
        }
        else {
            $extend_data['hasmore'] = true;
        }
        $extend_data['page_total'] = $page_info->lastPage();
    }
    return $extend_data;
}

function get_server_ip() {
    if (isset($_SERVER)) {
        if($_SERVER['SERVER_ADDR']) {
            $server_ip = $_SERVER['SERVER_ADDR'];
        } else {
            $server_ip = $_SERVER['LOCAL_ADDR'];
        }
    } else {
        $server_ip = getenv('SERVER_ADDR');
    }
    return $server_ip;
}

function http_get($url) {
    return file_get_contents($url);
}

function http_post($url, $param) {
    $postdata = http_build_query($param);

    $opts = array('http' =>
        array(
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => $postdata
        )
    );

    $context  = stream_context_create($opts);

    return @file_get_contents($url, false, $context);
}

function http_postdata($url, $postdata) {
    $opts = array('http' =>
        array(
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => $postdata
        )
    );

    $context  = stream_context_create($opts);

    return @file_get_contents($url, false, $context);
}


