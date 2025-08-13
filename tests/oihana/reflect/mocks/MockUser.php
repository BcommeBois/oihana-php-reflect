<?php

namespace tests\oihana\reflect\mocks;

class MockUser
{
    public    int  $age ;
    public string  $name ;
    public ?string $nickname ;

    public ?MockAddress $address = null;

    private int $id = 0;

    public function getName(): string {
        return $this->name;
    }

    public function setName( string $name ): void
    {
        $this->name = $name;
    }

    public function setAge( int $age = 30 ): void
    {
        $this->age = $age;
    }

    public function setNickname( ?string $nickname ): void
    {
        $this->nickname = $nickname;
    }

    public function addTags( ...$tags ): void
    {

    }

    protected function someProtectedMethod():void
    {

    }
}
