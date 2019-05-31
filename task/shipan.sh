#!/bin/bash
#实际买卖 实盘接口 每分钟一次
cd /home/wwwroot/yzzb/git/api && /usr/local/php/bin/php index.php  index/index/checkshipan  >> /home/task/log/shipan/$(date '+%Y%m%d').log
