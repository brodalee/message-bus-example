<?php

namespace App\Redis;

use Symfony\Component\Serializer\Attribute\Ignore;

class Action
{
    public function __construct(
        public readonly string $randomString,
        public readonly string $randomNumber,
        #[Ignore]
        public ?string $id = null,
    )
    {
    }
}