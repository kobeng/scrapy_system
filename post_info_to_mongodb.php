<?php

$host = "192.168.0.71";
$mongo = new MongoClient("mongodb://scrapy:scrapybiz72@120.31.132.248:17007/scrapy");

include '/home/scrapy/create_scrapy_project/api.php';

$scrapyd_dir = '/home/scrapy/scrapyd';
$scrapyd_ports = scandir($scrapyd_dir);
array_shift($scrapyd_ports);
array_shift($scrapyd_ports);

$host_collection = $mongo->selectDB("scrapy")->selectCollection("host");
foreach ($scrapyd_ports as $port) {
    $result = $host_collection->findOne(array("host" => "$host", "port" => $port));
    if (empty($result)) {
        $host_collection->insert(array("host" => "$host", "port" => $port));
    }
    //
}
echo "更新scrapyd完成" . "\n";

$host_project_collection = $mongo->selectDB("scrapy")->selectCollection("host_project");
foreach ($scrapyd_ports as $port) {
    $projects_name = get_all_project_name_by_port($port);

    if ($projects_name["status"] == "ok" && !empty($projects_name["projects"])) {
        foreach ($projects_name["projects"] as $project_name) {
            $data = array("host" => "$host",
                "port" => $port,
                "project_name" => $project_name,
            );
            $result = $host_project_collection->findOne($data);
            if (empty($result)) {
                $host_project_collection->insert($data);
            }
        }
    }
}
echo "更新project完成" . "\n";

$host_project_spider_collection = $mongo->selectDB("scrapy")->selectCollection("host_project_spider");

$host_project_data = $host_project_collection->find(array(), array("port" => 1, "project_name" => 1))->sort(array("port" => 1));
foreach ($host_project_data as $data) {
    $spiders_name = get_spider_name_by_port_project($data["port"], $data["project_name"]);

    if ($spiders_name["status"] == "ok" && !empty($spiders_name["spiders"])) {
        foreach ($spiders_name["spiders"] as $spider_name) {
            $spider_data = array(
                "host" => "$host",
                "port" => $data["port"],
                "project_name" => $data["project_name"],
                "spider_name" => $spider_name
            );
            $result = $host_project_spider_collection->findOne($spider_data);
            
            if (empty($result)) {
                echo $data["port"]."\n";
                echo "$spider_name"."\n";
                $host_project_spider_collection->insert($spider_data);
            }            
        }
    }
}
echo "更新spider完成" . "\n";





