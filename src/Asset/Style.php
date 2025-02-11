<?php

namespace Core\Asset;

use Core\AssetManager\Asset;

final class Style extends Asset
{
    public const Type TYPE = Type::STYLE;

    /**
     * @param string          $name
     * @param string|string[] $source
     *
     * @return array{name: string, type: Type, source: string[]}
     */
    public static function register(
        string       $name,
        string|array $source,
    ) : array {
        return [
            'name'   => self::name( $name ),
            'type'   => self::TYPE,
            'source' => (array) $source,
        ];
    }
}
