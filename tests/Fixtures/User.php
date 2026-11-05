<?php

namespace Zuber\Kit\Tests\Fixtures;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Zuber\Kit\Users\Contracts\HasRolesInterface;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
final class User extends Authenticatable implements FilamentUser, HasRolesInterface
{
    use HasRoles;

    protected $table = 'users';

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->checkPermissionTo(PermissionEnum::ACCESS_ADMIN);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
