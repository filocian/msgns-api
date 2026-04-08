# msgns API

Backend REST API for the msgns platform. Built with Laravel 12 and PHP 8.4, following a vertical slice architecture (S³ Lite) with CQRS and clean dependency boundaries.

All modern code lives under `src/`. The `app/` directory contains legacy code that is being progressively retired.

---

## Architecture

The API is organized into vertical slices, each owning its domain logic, application handlers, infrastructure adapters, and HTTP layer:

```
src/
├── Identity/    — authentication, users, roles, permissions
├── Products/    — product management and configuration
└── Places/      — location and Google Places integration
```

Routes are versioned under `/api/v2/`. Each slice registers its own routes and service providers via `bootstrap/providers.php`.

---

## Permissions

### How permissions work

Permissions are **code-defined** — they live in source code, not in the database. The canonical source of truth is:

```
src/Identity/Domain/Permissions/DomainPermissions.php
```

This class holds every permission as a named constant and exposes two static methods:

- `DomainPermissions::all()` — returns the full list of permission name strings
- `DomainPermissions::descriptions()` — returns a map of `permission_name => human-readable description`

The database is a **projection** of this code. It is kept in sync by the `rbac:reconcile` artisan command, which reads the domain and creates or removes permissions and role assignments as needed.

### How roles work

Roles are defined in `DomainRoles.php` and their default permission sets in `DomainRolePermissions.php`. Three roles are **core** and cannot be deleted or have their permissions changed via the API:

| Role | Description |
|---|---|
| `developer` | Full access to everything |
| `backoffice` | Full access to everything |
| `user` | Standard end-user access |

Additional roles (`designer`, `marketing`, and any custom role created via the API) are non-core and fully manageable.

### Adding a new permission

1. Add a constant to `DomainPermissions.php`:

```php
const MY_NEW_PERMISSION = 'my_new_permission';
```

2. Add its description to the `descriptions()` method in the same file:

```php
'my_new_permission' => 'Allows doing X in the admin panel.',
```

3. Assign it to the roles that should have it by default in `DomainRolePermissions.php`. If it should be available to all admin roles, add it to the `developer` and `backoffice` arrays (or use `DomainPermissions::all()` if those roles already use it).

4. Run `rbac:reconcile` as part of your deploy (see below). That's it — no migration, no manual DB step.

> Legacy code (`app/Static/Permissions/StaticPermissions.php`) automatically picks up any permission added to `DomainPermissions` — it is a thin adapter that delegates to the domain class. You do not need to touch legacy.

### rbac:reconcile

```bash
php artisan rbac:reconcile
```

Syncs the permission and role catalog from code to the database. It is:

- **Idempotent** — safe to run multiple times, produces the same result.
- **Non-destructive for custom roles** — only core roles and their default permission sets are reconciled. Custom roles created via the API are left untouched.

**Deploy ordering** — reconcile must run after `migrate` and before the web server reloads:

```bash
php artisan migrate --force
php artisan rbac:reconcile
# reload web server
```

This matters because the permission-based middleware (`permission:manage_roles_and_permissions`) checks the database. If reconcile has not run yet, the new permission does not exist in the DB and the middleware will return 403 until it does.

### Admin API endpoints

All endpoints below require the `manage_roles_and_permissions` permission. By default this is assigned to the `developer` and `backoffice` roles. Any custom role can also be granted this permission via the API.

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v2/identity/admin/roles` | List all roles |
| `POST` | `/api/v2/identity/admin/roles` | Create a custom role |
| `GET` | `/api/v2/identity/admin/roles/{id}` | Get a single role |
| `PATCH` | `/api/v2/identity/admin/roles/{id}` | Rename a role |
| `DELETE` | `/api/v2/identity/admin/roles/{id}` | Delete a custom role |
| `PUT` | `/api/v2/identity/admin/roles/{id}/permissions` | Sync permissions on a role (full replace) |
| `GET` | `/api/v2/identity/admin/permissions` | List all permissions with descriptions |

Core roles (`developer`, `backoffice`, `user`) are protected — delete and permission sync return 403 for them.

---

## Running tests

```bash
# Modern suite only (recommended)
php artisan test --group=modern

# Full suite
php artisan test
```

Static analysis:

```bash
./vendor/bin/phpstan analyse src/ --memory-limit=512M
```
