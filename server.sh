#!/bin/bash
if [ -z "$1" ]; then 
    echo "Usage: sh server.sh start|stop|restart"
    exit
fi


command=$1
if [ $command = "start" ]; then
    nohup php server.php &
elif [ $command = "stop" ]; then
    ps -ef |grep server.php|awk '{print $2}'|xargs kill -9
elif [ $command = "restart" ]; then 
    ps -ef |grep server.php|awk '{print $2}'|xargs kill -9
    nohup php server.php &
else 
    echo "command is not valid. Usage: sh server.sh start|stop|restart"
    exit
fi 


