<?php

namespace Core\AssetManager\Interface;

use Core\Interface\ViewInterface;
use Core\View\Element;
use Core\View\Element\Attributes;

interface AssetInterface extends ViewInterface
{
    public function element() : Element;

    public function attributes() : Attributes;

    public function build( ?string $assetID = null, bool $rebuild = false ) : self;
}
