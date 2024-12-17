<?php

declare(strict_types=1);

namespace Core\Service\AssetManager\Model;

use Core\Service\AssetManager\{Asset, AssetModel, Asset\AssetInterface};
use Northrook\{HTML\Element, MinifierInterface, StylesheetMinifier};
use ValueError;

final class StyleAsset extends AssetModel
{
    use Trait\MinifyAssetCompiler, Trait\InlinableAsset;

    public function render( ?array $attributes = null ) : AssetInterface
    {
        $compiledCSS = $this->compile( $this->getSources() );

        if ( ! $compiledCSS ) {
            throw new ValueError();
        }

        $attributes['asset-name'] = $this->getName();
        $attributes['asset-id']   = $this->assetID();

        if ( $this->prefersInline ) {
            $html = (string) new Element(
                tag        : 'style',
                attributes : $attributes,
                content    : $compiledCSS,
            );
        }
        else {
            $this->publicPath->save( $compiledCSS );

            $attributes['rel'] = 'stylesheet';
            $attributes['src'] = $this->publicUrl.$this->version();

            $html = (string) new Element( 'link', $attributes );
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
        return $this->compiler ??= $compiler ?? new StylesheetMinifier();
    }
}
