<?php

namespace Ybenhssaien\AuthorizationBundle\Service;

use Symfony\Component\Security\Core\Security;
use Ybenhssaien\AuthorizationBundle\Annotation\Authorization;
use Ybenhssaien\AuthorizationBundle\AuthorizationMap;
use Symfony\Component\Security\Core\User\UserInterface;
use Ybenhssaien\AuthorizationBundle\Exception\InvalidArgumentException;
use Ybenhssaien\AuthorizationBundle\UserActiveRoleInterface;

class AuthorizationService
{
    protected array $roles = [];
    protected ?string $activeRole = null;
    protected AuthorizationMap $authorizationMap;

    public function __construct(Security $security, AuthorizationMap $authorizationMap)
    {
        if (
            $security->getToken()
            && ($user = $security->getToken()->getUser()) instanceof UserInterface
        ) {
            $this->roles = $user->getRoles();

            if ($user instanceof UserActiveRoleInterface) {
                $this->activeRole = $user->getActiveRole();
            }
        }

        $this->authorizationMap = $authorizationMap;
    }

    public function getAuthorizationMap(): AuthorizationMap
    {
        return $this->authorizationMap;
    }

    public function canReadData(string $property, string $entity): bool
    {
        return $this->canPerformActionOnData($property, $entity, 'read');
    }

    public function canWriteData(string $property, string $entity): bool
    {
        return $this->canPerformActionOnData($property, $entity, 'write');
    }

    public function canPerformActionOnData(string $property, string $entity, $action = 'read'): bool
    {
        if (!\in_array($action, Authorization::ACTIONS)) {
            throw new InvalidArgumentException(sprintf(
                'The action "%s" is not supported, please choose one of the supported actions [%s]',
                $action,
                implode(', ', Authorization::ACTIONS)
            ));
        }

        /* If not connected => not authorized */
        if (! \count($this->roles)) {
            return false;
        }

        /* Returns authorizations map if exist, false otherwise */
        return $this->activeRole
            ? $this->isActionAuthorizedForRoleByProperty($property, $entity, $this->activeRole, $action)
            : $this->isActionAuthorizedForRolesByProperty($property, $entity, $this->roles, $action);
    }

    /**
     * Check if the property is mapped for authorization
     */
    public function isPropertyExists(string $property, string $entity): bool
    {
        return isset($this->authorizationMap->getMap()[$entity][$property]);
    }

    protected function getAuthorizationMapForProperty(string $property, string $entity): array
    {
        return $this->authorizationMap->getMap()[$entity][$property] ?? [];
    }

    protected function getAuthorizationMapByRoleForProperty(string $property, string $entity, string $role): bool
    {
        return $this->getAuthorizationMapForProperty($property, $entity)[$role] ?? [];
    }

    protected function isActionAuthorizedForRoleByProperty(string $property, string $entity, string $role, string $action): bool
    {
        return $this->getAuthorizationMapByRoleForProperty($property, $entity, $role)[$action] ?? false;
    }

    protected function isActionAuthorizedForRolesByProperty(string $property, string $entity, array $roles, string $action): bool
    {
        foreach ($roles as $role) {
            if ($this->isActionAuthorizedForRoleByProperty($property, $entity, $role, $action)) {
                return true;
            }
        }

        return false;
    }
}
