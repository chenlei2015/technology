#!/bin/bash
#每天计算递延费 每分钟一次
cd /home/wwwroot/yzzb/git/api && /usr/local/php/bin/php index.php  index/index/checkDeferMoney  >> /home/task/log/defer/$(date '+%Y%m%d').log
