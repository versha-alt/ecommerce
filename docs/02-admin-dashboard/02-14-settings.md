# Settings

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

Central configuration for General, Payment Gateways, Shipping Providers,
Tax/eTIMS, Email/SMS, API Keys, Integrations, Webhooks, Storage/CDN,
Search, Cache, Queue monitoring, Security, Notifications, Localization,
Feature Flags, Backup/Maintenance, Theme and Developer tools.

``` ts
interface SecretSetting { key:string; configured:boolean; maskedValue:string; updatedAt:string }
interface ApiKey { id:string; name:string; scopes:string[]; ipAllowlist:string[]; lastUsedAt?:string; expiresAt?:string }
```

``` sql
CREATE TABLE settings(key text PRIMARY KEY,value jsonb,encrypted_value bytea,updated_at timestamptz DEFAULT now());
CREATE TABLE api_keys(id uuid PRIMARY KEY,name text NOT NULL,key_hash bytea NOT NULL,scopes text[] NOT NULL,ip_allowlist inet[],expires_at timestamptz,last_used_at timestamptz);
```

### Secrets

Credentials are envelope-encrypted at rest using KMS-managed keys, never
returned after creation, masked in UI, excluded from audit payloads and
logs. Rotation supports overlap where provider permits.

### Webhooks

Subscriptions include event, URL, signing secret, status and retry
policy. Delivery logs retain status/latency/attempt count; replay
creates a new signed attempt.

### REST

`GET/PATCH /api/v1/settings/:section`; `/api-keys`; `/integrations`;
`/webhooks`; `/webhooks/:id/test`; `/webhook-deliveries/:id/replay`;
`/feature-flags`; `/queue-monitor`.

### Permissions

Separate `settings.<section>.read|manage`; secret reveal is not
supported. API key creation returns plaintext exactly once.

### Storefront impact

Feature flags, localization, search, payment/shipping enablement and
public integration config may alter storefront behavior through
versioned configuration APIs.

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
