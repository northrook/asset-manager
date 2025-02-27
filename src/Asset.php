<?php

declare(strict_types=1);

namespace Core;

use Core\Asset\Type;
use Core\AssetManager\Config\AssetReference;
use Core\AssetManager\Interface\AssetInterface;
use Core\View\Element;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Stringable;

abstract class Asset implements AssetInterface
{
    public const Type TYPE = Type::ABSTRACT;

    protected readonly AssetReference $reference;

    protected readonly Pathfinder $pathfinder;

    protected readonly ?CacheItemPoolInterface $cache;

    protected readonly ?LoggerInterface $logger;

    protected readonly string $publicDirectory;

    protected readonly string $publicAssetsDirectory;

    public readonly string $name;

    /** @var string `16` character alphanumeric */
    public readonly string $assetID;

    protected ?Element $element = null;

    protected string $path;

    protected ?string $url = null;

    abstract protected function initialize() : void;

    abstract public function compile() : self;

    final public function setDependencies(
        AssetReference          $reference,
        Pathfinder              $pathfinder,
        ?CacheItemPoolInterface $cache = null,
        ?LoggerInterface        $logger = null,
        string                  $publicDirectory = 'dir.public',
        string                  $publicAssetsDirectory = 'dir.public/assets',
    ) : AssetInterface {
        if ( $reference->type !== $this::TYPE ) {
            throw new RuntimeException(
                'Asset type must be `'.$this::TYPE->name.'`; `'.$reference->type->name.'` given.',
            );
        }

        if ( isset( $this->reference ) ) {
            throw new RuntimeException( 'Asset already initialized.' );
        }

        $this->reference             = $reference;
        $this->pathfinder            = $pathfinder;
        $this->cache                 = $cache;
        $this->logger                = $logger;
        $this->publicDirectory       = $publicDirectory;
        $this->publicAssetsDirectory = $publicAssetsDirectory;

        $this->name = $this->reference->name;

        $this->initialize();

        return $this;
    }

    final public function build(
        ?string $assetID = null,
    ) : AssetInterface {
        $this->setAssetID( $assetID );
        return $this;
    }

    final public function getReference() : AssetReference
    {
        return $this->reference;
    }

    public function __toString() : string
    {
        return $this->element()->render();
    }

    public function getHtml() : Stringable
    {
        return $this->element()->getHtml();
    }

    final protected function fileName( ?string $ext = null ) : string
    {
        $reference = \explode( '.', $this->reference->name, 2 );
        $name      = \end( $reference );
        $fileName  = \str_replace( '.', '-', $name );

        if ( $ext ) {
            $fileName .= '.'.\trim( $ext, '.' );
        }

        return $fileName;
    }

    /**
     * @param null|string $assetID
     *
     * @return string `16` character alphanumeric
     */
    private function setAssetID( ?string $assetID ) : string
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

    public function getSourceUrl( bool $version = false ) : string
    {
        $this->url ??= $this->pathfinder->get( $this->getSourcePath(), $this->publicDirectory );

        if ( $version ) {
            $this->url .= '?v='.$this->getVersion();
        }

        return $this->url;
    }

    public function getVersion() : string
    {
        return $this->assetID;
    }
}
