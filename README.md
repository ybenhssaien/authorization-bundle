The AuthorizationBundle
=======================

The AuthorizationBundle manages authorizations on properties of an entity, they can be either readable or writable or
 both by some roles and not others, every property of an entity can be authorized or not for reading or writing using
  [isGranted()][1] in PHP files or Twig version [is_granted()][2].

Installation
-------------------------------
1. Install with composer :
    ```shell
    composer require ybenhssaien/authorization-bundle
    ```
1. Enable it in `config/bundles.php` :
    ```php
   return [
       ......
       Ybenhssaien\AuthorizationBundle\AuthorizationBundle::class => ['all' => true],
   ];
    ```

Example
-------------------------------
- Declaration of properties :
```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ybenhssaien\AuthorizationBundle\Annotation\Authorization;
use Ybenhssaien\AuthorizationBundle\Annotation\HasAuthorizations;

/**
 * @ORM\Entity()
 *
 * @HasAuthorizations()
 */
class Person
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=10)
     *
     * @Authorization(
     *      "name" : "gender_renamed",
     *      {
     *          "ROLE_HR" : {"read": true, "write": true},
     *          "ROLE_USER" : {"read": true, "write": false},
     *          "ROLE_OTHERS" : {"read": true, "write": false},
     *      }
     *  )
     */
    private $gender;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Authorization({
     *     "ROLE_HR",
     *     "ROLE_USER" : {"write": false},
     *     "ROLE_OTHERS" : {"write": false},
     * })
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Authorization({
     *     "ROLE_HR",
     *     "ROLE_USER" : {"write": false},
     *     "ROLE_OTHERS" : {"write": false},
     * })
     */
    private $lastName;

    /**
     * @ORM\Column(type="string", length=10)
     *
     * @Authorization(
     *     {"ROLE_HR"},
     *     "aliases" : {"code"},
     * )
     */
    private $hrCode;
}
```
- Usage in a FormBuilder :
```php
<?php

namespace App\Form;

use App\Entity\Person;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class PersonType extends AbstractType
{
    protected Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // "gender_renamed" is the name chosen for this property ("gender" doesn't exist)
        if ($this->security->isGranted('write', [Person::class, 'gender_renamed'])) {
            $builder->add('gender');
        }

        if ($this->security->isGranted('write', [Person::class, 'firstName'])) {
            $builder->add('firstName');
        }

        if ($this->security->isGranted('write', [Person::class, 'lastName'])) {
            $builder->add('lastName');
        }

        // "code" is declared an alias of "hrCode" (can use both of them)
        if ($this->security->isGranted('write', [Person::class, 'code'])) {
            $builder->add('hrCode');
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Person::class,
        ]);
    }
}
```

- Usage in Twig :
```twig
{# Accepts "person" as object #}
{% if is_granted('read', [person, 'firstName']) %}
    {{ person.firstName }}
{% endif %}

{# Accepts class name #}
{% if is_granted('read', ['App\\Entitiy\\Person', 'lastName']) %}
    {{ person.lastName }}
{% endif %}
```

Documentation
-------------------------------
1. ### Annotations
    - **Required** : Declare an entity having authorizations, without `@HasAuthorizations` annotation, the entity is
     ignored automatically.
   ```php
   <?php

   namespace App\Entity;

   // ....   
   use Ybenhssaien\AuthorizationBundle\Annotation\HasAuthorizations;

   /**
    * @Entity()
    *
    * @HasAuthorizations()
    */
   class Entity
   {}       
   ```
   > **Important** : Authorizations work only with entities using [Doctrine ORM][3]

    - Declare authorizations map on properties using `@Authorization()` that accepts 3 named arguments
        - `name` : [optional] string to replace the default property name used (without `$` (example : `private $propertyOfEntity` => `propertyOfEntity`))
        ```php
           /**
             * @ORM\Column(type="string")
             *
             * @Authorization(
             *      "name" : "custom_name",
             *      {/* Roles */}
             *  )
             */
          private $property;
        ``` 
        - `aliases` : [optional] string array of different names can be used for the target property
        ```php
           /**
             * @ORM\Column(type="string")
             *
             * @Authorization(
             *      "aliases" : {"alias1", "alias2", ....},
             *      {/* Roles */}
             *  )
             */
          private $property;
        ``` 
        - `roles` : [optional] authorizations array of roles, each role may have an array of options with keys read and write that accept boolean as value.
            - Passing roles as an argument without `roles` key is acceptable
        ```php
           /**
             * @ORM\Column(type="string")
             *
             * @Authorization(
             *      "roles" : {
             *        "ROLE_USER",
             *        "ROLE_GUEST" : {"write" : false},
             *        "ROLE_OTHER" : {"read" : false, "write" : false},
             *      }
             *  )
             */
          private $property;

           /**
             * @ORM\Column(type="string")
             *
             * @Authorization(
             *      "name" : "custom_name",
             *      "aliases" : {"alias"},
             *      {
             *        "ROLE_USER",
             *        "ROLE_GUEST" : {"write" : false},
             *        "ROLE_OTHER" : {"read" : false, "write" : false},
             *      }
             *  )
             */
          private $property2;
        ```
      > - The default value of an undeclared action is `true` (example for `ROLE_USER` without actions is equivalent to 
      `{"read" : true, "write" : true}`)
      > - Declaring `@Authorization` without roles, is equivalent to `{"read" : false, "write" : false}` (the same case for undeclared roles)
1. ### Check authorization
To check authorization on a property, use Security `isGranted()` method or twig equivalent `is_granted()` following
 the signature :
 - `isGranted($action, [$entity, $property]` :
    - `$action` : 'read' or 'write' string
    - `$entity` : Object or class name as string
    - `$property` : The name of property, the custom `name` or an `alias` if declared

Commandes
-------------------------------
- `bin/console debug:authorization` : display authorizations map

Authorization services
-------------------------------
- [AuthorizationService][7] : Contains methods to check authorizations and to get the map of authorizations.
    -  `getAuthorizationMap(): AuthorizationMap` : return an instance of `AuthorizationMap` which contains the
     authorizations map and mapped entities.
    - `canReadData(string $property, string $entity): bool` : check if a property is authorized for reading.
    - `canWriteData(string $property, string $entity): bool` : check if a property is authorized for writing.
    - `isPropertyExists(string $property, string $entity): bool` : check if a property is declared for authorization.
- [AuthorizationMap][8] : Contains the map and entities declared for authorizations
    - `getMap(): array` : Return the map of authorizations.
    - `getEntities(): array` : Return the list of supported entities.

Contributing & bug report
-------------------------------
- Report a bug or suggest a feature : [Github issues][4]
- Contribute : [fork][5] && create [pull request][6]

-------------------------------
@developed by [Youssef BENHSSAIEN](https://github.com/ybenhssaien)

[1]: https://github.com/symfony/symfony/blob/5.1/src/Symfony/Component/Security/Core/Authorization/AuthorizationChecker.php
[2]: https://symfony.com/doc/current/security.html#access-control-in-templates
[3]: https://www.doctrine-project.org/projects/doctrine-orm/en/2.8/tutorials/getting-started.html
[4]: https://github.com/ybenhssaien/authorization-bundle/issues
[5]: https://github.com/ybenhssaien/authorization-bundle/fork
[6]: https://github.com/ybenhssaien/authorization-bundle/pulls
[7]: https://github.com/ybenhssaien/authorization-bundle/master/Service/AuthorizationService.php
[8]: https://github.com/ybenhssaien/authorization-bundle/master/AuthorizationMap.php