<?php

namespace Northrook;


use Northrook\AssetManager\Asset;
use Northrook\Cache\ManifestCache;
use Northrook\Core\Trait\SingletonClass;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;

/**
 * @template PathString of string
 * @template AssetArray of array
 */
final class AssetManager
{
    use SingletonClass;

    /** @var array<string, AssetArray> */
    private array $enqueued = [
        'stylesheet' => [],
        'script'     => [],
        'link'       => [],
    ];
    /** @var string<PathString> */
    public readonly string $publicRoot;
    /** @var string<PathString> */
    public readonly string $publicAssets;

    public function __construct(
        string                            $publicRoot,
        string                            $publicAssets,
        private readonly AdapterInterface $cache,
        private readonly ManifestCache    $manifest,
        private readonly ?LoggerInterface $logger = null,
    ) {
        $this->instantiationCheck();

        $this->publicRoot   = normalizeRealPath( $publicRoot );
        $this->publicAssets = normalizeRealPath( $publicAssets );
        AssetManager::$instance = $this;
    }

    public function addStylesheet(
        string $href,
        array  $attributes = [],
        ?bool  $inline = null,
    ) : AssetManager{
        $this->enqueued[ 'stylesheet' ][] = [
            'source' => $href,
            'attributes' => $attributes,
            'inline' => $inline,
        ];
        return $this;
    }

    public function addScript(
        string $src,
        array  $attributes = [],
        ?bool  $inline = null,
    ) : AssetManager{
        $this->enqueued[ 'script' ][] = [
            'source' => $src,
            'attributes' => $attributes,
            'inline' => $inline,
        ];
        return $this;
    }

    public function addLink(
        string $src,
        array  $attributes = [],
    ) : AssetManager{
        $this->enqueued[ 'link' ][] = [
            'source' => $src,
            'attributes' => $attributes,
        ];
        return $this;
    }

    /**
     * Get the {@see AssetManager} cache adapter.
     *
     * @return AdapterInterface
     */
    public static function cache() : AdapterInterface {
        return AssetManager::getInstance()->cache;
    }
}