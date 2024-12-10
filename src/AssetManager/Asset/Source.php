<?php

declare(strict_types=1);

namespace Core\Service\AssetManager\Asset;

use InvalidArgumentException;

enum Source
{
    case LOCAL;
    case REMOTE;
    case CDN;

    /**
     * @param Source|string $string
     * @param bool          $throwOnInvalid
     *
     * @return ($throwOnInvalid is true ? static : null|static)
     */
    public static function from( string|Source $string, bool $throwOnInvalid = false ) : ?static
    {
        if ( $string instanceof self ) {
            return $string;
        }

        $source = match ( \trim( \strtolower( $string ), ". \n\r\t\v\0" ) ) {
            'local'  => self::LOCAL,
            'remote' => self::REMOTE,
            'cdn'    => self::CDN,
            default  => null,
        };

        if ( ! $source && $throwOnInvalid ) {
            $enum    = self::class;
            $message = "Could not derive {$enum} from string: '{$string}'.";
            throw new InvalidArgumentException( $message );
        }

        return $source;
    }
}
