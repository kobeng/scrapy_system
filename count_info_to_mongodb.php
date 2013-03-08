<?php
date_default_timezone_set("Asia/Chongqing");
$yesterday = date("Ymd",strtotime("-1 day"));
$time_start =  strtotime(date("Y-m-d 00:00:00",strtotime("-1 day")));
$time_end =  strtotime(date("Y-m-d 23:59:59",strtotime("-1 day")));


$mongo = new MongoClient("mongodb://scrapy:scrapybiz72@120.31.132.248:17007/scrapy",array("wTimeout" => 30000));
$db = $mongo->selectDB("scrapy");
$spider_stats_collection = $mongo->selectDB("scrapy")->selectCollection("spider_stats");

//清除mapreduce产生的临时集合
$mongo->selectDB("scrapy")->selectCollection("count_spiders_temp")->drop();

//统计每个爬虫
$map = new MongoCode(" 
    var key = this.spider_name;
    var value = { 
        item_scraped_count: this.item_scraped_count,
        item_pages_count:this.item_pages_count,
        date: $yesterday,
        host:this.host,    
        scrapyd_port:this.scrapyd_port,
        project_name:this.project_name,
        spider_name:this.spider_name        
    };
    emit(key, value);        
");
$reduce = new MongoCode("
    function(k, vals) { 
        reducedValue = { item_scraped_count:0 ,item_pages_count: 0, date: 0 };
        for (var i in vals) {
            reducedValue.item_scraped_count += vals[i].item_scraped_count;
            reducedValue.item_pages_count += vals[i].item_pages_count;
            reducedValue.host = vals[i].host;
            reducedValue.scrapyd_port = vals[i].scrapyd_port;
            reducedValue.project_name = vals[i].project_name;
            reducedValue.spider_name = vals[i].spider_name;
        }
        
        reducedValue.date = $yesterday;
        return reducedValue; 
    }");

$count_spiders_temp = $db->command(array(
    "mapreduce" => "spider_stats", 
    "map" => $map,
    "reduce" => $reduce,
    "out" => array(
        "merge"  => "count_spiders_temp",        
        ),
    "query" => array(
        "finish_time" => array('$gte' => $time_start,'$lte' => $time_end),
        
        )
    ));

$count_spiders_collection = $mongo->selectDB("scrapy")->selectCollection("count_spiders");
$count_spiders_data = $db->selectCollection($count_spiders_temp['result'])->find();
foreach ($count_spiders_data as $v){
    $count_spiders_collection->save($v['value'],array("timeout"=>"safe"));
}

//清除mapreduce产生的临时集合
$mongo->selectDB("scrapy")->selectCollection("count_projects_temp")->drop();

//统计每个project的爬取数
$map = new MongoCode(" 
    var key = this.project_name;
    var value = { 
        item_scraped_count: this.item_scraped_count,
        item_pages_count:this.item_pages_count,
        time: $yesterday,
        host:this.host,    
        scrapyd_port:this.scrapyd_port,
        project_name:this.project_name,       
    };
    emit(key, value);        
");
$reduce = new MongoCode("
    function(k, vals) { 
        reducedValue = { item_scraped_count:0 ,item_pages_count: 0, date: 0 };
        for (var i in vals) {
            reducedValue.item_scraped_count += vals[i].item_scraped_count;
            reducedValue.item_pages_count += vals[i].item_pages_count;
            reducedValue.host = vals[i].host;
            reducedValue.scrapyd_port = vals[i].scrapyd_port;
            reducedValue.project_name = vals[i].project_name;
            
        }
        
        reducedValue.date = $yesterday;
        return reducedValue; 
    }");

$count_projects_temp = $db->command(array(
    "mapreduce" => "spider_stats", 
    "map" => $map,
    "reduce" => $reduce,
    "out" => array(
        "merge"  => "count_projects_temp",        
        ),
    "query" => array(
        "finish_time" => array('$gte' => $time_start,'$lte' => $time_end),
        
        )
    ));

$count_projects_collection = $mongo->selectDB("scrapy")->selectCollection("count_projects");
$count_projects_data = $db->selectCollection($count_projects_temp['result'])->find();
foreach ($count_projects_data as $v){
    $count_projects_collection->save($v['value'],array("timeout"=>"safe"));
}

//清除mapreduce产生的临时集合
$mongo->selectDB("scrapy")->selectCollection("count_ports_temp")->drop();

//统计每个端口爬取数
$map = new MongoCode(" 
    var key = this.host + '-' +this.scrapyd_port;
    var value = { 
        item_scraped_count: this.item_scraped_count,
        item_pages_count:this.item_pages_count,
        time: $yesterday,
        host:this.host,    
        scrapyd_port:this.scrapyd_port,
        
    };
    emit(key, value);        
");
$reduce = new MongoCode("
    function(k, vals) { 
        reducedValue = { item_scraped_count:0 ,item_pages_count: 0, date: 0 };
        for (var i in vals) {
            reducedValue.item_scraped_count += vals[i].item_scraped_count;
            reducedValue.item_pages_count += vals[i].item_pages_count;            
            reducedValue.host = vals[i].host; 
            reducedValue.scrapyd_port = vals[i].scrapyd_port;            
        }
        
        reducedValue.date = $yesterday;
        return reducedValue; 
    }");

$count_ports_temp = $db->command(array(
    "mapreduce" => "spider_stats", 
    "map" => $map,
    "reduce" => $reduce,
    "out" => array(
        "merge"  => "count_ports_temp",        
        ),
    "query" => array(
        "finish_time" => array('$gte' => $time_start,'$lte' => $time_end),
        
        )
    ));

$count_ports_collection = $mongo->selectDB("scrapy")->selectCollection("count_ports");
$count_ports_data = $db->selectCollection($count_ports_temp['result'])->find();
foreach ($count_ports_data as $v){
    $count_ports_collection->save($v['value'],array("timeout"=>"safe"));
}

//清除mapreduce产生的临时集合
$mongo->selectDB("scrapy")->selectCollection("count_hosts_temp")->drop();

//统计每个主机爬取数
$map = new MongoCode(" 
    var key = this.host;
    var value = { 
        item_scraped_count: this.item_scraped_count,
        item_pages_count:this.item_pages_count,
        time: $yesterday,
        host:this.host       
    };
    emit(key, value);        
");
$reduce = new MongoCode("
    function(k, vals) { 
        reducedValue = { item_scraped_count:0 ,item_pages_count: 0, date: 0 };
        for (var i in vals) {
            reducedValue.item_scraped_count += vals[i].item_scraped_count;
            reducedValue.item_pages_count += vals[i].item_pages_count;            
            reducedValue.host = vals[i].host;                    
        }
        
        reducedValue.date = $yesterday;
        return reducedValue; 
    }");

$count_hosts_temp = $db->command(array(
    "mapreduce" => "spider_stats", 
    "map" => $map,
    "reduce" => $reduce,
    "out" => array(
        "merge"  => "count_hosts_temp",        
        ),
    "query" => array(
        "finish_time" => array('$gte' => $time_start,'$lte' => $time_end),
        
        )
    ));

$count_hosts_collection = $mongo->selectDB("scrapy")->selectCollection("count_hosts");
$count_hosts_data = $db->selectCollection($count_hosts_temp['result'])->find();
foreach ($count_hosts_data as $v){
    $count_hosts_collection->save($v['value'],array("timeout"=>"safe"));
}
