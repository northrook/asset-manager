<?php

namespace Core\Service\AssetManager\Model;

use Northrook\HTML\Element;
use Northrook\StylesheetMinifier;
use Stringable;
use Core\{PathfinderInterface, Service\AssetManager\Asset, SettingsInterface};
use Core\Service\AssetManager\Asset\{AssetInterface, AssetModel, Source, Type};
use Support\Normalize;

final class StyleAsset extends AssetModel
{
    private readonly StylesheetMinifier $minifier;

    protected readonly PathfinderInterface $pathfinder;

    protected ?bool $prefersInline = null;

    public static function register( string $name, array $sources, Source|string $source = Source::LOCAL ) : array
    {
        return ( new self( $name, $sources, $source, Type::STYLE ) )->getConfiguration();
    }

    public function addSource( string|Stringable $source ) : self
    {
        $this->compiler()->addSource( (string) $source );
        return $this;
    }

    public function prefersInline( ?bool $set = true ) : self
    {
        $this->prefersInline = $set;
        return $this;
    }

    public function build(
        PathfinderInterface $pathfinder,
        ?SettingsInterface  $settings = null,
        ?string             $assetID = null,
    ) : self {
        $this->assetID( $assetID );
        $this->pathfinder = $pathfinder;

        $this->compiler()->addSource( ...$this->sources );

        return $this;
    }

    public function render( ?array $attributes = null ) : AssetInterface
    {
        $attributes['asset-name'] = $this->name;
        $attributes['asset-id']   = $this->assetID;

        if ( $this->prefersInline ) {
            $html = (string) new Element(
                tag        : 'style',
                attributes : $attributes,
                content    : $this->compiler()->toString(),
            );
        }
        else {
            $publicAssetPath = $this->pathfinder->getFileInfo(
                "dir.public.assets/{$this->name}.css",
            );

            $publicAssetPath->save( $this->compiler()->toString() );

            $relative = $this->pathfinder->get( $publicAssetPath, 'dir.public' );

            $url = Normalize::url( $relative );

            $attributes['rel'] = 'stylesheet';
            $attributes['src'] = "{$url}".$this->version();

            $html = (string) new Element( 'link', $attributes );
        }
        return new Asset(
            $this->name,
            $this->assetID,
            $html,
            $this->type,
        );
    }

    public function version() : string
    {
        return "?v={$this->assetID}";
    }

    private function compiler() : StylesheetMinifier
    {
        return $this->minifier ??= new StylesheetMinifier();
    }
}
