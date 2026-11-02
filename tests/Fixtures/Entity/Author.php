<?php

declare(strict_types=1);

namespace Wedrix\WatchtowerBundle\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Author
{
    public ?int $id = null;
    public Collection $books;

    public function __construct(public string $name)
    {
        $this->books = new ArrayCollection();
    }
}
