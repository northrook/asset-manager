<?php

declare(strict_types=1);

namespace Core\AssetManager\Exception;

use Core\Asset\Type;
use Core\AssetManager\Asset\AssetReference;
use Core\AssetManager\Config\AssetRegistration;
use InvalidArgumentException;
use Throwable;

final class UnknownAssetTypeException extends InvalidArgumentException
{
    /**
     * @param AssetReference|AssetRegistration|Type $asset
     * @param null|Throwable                        $previous
     */
    public function __construct(
        Type|AssetReference|AssetRegistration $asset,
        ?Throwable                            $previous = null,
    ) {
        $type = $asset instanceof Type ? $asset->name : $asset->type->name;

        $message = "Unknown Asset 'Type::{$type}'";

        if ( $asset instanceof AssetReference ) {
            $message .= " for '{$asset->name}'.";
        }
        else {
            $message .= '.';
        }

        parent::__construct( $message, 500, $previous );
    }
}
