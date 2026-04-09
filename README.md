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

## V2 Activation Flag

The API serves both the legacy system and the new V2 simultaneously. An environment flag controls which version handles the shared product entry points, allowing a safe rollback to legacy at any time without a code deployment.

### How it works

Product redirection has two types of entry points:

| URL | Description |
|---|---|
| `/nfc/{data}` | NFC scan — URL segment contains product ID and password |
| `/product/{id}/redirect/{password}` | Direct product redirect — typed params |

When `APP_V2_ENABLED=false` (the default), both URLs are handled by the **legacy** `RedirectionController`. When `APP_V2_ENABLED=true`, both URLs are handled by the **V2** `ProductRedirectionController`, which uses the vertical slice architecture under `src/Products/`.

The V2-specific route `/v2/product/{id}/redirect/{password}` always points to the V2 handler regardless of the flag. It is not affected by this toggle.

### Environment variables

| Variable | Default | Description |
|---|---|---|
| `APP_V2_ENABLED` | `false` | Master toggle. Set to `true` to activate V2 routing. |
| `FRONT_URL` | — | Base URL of the **legacy** frontend app. Used when `APP_V2_ENABLED=false`. |
| `FRONT_V2_URL` | — | Base URL of the **V2** frontend app. Used when `APP_V2_ENABLED=true`. |

Both frontend variables are required in production. `FRONT_V2_URL` is also read by `ResolveProductRedirectionHandler` when V2 is active — it builds the stepper and disabled product URLs against this base.

### Activating V2

1. Set the two variables in your environment:

```
APP_V2_ENABLED=true
FRONT_V2_URL=https://your-v2-frontend.example.com
```

2. Clear the config cache so the new values take effect (required if config is cached):

```bash
php artisan config:clear
php artisan config:cache
```

Laravel evaluates the routing condition at boot time from the cached config. Without clearing the cache, a previously cached `false` value will keep routing to legacy even after the env change.

### Deactivating V2 (rollback)

1. Set `APP_V2_ENABLED=false` in your environment.
2. Clear and re-cache config:

```bash
php artisan config:clear
php artisan config:cache
```

Routing returns to legacy immediately on the next boot. No code change or deployment is required.

### What changes when the flag is on

**Routing (`routes/web.php`)**: The two shared entry points are registered at application boot with different controller targets depending on the flag. The entire switch is a single `if/else` block — there is no runtime branching per request.

**NFC handling**: The V2 controller includes a `nfcRedirect()` method that replicates the legacy NFC URL parsing logic (`?psw=` query param and `&psw=` segment formats). Unparseable NFC data returns 404, matching legacy behaviour.

**Frontend URLs**: `ResolveProductRedirectionHandler` receives `FRONT_V2_URL` (instead of `FRONT_URL`) via dependency injection when V2 is active. This means all frontend redirects — the product stepper, the disabled product page — point to the V2 frontend automatically.

**Legacy is untouched**: When the flag is off, no V2 code runs in the redirection path. The legacy `RedirectionController` and its use case class remain the sole handlers.

### Config keys

| Config key | Env variable | Where used |
|---|---|---|
| `app.v2_enabled` | `APP_V2_ENABLED` | Route registration condition in `routes/web.php` |
| `services.products.front_url` | `FRONT_URL` | Injected into `ResolveProductRedirectionHandler` when V2 is off |
| `services.products.v2_front_url` | `FRONT_V2_URL` | Injected into `ResolveProductRedirectionHandler` when V2 is on |

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
