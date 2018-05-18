<?php
namespace EasyRouter\Parser;


interface ParserInterface
{
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
    public function parse($route);
}