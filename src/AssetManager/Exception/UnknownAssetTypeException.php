<?php

declare(strict_types=1);

namespace Core\AssetManager\Exception;

use Core\Asset\Type;
use Core\AssetManager\AssetDefinition;
use Core\AssetManager\Config\AssetRegistration;
use InvalidArgumentException;
use Throwable;

final class UnknownAssetTypeException extends InvalidArgumentException
{
    /**
     * @param AssetDefinition|AssetRegistration|Type $asset
     * @param null|Throwable                         $previous
     */
    public function __construct(
        Type|AssetDefinition|AssetRegistration $asset,
        ?Throwable                             $previous = null,
    ) {
        $type = $asset instanceof Type ? $asset->name : $asset->type->name;

        $message = "Unknown Asset 'Type::{$type}'";

        if ( $asset instanceof AssetDefinition ) {
            $message .= " for '{$asset->name}'.";
        }
        else {
            $message .= '.';
        }

        parent::__construct( $message, 500, $previous );
    }
}
