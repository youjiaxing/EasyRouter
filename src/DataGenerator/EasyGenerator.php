<?php
namespace EasyRouter\DataGenerator;


use EasyRouter\Exception\BadRouteException;

class EasyGenerator implements DataGeneratorInterface
{
    protected $staticRoutes = [];

    protected $dynamicRoutes = [];

    /**
     * 添加一条路由规则到 data generator 中, $routeData 路由数据是由 ParserInterface->parse(...) 返回的数据格式
     *
     * $routeData 格式类似:
     *      [
     *          "/fixedRoutePart/",
     *          ["varName", "[^/]+"],
     *          "/welcome"
     *      ],
     *
     * @param string $httpMethod
     * @param array $routeData
     * @param mixed $handler
     */
    public function addRoute($httpMethod, $routeData, $handler)
    {
        if ($this->isStaticRoute($routeData)) {
            $this->addStaticRoute($httpMethod, $routeData[0], $handler);
        } else {
            $this->addDynamicRoute($httpMethod, $routeData, $handler);
        }
    }

    protected function isStaticRoute($routeData)
    {
        return count($routeData) === 1 && is_string($routeData[0]);
    }

    protected function addStaticRoute($httpMethod, $routeStr, $handler)
    {
        // 判断路由重复定义
//        if (isset($this->staticRoutes[$httpMethod][$routeStr])) {
//            throw new BadRouteException(sprintf('Cannot register two routes matching "%s" for method "%s"',
//                $routeStr, $httpMethod));
//        }

        //TODO 判断是否会被动态路由覆盖

        $this->staticRoutes[$httpMethod][$routeStr] = $handler;
    }

    /**
     * $routeData 格式类似:
     *      [
     *          "/fixedRoutePart/",
     *          ["varName", "[^/]+"],
     *          "/welcome"
     *      ],
     *
     * @param $httpMethod
     * @param $routeData
     * @param $handler
     */
    protected function addDynamicRoute($httpMethod, $routeData, $handler)
    {
        $regex = "";
        $variables = [];
        foreach ($routeData as $part) {
            if (is_string($part)) {
                $regex .= preg_quote($part, '~');
                continue;
            }

            list($varName, $regexPart) = $part;
            if (isset($variables[$varName])) {
                throw new BadRouteException(sprintf(
                    'Cannot use the same placeholder "%s" twice', $varName
                ), json_encode($routeData, JSON_UNESCAPED_SLASHES));
            }

//            if ($this->regexHasCapturingGroups($regexPart)) {
//                throw new BadRouteException(sprintf(
//                    'Regex "%s" for parameter "%s" contains a capturing group',
//                    $regexPart, $varName
//                ));
//            }

            $variables[$varName] = $varName;
            $regex .= "(?P<$varName>".$regexPart.")";
        }
        $regex = '~^'.$regex.'$~';

        $this->dynamicRoutes[$httpMethod][$regex] = $handler;
    }

    /**
     * 返回整理后的路由规则数据
     *
     * list($staticData, $dynamicData) = $this->getData()
     *
     * @return []
     */
    public function getData()
    {
        return [$this->staticRoutes, $this->dynamicRoutes];
    }
}