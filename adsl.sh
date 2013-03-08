#!/bin/bash
# 根据key返回要获取的正则表达式
getRegexp()
{
	case $1 in
		'value') echo '[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}';;
		'type') echo '[A-Z]\+';;
		'name') echo '[-_.A-Za-z*]\+';;
		'ttl'|'id') echo '[0-9]\+';;
		'line') echo '[^"]\+';;
	esac
}

function adsl
{

sudo poff dsl-provider 

sleep 30

sudo pon dsl-provider

sleep 2
}

ipUrl='http://members.3322.org/dyndns/getip'

oldip=$(curl -s $ipUrl|grep -o $(getRegexp 'value'))
newip=$(curl -s $ipUrl|grep -o $(getRegexp 'value'))
c=0
while [ "$oldip" == "$newip" ]; do
    adsl
    newip=$(curl -s $ipUrl|grep -o $(getRegexp 'value'))
    echo `date +"%Y-%m-%d %H:%M:%S"`
    echo "oldIp:${oldip}"
    echo "newIp:${newip}"
    
    let c+=1
    if [ "$c" == 4 ]; then
	exit 1
    fi

done

lynx -source -auth=kobeng88:kobeng831207 "http://members.3322.net/dyndns/update?system=dyndns&hostname=scrapy072.f3322.org"
#lynx -source -auth=kobeng8:kobeng831207 "http://members.3322.net/dyndns/update?system=dyndns&hostname=scrapy71.f3322.org"