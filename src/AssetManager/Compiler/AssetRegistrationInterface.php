<?php

namespace Core\Service\AssetManager\Compiler;

use JetBrains\PhpStorm\Deprecated;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag( 'core.asset_registration' )]
#[Deprecated]
interface AssetRegistrationInterface
{
}
