---
paths:
  - "app/Policies/**/*.php"
  - "app/Enums/Access/**/*.php"
---

# Access: roles, permissions, policies

- Account roles and permissions — `spatie/laravel-permission` (add it when the site has more than
  one kind of user). **Permissions are code, roles are data.** `App\Enums\Access\PermissionEnum`
  (`HasLabel`, optionally `HasDescription`) lists every permission the code checks: a new section
  or feature that needs its own access adds a case and checks it. Roles are created, renamed,
  deleted and given permissions (checkboxes) in the admin by the site owner.
- `App\Enums\Access\RoleEnum` (the kit's `RoleEnumInterface`) holds only the default roles —
  `super_admin`, `admin`, `manager` — with their starting permissions in `permissions()`. The
  kit's command `php artisan kit:sync-roles` (the shared deploy runs it after the migrations, the
  local `DatabaseSeeder` calls `SyncRolesAction` with `RoleEnum`, `PermissionEnum` and the super
  admin role) creates the permissions of `PermissionEnum`, creates the missing default roles and
  gives the super admin every permission; it never touches the permissions of existing roles —
  those belong to the admin. A default role cannot be renamed or deleted there. No seeder of roles
  in the project. Other enum classes — `config/kit.php` → `access.roles`, `access.permissions`,
  `access.super_admin`.
- A new permission reaches the existing roles only through the admin (the super admin gets it on
  deploy): mention in the report which roles should get it.
- The admin users and roles screens are the kit's `UsersPlugin` (Filament), not the project's
  own resources.
- The first admin on a server (seeders never run there): `php artisan kit:make-user
  owner@site.kz --role=admin` — the generated password is printed once; a second run for the same
  email changes nothing, `--reset-password` issues a new one, `--ask-password` lets the person type
  their own (hidden, confirmed, never printed). Never a seeder with real accounts.
- Policies check **permissions** (`hasPermissionTo`), never role names — roles are renamed and
  created in the admin. A role inside one record
  (owner, participant, assignee) is data of that record, not a spatie role.
- An ability is a case of an enum `App\Enums\Access\…AbilityEnum`, one enum per policy
  (`OrderAbilityEnum`, `DocumentAbilityEnum`); the value is the policy method name. Code and
  templates use only the cases: `$this->authorize(OrderAbilityEnum::CANCEL, $order)`,
  `$user->can(OrderAbilityEnum::PAY, $order)`, `@can(OrderAbilityEnum::CANCEL, $order)`
  (`@use` the enum in Blade). No ability strings anywhere. A new policy method is a new case; a
  test compares the enums with the policies.
- Access to records goes through policies **and** through "records visible to the user" — a
  service rule applied by the repository query. Someone else's record is unreachable by a direct
  id too.
- Tests: every policy method — allowed and refused; every visibility rule — the foreign record is
  absent from lists and answers 403/404 by id.
