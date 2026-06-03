<?php

namespace App;

class Greeter
{
    public function sayHello(string $name): string
    {
        return "Hello {$name}";
    }
}
