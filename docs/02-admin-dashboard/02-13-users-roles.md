# Users & Roles

## Assumptions

-   Admin-only scope. Storefront implementation is excluded; only admin
    configuration and explicit storefront contracts/impact are
    documented.
-   Single Kenyan retailer, multi-brand, omnichannel, English-first and
    Swahili-ready.
-   Currency is KES; prices are VAT-inclusive by default. VAT supports
    16%, zero-rated and exempt classes.
-   Scale baseline: 5--10K active SKUs at launch, 300--500 orders/day
    with 3--5× peaks; architecture must remain viable toward 100K+
    products and 1M+ customers.
-   API prefix is `/api/v1`; IDs are UUIDs; timestamps are UTC ISO-8601
    and rendered in `Africa/Nairobi`.
-   Backend is NestJS + TypeScript; PostgreSQL is system of record;
    Redis provides cache/queues; OpenSearch provides catalog/admin
    search and facets.
-   Admin frontend is React + TypeScript + Vite, TanStack React Query,
    React Hook Form + Zod, and MUI as the recommended component library.
-   Every mutation writes an immutable audit event containing actor,
    action, entity, before/after summary, IP, user agent and correlation
    ID.

Granular RBAC supports module/action permissions, branch scopes, 2FA,
SSO-ready identity, sessions and audit.

``` ts
interface Role { id:string; name:string; permissionCodes:string[]; branchScope:'all'|'selected'; branchIds:string[] }
interface AdminUser { id:string; email:string; status:'active'|'disabled'; roleIds:string[]; twoFactorEnabled:boolean }
```

``` sql
CREATE TABLE admin_users(id uuid PRIMARY KEY,email citext UNIQUE NOT NULL,status text NOT NULL,two_factor_enabled boolean DEFAULT false);
CREATE TABLE roles(id uuid PRIMARY KEY,name text UNIQUE NOT NULL);
CREATE TABLE permissions(code text PRIMARY KEY,description text NOT NULL);
CREATE TABLE role_permissions(role_id uuid REFERENCES roles(id),permission_code text REFERENCES permissions(code),PRIMARY KEY(role_id,permission_code));
```

### Required separation

`catalog.products.manage` never implies `catalog.attributes.manage`.
Categories, Brands, CMS and Store Locator are independently
permissioned. Branch scope is enforced in repository/service queries,
not only UI filters.

### REST

`/api/v1/admin-users`, `/roles`, `/permissions`, `/sessions`;
`POST /admin-users/:id/reset-2fa`; `DELETE /sessions/:id`.

### Security

2FA required for privileged roles; SSO can be enforced by role. Session
revocation is immediate via Redis/session store. Role edits affecting
current users are audited.

### Storefront impact

None.

## Open questions

1.  Which ERP/accounting platform, if any, is authoritative for stock,
    cost and invoices?
2.  Which KRA eTIMS integration mode/provider will be contracted?
3.  Are branch managers allowed to view cross-branch customer/order
    data?
4.  Which payment aggregator and BNPL providers are confirmed for
    launch?

## Related files

See [README](../README.md), [Navigation & Admin
UX](00-navigation-and-admin-ux.md), [Users &
Roles](02-13-users-roles.md), and [Settings](02-14-settings.md).

## Standard list/filter contract

List endpoints support `page`, `pageSize`, `sort`, `q` and
module-specific filters. Unknown filters return `400`. Exporting the
current filter creates an async job. Saved views are user-owned unless
explicitly shared by an administrator.

## Standard mutation response

``` json
{"data":{"id":"uuid","version":2},"meta":{"correlationId":"req_..."}}
```

Validation failures use HTTP 422; authorization uses 403; optimistic
version conflict uses 409; missing resource uses 404. External provider
failures map to stable internal error codes and retain provider
correlation IDs without leaking secrets.

## Observability

Every request carries a correlation ID. Metrics include latency/error
rate, queue lag and provider failure counts. Domain events use an outbox
transaction so database state and event publication cannot silently
diverge.
