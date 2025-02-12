<?php

namespace Core\AssetManager;

use Core\Asset\Type;
use Core\AssetManager\Interface\AssetInterface;
use Core\Interface\PathfinderInterface;
use Core\View\Element;
use Core\View\Element\Attributes;
use Stringable;
use voku\helper\ASCII;

/**
 * @method static register( string $name, string|string[] $source )
 */
abstract class Asset implements AssetInterface
{
    public const Type TYPE = Type::ABSTRACT;

    /** @var string `16` character alphanumeric */
    protected readonly string $assetID;

    protected ?Element $element = null;

    public function __construct(
        protected readonly AssetReference      $reference,
        protected readonly PathfinderInterface $pathfinder,
        protected readonly string              $publicRootKey,
        protected readonly string              $storageDirectory,
    ) {}

    abstract protected function construct( bool $rebuild ) : void;

    final public function build( ?string $assetID = null, bool $rebuild = false ) : self
    {
        $this->construct( $rebuild );
        $this->setAssetID( $assetID );
        return $this;
    }

    /**
     * @param string[] $source
     *
     * @return self
     */
    abstract public function addSource( array $source ) : self;

    public function attributes() : Attributes
    {
        return $this->element()->attributes;
    }

    public function __toString() : string
    {
        return $this->element()->getHtml();
    }

    public function getHtml() : Stringable
    {
        return $this->element()->getHtml();
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

    /**
     * @param null|string $assetID
     *
     * @return string `16` character alphanumeric
     */
    final protected function setAssetID( ?string $assetID ) : string
    {
        $this->assetID ??= $assetID ?? \hash(
            algo : 'xxh3',
            data : \implode(
                ':',
                [
                    $this::class,
                    $this->reference->name,
                    $this->reference->type->name,
                    ...$this->reference->source,
                ],
            ),
        );

        \assert(
            \strlen( $this->assetID ) === 16 && \ctype_alnum( $this->assetID ),
            'Asset ID must be 16 alphanumeric characters; ['.\strlen(
                $this->assetID,
            )."] `{$this->assetID}` given",
        );

        return $this->assetID;
    }

    final protected function fileName( ?string $ext = null ) : string
    {
        $reference = explode( '.', $this->reference->name, 2);
        $name      = end( $reference );
        $fileName  = \str_replace( '.', '-', $name );

        if ( $ext ) {
            $fileName .= '.'.\trim( $ext, '.' );
        }

        return $fileName;
    }

    /**
     * Asset names must:
     * - be `lower-case.dot.notated`
     * - start with the type: `type.asset.name`
     *
     * @param string $name
     *
     * @return non-empty-lowercase-string
     */
    // final public static function name( string $name ) : string
    // {
    //     \assert(
    //             \ctype_alpha( \str_replace( ['.', '-'], '', $name ) ),
    //             "Asset names must only contain ASCII characters, underscores and dashes. '{$name}' provided.",
    //     );
    //
    //     // Ensure extending class sets the required Type constant
    //     \assert(
    //             static::TYPE !== Type::ABSTRACT,
    //             'Required public class constant '.static::class.'::TYPE not set.',
    //     );
    //
    //     $type = \strtolower( static::TYPE->name );
    //     $name = \strtolower( \trim( $name, '.' ) );
    //
    //     $fragments = \array_filter( \explode( '.', $name ) );
    //
    //     if ( $fragments[0] !== $type ) {
    //         $fragments = [$type, ...$fragments];
    //     }
    //
    //     $name = \implode( '.', $fragments );
    //
    //     \assert(
    //             \str_contains( $name, '.' ) && \strlen( $name ) > 5,
    //             "Invalid Asset name '{$name}'. Names must contain at least 5 characters and start with their Type.",
    //     );
    //
    //     return $name;
    // }
}
