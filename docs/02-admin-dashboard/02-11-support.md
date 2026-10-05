# Support

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

Unified tickets from admin, email/chat/WhatsApp references, return
requests and warranty claims. SLA timers are queue/priority aware.

``` ts
interface Ticket { id:string; number:string; customerId?:string; orderId?:string; channel:string; status:'open'|'pending'|'resolved'|'closed'; priority:string; assigneeId?:string; slaDueAt?:string }
```

``` sql
CREATE TABLE support_tickets(id uuid PRIMARY KEY,number text UNIQUE NOT NULL,customer_id uuid,order_id uuid,channel text,status text NOT NULL,priority text NOT NULL,assignee_id uuid,sla_due_at timestamptz);
```

REST: `/api/v1/support/tickets`, `/canned-responses`,
`/warranty-claims`; assign, reply, resolve and validate-warranty
actions.

Warranty validates serial, product, purchase/order, coverage policy and
prior claims. Permissions: `support.tickets.read|reply|assign`,
`support.warranty.manage`, `support.returns.manage`.

### Storefront impact

Future customer support/claim forms create records through controlled
public APIs.

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
