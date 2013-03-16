<?php
/*
 * 检查每个端口是否还有pending的任务，
 * 如果没有，那么，把这个端口所有的project的爬虫重新爬取  
 * 这个脚本主要放在crontab里面
 */


echo date('Y-m-d H:i:s');
echo "\n";
include '/home/scrapy/scrapy_system/api.php';

$ports = array(
    6800,6801,6802,6803,6804,6805,6806,6807,6808,6809,6810,6811
);
main($ports);

function main($ports){
    foreach ($ports as $port) {
        //检查这个端口有没有pending的爬虫任务
        $is_have_pending = FALSE;
        $projects = get_all_project_name_by_port($port);
        if ($projects["status"]=="ok" && !empty($projects["projects"])){
            foreach ($projects["projects"] as $project_name) {
                $pending_spiders = get_pending_spider_name_by_port_and_project($port,$project_name);
                if(sizeof($pending_spiders)>0) {
                    $is_have_pending = TRUE;
                    break;
                }
            }
            
            //如果这个端口没有pending的爬虫任务,
            //那么把这个端口所有的项目里面的爬虫都添加紧爬取列表里面
            //这些端口有项目，但是这些项目里面爬虫已经爬完,
            //需要把这些爬虫重新添加到爬虫任务列表
            if(!$is_have_pending){
                foreach ($projects["projects"] as $project_name) {
                    $spiders = get_spider_name_by_port_project($port,$project_name);
                    
                    if ($spiders["status"]=="ok" && !empty($spiders["spiders"])){
                        foreach ($spiders["spiders"] as $spider_name) {
                            start_crawl_by_port_project_spider($port,$project_name,$spider_name);
                        }
                    }
                }               
            }
        }
        
    }
}




?>
