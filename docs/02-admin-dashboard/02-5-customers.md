# Customers

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

Profiles include identity, contacts, addresses, consent, groups,
segments, order history, LTV, wishlist and abandoned-cart summaries.
Kenya DPA workflows support access/export and deletion/anonymization
subject to legal retention.

``` ts
interface Customer { id:string; email?:string; phone?:string; firstName:string; lastName:string; groupId?:string; status:'active'|'blocked'; marketingConsent:boolean; createdAt:string }
interface PrivacyRequest { id:string; customerId:string; type:'export'|'delete'; status:'open'|'verified'|'processing'|'completed'|'rejected' }
```

``` sql
CREATE TABLE customers(id uuid PRIMARY KEY,email citext UNIQUE,phone text,first_name text,last_name text,group_id uuid,status text NOT NULL,marketing_consent boolean DEFAULT false,created_at timestamptz DEFAULT now());
CREATE TABLE privacy_requests(id uuid PRIMARY KEY,customer_id uuid NOT NULL,type text NOT NULL,status text NOT NULL,created_at timestamptz DEFAULT now());
```

### Screens/actions

Customer grid/detail, groups, dynamic segments, addresses, orders,
notes, privacy requests, abandoned carts. Bulk: assign group, tag,
export (permissioned), marketing opt-out.

### REST

`/api/v1/customers`, `/customers/:id/addresses`, `/groups`, `/segments`,
`/privacy-requests`, `/customers/:id/export`,
`/customers/:id/anonymize`.

### Edge cases

Duplicate identities require merge workflow; never auto-merge on phone
alone. Deletion anonymizes records that must be retained for tax/fraud
obligations. Consent changes are timestamped and source-attributed.

### Permissions

`customers.customers.read|update|export`, `customers.segments.manage`,
`customers.privacy.manage`.

### Storefront impact

Customer/account APIs later consume profile, group and consent data.

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
