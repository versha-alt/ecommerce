# E-commerce Admin Dashboard --- Architecture & Implementation Documentation

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

## Purpose

This suite specifies the centralized back-office for catalog, inventory,
orders, customers, finance, logistics, promotions, CMS, marketing,
support, reporting, security and platform configuration. The storefront
is intentionally out of scope.

## Reading order

1.  `00-navigation-and-admin-ux.md`
2.  `02-2d-attribute-engine.md` before `02-2-product-module.md`
3.  `02-3-inventory.md` → `02-4-orders.md` → `02-6-payments-finance.md`
    → `02-7-shipping.md`
4.  `02-8-promotions.md` and `02-9-cms.md`
5.  Customer, marketing, support, reports, RBAC and settings modules.

## Architecture decisions

  ----------------------------------------------------------------------------
  Concern                 Decision                Reason
  ----------------------- ----------------------- ----------------------------
  Backend                 NestJS REST modular     Strong boundaries and DI
                          monolith initially      without premature
                                                  distributed-system cost

  Primary DB              PostgreSQL              Transactions, reporting,
                                                  referential integrity, JSONB
                                                  flexibility

  Attribute values        PostgreSQL relational   Easier than pure Magento EAV
                          core + JSONB/hybrid     while retaining dynamic
                          projections             schemas

  Search/facets           OpenSearch              Fast full-text, facets,
                                                  admin search and scalable
                                                  filtering

  Cache/jobs              Redis + BullMQ          Definition/schema cache,
                                                  reservations, imports, async
                                                  integration work

  Admin UI                React/Vite + MUI        Mature enterprise
                                                  grids/forms/accessibility;
                                                  easy custom design tokens
  ----------------------------------------------------------------------------

## Intentional differences from Magento

This system preserves Magento-like **products, configurable-style
variables, attributes, attribute groups, attribute sets, categories and
CMS concepts**, but does not copy Magento's storage/runtime
architecture. Dynamic attribute definitions are normalized while product
attribute values use a controlled JSONB/hybrid model, reducing EAV
joins. Only `Simple` and `Variable` product types exist; combos are
modeled as a sellable simple product plus component relations. APIs are
explicit REST contracts rather than Magento service contracts.

## Module dependency overview

``` mermaid
graph TD
  Settings --> Products
  Settings --> Inventory
  Settings --> Orders
  AttributeEngine --> Products
  AttributeEngine --> Categories
  AttributeEngine --> Promotions
  AttributeEngine --> Search
  Products --> Inventory
  Products --> Promotions
  Customers --> Orders
  Inventory --> Orders
  Promotions --> Orders
  Shipping --> Orders
  Payments --> Orders
  Orders --> Reports
  CMS --> StorefrontAPI
  Marketing --> Customers
  Support --> Orders
```

**Boundary rule:** Product depends on Attribute Engine; Attribute Engine
never imports Product domain code. Consumers use Attribute APIs/events.

## Global conventions

-   Pagination: `page`, `pageSize` (max 200); list responses return
    `items`, `page`, `pageSize`, `total`.
-   Optimistic concurrency: mutable aggregates expose `version`; update
    requests may send `If-Match`.
-   Idempotency: payment, refund, fulfilment and webhook mutation
    endpoints require `Idempotency-Key`.
-   Error envelope:
    `{"error":{"code":"VALIDATION_ERROR","message":"...","fields":{},"correlationId":"..."}}`.
-   Permissions use `module.resource.action`,
    e.g. `catalog.products.update`.
-   Soft deletion is preferred for business records referenced by
    orders/audit history.
-   Async jobs return `202` with `jobId`; job status is available
    through `/api/v1/jobs/:jobId`.

## Cross-cutting entities

``` ts
export interface AuditEvent {
  id: string; actorUserId?: string; action: string;
  entityType: string; entityId: string; branchId?: string;
  before?: unknown; after?: unknown; correlationId: string;
  ip?: string; userAgent?: string; createdAt: string;
}
export interface Money { currency: 'KES' | string; amount: string }
export interface Page<T> { items: T[]; page: number; pageSize: number; total: number }
```

``` sql
CREATE TABLE audit_events (
  id uuid PRIMARY KEY,
  actor_user_id uuid,
  action text NOT NULL,
  entity_type text NOT NULL,
  entity_id uuid NOT NULL,
  branch_id uuid,
  before_json jsonb,
  after_json jsonb,
  correlation_id text NOT NULL,
  ip inet,
  user_agent text,
  created_at timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX audit_entity_idx ON audit_events(entity_type, entity_id, created_at DESC);
```

## Consistency check

The generated suite uses: `Products` for the navigation module,
`Attribute Engine` for the backend boundary, `/api/v1` for APIs, UUID
identifiers, KES/VAT-inclusive defaults, `Simple|Variable` as the only
product types, and `module.resource.action` permission names. Attribute
changes invalidate Redis schema caches and publish reindex events to
OpenSearch. Order, payment, inventory and fulfilment mutations are
auditable and idempotent where external side effects exist.

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
