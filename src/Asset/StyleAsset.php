<?php

namespace Core\Asset;

use Core\Asset\Meta\StyleMeta;
use Core\AssetManager\Asset\InlinableAsset;
use Core\AssetManager\AssetDefinition;
use Core\AssetManager\Interface\{AssetMetaInterface, MinifiedAssetInterface};
use Core\View\Element;
use Core\View\Element\Attributes;
use Support\{StylesheetMinifier};
use Psr\Cache\CacheItemPoolInterface;
use Stringable;
use UnitEnum;
use function Support\{file_save, is_path};

/**
 * @property-read StyleMeta $meta
 */
final class StyleAsset extends AssetDefinition implements MinifiedAssetInterface
{
    use InlinableAsset;

    public const Type TYPE = Type::STYLE;

    private readonly StylesheetMinifier $minifier;

    protected ?string $path = null;

    public ?string $compiled = null;

    /**
     * @param string                  $name
     * @param array<array-key,string> $source
     * @param null|AssetMetaInterface $meta
     */
    public function __construct(
        string              $name,
        protected array     $source,
        ?AssetMetaInterface $meta = null,
    ) {
        parent::__construct( $name, $meta );
    }

    protected function initialize() : void {}

    public function addSource( string|Stringable $path, bool $prepend = false ) : self
    {
        if ( $prepend ) {
            $this->source = [(string) $path, ...$this->source];
        }
        else {
            $this->source[] = (string) $path;
        }

        return $this;
    }

    public function compile() : self
    {
        if ( $this->compiled ) {
            return $this;
        }

        foreach ( $this->source as $source ) {
            $isPath = is_path( $source );
            if ( $isPath ) {
                if ( \glob( $source ) ) {
                    $this->getMinifier()->setSource( ...\glob( $source ) ?: [] );
                }
                elseif ( \file_exists( $source ) ) {
                    $this->getMinifier()->setSource( $source );
                }
            }
            else {
                $this->getMinifier()->setSource( $source );
            }
        }

        $this->getMinifier()->minify( $this->name );

        $this->compiled = $this->getMinifier()->__toString();

        $this->path ??= $this->getSourcePath();

        if ( ! $this->getMinifier()->usedCache() || ! \file_exists( $this->path ) ) {
            file_save( $this->path, $this->compiled );
        }

        return $this;
    }

    public function element(
        string|Attributes|bool|int|array|float|UnitEnum|null ...$attributes,
    ) : Element {
        $this->compile();

        $this->element ??= $this->meta->prefersInline
                ? Element::style(
                    inline : $this->compiled,
                )
                : Element::style(
                    href : $this->getSourceUrl( true ),
                    rel  : 'stylesheet',
                );

        if ( $attributes ) {
            $this->element->attributes->merge( $attributes );
        }

        $this->element->attributes
            ->set( 'asset-name', $this->name )
            ->set( 'asset-id', $this->assetID );

        return $this->element;
    }

    protected function export() : array
    {
        return [
            'source' => $this->source,
            'path'   => $this->path,
        ];
    }

    public function getSourcePath() : string
    {
        return $this->path ??= $this->pathfinder->get(
            "dir.public.assets/{$this->fileName( 'css' )}",
        );
    }

    public function getSourceUrl( bool $version = false ) : string
    {
        return $this->pathfinder->get(
            $this->getSourcePath(),
            'dir.public',
        ).( $version ? "?v={$this->assetID}" : '' );
    }

    public function getMinifier() : StylesheetMinifier
    {
        return $this->minifier ??= new StylesheetMinifier(
            cachePool : $this->cache instanceof CacheItemPoolInterface ? $this->cache : null,
            logger    : $this->logger,
        );
    }
}
