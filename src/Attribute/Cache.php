<?php

namespace Arnaud23\AttributeExecutionBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Cache
{
    public function __construct(
        public string $strategy = 'array',
        public int $ttl = 300
    ) {}
}