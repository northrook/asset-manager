<?php

declare(strict_types=1);

namespace Core\Service\AssetManager;

use Core\Service\AssetManager\Asset\{AssetInterface, Type};
use Latte\Runtime\Html;
use Support\Interface\DataObject;
use Stringable;

final readonly class Asset extends DataObject implements AssetInterface
{
    public function __construct(
        private string $name,
        private string $assetID,
        private string $html,
        private Type   $type,
    ) {
    }

    /**
     * Return the {@see Asset::build} as {@see HtmlStringable}
     *
     * @return string|Stringable
     */
    public function getHtml() : string|Stringable
    {
        if ( \class_exists( Html::class ) ) {
            return new Html( $this->html );
        }
        return $this->html;
    }

    public function name() : string
    {
        return $this->name;
    }

    public function assetID() : string
    {
        return $this->assetID;
    }

    /**
     * Returns the asset `type` by default.
     *
     * @param null|string|Type $is
     *
     * @return bool|Type
     */
    public function type( string|Type|null $is = null ) : Type|bool
    {
        return $is ? Type::from( $is ) === $this->type : $this->type;
    }
}
