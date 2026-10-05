# Shipping & Logistics

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

Models own fleet and carriers, zones, rates, free-delivery/install
eligibility, paid installation, pickup, POD, labels, tracking and SLA.

``` ts
interface ShippingZone { id:string; name:string; counties:string[]; postalPatterns?:string[]; slaHours:number }
interface RateRule { id:string; zoneId:string; method:string; priority:number; conditions:unknown; price:string; free:boolean }
```

``` sql
CREATE TABLE shipping_zones(id uuid PRIMARY KEY,name text NOT NULL,definition jsonb NOT NULL,sla_hours int NOT NULL,status text NOT NULL);
CREATE TABLE shipping_rate_rules(id uuid PRIMARY KEY,zone_id uuid REFERENCES shipping_zones(id),method text NOT NULL,priority int NOT NULL,conditions jsonb NOT NULL,price numeric(14,2),free boolean DEFAULT false);
```

### REST

CRUD `/api/v1/shipping/zones`, `/rate-rules`, `/carriers`,
`/pickup-locations`, `/installation-options`; `POST /shipping/quote`;
tracking endpoints.

### Rule evaluation

Rules may use destination zone, category, product flags, order subtotal,
weight, installation eligibility and branch stock. Highest-priority
matching explicit rule wins; admin simulator explains the match.

### Edge cases

Unsupported postcode/address falls back to manual quote only when
enabled. Free installation never silently overrides products requiring
specialist paid installation. Pickup capacity can be throttled by
location/day.

### Permissions

`shipping.zones.manage`, `shipping.rates.manage`,
`shipping.carriers.manage`, `shipping.installation.manage`.

### Storefront impact

Checkout consumes quote and installation/pickup option APIs.

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
