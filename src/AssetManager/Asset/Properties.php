<?php

namespace Core\Service\AssetManager\Asset;

use Support\Interface\DataObject;
use IteratorAggregate;
use UnitEnum;
use Traversable;
use ArrayIterator;
use InvalidArgumentException;

/**
 * @implements IteratorAggregate<string, null|string|int|float|bool|\UnitEnum>
 *
 * @internal
 * @author Martin Nielsen <mn@northrook.com>
 */
final readonly class Properties extends DataObject implements IteratorAggregate
{
    /** @var array<string, null|bool|float|int|string|UnitEnum> */
    private array $properties;

    /**
     * @param array<string, null|bool|float|int|string|UnitEnum> $properties
     */
    public function __construct( array $properties )
    {
        foreach ( $properties as $key => $property ) {
            \assert(
                ( \is_string( $key ) && \ctype_alnum( \str_replace( '.', '', $key ) ) ),
                'Property keys must be alphanumeric strings.',
            );
            \assert(
                (
                    \is_string( $property )
                        || \is_null( $property )
                        || \is_int( $property )
                        || \is_float( $property )
                        || \is_bool( $property )
                        || $property instanceof UnitEnum
                ),
            );
        }
        $this->properties = $properties;
    }

    public function has( string $property ) : bool
    {
        return \array_key_exists( $property, $this->properties );
    }

    public function get( string $property ) : null|bool|float|int|string|UnitEnum
    {
        if ( $this->has( $property ) ) {
            $message = "Property '{$property}' does not exist.";
            throw new InvalidArgumentException( $message );
        }
        return $this->properties[$property];
    }

    /**
     * @return ArrayIterator<string, null|bool|float|int|string|UnitEnum>
     */
    public function getIterator() : Traversable
    {
        return new ArrayIterator( $this->properties );
    }
}
