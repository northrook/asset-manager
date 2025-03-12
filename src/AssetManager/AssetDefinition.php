<?php

namespace Core\AssetManager;

use Cache\CachePoolTrait;
use Core\Asset\Type;
use Core\AssetManager\Config\AssetRegistration;
use Core\AssetManager\Interface\AssetMetaInterface;
use Core\Pathfinder;
use Core\Symfony\DependencyInjection\SettingsAccessor;
use Core\View\Element;
use Core\View\Element\Attributes;
use Psr\Cache\CacheItemPoolInterface;
use Stringable;
use UnitEnum;
use const Time\HOUR_4;
use LogicException;

abstract class AssetDefinition implements Stringable
{
    use CachePoolTrait, SettingsAccessor;

    public const Type TYPE = Type::ABSTRACT;

    /** @var string `type.name` */
    public readonly string $name;

    public readonly Type $type;

    /** @var string `8` character alphanumeric */
    public readonly string $assetID;

    public readonly AssetMetaInterface $meta;

    protected readonly Pathfinder $pathfinder;

    protected ?Element $element = null;

    /**
     * @param string              $name
     * @param ?AssetMetaInterface $meta
     */
    public function __construct( string $name, ?AssetMetaInterface $meta = null )
    {
        \assert(
            \ctype_alnum( \str_replace( ['.', '-'], '', $name ) ),
            "Asset names must only contain ASCII letters, numbers, periods, and hyphens. '{$name}' provided.",
        );

        $this->name = $name;
        $this->type = Type::from( $name );

        \assert(
            $this::TYPE === $this->type,
            "AssetDefinition type mismatch. Expected '{$this::TYPE->name}', got '{$this->type->name}'.",
        );

        $this->meta = $meta ?? AssetRegistration::getDefaultMeta( $this->type );
    }

    /**
     * Return the HTML element for this Asset.
     *
     * Called when using {@see self::getHtml()} or cast to `string`.
     *
     * @param null|array<array-key, ?string>|Attributes|scalar|UnitEnum ...$attributes
     *
     * @return Element
     */
    abstract public function element(
        Attributes|array|null|bool|float|int|string|UnitEnum ...$attributes,
    ) : Element;

    public function getHtml() : Stringable
    {
        return $this->element()->getHtml();
    }

    final public function __toString() : string
    {
        return (string) $this->getHtml();
    }

    /**
     * @param null|string $assetID
     *
     * @return $this
     */
    final public function build(
        ?string $assetID = null,
    ) : self {
        $this->assetID ??= $assetID ?? \hash( 'xxh32', $this->assetID() );
        \assert(
            \strlen( $this->assetID ) === 8 && \ctype_alnum( $this->assetID ),
            'Asset ID must be 16 alphanumeric characters; ['.\strlen(
                $this->assetID,
            )."] `{$this->assetID}` given",
        );
        return $this;
    }

    /**
     * Set by the {@see AssetManifest}.
     *
     * @internal
     *
     * @param Pathfinder $pathfinder
     *
     * @param ?CacheItemPoolInterface $cache
     *
     * @return self
     */
    final public function setDependencies(
        Pathfinder              $pathfinder,
        ?CacheItemPoolInterface $cache = null,
    ) : self {
        $this->pathfinder ??= $pathfinder;

        $this->setCacheAdapter(
            cache      : $cache,
            prefix     : 'manifest',
            defer      : $this->getSetting( 'asset.cache.defer', true ),
            expiration : $this->getSetting( 'asset.cache.expiration', HOUR_4 ),
        );

        return $this;
    }

    final protected function fileName( ?string $ext = null ) : string
    {
        $fileName = \str_replace( '.', '/', $this->name );

        if ( $ext ) {
            $fileName .= '.'.\trim( $ext, '.' );
        }

        return $fileName;
    }

    /**
     * @param null|string $assetID
     *
     * @return string `8` hexadecimal
     */
    protected function assetID() : string
    {
        if ( isset( $this->assetID ) ) {
            throw new LogicException( 'Asset ID has already been set.' );
        }
        return \implode( ':', [$this::class, $this->name, \serialize( $this->meta )] );
    }

    final public function __serialize() : array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'meta' => $this->meta,
        ] + $this->export();
    }

    /**
     * @return array<string, mixed>
     */
    abstract protected function export() : array;
}
