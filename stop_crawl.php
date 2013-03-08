<?php

/*
 * 根据端口把本机的pending状态爬虫停止， 并不是停止爬虫端口服务器
 * 
 * 调用 php stop_crawl.php 6800 
 * 
 */

$scrapyd_port = $argv[1];

$projectes = "http://localhost:$scrapyd_port/listprojects.json";
$projectesData = json_decode(file_get_contents($projectes), TRUE);

//拿这个端口的所有项目
foreach ($projectesData["projects"] as $p) {
    //获取每个project pending状态
    $jobs = "http://localhost:$scrapyd_port/listjobs.json?project=$p";
    $jobsData = json_decode(file_get_contents($jobs), TRUE);

    foreach ($jobsData["pending"] as $pending) {
        unset($output);
        exec("curl http://localhost:$scrapyd_port/cancel.json -d project=$p -d job={$pending["id"]}", $output);
        print_r($output);
    }

    foreach ($jobsData["running"] as $pending) {
        unset($output);
        exec("curl http://localhost:$scrapyd_port/cancel.json -d project=$p -d job={$pending["id"]}", $output);
        print_r($output);
    }
}


