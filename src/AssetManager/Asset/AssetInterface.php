<?php

declare(strict_types=1);

namespace Core\Service\AssetManager\Asset;

use Stringable;

/**
 * @used-by \Core\Service\AssetManager\Interface\AssetManagerInterface
 *
 * @author  Martin Nielsen <mn@northrook.com>
 */
interface AssetInterface extends Stringable
{
    /**
     * This class contains a fully resolved asset.
     *
     * @param string $name
     * @param string $assetID
     * @param Type   $type
     * @param string $html
     */
    public function __construct( string $name, string $assetID, Type $type, string $html );

    /**
     * @return string `dot.separated` lowercase
     */
    public function name() : string;

    /**
     * @return string `16` character alphanumeric hash
     */
    public function assetID() : string;

    /**
     * Returns the asset `type` by default.
     *
     * @param null|string|Type $is
     *
     * @return bool|Type
     */
    public function type( null|string|Type $is = null ) : Type|bool;

    /**
     * Returns fully resolved `HTML` of the asset.
     *
     * @return string|Stringable
     */
    public function getHTML() : string|Stringable;
}
