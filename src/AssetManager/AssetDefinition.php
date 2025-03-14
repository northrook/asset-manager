<?php

namespace Core\AssetManager;

use Cache\CachePoolTrait;
use Core\Asset\Type;
use Core\AssetManager\Compiler\AssetValidationTrait;
use Core\AssetManager\Config\AssetRegistration;
use Core\AssetManager\Interface\{AssetInterface, AssetMetaInterface};
use Core\Pathfinder;
use Core\Symfony\DependencyInjection\SettingsAccessor;
use Core\View\Element;
use Core\View\Element\Attributes;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\{LoggerAwareInterface, LoggerInterface};
use Stringable;
use UnitEnum;
use function Support\{key_hash};
use const Time\HOUR_4;

abstract class AssetDefinition implements AssetInterface
{
    use CachePoolTrait, SettingsAccessor, AssetValidationTrait;

    public const Type TYPE = Type::ABSTRACT;

    /** @var string `type.name` */
    public readonly string $name;

    public readonly string $reference;

    public readonly Type $type;

    /** @var string `8` character alphanumeric */
    public readonly string $assetID;

    public readonly AssetMetaInterface $meta;

    protected readonly Pathfinder $pathfinder;

    protected readonly ?LoggerInterface $logger;

    protected ?Element $element = null;

    public function __construct(
        string              $name,
        ?AssetMetaInterface $meta = null,
    ) {
        \assert(
            $this->isName( $name ),
            "Asset names must only contain ASCII letters, numbers, periods, and hyphens. '{$name}' provided.",
        );

        $this->name      = $name;
        $this->type      = Type::from( $name );
        $this->reference = \hash( 'xxh64', $name );

        \assert(
            $this::TYPE === $this->type,
            "AssetDefinition type mismatch. Expected '{$this::TYPE->name}', got '{$this->type->name}'.",
        );

        $this->meta = $meta ?? AssetRegistration::getDefaultMeta( $this->type );
    }

    final public function setDependencies(
        Pathfinder              $pathfinder,
        ?CacheItemPoolInterface $cache = null,
        ?LoggerInterface        $logger = null,
    ) : self {
        $this->pathfinder ??= $pathfinder;
        $this->logger     ??= $logger;

        $this->assignCacheAdapter(
            cache      : $cache,
            prefix     : 'manifest',
            defer      : $this->getSetting( 'asset.cache.defer', true ),
            expiration : $this->getSetting( 'asset.cache.expiration', HOUR_4 ),
        );

        if ( $this->logger && $this->cache instanceof LoggerAwareInterface ) {
            $this->cache->setLogger( $this->logger );
        }

        $this->initialize();

        return $this;
    }

    abstract protected function initialize() : void;

    /**
     * @param null|array<array-key, ?string>|Attributes|scalar|UnitEnum ...$attributes
     */
    abstract public function element(
        Attributes|array|null|bool|float|int|string|UnitEnum ...$attributes,
    ) : Element;

    final public function build( ?string $assetID = null ) : self
    {
        $this->assetID ??= $assetID ?? key_hash( 'xxh32', $this::class, $this->name, $this->meta );

        \assert(
            $this->isAssetID( $this->assetID, $message ),
            $message,
        );

        return $this;
    }

    public function getHtml() : Stringable
    {
        return $this->element()->getHtml();
    }

    public function getVersion() : string
    {
        return $this->assetID;
    }

    final public function __toString() : string
    {
        return (string) $this->getHtml();
    }

    final public function __serialize() : array
    {
        return [
            'name'      => $this->name,
            'type'      => $this->type,
            'reference' => $this->reference,
            'meta'      => $this->meta,
        ] + $this->export();
    }

    /**
     * @return array<string, mixed>
     */
    abstract protected function export() : array;

    final protected function fileName( ?string $ext = null ) : string
    {
        $fileName = \str_replace( '.', '/', $this->name );

        if ( $ext ) {
            $fileName .= '.'.\trim( $ext, '.' );
        }

        return $fileName;
    }
}
