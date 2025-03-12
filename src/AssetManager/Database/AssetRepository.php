<?php

namespace Core\AssetManager\Database;

use Doctrine\ORM\EntityRepository;

class AssetRepository extends EntityRepository
{
    public function findByName( string $assetName ) : ?AssetEntity
    {
        return $this->findOneBy( ['assetName' => $assetName] );
    }
}
