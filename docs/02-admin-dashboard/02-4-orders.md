# Orders

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

Lifecycle:
`draft → pending_payment → paid → processing → partially_fulfilled → fulfilled → completed`;
side paths include `cancelled`, `refunded`, `partially_refunded`,
`on_hold`. Payment and fulfilment are separate state machines.

### Screens/features

Order grid/detail, timeline/notes, fulfilment, partial shipment,
RMA/returns, refunds/exchanges, cancellation, draft orders,
quote-to-order, eTIMS invoice, delivery/installation scheduling,
warranty registration, pickup and Pay on Delivery.

``` ts
interface Order { id:string; number:string; customerId?:string; status:string; paymentStatus:string; fulfilmentStatus:string; currency:'KES'; subtotal:string; taxTotal:string; grandTotal:string; branchId?:string; version:number }
interface OrderItem { id:string; orderId:string; productId:string; sku:string; name:string; quantity:number; unitPrice:string; taxAmount:string; comboParentItemId?:string }
```

``` sql
CREATE TABLE orders (
 id uuid PRIMARY KEY, number text UNIQUE NOT NULL, customer_id uuid,
 status text NOT NULL, payment_status text NOT NULL, fulfilment_status text NOT NULL,
 currency char(3) NOT NULL DEFAULT 'KES', subtotal numeric(14,2) NOT NULL,
 tax_total numeric(14,2) NOT NULL, grand_total numeric(14,2) NOT NULL,
 branch_id uuid, version int NOT NULL DEFAULT 1, created_at timestamptz DEFAULT now()
);
```

### REST

`GET/POST /api/v1/orders`; `GET/PATCH /orders/:id`;
`POST /orders/:id/cancel`; `/fulfilments`; `/returns`; `/refunds`;
`/exchange`; `/notes`; `/schedule`; `/warranty`; `/etims-invoice`;
`/quotes/:id/convert`.

### Rules/edge cases

Cannot refund above captured amount; cannot ship above unfulfilled
quantity. Single combo components may be returned; pricing allocation is
persisted at order creation to calculate component refund limits. eTIMS
failure does not erase the commercial order; it creates a visible
compliance exception/retry task. POD supports collection status
independent of delivery status.

### Permissions

`orders.orders.read|create|update|cancel`, `orders.fulfilments.manage`,
`orders.refunds.manage`, `orders.returns.manage`,
`orders.quotes.manage`, `orders.etims.manage`.

### Audit

Status transitions, notes, address edits, refund/cancel, fulfilment,
invoice generation and manual payment confirmation.

### Storefront impact

Order status, tracking, pickup, warranty and return eligibility are
exposed through customer-facing APIs later.

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
