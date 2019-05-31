#!/bin/bash
#每天查看红包有效期
cd /home/wwwroot/yzzb/git/api && /usr/local/php/bin/php index.php  index/index/checkpacket  >> /home/task/log/packet/$(date '+%Y%m%d').log
