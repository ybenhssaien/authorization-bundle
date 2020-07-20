<?php

namespace Ybenhssaien\AuthorizationBundle\Service;

use Symfony\Component\Security\Core\Security;
use Ybenhssaien\AuthorizationBundle\AuthorizationMap;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthorizationService
{
    protected string $userRole;
    protected AuthorizationMap $authorizationMap;

    public function __construct(Security $security, AuthorizationMap $authorizationMap)
    {
        if (
            $security->getToken()
            && ($user = $security->getToken()->getUser()) instanceof UserInterface
        ) {
            $this->userRole = \method_exists($user, 'getCurrentRole')
                ? $user->getCurrentRole()
                : \current($user->getRoles());
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
        /* If not conencted by default not authorized */
        if (\is_null($this->userRole)) {
            return false;
        }

        /* Return authorization from map or false if not exists */
        return $this->authorizationMap->getMap()[$entity][$property][$this->userRole][$action] ?? false;
    }

    /**
     * Check if property is declared
     */
    public function isPropertyExists(string $property, string $entity): bool
    {
        return isset($this->authorizationMap->getMap()[$entity][$property]);
    }
}
