## Swoole-Task ##

### Swoole-Task 用法示例 ###
------------------------------
- 路由说明

  127.0.0.1:9510/ctrl/action?paramas=xxx
  
  ctrl对应app/ctrl目录下的xxxCtrl.php文件(xxxCtrl.php中的class名称也必须是xxxCtrl,继承base\Ctrl)
  
  action对应xxxCtrl.php文件中的xxxAction(action 的最后必须返回$this->ret,即最后一句 return $this->ret)
  
  127.0.0.1:9510?op=ctrl.action&params=xxx 等同于 127.0.0.1:9510/ctrl/action?paramas=xxx
  
- 启动swoole-task后发起请求(假定监控的ip和端口是127.0.0.1:9510)

  curl "127.0.0.1:9510/ctrl/action?paramas=xxx" 或者 curl "127.0.0.1:9510?op=ctrl.action&params=xxx"
  
  投递任务到swoole-task进行处理
  
- swoole-task目录结构说明

  app  swoole-task具体处理业务逻辑的地方
  
  app\config 配置文件目录，根据环境dev,test,prod来加载配置,环境的配置在config/swoole.ini的配置项 env
  
  app\ctrl controller文件所在目录，处理具体业务逻辑，继承base\Ctrl这个类
  
  app\dao  数据访问层，操作数据库的方法
  
  app\helper 公共方法类
  
  app\vendor 如果依赖composer 第三方库，在在app目录下创建composer.json
  
  base 核心框架类，Ctrl Dao Helper App 四个类，命名空间base
  
  config swoole-task 配置文件目录swoole.ini，配置参数类容参考源码说明
  
  tmp 临时目录，日志swoole-task中间文件等等在此目录下存放

> swoole-task 本身是一个比较简单的基于swoole扩展的异步任务处理框架，更详细使用方法看源码，或者在github留issues，有好的建议我会根据情况及时改进

### Swoole-Task服务管理脚本功能说明 ###
----------------------------------------
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
