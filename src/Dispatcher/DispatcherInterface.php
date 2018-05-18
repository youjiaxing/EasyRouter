<?php
namespace EasyRouter\Dispatcher;

Interface DispatcherInterface
{
    const NOT_FOUND = 0;
    const FOUND = 1;
    const METHOD_NOT_ALLOWED = 2;

    /**
     * 根据传入的 HTTP method 和 uri, 分配合适的路由
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
    public function dispatch($routeDatas, $httpMethod, $uri, $needAllowMethod=false);
}