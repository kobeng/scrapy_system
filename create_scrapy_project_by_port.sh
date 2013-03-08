#!/bin/bash

if [ $1 ]; then
PORT=$1
fi

#所有爬虫项目的总路径
all_project_path="/home/scrapy/project"

#生成爬虫文件
filelist=`ls ${all_project_path}'/../xml/'${PORT}'/'`

for dirname in $filelist
do    
    if test -d ${all_project_path}'/../xml/'${PORT}'/'${dirname};then
        cd ${all_project_path}'/../create_scrapy_project/'
        ./create_scrapy_project.sh ${PORT} ${dirname}                
    fi
done