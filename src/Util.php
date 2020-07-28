<?php
declare(strict_types = 1);

namespace RB\Transport;

class Util
{
    /**
     * @param string $name
     * @return string
     */
    public static function caseCamelToSnake(string $name): string
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $name, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }

    /**
     * @param string $name
     * @return string
     */
    public static function caseSnakeToCamel(string $name): string
    {
        $ret = explode('_', $name);
        foreach ($ret as &$str) {
            $str = ucfirst($str);
        }
        return implode('', $ret);
    }
}