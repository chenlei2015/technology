#!/bin/bash
#止盈止损每天平仓 每分钟一次
cd /home/wwwroot/yzzb/git/api && /usr/local/php/bin/php index.php  index/index/checkStrategyAlltime  >> /home/task/log/strategy/$(date '+%Y%m%d').log
