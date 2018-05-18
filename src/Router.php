<?php
namespace EasyRouter;

use EasyRouter\DataGenerator\DataGeneratorInterface;
use EasyRouter\Dispatcher\DispatcherInterface;
use EasyRouter\Exception\InternalException;
use EasyRouter\Parser\ParserInterface;

class Router
{
    protected $currentGroupPrefix = '';

    public $cacheEnable = false;

    public $cacheExpire = 30;

    protected $cacheFile;

    /* @var ParserInterface */
    protected $parser;

    /* @var DispatcherInterface */
    protected $dispatcher;

    /* @var DataGeneratorInterface */
    protected $dataGenerator;

    protected $routeDatas;

    protected $errors = [];

    /**
     * 路由规则添加过程
     * 1. 通过 addRoute 传入的路由规则经过 $parser->parse($route) 解析后(分离可选路由项, 分离占位符)
     * 2. 将初步解析后的路由, 保存到 $dataGenerator->addRoute($httpMethod, $routeData, $handler) 中
     *      按照静态路由, 动态路由分别存储, 并根据不同 $dataGenerator 的策略, 可对动态路由进行 正则规则合并|分块 等
     * 3. 从 $dataGenerator->getData() 获取整理后的路由数据
     *
     * 路由规则匹配过程
     * 1. 通过 dispatch($httpMethod, $uri), 间接使用 $dispatcher->dispatch(...), 返回匹配结果
     */
//    public function __construct(ParserInterface $parser, DataGeneratorInterface $dataGenerator, DispatcherInterface $dispatcher)
    public function __construct(ParserInterface $parser,  $dataGenerator,  $dispatcher)
    {
        $this->parser = $parser;
        $this->dataGenerator = $dataGenerator;
        $this->dispatcher = $dispatcher;
    }

    public function LoadCache($cacheFile) {
        $this->cacheFile = $cacheFile;
        $this->cacheEnable = true;
        if (!file_exists($cacheFile)) {
            return false;
        }

        if (time() - filemtime($cacheFile) > $this->cacheExpire) {
            return false;
        }

        $routeDatas = include $cacheFile;
        if (!is_array($routeDatas)) {
            return false;
        }

        $this->routeDatas = $routeDatas;
        return true;
    }

    public function addGroup($prefix, callable $callback)
    {
        $previousGroupPrefix = $this->currentGroupPrefix;
        $this->currentGroupPrefix .= $prefix;
        $callback($this);
        $this->currentGroupPrefix = $previousGroupPrefix;
    }


    public function addRoute($httpMethod, $route, $handler)
    {
        if (!is_null($this->routeDatas)) {
            throw new InternalException("already has routeDatas, addRoute is not allowed now!");
        }

        if ($httpMethod === '*') {
            $httpMethod = ['GET', 'POST', 'HEAD', 'PUT', 'DELETE', 'PATCH'];
        }

        $route = $this->currentGroupPrefix.$route;
        $routeDatas = $this->parser->parse($route);
//        dump($route);
//        Log::info(sprintf("%s %s", $httpMethod, $route), $routeDatas);
//        dump($routeDatas);
        foreach ((array)$httpMethod as $method) {
            $method = strtoupper($method);
            foreach ($routeDatas as $routeData) {
                $this->dataGenerator->addRoute($method, $routeData, $handler);
            }
        }
    }

    public function get($route, $handler)
    {
        $this->addRoute('GET', $route, $handler);
    }

    public function post($route, $handler)
    {
        $this->addRoute('POST', $route, $handler);
    }

    public function put($route, $handler)
    {
        $this->addRoute('PUT', $route, $handler);
    }

    public function delete($route, $handler)
    {
        $this->addRoute('DELETE', $route, $handler);
    }

    public function head($route, $handler)
    {
        $this->addRoute('HEAD', $route, $handler);
    }

    public function patch($route, $handler)
    {
        $this->addRoute('PATCH', $route, $handler);
    }

    public function error($handler, $httpMethod='*')
    {
        if (!is_null($this->routeDatas)) {
            throw new InternalException("already has routeDatas, addRoute is not allowed now!");
        }

        if ($httpMethod === '*') {
            $httpMethod = ['GET', 'POST', 'HEAD', 'PUT', 'DELETE'];
        }

        foreach ((array)$httpMethod as $method) {
            $this->errors[$method] = $handler;
        }
    }

    public function dispatch($httpMethod, $uri, $needAllowMethod=false)
    {
        $httpMethod = strtoupper($httpMethod);
        return $this->dispatcher->dispatch($this->getRouteData(), $httpMethod, $uri, $needAllowMethod);
    }

    public function getRouteData()
    {
        if (is_null($this->routeDatas)) {
            $this->routeDatas = $this->dataGenerator->getData();
            $this->routeDatas[2] = $this->errors;

            if ($this->isCacheEnable() && !empty($this->cacheFile)) {
                file_put_contents($this->cacheFile, '<?php return '.var_export($this->routeDatas, true).';');
            }
        }
        return $this->routeDatas;
    }

    /**
     * @return bool
     */
    public function isCacheEnable()
    {
        return $this->cacheEnable;
    }

    /**
     * @param bool $cacheEnable
     */
    public function setCacheEnable($cacheEnable)
    {
        $this->cacheEnable = $cacheEnable;
    }
}