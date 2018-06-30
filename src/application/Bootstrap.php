<?php

/**
 * 所有在Bootstrap类中, 以_init开头的方法, 都会被Yaf调用,
 * 这些方法, 都接受一个参数:Yaf_Dispatcher $dispatcher
 * 调用的次序, 和申明的次序相同
 */
class Bootstrap extends Yaf_Bootstrap_Abstract
{
    /** @var  \Noodlehaus\Config */
    private $_config;

    public function _initConfig()
    {
        $dir = Yaf_Application::app()->getConfig()['application']['configDir'];
        $this->_config = new \Noodlehaus\Config(
            [
                $dir . ENV(),
            ]
        );
        Yaf_Registry::set("config", $this->_config);
        Yaf_Registry::set("_config", $this->_config);
    }

    public function _initLog()
    {
        //application log
        $logger = new \Monolog\Logger($this->_config['logger']['app']['name']);
        $UidProcessor = new \Monolog\Processor\UidProcessor();
        $logger->pushProcessor(new \Monolog\Processor\IntrospectionProcessor());
        $logger->pushProcessor($UidProcessor);
        $stream = new \Monolog\Handler\StreamHandler(
            $this->_config['logger']['app']['path'] . '.' . date('ymd'),
            $this->_config['logger']['app']['level'],
            $this->_config['logger']['app']['bubble'],
            0766
        );
        $logger->pushHandler($stream);
        Yaf_Registry::set("_log", $logger);

        //admin log
        $logger = new \Monolog\Logger($this->_config['logger']['admin']['name']);
        $UidProcessor = new \Monolog\Processor\UidProcessor();
        $logger->pushProcessor(new \Monolog\Processor\IntrospectionProcessor());
        $logger->pushProcessor($UidProcessor);
        $stream = new \Monolog\Handler\StreamHandler(
            $this->_config['logger']['admin']['path'] . '.' . date('ymd'),
            $this->_config['logger']['admin']['level'],
            $this->_config['logger']['admin']['bubble'],
            0766
        );
        $logger->pushHandler($stream);
        Yaf_Registry::set("_adminlog", $logger);

        //com log
        $logger = new \Monolog\Logger($this->_config['logger']['com']['name']);
        $logger->pushProcessor($UidProcessor);
        $stream = new \Monolog\Handler\StreamHandler(
            $this->_config['logger']['com']['path'] . '.' . date('ymd'),
            $this->_config['logger']['com']['level'],
            $this->_config['logger']['com']['bubble'],
            0766
        );
        $logger->pushHandler($stream);
        \App\Library\Help\ETS::setLogger($logger);
        Yaf_Registry::set("com_logger", $logger);

        //monitor log
        $logger = new \Monolog\Logger($this->_config['logger']['monitor']['name']);
        $stream = new \Monolog\Handler\StreamHandler(
            $this->_config['logger']['monitor']['path'] . '.' . date('ymd'),
            $this->_config['logger']['monitor']['level'],
            $this->_config['logger']['monitor']['bubble'],
            0766
        );
        $logger->pushHandler($stream);
        Yaf_Registry::set("_monitorlog", $logger);

        // trace log
        $logger = new \Monolog\Logger($this->_config['logger']['trace']['name']);
        $stream = new \Monolog\Handler\StreamHandler(
            $this->_config['logger']['trace']['path'] . '.' . date('ymd'),
            $this->_config['logger']['trace']['level'],
            $this->_config['logger']['trace']['bubble'],
            0766
        );
        $output = "%message%\n";
        $formatter = new Monolog\Formatter\LineFormatter($output);
        $stream->setFormatter($formatter);
        $logger->pushHandler($stream);
        Yaf_Registry::set("_tracelog", $logger);
    }


//    public function _initRedis(Yaf_Dispatcher $dispatcher)
//    {
//        \App\Library\Core\Redis::setGlobalConfig($this->_config->get('redis'));
//    }

    public function _initDefaultName(Yaf_Dispatcher $dispatcher)
    {
        $dispatcher->setDefaultModule("Index")->setDefaultController("Index")->setDefaultAction("index");
    }

    public function _initPlugin(Yaf_Dispatcher $dispatcher)
    {
        $dispatcher->registerPlugin(new CorsPlugin($this->_config->get('cors', [])));

        $dispatcher->registerPlugin(new AuthPlugin());
    }

    public function _initRouter(Yaf_Dispatcher $dispatcher)
    {
        $baseUri = trim($dispatcher->getRequest()->getRequestUri(), '/');
        $uri = explode('/', $baseUri);
        if (count($uri) >= 3) {
            $module = $uri[0];
            $controller = $uri[1];
            $rewriteController = ucfirst(strtolower($module)) . '_' . ucfirst(strtolower($controller));
            $action = $uri[2];
            $mod = count($uri) == 3 ? "$module/$controller/$action" : "$module/$controller/$action/*";
            $dispatcher->getRouter()->addRoute(
                $controller,
                new Yaf_Route_Rewrite($mod, ['controller' => $rewriteController, 'action' => $action])
            );
        }

    }

    public function _initView(Yaf_Dispatcher $dispatcher)
    {
        $dispatcher->autoRender(false);
        $dispatcher->disableView();
        $dispatcher->returnResponse(true);
    }

    public function _initServBox()
    {
        $servBox = new \App\Library\ServBox();
        Yaf_Registry::set('_serv_box', $servBox);
    }

    public function _initRegBox()
    {
        $regBox = new \App\Library\RegBox();
        Yaf_Registry::set('_reg_box', $regBox);
    }

    public function _initRequest()
    {
        Yaf_Registry::set('_request', new \App\Library\Request(\Yaf_Dispatcher::getInstance()->getRequest()));
    }

    public function _initSession()
    {
        \App\Library\Session::setToken(RegBox()->Request()->getToken());
        Yaf_Registry::set('_session', \App\Library\Session::getInstance());
    }
}