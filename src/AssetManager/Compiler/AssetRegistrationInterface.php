<?php

namespace Core\Service\AssetManager\Compiler;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag( 'core.asset_registration' )]
interface AssetRegistrationInterface
{
}
