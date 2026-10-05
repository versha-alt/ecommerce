# Navigation and Admin UX

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

## Sidebar hierarchy

-   **Dashboard**
-   **Products**
    -   All Products
    -   Categories
    -   Brands
    -   Attributes
        -   Attributes
        -   Attribute Groups
        -   Attribute Sets
    -   Reviews & Q&A
    -   Import / Export
-   **Inventory**
    -   Stock by Location
    -   Adjustments
    -   Transfers
    -   Purchase Orders
    -   Suppliers
    -   Serial / Batch Tracking
-   **Orders**
    -   All Orders
    -   Draft Orders
    -   Quotes & Enquiries
    -   Returns / RMA
    -   Fulfilments
    -   Delivery & Installation
-   **Customers**
    -   Customers
    -   Segments
    -   Groups
    -   Abandoned Carts
    -   Privacy Requests
-   **Payments & Finance**
    -   Transactions
    -   Refunds
    -   Settlements
    -   Reconciliation
    -   Invoices / eTIMS
    -   Disputes
-   **Shipping & Logistics**
    -   Zones
    -   Rate Rules
    -   Carriers / Fleet
    -   Pickup
    -   Installation
    -   Tracking
-   **Promotions**
    -   Coupons
    -   Cart Rules
    -   Catalog Rules
    -   Campaigns
    -   Combo Offers
    -   Gift Cards
    -   Loyalty / Referral
-   **CMS**
    -   Pages
    -   Blocks / Widgets
    -   Homepage Builder
    -   Navigation / Mega Menu
    -   Store Locator
    -   Services / Enquiries
    -   Blog
    -   Media Library
    -   URL Rewrites
    -   Sitemap / Robots
-   **Marketing**
-   **Support**
-   **Reports**
-   **Users & Roles**
-   **Settings**

## Breadcrumb rules

Breadcrumbs represent route hierarchy, never browser history. Example:
`Products / Attribute Sets / Television / Edit`. IDs are not displayed
when a human-readable label exists.

## Layout

Desktop uses a persistent 256px sidebar, 64px top bar, contextual page
header, content canvas and optional right inspector. Tablet collapses
navigation to a drawer. Destructive or high-risk operations use explicit
confirmation dialogs.

## Design tokens

``` ts
export const tokens = {
  spacing: { xs: 4, sm: 8, md: 16, lg: 24, xl: 32 },
  radius: { sm: 6, md: 10, lg: 14 },
  typography: { fontFamily: 'Inter, system-ui, sans-serif' },
  density: { gridRow: 44, compactGridRow: 36 },
  zIndex: { appBar: 1100, drawer: 1200, modal: 1300, toast: 1400 }
};
```

Use semantic theme colors (`primary`, `success`, `warning`, `error`,
`surface`, `text`) rather than hard-coded feature colors. Dark mode must
preserve WCAG AA contrast.

## Component inventory

`AppShell`, `PageHeader`, `Breadcrumbs`, `DataGrid`, `FilterBar`,
`SavedViews`, `BulkActionBar`, `ColumnChooser`, `FormSection`,
`DynamicAttributeForm`, `MediaPicker`, `CategoryTree`,
`AttributeSetBuilder`, `RuleBuilder`, `RichTextEditor`,
`SchedulePicker`, `StatusChip`, `AuditTimeline`, `MetricCard`,
`ChartPanel`, `JobProgress`, `PermissionGate`, `ConfirmDialog`.

## Grid UX contract

All large grids use server-side filtering/sorting/pagination, saved
views, column persistence per user, export as an async job, row
selection across current page by default, and explicit "select all
matching N records" for cross-page bulk operations.

## Wireframes

### Product grid

`Page header → search/filter/saved views → KPI/status strip → server grid`.
Variable parents have expandable child rows. Columns: image, name, SKU,
type, status, attribute set, brand, categories, price, salable stock,
updated. Bulk actions are permission-aware.

### Product edit

Sticky header: Back, product identity, status, Save, Save & Close.
Left/upper section navigation comes from Attribute Set schema.
Validation summary links directly to invalid fields. Variable products
expose a Variations section with variation-attribute selection and
matrix generation.

### Attribute set builder

Three-pane layout: unassigned attributes \| group tree \| inspector.
System Base group is locked. Drag/drop writes ordered set memberships.
Dirty state is explicit; save validates variation constraints.

### Category tree + mega menu

Left: lazy-loaded category tree with drag/drop. Center: category form.
Right: preview/config for menu columns, promo tiles and brand logos.
Moving a category shows resulting URL/menu impact before save.

### CMS homepage builder

Palette → ordered canvas → inspector. Blocks have device visibility,
schedule, targeting/rule data and preview state. Publishing creates a
version and triggers storefront invalidation.

## Accessibility and performance

Keyboard-accessible drag/drop alternatives are mandatory. Forms announce
errors. Grids virtualize rows where appropriate. Route chunks are
lazy-loaded. React Query keys include tenant/store/branch scope and
filters.

## Permissions

Navigation items are hidden only when the user has no readable resource
beneath them. Direct URLs still enforce backend authorization.

## Audit events

`admin.view.saved`, `admin.view.updated`, `admin.preference.updated`,
plus domain-specific mutation events.

## Storefront impact

None directly. CMS/category/product configuration screens produce
contracts consumed by storefront services.

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
