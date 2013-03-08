<?php
/*
 * 根据端口把本机的爬虫加入到爬虫任务列表
 * 
 * 调用 php start_crawl.php 6800 
 * 
 */
echo date('Y-m-d H:i:s');
echo "\n";
$scrapyd_port = $argv[1];

$projectes = "http://localhost:$scrapyd_port/listprojects.json";
$projectesData = json_decode(file_get_contents($projectes),TRUE);

//拿这个端口的所有项目
foreach ($projectesData["projects"] as $p){
    $scrapyd_url = "http://localhost:$scrapyd_port/listspiders.json?project=$p"; 
    $spidersData = json_decode(file_get_contents($scrapyd_url),TRUE);   
    
    foreach ($spidersData["spiders"] as $s){
        //echo "curl http://localhost:$scrapyd_port/schedule.json -d project=$p -d spider=$s \n";
        unset($output);
        exec("curl http://localhost:$scrapyd_port/schedule.json -d project=$p -d spider=$s",$output);
        print_r($output);
    }
}
exit;
