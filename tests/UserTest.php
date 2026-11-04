<?php

namespace Tests;

use App\User;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserUsesTheInstalledEloquentModel(): void
    {
        $user = new User(['name' => 'Diana', 'email' => 'diana@example.com']);
        self::assertInstanceOf(Model::class, $user);
        self::assertSame('Diana', $user->name);
        self::assertSame('diana@example.com', $user->email);
        self::assertSame('default', $user->getConnectionName());
    }
}
