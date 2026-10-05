# Payments & Finance

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

Supports M-Pesa STK Push/Daraja, cards via aggregator, optional
Stripe/PayPal, bank transfer, POD and BNPL. Finance owns transaction
truth, reconciliation, settlements, refunds, disputes, tax and eTIMS
integration state.

``` ts
interface PaymentTransaction { id:string; orderId:string; provider:string; providerRef?:string; type:'authorize'|'capture'|'refund'|'void'; status:string; amount:string; currency:string; idempotencyKey:string }
interface ReconciliationItem { id:string; provider:string; externalRef:string; internalTransactionId?:string; status:'matched'|'unmatched'|'mismatch' }
```

``` sql
CREATE TABLE payment_transactions(id uuid PRIMARY KEY,order_id uuid NOT NULL,provider text NOT NULL,provider_ref text,type text NOT NULL,status text NOT NULL,amount numeric(14,2) NOT NULL,currency char(3) NOT NULL,idempotency_key text UNIQUE NOT NULL,raw_response jsonb,created_at timestamptz DEFAULT now());
```

### REST

`/api/v1/payments/transactions`, `/refunds`, `/settlements`,
`/reconciliation/import`, `/reconciliation/:id/match`, `/disputes`,
`/invoices`, `/tax-rules`.

### M-Pesa

STK request creates pending transaction; signed/provider callback
finalizes it. Timeout is not treated as failure until status
query/reconciliation resolves it. Manual confirmation requires finance
permission, reason and evidence reference.

### VAT/eTIMS

Line tax class is snapshotted on the order. Tax-inclusive formula
derives net/tax; zero/exempt reasons are retained. eTIMS
requests/responses are immutable compliance records with retry state.

### Permissions

`finance.transactions.read`, `finance.refunds.manage`,
`finance.reconciliation.manage`, `finance.tax.manage`,
`finance.etims.manage`.

### Storefront impact

Checkout later consumes enabled payment methods and payment-status
contracts.

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
