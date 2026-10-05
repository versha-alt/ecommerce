# Product Module

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

## Purpose and personas

Catalog managers own products, categories, brands, reviews/Q&A and
imports. Marketing can manage merchandising fields where granted.
Warehouse users receive read-only product identity plus
inventory-specific permissions.

## Product grid

Search by name/SKU/model/barcode. Filters: status, type, attribute set,
category, brand, stock state, price range, visibility, updated date.
Sorting, column chooser, saved views and expandable variable-parent rows
are server-side. Bulk actions: enable/disable, soft-delete, price
update, category assignment, attribute-set change, export.

## Product types

Only: - `SIMPLE`: one sellable SKU with price/stock. - `VARIABLE`:
non-sellable parent merchandising record whose children are full
`SIMPLE` records.

``` ts
interface Product {
 id:string; type:'SIMPLE'|'VARIABLE'; sku:string; title:string; slug:string;
 status:'draft'|'active'|'disabled'; attributeSetId:string; parentId?:string;
 brandId?:string; price:string; specialPrice?:string; specialFrom?:string; specialTo?:string;
 cost?:string; taxClass:'STANDARD_16'|'ZERO_RATED'|'EXEMPT';
 attributeValues:Record<string,unknown>; visibility:'catalog_search'|'catalog'|'search'|'none';
 version:number;
}
interface ProductVariantLink { parentId:string; childId:string; variationValues:Record<string,string> }
```

``` sql
CREATE TABLE products (
 id uuid PRIMARY KEY, type text NOT NULL CHECK(type IN ('SIMPLE','VARIABLE')),
 parent_id uuid REFERENCES products(id), sku text UNIQUE NOT NULL, title text NOT NULL,
 slug text UNIQUE NOT NULL, status text NOT NULL, attribute_set_id uuid NOT NULL,
 brand_id uuid, price numeric(14,2) NOT NULL DEFAULT 0, special_price numeric(14,2),
 special_from timestamptz, special_to timestamptz, cost numeric(14,2),
 tax_class text NOT NULL DEFAULT 'STANDARD_16', attribute_values jsonb NOT NULL DEFAULT '{}',
 visibility text NOT NULL, version int NOT NULL DEFAULT 1, created_at timestamptz DEFAULT now()
);
CREATE INDEX product_attr_gin ON products USING gin(attribute_values);
CREATE INDEX product_parent_idx ON products(parent_id);
```

## Variable-product flow

1.  Create parent with type and Attribute Set.
2.  Select attributes flagged `useForVariations`.
3.  Only required, global dropdown or swatch attributes are eligible.
4.  Select option values.
5.  Matrix generator calculates Cartesian combinations and previews
    count.
6.  Admin generates child Simple records; SKU template may use parent
    SKU + option codes.
7.  Each child owns SKU, price, special price, stock, barcode, images
    and status.

Parent provides inherited defaults for
descriptions/media/shipping/warranty where child field is unset. Child
explicit values win. Variation-defining values never inherit. Storefront
later renders dropdown/swatch selectors, aggregates salable child stock,
and displays exact selected price; before selection it may show price
range or `From KES X`.

## Combos without a third type

**Option A --- recommended:** a Simple sellable product plus
`combo_components(product_id, component_product_id, qty, price_allocation)`.
This keeps only two product types and makes the combo a commercial SKU.
**Option B:** virtual promotion assembling independent products. Easier
pricing experimentation but weaker order/warranty/returns identity.
**Recommendation:** Option A. Persist component snapshot and price
allocation on order lines. Inventory reserves/deducts each component per
location. If any required component is unavailable, combo is unavailable
unless explicitly configured for split fulfilment. Warranty/serial
capture and returns remain component-level. "Was" is sum of component
reference prices; "Now" is combo sell price.

``` sql
CREATE TABLE combo_components(
 product_id uuid REFERENCES products(id), component_product_id uuid REFERENCES products(id),
 quantity numeric(12,3) NOT NULL CHECK(quantity>0), price_allocation numeric(14,2),
 PRIMARY KEY(product_id,component_product_id)
);
```

## Pricing

Regular, scheduled special, cost, customer/group tier prices, tax class
and computed discount %. Badges (`Sale`, `New Arrival`, `Best Seller`,
`Black Friday`, custom) can be manual or rule-driven with schedule.
Historical order prices are snapshots and never recalculated.

## Create/edit

Step 1 selects product type + attribute set. Step 2 fetches
`/api/v1/attribute-sets/:id/form-schema`. Default sections: title,
SKU/model, slug, status, descriptions, price, media, categories, brand,
inventory, SEO, shipping/weight/dimensions/HS code, warranty,
visibility, tax, related/cross-sell/up-sell and scheduling.

## Categories

Lazy drag/drop tree supports 3+ levels. Fields: name, URL key,
description, image, icon, status, include-in-menu, display mode, SEO,
manual/rule assignment, sorting, layered-navigation config and mega-menu
columns/promo tiles/brand logos. A category has exactly one canonical
URL; old paths create explicit redirects.

## Brands

CRUD: name, slug, logo, banner, description, status, SEO and landing
configuration. Bulk product assignment and shop-by-brand blocks are
supported.

## Import/export

CSV/XLSX uploads create async jobs. Validation occurs before commit;
admin receives row/column error report. Imports support create/update by
SKU, dry-run, attribute option resolution, media references and job
history. Export respects field/branch permissions.

## Reviews/Q&A, history and clone

Moderation states: pending/approved/rejected. Product versions store
meaningful catalog snapshots. Clone creates a new draft with new
SKU/slug required and no inventory/history.

## REST

-   `GET/POST /api/v1/products`
-   `GET/PATCH/DELETE /api/v1/products/:id`
-   `POST /api/v1/products/:id/clone`
-   `POST /api/v1/products/:id/variants/generate`
-   `GET/POST /api/v1/categories`; `PATCH /categories/:id/move`
-   CRUD `/api/v1/brands`
-   `/api/v1/catalog-imports`, `/catalog-exports`, `/reviews`,
    `/questions`

Create:

``` json
{"type":"SIMPLE","sku":"TV-001","title":"55-inch TV","attributeSetId":"uuid","price":"89999.00","taxClass":"STANDARD_16","attributeValues":{"screen_size":"55"}}
```

Response:

``` json
{"data":{"id":"uuid","sku":"TV-001","version":1}}
```

## Validation / edge cases

SKU and slug unique; special price cannot exceed regular unless policy
explicitly allows; scheduled ranges require end \> start. Set changes
run an orphan-data diff before commit. Variable parents cannot be
assigned stock directly. Deleting a child used in historical orders
soft-disables it. Category cycles are rejected.

## Permissions

`catalog.products.read|create|update|delete|bulk|import|export`,
`catalog.categories.manage`, `catalog.brands.manage`,
`catalog.reviews.moderate`. Attribute permissions are separate.

## Audit

Product create/update/status/set change, variant generation, category
move, brand assignment, import commit, moderation and clone.

## Storefront impact

Product/category/brand mutations enqueue OpenSearch reindex and cache
invalidation. Published catalog data later drives PDP/PLP/search,
selectors, canonical category URLs and merchandising.

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
