<?php
/**
 * 应用入口App,框架的唯一入口
 */

namespace base;


class App
{
    /**
     * 当前请求协议 key="fromId#taskId" value="ctrl.action"
     * @var array
     */
    private $_op = [];

    /**
     * 当前请求ctrl，key="fromId#taskId" value=ctrlClass(包括namespace)
     * @var array
     */

    private $_ctrl = [];

    /**
     * 当前请求action, key="fromId#taskId" value=actionName
     * @var array
     */
    private $_action = [];

    /**
     *  当前请求 get+post数据 key="fromId#taskId"
     * @var array
     */
    private $_request = [];
    /**
     * 当前请求是否debug模式
     * @var array
     */
    private $_debug = [];


    /**
     * 返回App的单例实例,swoole-http-server启动加载在内存不再改变
     * @var null
     */
    private static $_app = null;


    /**
     * 读取swoole/config/swoole.ini中的env配置的值
     * 当前环境 dev,test,product
     * @var string
     */
    public static $env;

    /**
     * swoole-http-server
     * @var  object
     */
    public static $server;

    /**
     * 常驻内存
     * app 默认配置&加载config目录配置
     * @var array
     */
    public static $conf = [
        //namespace必须和目录名称一致 轻易不要去改变，按照这个规则即可`
        'path'      => [
            'log'    => 'tmp/log',
            'app'    => 'app',
            'vendor' => 'vendor',//注意vendorPath优先加载，所以必须写在第一个
            'config' => 'config',
            'ctrl'   => 'ctrl',
            'dao'    => 'dao',
            'helper' => 'helper',
        ],
        'namespace' => [
            'app'    => 'app',
            'ctrl'   => 'app\ctrl',
            'dao'    => 'app\dao',
            'helper' => 'app\helper',
        ],
        //config 目录配置加载
        'conf'      => [],
    ];

    //单例需要
    private function __construct()
    {

    }

    //单例需要
    private function __clone()
    {

    }

    /**
     * TODO 继续完善优化规则
     * 简单路由规则实现
     * @param $request
     * @param $id
     * @return mixed 错误返回代码 成功返回op
     */
    private function route($request, $id)
    {
        $this->_request[$id] = [];
        if (!empty($request->get)) {
            $this->_request[$id] = array_merge($this->_request[$id], $request->get);
        }
        if (!empty($request->post)) {
            $this->_request[$id] = array_merge($this->_request[$id], $request->post);
        }
        $route = ['index', 'index'];
        if (!empty($request->server['path_info'])) {
            $route = explode('/', trim($request->server['path_info'], '/'));
        }
        if (!empty($this->_request[$id]['op'])) {
            //请求显式的指定路由:op=ctrl.action
            $route = explode('.', $this->_request[$id]['op']);
        }
        if (count($route) < 2) {
            return 1;
        }
        $this->_op[$id] = implode('.', $route);
        $ctrl = '\\' . self::$conf['namespace']['ctrl'] . '\\' . ucfirst($route[0]) . 'Ctrl';
        $action = lcfirst($route[1]) . 'Action';
        if (!class_exists($ctrl) || !method_exists($ctrl, $action)) {
            return 2;
        }
        $this->_ctrl[$id] = $ctrl;
        $this->_action[$id] = $action;

        //TODO debug模式设置优化
        //设置请求调试模式
        $debug = false;
        if (self::$env == 'dev') {
            //如果是开发环境，优先级次之 debug 直接是true
            //$debug = true;
            //响应群众呼声,debug模式自己添加即可
            $debug = false;
        }
        if (!empty($this->_request[$id]['debug'])) {
            //请求中携带 debug 标识，优先级最高
            $debug = $this->_request[$id]['debug'];
        }
        $debug = ($debug === true || $debug === 'yes' || $debug === 'true' || $debug === 1 || $debug === '1') ? true : false;
        $this->_debug[$id] = $debug;

        return $this->_op[$id];
    }

    /**
     * app conf 获取 首先加载内置配置
     * @param string $key
     * $param mixed  $default
     * @return array
     */
    public static function getConfig($key = '', $default = '')
    {
        if ($key === '') {
            return self::$conf;
        }
        $value = [];
        $keyList = explode('.', $key);
        $firstKey = array_shift($keyList);
        if (isset(self::$conf[$firstKey])) {
            $value = self::$conf[$firstKey];
        } else {
            if (!isset(self::$conf['conf'][$firstKey])) {
                return $value;
            }
            $value = self::$conf['conf'][$firstKey];
        }
        //递归深度最大5层
        $i = 0;
        do {
            if ($i > 5) {
                break;
            }
            $k = array_shift($keyList);
            if (!isset($value[$k])) {
                $value = empty($default) ? [] : $default;

                return $value;
            }
            $value = $value[$k];
            $i++;
        } while ($keyList);

        return $value;
    }

    /**
     * TODO 继续优化完善
     * 获取一个app的实例
     * @param $server
     * @return App|null
     */
    public static function getApp($server = null)
    {
        if (self::$_app) {
            return self::$_app;
        }

        self::$server = $server;
        //path 和 namespace 暂时写死
        self::$conf['path']['log'] = SWOOLE_PATH . '/' . self::$conf['path']['log'];
        self::$conf['path']['app'] = SWOOLE_PATH . '/' . self::$conf['path']['app'];
        //首次运行创建文件夹
        if (!file_exists(self::$conf['path']['log'])) {
            mkdir(self::$conf['path']['log']);
        }
        if (!file_exists(self::$conf['path']['app'])) {
            mkdir(self::$conf['path']['app']);
        }
        foreach (self::$conf['path'] as $k => $v) {
            if ($k == 'app' || $k == 'log') {
                continue;
            }
            $dir = self::$conf['path']['app'] . '/' . $v;
            self::$conf['path'][$k] = $dir;
            //首次创建文件夹
            file_exists($dir) or mkdir($dir);
            //vendor 自动加载
            if ($k == 'vendor' && file_exists(self::$conf['path']['vendor'] . '/autoload.php')) {
                include self::$conf['path']['vendor'] . '/autoload.php';
            }
            //app config 目录配置加载
            if ($k == 'config') {
                self::$env = self::$server->setting['env'];
                $configDir = $dir . '/' . self::$env;
                if (file_exists($configDir)) {
                    $files = Helper::getFiles($configDir);
                } else {
                    $files = Helper::getFiles($dir . '/dev');
                }
                foreach ($files as $inc) {
                    $file = pathinfo($inc);
                    self::$conf['conf'][$file['filename']] = include($inc);
                }
            }
            //注册类自动加载
            if (in_array($k, ['ctrl', 'dao', 'helper'])) {
                spl_autoload_register(function ($className) use ($k, $dir) {
                    $path = array_filter(explode('\\', $className));
                    $className = array_pop($path);
                    if (stripos($className, ucfirst($k)) === false) {
                        return false;
                    }
                    //set_include_path($dir);
                    if ($path) {
                        foreach ($path as $d) {
                            if (strpos($dir, $d) === false) {
                                //$className = $d . '\\' . $className;
                                $dir .= DIRECTORY_SEPARATOR . $d;
                            }
                        }
                    }
                    set_include_path($dir);
                    //WARN spl_autoload函数默认把类名转化为小写，然后include，如果操作系统不区分大小写没事，区分就坑爹
                    include $className . '.php';

                    return true;
                });
            }
        }
        self::$_app = new self();

        return self::$_app;
    }

    public function getDebug($id)
    {
        return $this->_debug[$id];
    }


    /**
     * @param string $msg msg body
     * @param array $attach [filename=>file-type, 'test.jpg'=>'image/jpeg']
     * @param array $sendTo send to email
     */
    public function sendMail($msg, $attach = [], $sendTo = [], $title = '')
    {
        $msg = is_string($msg) ? $msg : var_export($msg, true);
        $msg = "<pre>{$msg}</pre>";
        $config = self::getConfig('app.mail');
        if ($title) {
            $config['from']['php-service@jrq.com'] = $title;
        }
        $sendTo = $sendTo ? $sendTo : $config['to'];
        $transport = \Swift_SmtpTransport::newInstance($config['smtp'], $config['port'], $config['security'])
            ->setEncryption('ssl')
            ->setUsername($config['user'])
            ->setPassword($config['password']);
        $mailer = \Swift_Mailer::newInstance($transport);
        $message = \Swift_Message::newInstance()
            ->setContentType('text/html')
            ->setSubject($config['subject'])
            ->setFrom($config['from'])
            ->setTo($sendTo)
            ->setBody($msg, 'text/html');
        if ($attach) {
            $attach = is_array($attach) ? $attach : [$attach];
            foreach ($attach as $k => $v) {
                $file = \Swift_Attachment::fromPath($k, $v)->setFilename(basename($k));
                $message->attach($file);
            }
        }
        //TODO 可附加额外信息和处理信息
        try {
            $mailer->send($message);
        } catch (\Swift_ConnectionException $e) {
            $this->error('There was a problem communicating with SMTP: ' . $e->getMessage());
        }
    }

    public function logger($msg, $type = null)
    {
        if (empty($msg)) {
            return false;
        }
        //参数处理
        $type = $type ? $type : 'debug';
        if (!is_string($msg)) {
            $msg = var_export($msg, true);
        }
        $msg = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;

        $maxSize = 2097152;//2M
        list($y, $m, $d) = explode('-', date('Y-m-d'));
        $dir = self::$conf['path']['log'] . DIRECTORY_SEPARATOR . $y . $m;
        $file = "{$dir}/{$type}-{$d}.log";
        if (!file_exists($dir)) {
            mkdir($dir, 0777);
        }
        if (file_exists($file) && filesize($file) >= $maxSize) {
            $a = pathinfo($file);
            $bak = $a['dirname'] . DIRECTORY_SEPARATOR . $a['filename'] . '-bak.' . $a['extension'];
            if (!rename($file, $bak)) {
                echo "rename file:{$file} to {$bak} failed";
            }
        }
        error_log($msg, 3, $file);
    }

    public function debug($msg)
    {
        $this->logger($msg, 'debug');
    }

    public function error($msg)
    {
        $this->logger($msg, 'error');
    }

    public function warn($msg)
    {
        $this->logger($msg, 'warn');
    }

    public function info($msg)
    {
        $this->logger($msg, 'info');
    }

    public function sql($msg)
    {
        $this->logger($msg, 'sql');
    }

    public function op($msg)
    {
        $this->logger($msg, 'op');
    }

    /**
     * 执行一个请求
     * @param $request
     * @param $taskId
     * @param $fromId
     * @return mixed
     */
    public function run($request, $taskId, $fromId)
    {
        $id = "{$fromId}#{$taskId}";
        //请求运行开始时间
        $runStart = time();
        //请求运行开始内存
        $mem = memory_get_usage();

        //TODO  before route
        $op = $this->route($request, $id);
        if (is_int($op)) {
            if ($op == 1) {
                $error = 'missing route';
                $this->error($error);

                return false;
            }
            if ($op == 2) {
                $error = "op resolve failed:{$this->_op[$id]}";
                $this->error($error);

                return false;
            }
        }

        //TODO after route
        try {
            $ctrl = new $this->_ctrl[$id]($this->_request[$id], $this->_op[$id], $id);

            //before action:比如一些ctrl的默认初始化动作,加载dao等
            if (method_exists($ctrl, 'init')) {
                //执行action之前进行init
                $ctrl->init();
            }
            $res = $ctrl->{$this->_action[$id]}();
            //FIXME $res 返回如果不是数组会报错
            //after action
            if (method_exists($ctrl, 'done')) {
                //执行完action之后要做的事情
                $ctrl->done();
            }

            //请求运行时间和内存记录
            $runSpend = time() - $runStart;//请求花费时间
            $info = "op:{$op}, spend: {$runSpend}s, memory:" . Helper::convertSize(memory_get_usage() - $mem) . ", peak memory:" . Helper::convertSize(memory_get_peak_usage());
            $info .= ",date:{$ctrl->date}";
            $this->op($info);


            return $res;
        } catch (\Exception $e) {
            $this->error($e->getMessage() . PHP_EOL . $e->getTraceAsString());
        } finally {
            //WARN 请求结束后必须释放相关的数据,避免内存使用的无限增长
            unset($this->_op[$id], $this->_ctrl[$id], $this->_action[$id], $this->_request[$id], $this->_debug[$id]);
        }
    }
}
