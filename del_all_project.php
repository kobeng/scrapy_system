<?php
/*
 *  删除某一个端口的全部项目
 * 
 *  调用: php del_all_project.php $port
 * 
 * 
 */


$scrapyd_port = $argv[1];

$projectes = "http://localhost:$scrapyd_port/listprojects.json";
$projectesData = json_decode(file_get_contents($projectes), TRUE);

//拿这个端口的所有项目
foreach ($projectesData["projects"] as $p) {    
        unset($output);
        exec("curl http://localhost:$scrapyd_port/delproject.json -d project=$p", $output);
        print_r($output);    
}