<?php

namespace Ybenhssaien\AuthorizationBundle;

use Doctrine\ORM\Configuration;
use Doctrine\Common\Annotations\Reader;
use Ybenhssaien\AuthorizationBundle\Annotation\Authorization;
use Ybenhssaien\AuthorizationBundle\Annotation\HasAuthorizations;

class AuthorizationMap
{
    protected Reader $reader;
    protected array $allEntities = [];
    protected array $entities = [];
    protected array $map = [];

    public function __construct(Configuration $config, Reader $reader)
    {
        $this->allEntities = $config->getMetadataDriverImpl()->getAllClassNames();
        $this->reader = $reader;

        $this->loadAnnotations();
    }

    public function getMap(): array
    {
        return $this->map;
    }

    public function getEntities(): array
    {
        return $this->entities;
    }

    protected function loadAnnotations()
    {
        foreach ($this->allEntities as $class) {
            $this->loadAnnotationFromClass($class);
        }
    }

    protected function loadAnnotationFromClass($class)
    {
        $classRef = new \ReflectionClass($class);

        if (! $this->reader->getClassAnnotation($classRef, HasAuthorizations::class)) {
            return;
        }

        $this->entities[] = $class;

        $this->loadAnnotationFromAttributes($classRef);
    }

    protected function loadAnnotationFromAttributes(\ReflectionClass $classRef)
    {
        $class = $classRef->getName();

        foreach ($classRef->getProperties() as $propertyRef) {
            /** @var Authorization $annotation */
            $annotation = $this->reader->getPropertyAnnotation($propertyRef, Authorization::class);

            if (\is_null($annotation)) {
                continue;
            }

            $name = $annotation->name ?? $propertyRef->getName();
            $this->map[$class][$name] = $annotation->roles;

            foreach ($annotation->aliases as $alias) {
                $this->map[$class][$alias] = $annotation->roles;
            }
        }
    }
}
