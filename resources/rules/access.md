---
paths:
  - "app/Policies/**/*.php"
  - "app/Enums/Access/**/*.php"
  - "database/seeders/RolesAndPermissionsSeeder.php"
---

# Access: roles, permissions, policies

- Account roles and permissions — `spatie/laravel-permission` (add it when the site has more than
  one kind of user): `RoleEnum`, `PermissionEnum`, the permissions of a role in
  `RoleEnum::permissions()`. A `RolesAndPermissionsSeeder` brings the database in line with the
  code and runs on every deploy.
- Policies check **permissions** (`hasPermissionTo`), never role names. A role inside one record
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
