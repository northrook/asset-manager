<?php

declare(strict_types=1);

namespace Core\Service;

use Core\{
    Service\AssetManager\Asset\AssetInterface,
    Service\AssetManager\Interface\AssetManagerInterface
};
use Core\Service\AssetManager\AssetFactory;
use Psr\Log\LoggerInterface;

final class AssetManager implements AssetManagerInterface
{
    /** @var null|string[] */
    protected ?array $deployed = null;

    /** @var string[] */
    protected array $enqueued = [];

    /**
     * @param AssetFactory     $factory
     * @param ?LoggerInterface $logger
     *
     * @noinspection PhpPropertyOnlyWrittenInspection
     */
    public function __construct(
        private readonly AssetFactory     $factory,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Indicate that one or more assets have already been resolved.
     *
     * These will be skipped on {@see self::resolveEnqueuedAssets()}.
     *
     * @param string ...$deployed
     *
     * @return void
     */
    public function setDeployed( string ...$deployed ) : void
    {
        $this->deployed = $deployed;
    }

    /**
     * Enqueue one or more assets to later be resolved.
     *
     * @param string ...$name
     *
     * @return void
     */
    public function enqueueAsset( string ...$name ) : void
    {
        foreach ( $this->enqueued as $asset ) {
            $this->enqueued[$asset] ??= $asset;
        }
    }

    // TODO : Check if asset is registered somewhere
    public function hasEnqueued( string $name ) : bool
    {
        return isset( $this->enqueued[$name] );
    }

    public function renderAsset( string $name, array $attributes = [] ) : ?AssetInterface
    {
        dump( [__METHOD__, $name, $attributes, AssetInterface::class] );
        return null;
    }

    public function resolveEnqueuedAssets( bool $cached = true ) : array
    {
        dump(
            [
                __METHOD__,
                'enqueued' => $this->enqueued,
                'cached'   => $cached,
                [AssetInterface::class],
            ],
        );

        return [];
    }

    public function getEnqueuedAssets() : array
    {
        return $this->enqueued;
    }

    // private function assetFactory() : AssetFactory
    // {
    //     return ( $this->lazyFactory )();
    // }
}
