<?php

namespace Core\Asset;

use Core\AssetManager\Asset;
use Stringable;
use voku\helper\ASCII;

final class Image extends Asset
{
    public const Type TYPE = Type::IMAGE;

    /**
     * @param string ...$directory
     *
     * @return array{type: Type, scan: string[]}
     */
    public static function register(
        string ...$directory,
    ) : array {
        return [
            'type' => self::TYPE,
            'scan' => $directory,
        ];
    }

    public static function key( Stringable|string $from ) : string
    {
        $string = (string) $from;

        $string = \strstr( $string, '.', true ) ?: $string;

        $string = ASCII::to_ascii( $string );

        $string = (string) \preg_replace( '/[^a-z0-9.]+/i', '.', $string );

        $string = \trim( $string, '.' );

        return \strtolower( self::TYPE->name.'.'.$string );
    }
}
