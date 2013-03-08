<?php
$items_array = array();
$i = 0;
//把所有item都找出来
foreach ($items as $item){
    $item_children = $item->children();    
    foreach($item_children as $item_child){        
        $items_array[$i] .= $item_child->getName().',';        
    }
    $i++;
}


//判断item类型 是same 还是diff
foreach($xml_dom->items->attributes() as $k =>$v){
    switch ($k) {
        case 'type':
            $items_type = $v;
            break;
        default:
            break;
    }    
}
$parse_item_function_py_code = "";

if($items_type=='same'){
    $item_attributes = $item->attributes();
     
    foreach ($items as $item){
        //print_r($item->attributes());
        $item_children = $item->children();
        $item_py_code_spider_var = "";
        $item_next_page_spider_var = "";
        
        foreach($item_children as $item_child){
            $item_child_attributes = $item_child->attributes();
            //print_r($item_child_attributes);
            $name  = $item_child->getName();            
            $value = $item_child[0];
            if(empty($value)){
                continue;
            }
            switch ($value) {
                case "@self_url@":
$item_py_code_spider_var .="
        item['$name'] = response.url
";          
                    break;
            
                case "@text@":
                    if(!empty($item_attributes['next_page'])){
$item_single_py_code_spider_var .="
            item['$name'] = \"{$item_child_attributes["text"]}\"";                        
                    }else{
$item_py_code_spider_var .="
        item['$name'] = \"{$item_child_attributes["text"]}\"
";                        
                    }
                    
                    //print_r($item_child_attributes);
                    break;

                default:
                     $value_arr = explode('|', $value);
                     //如果有下一页属性
                     if(!empty($item_attributes['next_page'])){
$item_single_py_code_spider_var .="
            item['$name'] = self.listToStr(hxs.select(u'{$value_arr[0]}').extract())";
                        //如果这个item 
                        if(sizeof($value_arr)>1){
$item_single_py_code_spider_var .="            
            if(len(item['$name'])<1):
                item['$name'] = self.listToStr(hxs.select(u'{$value_arr[1]}').extract())
            ";                            
                        }

                        if($item_child_attributes['add']=='true'){
$item_double_py_code_spider_var .="
            item['$name'] = item['$name'] + self.listToStr(hxs.select(u'$value').extract())";
                        }
                     }else{
  $item_py_code_spider_var .="
        item['$name'] = self.listToStr(hxs.select(u'$value').extract())
";                        
                     }

            }               
        }
        
        
        $item_attributes = $item->attributes();
        //var_dump($item_attributes['next_page_rule']);
        if(!empty($item_attributes['next_page'])){
            $create_item_class_py_code = "if 'item' not in response.meta:\n";
            $create_item_class_py_code .= "            item = import_and_get_mod(self.itemsName).parse_item_1_Item()";
            $create_item_class_py_code .= "            $item_single_py_code_spider_var\n";
            $create_item_class_py_code .= "        else:\n";
            $create_item_class_py_code .= "            item = response.meta['item']";
            $create_item_class_py_code .= "            $item_double_py_code_spider_var";                      
            $item_next_page_spider_var=
<<<PY
next_url = hxs.select(u'{$item_attributes['next_page']}').extract()            
        if len(next_url) > 0 and len(next_url[0]) > 0  and self.getFullUrl(response.url,next_url[0]) != response.url:
            next_url[0] = self.getFullUrl(response.url,next_url[0])
            yield Request(next_url[0], meta={'item': item}, callback=self.parse_item_1)
        else :
            print item
            yield item
            
PY;
            
        }else{
            $create_item_class_py_code = "item = import_and_get_mod(self.itemsName).parse_item_1_Item()";
            $item_next_page_spider_var = "print item 
        yield item";
        }        
    }
    $parse_item_function_py_code .= 
<<<PY
def parse_item_1(self,response):
        print "parse_item_1:"+response.url
        hxs = HtmlXPathSelector(response)
        {$create_item_class_py_code}
        {$item_py_code_spider_var}
        {$item_next_page_spider_var}             
PY;
}

////////////////////////////////////////////////////////////////////////////////////////////////

if($items_type == 'diff'){    
    $i = 0;
    
    foreach ($items as $item){
        $item_children = $item->children();
        $item_py_code_spider_var = "";
        $item_next_page_spider_var = "";   
        foreach($item_children as $item_child){
            $item_child_attributes = $item_child->attributes();
            $name  = $item_child->getName();
            $value = $item_child[0];
            if(empty($value)){
                continue;
            }
            switch ($value) {
                case "@self_url@":
$item_py_code_spider_var .="
        item['$name'] = response.url
";          
                break;
            
                case "@text@":                    
$item_py_code_spider_var .="
        item['$name'] = \"{$item_child_attributes["text"]}\"
";                                            
                    break;

                default:
                    $value_arr = explode('|', $value);                    
$item_py_code_spider_var .="
        item['$name'] = self.listToStr(hxs.select(u'{$value_arr[0]}').extract())
";
                    if(sizeof($value_arr)>1){
$item_py_code_spider_var .="            
        if(len(item['$name'])<1):
            item['$name'] = self.listToStr(hxs.select(u'{$value_arr[1]}').extract())
";                            
                    }
            }
            
        }
        //生成其他item
        for($iii=$i;$iii<sizeof($items_array);$iii++){
            if ($iii > $i) {
                $arr = explode(",", $items_array[$iii]);
                array_pop($arr);
                foreach ($arr as $arr_v) {
                    $item_py_code_spider_var .="        item['$arr_v'] = ''\n";
                }
            }            
        }
        $i++;
        $item_attributes = $item->attributes();        
        if(!empty($item_attributes['next_page']) || !empty($item_attributes['next_page_rule'])){
            $ii = 0;
            $ii = $i+1;
            //通过组合url的方式去获取下一个item的内容
            if($item_attributes['next_page_rule']=='url'){
                $rule_no = $item_attributes['rule_no'];
                $rule_no = $rule_no - 1; 
                $url = $rules[$rule_no]->url;
                $url = $url . $rules[$rule_no]->link_symbols;
                //echo $url."\n";
                //echo $rules[$rule_no]->parame_link_symbols."\n";
                //echo $rules[$rule_no]->parame_value_link_symbols."\n";
                //print_r($rules[$rule_no]->parame_value);
                $rules_children = $rules[$rule_no]->parame_value->children();    
                $getValuePy = "";
                $linkValue = "";
                foreach($rules_children as $rule_children){
                    $getValuePy .= "{$rule_children->getName()} = self.extractStrByRegular(response,$rule_children->xpath,$rule_children->regular_in_xpath)\n        ";                 
                    $linkValue  .= "\"{$rule_children->getName()}{$rules[$rule_no]->parame_value_symbols}\" + ".$rule_children->getName()." + \"{$rules[$rule_no]->parame_link_symbols}\" + ";
                }
                $linkValue = substr($linkValue,0,strlen($linkValue)-8);
            $item_next_page_spider_var=<<<PY
$getValuePy                
        next_url = "$url" + $linkValue                
        if len(next_url) > 0:
            yield Request(next_url, meta={'item': item}, callback=self.parse_item_{$ii})
        else :
            print item
            yield item
PY;

            }else{//默认是xpath获取下一个item的连接 
            $item_next_page_spider_var=<<<PY
next_url = hxs.select(u'{$item_attributes['next_page']}').extract()                
        if len(next_url) > 0 and len(next_url[0]) > 0:
            next_url[0] = self.getFullUrl(response.url,next_url[0])
            yield Request(next_url[0], meta={'item': item}, callback=self.parse_item_{$ii})
        else :
            print item
            yield item
PY;
            }
        }else{
        $item_next_page_spider_var = "print item 
        yield item";    
        }
        $item_list = "";
        if($i==1){
            $item_list= "item = import_and_get_mod(self.itemsName).parse_item_1_Item()";    
        }else{
            $item_list= "item = response.meta['item']";
        }
        $parse_item_function_py_code .= <<<PY

    def parse_item_{$i}(self,response):
        print "parse_item_{$i}:"+response.url
        hxs = HtmlXPathSelector(response)
        {$item_list}
        {$item_py_code_spider_var}
        {$item_next_page_spider_var}
            
PY;
    }
}

