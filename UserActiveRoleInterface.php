<?php

namespace Ybenhssaien\AuthorizationBundle;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Interface UserActiveRoleInterface
 *
 * getActiveRole() : returns the selected role in case user has multiple roles
 *
 * @package Ybenhssaien\AuthorizationBundle
 */
interface UserActiveRoleInterface extends UserInterface
{
    public function getActiveRole(): string;
}