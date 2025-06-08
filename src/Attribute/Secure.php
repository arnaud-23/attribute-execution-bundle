<?php

namespace Arnaud23\AttributeExecutionBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Secure
{
    public function __construct(public string $role = 'ROLE_USER') {}
}