# Inventory

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

Purpose: maintain accurate sellable stock across central warehouse and
7+ showrooms.

### Screens/features

Stock by Location, Adjustments, Transfers, Purchase Orders, Suppliers,
Serial/Batch Tracking, Reservations, Low-stock Alerts. Filters: SKU,
product, location, salable/on-hand/reserved, low-stock, serial status.
Bulk: import adjustment, transfer, threshold update, export.

``` ts
interface StockItem { productId:string; locationId:string; onHand:number; reserved:number; salable:number; reorderPoint:number }
interface StockReservation { id:string; orderId:string; productId:string; locationId:string; quantity:number; expiresAt:string }
interface Transfer { id:string; fromLocationId:string; toLocationId:string; status:'draft'|'in_transit'|'received'|'cancelled' }
```

``` sql
CREATE TABLE inventory_stock (
 product_id uuid NOT NULL, location_id uuid NOT NULL,
 on_hand numeric(14,3) NOT NULL DEFAULT 0, reserved numeric(14,3) NOT NULL DEFAULT 0,
 reorder_point numeric(14,3) NOT NULL DEFAULT 0,
 PRIMARY KEY(product_id, location_id), CHECK(on_hand>=0), CHECK(reserved>=0)
);
CREATE TABLE stock_movements (
 id uuid PRIMARY KEY, product_id uuid NOT NULL, location_id uuid NOT NULL,
 quantity numeric(14,3) NOT NULL, reason text NOT NULL, reference_type text,
 reference_id uuid, created_at timestamptz NOT NULL DEFAULT now()
);
```

### REST

`GET /api/v1/inventory/stock`; `POST /adjustments`; `POST /transfers`;
`POST /transfers/:id/ship`; `POST /transfers/:id/receive`;
`POST /reservations`; `DELETE /reservations/:id`; CRUD
`/purchase-orders`, `/suppliers`.

Adjustment request:
`{"productId":"uuid","locationId":"uuid","delta":-1,"reason":"DAMAGE"}`.

### Combo deduction

A combo reserves/deducts each component independently at the selected
fulfilment location. The combo is salable only when every required
component is salable. Cross-location fulfilment is opt-in and must
surface split fulfilment. Serial/warranty remains per component.

### Edge cases

Concurrent checkout reservations use row locking/atomic SQL. Expired
reservations release via queue worker. Negative on-hand requires
privileged override. Receiving transfers is idempotent.

### Permissions

`inventory.stock.read`, `.adjust`, `inventory.transfers.manage`,
`inventory.purchase_orders.manage`, `inventory.suppliers.manage`,
`inventory.serials.manage`.

### Storefront impact

Publishes salable-stock and location-availability events used by
PDP/cart/pickup experiences.

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
