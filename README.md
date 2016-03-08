### Swoole-Task服务管理脚本功能说明 ###

> 1 如果swoole.php 脚本所在目录config/swoole.ini 文件不存在，可以直接删除config目录，会自动创建配置文件，创建后的文件根据需求修改  
  2 注意swoole.ini 的参数dev，这个取值为dev,test,prod 根据此值读取swoole/app的配置文件

-----------------------------
##### 用法介绍 #####
------------
- 服务启动

```sh
#启动服务,不指定绑定端口和ip，则使用config目录下的swoole.ini配置
php swoole.php start 
#启动服务 指定ip 和 port
php swoole.php -h127.0.0.1 -p9510 start
#启动服务 守护进程模式
php swoole.php -h127.0.0.1 -p9510 -d start
#启动服务 非守护进程模式
php swoole.php -h127.0.0.1 -p9510 -D start
#启动服务 指定进程名称(显示进程名为 swooleServ-9510-[master|manager|event|task]
php swoole.php -h 127.0.0.1 -p 9510 -n 9510 start
```

- 服务停止(停止服务最少要指定端口)

```sh
php swoole.php -p 9510 stop
php swoole.php -h 127.0.0.1 - p 9510 stop
```

- 服务重启(至少指定端口)

```sh
php swoole.php -p 9510 restart
php swoole.php -h 127.0.0.1 - p 9510 restart
```

- 服务状态(必须指定ip 和 端口)

```sh
php swoole.php -h 127.0.0.1 - p 9510 status
```

- swoole-task所有启动实例进程列表(一台服务器swoole-task可以有多个端口绑定的实例)

```sh
php swoole.php list
```

##### 参数说明 #####
------------
- --help
  显示帮助
- -d, --daemon
  指定此参数，服务以守护进程模式运行，不指定读取配置文件值
- -D, --nondaemon
  指定此参数，以非守护进程模式运行,不指定则读取配置文件值
- -h, --host  
  指定监听ip,例如 php swoole.php -h 127.0.0.1
- -p, --port
  指定监听端口port， 例如 php swoole.php -h 127.0.0.1 -p 9520
- -n, --name 
  指定服务进程名称，例如 php swoole.php -n test start, 则进程名称为SWOOLE_TASK_NAME_PRE-name

##### 命令说明 #####
------------
- start 启动服务

  可指定服务绑定ip 端口 及 是否守护进程模式，还有启动后进程名称(进程名称前缀默认为swooleServ-, 指定后 swooleServ-name-[master|manager|event|task])
- stop  停止服务

  必须指定端口参数 -p(--port)
- restart 重启服务

  必须指定端口参数 -p(--port) 后续是否需要根据进程名称重启根据实际情况来看(--TODO)
- status 

  查看指定ip和端口的服务状态，tasking_num是指当前正在运行的任务
- list 

  查看当前服务器上运行的swoole-task实例，
  返回结果显示服务进程的 USER PID RSS(kb)[使用内存] STAT[进程状态] START[进程运行开始时间] COMMAND[进程命令或者进程名称]
