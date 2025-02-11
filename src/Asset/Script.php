<?php

namespace Core\Asset;

use Core\AssetManager\Asset;

final class Script extends Asset
{
    public const Type TYPE = Type::SCRIPT;

    /**
     * @param string $name
     * @param string $source
     *
     * @return array{name: string, type: Type, source: string}
     */
    public static function register(
        string $name,
        string $source,
    ) : array {
        return [
            'name'   => self::name( $name ),
            'type'   => self::TYPE,
            'source' => $source,
        ];
    }
}
