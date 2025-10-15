<?php

namespace tests\oihana\reflect\mocks;

use oihana\reflect\attributes\HydrateWith;

class MockCreativeWork
{
    /**
     * @var array<MockPerson|MockOrganization>|MockPerson|MockOrganization|string|null
     */
    #[HydrateWith( MockPerson::class , MockOrganization::class ) ]
    public array|MockPerson|MockOrganization|string|null $sponsor;
}
