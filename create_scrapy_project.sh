#!/bin/bash

if [ $1 ]; then
PORT=$1
fi

if [ $2 ]; then
PROJECT_NAME=$2
fi

#读取输入project name ; 创建爬虫projiect
if [ ! $PORT ]; then 
read -p "Please select the port to run this project [$_PORT] " PORT
fi

if [ ! $PROJECT_NAME ]; then
read -p "Please select the scrapy project name [$_PROJECT_NAME] " PROJECT_NAME
fi


#所有爬虫项目的总路径
all_project_path="/home/scrapy/project"

#总路径不存在就创建
if [ ! -e "$all_project_path" ] ; then
    mkdir -p $all_project_path
    chown -R scrapy:nogroup $all_project_path  
fi


if [ ! -e "${all_project_path}/${PORT}" ] ; then
    mkdir -p "${all_project_path}/${PORT}"
fi
cd $all_project_path/$PORT

#检查项目路径是否存在 存在就删除 避免xml文件夹里面减少文件后 原来的爬虫文件还在
if [ -e "${all_project_path}/${PORT}/${PROJECT_NAME}" ] ; then
    rm -rf "${all_project_path}/${PORT}/${PROJECT_NAME}"
fi

#检查目录路径是否存在 ; 不存在就创建项目～～～
if [ ! -e "${all_project_path}/${PORT}/${PROJECT_NAME}" ] ; then    
    scrapy startproject $PROJECT_NAME

#修改对应项目的scrapy.cfg
echo  "
[deploy:${PROJECT_NAME}]
url= http://localhost:${PORT}/
project = ${PROJECT_NAME}
" >> "${all_project_path}/${PORT}/${PROJECT_NAME}/scrapy.cfg"

#修改对应项目的settings.py
#为了统计的时候
cat <<EOF >> "${all_project_path}/${PORT}/${PROJECT_NAME}/${PROJECT_NAME}/settings.py"
SCRAPYD_PORT = ${PORT}

EOF
fi

#创建爬虫文件夹
php_create_spiders_path="${all_project_path}/../spiders/${PORT}/${PROJECT_NAME}"
#echo "${php_create_spiders_path}/spiders"
if [ ! -e "${php_create_spiders_path}/spiders/" ] ; then    
    mkdir -p "${php_create_spiders_path}/spiders/"
fi


cd "${all_project_path}/../create_scrapy_project/"

#删除之前生成爬虫文件
rm -f "${php_create_spiders_path}/"*.py
rm -f "${php_create_spiders_path}/spiders/"*.py

#生成爬虫文件
filelist=`ls ${all_project_path}'/../xml/'${PORT}'/'${PROJECT_NAME}`
for filename in $filelist
do
    if [ "${filename##*.}" == "xml" ]; then
        spidername=${filename%.*}        
        #php脚本已经生成了爬虫文件在 $php_create_spiders_path      
        php create_spider.php $PROJECT_NAME $spidername $PORT
    fi
done

#把爬虫文件复制到相应scrapy project目录里面
cp -a "${php_create_spiders_path}/"* "${all_project_path}/${PORT}/${PROJECT_NAME}/${PROJECT_NAME}/"


#部署项目
cd "${all_project_path}/${PORT}/${PROJECT_NAME}"
scrapy deploy $PROJECT_NAME

#删除xml文件里面没有project
cd "${all_project_path}/../create_scrapy_project/"
php del_project.php ${PORT}
