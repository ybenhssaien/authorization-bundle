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
        /* Si pas connecté => non autorisé */
        if (\is_null($this->userRole)) {
            return false;
        }

        /* Si admin toujours autorisé */
        if ('ROLE_ADMIN' === $this->userRole) {
            return true;
        }

        /* Retourner l'autorisation depuis l'authorizationMap si existe, false si n'exste pas */
        return $this->authorizationMap->getMap()[$entity][$property][$this->userRole][$action] ?? false;
    }

    /**
     * Vérifier si la propriété est déclarée.
     */
    public function isPropertyExists(string $property, string $entity): bool
    {
        return isset($this->authorizationMap->getMap()[$entity][$property]);
    }
}
