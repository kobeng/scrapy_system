<?php
/*
 *  删除某一个端口的多余项目
 * 
 *  调用: php del_project.php $port
 * 
 *  作用: 根据 /home/scrapy/xml/$port/ 里面project文件夹 删除在scrapyd某个端口上面多余的项目
 * 
 *       主要被create_scrapy_project.sh调用
 * 
 * 
 */


$scrapyd_port = $argv[1];

$projectes = "http://localhost:$scrapyd_port/listprojects.json";
$projectesData = json_decode(file_get_contents($projectes), TRUE);

//拿这个端口的所有项目
foreach ($projectesData["projects"] as $p) {
    if(!is_dir("../xml/$scrapyd_port/$p")) {
        unset($output);
        exec("curl http://localhost:$scrapyd_port/delproject.json -d project=$p", $output);
        print_r($output);
    }
}