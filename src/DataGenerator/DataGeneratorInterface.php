<?php
namespace EasyRouter\DataGenerator;


interface DataGeneratorInterface
{
    /**
     * 添加一条路由规则到 data generator 中, $routeData 路由数据是由 ParserInterface->parse(...) 返回的数据格式
     * @param string $httpMethod
     * @param array $routeData
     * @param mixed $handler
     */
    public function addRoute($httpMethod, $routeData, $handler);

    /**
     * 返回整理后的路由规则数据
     * @return []
     */
    public function getData();
}