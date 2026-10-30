<?php

namespace Annotation\Routing\Attributes;

use Annotation\Route\Concerns\Arrayable;
use Annotation\Route\Contracts\RoutingContract;
use Attribute;
use Override;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Metadata extends Arrayable implements RoutingContract
{
    public function __construct(
        public string $key,
        public string $value,
    ) {
        //
    }

    public function setValue(string $value): static
    {
        $this->value = $value;
        return $this;
    }

    #[Override]
    public function toArray(): array
    {
        return [
            $this->key => $this->value,
        ];
    }
}
