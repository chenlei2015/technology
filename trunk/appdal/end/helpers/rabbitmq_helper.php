<?php

    function handle_package($envelope, $q){
        $msg = $envelope->getBody();
        $body = json_decode($msg,true);
        sleep(1);
        echo isset($body['package_id']) ? '处理包裹号:'.$body['package_id']."任务结束" : '接收包裹号失败';
        $q->ack($envelope->getDeliveryTag()); //手动发送ACK应答
    }

    function test(){
        echo "test";
    }
