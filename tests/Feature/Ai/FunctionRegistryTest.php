<?php

namespace Tests\Feature\Ai;

use App\Services\Ai\FunctionDefinitions;
use App\Services\Ai\FunctionRegistry;
use App\Services\Ai\UnknownAiFunctionException;
use Tests\TestCase;

class FunctionRegistryTest extends TestCase
{
    public function test_resolves_every_declared_function_name(): void
    {
        $registry = app(FunctionRegistry::class);

        foreach (FunctionDefinitions::names() as $name) {
            $this->assertSame($name, $registry->resolve($name)->name());
        }
    }

    public function test_throws_for_unknown_function_name(): void
    {
        $this->expectException(UnknownAiFunctionException::class);

        app(FunctionRegistry::class)->resolve('tidak_ada');
    }
}
