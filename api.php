<?php
//拿某个端口所有project name
function get_all_project_name_by_port($port){
    $projectes = "http://localhost:$port/listprojects.json";
    $projectesData = json_decode(file_get_contents($projectes),TRUE);
    return $projectesData;
}

//从爬虫任务列表 
//获取某个端口某个project的pending状态的spider name
function get_pending_spider_name_by_port_and_project($port,$project){
    //获取每个project pending状态
    $jobs = "http://localhost:$port/listjobs.json?project=$project";
    $jobsData = json_decode(file_get_contents($jobs),TRUE);
    //print_r($jobsData["pending"]);
    $pendingArray = array();
    foreach($jobsData["pending"] as $pending){
        $pendingArray[] = $pending["spider"];
    }
    return $pendingArray;
}

//拿某个端口的某个项目里面的爬虫名字
function get_spider_name_by_port_project($port,$project){
    $scrapyd_url = "http://localhost:$port/listspiders.json?project=$project"; 
    $spidersData = json_decode(file_get_contents($scrapyd_url),TRUE);
    return $spidersData;
}

//把某个爬虫添加到爬虫任务列表
function start_crawl_by_port_project_spider($port,$project,$spider){
    unset($output);
    exec("curl http://localhost:$port/schedule.json -d project=$project -d spider=$spider",$output);
    print_r($output);
}
?>
