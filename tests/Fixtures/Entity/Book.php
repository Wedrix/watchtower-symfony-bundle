<?php

declare(strict_types=1);

namespace Wedrix\WatchtowerBundle\Tests\Fixtures\Entity;

class Book
{
    public ?int $id = null;

    public function __construct(public string $title, public Author $author)
    {
        $author->books->add($this);
    }
}
