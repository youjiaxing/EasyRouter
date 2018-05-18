<?php
namespace EasyRouter\Parser;

/**
 * 在 FastRouteParser 基础上新增占位符类型的语法糖
 * Class EasyRouteParser
 * @package EasyRouter\RouteParser
 */
class EasyRouteParser extends FastRouteParser
{
    /**
     * 占位符类型语法糖
     * @var array
     */
    protected $placeHolderSugar = [
        'i'=>'\d+',             // 数字
        'h'=>'[0-9a-fA-F]+',    // 十六进制(颜色表示等)
        'o'=>'[0-7]',           // 八进制
        'a'=>'[a-zA-Z]+',                   // 纯字母单词
        'c'=>'[a-zA-Z_][a-zA-Z0-9_-]*',     // 字母+数字+'_'+'-', 不可以数字或'-'开头
        '*'=>self::DEFAULT_DISPATCH_REGEX,  // 任意匹配
        ''=>self::DEFAULT_DISPATCH_REGEX,   // 任意匹配
    ];

    /**
     * PHP 5 allows developers to declare constructor methods for classes.
     * Classes which have a constructor method call this method on each newly-created object,
     * so it is suitable for any initialization that the object may need before it is used.
     *
     * Note: Parent constructors are not called implicitly if the child class defines a constructor.
     * In order to run a parent constructor, a call to parent::__construct() within the child constructor is required.
     *
     * param [ mixed $args [, $... ]]
     * @return void
     * @link http://php.net/manual/en/language.oop5.decon.php
     */
    public function __construct($sugars = [])
    {
        $this->addPlaceHolderSugar($sugars);
    }

    /**
     * @param array $placeHolderSugar
     */
    public function setPlaceHolderSugar($sugars = [])
    {
        $this->placeHolderSugar = $sugars;
    }

    public function addPlaceHolderSugar($sugars)
    {
        $this->placeHolderSugar = array_merge($this->placeHolderSugar, $sugars);
    }

    /**
     * 解析不包含可选项的单条路由字符串
     *
     * /fixedRoutePart/{varName}
     * 解析结果如下
     * [
     *      "/fixedRoutePart/",
     *      ["varName", "[^/]+"]
     * ],
     *
     * @param string $route
     * @return mixed[]
     */
    public function parsePlaceholders($route)
    {
        if (!preg_match_all('~'.self::VARIABLE_REGEX.'~x', $route, $matches, PREG_SET_ORDER|PREG_OFFSET_CAPTURE)) {
            return [$route];
        }
        /*
        $route = "/user/{id:\d+}/{name:\w}/hello"

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

        $routeData = [];
        $offset = 0;
        foreach ($matches as $match) {
            if ($match[0][1] > $offset) {
                $routeData[] = substr($route, $offset, $match[0][1]-$offset);
            }

            $varName = $match[1][0];
            if (isset($match[2])) {
                $varRegex = trim($match[2][0]);
                $varRegex = isset($this->placeHolderSugar[$varRegex]) ? trim($this->placeHolderSugar[$varRegex]) : $varRegex;
            } else {
                $varRegex = self::DEFAULT_DISPATCH_REGEX;
            }

            $routeData[] = [$varName, $varRegex];
            $offset = $match[0][1] + strlen($match[0][0]);
        }

        if ($offset !== strlen($route)) {
            $routeData[] = substr($route, $offset);
        }

        return $routeData;
    }

}