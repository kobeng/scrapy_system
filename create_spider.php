<?php
/*
 *  创建python爬虫程序
 * 
 *  调用: php create_spider.php $projectName $spiderName $port
 * 
 *  作用: 生成/home/scrapy/xml/$port/$projiectName/$spiderName 爬虫
 * 
 *       主要被create_scrapy_project.sh调用
 */

$project_name = $argv[1];
$spider_name  = $argv[2];
$scrapyd_port = $argv[3];

$xml_dom                        = simplexml_load_file("../xml/$scrapyd_port/$project_name/$spider_name.xml");

$start_urls                     = $xml_dom->spider->start_urls;
$start_urls_type                = $xml_dom->spider->start_urls_type;
$start_urls_rule                = $xml_dom->spider->start_urls_rule;
$start_urls_rule_expression     = $xml_dom->spider->start_urls_rule_expression;
$start_urls_ruleXpathExpression = $xml_dom->spider->start_urls_ruleXpathExpression;

$next_page_urls_rule               = $xml_dom->spider->next_page_urls_rule;
$next_page_rule_expression         = $xml_dom->spider->next_page_rule_expression;
$next_page_url_ruleXpathExpression = $xml_dom->spider->next_page_url_ruleXpathExpression;
$next_page_end_rule                = $xml_dom->spider->next_page_end_rule;
$next_page_end_rule_expression     = $xml_dom->spider->next_page_end_rule_expression;
$next_page_end_regular_value       = $xml_dom->spider->next_page_end_regular_value;

$item_page_urls_rule               = $xml_dom->spider->item_page_urls_rule;
$item_page_rule_expression         = $xml_dom->spider->item_page_rule_expression;
$item_page_url_ruleXpathExpression = $xml_dom->spider->item_page_url_ruleXpathExpression;
$item_page_url_parse_only_one      = $xml_dom->spider->item_page_url_parse_only_one;
if(empty($item_page_url_parse_only_one)){
    $item_page_url_parse_only_one = "True";
}

$items         = $xml_dom->items->children();

$rules         = $xml_dom->rules->children();

$host       = $xml_dom->storage->host;
$db_user    = $xml_dom->storage->user;
$db_pass    = $xml_dom->storage->password;
$port       = $xml_dom->storage->port;
$db_name    = $xml_dom->storage->db_name;
//$table_name = $xml_dom->storage->table_name;

include_once 'items_type.php';

//创建spider
$spider_py_code_file = <<<PY
#-*- coding: UTF-8 -*-
from scrapy.spider import BaseSpider
from scrapy.selector import HtmlXPathSelector
from scrapy.http import Request
from scrapy.conf import settings
import string,re,redis,hashlib

def import_and_get_mod(str, parent_mod=None):
    """Attempts to import the supplied string as a module.
    Returns the module that was imported."""
    mods = str.split('.')
    child_mod_str = '.'.join(mods[1:])
    if parent_mod is None:
        if len(mods) > 1:
            #First time this function is called; import the module
            #__import__() will only return the top level module
            return import_and_get_mod(child_mod_str, __import__(str))
        else:
            return __import__(str)
    else:
        mod = getattr(parent_mod, mods[0])
        if len(mods) > 1:
            #We're not yet at the intended module; drill down
            return import_and_get_mod(child_mod_str, mod)
        else:
            return mod

class {$spider_name}Spider(BaseSpider):
    name = "$spider_name"    
    start_urls = $start_urls    
    
    #入口页面变量定义
    #从入口页是列表页面汇总（例如首页），那么拿到所有列表页面的url start_urls_type = "index"
    #或者
    #从入口页是列表页面（例如标签页面），那么拿到所有最终页面的url start_urls_type = "list"
    start_urls_type = $start_urls_type   
    
    #当start_urls_type = index 才要填写
    #提取入口页面的规则是通过 正则 还是 通过 xpath
    #start_urls_rule = "regular"    
    #start_urls_rule = "xpath"
    #其实如果入口页面的index 那么基本只能通过正则匹配出所有列表的页面 
    #当然如果某网站的列表页面的url可以通过xpath规则提取，那么你也可以通过xpath规则提取列表页面的url
    start_urls_rule = $start_urls_rule
    
    #当start_urls_type = index 才要填写
    #提取入口页面的规则表达式
    start_urls_rule_expression = $start_urls_rule_expression    
    
    #当start_urls_type = index 才要填写
    #如果设置了 start_urls_rule = "regular"
    #那么这个值才生效
    #在xpath规则下拿到的html 然后再去匹配正则规则 从而拿到相对应的连接 
    #这个值是填写xpath表达式
    start_urls_ruleXpathExpression = $start_urls_ruleXpathExpression   
    
    ###############################################################################
    
    #列表页面下一页变量定义
    
    #提取下一页面的url规则是通过 正则 还是 通过 xpath
    #通过规则提取下一页的url只能返回一条
    #next_page_urls_rule = "regular"    
    #next_page_urls_rule = "xpath"
    #其实提取下一页面的url 通过xpath规则提取 
    #当然如果某网站的下一页的url可以通过正则提取一条的下一页url，那么你也可以通过正则提取一条的下一页url    
    next_page_urls_rule = $next_page_urls_rule
    
    #提取下一页面的规则表达式
    next_page_rule_expression = $next_page_rule_expression    
    
    #如果设置了 next_page_urls_rule = "regular"
    #那么这个值才生效
    #在xpath规则下拿到的html 然后再去匹配正则规则 从而拿到相对应的连接
    #这个值是填写xpath表达式 
    next_page_url_ruleXpathExpression = $next_page_url_ruleXpathExpression

    #结束的列表页数的获取规则类型
    ##例如 用正则 那么你写的正则规则要匹配出当前爬行中列表的url的页数的那个数字出来
    ##如果改值为空 那么就获取到底了 <next_page_end_rule>''</next_page_end_rule>
    #next_page_end_rule = "regular"#next_page_end_rule = "regular"    
    next_page_end_rule = $next_page_end_rule
    
    #结束的列表页数的获取规则表达式
    #对于入口类型是index的 那么正则规则可以写多个匹配 写法用python list类型定于 ['a正则规则','b正则规则']
    #对于入口类型是list的  那么正则规则可以写一个匹配 写法用python list类型定于 ['a正则规则']
    next_page_end_rule_expression = $next_page_end_rule_expression
    
    #next_page_end_rule = "regular" 那么这个值要填写终止页面数的值 例如5 
    next_page_end_regular_value = $next_page_end_regular_value
    ###############################################################################
    # item变量定义
    
    #提取最终页面的url规则是通过 正则 还是 通过 xpath
    #通过规则提取最终页面的url
    #item_page_urls_rule = "regular"    
    #item_page_urls_rule = "xpath"    
    item_page_urls_rule = $item_page_urls_rule    
    
    #提取最终页的url规则表达式
    item_page_rule_expression = $item_page_rule_expression
        
    #如果设置了  item_page_urls_rule = "regular"
    #那么这个值才生效
    #在xpath规则下拿到的html 然后再去匹配正则规则 从而拿到相对应的连接
    #这个值是填写xpath表达式 
    item_page_url_ruleXpathExpression = $item_page_url_ruleXpathExpression

    #最终页面的url是否去重
    #值为true 加入redis url set里面 这些url爬一次就不会再爬
    #值为false 每次启动爬虫，这些最终页面都会爬取
    item_page_url_parse_only_one = $item_page_url_parse_only_one

    #redis
    redisObj = redis.StrictRedis(settings['REDIS_SERVER'], settings['REDIS_PORT'], settings['REDIS_DB'])
    #导入items object
    itemsName = settings.get('BOT_NAME') + '.' +name + 'items'
    
    #从规则里面获取了多少个item的url
    item_pages_count = 0

    #经过url去重后获取了多少个item的url
    item_pages_cross_cache_count = 0

    #是否被封爬虫 
    banned = 0
    
    #从入口页面开始捉取
    def parse(self,response):
        if 'next_page_url' in response.meta:
            print "parse_next_page:" + response.meta['next_page_url']
        else :
            print "parse url:" + response.url
            if self.start_urls[0] != response.url:
                self.banned = 1

        if self.start_urls_type == "index":
            #获取列表页面 或者 最终页面 的url规则是xpath
            if self.start_urls_rule == "xpath":
                urls = self.extractUrlByXpath(response, self.start_urls_rule_expression)
        
            #获取列表页面 或者 最终页面 的url规则是正则    
            if self.start_urls_rule == "regular":
                urls = self.extractUrlByRegular(response, self.start_urls_rule_expression , self.start_urls_ruleXpathExpression)
                
            for url in urls:
                url = self.getFullUrl(response.url,url)
                yield Request(url, callback=self.parse_list_page)
        
        if self.start_urls_type == "list":            
            #获取列表页面 或者 最终页面 的url规则是xpath
            if self.item_page_urls_rule == "xpath":
                urls = self.extractUrlByXpath(response, self.item_page_rule_expression)
        
            #获取列表页面 或者 最终页面 的url规则是正则    
            if self.item_page_urls_rule == "regular":
                urls = self.extractUrlByRegular(response, self.item_page_rule_expression, self.item_page_url_ruleXpathExpression)            
            
            self.item_pages_count = self.item_pages_count + len(urls)
            print "this enter page item pages count:" + str(len(urls))
            item_pages_cross_cache_count = 0
            
            for url in urls:
                url = self.getFullUrl(response.url,url)
                if self.isUrlParse(url) == False :
                    self.item_pages_cross_cache_count = self.item_pages_cross_cache_count + 1
                    item_pages_cross_cache_count = item_pages_cross_cache_count + 1
                    yield Request(url, callback=self.parse_item_1)
            print "this enter page item pages cross cache count:" + str(item_pages_cross_cache_count)
            print "---------------------------------------------"
            print ""
            
            #分析下一页
            if self.next_page_urls_rule =='xpath':
                next_page_urls = self.extractUrlByXpath(response, self.next_page_rule_expression)                

            if self.next_page_urls_rule =='regular':
                next_page_urls = self.extractUrlByRegular(response, self.next_page_rule_expression , self.next_page_url_ruleXpathExpression)
            
            if len(next_page_urls) > 0 :                
                for next_page_url in next_page_urls:                    
                    next_page_url = self.getFullUrl(response.url,next_page_url)                    
                    #检查是否达到终止列表页面
                    if self.next_page_end_rule == 'regular':
                        next_page_no = 1
                        next_page_go = False
                        for next_page_end_rule_expression in self.next_page_end_rule_expression:                            
                            m = re.search(next_page_end_rule_expression,next_page_url)
                            if m:
                                if m.group(1)!=None:
                                    next_page_no = int(m.group(1))
                            if next_page_no <= self.next_page_end_regular_value and next_page_no >1:    
                                next_page_go = True
                                break
                        if next_page_go:         
                            yield Request(next_page_url,meta={'next_page_url': next_page_url},callback=self.parse)             
                    else:
                        yield Request(next_page_url,meta={'next_page_url': next_page_url},callback=self.parse)
    
    #爬每个列表的url 从每个列表的url拿到每个列表的下一页连接 和 从每个列表的url拿到最终页面的url     
    def parse_list_page(self,response):
        if 'next_page_url' in response.meta:
            print "parse_next_page:" + response.meta['next_page_url']
        else :
            print "parse_list_page:" + response.url       
        
        if self.item_page_urls_rule == 'xpath':                
            urls = self.extractUrlByXpath(response,self.item_page_rule_expression)
        if self.item_page_urls_rule == 'regular':                
            urls = self.extractUrlByRegular(response,self.item_page_rule_expression , self.item_page_url_ruleXpathExpression)       
        
        self.item_pages_count = self.item_pages_count + len(urls)
        print "this enter page item pages count:" + str(len(urls))
        item_pages_cross_cache_count = 0
        
        for url in urls:
            url = self.getFullUrl(response.url,url)
            if self.isUrlParse(url) == False :
                self.item_pages_cross_cache_count = self.item_pages_cross_cache_count + 1
                item_pages_cross_cache_count = item_pages_cross_cache_count + 1

                yield Request(url, callback=self.parse_item_1)        
        print "this enter page item pages cross cache count:" + str(item_pages_cross_cache_count)
        print "---------------------------------------------"
        print ""
        
        #分析下一页
        if self.next_page_urls_rule =='xpath':
            next_page_urls = self.extractUrlByXpath(response, self.next_page_rule_expression)                

        if self.next_page_urls_rule =='regular':
            next_page_urls = self.extractUrlByRegular(response, self.next_page_rule_expression , self.next_page_url_ruleXpathExpression)

        if len(next_page_urls) > 0 :                
            for next_page_url in next_page_urls:                    
                next_page_url = self.getFullUrl(response.url,next_page_url)                    
                #检查是否达到终止列表页面
                if self.next_page_end_rule == 'regular':
                    next_page_no = 1
                    next_page_go = False
                    for next_page_end_rule_expression in self.next_page_end_rule_expression:                            
                        m = re.search(next_page_end_rule_expression,next_page_url)
                        if m:
                            if m.group(1)!=None:
                                next_page_no = int(m.group(1))
                        if next_page_no <= self.next_page_end_regular_value and next_page_no >1:    
                            next_page_go = True
                            break
                    if next_page_go:         
                        yield Request(next_page_url,meta={'next_page_url': next_page_url},callback=self.parse_list_page)             
                else:
                    yield Request(next_page_url,meta={'next_page_url': next_page_url},callback=self.parse_list_page)        
    
    {$parse_item_function_py_code}
    #如果参数xpathExprssion为空 那么从所有的html去匹配
    #如果参数xpathExprssion不为空 那么从xpth拿到的html里面去匹配   
    def extractUrlByRegular(self,response,ruleExpressionList,xpathExprssion=''):
        urls = []        
        hxs = HtmlXPathSelector(response)
        for ruleExpression in ruleExpressionList:
            tempUrl = []            
            if xpathExprssion != '':
                pageAllUrl = hxs.select(xpathExprssion).extract()
                #print pageAllUrl
            else:
                pat = re.compile(r'\s+href\s*=\s*[\'"]?\s*([^\s\'">]*?)[\'"\s]+?')
                pageAllUrl = re.findall(pat, response.body)
                
            compiled_rule=re.compile(ruleExpression)
            for url in pageAllUrl:
                regUrl = compiled_rule.findall(url)
                if len(regUrl)>0:
                    tempUrl.append(regUrl[0])
            #去掉重复的连接                
            for url in tempUrl:
                if url not in urls:
                    urls.append(url)                
        return urls
    
    def extractUrlByXpath(self,response,ruleExpression=''):
        urls = []
        hxs = HtmlXPathSelector(response)
        if ruleExpression !='':
            urls = hxs.select(ruleExpression).extract()
        return urls
    
    #根据childUrl路径返回绝对路径
    def getFullUrl(self,parnentUrl,childUrl):        
        tempChildUrl = childUrl
        tempChildUrl = list(tempChildUrl)
        tempChildUrlSplit = childUrl.split('/')        
        url = ""        
        tempUrl = parnentUrl.split('/')
        #相对路径 相对于根域名        
        if tempChildUrl[0] =='/':            
            url = tempUrl[0] + "//" + tempUrl[2] + childUrl
            return url
        #相对路径 相对于上一级路径
        if tempChildUrl[0] =='.' and tempChildUrl[1] =='.' and tempChildUrl[2] =='/':            
            tempUrl.pop()
            tempUrl.pop()
            for i in tempUrl:                                
                url = url + i + "/"
            return url + childUrl[3:len(childUrl)]    
                
        #绝对路径
        if tempChildUrlSplit[0] == "http:" :
            return childUrl
        #相对路径 相对于本页的url
        if tempChildUrl[0] !='/':
            last_str = parnentUrl[-1]
            if last_str == "/" :                
                return parnentUrl + childUrl
            else :                  
                last = tempUrl[len(tempUrl)-1]                
                for i in tempUrl:
                    if i != last:
                        url = url + i + "/"                
                return url + childUrl      
      
    def listToStr(self,Value):
        if isinstance(Value,list) :
            Value = string.join(Value)
        return Value
        
    def extractStrByRegular(self,response,xpathExprssion='',ruleExpression=''):               
        if len(xpathExprssion)==0 and len(ruleExpression)>0:
            str_rule=re.compile(ruleExpression)
            regUrl = str_rule.findall(response.url)            
            if len(regUrl)>0:     
                return regUrl[0]        
        
        if len(xpathExprssion)>0 and len(ruleExpression)==0:            
            hxs = HtmlXPathSelector(response)
            str = hxs.select(xpathExprssion).extract()
            if len(str)>0:
                return str[0]
        
        if len(xpathExprssion)>0 and len(ruleExpression)>0:
            hxs = HtmlXPathSelector(response)
            str = hxs.select(xpathExprssion).extract()
            str = str[0].encode("utf-8")        
            str_rule=re.compile(ruleExpression)
            regUrl = str_rule.findall(str)            
            if len(regUrl)>0:     
                return regUrl[0]
 
    def isUrlParse(self,url):
        #如果配置文件设置不限制支爬一次ng
        if self.item_page_url_parse_only_one == False:
            return False
        if self.redisObj.sismember(self.name,url) == False:
            self.redisObj.sadd(self.name,url)
            return False
        else:
            return True
PY;
file_put_contents("../spiders/$scrapyd_port/$project_name/spiders/$spider_name.py", $spider_py_code_file);




//create item
//create item
$item_class_py_code = "# Define here the models for your scraped items
#
# See documentation in:
# http://doc.scrapy.org/topics/items.html
from scrapy.item import Item, Field
from fsdal.dal import db,Connection,set_conn
from string import join

";
$i = 0;
$item_child_name_py_code = "";
$item_child_name_self_py_code = "";
foreach ($items as $item){
    $i++;
    $item_children = $item->children();    
    $table_name ="";
    foreach($item->attributes() as $key => $value){
        if($key =="table_name"){
            $table_name = $value;
        }
    }
    foreach($item_children as $item_child){
        $name = $item_child->getName();
        $item_child_name_py_code .= "    ".$name."= Field()
";
        $item_child_name_self_py_code .="            $name=self.get('$name'),
";
    }
    

 
}
    $item_class_py_code .= <<<PY
class parse_item_1_Item(Item):
{$item_child_name_py_code}
    def __str__(self):
        set_conn(Connection(host="{$host}:{$port}",user="{$db_user}",password="{$db_pass}",database="{$db_name}"))
        db.{$table_name}.insert(
{$item_child_name_self_py_code}
	)
        return "ok"
        

PY;
file_put_contents("../spiders/$scrapyd_port/$project_name/{$spider_name}items.py", $item_class_py_code);
