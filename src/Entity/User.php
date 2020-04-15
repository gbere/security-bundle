<?php

declare(strict_types=1);

namespace Gbere\SimpleAuth\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Gbere\SimpleAuth\Repository\UserRepository")
 */
class User extends UserBase
{
}
