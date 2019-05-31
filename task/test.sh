#!/bin/bash
#止盈止损每天平仓
cd /home/wwwroot/yzzb/git/api && /usr/local/php/bin/php  index.php index/index/index  >> /home/task/log/test/$(date '+%Y%m%d').log
