# Promotions

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

Supports coupons, cart/catalog price rules, scheduled specials,
automatic discounts, combo offers, tiers, flash sales, gift cards and
loyalty/referral.

``` ts
interface PromotionRule { id:string; name:string; type:'cart'|'catalog'|'combo'; priority:number; stackable:boolean; conditions:unknown; actions:unknown; startsAt?:string; endsAt?:string; usageLimit?:number }
```

``` sql
CREATE TABLE promotion_rules(id uuid PRIMARY KEY,name text NOT NULL,type text NOT NULL,priority int NOT NULL,stackable boolean DEFAULT false,conditions jsonb NOT NULL,actions jsonb NOT NULL,starts_at timestamptz,ends_at timestamptz,status text NOT NULL);
```

### REST

CRUD `/api/v1/promotions/rules`, `/coupons`, `/campaigns`,
`/gift-cards`; `POST /promotions/simulate`; `/rules/:id/activate`.

### Attribute dependency

Conditions reference immutable attribute codes resolved through
Attribute Engine. Promotion module may read definitions but cannot
mutate them.

### Conflict policy

Evaluate exclusions first, then non-stackable rules by priority, then
stackable rules. Persist applied rule IDs and discount allocation on
order lines. Flash-sale activation is queued and cache-invalidation
aware.

### Permissions

`promotions.rules.manage`, `promotions.coupons.manage`,
`promotions.gift_cards.manage`, `promotions.publish`.

### Storefront impact

Pricing/cart services consume active compiled rules and badges.

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
