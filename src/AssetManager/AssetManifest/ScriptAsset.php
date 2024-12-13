<?php

namespace Core\Service\AssetManager\AssetManifest;

use JetBrains\PhpStorm\Deprecated;
use Northrook\HTML\Element;
use Northrook\{JavaScriptMinifier};
use Stringable;
use Core\{PathfinderInterface,
        Service\AssetManager\Asset,
        Service\AssetManager\AssetManifest\AssetModel,
        SettingsInterface};
use Core\Service\AssetManager\Asset\{AssetInterface, Source, Type};
use Support\Normalize;

#[Deprecated]
final class ScriptAsset extends AssetModel
{
    private readonly JavaScriptMinifier $minifier;

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
                tag        : 'script',
                attributes : $attributes,
                content    : $this->compiler()->minify(),
            );
        }
        else {
            $publicAssetPath = $this->pathfinder->getFileInfo(
                "dir.public.assets/{$this->name}.js",
            );

            $publicAssetPath->save( $this->compiler()->minify() );

            $relative = $this->pathfinder->get( $publicAssetPath, 'dir.public' );

            $url = Normalize::url( $relative );

            $attributes['href'] = "{$url}".$this->version();

            $html = (string) new Element( 'script', $attributes );
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

    private function compiler() : JavaScriptMinifier
    {
        return $this->minifier ??= new JavaScriptMinifier( [] );
    }
}
