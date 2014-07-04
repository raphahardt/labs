<?php

namespace Broda\Util;

/**
 * Classe FileUtils
 *
 * @author Raphael Hardt <raphael.hardt@gmail.com>
 */
class Masker
{
    protected static $wildcards = array(
        'number' => '9',
        'alpha' => 'A',
        'any' => '#'
    );

    protected static $escape_char = '$';

    private function __construct() {}

    public static function setNumberWildcard($wildcard = '9')
    {
        static::$wildcards['number'] = $wildcard;
    }

    public static function setAlphaWildcard($wildcard = 'A')
    {
        static::$wildcards['alpha'] = $wildcard;
    }

    public static function setCharWildcard($wildcard = '#')
    {
        static::$wildcards['any'] = $wildcard;
    }

    public static function setEscapeChar($char = '$')
    {
        static::$escape_char = $char;
    }

    public static function mask($input, $mask)
    {
        $mask_chars = $mask;
        $input_chars = $input;
        $output = '';

        $m_effective = 0;
        for($m=0,$i=0; $m < strlen($mask_chars); $m++) {
            $mask_char = $mask_chars[$m];
            $input_char = $input_chars[$i];
            switch (true) {
                case $mask_char === static::$wildcards['number']:
                    $test_n = $mask_char === static::$wildcards['number'];
                case $mask_char === static::$wildcards['alpha']:
                    $test_a = $mask_char === static::$wildcards['alpha'];
                case $mask_char === static::$wildcards['any']:
                    if ($test_n && !is_numeric($input_char)) throw static::throwParseException($mask, $m, $input_char, 'numeric');
                    if ($test_a && is_numeric($input_char)) throw static::throwParseException($mask, $m, $input_char, 'numeric');
                    if (!static::isEmpty($input_char)) {
                        $output .= $input_char;
                        ++$i;
                    }
                    ++$m_effective;
                    break;
                case $mask_char === static::$escape_char:
                    $output .= $mask_chars[++$m];
                    break;
                default:
                    $output .= $mask_char;
            }
        }
        if ($m_effective > strlen($input_chars)) {
            $output = '';
        }

        return $output;
    }

    public static function unmask($maskedInput, $mask)
    {
        $mask_chars = $mask;
        $input_chars = $maskedInput;

        if (strlen($input_chars) !== strlen(str_replace(static::$escape_char, '', $mask_chars))) {
            return '';
        }

        $output = '';

        for($m=0,$i=0; $m < strlen($mask_chars); $m++, $i++) {
            $mask_char = $mask_chars[$m];
            $input_char = $input_chars[$i];
            switch (true) {
                case $mask_char === static::$wildcards['number']:
                    $test_n = $mask_char === static::$wildcards['number'];
                case $mask_char === static::$wildcards['alpha']:
                    $test_a = $mask_char === static::$wildcards['alpha'];
                case $mask_char === static::$wildcards['any']:
                    if ($test_n && !is_numeric($input_char)) throw static::throwParseException($mask, $m, $input_char, 'numeric');
                    if ($test_a && is_numeric($input_char)) throw static::throwParseException($mask, $m, $input_char, 'numeric');
                    $output .= $input_char;
                    break;
                case $mask_char === static::$escape_char:
                    ++$m;
                    break;
                default:
                    if ($input_char !== $mask_char) {
                        return '';
                    }
            }
        }

        return $output;
    }

    private static function isEmpty($char)
    {
        return empty($char) && $char != '0';
    }

    private static function throwParseException($mask, $col, $atual, $expected)
    {
        return new \LogicException(sprintf(
                'Error in mask "%s": got "%s", expected %s in col %s',
                $mask, $atual, $expected, $col
                ));
    }

}

/*

// unit tests

$input = $f = '1930250000';
$mask = '(99) 9999-9999';

var_dump($input, 'start');

for($i=0;$i<40;$i++) {
    if ($i % 2 === 0) {
        $input = Masker::mask($input, $mask);
    } else {
        $input = Masker::unmask($input, $mask);
    }
    var_dump($input);
}

var_dump($input === $f);*/