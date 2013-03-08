#-*- coding: UTF-8 -*-
import redis,sys

redisObj = redis.StrictRedis("127.0.0.1", 6379, 0)
urlList = redisObj.smembers(sys.argv[1])
print urlList 
for url in urlList:
    redisObj.srem(sys.argv[1],url)

print redisObj.smembers(sys.argv[1]) 