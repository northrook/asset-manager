<?php

declare(strict_types=1);

namespace Core\Service\AssetManager\Asset;

use Support\Interface\DataObject;
use Support\Normalize;
use Stringable;
use InvalidArgumentException;
use UnitEnum;

/**
 * Created by the {@see \Core\Service\AssetManager\AssetCompiler}, stored in the {@see \Core\Service\AssetManager\AssetManifest}.
 *
 * Can be used retrieve detailed information about the asset, or recreate it from source.
 *
 * @internal
 *
 * @author Martin Nielsen
 */
final readonly class AssetReference extends DataObject
{
    public string $name;

    /** @var string `relative` */
    public string $publicUrl;

    /** @var string|string[] `relative` */
    public string|array $source;

    public Type $type;

    public ?Properties $properties;

    private function __construct()
    {
    }

    /**
     * @param string                                                  $name
     * @param Type                                                    $type
     * @param string|string[]                                         $source
     * @param string|Stringable                                       $publicUrl
     * @param null|array<string, null|bool|float|int|string|UnitEnum> $properties
     *
     * @return AssetReference
     */
    public static function create(
        string            $name,
        Type              $type,
        string|array      $source,
        string|Stringable $publicUrl,
        ?array            $properties = [],
    ) : AssetReference {
        $reference = new self();
        $reference
            ->type( $type )
            ->name( $name )
            ->publicUrl( $publicUrl )
            ->source( $source )
            ->properties( $properties );

        return $reference;
    }

    private function type( Type $set ) : self
    {
        $this->type = $set;
        return $this;
    }

    private function name( string $set ) : self
    {
        \assert( \ctype_alpha( \str_replace( ['.', '-'], '', $set ) ), $set );
        $normalized = \strtolower( \trim( $set, '.' ) );
        $fragments  = \explode( '.', $normalized );

        $type         = \strtolower( $this->type->name );
        $typeFragment = $fragments[0] ?? throw new InvalidArgumentException();

        if ( ! ( $typeFragment === $type || $typeFragment === "{$type}s" ) ) {
            \array_unshift( $fragments, $type );
        }
        $this->name = \implode( '.', \array_filter( $fragments ) );
        return $this;
    }

    private function publicUrl( string|Stringable $string ) : self
    {
        $this->publicUrl = Normalize::url( (string) $string );
        \assert( '/' === $this->publicUrl[0] );
        return $this;
    }

    /**
     * @param string|string[] $set
     *
     * @return self
     */
    private function source( string|array $set ) : self
    {
        foreach ( (array) $set as $source ) {
            \assert( \is_string( $source ) );
        }

        $this->source = $set;
        return $this;
    }

    /**
     * @param null|array<string, null|bool|float|int|string|UnitEnum> $set
     *
     * @return $this
     */
    private function properties( ?array $set ) : self
    {
        $this->properties = $set ? new Properties( $set ) : null;
        return $this;
    }

    /**
     * @param string                                                  $name
     * @param string                                                  $publicUrl
     * @param string|string[]                                         $source
     * @param string                                                  $type
     * @param null|array<string, null|bool|float|int|string|UnitEnum> $properties
     *
     * @return AssetReference
     */
    public static function hydrate(
        string       $name,
        string       $publicUrl,
        string|array $source,
        string       $type,
        ?array       $properties,
    ) : AssetReference {
        $reference = new self();

        $reference->type       = Type::from( $type, true );
        $reference->name       = $name;
        $reference->publicUrl  = $publicUrl;
        $reference->source     = $source;
        $reference->properties = $properties ? new Properties( $properties ) : null;

        return $reference;
    }

    final public function toArray() : array
    {
        $properties = $this->properties?->getIterator();

        return [
            'name'       => $this->name,
            'publicUrl'  => $this->publicUrl,
            'source'     => $this->source,
            'type'       => $this->type->name,
            'properties' => $properties ? \iterator_to_array( $properties ) : null,
        ];
    }
}
