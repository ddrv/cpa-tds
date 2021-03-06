<?php

namespace Cpa\TDS;

use Cpa\TDS\Config\Config;
use Cpa\TDS\Core\Click;
use Cpa\TDS\Core\Handler;
use Cpa\TDS\Core\Request;
use Cpa\TDS\Core\Response;
use Cpa\TDS\Core\Storage;


/**
 * Class TDS
 */
class TDS
{

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Storage
     */
    protected $storage;

    /**
     * @param Config $config
     */
    public function __construct(Config $config = null)
    {
        if (!$config) {
            $config = new Config();
        }
        $this->config = $config;
        $this->storage = new Storage($config->path->links, $config->path->responses, $config->path->tmp);
    }

    /**
     * @param Request $request
     * @return null|string
     */
    public function link(Request $request)
    {
        $key = $request->param(
            $this->config->key->in,
            $this->config->key->position,
            $this->config->key->pattern,
            $this->config->key->match
        );
        return $key;
    }

    /**
     * @param Request $request
     * @param string $linkToken
     * @param array $replace
     * @return Click
     */
    public function click(Request $request, $replace = array())
    {
        $key = $this->link($request);
        $tokens = array();
        $cookies = array();
        $result = false;
        $criteria = array();

        $class = '\Cpa\Tds\Binary\Link\Link'.mb_strtoupper(md5($key));
        $file = $this->config->path->links.DIRECTORY_SEPARATOR.'link-'.$key.'.php';
        if (file_exists($file)) {
            require_once ($file);
        }
        if (class_exists($class)) {
            /**
             * @var Handler $handler
             */
            $handler = new $class($this->config->path->responses);
            $result = $handler->click($request);
        }

        if ($result) {
            $response = $result->response();
            $tokens = $result->tokens();
            $cookies = $result->cookies();
            $criteria = $result->criteria();
        } else {
            $response = new Response(
                'traffback',
                $this->config->trafficBack->status,
                $this->config->trafficBack->headers,
                $this->config->trafficBack->body
            );
        }
        if ($cookies) {
            $response->setCookies($cookies);
        }
        $response->replace($replace, false);
        $response->replace($tokens, true);
        return new Click($request, $response, $key, $criteria, $tokens);
    }

    /**
     * @return Storage
     */
    public function storage()
    {
        return $this->storage;
    }
}