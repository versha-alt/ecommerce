# Dashboard / Analytics

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

KPIs: gross/net sales, orders, AOV, units, refunds, conversion, payment
success, fulfilment SLA, low-stock SKUs, top products/categories/brands
and per-store performance. Filters: date range, branch/location,
channel, brand/category. Real-time activity uses event-fed counters;
historical charts query reporting projections, not transactional tables.

### Screens and features

Overview, Sales, Product Performance, Store Performance, Real-time
Activity. Widgets can be reordered per user. Exports are asynchronous.

### Core entity

``` ts
interface DashboardQuery { from:string; to:string; branchIds?:string[]; channel?:string }
interface MetricPoint { bucket:string; value:number; previousValue?:number }
```

``` sql
CREATE MATERIALIZED VIEW daily_sales_metrics AS
SELECT date_trunc('day', created_at) day, branch_id,
       count(*) orders, sum(grand_total) gross_sales
FROM orders WHERE status <> 'cancelled' GROUP BY 1,2;
```

### REST

`GET /api/v1/dashboard/summary`, `/sales-trend`, `/top-products`,
`/store-performance`, `/activity`. Response:
`{"data":{"grossSales":"1250000.00","orders":412,"aov":"3033.98"}}`.

### Validation / edge cases

Timezone boundaries use Africa/Nairobi. Late refunds/payment callbacks
update affected reporting buckets. Currency conversions are never
silently mixed.

### Permissions

`analytics.dashboard.read`, `analytics.sales.read`,
`analytics.store.read`, `analytics.export`.

### Audit

Dashboard reads are not individually audited; exports and saved-view
changes are.

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
