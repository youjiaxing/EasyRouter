<?php
namespace EasyRouter\Parser;

use EasyRouter\Exception\BadRouteException;

/**
 * 直接采用 FastRoute 的默认解析类 Std
 * Class FastRouteParser
 * @package EasyRouter\RouteParser
 */
class FastRouteParser implements ParserInterface
{
    /**
     * 匹配路由规则中的占位符部分, eg "{id : \d+}" 或 "{id}"
     */
    const VARIABLE_REGEX = <<<'REGEX'
\{
    \s* ([a-zA-Z_][a-zA-Z0-9_-]*) \s*
    (?:
        : \s* ([^{}]*(?:\{(?-1)\}[^{}]*)*)
    )?
\}
REGEX;

    const DEFAULT_DISPATCH_REGEX = '[^/]+';

    /**
     * 将一条string的路由解析成 多个路由数据(数组形式)
     *
     * 样例解析 "/fixedRoutePart/{varName}[/moreFixed/{varName2:\d+}]"
     * 其中 {varName}作为占位符, [...]解析为可选的路由部分, 相应的返回为:
     *
     * [
     *      // 第一个路由: 无可选项
     *      [
     *          "/fixedRoutePart/",
     *          ["varName", "[^/]+"]
     *      ],
     *      // 第二个路由: 有可选项
     *      [
     *          "/fixedRoutePart/",
     *          ["varName", "[^/]+"],
     *          "/moreFixed/",
     *          ["varName2", "[0-9]+"],
     *      ],
     * ]
     *
     * @param string $route 待解析的路由字符串
     * @return mixed[][] 路由数据数组 的 数组
     */
    public function parse($route)
    {
        # 1. 解析可选项
        $routeWithoutClosingOptionals = rtrim($route, ']');
        $optionCount = strlen($route) - strlen($routeWithoutClosingOptionals);

        # 2. 分割可选项 '[' ,注意略过占位符中的正则规则 [
        #   关于 (*SKIP)(*F) 的理解可参考 https://stackoverflow.com/questions/24534782/how-do-skip-or-f-work-on-regex
        #   模式修饰符 x , 等同于 perl 中的 /x 修饰符, 可以使编译模式中包含注释(self::VARIABLE_REGEX 中未转义的空格换行都会被忽略), 可以让正则书写更美观
        $segments = preg_split('~' . self::VARIABLE_REGEX . '(*SKIP)(*F) | \[~x', $routeWithoutClosingOptionals);
        if ($optionCount !== count($segments)-1) {
            // If there are any ] in the middle of the route, throw a more specific error message
            if (preg_match('~' . self::VARIABLE_REGEX . '(*SKIP)(*F) | \]~x', $routeWithoutClosingOptionals)) {
                throw new BadRouteException("Optional segments can only occur at the end of a route", $route);
            }
            throw new BadRouteException("Number of opening '[' and closing ']' does not match", $route);
        }

        $currentRoute = '';
        $routeDatas = [];
        foreach ($segments as $n=>$segment) {
            if ($segment === '' && $n !== 0) {
                throw new BadRouteException("Empty optional part", $route);
            }

            $currentRoute .= $segment;
            $routeDatas[] = $this->parsePlaceholders($currentRoute);
        }


        return $routeDatas;
    }

    /**
     * 解析不包含可选项的单条路由字符串
     * @param string $route
     * @return mixed[]
     */
    public function parsePlaceholders($route)
    {
        $flag = PREG_OFFSET_CAPTURE | PREG_SET_ORDER;
        if (!preg_match_all("~".self::VARIABLE_REGEX."~x", $route, $matches, $flag)) {
//            Log::debug("~~parsePlaceholders, '$route' 不包含占位符", [$route]);
            return [$route];
        }

        /*
        $route = "/user/{id:\d+}/{name:\w}"

        $matches = array:2 [▼
          0 => array:3 [▼
            0 => array:2 [▼
              0 => "{id:\d+}"
              1 => 6
            ]
            1 => array:2 [▼
              0 => "id"
              1 => 7
            ]
            2 => array:2 [▼
              0 => "\d+"
              1 => 10
            ]
          ]
          1 => array:3 [▼
            0 => array:2 [▼
              0 => "{name:\w}"
              1 => 15
            ]
            1 => array:2 [▼
              0 => "name"
              1 => 16
            ]
            2 => array:2 [▼
              0 => "\w"
              1 => 21
            ]
          ]
        ]
        */

        $offset = 0;
        $routeData = [];
        foreach ($matches as $set) {
            if ($set[0][1] > $offset) {
                $routeData[] = substr($route, $offset, $set[0][1] - $offset);
            }
            $routeData[] = [
                $set[1][0],
                isset($set[2]) ? trim($set[2][0]) : self::DEFAULT_DISPATCH_REGEX
            ];
            $offset = $set[0][1] + strlen($set[0][0]);
        }

        if ($offset !== strlen($route)) {
            $routeData[] = substr($route, $offset);
        }

        return $routeData;
    }
}