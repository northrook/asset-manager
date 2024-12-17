<?php

declare(strict_types=1);

namespace Core\Service\AssetManager\Model;

use Core\Service\AssetManager\{Asset, AssetModel, Asset\AssetInterface};
use Northrook\{HTML\Element, JavaScriptMinifier, MinifierInterface};
use ValueError;

final class ScriptAsset extends AssetModel
{
    use Trait\MinifyAssetCompiler, Trait\InlinableAsset;

    public function render( ?array $attributes = null ) : AssetInterface
    {
        $compiledJS = $this->compile( $this->getSources() );

        if ( ! $compiledJS ) {
            throw new ValueError();
        }

        $attributes['asset-name'] = $this->getName();
        $attributes['asset-id']   = $this->assetID();

        if ( $this->prefersInline ) {
            $html = (string) new Element(
                tag        : 'script',
                attributes : $attributes,
                content    : $compiledJS,
            );
        }
        else {
            $this->publicPath->save( $compiledJS );

            $attributes['href'] = $this->publicUrl.$this->version();

            $html = (string) new Element( 'script', $attributes );
        }

        return new Asset(
            $this->getName(),
            $this->assetID(),
            $this->getType(),
            $html,
        );
    }

    /**
     * @param null|MinifierInterface $compiler
     *
     * @return MinifierInterface
     */
    protected function compiler( ?MinifierInterface $compiler = null ) : MinifierInterface
    {
        return $this->compiler ??= $compiler ?? new JavaScriptMinifier( [] );
    }
}
