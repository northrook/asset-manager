<?php

declare(strict_types=1);

namespace Core\AssetManager\Database;

use Core\Asset\Type;
use Core\AssetManager\Asset\{AssetMeta, AssetReference};
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use DateTimeImmutable;
use JsonException;

#[ORM\Entity( repositoryClass : AssetRepository::class )]
#[ORM\Table( name : 'assets' )]
#[ORM\Index( name : 'idx_asset_name', columns : ['asset_name'] )]
#[ORM\HasLifecycleCallbacks]
class AssetEntity
{
    #[ORM\Id]
    #[ORM\Column(
        name     : 'asset_name',
        type     : Types::STRING,
        length   : 255,
        unique   : true,
        nullable : false,
    )]
    private string $assetName;

    #[ORM\Column( name : 'type', type : Types::STRING, length : 50 )]
    private string $type;

    /** @var array<array-key, string> */
    #[ORM\Column( name : 'source', type : Types::JSON )]
    private array $source;

    /** @var array<array-key, string> */
    #[ORM\Column( name : 'service_pass', type : Types::JSON )]
    private array $servicePass;

    #[ORM\Column( name : 'reference', type : Types::TEXT )]
    private string $reference;

    #[ORM\Column( name : 'meta', type : Types::TEXT )]
    private string $meta;

    #[ORM\Column( name : 'created_at', type : Types::DATETIME_IMMUTABLE )]
    private DateTimeImmutable $createdAt;

    #[ORM\Column( name : 'updated_at', type : Types::DATETIME_IMMUTABLE )]
    private DateTimeImmutable $updatedAt;

    /**
     * @param string                     $assetName
     * @param string                     $type
     * @param array<array-key, string>   $source
     * @param array<array-key, string>   $servicePass
     * @param null|AssetReference|string $reference
     * @param null|AssetMeta|string      $meta
     */
    public function __construct(
        string                     $assetName,
        Type|string                $type,
        array                      $source = [],
        array                      $servicePass = [],
        null|string|AssetReference $reference = null,
        null|string|AssetMeta      $meta = null,
    ) {
        $this->assetName = $assetName;
        $this->setType( $type );
        $this->source      = $source;
        $this->servicePass = $servicePass;
    }

    public function getName() : string
    {
        return $this->assetName;
    }

    public function getType() : string
    {
        return $this->type;
    }

    /**
     * @return array<array-key, string>
     */
    public function getSource() : array
    {
        return $this->source;
    }

    /**
     * @return array<array-key, string>
     */
    public function getServicePass() : array
    {
        return $this->servicePass;
    }

    /**
     * Returns {@see AssetReference} as encoded `JSON`.
     *
     * @return string
     */
    public function getReference() : string
    {
        return $this->reference;
    }

    /**
     * Returns {@see AssetMeta} as encoded `JSON`.
     *
     * @return string
     */
    public function getMeta() : string
    {
        return $this->meta;
    }

    public function getCreatedAt() : DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt() : DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue() : void
    {
        $this->createdAt = new DateTimeImmutable();
        $this->setUpdatedAtValue();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue() : void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function setName( string $assetName ) : void
    {
        $this->assetName = $assetName;
    }

    public function setType( Type|string $type ) : void
    {
        $this->type = $type instanceof Type
                ? $type->name
                : Type::from( $type )->name;
    }

    /**
     * @param array<array-key, string> $source
     *
     * @return void
     */
    public function setSource( array $source ) : void
    {
        $this->source = $source;
    }

    /**
     * @param array<array-key, string> $servicePass
     *
     * @return void
     */
    public function setServicePass( array $servicePass ) : void
    {
        $this->servicePass = $servicePass;
    }

    /**
     * @param null|AssetReference|string $reference
     *
     * @throws JsonException
     */
    public function setReference( null|string|AssetReference $reference ) : void
    {
        if ( $reference instanceof AssetReference ) {
            $reference = $reference->export();
        }

        $this->reference = $reference ?? '';
    }

    /**
     * @param null|AssetMeta|string $meta
     *
     * @throws JsonException
     */
    public function setMeta( null|string|AssetMeta $meta ) : void
    {
        if ( $meta instanceof AssetMeta ) {
            $meta = $meta->export();
        }

        $this->meta = $meta ?? '';
    }
}
