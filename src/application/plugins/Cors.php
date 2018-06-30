<?php

class CorsPlugin extends Yaf_Plugin_Abstract
{
    protected $settings;

    public function __construct($settings = [])
    {
        $this->settings = array_merge(
            array(
                'origin'       => '*',    // Wide Open!
                'allowMethods' => 'GET,HEAD,PUT,POST,DELETE'
            ),
            $settings
        );
    }

    protected function setOrigin(Yaf_Request_Http $req, Yaf_Response_Http $rsp)
    {
        $origin = $this->settings['origin'];
        // handle multiple allowed origins
        if (is_array($origin)) {
            $allowedOrigins = $origin;
            // default to the first allowed origin
            $origin = reset($allowedOrigins);
            // but use a specific origin if there is a match
            foreach ($allowedOrigins as $allowedOrigin) {
                if ($allowedOrigin === $req->getServer("Origin")) {
                    $origin = $allowedOrigin;
                    break;
                }
            }
        }
        $rsp->setHeader('Access-Control-Allow-Origin', $origin);
        $rsp->setHeader('Access-Control-Allow-Credentials', 'true');
    }

    protected function setExposeHeaders(Yaf_Request_Http $req, Yaf_Response_Http $rsp)
    {
        if (isset($this->settings['exposeHeaders'])) {
            $exposeHeaders = $this->settings['exposeHeaders'];
            if (is_array($exposeHeaders)) {
                $exposeHeaders = implode(", ", $exposeHeaders);
            }
            $rsp->setHeader('Access-Control-Expose-Headers', $exposeHeaders);
        }
    }

    protected function setMaxAge(Yaf_Request_Http $req, Yaf_Response_Http $rsp)
    {
        if (isset($this->settings['maxAge'])) {
            $rsp->setHeader('Access-Control-Max-Age', $this->settings['maxAge']);
        }
    }

    protected function setAllowCredentials(Yaf_Request_Http $req, Yaf_Response_Http $rsp)
    {
        if (isset($this->settings['allowCredentials']) && $this->settings['allowCredentials'] === true) {
            $rsp->setHeader('Access-Control-Allow-Credentials', 'true');
        }
    }

    protected function setAllowMethods(Yaf_Request_Http $req, Yaf_Response_Http $rsp)
    {
        if (isset($this->settings['allowMethods'])) {
            $allowMethods = $this->settings['allowMethods'];
            if (is_array($allowMethods)) {
                $allowMethods = implode(", ", $allowMethods);
            }

            $rsp->setHeader('Access-Control-Allow-Methods', $allowMethods);
        }
    }

    protected function setAllowHeaders(Yaf_Request_Http $req, Yaf_Response_Http $rsp)
    {
        if (isset($this->settings['allowHeaders'])) {
            $allowHeaders = $this->settings['allowHeaders'];
            if (is_array($allowHeaders)) {
                $allowHeaders = implode(", ", $allowHeaders);
            }
        } else {  // Otherwise, use request headers
            $allowHeaders = $req->getServer("Access-Control-Request-Headers");
        }
        if (isset($allowHeaders)) {
            $rsp->setHeader('Access-Control-Allow-Headers', $allowHeaders);
        }
    }


    public function preResponse(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
    }


    public function routerStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
        Yaf_Registry::set('_response', new \App\Library\Response($response));
    }

    public function routerShutdown(Yaf_Request_Abstract $req, Yaf_Response_Abstract $rsp)
    {
        if ($req->isOptions()) {
            $this->setOrigin($req, $rsp);
            $this->setMaxAge($req, $rsp);
            $this->setAllowCredentials($req, $rsp);
            $this->setAllowMethods($req, $rsp);
            $this->setAllowHeaders($req, $rsp);
            $rsp->response();
            exit();
        } else {
            $this->setOrigin($req, $rsp);

            $this->setExposeHeaders($req, $rsp);
            $this->setAllowCredentials($req, $rsp);


        }
    }

    public function dispatchLoopStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
    }

    public function preDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
    }

    public function postDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {

    }

    public function dispatchLoopShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {

    }
}