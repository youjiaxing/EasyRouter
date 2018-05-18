<?php
namespace EasyRouter\Dispatcher;

class EasyDispatcher implements DispatcherInterface
{

    /**
     * 根据传入的 HTTP method 和 uri, 分配合适的路由
     *
     * $routeDatas 格式如下
     *  array:2 [▼
     *     0 => array:1 [▼
     *       "GET" => array:1 [▼
     *         "/user/list/all" => "handler"
     *       ]
     *     ]
     *     1 => array:1 [▼
     *       "GET" => array:3 [▼
     *         "/author/(?P<name>[a-zA-Z]+)/(?P<nickname>[a-zA-Z_][a-zA-Z0-9_-]*)" => ""
     *         "/user/(?P<id>\d+)" => "handler"
     *         "/user/(?P<id>\d+)/(?P<name>\w)/hello(?P<name2>[^/]+)" => "handler"
     *       ]
     *     ]
     *   ]
     *
     * 返回数组格式如下:
     *
     *      [self::NOT_FOUND, $errorHandler]
     *      [self::METHOD_NOT_ALLOWED, ['GET', '其他允许的METHOD'], $errorHandler]
     *      [self::FOUND, $handler, ['varName'=>'value', ...]]
     *
     * @param array $routeDatas
     * @param string $httpMethod
     * @param string $uri
     * @return array
     */
    public function dispatch($routeDatas, $httpMethod, $uri, $needAllowMethod=false)
    {
//        Log::info(sprintf("%s %s %s", $httpMethod, $uri, var_export($routeDatas,true)));
        list($staticRouteData, $dynamicRouteData, $errorRouteData) = $routeDatas;

        // 先匹配静态路由
        if (isset($staticRouteData[$httpMethod][$uri])) {
            return [self::FOUND, $staticRouteData[$httpMethod][$uri], []];
        }

        // 逐个匹配动态路由(其实效率很差)
        if (isset($dynamicRouteData[$httpMethod])) {
            if (($response = $this->dispatchDynamicRoute($dynamicRouteData[$httpMethod], $uri)) !== false) {
                return $response;
            }
        }

        // HEAD 请求, 尝试定向到 GET
        if ($httpMethod === 'HEAD') {
            if (isset($staticRouteData['GET'][$uri])) {
                return [self::FOUND, $staticRouteData['GET'][$uri], []];
            }

            if (isset($dynamicRouteData['GET'])) {
                if (($response = $this->dispatchDynamicRoute($dynamicRouteData['GET'], $uri)) !== false) {
                    return $response;
                }
            }
        }

        $errorHandler = null;
        if (isset($errorRouteData[$httpMethod])) {
            $errorHandler = $errorRouteData[$httpMethod];
        }

        // 查找 $uri 匹配允许的方法
        if ($needAllowMethod) {
            $allowedMethod = [];

            foreach ($staticRouteData as $method=>$uriMap) {
                if (isset($uriMap[$uri])) {
                    $allowedMethod[$method] = $method;
                }
            }

            foreach ($dynamicRouteData as $method=>$uriMap) {
                if ($method === $httpMethod) {
                    continue;
                }

                if (false !== ($response = $this->dispatchDynamicRoute($uriMap, $uri))) {
                    $allowedMethod[$method] = $method;
                }
            }

            if (!empty($allowedMethod)) {
                return [self::METHOD_NOT_ALLOWED, array_values($allowedMethod), $errorHandler];
            }
        }





        return [self::NOT_FOUND, $errorHandler];
    }

    /**
     * @param $routeData
     * @param $uri
     * @return array|false
     */
    protected function dispatchDynamicRoute($routeData, $uri)
    {
        foreach ($routeData as $regex=>$handler) {
            if (!preg_match($regex, $uri, $matches)) {
                continue;
            }

            foreach (range(0,20) as $index) {
                unset($matches[$index]);
            }

            return [self::FOUND, $handler, $matches];
        }
        return false;
    }
}