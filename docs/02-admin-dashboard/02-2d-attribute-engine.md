# Attribute Engine

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

## Boundary

Attribute Engine owns attribute definitions, options, groups, sets and
form schemas. Product, Category, Search, Promotion, Comparison and
Import/Export consume it. **Dependency direction is consumer → Attribute
Engine only.**

``` mermaid
graph LR
  Product --> AttributeEngine
  Category --> AttributeEngine
  Search --> AttributeEngine
  Promotion --> AttributeEngine
  Comparison --> AttributeEngine
  ImportExport --> AttributeEngine
  AttributeEngine --> PostgreSQL
  AttributeEngine --> Redis
  AttributeEngine --> Events
  Events --> OpenSearch
```

## Attribute definition

``` ts
type AttributeInput = 'text'|'textarea'|'rich_text'|'number'|'decimal'|'boolean'|'date'|'dropdown'|'multi_select'|'swatch_color'|'swatch_image'|'swatch_text'|'media'|'price';
interface AttributeDefinition {
 id:string; code:string; label:string; inputType:AttributeInput; unit?:string;
 scope:'global'|'store_view'; required:boolean; unique:boolean; searchable:boolean;
 filterable:boolean; comparable:boolean; pdpVisible:boolean; promoUsable:boolean;
 sortable:boolean; useForVariations:boolean; system:boolean;
 validation?:Record<string,unknown>; defaultValue?:unknown;
 localizedLabels:Record<string,string>;
}
interface AttributeOption { id:string; attributeId:string; value:string; label:string; sortOrder:number; swatch?:string }
interface AttributeSet { id:string; code:string; name:string; groups:AttributeSetGroup[]; version:number }
```

## PostgreSQL

``` sql
CREATE TABLE attributes(
 id uuid PRIMARY KEY, code text UNIQUE NOT NULL, label text NOT NULL, input_type text NOT NULL,
 unit text, scope text NOT NULL DEFAULT 'global', required boolean DEFAULT false,
 unique_value boolean DEFAULT false, searchable boolean DEFAULT false, filterable boolean DEFAULT false,
 comparable boolean DEFAULT false, pdp_visible boolean DEFAULT false, promo_usable boolean DEFAULT false,
 sortable boolean DEFAULT false, use_for_variations boolean DEFAULT false, system boolean DEFAULT false,
 validation jsonb, default_value jsonb, localized_labels jsonb NOT NULL DEFAULT '{}'
);
CREATE TABLE attribute_options(id uuid PRIMARY KEY,attribute_id uuid REFERENCES attributes(id) ON DELETE CASCADE,value text NOT NULL,label text NOT NULL,sort_order int NOT NULL DEFAULT 0,swatch jsonb,UNIQUE(attribute_id,value));
CREATE TABLE attribute_groups(id uuid PRIMARY KEY,code text UNIQUE NOT NULL,name text NOT NULL,system boolean DEFAULT false);
CREATE TABLE attribute_sets(id uuid PRIMARY KEY,code text UNIQUE NOT NULL,name text NOT NULL,version int NOT NULL DEFAULT 1);
CREATE TABLE attribute_set_members(
 set_id uuid REFERENCES attribute_sets(id),group_id uuid REFERENCES attribute_groups(id),
 attribute_id uuid REFERENCES attributes(id),sort_order int NOT NULL,
 locked boolean DEFAULT false,PRIMARY KEY(set_id,attribute_id)
);
```

## Groups

Defaults: General, Pricing, Media, Specifications, Dimensions, Warranty,
SEO. Groups map to admin form sections and later PDP specification
tables.

## Sets

Templates: Default, Television, Washing Machine, Refrigerator, Air
Conditioner, Small Appliance. Clone copies memberships/order, not
product data.

## Locked Base group

Every set contains non-removable **Base** system attributes: `title`,
`sku`, `model`, `slug`, `status`, `short_description`, `description`,
`price`, `special_price`, `special_from`, `special_to`, `tax_class`,
`brand`, `categories`, `media`, `visibility`, `weight`, `length`,
`width`, `height`, `hs_code`, `warranty_policy`, `seo_title`,
`seo_description`. Membership is locked. Administrators may reorder only
attributes marked `reorderable=true`; identity/system-critical fields
(`title`, `sku`, `status`, `price`) stay fixed.

## Create attribute workflow

Admin defines attribute, then selects one or more target sets and a
destination group per set. Example: `screen_size` → Television /
Specifications only. `capacity_kg` → Washing Machine / Specifications
only. One definition can belong to many sets without duplication.

## Variation constraints

`useForVariations=true` requires: input is dropdown or swatch,
scope=`global`, required=`true`, options \>= 1. Multi-select/text/number
cannot define variant matrices.

## Set change and orphaned values

Before product set change, API returns a diff. Values whose attributes
are absent in target set default to **retain hidden** so rollback/data
recovery is safe. Admin may explicitly discard them with
`catalog.attributes.discard_orphans`. Hidden orphan values are excluded
from forms, storefront projections, facets and promotions.

## Delete-in-use

System attributes cannot be deleted. User attributes referenced by
sets/products/rules are blocked by default. "Unassign and retire"
requires impact preview + confirmation; definition becomes retired until
references are migrated. Hard deletion is allowed only when no
references/data remain.

## Storage decision

-   Pure EAV: maximum dynamism, expensive joins and operational
    complexity.
-   Pure JSONB: simple writes, weaker governance/unique constraints and
    harder selective indexing.
-   **Recommended hybrid:** normalized definitions/options/sets + JSONB
    product values + generated/side indexes for high-value
    filterables/uniques. OpenSearch is the primary faceting/search
    projection.

For heavily filtered attributes, maintain typed projection rows:

``` sql
CREATE TABLE product_attribute_index(
 product_id uuid NOT NULL, attribute_id uuid NOT NULL,
 text_value text, numeric_value numeric, option_id uuid,
 PRIMARY KEY(product_id,attribute_id,option_id)
);
CREATE INDEX pai_numeric ON product_attribute_index(attribute_id,numeric_value);
CREATE INDEX pai_option ON product_attribute_index(attribute_id,option_id);
```

## Form-schema API

`GET /api/v1/attribute-sets/:id/form-schema?locale=en`

``` json
{"data":{"setId":"uuid","version":7,"groups":[{"code":"specifications","label":"Specifications","fields":[{"code":"screen_size","label":"Screen Size","input":"dropdown","required":true,"unit":"inch","options":[{"value":"55","label":"55"}]}]}]}}
```

The frontend renders fields from schema; Zod rules are generated from
server constraints but server validation remains authoritative.

## REST

CRUD `/api/v1/attributes`, `/attribute-groups`, `/attribute-sets`;
`/attributes/:id/options`; `POST /attribute-sets/:id/clone`;
`PUT /attribute-sets/:id/members`;
`POST /attribute-sets/:id/validate-product-change`;
`GET /attribute-sets/:id/form-schema`.

## Caching

Redis keys: `attr:def:<id>:v<n>`, `attr:set:<id>:v<n>`,
`attr:form:<setId>:<locale>:v<n>`. Any definition/option/group/set
mutation increments affected versions, deletes known keys and publishes
`attribute.schema.changed`; consumers re-fetch. No consumer writes cache
keys directly.

## Permissions

`catalog.attributes.read|create|update|retire`,
`catalog.attribute_groups.manage`, `catalog.attribute_sets.manage`,
`catalog.attributes.discard_orphans`.

## Edge cases

Immutable `code` after creation. Option values referenced by products
are retired, not silently removed. Changing type is blocked after values
exist except approved lossless migrations. Unique attributes use typed
index + DB enforcement strategy.

## Audit

Definition/option/group/set create/update/retire, membership reorder,
cache-version change and destructive orphan discard.

## Storefront impact

Definitions drive PDP specifications, comparison, filters, promotion
eligibility and variant selectors through product/search projections;
storefront never reads admin tables directly.

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
