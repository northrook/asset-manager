<?php

namespace Core\Asset;

use Core\AssetManager\Asset;

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
}
