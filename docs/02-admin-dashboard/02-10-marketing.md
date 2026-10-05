# Marketing

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

Admin manages campaigns, subscribers, email/SMS/WhatsApp broadcasts,
popups, push, SEO utilities and GTM/pixels. Sending is async and
consent-aware.

``` ts
interface Campaign { id:string; channel:'email'|'sms'|'whatsapp'|'push'; name:string; segmentId?:string; status:'draft'|'scheduled'|'sending'|'sent'|'cancelled'; scheduledAt?:string; content:unknown }
```

``` sql
CREATE TABLE marketing_campaigns(id uuid PRIMARY KEY,channel text NOT NULL,name text NOT NULL,segment_id uuid,status text NOT NULL,scheduled_at timestamptz,content jsonb NOT NULL);
```

REST: `/api/v1/marketing/campaigns`, `/subscribers`, `/templates`,
`/popups`, `/pixels`; `POST /campaigns/:id/schedule`, `/send-test`.

Edge cases: suppress unsubscribed contacts; provider throttling retries
with backoff; WhatsApp templates must be provider-approved where
required. Permissions: `marketing.campaigns.manage|send`,
`marketing.subscribers.read`, `marketing.tracking.manage`.

### Storefront impact

Publishes popup/tracking configuration and SEO metadata where
applicable.

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
