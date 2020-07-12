<?php

namespace Ybenhssaien\AuthorizationBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Annotation\Target({"PROPERTY")
 * @Annotation\Attributes({
 *     @Annotation\Attribute("name", type = "string"),
 *     @Annotation\Attribute("roles", type = "array"),
 *     @Annotation\Attribute("aliases", type = "array"),
 * })
 */
class Authorization
{
    const ACTIONS = ['read', 'write'];

    public $name;
    public array $aliases = [];
    public array $roles = [];

    public function __construct($attributes = [])
    {
        $attributes = $attributes['value'];

        if (\array_key_exists('name', $attributes)) {
            /* Ignore name if not a string */
            $this->name = \is_string($attributes['name']) ? $attributes['name'] : null;
            unset($attributes['name']);
        }

        if (\array_key_exists('aliases', $attributes)) {
            /* ignore aliases if not strings */
            $this->aliases = array_filter((array) $attributes['aliases'], fn ($alias) => \is_string($alias));
            unset($attributes['aliases']);
        }

        $this->prepareRoles($attributes['roles'] ?? (array) $attributes);
    }

    protected function prepareRoles(array $roles = [])
    {
        /* default actions set to true */
        $defaultActions = array_fill_keys(self::ACTIONS, true);

        foreach ($roles as $key => $value) {
            $key = ! \is_int($key) ? $key : $value;

            if (\is_array($key)) {
                $this->prepareRoles($key);
            } else {
                /* Merge default actions & actions declared in annotation */
                $mergeActions = array_merge($defaultActions, (array) $value);

                /* keep only supported actions */
                $this->roles[$key] = array_intersect_key($mergeActions, $defaultActions);
            }
        }
    }
}
