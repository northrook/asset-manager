<?php

declare(strict_types=1);

namespace Core\Asset\Meta;

use Core\Asset\Type;
use Core\AssetManager\Interface\AssetMetaInterface;
use JsonException;
use Stringable;
use UnitEnum;
use stdClass;

// : Provided by AssetEntity

/**
 * @internal
 */
final class AssetMeta extends stdClass implements AssetMetaInterface
{
    /**
     * @param null|string|UnitEnum $meta `json-string` or {@see AssetMeta::$meta}
     *
     * @throws JsonException
     */
    public function __construct( null|string|array $meta = null )
    {
        if ( ! $meta ) {
            return;
        }

        if ( \is_string( $meta ) ) {
            $meta = \json_decode(
                $meta,
                true,
                512,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE,
            );
        }

        /** @var string|UnitEnum $meta */
        $this->set( $meta );
    }

    public function has( string $key ) : bool
    {
        return \property_exists( $this, $key );
    }

    /**
     * @param string                                                                  $add
     * @param null|array<array-key, scalar>|bool|float|int|string|Stringable|UnitEnum $value
     *
     * @return $this
     */
    public function add(
        string                                               $add,
        null|bool|string|int|float|UnitEnum|Stringable|array $value = null,
    ) : AssetMeta {
        $this->{$add} ??= $this->value( $value );

        \assert(
            $this->validate(),
            'AssetReference meta keys must be strings.',
        );
        return $this;
    }

    /**
     * @param string|UnitEnum                                                         $set
     * @param null|array<array-key, scalar>|bool|float|int|string|Stringable|UnitEnum $value
     *
     * @return $this
     */
    public function set( array|string $set, mixed $value = null ) : AssetMeta
    {
        if ( \is_array( $set ) ) {
            foreach ( $set as $key => $value ) {
                $this->set( $key, $value );
            }
        }
        else {
            $this->{$set} = $this->value( $value );
        }

        \assert(
            $this->validate(),
            'AssetReference meta keys must be strings.',
        );

        return $this;
    }

    /**
     * @param ?string $key
     *
     * @return ($key is null ? array<string, null|array<array-key, scalar>|bool|float|int|string> : null|array<array-key, scalar>|bool|float|int|string )
     */
    public function get( ?string $key = null ) : mixed
    {
        if ( $key === null ) {
            return (array) $this;
        }

        return $this->{$key} ?? null;
    }

    /**
     * @throws JsonException
     */
    public function export() : string
    {
        return \json_encode( (array) $this, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE );
    }

    /**
     * @param null|array<array-key, scalar>|bool|float|int|string|Stringable|UnitEnum $value
     *
     * @return null|array<array-key, scalar>|bool|float|int|string
     */
    public static function value(
        null|array|bool|float|int|string|Stringable|UnitEnum $value,
    ) : null|array|bool|float|int|string {
        return match ( true ) {
            $value instanceof Stringable => $value->__toString(),
            $value instanceof Type       => $value->name(),
            $value instanceof UnitEnum   => $value->name,
            default                      => $value,
        };
    }

    private function validate() : bool
    {
        foreach ( $this as $key => $value ) {
            dump( [$key, $value] );
            if ( ! \is_string( $key ) ) {
                return false;
            }
            \assert(
                \ctype_alnum( \str_replace( ['.', '-'], '', $key ) ),
                "AssetReference meta keys must only contain ASCII letters, numbers, periods, and hyphens. '{$key}' provided.",
            );
        }
        return true;
    }
}
