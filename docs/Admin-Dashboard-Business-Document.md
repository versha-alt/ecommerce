# Admin Dashboard Business Document

## Reader's guide and business foundations

This document explains the back office for one Kenyan retailer selling multiple brands through its online channel, central warehouse and seven or more showrooms. It describes what staff see and how they work, including the effect of their decisions on the future customer website. Building the website itself is outside scope.

All 18 existing documentation files are the source of facts: the overview, navigation/experience specification, dashboard, products, attribute engine, inventory, orders, customers, finance, shipping, promotions, CMS, marketing, support, reports, users/roles, settings and consistency check. Technical implementation details are translated into business behaviour.

**Proposed (to confirm)** marks additions where the sources do not define a screen detail, policy, or release. A heading carrying this label applies to the whole subsection. “Proposed” inside a table has the same meaning. Documented capabilities remain requirements even when their detailed presentation is proposed. “Required: Yes” means necessary for the stated action; “Conditional” means necessary in the stated case; “Display” means shown rather than entered; “Not stated” preserves unspecified requiredness. Template membership does not automatically mean mandatory entry.

Every release assignment is **Proposed (to confirm)**: the sources provide no MVP, Phase 2 or Phase 3 plan. MVP means minimum viable launch release. Costs, effort and dates are [To be estimated]; owners, limits, approvals, service targets and final policies are [To be agreed]. The final incomplete template line “Business” is interpreted as business outcomes, dependencies, customer-facing effects and decisions.

Prices are Kenyan shillings, displayed as KES, and VAT-inclusive by default. VAT means value-added tax applied to eligible sales. Supported tax classes are Standard 16%, Zero-rated and Exempt; zero-rated/exempt reasons are retained. Dashboard times, schedules and reporting boundaries use Nairobi time.

The documented planning baseline is 5,000–10,000 active stock codes at launch, 300–500 orders a day and peaks three to five times higher. These are source assumptions, not measured results or new forecasts. The dashboard should remain viable beyond 100,000 products and one million customers. SKU means stock-keeping code identifying a product or sellable variation.

Every business change creates a permanent audit record of who did what, when, relevant branch, before/after summary and request/device trace information. Secrets are excluded. Records referenced by historical orders are generally disabled, retired or anonymized rather than erased. Repeated payment, refund, fulfilment and external-confirmation submissions must not duplicate actions. Concurrent edits must be detected instead of silently overwriting another person's work. Operational records remain authoritative; search, reporting summaries and temporary saved information support them. Changes and notices sent to dependent areas must remain consistent.

# Part A — How the Admin Dashboard works

## A1. Overall layout

Desktop has a persistent 256-pixel sidebar and a 64-pixel top bar. Breadcrumbs and a contextual page header sit above the working area. An optional right inspector exposes selected-item settings without leaving the screen.

| Area | Appearance and use | Basis |
|---|---|---|
| Sidebar | Expandable modules/submenus; current item highlighted | Hierarchy documented; highlight proposed |
| Top bar | Shared workspace/account controls | Bar documented; detailed controls proposed |
| Breadcrumbs | Menu hierarchy, such as Products / Attribute Sets / Television / Edit | Documented; readable labels replace internal references |
| Page title | Screen name, context and actions | Documented |
| Main area | Grids, forms, charts, trees and builders | Documented |
| Right inspector | Selected-record/component details | Optional; documented |
| Footer | Help, dashboard version and Nairobi-time reminder | Proposed (to confirm) |

### Shared layout sketch

| Left | Centre | Right |
|---|---|---|
| Persistent sidebar | Top bar over workspace | Shared controls |
| Current module/submenu | Breadcrumbs, title, actions | Status/context |
| Available navigation | Search/filters or form sections | Inspector where relevant |
| Available navigation | Main list, form, chart or builder | Selected-item fields |
| Sidebar continues | Proposed footer | Proposed help |

Breadcrumbs show route hierarchy, never browsing history. Consistent spacing, rounded panels, readable Inter/system lettering and comfortable or compact grid density support daily use. Standard rows are 44 pixels high; compact rows are 36 pixels. Success, warning, error, text and background colours have consistent meanings. Status text accompanies colour.

Dark mode preserves WCAG AA contrast, a recognised readability standard for text and backgrounds. Drag-and-drop has keyboard alternatives. Forms announce errors to assistive tools. Large lists display visible rows efficiently, and sections load on opening. Shared tools include data grids, filters, saved views, bulk bars, column chooser, form sections, media picker, category tree, attribute-set builder, rule builder, formatted-text editor, scheduling, status labels, audit timeline, metrics, charts, job progress and confirmation dialogs. Destructive/high-risk work uses explicit confirmation.

## A2. Complete sidebar

| Top-level menu | Documented sub-items, in order |
|---|---|
| Dashboard | No sidebar children specified; five screens in B1 |
| Products | All Products; Categories; Brands; Attributes > Attributes, Attribute Groups, Attribute Sets; Reviews & Q&A; Import / Export |
| Inventory | Stock by Location; Adjustments; Transfers; Purchase Orders; Suppliers; Serial / Batch Tracking |
| Orders | All Orders; Draft Orders; Quotes & Enquiries; Returns / RMA; Fulfilments; Delivery & Installation |
| Customers | Customers; Segments; Groups; Abandoned Carts; Privacy Requests |
| Payments & Finance | Transactions; Refunds; Settlements; Reconciliation; Invoices / eTIMS; Disputes |
| Shipping & Logistics | Zones; Rate Rules; Carriers / Fleet; Pickup; Installation; Tracking |
| Promotions | Coupons; Cart Rules; Catalog Rules; Campaigns; Combo Offers; Gift Cards; Loyalty / Referral |
| CMS | Pages; Blocks / Widgets; Homepage Builder; Navigation / Mega Menu; Store Locator; Services / Enquiries; Blog; Media Library; URL Rewrites; Sitemap / Robots |
| Marketing | Child hierarchy unspecified |
| Support | Child hierarchy unspecified |
| Reports | Child hierarchy unspecified |
| Users & Roles | Child hierarchy unspecified |
| Settings | Child hierarchy unspecified |

CMS means content management system: the area for website pages and reusable content. RMA means return merchandise authorization: recorded handling of a goods-return request.

### Additional screen links — Proposed (to confirm)

| Parent | Links exposing documented module capabilities |
|---|---|
| Dashboard | Overview; Sales; Product Performance; Store Performance; Real-time Activity |
| Inventory | Reservations; Low-stock Alerts |
| Marketing | Campaigns; Subscribers; Templates; Popups; SEO Utilities; Tracking; Push within Campaigns |
| Support | Tickets; Canned Responses; Warranty Claims; Returns workspace |
| Reports | Report Catalog; Report Runs; Scheduled Reports |
| Users & Roles | Admin Users; Roles; Permission Review; Sessions; Audit Trail |
| Settings | General; Payment Gateways; Shipping Providers; Tax / eTIMS; Email / SMS; API Keys; Integrations; Webhooks; Storage / Media Delivery; Search; Cache; Queue Monitoring; Security; Notifications; Localization; Feature Flags; Backup / Maintenance; Theme; Developer Tools |

## A3. Top-bar controls — Proposed (to confirm)

| Control | Behaviour | Required |
|---|---|---|
| Global Search | Searches permitted products, SKUs, models, orders, customers and content; groups results by type | Optional |
| Branch / Showroom | Selects permitted scope; All Locations available only with appropriate scope | Conditional |
| Notifications | Low stock, assigned work, job results, compliance exceptions and delivery/integration problems | Display / optional open |
| Quick Create | Permitted New Product, Draft Order, Ticket or Content Page shortcuts | Optional |
| User menu | Profile, preferences, security and Sign Out | Optional |
| Light / Dark Mode | Changes appearance with readable contrast | Optional |
| Language | English initially, with Swahili readiness | Optional |

Branch switching never expands actual access. Proposed: show scope prominently, retain it between screens and warn before changing scope with unsaved work. Global Search obeys underlying field and branch restrictions.

## A4. Leadership home

### Overview layout — arrangement Proposed (to confirm)

| Band | Contents |
|---|---|
| Header | Overview; Date Range; Branch / Location; Channel; Brand; Category; Export |
| First cards | Gross Sales; Net Sales; Orders; Average Order Value |
| Second cards | Units; Refunds; Conversion; Payment Success |
| Operational cards | Fulfilment Service Level; Low-stock SKUs |
| Main panels | Sales trend; top products/categories/brands; per-store comparison |
| Lower panel | Real-time counters and proposed activity feed |

| KPI/panel | Business question | Qualification |
|---|---|---|
| Gross Sales | What is selected-period sales value? | Treatment of unpaid orders, tax, discounts and charges [To be agreed] |
| Net Sales | What remains after agreed deductions? | Deductions [To be agreed] |
| Orders | How many orders are in scope? | Source daily-sales example excludes cancelled orders |
| Average Order Value | What is the average eligible order worth? | Calculation basis [To be agreed] |
| Units | How many units sold? | Combo commercial-unit/component convention [To be agreed] |
| Refunds | What value was returned? | Count beside amount proposed |
| Conversion | How effectively do visits/opportunities become orders? | Denominator/data source [To be agreed] |
| Payment Success | How reliably do attempted payments complete? | Attempt/success definitions [To be agreed] |
| Fulfilment Service Level | Are orders handled within promised windows? | SLA means agreed service target; targets [To be agreed] |
| Low-stock SKUs | What needs replenishment? | Location thresholds apply |
| Sales trend | When is performance changing? | Line chart/prior-period overlay proposed |
| Top products/categories/brands | What contributes most? | Ranking basis proposed selectable sales/units |
| Per-store performance | Which showrooms need attention? | Permitted branches only |
| Real-time activity | What happens now? | Event-fed counters documented; order/payment/fulfilment feed entries proposed |

Date Range, Branch / Location, Channel, Brand and Category are documented filters. Channel means the selling route, such as online or showroom; choices [To be agreed]. Widgets can be reordered per user. Exports run in the background. Late refunds/payment confirmations revise affected reporting periods. Currencies are never silently mixed. Historical charts use prepared reporting information to avoid disrupting operations. Ordinary dashboard reads are not individually audited; exports and saved-view changes are.

## A5. Every list screen

| Control | Behaviour | Required |
|---|---|---|
| Search | Searches all permitted results, not just the current page | Optional |
| Filters | Supported business criteria; unknown filters cause a clear error | Optional |
| Sort | Orders the entire matching set | Optional |
| Column Chooser | Saves allowed visible columns per user | Optional |
| Saved Views | User-owned setup unless administrator explicitly shares it | Optional |
| Row selection | Current page selected by default | Optional |
| Select All Matching Records | Explicitly selects stated matching count across pages | Conditional |
| Bulk Action Bar | Appears for permitted actions after selection | Display/action |
| Pagination | Page, page size, total; maximum 200 rows per page | Display/choice |
| Export | Current filtered results as a background job, with field/branch limits | Optional; separate grant |
| Row actions | Operate on one record | Optional |

Proposed (to confirm): Apply/Clear Filters, Save/Rename/Delete View, and confirmation with bulk count/impact. Mixed-eligibility bulk work should identify outcomes per row; atomic or partial handling is [To be agreed] per action. Saved views and exports never expand access.

## A6. Every form screen

| Control/behaviour | Meaning | Basis |
|---|---|---|
| Save | Validate and stay | Product/set documented; universal label proposed |
| Save & Close | Save and return to list | Product documented; elsewhere proposed |
| Save as Draft | Keep incomplete work without going live | Proposed common button; only where draft state exists |
| Save and Continue | Continue multistep work | Proposed label |
| Back / Cancel | Leave form | Product Back documented; common Cancel proposed |
| Validation summary | Links to invalid fields | Product documented; shared treatment proposed |
| Inline messages | Explain missing/invalid values; announce errors accessibly | Documented |
| Unsaved warning | Warn before losing changes | Set-builder dirty state documented; universal warning proposed |
| Version history | Meaningful earlier snapshots | Products/CMS documented; universal versions not promised |
| Audit trail | Permanent change record | All business mutations documented; visible tab proposed |
| Notes | Internal context | Orders/customers documented; elsewhere proposed |

Product fields and checks come from the selected Attribute Set. Television and Washing Machine forms therefore differ. Local checks help, but saved business rules remain authoritative. Conflicting saves report that another user changed the record. Proposed: preserve edits, show differences and offer Reload Latest before resubmission. Failed saves retain entered work where practical. There is no documented universal Pending Approval status.

## A7. Standard states — visual treatment Proposed (to confirm)

| State | User sees | Next action |
|---|---|---|
| Empty | No records yet, with permitted creation | Create first record |
| No matches | No results match filters | Clear/change filters |
| Loading | Placeholder rows/panels and Loading | Wait without repeated submission |
| Validation error | Summary and field messages | Correct entries |
| Conflict | Record changed after opening | Review latest |
| Missing record | Record unavailable | Return/refresh |
| No permission | Access denied for this action/screen | Contact access owner |
| Service error | Plain explanation and investigation reference | Safe Retry or support |
| Maintenance | Administration temporarily restricted | Follow communication; duration [To be estimated] |

External failures retain investigation references but expose no secrets. Response speed, errors, provider failures and queue delays are monitored.

## A8. Role-specific dashboards

Roles control actions and branch scope. A menu hides only if none of its resources are readable. Bookmarks still check access. Product management never implies attribute management. Categories, brands, CMS and Store Locator are independently controlled.

The following role packages are Proposed (to confirm); catalog ownership, marketing's conditional merchandising access, warehouse identity read and finance responsibility are documented.

| Role | Landing experience | Suggested work | Separately granted |
|---|---|---|---|
| Leadership | Overview/store comparison | Analytics/reports | Exports, sensitive fields, changes |
| Showroom manager | Local sales, stock, orders, pickup/install | Own-branch handling | Other branches, adjustments, refunds, prices |
| Catalog manager | Products/moderation/imports | Product create/edit | Attributes/sets/groups, brands/categories, exports |
| Finance user | Transactions/reconciliation/eTIMS | Evidence-backed finance | Tax/eTIMS setup, settings, exports |
| Support agent | Assigned tickets and allowed context | Read/reply | Assignment, warranty/returns, refunds, exports |
| Warehouse user | Stock/transfers/serials | Inventory work; product identity read | Catalog edits, finance, purchasing/suppliers |
| Marketing user | Campaigns/content | Granted merchandising/preparation | Send/publish/tracking/attributes |
| Access administrator | Users/roles/sessions/audit | Identity administration | Every business/settings section separately |

Final grants and managers' cross-branch customer/order visibility are [To be agreed].

## A9. Tablet, mobile and themes

Tablet drawer navigation is documented. Proposed (to confirm): horizontally scrolling grids, full-width inspectors and persistent primary actions. Mobile managers get stacked KPI cards, compact order/stock summaries, filter drawers and large buttons. Stock checks, order reviews, delivery scheduling and job tracking remain practical; complex matrices/builders are best on larger screens. Offline mode is not specified.

Light/dark views retain the same actions and meanings. Contrast, charts, warnings and disabled controls remain readable. Proposed: save preference per user and offer device-theme default.

## A10. Background jobs

Imports/exports, reports, campaign sends, flash-sale activation and integration/content-delivery retries can continue while users work. A queue is the waiting line for background work. Catalog progress/history and import error reports are documented.

| Item | Meaning | Basis |
|---|---|---|
| Name/type/requester/time/scope | Identifies job and permitted selection | Detailed fields proposed |
| Queued, Running, Completed, Completed with Errors, Failed | Suggested shared job states | Proposed labels |
| Progress/processed count | Known completion; no invented percentage without a total | Progress documented; detailed count proposed |
| Results/Error Report | Successes and rejected row/column details | Import errors documented; common summary proposed |
| Download Results/View Details | Retrieve allowed output and history | Proposed labels |
| Retry | Safely repeat eligible failures | Documented for specific integrations; universal retry not promised |

Imports validate before commit and offer Dry Run. Reports may have expiring downloads. Proposed: shared Jobs panel under Notifications. Cancellation, retention, expiry and partial-success policies are [To be agreed] by job type.

# Part B — Module-by-module walkthrough

Part A's list/form behaviour, audit, conflicts, errors, branch restrictions and job treatment apply to every relevant screen. Below, controls stated as proposed are additions to terse sources, not approved facts.

## B1. Dashboard and analytics

**Purpose/users:** leaders/managers review sales, products, stores and service. **Release — Proposed (to confirm):** five screens in MVP; richer comparison in Phase 2. **Menu/screens:** Dashboard > Overview, Sales, Product Performance, Store Performance, Real-time Activity; tabs proposed.

### Overview

The complete layout, every documented KPI and its meaning are in A4. Period is required for a period query; other filters are optional. Reorder Widgets and Export are documented capabilities, labels proposed. No transactional row/bulk mutations apply.

**Workflow:** 1. Choose today's Nairobi range. 2. Choose permitted showroom. 3. Review cards. 4. Inspect low-stock/top-performer panels. 5. Reorder widgets. 6. Export if allowed. Proposed card-click drill-through.

### Sales

Proposed arrangement: sales-trend chart above period grid.

| Control/column | Meaning | Required |
|---|---|---|
| Gross/Net Sales, Orders, Average Order Value, Units, Refunds | Selected commercial measures | Display |
| Period, Value, Previous Value | Time bucket and supported prior comparison | Display; comparison selector proposed |
| Date Range, Branch/Location, Channel, Brand, Category | Shared filters | Period needed; others optional |
| Export | Background output | Optional |

**Workflow:** 1. Select week. 2. Narrow to TV brand. 3. Review daily sales/refunds. 4. Investigate a dip through permitted records (proposed). 5. Export. No row/bulk edits. Late events revise periods; currency remains separated.

### Product Performance

Top products/categories/brands are documented; three ranking tabs are proposed.

| Item | Meaning | Required |
|---|---|---|
| Product/Category/Brand | Ranked identity | Display |
| Sales Value, Units, Rank | Suggested ranking columns | Proposed display |
| Ranking Basis | Sales or units | Proposed optional |
| Shared filters/Export | Scope/output | Optional |

**Workflow:** 1. Choose period. 2. Review TV rankings. 3. Compare categories/brands. 4. Open permitted product (proposed row action). 5. Export. No documented bulk change other than export.

### Store Performance

Proposed chart over branch grid.

| Item | Meaning | Required |
|---|---|---|
| Store/Branch | Location | Display |
| Sales, Orders, Average Order Value, Units, Fulfilment Service Level | Suggested comparison columns | Proposed display |
| Shared filters/Export | Scope/output | Optional; period needed |

**Workflow:** 1. Choose month. 2. Compare permitted showrooms. 3. Identify delays. 4. Open branch context (proposed). 5. Export. No mutations.

### Real-time Activity

| Feed control — Proposed (to confirm) | Meaning | Required |
|---|---|---|
| Time / Branch / Event / Record / Outcome | When and where activity happened, what changed, and related record | Display |
| Event Type | Narrows event feed | Optional |
| Refresh / Open Record | Update display or inspect permitted underlying record | Optional |

Live event-fed counters are documented. Proposed feed columns: Time, Branch, Event, Record, Outcome; optional Event Type filter; Refresh/Open Record buttons.

**Workflow:** 1. Open activity. 2. Choose showroom. 3. Observe new order/payment. 4. Open allowed record (proposed). 5. Investigate exceptions. Counters do not substitute for verified payment evidence. No feed edits or bulk actions. Loading/error follow A7; freshness label proposed.

| Access | Allows |
|---|---|
| Dashboard read | Overview/granted activity |
| Sales analytics read | Granted sales measures |
| Store analytics read | Allowed branch comparisons |
| Analytics export | Permitted output |

**Business:** depends on orders, payments, refunds, inventory and stores. Metric definitions, conversion denominator and combo counting remain [To be agreed].

## B2. Products

**Purpose/users:** catalog managers own products/categories/brands/moderation/files; marketing edits granted merchandising; warehouse reads identity. **Release — Proposed (to confirm):** core screens in MVP; advanced merchandising Phase 2. **Menu/screens:** Products > All Products; contextual Create/Edit, Variations, Combo Composition, History/Clone; Categories; Brands; Reviews & Q&A; Import / Export. Attributes in B3.

### All Products

| Area | Appearance |
|---|---|
| Header | All Products; proposed New Product |
| Controls | Search/filters/saved views/columns |
| Status strip | Documented strip; exact metrics proposed |
| Grid | Product rows; expandable variable parents |
| Selection bar | Permitted bulk actions |

| Column/filter | Meaning | Required |
|---|---|---|
| Image, Name, SKU | Thumbnail/title/code | Display |
| Type, Status | Simple/Variable; Draft/Active/Disabled | Display/optional filters |
| Attribute Set, Brand, Categories | Template/assignments | Display/optional filters |
| Price | Commercial price; parent range display proposed | Display/optional range |
| Salable Stock | Available-to-sell quantity | Display |
| Updated | Last change | Display/optional date filter |
| Search | Name/SKU/model/barcode | Optional |
| Stock State | Availability | Optional; exact choices unspecified |
| Visibility | Catalog & Search, Catalog Only, Search Only, Hidden | Optional filter |

**Bulk:** Enable/Disable, soft Remove, Update Prices, Assign Categories, Change Attribute Set, Export. **Rows:** edit/clone documented; Open/Edit/Clone/Enable/Disable/View History/Remove menu placement proposed.

**Workflow:** 1. Search TV model. 2. Filter active products. 3. Expand parent sizes. 4. Select rows. 5. Choose allowed action. 6. Review impact/confirm. 7. Export. Referenced records are preserved.

### Create/Edit Product

First select type/set; then the set-defined form appears. Sticky header: Back, identity, status, Save, Save & Close. Error summary links to fields.

| Navigation | Main area | Header/panel |
|---|---|---|
| Base and set groups | Current section's fields/errors | Back, identity, status, Save, Save & Close |
| Pricing/Media/Specifications/Dimensions/Warranty/SEO | Product values | Proposed audit/history |
| Variations when relevant | Matrix choices/preview | Proposed child count |

| Field | Meaning | Required |
|---|---|---|
| Product Type | Simple sells one SKU; Variable groups sellable Simple children and cannot itself sell | Yes |
| Attribute Set | Specification template | Yes |
| Title, SKU, URL Key | Name, unique stock code, unique address ending | Yes |
| Model | Manufacturer designation | Not stated |
| Status | Draft/Active/Disabled | Yes |
| Short Description/Description | Brief/full explanation | Not stated |
| Regular Price | Normal selling price | Yes for sellable item |
| Special Price | Promotional price | Optional |
| Special From/To | Promotion times | Conditional |
| Cost | Internal buying cost | Optional/protected |
| Customer/Group Tier Prices | Targeted/quantity prices | Conditional if used; editor proposed |
| Discount Percentage | Computed reduction | Display |
| Tax Class | Standard 16%, Zero-rated, Exempt | Yes; standard default |
| Brand/Categories | Merchandise placement | Brand optional; categories not stated |
| Media/Barcode | Assets and scan identity | Not stated |
| Inventory | Location stock/settings | Conditional; no parent stock |
| Visibility | Catalog & Search, Catalog Only, Search Only, Hidden | Yes |
| Weight/Length/Width/Height | Shipping measurements | Not stated; units [To be agreed] |
| HS Code | International goods classification for relevant trade needs | Not stated |
| Warranty Policy | Coverage | Not stated |
| SEO Title/Description | Search heading/summary; SEO means improving discoverability and search presentation | Not stated |
| Related/Cross-sell/Up-sell | Associated products/complements/higher-spec alternatives | Optional |
| Scheduling | Applicable merchandising timing | Optional; precise controls unspecified |
| Set-defined specifications | Screen Size, Capacity and relevant attributes | Per-set rules |
| Badges | Sale, New Arrival, Best Seller, Black Friday, custom | Optional |
| Badge Mode/Schedule | Manual or rule-driven timing | Conditional; editor proposed |

Every set's locked Base contains Title, SKU, Model, URL Key, Status, Short Description, Description, Price, Special Price, Special From, Special To, Tax Class, Brand, Categories, Media, Visibility, Weight, Length, Width, Height, HS Code, Warranty Policy, SEO Title, SEO Description. Membership alone does not require every value.

**Workflow:** 1. Choose Simple/Television. 2. Enter identity. 3. Complete specifications. 4. Add images/brand/categories/pricing/tax/shipping/warranty. 5. Schedule Special Price. 6. Review status/visibility. 7. Save/fix errors. Save as Draft and Save and Continue are proposed conveniences.

**Rules:** unique SKU/URL Key; Special Price cannot exceed Regular Price unless policy permits; end follows start; set change previews affected values; historical order prices never recalculate; variable parents cannot own stock; ordered children are soft-disabled rather than erased.

### Variations

| Field/button | Meaning | Required |
|---|---|---|
| Variation Attributes | Fields marked Use for Variations | Yes for matrix |
| Option Values | Size/colour choices | Yes |
| Combination Preview/Count | All resulting combinations | Display |
| SKU Template | Child-code pattern using parent/options | Optional; label proposed |
| Generate Variations | Creates Simple children | Conditional action |
| Child SKU/Price/Special Price/Stock/Barcode/Images/Status | Child-owned values | SKU/price/status needed for sale; others as applicable |

**Workflow:** 1. Create Variable TV parent/set. 2. Select eligible Screen Size. 3. Choose values. 4. Review combination count. 5. Generate. 6. Check children. 7. Save sellable offers. Proposed Open/Edit Child row actions; no additional variant bulk action beyond generation/product bulk actions.

Only required, global dropdown or swatch attributes with options qualify. A swatch is a visual choice such as colour, image or text tile. Text/number/multi-select cannot define matrices. Description/media/shipping/warranty defaults inherit only when unset; child values win. Defining variation values never inherit. Website selection shows exact child price, aggregate salable child stock and optionally a range or “From KES …” before selection.

### Combo Composition

Recommended: Simple commercial SKU plus linked components, never a third product type. TV + soundbar + washer retains component stock/serial/warranty/return identity. Alternative: a promotion assembling independent products, easier for pricing experiments but weaker combined identity.

| Field/button | Meaning | Required |
|---|---|---|
| Combo/Component Product | Offer and included items | Yes |
| Component Quantity | Units per combo | Yes; greater than zero |
| Price Allocation | Value allocated per component | Supported; completeness [To be agreed] |
| Was/Now | Sum of component reference prices/combo selling price | Display |
| Split Fulfilment | Different-location handling | Explicit opt-in |
| Add/Remove Component/Save | Suggested editor controls | Proposed actions |

**Workflow:** 1. Create Simple combo. 2. Select TV/soundbar/washer. 3. Set quantities/allocation. 4. Review Was/Now. 5. Check components at location. 6. Decide split handling explicitly. 7. Save. Missing required component makes combo unavailable unless configured split handling applies. Orders snapshot components/allocation; one-component returns use original limits.

### Categories

| Left | Centre | Right |
|---|---|---|
| Lazy-loaded tree, 3+ levels; drag/drop/keyboard movement | Category form | Menu columns/promo tiles/brand logos preview |
| Selected category | Proposed Save/Cancel | URL/menu impact before move |

| Field/control | Meaning | Required |
|---|---|---|
| Name/URL Key | Label/address | Not stated; proposed required |
| Description/Image/Icon | Content/assets | Not stated |
| Status/Include in Menu | Availability/navigation | Not stated; labels [To be agreed] |
| Display Mode | Presentation | Not stated; choices [To be agreed] |
| SEO | Search information | Not stated |
| Manual/Rule Assignment | Explicit or condition-based membership | Conditional |
| Sorting | Product order | Not stated |
| Layered Navigation | Customer-filter configuration | Not stated |
| Menu Columns/Promo Tiles/Brand Logos | Expanded menu | Optional |
| Move/Impact Preview | Resulting URL/menu change | Conditional |

**Workflow:** 1. Select Televisions. 2. Edit content/search. 3. Assign products. 4. Set sorting/filters. 5. Compose menu. 6. Review move impact. 7. Save. Cycles rejected; one canonical, or main, URL per category; old paths get explicit redirects. Proposed Add/Edit/Move/Retire. No category bulk actions/status labels specified.

### Brands

Proposed grid opens brand form.

| Field/control | Meaning | Required |
|---|---|---|
| Name/URL Key | Identity/address | Not stated; proposed required |
| Logo/Banner/Description | Presentation | Not stated |
| Status | Availability | Not stated |
| SEO/Landing Configuration | Search/brand-page settings | Not stated |
| Bulk Assign Products | Many-product assignment | Conditional |
| Create/Edit/Remove/Save | Documented maintenance; labels proposed | Actions |

**Workflow:** 1. Create/open brand. 2. Add assets/content. 3. Configure landing page. 4. Bulk-assign products. 5. Save. 6. Use shop-by-brand block. Referenced-brand removal impact check proposed.

### Reviews & Q&A

| Item | Meaning | Required |
|---|---|---|
| Pending/Approved/Rejected | Waiting/accepted/refused | Documented states |
| Product/Content/Submitter/Date | Suggested moderation columns | Proposed display |
| Search/Product/Status | Suggested filters | Proposed optional |
| View/Approve/Reject | Moderation; placement/labels proposed | Conditional |
| Answer | Suggested Q&A response | Proposed |

**Workflow:** 1. Filter pending TV entries. 2. Open. 3. Review. 4. Approve/reject. 5. Audit. Rejection reason and reviewed bulk Approve/Reject proposed. Separate moderation grant.

### Import/Export

| Item | Meaning | Required |
|---|---|---|
| CSV/XLSX Upload | Catalog spreadsheet | Yes for import |
| Create/Update by SKU | Match stock code | Documented; selector proposed |
| Dry Run | Check without change | Optional |
| Option Resolution/Media References | Valid choices/assets | Conditional |
| Validate/Commit | Check before applying | Required sequence; labels proposed |
| Row/Column Error Report | Precise corrections | Display/download |
| Export Fields/Current Scope | Suggested permitted chooser | Proposed optional |
| Job History/Progress/Results | Background record | Display |

**Workflow:** 1. Upload. 2. Choose SKU matching. 3. Resolve options/media. 4. Dry Run. 5. Fix report. 6. Revalidate. 7. Commit. 8. Review results. 9. Export permitted scope. Partial-file policy [To be agreed].

### History and Clone

| Control — presentation Proposed (to confirm) | Meaning | Required |
|---|---|---|
| Version / Changed By / Date / Summary | Meaningful catalog snapshot history | Display |
| View Version | Inspect earlier snapshot | Optional |
| Clone | Create a new Draft from current catalog record | Optional |
| New SKU / New URL Key | Identity for cloned product | Yes to save clone |

Meaningful catalog snapshots are versioned. Proposed History: Version, Changed By, Date, Summary, View Version. Product restore not specified. Clone creates Draft with new SKU/URL Key, no inventory/history.

**Workflow:** 1. Open TV. 2. Clone. 3. Enter new identity. 4. Adjust values. 5. Save Draft. 6. Establish its own inventory. No history bulk action specified.

| Status | Meaning |
|---|---|
| Draft | Saved before active use |
| Active | Enabled, subject to stock/visibility |
| Disabled | Not actively offered; identity/history retained |

| Access | Allowed work |
|---|---|
| Product read/create/update/delete | Separate record grants |
| Bulk/import/export | Separate mass/file grants |
| Categories/Brands | Independent management |
| Moderation | Review/Q&A decisions |
| Attributes | Separate B3 grants |

**Business:** changes refresh catalog/search/customer browsing, selectors, category addresses and merchandising. Combo model, pricing exceptions, mandatory content, import policy and activation criteria [To be agreed].


## B3. Attributes, groups and sets

**Purpose/users:** authorized catalog specialists define product specifications and form templates once, consistently. **Release — Proposed (to confirm):** all screens in MVP. **Menu/screens:** Products > Attributes > Attributes, contextual Attribute Editor/Options/Assignment/Retirement, Attribute Groups, Attribute Sets and Set Builder/Clone/Change Impact.

An attribute is a named product characteristic, such as Screen Size. A group is a form section, such as Specifications. A set is the template combining appropriate sections/fields for a product family. Definitions govern products, categories, search filters, promotions, comparison and imports. Those areas consume definitions but cannot redefine them behind the administrator's back.

### Attributes list and definition editor

Proposed layout: definition grid on the left/list screen; editing opens labelled field sections and an options panel.

| Field/control | Meaning | Required |
|---|---|---|
| Attribute Reference | Stable business reference used across the catalog | Yes; unique; cannot change after creation |
| Label | User-visible field name | Yes |
| Input Type | How users enter values | Yes |
| Unit | Inch, kg or other agreed measure | Optional |
| Scope | Global: same definition/value scope across views; Store View: view-specific | Yes |
| Required | Product entry must include a value | Yes/no choice |
| Unique | Prevent repeated values where uniqueness is configured | Yes/no choice |
| Searchable | Can contribute to product search | Yes/no choice |
| Filterable | Can be a customer/product-search filter | Yes/no choice |
| Comparable | Appears in product comparison | Yes/no choice |
| Visible on Product Page | Appears in product details/specifications | Yes/no choice |
| Usable in Promotions | Can be used as a promotional condition | Yes/no choice |
| Sortable | Can order product results | Yes/no choice |
| Use for Variations | Can define sellable child combinations | Yes/no choice |
| System Attribute | Indicates protected built-in field | Display |
| Validation | Permitted value constraints | Optional; detailed controls not specified |
| Default Value | Starting value where appropriate | Optional |
| Localized Labels | Language-specific labels | Supported; requiredness not stated |
| Target Sets / Destination Group per Set | Where new definition appears | One or more sets in documented workflow |

The following are all documented input choices.

| Input choice | What entry looks like |
|---|---|
| Text | Short typed value |
| Text Area | Longer plain text |
| Formatted Text | Text with approved formatting |
| Number | Numeric value |
| Decimal | Numeric value allowing fractional amounts |
| Yes / No | Binary choice |
| Date | Calendar value |
| Dropdown | One option |
| Multiple Selection | More than one option |
| Colour Swatch | Visual colour option |
| Image Swatch | Visual image option |
| Text Swatch | Text tile option |
| Media | Asset selection |
| Price | Monetary entry |

**Proposed list controls:** columns Label, Attribute Reference, Type, Scope, Required, Use for Variations, System/Retired; filters Type, Scope, Variation Eligibility, System/Retired; rows Open/Edit/Manage Options/Assign/Retire. No attribute-specific bulk action is documented; proposed batch assignment should be confirmed before inclusion in implementation. Create/Update/Retire are documented capabilities, button labels proposed.

**Workflow:** 1. Create Screen Size. 2. Choose dropdown, unit inch, global scope and required entry. 3. Add valid sizes. 4. Enable Use for Variations if needed. 5. Assign Television > Specifications. 6. Save. Create Capacity separately for Washing Machine > Specifications. The same definition can belong to several sets without duplication.

### Attribute Options

| Field/button | Meaning | Required |
|---|---|---|
| Option Value | Stable choice used by products/imports | Yes; unique within attribute |
| Label | User-facing choice name | Yes |
| Sort Order | Choice order | Supported; proposed default |
| Swatch | Colour/image/text representation | Conditional by input type |
| Add/Edit/Reorder Option | Definition maintenance | Documented capability; labels proposed |
| Retire Option | Withdraw future use while preserving references | Conditional |
| Save | Commit validated choices | Proposed label |

**Workflow:** 1. Open Screen Size options. 2. Add a size and readable label. 3. Order choices. 4. Supply swatch if relevant. 5. Save. Existing product references prevent silent removal: referenced options are retired. Proposed status labels Available/Retired, and a Retired filter. No options bulk action specified.

### Attribute Groups

Default sections are General, Pricing, Media, Specifications, Dimensions, Warranty and SEO, alongside locked Base. Groups drive admin sections and later customer specification tables.

| Field/control | Meaning | Required |
|---|---|---|
| Group Reference / Name | Unique stable reference and visible section name | Reference/name required by definition |
| System Group | Protected status | Display |
| Create/Edit/Save | Section maintenance | Labels proposed |
| Group list | Proposed Name, Reference, System columns; Search/System filter | Proposed |

**Workflow:** 1. Open groups. 2. Create an approved specification section. 3. Name it clearly. 4. Save. 5. Place it in the relevant set builder. No group-specific bulk action or lifecycle is specified. Protected groups cannot bypass Base restrictions.

### Attribute Sets list, builder and clone

Templates: Default, Television, Washing Machine, Refrigerator, Air Conditioner, Small Appliance.

| Left pane | Middle pane | Right pane |
|---|---|---|
| Unassigned attributes | Ordered group tree and memberships | Selected group/attribute inspector |
| Search/selection proposed | Locked Base clearly indicated | Settings/validation |
| Available definitions | Keyboard or drag/drop reorder | Save and explicit unsaved state |

| Field/control | Meaning | Required |
|---|---|---|
| Set Reference / Name | Unique template reference and readable name | Yes |
| Groups | Sections in the product form | Required template structure |
| Membership / Order | Attributes included and position within group | Conditional |
| Locked Membership | Cannot remove protected Base field | Display |
| Version | Current saved template revision | Display |
| Add Group / Assign / Unassign / Reorder | Suggested labels for documented builder operations | Conditional |
| Save | Validates variations and saves layout | Required action |
| Clone | Copies memberships/order, not products | Optional |
| List Name/Reference/Version; Search | Proposed set-list controls | Proposed |

Title, SKU, Status and Price stay fixed. Other Base fields may be reordered only if explicitly marked reorderable. System Base cannot be removed. All Base fields are listed in B2.

**Workflow:** 1. Open Television. 2. Place Screen Size into Specifications. 3. Arrange permitted fields. 4. Confirm Base remains locked. 5. Validate variation eligibility. 6. Save. To create a related template, Clone, give it a new unique reference/name and adjust memberships. Clone transfers no product data. No set-specific bulk action specified.

### Set Change Impact and retirement

| Control/state | Meaning | Required |
|---|---|---|
| Current / Target Set | Existing and proposed template | Yes for change |
| Impact Preview | Values absent from target template | Display before commit |
| Retain Hidden | Default: preserve those values for safe recovery | Default |
| Discard Values | Permanently discard affected values | Separate destructive permission/confirmation |
| Unassign and Retire | Remove future use while preserving definition until migration | Impact preview/confirmation |
| Retired | Preserved definition awaiting migration or cleanup | Business state |
| Delete | Allowed only when no references/data remain | Conditional |

**Workflow:** 1. Request product set change. 2. Review lost form fields. 3. Keep hidden values by default. 4. Only an authorized user may explicitly discard. 5. Save. Hidden values no longer influence forms, customer views, filters or promotions. System fields cannot be deleted. User attributes referenced by sets/products/rules are blocked from ordinary deletion. Type change is blocked after values exist unless an approved migration preserves them without loss. Use for Variations requires dropdown/swatch, global scope, required entry and at least one option.

| Access | Work |
|---|---|
| Attribute read/create/update/retire | Separate definition operations |
| Group management | Section definitions |
| Set management | Templates/memberships/order/clone |
| Discard hidden values | Separate destructive capability |

**Business:** changes refresh forms, definitions and dependent search information; all definition/option/group/set changes, reorders, version changes and discarded values are audited. Search/storage approach is governed centrally so dynamic fields remain consistent and unique-value checks reliable. Attribute entry never writes product records. Retirement policy and lossless-change approval owner [To be agreed].

## B4. Inventory

**Purpose/users:** inventory staff and showroom managers maintain accurate available-to-sell stock across warehouse/showrooms. **Release — Proposed (to confirm):** stock, adjustments, transfers, reservations, alerts, serial tracking in MVP; richer purchasing/supplier work Phase 2. **Menu/screens:** Inventory > Stock by Location, Adjustments, Transfers, Purchase Orders, Suppliers, Serial / Batch Tracking; Reservations and Low-stock Alerts are documented screens with proposed extra links.

### Stock by Location

Proposed layout: header/location selector, filters and stock grid; right panel shows selected item's movements/reservations.

| Column/filter | Meaning | Required |
|---|---|---|
| Product / SKU | Item identity | Display / optional filters |

| Location | Warehouse/showroom | Display / optional filter |
| On Hand | Physically recorded quantity | Display / supported filter |
| Reserved | Held for orders, not available for another sale | Display / supported filter |
| Salable | Quantity available to sell | Display / supported filter |
| Reorder Point | Threshold for replenishment attention | Display; threshold-entry requiredness not stated |
| Low Stock | Narrows threshold breaches | Optional filter |
| Serial Status | Narrows serialized stock | Optional filter; choices unspecified |

**Documented inventory bulk capabilities:** Import Adjustment, Transfer, Update Thresholds, Export. Proposed placement: threshold/export on stock grid; imported adjustments in Adjustments; transfer initiation on stock selection. Proposed rows: View Movements, Adjust Stock, Start Transfer, View Serials.

**Workflow:** 1. Search TV SKU. 2. Select showroom. 3. Compare on-hand/reserved/salable. 4. Review movement context. 5. Adjust threshold if permitted. 6. Start transfer if replenishment required. Salable combo stock depends on every component at the chosen location. Variable-parent stock cannot be directly adjusted.

### Adjustments

| Field/control | Meaning | Required |
|---|---|---|
| Product / Location | Stock being changed | Yes |
| Quantity Change | Positive receipt/correction or negative reduction | Yes |
| Reason | Why stock changes; damage is a documented example | Yes |
| Reference | Related business record/document | Optional |
| Date | Movement time | Display |
| Import Adjustments | File-based bulk adjustment | Documented bulk |
| Save Adjustment | Suggested individual action label | Conditional |
| Before/After quantity; requester | Proposed grid/detail context | Proposed display |

**Workflow:** 1. Choose damaged soundbar/location. 2. Enter reduction. 3. Select reason. 4. Link evidence/reference if available. 5. Review and confirm. 6. Check movement history. On-hand/reserved quantities are non-negative by normal rule. Source describes negative on-hand as requiring privileged override; whether such exceptions are actually enabled must be agreed, not assumed. No ordinary stock user can bypass it. Proposed filters: date/reason/product/location; no adjustment-edit lifecycle specified after recording.

### Transfers

| Field/control | Meaning | Required |
|---|---|---|
| From / To Location | Origin/destination | Yes |
| Products / Quantities | Items moving | Conditional; proposed required for dispatch |
| Status | Draft, In Transit, Received, Cancelled | Display |
| Create / Edit Draft / Ship / Receive / Cancel | Documented ship/receive capabilities; other labels proposed from lifecycle | Conditional |
| Search/Status/Origin/Destination | Suggested transfer filters | Proposed optional |
| Reference, Date | Suggested transfer columns | Proposed display |

**Workflow:** 1. Create warehouse-to-showroom draft. 2. Add TV/washer quantities. 3. Review source availability. 4. Ship. 5. Destination checks receipt. 6. Receive once; repeated receipt does not duplicate stock. Proposed: discrepancy notes and partial-receipt policy [To be agreed]. Bulk transfer is documented; mass dispatch/receipt is not. Draft means unshipped; In Transit means dispatched; Received means stock accepted at destination; Cancelled means stopped under agreed conditions.

### Purchase Orders — detailed screen Proposed (to confirm)

| Column, field or control | Meaning | Required |
|---|---|---|
| Reference / Supplier / Destination / Date / Status | Buying-record identity, counterparty, receiving location and progress | Display; Supplier and Destination proposed required for submission |
| Product / Quantity | Items to buy | Proposed required for submission |
| Expected Date / Buying Price / Notes | Receiving/commercial context | Proposed optional; terms [To be agreed] |
| Supplier / Location / Status / Date filters | Narrows purchase-order list | Optional |
| New / Open / Edit / Save Draft / Submit / Record Receipt / Close / Cancel | Proposed workflow controls | Conditional action |

Documented capability: purchase-order maintenance. Proposed list columns Reference, Supplier, Destination, Date, Status; filters Supplier/Location/Status/Date. Form: Supplier, Receiving Location and Product/Quantity lines required; expected date, buying price and notes optional; exact commercial mandatory fields [To be agreed]. Buttons New Purchase Order, Save Draft, Submit, Record Receipt, Close, Cancel. Rows Open/Edit; no documented purchase-order bulk action.

**Workflow:** 1. Select supplier. 2. Add TVs/quantities. 3. Choose receiving warehouse. 4. Save and approve under agreed policy. 5. Record receipt and serials. 6. Check stock. Proposed states Draft, Submitted, Partially Received, Received, Cancelled; approval and stock-posting rules [To be agreed]. These stages are not specified in the source.

### Suppliers — detailed screen Proposed (to confirm)

| Field, column or control | Meaning | Required |
|---|---|---|
| Name | Supplier identity | Proposed yes |
| Contact / Phone / Email / Address | Supplier communication details | Proposed optional |
| Payment Terms / Notes | Agreed commercial context | Proposed optional |
| Status | Active or Inactive, proposed | Display/choice |
| Search / Status filter | Narrows list | Optional |
| Create / Edit / Save / Retire | Supplier maintenance | Conditional action |

Documented capability: supplier maintenance. Proposed grid Name, Contact, Phone/Email, Status; Search/Status filters. Form Name required; contacts, address, payment terms and notes optional. Actions Create/Edit/Save/Retire; no bulk action specified. Proposed Active/Inactive states.

**Workflow:** 1. Create supplier. 2. Add reliable contacts. 3. Save. 4. Select in purchase order. 5. Retire rather than remove used supplier (proposed historical rule). Duplicate-detection and commercial-document retention [To be agreed].

### Serial / Batch Tracking — detailed screen Proposed (to confirm)

| Field, column or control | Meaning | Required |
|---|---|---|
| Product / Location | Tracked stock identity | Proposed yes |
| Serial Number / Batch Reference | Physical-unit or lot identity | Conditional when tracking is configured |
| Status / Order / Warranty Link | Availability and sale/coverage links | Display/conditional |
| Product / Location / Serial Status / Search | Finds tracked units | Optional filters |
| Register / View History / Link to Order / Correct with Reason | Traceability actions | Conditional; reason proposed required for correction |

Documented capability: manage serial/batch traceability; component warranty/serial identity remains separate. Proposed fields/columns Product, Location, Serial Number or Batch Reference, Status, Order, Warranty Link. Required product/location and serial or batch when tracking is configured. Proposed filters Product/Location/Serial Status/Search. Actions Register, View History, Link to Order, Correct with Reason; no bulk action specified.

**Workflow:** 1. Find washer in stock. 2. Register/check its serial. 3. Link it to the shipped order component. 4. Confirm warranty context. 5. Investigate history if claimed later. Proposed Available, Reserved, Sold, Returned, Quarantined; exact states, duplicate rules and correction approval [To be agreed].

### Reservations

| Field/control | Meaning | Required |
|---|---|---|
| Order, Product, Location, Quantity | Stock hold for a particular order | Yes to create |
| Expires At | When temporary hold ends | Supported; expiry policy [To be agreed] |
| Create / Release Reservation | Supported operations; labels proposed | Conditional |
| List/status filters | Suggested order/location/product/expiry filters | Proposed optional |

**Workflow:** 1. Open order stock hold. 2. Confirm item/location/quantity. 3. Create or inspect reservation. 4. Complete order or allow expiry. 5. Release only when allowed. Simultaneous checkout holds cannot sell the same last unit twice. Expired reservations release automatically. Proposed Active/Expired/Released/Consumed labels; no reservation bulk action specified.

### Low-stock Alerts — detailed screen Proposed (to confirm)

| Column or control | Meaning | Required |
|---|---|---|
| Product / Location / Salable / Reorder Point / Shortfall | Item, branch and replenishment gap | Display |
| Product / Location filters | Limits alert list | Optional |
| Open Stock / Update Threshold / Start Transfer | Investigates or resolves shortage | Conditional action |
| Export / Bulk Update Thresholds | Permitted inventory bulk work | Optional; separate grant |

Documented screen relies on threshold/stock data. Proposed columns Product, Location, Salable, Reorder Point, Shortfall; optional Location/Product filters. Actions Open Stock, Update Threshold, Start Transfer; bulk threshold update/export are documented inventory capabilities. Proposed alert states Needs Attention/Resolved; notification recipients [To be agreed].

**Workflow:** 1. Review showroom alerts. 2. Open low-stock TV. 3. Compare warehouse availability. 4. Start transfer or purchase-order work. 5. Verify alert after replenishment.

| Access | Allows |
|---|---|
| Stock read / adjust | Separate view/change grants |
| Transfer management | Movement workflow |
| Purchase-order management | Buying records |
| Supplier management | Supplier records |
| Serial management | Traceability |
| Reservation/threshold overrides | Detailed access mapping [To be agreed]; not implied by read |

**Business:** available-stock updates drive future product, cart and pickup availability. Combos reserve/deduct component quantities at selected locations; split handling must be explicit. Serial/warranty stays per component. Adjustment/transfer/reservation records remain auditable. Stock authority versus any accounting/enterprise system is unresolved.

## B5. Orders

**Purpose/users:** authorized sales/showroom/operations staff manage the commercial order and its service; finance handles granted payment/refund work; support handles permitted returns/context. **Release — Proposed (to confirm):** core order, draft, quote conversion, fulfilment, returns/refunds, scheduling, pickup, POD, warranty and invoice exception handling in MVP; richer exchange tools Phase 2. **Menu/screens:** Orders > All Orders/detail/timeline, Draft Orders, Quotes & Enquiries, Returns / RMA, Fulfilments, Delivery & Installation; contextual Refunds/Exchanges, Cancellation, Warranty Registration, Pickup/POD and eTIMS invoice.

### All Orders and Order Detail

Proposed list layout: search/filters above grid; detail combines order lines and totals with a right payment/fulfilment panel and timeline below.

| Area | Content |
|---|---|
| Header | Order number, commercial status, allowed actions |
| Main | Customer/address, order lines, prices/tax/totals |
| Right panel | Payment status, fulfilment status, branch, schedule |
| Lower tabs | Timeline, Notes, Returns, Warranty, eTIMS; arrangement proposed |

| Field/column | Meaning | Required |
|---|---|---|
| Order Number | Unique readable reference | Display |
| Customer | Linked customer if available | Optional by source |
| Commercial Status | Overall order stage | Display/controlled action |
| Payment Status | Money state, separate from delivery | Display |
| Fulfilment Status | Goods/service state, separate from payment | Display |
| Currency | KES | Display/default |
| Subtotal, Tax Total, Grand Total | Commercial amounts | Display |
| Branch | Associated location | Optional by source; operational policy [To be agreed] |
| Product, SKU, Name, Quantity, Unit Price, Line Tax | Recorded order lines | Product/quantity needed for creation; identity/price/tax recorded |
| Combo Components/Allocation | Original included items/value allocation | Conditional for combo |
| Address | Delivery details; edits audited | Conditional; field breakdown proposed |
| Timeline/Notes | Events and internal context | Display; note entry conditional |


Proposed filters Search/Date/Branch/Customer/Commercial Status/Payment Status/Fulfilment Status; proposed columns Date/Customer/Branch/Grand Total beside documented identity/state. Proposed buttons Create Draft, Open, Edit permitted details, Add Note, Cancel, Fulfil, Return, Refund, Exchange, Schedule, Register Warranty, Generate eTIMS Invoice. No order-specific bulk action is documented beyond common permitted export; mass cancellation/refund is not established.

**Workflow:** 1. Find order. 2. Confirm customer/items/totals. 3. Check money and goods states separately. 4. Reserve/fulfil from permitted location. 5. Schedule service. 6. Add notes. 7. Follow timeline to completion. Historical prices, tax class and combo allocation remain snapshots.

### Lifecycle

| Status | Meaning |
|---|---|
| Draft | Not finalized |
| Pending Payment | Awaiting required payment |
| Paid | Required payment recognized |
| Processing | Preparing the order |
| Partially Fulfilled | Some goods fulfilled |
| Fulfilled | Required goods fulfilled |
| Completed | Commercial workflow completed |
| Cancelled | Order stopped under permitted conditions |
| Refunded | Captured money fully returned under financial rules |
| Partially Refunded | Only some captured money returned |
| On Hold | Progress paused pending resolution |

Documented main sequence: Draft, Pending Payment, Paid, Processing, Partially Fulfilled, Fulfilled, Completed. Side paths include the other statuses. Exact transition permissions/criteria are [To be agreed]. Payment and fulfilment have independent lifecycles; exact complete label lists are not supplied.

### Draft Orders

| Proposed field/control | Meaning | Required |
|---|---|---|
| Customer / Branch | Sales context | Customer conditional; branch policy [To be agreed] |
| Products / Quantities | Basket to finalize | Yes to finalize |
| Address / Delivery Option | Where/how goods reach buyer | Conditional |
| Payment Method | How buyer pays | Conditional to finalize |
| Totals | Calculated commercial values | Display |
| Search / Branch / Date | Draft-list filters | Optional |
| Open / Edit / Save Draft / Recalculate / Finalize Order | Draft workflow | Conditional |

Proposed editor uses Customer, Products/Quantities, Branch, Address/Delivery Option, Payment Method and calculated totals; products/quantities required to finalize, customer/address requirements conditional on sales mode. Buttons Save Draft, Recalculate, Finalize Order are proposed. Grid filters Draft/Search/Branch/Date proposed; rows Open/Edit/Finalize. No specific bulk action.

**Workflow:** 1. Start showroom draft. 2. Add TV/soundbar or combo. 3. Select customer/location. 4. Review prices/tax/service. 5. Save Draft. 6. Finalize into the payment workflow (label proposed). Validate availability/pricing; draft-to-order commitment policy [To be agreed].

### Quotes & Enquiries

| Proposed field/column/control | Meaning | Required |
|---|---|---|
| Reference / Customer / Request / Value / Date / Status | Quote/enquiry overview | Display |
| Customer / Contact | Requester identity/contact | [To be agreed] |
| Items / Quantities / Proposed Prices | Offer being considered | Conditional to create usable quote |
| Valid Until / Notes | Optional validity/context | [To be agreed] |
| Search / Status / Branch | List filters | Optional |
| Create / Open / Save / Convert to Order | Quote maintenance/conversion | Conditional |
| Send Quote | Suggested delivery action | Proposed; channel/authorization [To be agreed] |

Quote-to-order is documented. Proposed grid Reference, Customer, Request, Value, Status, Date; Search/Status/Branch filters. Quote form Customer/Contact, Items/Quantities, Proposed Prices, Notes, Valid Until; exact requiredness [To be agreed]. Buttons Create Quote, Save, Convert to Order; Send Quote is proposed, not an existing send specification. Proposed states Draft, Issued, Accepted, Converted, Expired, Declined.

**Workflow:** 1. Record enquiry for a TV + washer package. 2. Build quote. 3. Review prices/services. 4. Receive acceptance under agreed process. 5. Convert to order. 6. Verify payment and stock. Conversion is permitted only with quote-management access. Rules for quote expiry and honoring old prices are proposed decisions; no quote bulk action specified.

### Fulfilments and partial shipments

| Field/control | Meaning | Required |
|---|---|---|
| Order / Location | Source order and stock origin | Yes |
| Items / Quantity to Fulfil | Which remaining units ship | Yes |
| Existing Fulfilled / Remaining | Prevent over-shipment | Display |
| Component serials | Identity for relevant appliances | Conditional |
| Tracking / Shipment Reference | Carrier context | Supported; detailed fields proposed |
| Create Fulfilment / Confirm | Suggested operation labels | Conditional |
| Partial Shipment | Fulfil selected remainder without claiming everything shipped | Documented capability |

**Workflow:** 1. Open paid/eligible order. 2. Choose location and remaining items. 3. Capture required serials. 4. Confirm shipment. 5. Schedule remaining goods. Quantity cannot exceed unfulfilled quantity. Repeated confirmation cannot duplicate fulfilment. Proposed filters Order/Branch/Status/Date; no mass-fulfilment action documented.

### Returns / RMA

| Proposed field/column/control | Meaning | Required |
|---|---|---|
| Return Reference / Order / Customer | Return identity/context | Order required |
| Item or Component / Quantity | Goods being returned | Yes |
| Reason / Condition / Inspection / Outcome | Eligibility and disposition evidence | [To be agreed] |
| Status | Return progress | Display |
| Date / Branch / Status / Reason filters | Narrows queue | Optional |
| Create / Record Receipt / Inspect / Approve Outcome / Start Refund / Exchange | Return workflow | Conditional |

Proposed screen shows Return Reference, Order, Customer, Items/Components, Quantity, Reason, Inspection, Outcome and Status. Order/item/quantity required to identify return; reason/condition policy [To be agreed]. Buttons Create Return, Record Receipt, Inspect, Approve Outcome, Start Refund, Exchange are proposed labels for documented return/exchange capabilities. Proposed filters Date/Branch/Status/Reason. Proposed states Requested, Authorized, Received, Inspected, Resolved, Rejected.

**Workflow:** 1. Find combo order. 2. Select soundbar component. 3. Record request/reason. 4. Check eligibility. 5. Record receipt/inspection. 6. Decide refund/exchange under policy. 7. Use original component allocation for refund ceiling. Single combo components may be returned; warranty/serial checks remain specific. Stock disposition and customer-return window [To be agreed]. No return bulk action documented.

### Refunds / Exchanges

| Field/control | Meaning | Required |
|---|---|---|
| Order/Payment/Item or Component | Identifies affected sale and funds | Yes |
| Captured Amount / Already Refunded / Available Refund | Financial ceiling context | Display; suggested layout |
| Refund Amount | Money to return | Yes |
| Reason / Evidence | Audit context | Proposed required for manual financial action; exact refund policy not stated |
| Replacement Item / Quantity | New goods in an exchange | Conditional; editor proposed |
| Submit Refund / Create Exchange | Documented operations; labels proposed | Conditional |

**Workflow:** 1. Open approved return. 2. Review capture/prior refunds. 3. Select component and allowed amount. 4. Submit once. 5. Follow provider result. 6. For exchange, choose replacement and resolve value/stock difference under agreed policy. Never refund more than captured funds; combo component limits use saved allocations. Proposed Pending/Processing/Completed/Failed refund labels; exact provider mappings [To be agreed]. No mass refund specified.

### Cancellation and timeline/notes

| Proposed control | Meaning | Required |
|---|---|---|
| Current payment / fulfilment / commercial status | Context before cancelling | Display |
| Reason / Impact | Justification and affected work | Proposed reason required |
| Confirm Cancel | Applies authorized cancellation | Conditional |
| Note Text / Add Note | Records internal context | Text required when adding |
| Author / Time / Event / Before-After | Timeline/audit information | Display |

Cancellation is an explicit order action. Proposed dialog: current money/goods status, impact, required Reason, Confirm Cancel. **Workflow:** 1. Open order. 2. Review fulfilment/payment. 3. Request cancellation. 4. Confirm reason/impact. 5. Handle release/refund through separate allowed operations. Cancellation eligibility and automatic side effects [To be agreed]. Notes are recorded and auditable; proposed Add Note field requires non-empty text. Address edits, status transitions, refunds/cancels, fulfilments, invoice generation and manual payment confirmation are audited.

### Delivery & Installation

Documented scheduling; proposed calendar above task grid.

| Field/control | Meaning | Required |
|---|---|---|
| Order / Delivery Address / Branch | Service context | Conditional by delivery mode |
| Delivery Date/Window | Appointment | Conditional when scheduling |
| Installation Option / Date / Assignee | Service and allocation | Conditional |
| Free/Paid Eligibility | Explains included or charged service | Display |
| Save Schedule / Reschedule | Suggested operation labels | Conditional |
| Search/Date/Branch/Service Status | Suggested task filters | Proposed optional |

**Workflow:** 1. Open TV/washer combo. 2. Check delivery/install eligibility. 3. Select agreed date/window. 4. Assign service capacity. 5. Save. 6. Record completion. Proposed Unscheduled, Scheduled, In Progress, Completed, Failed/Needs Reschedule. Capacity/time-window rules [To be agreed]; free installation does not override specialist paid requirements.

### Pickup and Pay on Delivery

| Proposed field/control | Meaning | Required |
|---|---|---|
| Pickup Location / Appointment | Collection arrangement | Conditional |
| Amount Due / Amount Collected | Financial collection context | Display/conditional entry |
| Collection Status | Payment state independent of delivery | Display/controlled |
| Collector / Evidence | Handover/payment traceability | [To be agreed] |
| Ready for Pickup / Record Handover / Record Collection | Goods and money actions | Conditional |

POD means Pay on Delivery: payment is collected at handover under the configured method. Proposed detail fields Pickup Location/Appointment, Collection Status, Amount Due/Collected, Collector, Evidence. Requiredness conditional on pickup/POD. Proposed buttons Ready for Pickup, Record Handover, Record Collection; status/filter labels [To be agreed].

**Workflow:** 1. Choose pickup or POD. 2. Confirm location/capacity. 3. Record goods handover. 4. Record payment separately with allowed evidence. 5. Reconcile. Delivered does not automatically mean Collected; collection status is independent.

### Warranty Registration and eTIMS invoice

| Proposed field/control | Meaning | Required |
|---|---|---|
| Order / Component / Product / Serial | Item being covered | Conditional identifying evidence |
| Purchase Date / Warranty Policy | Coverage basis | Required validation context |
| Register / View Warranty | Records/checks coverage | Conditional |
| Tax / Customer details | Fiscal submission basis | Required as applicable under agreed eTIMS mode |
| Generate eTIMS Invoice / View Result / Retry | Submits or investigates fiscal record | Conditional; separate grant |
| Registration Result / Compliance Exception | Separate warranty/fiscal outcomes | Display |

Warranty registration is documented. Proposed form: Order, Component/Product, Serial, Purchase Date, Policy, Registration Result; identifying values conditional for claimable coverage. **Workflow:** 1. Select sold washer. 2. Confirm serial/policy. 3. Register. 4. Review warranty record. Proposed Register/View buttons and Registered/Needs Information labels; exact warranty-period policy [To be agreed].

KRA eTIMS is Kenya Revenue Authority's electronic tax-invoice management system. Order detail exposes Generate eTIMS Invoice and exception/retry capability; label placement proposed. **Workflow:** 1. Check recorded tax/customer details. 2. Submit invoice. 3. Review result. 4. If failure, keep commercial order and show compliance exception. 5. Authorized user retries. Fiscal failure never erases the sale; full finance treatment in B7.

| Access | Allows |
|---|---|
| Order read/create/update/cancel | Separately granted order work |
| Fulfilment management | Shipments/partial handling |
| Refund management | Financial returns |
| Return management | Goods returns |
| Quote management | Quotes/conversion |
| Order eTIMS management | Invoice actions |
| Warranty/scheduling/POD detail | Final action-to-role mapping [To be agreed] |

**Business:** customer-facing order status, tracking, pickup, warranty and return eligibility later reflect these records. Depends on products, stock, customers, promotions, finance and logistics. Return/exchange/cancellation policies and approvals remain [To be agreed].

## B6. Customers


**Purpose/users:** authorized customer/support/marketing staff manage identity and relationships; privacy staff handle rights requests. **Release — Proposed (to confirm):** profile/groups/privacy in MVP; segments/carts/merge refinements Phase 2. **Menu/screens:** Customers > Customers/detail/Addresses/Orders/Notes, Segments, Groups, Abandoned Carts, Privacy Requests; contextual Merge.

### Customers list and profile

Proposed layout: identity/contact header, profile tabs, order/value summaries, consent panel and notes.

| Field/column | Meaning | Required |
|---|---|---|
| First Name / Last Name | Customer identity | Requiredness not specified |
| Email | Contact address; unique when supplied | Optional |
| Phone | Contact number | Optional |
| Group | Assigned customer group | Optional |
| Status | Active / Blocked | Yes |
| Marketing Consent | Recorded permission for marketing | Yes/no; default not consented |
| Created At | Registration time | Display |
| Addresses | Delivery/contact locations | Supported; requiredness by use |
| Segments | Dynamic groupings | Display |
| Order History | Customer's past purchases | Display |
| Lifetime Value | Cumulative customer commercial value | Display; calculation [To be agreed] |
| Wishlist / Abandoned-cart Summary | Interests and uncompleted purchases | Display |
| Notes / Tags | Working context/classification | Notes documented; tags supported in bulk |

Proposed filters Search/Status/Group/Segment/Consent/Date; list columns identity/contact/status/group/created. **Documented bulk:** Assign Group, Tag, Export with permission, Marketing Opt-out. Proposed rows Open/Edit/View Orders/Privacy Export; creation UI not explicitly specified.

**Workflow:** 1. Find customer. 2. Verify identity/contact. 3. Review address/order context. 4. Correct permitted details. 5. Record consent with source/time. 6. Assign group/tag if appropriate. 7. Save. Active means usable profile; Blocked means restricted account under policy [To be agreed]. Consent changes are timestamped and source-attributed.

### Addresses, Orders and Notes

These are documented profile sections; separate tab arrangement proposed.

| Section | Fields/actions | Requiredness |
|---|---|---|
| Addresses | Proposed recipient, contact, address lines, town/county/postcode, default choice; Add/Edit/Save | Address details conditional for delivery; exact mandatory list [To be agreed] |
| Orders | Order number/date/state/value; Open Order proposed | Display; same order access limits |
| Notes | Text/date/author; Add Note proposed | Note text when adding |

**Workflow:** 1. Open profile. 2. Verify address before scheduling washer delivery. 3. Save permitted correction. 4. Open related order. 5. Add relevant internal context. No section-specific bulk actions documented.

### Groups — detailed screen Proposed (to confirm)

| Field, column or control | Meaning | Required |
|---|---|---|
| Name / Description | Customer-group identity/meaning | Name proposed required; description optional |
| Member Count / Tier-price Context | Membership and pricing relationship | Display |
| Search | Narrows group list | Optional |
| Create / Edit / Save / Assign Customers | Group work | Conditional |

Documented customer grouping and maintenance. Proposed list Name, Description, Member Count; Search. Form Name required, description optional, linked tier-pricing context display. Buttons Create/Edit/Save/Assign Customers. Customer-grid bulk assignment is documented. Proposed Active/Retired labels; detailed group lifecycle/permissions [To be agreed].

**Workflow:** 1. Create agreed customer group. 2. Describe eligibility. 3. Assign authorized customers. 4. Coordinate group prices in Products. 5. Review membership. Group membership is not permission to send marketing.

### Segments — detailed screen Proposed (to confirm)

| Field, column or control | Meaning | Required |
|---|---|---|
| Name / Conditions | Dynamic audience identity/rules | Proposed yes |
| Matching Customers / Count / Updated | Current preview | Display |
| Preview / Save | Checks and records rules | Conditional |
| Search / State | Proposed segment list controls | Optional |

Dynamic segments are documented. Proposed fields Name (required), Conditions (required), Matching Customers/Count (display), Preview/Save; grid Name/Criteria/Count/Updated, Search. Candidate conditions: purchase history, groups and engagement, all subject to agreed allowed data. Proposed Active/Paused states; no segment bulk action documented.

**Workflow:** 1. Define washer buyers under agreed rule. 2. Preview permitted matching customers. 3. Save. 4. Select segment for campaign. 5. Marketing separately suppresses unsubscribed contacts. Segment management does not grant customer export.

### Abandoned Carts — detailed screen Proposed (to confirm)

| Column or control | Meaning | Required |
|---|---|---|
| Customer / Contact / Items / Value / Last Activity / Recovery State | Unfinished-purchase context | Display |
| Date / Value / Group | List filters | Optional |
| Open Cart / View Customer | Inspection | Optional |
| Route Follow-up | Consent-aware marketing handoff | Conditional; proposed |
| Export | Common permitted list output | Optional with grant |

Documented screen and summaries. Proposed columns Customer/Contact, Items, Cart Value, Last Activity, Recovery State; filters Date/Value/Group; row Open Cart/View Customer. Proposed follow-up action requires marketing-send authority and consent; no automatic recovery sending is specified. Suggested Open/Recovered/Expired state labels and inactivity threshold [To be agreed].

**Workflow:** 1. Review uncompleted TV cart. 2. Check identity/consent. 3. Inspect items. 4. Route eligible follow-up to Marketing. 5. Track resulting order if supported. No cart bulk action specified beyond permitted common export.

### Privacy Requests

These implement the documented Kenya Data Protection Act 2019 rights workflow, subject to legal retention. This document does not set legal retention periods; those are [To be agreed] with the responsible adviser.

| Field/control | Meaning | Required |
|---|---|---|
| Customer | Whose data is involved | Yes |
| Request Type | Access/Export or Delete/Anonymize | Yes |
| Status | Open, Verified, Processing, Completed, Rejected | Display |
| Identity-verification evidence | Suggested verification reference | Proposed conditional |
| Retention reason / Outcome | Suggested explanation of retained/anonymized data | Proposed conditional |
| Verify/Start Export/Anonymize/Complete/Reject | Suggested labels for documented workflow | Conditional |
| Search/Type/Status/Date | Suggested filters | Proposed optional |

Open means received; Verified means identity checked; Processing means work underway; Completed means outcome fulfilled; Rejected means request cannot proceed with recorded reason (reason control proposed). **Workflow:** 1. Record request. 2. Verify requester. 3. Review legally retained orders/tax/fraud obligations. 4. Export or anonymize allowed information. 5. Retain necessary records without unnecessary identifying data. 6. Complete and record outcome. No mass privacy processing specified.

### Duplicate identity / Merge

| Proposed field/control | Meaning | Required |
|---|---|---|
| Candidate Profiles | Two possible duplicate identities | Yes |
| Contact / Address / Order Comparison | Evidence for identity decision | Display |
| Identity-verification Evidence | Supports same-person conclusion | Proposed required |
| Survivor Choice / Reason | What remains and why | Proposed required |
| Preview Merge / Confirm Merge | Reviews and applies authorized merge | Conditional |

Duplicate identities require a merge workflow and must never auto-merge on phone alone. Proposed comparison screen shows two identities, contact/address differences, order links and survivor choice; Preview Merge/Confirm Merge, reason and final permission owner [To be agreed].

**Workflow:** 1. Identify possible duplicate. 2. Compare evidence. 3. Verify same person. 4. Preview retained history. 5. Confirm authorized merge. 6. Audit. A shared phone is not sufficient proof.

| Access | Allows |
|---|---|
| Customer read/update | Separate viewing/editing |
| Customer export | Sensitive-data export |
| Segment management | Dynamic audience rules |
| Privacy management | Verification/export/anonymization workflow |
| Groups/merge/tags | Detailed action grants [To be agreed] |

**Business:** profiles/groups/consent feed future account and pricing experiences. Privacy and retained history must coexist. Consent-source standards, identity evidence, retention periods and merge ownership [To be agreed].

## B7. Payments & Finance

**Purpose/users:** finance owns payment truth, reconciliation, settlements, refunds, disputes, tax and eTIMS state. **Release — Proposed (to confirm):** M-Pesa, bank transfer, POD, selected cards, reconciliation/refund/eTIMS in MVP; optional wallets/BNPL and enhanced disputes Phase 2 or Phase 3 after provider agreement. **Menu/screens:** Transactions/detail/manual confirmation; Refunds; Settlements; Reconciliation/import/matching; Invoices / eTIMS; Disputes; contextual Tax Rules.

M-Pesa STK Push means a payment prompt sent to the customer's phone through the approved M-Pesa connection, known as Daraja. Cards use an agreed payment aggregator. Stripe/PayPal are optional. Bank transfer, POD and BNPL are supported capabilities; BNPL means buy now, pay later with an approved provider. Launch providers are unconfirmed.

### Transactions

| Field/column | Meaning | Required |
|---|---|---|
| Order | Linked commercial sale | Yes for transaction |
| Provider / Provider Reference | Payment service and traceable receipt/reference | Provider yes; reference when available |
| Type | Authorization, Capture, Refund, Void | Yes |
| Status | Provider-mapped financial state | Display; full labels [To be agreed] |
| Amount / Currency | Recorded money movement | Yes |
| Submitted/Updated time | Proposed chronology | Proposed display |
| Search/Order/Provider/Type/Status/Date | Suggested specialist filters | Proposed optional |
| Open / Query Status | Review and check unresolved payment | Query capability documented; labels proposed |

Authorization means permission to collect money; Capture means money collected; Refund means money returned; Void means cancelling an uncompleted authorization.

**Workflow:** 1. Find order payment. 2. Review amount/provider/reference. 3. For M-Pesa, see Pending after prompt. 4. Wait for verified provider confirmation. 5. If delayed, query/reconcile. 6. Update only on trustworthy result. Timeout is not treated as definitive failure until querying/reconciliation resolves it. No transaction-specific bulk action documented beyond allowed export.

### Manual Payment Confirmation

Finance permission, reason and evidence reference are required.

| Field/control | Meaning | Required |
|---|---|---|
| Order/Transaction | Payment being investigated | Yes |
| Evidence Reference | Receipt/bank/provider evidence | Yes |
| Reason | Why manual action is needed | Yes |
| Confirm Payment | Suggested explicit action label | Conditional |
| Amount/provider context | Shows what will be confirmed | Display; proposed layout |

**Workflow:** 1. Investigate pending transfer/payment. 2. Verify evidence. 3. Enter reference/reason. 4. Confirm under finance grant. 5. Audit and reconcile. No bulk manual confirmation is specified.

### Refunds

| Finance control — detailed presentation Proposed (to confirm) | Meaning | Required |
|---|---|---|
| Order / Transaction / Provider | Identifies funds | Yes |
| Amount / Currency | Refund movement | Yes |
| Available Refund / Prior Refunds | Prevents excess payout | Display |
| Reference / Status | Provider trace/outcome | Display when available |
| Reason / Evidence | Financial justification | Policy [To be agreed] |
| Submit / Query Result / Eligible Retry | Refund handling | Conditional |
| Provider / Status / Order / Date filters | Narrows finance refund list | Optional |


Order and finance refund work refer to the same recorded financial action, not two payouts. Fields: Order/Transaction, Type Refund, Amount/Currency, Provider/Reference, Status, available refundable amount; Required order/amount/currency/provider context. Proposed Reason, Evidence, Submit, Query Result and Retry where safe; filters Provider/Status/Order/Date.

**Workflow:** 1. Open approved refund context. 2. Check captured/prior refunded totals. 3. Select amount within limit and combo allocation. 4. Submit once. 5. Follow result. 6. Reconcile provider movement. No double payout on resubmission. Proposed Pending/Processing/Completed/Failed labels; retry never assumes a timeout proves failure. No refund bulk action documented.

### Settlements — detailed screen Proposed (to confirm)

| Field, column or control | Meaning | Required |
|---|---|---|
| Provider / Reference / Period | Settlement identity | Conditional to record |
| Expected / Received / Difference | Comparison of due and received funds | Display/recorded evidence |
| Status / Linked Transactions / Bank Evidence | Review context | Display/conditional |
| Provider / Period / Status filters | Narrows list | Optional |
| Open / Import Statement / Mark Reviewed | Proposed review controls | Conditional |

Documented settlement capability. Proposed grid Provider, Settlement Reference, Period, Expected Amount, Received Amount, Difference, Status; filters Provider/Period/Status. Form/inspection links underlying transactions and bank evidence; Reference/Provider/Period conditional for recording. Buttons Open, Import Statement, Mark Reviewed; exact input/settlement rules [To be agreed]. Proposed Awaiting Settlement/Received/Exception.

**Workflow:** 1. Select provider settlement. 2. Compare transactions and received funds. 3. Inspect differences. 4. Send unresolved items to Reconciliation. 5. Record review. No mass-settlement approval specified.

### Reconciliation and statement import

Reconciliation means checking the retailer's money records against provider or bank records.

| Field/control | Meaning | Required |
|---|---|---|
| Provider / External Reference | Statement origin and external payment reference | Yes |
| Internal Transaction | Candidate matching retailer record | Optional until matched |
| Status | Matched, Unmatched, Mismatch | Display |
| Statement Upload | File for checking | Conditional; detailed file format [To be agreed] |
| Match | Associate evidence with correct internal transaction | Conditional action |
| Amounts/difference/evidence | Suggested comparison columns | Proposed display |
| Search/Provider/Status/Date | Suggested filters | Proposed optional |

Matched means compatible records linked; Unmatched means no linked record; Mismatch means linked or candidate records disagree. **Workflow:** 1. Import M-Pesa/provider statement. 2. Review unmatched/mismatched entries. 3. Open candidate transaction. 4. Compare evidence. 5. Match when justified. 6. Escalate unresolved differences. Statement import is documented bulk intake; automatic mass acceptance is not. Matching cannot manufacture a receipt.

### Invoices / eTIMS

| Field/control | Meaning | Required |
|---|---|---|
| Order/Invoice Reference | Sale and fiscal record | Display; order required to generate |
| Line Tax Class/Net/Tax/Gross | Original tax treatment | Display |
| Zero-rated/Exempt Reason | Why special class applies | Conditional |
| eTIMS Request/Response History | Permanent compliance evidence | Display, immutable |
| Retry State / Exception | Compliance submission outcome | Display |
| Generate / Retry / View Record | Documented capabilities; labels proposed | Conditional |
| Search/Date/Status/Branch | Suggested invoice filters | Proposed optional |

**Workflow:** 1. Review original tax classes. 2. Generate invoice. 3. Submit via agreed eTIMS mode/provider. 4. Preserve request/response. 5. If failed, show compliance exception. 6. Retry without deleting order. Tax-inclusive prices are separated into net and tax; order-line class and reasons remain as at sale. Proposed fiscal labels Pending Submission, Submitted, Accepted, Retry Required; actual eTIMS status mapping [To be agreed]. No bulk submission action specified.

### Tax Rules

Documented finance tax-management capability; sidebar placement proposed within Invoices or Settings.

| Field/control | Meaning | Required |
|---|---|---|
| Tax Class | Standard 16%, Zero-rated, Exempt | Yes |
| Rate / Treatment | Supported tax basis | Required for applicable rule |
| Reason requirements | Zero/exempt justification | Conditional |
| Save Tax Rule | Suggested maintenance label | Conditional |
| Effective timing | Proposed policy control | Proposed; [To be agreed] |

**Workflow:** 1. Review class. 2. Confirm approved treatment. 3. Save authorized setup. 4. Verify new order calculation. Old orders retain original class/amounts. Detailed rounding/effective-date policy [To be agreed]; no arbitrary new tax rates invented.

### Disputes — detailed screen Proposed (to confirm)

| Field, column or control | Meaning | Required |
|---|---|---|
| Case / Provider / Order / Amount / Deadline / Status | Disputed-payment context | Display |
| Evidence / Reference | Response support | Conditional for response |
| Provider / Status / Date filters | Narrows cases | Optional |
| Open / Add Evidence / Submit Response / Record Outcome | Dispute handling | Conditional |

Documented capability. Proposed columns Case Reference, Provider, Order, Disputed Amount, Deadline, Status; filters Provider/Status/Date. Evidence/reference required for submitting a response; buttons Open, Add Evidence, Submit Response, Record Outcome. Proposed Open, Evidence Required, Submitted, Won, Lost. Full dispute grants/deadlines/provider rules [To be agreed].

**Workflow:** 1. Open card dispute. 2. Link order/payment/delivery evidence. 3. Review provider deadline. 4. Submit through authorized process. 5. Record outcome and reconciliation effects. No dispute bulk action specified.

| Access | Allows |
|---|---|
| Transaction read | View financial evidence |
| Refund management | Return captured funds |
| Reconciliation management | Import/match/investigate |
| Tax management | Tax rules |
| Finance eTIMS management | Fiscal records/retries |
| Settlements/disputes/manual confirmation | Finance authority required; detailed grants [To be agreed] |

**Business:** enabled methods and verified status feed future checkout. Finance state is separate from goods delivery. eTIMS records remain immutable/retryable. Payment aggregator, BNPL providers, eTIMS contract/mode, accounting authority and settlement/dispute rules [To be agreed].


## B8. Shipping & Logistics

**Purpose/users:** authorized logistics staff set service coverage, prices, own-fleet/carrier options, pickup, installation and tracking. **Release — Proposed (to confirm):** zones, rates, delivery/install eligibility, pickup and tracking in MVP; enhanced fleet planning Phase 2. **Menu/screens:** Shipping & Logistics > Zones, Rate Rules, Carriers / Fleet, Pickup, Installation, Tracking; contextual Quote Simulator.

### Zones

Proposed layout: coverage list beside zone form; a coverage preview is proposed, not a documented map requirement.

| Field/control | Meaning | Required |
|---|---|---|
| Name | Service-area label | Yes |
| Counties | Counties included | Yes for county-defined zone |
| Postal Patterns | Optional postal-area matching | Optional |
| Service Target in Hours | Delivery service target | Yes in zone definition; value [To be agreed] |
| Status | Zone availability | Display/choice; exact states not stated |
| Create/Edit/Save/Remove | Documented maintenance, labels proposed | Actions |
| Search/County/Status | Suggested filters | Proposed optional |

**Workflow:** 1. Create Nairobi service zone. 2. Select covered counties/postal patterns as applicable. 3. Set agreed service target. 4. Save. 5. Test a destination. Proposed validation for overlap and incomplete coverage should show impact rather than silently choose inconsistent zones. No zone-specific bulk action specified.

### Rate Rules

| Field/control | Meaning | Required |
|---|---|---|
| Zone | Applicable area | Yes |
| Method | Delivery/service method | Yes |
| Priority | Decides which matching explicit rule wins | Yes |
| Conditions | Eligibility criteria | Yes |
| Price | Charge when not free | Conditional |
| Free | Whether matched service carries no charge | Yes/no |
| Create/Edit/Save/Simulate | Maintenance/test; labels proposed | Actions |
| Search/Zone/Method/Free | Suggested filters | Proposed optional |

Conditions may use destination zone, category, product flags, order subtotal, weight, installation eligibility and branch stock. Highest-priority matching explicit rule wins.

**Workflow:** 1. Select zone/method. 2. Define free-delivery eligibility for the agreed product/order conditions. 3. Set priority. 4. Set price or Free. 5. Simulate TV + soundbar + washer order. 6. Review why rule wins. 7. Save. Charges/thresholds are [To be agreed], not invented. Proposed Active/Inactive status labels; rate-rule status list is not specified. No rate-rule bulk action documented.

### Quote Simulator

| Field/control | Meaning | Required |
|---|---|---|
| Destination | Zone/address being tested | Yes |
| Category/Product Flags/Items | Relevant order characteristics | Conditional |
| Subtotal/Weight | Commercial/physical values | Conditional |
| Installation Eligibility/Branch Stock | Service/location conditions | Conditional |
| Calculate Quote | Evaluates configured rules | Action |
| Winning Rule/Priority/Price/Explanation | Why service/charge was selected | Display |

**Workflow:** 1. Enter sample showroom combo. 2. Enter destination/weight/subtotal. 3. Request delivery and relevant installation. 4. Calculate. 5. Review explanation. 6. Adjust configuration only through granted editing. Unsupported address/postcode falls back to a manual quote only when enabled. Free installation must never silently override specialist paid installation. Equal-priority tie policy [To be agreed]. No simulator lifecycle/bulk action applies.

### Carriers / Fleet — detailed screen Proposed (to confirm)

| Field, column or control | Meaning | Required |
|---|---|---|
| Name / Own Fleet or Carrier | Service identity/type | Proposed yes |
| Contact / Service Methods / Coverage | How service operates | Conditional |
| Status | Availability | Display/choice |
| Type / Status / Search | Filters | Optional |
| Create / Edit / Save / View Shipments / Create Label | Maintenance and shipment work | Conditional |

Documented capability includes own fleet/carriers, labels and POD. Proposed grid Name, Own Fleet/Carrier, Contact, Service Methods, Status; filters Type/Status/Search. Form Name/type required, contacts and permitted services conditional. Buttons Create/Edit/Save, View Shipments, Create Label. Proposed Active/Inactive states. Vehicle/driver fields and routing are not specified.

**Workflow:** 1. Add agreed carrier or own-fleet service. 2. Set contact/service coverage. 3. Save. 4. Allocate an order shipment. 5. Create its label if supported. 6. Follow tracking. No fleet bulk action documented.

### Pickup — detailed screen Proposed (to confirm)

| Field, column or control | Meaning | Required |
|---|---|---|
| Location / Name / Address / Contact | Collection place | Location required; other mandatory detail [To be agreed] |
| Hours / Date / Daily Capacity | Controls appointment availability | Conditional |
| Status | Availability/capacity state | Display |
| Location / Date / Status filters | Narrows pickup workspace | Optional |
| Create / Edit / Save / Set Capacity / View Tasks | Pickup configuration | Conditional |

Pickup-location maintenance and location/day capacity throttling are documented. Proposed fields Location/name, address/contact, available hours, daily capacity, status; location required, capacity conditional. List filters Location/Date/Status; buttons Create/Edit Location, Save, Set Capacity, View Pickup Tasks. Proposed Available/At Capacity/Unavailable displays; exact appointment states [To be agreed].

**Workflow:** 1. Open showroom pickup location. 2. Set permitted dates/hours/capacity. 3. Save. 4. Check an order against stock/capacity. 5. Record pickup in Orders. No pickup bulk action specified.

### Installation

| Detailed controls — Proposed (to confirm) | Meaning | Required |
|---|---|---|
| Option Name / Eligible Products or Categories | Service and eligibility | Proposed yes |
| Specialist Requirement / Free or Paid | Delivery of service and charging mode | Conditional/choice |
| Charge / Coverage / Availability | Applicable cost/scope/capacity | Charge conditional for paid service; values [To be agreed] |
| Category / Free or Paid / Status filters | Narrows options | Optional |
| Create / Edit / Save / Test Eligibility | Service configuration | Conditional |

Documented free/paid installation options and eligibility. Proposed fields Option Name, Eligible Products/Categories, Specialist Requirement, Free/Paid, Charge, Coverage and Service Availability. Name/eligibility/service mode required to configure; charge conditional for paid service. Proposed grid filters Free/Paid/Category/Status and Create/Edit/Save/Test Eligibility buttons. Exact status labels not stated.

**Workflow:** 1. Define TV setup or washer installation. 2. Identify eligible items and specialist needs. 3. Set free/paid rules. 4. Simulate order. 5. Save. 6. Schedule service in Orders. Specialist paid work cannot become free merely because a broad promotional rule matches. No installation bulk action documented.

### Tracking — detailed screen Proposed (to confirm)

| Column or control | Meaning | Required |
|---|---|---|
| Shipment / Order / Carrier / Branch / Reference | Goods movement identity | Display |
| Stage / Last Update / Target / Exception | Service progress | Display |
| Carrier / Branch / Stage / Date filters | Narrows movements | Optional |
| Open Order / View Tracking / Print Label | Shipment inspection/label use | Optional |

Documented tracking/labels/service-target capability. Proposed grid Shipment/Order, Carrier, Branch, Tracking Reference, Current Stage, Last Update, Target, Exception; filters Carrier/Branch/Stage/Date; row Open Order, View Tracking, Print Label. Suggested stages Preparing, Dispatched, In Transit, Delivered, Exception; provider mapping [To be agreed].

**Workflow:** 1. Find combo shipment. 2. Review latest tracking and target. 3. Investigate exception. 4. Coordinate reschedule in Orders. 5. Keep POD collection separate. No tracking bulk action specified.

| Access | Work |
|---|---|
| Zone management | Coverage definitions |
| Rate management | Prices/eligibility/simulator changes |
| Carrier management | Delivery providers/fleet |
| Installation management | Service options |
| Pickup/tracking | Detailed action grants [To be agreed] |

**Business:** future checkout receives eligible delivery, pickup, installation and quoted prices. Depends on products, categories, order values, stock and addresses. Targets, capacities, manual-quote fallback, tie-breaking and specialist policy [To be agreed].

## B9. Promotions

**Purpose/users:** authorized merchandising/marketing staff configure discounts; publishers activate approved offers. **Release — Proposed (to confirm):** coupons, cart/catalog rules, scheduled specials, combos and basic campaigns in MVP; gift cards/tier refinements Phase 2; loyalty/referral Phase 3. **Menu/screens:** Coupons, Cart Rules, Catalog Rules, Campaigns, Combo Offers, Gift Cards, Loyalty / Referral; shared Rule Editor, Simulation and Activation.

### Shared Rule Editor

Proposed layout: identity/schedule across the top, condition builder left, effect settings right, simulation below.

| Field/control | Meaning | Required |
|---|---|---|
| Name | Readable promotion name | Yes |
| Rule Type | Cart, Catalog, Combo | Yes |
| Priority | Order in which applicable rules compete | Yes |
| Stackable | Can combine with other eligible discounts | Yes/no |
| Conditions | Customer/product/cart requirements | Yes |
| Actions | Discount or promotional effect | Yes |
| Starts At / Ends At | Optional schedule | Conditional if scheduled |
| Usage Limit | Maximum applications where configured | Optional |
| Status | Availability of rule | Display/choice; full source labels not supplied |
| Save / Simulate / Activate | Documented capabilities; labels proposed | Conditional |
| Exclusions | Conditions that disqualify an offer | Documented evaluation; editor placement proposed |

Exclusions run first, then non-combinable rules by priority, then combinable rules. Orders save which rules applied and the discount allocation on each line. Attribute conditions use stable catalog definitions. Promotion staff can read those definitions but cannot edit them through the rule builder.

**Workflow:** 1. Name Black Friday TV offer. 2. Choose type/conditions/action. 3. Set priority/combination policy. 4. Add schedule/limit if needed. 5. Simulate with other offers. 6. Save. 7. Authorized publisher activates. Proposed Draft, Scheduled, Active, Paused, Expired states; schedules/end-date checks and usage-limit validation presentation proposed. Equal-priority conflicts and negative-total protections [To be agreed]. Flash-sale activation runs in the background and refreshes customer pricing information; saving does not prove activation completed.

### Coupons

| Detailed control — Proposed (to confirm) | Meaning | Required |
|---|---|---|
| Coupon / Linked Rule | Redemption identity and price rule | Yes |
| Schedule / Usage / Limit / Status | Timing and permitted use | Conditional/display |
| Search / Status / Date filters | Finds coupon | Optional |
| Create / Edit / Save / Activate / Simulate | Maintenance/test/release | Conditional |

Documented coupon management. Proposed grid Coupon, Linked Rule, Schedule, Usage/Limit, Status; filters Search/Status/Date. Form Coupon/Linked Rule required; exact uniqueness/case policy [To be agreed]. Buttons Create/Edit/Save/Activate/Simulate. Bulk code generation, mass activation and coupon-specific bulk changes are not documented.

**Workflow:** 1. Create coupon linked to cart rule. 2. Set agreed usage/schedule. 3. Test eligible TV purchase and excluded case. 4. Activate with publish authority. 5. Review applied usage. Suggested coupon states follow proposed promotion lifecycle; used/expired feedback proposed.

### Cart Rules

| List control — Proposed (to confirm) | Meaning | Required |
|---|---|---|
| Name / Priority / Stackable / Schedule / Status | Cart-rule overview | Display |
| Type / Status / Date filters | Narrows rules | Optional |
| Open / Edit / Simulate / Activate | Works on one rule | Conditional |
| All Shared Rule Editor fields | Full configuration in the table above | As stated there |

A cart rule evaluates the selected basket and applies an automatic or coupon-triggered effect. It uses all shared rule fields; proposed grid Name/Priority/Stackable/Schedule/Status with Type/Status/Date filters and Open/Edit/Simulate/Activate rows.

**Workflow:** 1. Create rule for agreed basket conditions. 2. Define discount effect. 3. Set exclusions/priority. 4. Simulate with a TV + washer basket. 5. Save/activate. No cart-rule-specific bulk action documented.

### Catalog Rules

| List control — Proposed (to confirm) | Meaning | Required |
|---|---|---|
| Name / Priority / Stackable / Schedule / Status | Catalog-price rule overview | Display |
| Type / Status / Date / eligible Category or Brand | Filters/rule context | Optional/conditional |
| Open / Edit / Simulate / Activate | Rule work | Conditional |
| All Shared Rule Editor fields | Full rule configuration | As stated there |

Catalog rules adjust applicable merchandise pricing before the final basket context. Scheduled specials, tier pricing and automatic discounts are supported. Shared fields apply; proposed grid/filters/actions match Cart Rules, with category/brand conditions where eligible.

**Workflow:** 1. Choose applicable TV products through rules. 2. Define price effect. 3. Set schedule/priority. 4. Test the displayed offer and overlapping Special Price. 5. Publish. Exact interaction with product special/tier prices [To be agreed] beyond documented rule conflict order. No bulk action specified.

### Campaigns

| Detailed control — Proposed (to confirm) | Meaning | Required |
|---|---|---|
| Name / Linked Offers | Promotional campaign and included offers | Name/linkage needed for usable campaign |
| Schedule / Status | Event timing and availability | Conditional/display |
| Search / Status / Date | Filters | Optional |
| Create / Edit / Save / Preview / Activate | Campaign work | Conditional |

Promotional campaigns group offers and scheduled events, including flash sales. They are distinct from Marketing's message-send campaigns. Proposed fields Name, Linked Offers, Schedule, Status; required name/offer linkage for usable campaign; buttons Create/Edit/Save/Preview/Activate. Filters Search/Status/Date proposed.

**Workflow:** 1. Build Black Friday campaign. 2. Link offers/badges. 3. Set schedule. 4. Simulate applicable prices. 5. Activate. 6. Observe background completion. No campaign bulk action specified; detailed campaign lifecycle [To be agreed].

### Combo Offers

| Detailed control — Proposed (to confirm) | Meaning | Required |
|---|---|---|
| Combo Product / Components | Commercial offer and stock dependencies | Commercial SKU for recommended model |
| Was / Now | Reference/commercial selling prices | Display |
| Rule / Schedule / Priority | Promotion governance | As shared rule table |
| Simulate / Activate | Tests and releases | Conditional |

Shared combo-rule fields govern promotional eligibility/discounts. Component composition and stock remain in Products/Inventory. Proposed screen shows Combo Product, Included Components, Was/Now, Rule/Schedule/Priority, Simulate/Activate; commercial SKU required for recommended combo model.

**Workflow:** 1. Select TV + soundbar + washer combo. 2. Inspect component allocation and availability. 3. Add applicable rule/schedule. 4. Test exclusions/other offers. 5. Activate. Do not create an unsupported third product type. No combo-specific bulk action documented.

### Gift Cards — detailed screen Proposed (to confirm)

| Field, column or control | Meaning | Required |
|---|---|---|
| Card Reference / Original Value | Card identity and issued value | Proposed yes |
| Remaining Balance / Expiry / Status | Usage/availability | Balance display; expiry conditional |
| Customer | Associated recipient | Conditional by agreed policy |
| Search / Status / Date | Filters | Optional |
| Issue / View Ledger / Suspend / Reactivate | Card management | Conditional |

Gift-card management is documented; operating rules are not. Proposed grid Card Reference, Original Value, Remaining Balance, Expiry, Status; filters Status/Date/Search. Form reference/value required; customer/expiry conditional by policy. Buttons Issue, View Ledger, Suspend, Reactivate. Suggested Active, Used, Expired, Suspended. Transferability, refunds, expiry, security and liability treatment [To be agreed]; no invented fee/value policy.

**Workflow:** 1. Issue under agreed policy. 2. Record value/customer if needed. 3. Review balance ledger. 4. Apply through approved redemption process. 5. Investigate disputes with audit. No bulk issue/redemption action specified.

### Loyalty / Referral — detailed screen Proposed (to confirm)

| Field, column or control | Meaning | Required |
|---|---|---|
| Program Name / Eligibility / Earning or Reward Rule | Program identity and qualification | Mandatory policy [To be agreed] |
| Limits / Schedule / Status | Program controls | Conditional/display |
| Members / Referrals / Reward History | Participation evidence | Display |
| Save / Preview / Activate | Configure/test/release | Conditional |

Documented capability with no detailed design. Proposed tabs Program Rules, Members/Referrals and Reward History. Proposed fields Program Name, Eligibility, Earning/Reward Rule, Limits, Schedule, Status; exact mandatory values/payouts [To be agreed]. Buttons Save, Preview, Activate. Suggested Draft/Active/Paused states; no bulk points or reward issuance specified.

**Workflow:** 1. Agree program. 2. Define eligibility/rewards. 3. Test a qualifying purchase/referral. 4. Publish through approved role. 5. Review history. Earn/redeem ratios, anti-abuse and reversal policies [To be agreed].

### Simulation and activation

| Simulation control — Proposed (to confirm) | Meaning | Required |
|---|---|---|
| Customer / Group / Products / Quantities | Test sale context | Conditional |
| Basket Value / Date / Other Context | Rule inputs | Conditional |
| Simulate | Evaluates without a real sale | Action |
| Eligibility / Exclusions / Winning Rules / Allocations / Final Prices | Explains result | Display |
| Activate / View Activation Progress | Authorized release and queued outcome | Conditional |

Simulation is documented; proposed inputs Customer/Group, Products/Quantities, Basket Value, Date and Context as needed. Results show eligibility, exclusions, winning/combinable rules, discounts/allocation and final prices. No monetary amounts are assumed.

**Workflow:** 1. Test eligible combo. 2. Test unavailable/excluded product. 3. Add competing coupon. 4. Review rule order. 5. Activate only after validation. No simulator bulk action or record lifecycle applies.

| Access | Work |
|---|---|
| Rule management | Configure cart/catalog/combo offers |
| Coupon management | Coupon records |
| Gift-card management | Gift-card records |

| Promotion publishing | Activation independent of preparation |
| Loyalty/campaign details | Granular grant mapping [To be agreed] |

**Business:** active rules/badges feed pricing and basket experiences. Historical orders retain allocations. Usage/stacking/tier policy, gift-card terms and loyalty economics [To be agreed].

## B10. CMS: pages, content and navigation

**Purpose/users:** content editors/marketing maintain site content; separately authorized publishers release it. **Release — Proposed (to confirm):** all essential pages/blocks/homepage/navigation/store locator/services/media/redirects/search guidance in MVP; richer blog/editorial work Phase 2. **Menu/screens:** Pages; Blocks / Widgets; Homepage Builder; Navigation / Mega Menu; Store Locator; Services / Enquiries; Blog; Media Library; URL Rewrites; Sitemap / Robots; contextual Preview, Versions and Publishing Delivery.

### Pages

Seed types: About, Contact, Privacy, Terms, FAQ, Shipping & Returns, Warranty. A formatted visual editor or block editor creates content; preview, scheduling, search information and permanent versions are documented.

| Field/control | Meaning | Required |
|---|---|---|
| Title / URL Key | Page name and unique address ending | Yes |
| Status | Draft, Scheduled, Published, Archived | Yes/controlled |
| Blocks/Content | Ordered text and content sections | Requiredness not stated |
| SEO Title/Description | Search heading/summary | Optional |
| Publish At | Scheduled release time | Conditional |
| Version | Saved/published revision | Display |
| Save/Preview/Schedule/Publish/Unpublish/View Versions/Restore | Documented capabilities; labels proposed | Conditional |
| Search/Status/Type/Date | Suggested page-list filters | Proposed optional |

Proposed grid Title/URL Key/Status/Schedule/Version/Updated; row actions as above. No CMS-page bulk action specified beyond permitted common export.

**Workflow:** 1. Open Warranty page. 2. Edit clear coverage text. 3. Review search heading. 4. Preview. 5. Save draft or schedule. 6. Authorized publisher releases. 7. Inspect version/delivery status. Duplicate URL Keys rejected.

| State | Meaning |
|---|---|
| Draft | Editing, not released |
| Scheduled | Waiting for specified release time |
| Published | Released content version |
| Archived | Preserved outside current active publication |

### Blocks / Widgets

| Detailed control — Proposed (to confirm) | Meaning | Required |
|---|---|---|
| Name / Type / Content | Reusable component identity/content | Name proposed required; type/content needed |
| Version / Placements / Status | Revision and usage | Display |
| Schedule / Device / Targeting | Applicable presentation controls | Conditional |
| Search / Type / Status | Filters | Optional |
| Create / Edit / Preview / Save / Impact Preview / Publish | Component work | Conditional |

Reusable content is versioned. Proposed grid Name/Type/Version/Placements/Status; filters Type/Status/Search. Editor fields Type and Content required to create meaningful block, readable Name proposed required; schedule/device/targeting where supported. Actions Create/Edit/Preview/Save/Publish/Impact Preview.

**Workflow:** 1. Edit free-delivery banner block. 2. Preview. 3. Review every affected published placement. 4. Publish permitted version. 5. Check delivery. Referenced blocks cannot be deleted. No block-specific bulk action specified. Exact block status set [To be agreed]; page lifecycle must not be assumed automatically for every block.

### Homepage Builder

| Left | Centre | Right |
|---|---|---|
| Section palette | Ordered homepage canvas | Selected-section inspector |
| Available block types | Drag/drop with keyboard alternatives | Content/rules/schedule/device settings |
| Proposed Add Section | Preview arrangement | Save/Preview/Publish (labels proposed) |

Documented section types: Hero, Category Grid, Offers, Rule-driven Product Carousels, Shop by Brand, Store Locator, Services.

| Field/control | Meaning | Required |
|---|---|---|
| Section Type/Order | Kind of section and position | Yes |
| Content/References | Assets/text/category/brand/service linkage | Conditional by section |
| Product Rules | Defines carousel selection instead of manually pasted product presentation | Conditional for rule carousel |
| Device Visibility | Mobile, Tablet, Desktop availability | Supported; default [To be agreed] |
| Schedule | When section is eligible | Optional |
| Targeting Rules | Which audiences/context see it | Supported in builder; details [To be agreed] |
| Preview State | How proposed layout looks | Display |
| Add/Remove/Move/Save/Preview/Publish | Builder actions; exact labels proposed | Conditional |

**Workflow:** 1. Add hero. 2. Add category grid. 3. Add combo offer. 4. Define carousel rule. 5. Add brand/store/service sections. 6. Set device/timing. 7. Preview variants. 8. Publish authorized version. Content delivery failures have separate retry status; they do not undo successful publishing. No homepage bulk action applies.

### Navigation / Mega Menu

Documented header/footer trees, menu columns, category links, promotion tiles and brand logos. Proposed tree beside item inspector/preview.

| Field/control | Meaning | Required |
|---|---|---|
| Header/Footer Tree | Menu placement and hierarchy | Conditional |
| Label/Destination | Proposed item text/link | Conditional required for usable item |
| Category Reference | Linked category | Conditional |
| Menu Columns/Promo Tiles/Brand Logos | Expanded-menu composition | Optional |
| Move/Add/Edit/Preview/Save/Publish | Documented editing capability; labels proposed | Conditional |
| Broken/Unpublished References | Validation issue | Display |

**Workflow:** 1. Select header. 2. Add TV category reference. 3. Arrange columns. 4. Add soundbar promo/brand logos. 5. Preview. 6. Correct broken/unpublished references. 7. Publish. Such references block publication unless an explicitly authorized override is used; override owner/reason policy [To be agreed]. No menu bulk action specified.

### Store Locator

Seven or more showrooms and applicable warehouse public locations can be maintained; a stock location and a publicly visible locator entry serve different business purposes.

| Field/control | Meaning | Required |
|---|---|---|
| Name/City/Address | Public location identity | Yes |
| Coordinates | Latitude/longitude for location display | Optional |
| Phone | Public contact | Optional |
| Hours | Opening schedule | Supported; requiredness not stated |
| Services | Offered showroom/service capabilities | Supported; requiredness not stated |
| Status | Public availability | Required status choice; exact labels unspecified |
| Create/Edit/Save/Remove | Maintenance labels proposed | Actions |
| Search/City/Status | Suggested filters | Proposed optional |

**Workflow:** 1. Open showroom. 2. Verify address/contacts/hours. 3. List pickup/demo/service capabilities as agreed. 4. Add coordinates if available. 5. Set status. 6. Save. Proposed validation for invalid coordinates and contradictory hours. No location bulk action specified. Store Locator access is independent of general CMS editing.

### Services / Enquiries and WhatsApp configuration

Service definitions and enquiry inbox are documented; proposed two-tab layout.

| Field/control | Meaning | Required |
|---|---|---|
| Service Definition | Description of offered service | Fields not specified; proposed Name/Description/Status |
| Enquiry/Contact/Service | Incoming lead context | Requiredness [To be agreed] |
| Lead Status | New, Contacted, Qualified, Won, Lost | Display/controlled |
| Approved WhatsApp Destination | Approved business contact target | Conditional when click-to-chat enabled |
| Template Parameters | Approved message context | Conditional |
| Open/Update Status/Save Service | Suggested labels | Conditional |
| Search/Service/Lead Status/Date | Suggested inbox filters | Proposed optional |

New means received; Contacted means follow-up made; Qualified means suitable opportunity; Won means successful outcome; Lost means unsuccessful outcome. **Workflow:** 1. Open installation enquiry. 2. Review service/contact. 3. Contact through authorized process. 4. Update stage. 5. Record outcome. Enquiry-status changes audited. Click-to-chat configuration uses approved destination/template parameters; the dashboard does not promise an unrestricted messaging inbox. No enquiry bulk action specified.

### Blog

| Detailed control — Proposed (to confirm) | Meaning | Required |
|---|---|---|
| Title / URL Key / Content | Article identity/body | Blog policy [To be agreed] |
| Author / Category / Tags | Attribution/classification | Supported; mandatory policy [To be agreed] |
| Schedule / SEO / Status | Publishing/search context | Conditional |
| Search / Author / Category / Status | Filters | Optional |
| Create / Edit / Preview / Save / Schedule / Publish | Article work | Conditional |

Documented Author, Category/Tags, Schedule and SEO alongside post content. Proposed grid Title/Author/Category/Status/Schedule; filters Search/Author/Category/Status. Title/URL Key/content requiredness should follow approved blog rules; author/categories/tags supported. Buttons Create/Edit/Preview/Save/Schedule/Publish; separate publish grant. Proposed use of content lifecycle where applicable, to confirm.

**Workflow:** 1. Draft TV-selection article. 2. Set author/tags. 3. Add search metadata. 4. Preview. 5. Schedule/publish. 6. Check published version. No blog bulk action specified; public publishing still follows source content/version safeguards.

### Media Library

| Field/control | Meaning | Required |
|---|---|---|
| Asset/Metadata | File and descriptive properties | Asset required for upload; exact metadata list unspecified |
| Alternative Text | Text describing image to people who cannot see it | Supported; requiredness [To be agreed] |
| Delivery Rendition Status | Whether display-size versions are ready | Display |
| Usage References | Proposed list of placements | Proposed display |
| Upload/Edit Metadata/Select/Delete | Documented library capabilities; labels proposed | Conditional |
| Search/Type/Ready Status | Suggested filters | Proposed optional |

**Workflow:** 1. Upload TV/banner image. 2. Describe it with alternative text. 3. Check display versions. 4. Select in product/page. 5. Before removal, inspect references. Referenced media deletion is blocked. Rendition means an appropriately sized version for display; background delivery prepares it. No bulk upload/delete rule explicitly specified.

### URL Rewrites

A redirect sends someone using an old web address to the correct current page.

| Field/control | Meaning | Required |
|---|---|---|
| Old Address / Target Address | Unique source and destination | Yes |
| Redirect Type | Permanent or Temporary | Yes |
| Create/Edit/Save/Test | Suggested labels for documented maintenance | Conditional |
| Search/Type | Suggested list filters | Proposed optional |

Permanent indicates lasting move; Temporary indicates provisional move. **Workflow:** 1. Review moved category's old address. 2. Enter target main address. 3. Choose permanent/temporary treatment. 4. Check destination. 5. Save. Loops rejected; chains prevented where possible. No URL-rewrite bulk action specified.

### Sitemap / Robots

| Editor control — Proposed (to confirm) | Meaning | Required |
|---|---|---|
| Sitemap Settings / Content | Public-page discovery list | Conditional |
| Crawler Guidance | Search-engine access guidance | Conditional |
| Preview / Validate / Save | Check and save valid changes | Conditional |
| Publish | Suggested release control where needed | Policy [To be agreed] |

A sitemap lists pages for search engines. Robots guidance tells crawlers which areas they may inspect. The documented editors validate their content.

Proposed fields: sitemap settings/content and crawler guidance; Preview/Validate/Save, with Publish where agreed. Requiredness depends on submitted changes. No list filters/columns or bulk action applies to these editors.

**Workflow:** 1. Open relevant editor. 2. Review public discovery guidance. 3. Change under authorized policy. 4. Validate. 5. Correct errors before saving. Exact search-indexing policies and approvals [To be agreed].

### Preview, versions and publishing delivery

| Control — presentation Proposed (to confirm) | Meaning | Required |
|---|---|---|
| Preview | Short-lived, non-indexed draft view | Optional |
| Version / Author / Time / Content | Immutable content history | Display |
| Compare / View / Restore | Review or restore content | Conditional; Compare label proposed |
| Publish / Unpublish | Controlled release/removal from live use | Conditional |
| Delivery Status / Affected Pages / Retry | Website refresh outcome, separate from publish outcome | Display/conditional |

Preview access is short-lived and excluded from search indexing. Versions preserve content and author/time information. Restore is documented for CMS; proposed Compare/View/Restore controls. Publishing/unpublishing, restore, navigation changes, redirects, media deletion and enquiry-status changes are audited.

**Workflow:** 1. Preview selected draft. 2. Authorized publisher releases immutable version. 3. Dashboard records publication/version/time and separately tracks delivery refresh. 4. If customer-page refresh is temporarily unavailable, publication remains successful. 5. Retry delivery to the exact affected pages. Proposed delivery labels Pending, Delivered, Retry Required; no bulk restore promised.

| Access | Allows |
|---|---|
| Page read/manage/publish | Separate read/edit/release |
| Blocks management | Reusable content |
| Homepage manage/publish | Separate build/release |
| Navigation manage/publish | Separate menu edit/release |
| Store Locator management | Independent public-location work |
| Blog manage/publish | Separate post edit/release |
| Media management | Assets/metadata |
| Redirect management | Address migration |
| Services/enquiries/discovery editor | Detailed grants [To be agreed] |

**Business:** customer website uses only published content and controlled refreshes; it receives version/publication information without exposing the editing workspace. Broken references, duplicate addresses, loops and referenced deletion are prevented. Publication governance, override authority, accessibility-required assets and enquiry ownership [To be agreed].


## B11. Marketing

**Purpose/users:** authorized marketing staff prepare consent-aware communication and site marketing configuration; send authority is separate. **Release — Proposed (to confirm):** subscriber/consent-aware campaigns/templates and agreed launch channels in MVP; popups/tracking/SEO refinements Phase 2; push where not launch-ready Phase 3. **Menu/screens:** Marketing, with proposed links Campaigns/Broadcasts, Subscribers, Templates, Popups, SEO Utilities, Tracking; Push is a campaign channel.

### Campaigns / Broadcasts

Documented channels are Email, SMS, WhatsApp and Push. SMS means text message; push means a device/browser notification where the recipient has the necessary permission. Sending runs in the background.

Proposed layout: campaign list; editor with audience left, content centre and schedule/test/send panel right.

| Field/control | Meaning | Required |
|---|---|---|
| Name | Readable campaign identity | Yes |
| Channel | Email, SMS, WhatsApp or Push | Yes |
| Segment | Chosen audience | Optional in source; audience-selection policy [To be agreed] |
| Content | Message body and relevant media | Yes for usable campaign |
| Status | Draft, Scheduled, Sending, Sent, Cancelled | Display |
| Scheduled At | Send time | Conditional if scheduled |
| Create/Edit/Save | Maintenance, labels proposed | Actions |
| Send Test | Sends a controlled test | Documented; target field proposed conditional |
| Schedule | Queues planned sending | Documented |
| Send/Cancel | Suggested controls for documented send/lifecycle | Conditional |
| Search/Channel/Status/Schedule | Suggested filters | Proposed optional |
| Eligible/Suppressed Recipient Count | Suggested consent-check result | Proposed display |

Draft means preparation; Scheduled means awaiting send time; Sending means in progress; Sent means send workflow completed, not proof every person read it; Cancelled means stopped under the provider/job rules.

**Workflow:** 1. Create TV/washer campaign. 2. Select channel/segment. 3. Write appropriate content. 4. Check eligible recipients. 5. Send Test. 6. Schedule using granted send authority. 7. Track progress/results. Unsubscribed contacts are suppressed. Provider throttling, or limits on sending speed, triggers retries with increasing wait rather than unlimited immediate resubmission. WhatsApp templates must be provider-approved where required. No marketing-specific bulk campaign action is documented; a broadcast is itself controlled audience work.

### Subscribers

| List control — Proposed (to confirm) | Meaning | Required |
|---|---|---|
| Contact / Channel / Consent / Source / Consent Date / Status / Segment | Allowed subscriber context | Display |
| Search / Channel / Consent / Segment | Filters | Optional |
| View / Opt Out | Inspect or record withdrawal | Opt Out placement proposed; customer opt-out capability documented |

Documented subscriber viewing; proposed grid Contact, Channel, Consent, Source, Consent Date, Status, Segment. Filters Search/Channel/Consent/Segment proposed. Opt-out is documented as a customer bulk action; a subscriber-list Opt Out placement is proposed and must use the same consent record. Export/import subscriber permissions are not established by subscriber-read access.

**Workflow:** 1. Find customer/contact. 2. Review consent source/time. 3. Honor opt-out. 4. Verify suppression in campaign selection. Proposed Subscribed/Unsubscribed labels; separate email/SMS/WhatsApp consent granularity [To be agreed]. Subscriber duplication/identity checks follow Customers; no auto-merge by phone.

### Templates

| Detailed control — Proposed (to confirm) | Meaning | Required |
|---|---|---|
| Name / Channel / Content | Reusable message | Needed to use |
| Approval State / Provider Reference | Required provider approval evidence | Conditional for applicable WhatsApp templates |
| Search / Channel / Approval | Filters | Optional |
| Create / Edit / Preview / Test / Save | Message preparation | Conditional |

Template management and campaign tests are documented. Proposed fields Name, Channel, Content, Approval State, Provider Reference; name/channel/content required to use a template; provider approval reference conditional for WhatsApp. Proposed rows Create/Edit/Preview/Test/Save and filters Channel/Approval/Search. Suggested Draft/Pending Provider Approval/Approved/Rejected for provider templates; these are proposed, not a universal administrative approval lifecycle.

**Workflow:** 1. Draft approved-purpose washer-install message. 2. Choose channel. 3. Supply content and relevant parameters. 4. Obtain provider approval where necessary. 5. Test. 6. Reuse in campaign. No template bulk action specified.

### Popups — detailed screen Proposed (to confirm)

| Field, column or control | Meaning | Required |
|---|---|---|
| Name / Content | Popup identity/message | Proposed yes |
| Placement / Audience / Device | Context where popup appears | Conditional |
| Schedule / Frequency / Dismissal | Timing and interaction | Conditional; values [To be agreed] |
| Status | Availability | Display/choice |
| Search / Status / Date | Filters | Optional |
| Preview / Save / Activate | Popup work | Conditional |

Documented popup management. Proposed grid Name, Placement, Audience, Schedule, Status; filters Search/Status/Date. Form Name/content required; device visibility, audience, display frequency and dismissal rules conditional. Buttons Preview/Save/Activate. Proposed Draft/Active/Paused/Expired. Exact frequency, consent presentation and eligibility [To be agreed].

**Workflow:** 1. Create combo offer popup. 2. Choose appropriate audience/device placement. 3. Set agreed frequency/schedule. 4. Preview. 5. Activate with appropriate access. No popup bulk action specified.

### SEO Utilities — detailed screen Proposed (to confirm)

| Column or control | Meaning | Required |
|---|---|---|
| Record / Search Title / Description / Issue / Owner | Search-metadata review | Display |
| Search / Record Type / Issue | Filters | Optional |
| Open Record / Review | Correct through owning product/content editor | Optional |
| Metadata entry | Uses owning module's fields | As owning module |

Documented marketing SEO utilities/metadata, while actual product/page search fields remain owned in their editors. Proposed audit list Record, Search Title, Description, Issue, Owner; Search/Type/Issue filters; Open Record and Review actions. No new automatic rewrite of all metadata is established.

**Workflow:** 1. Review missing TV page descriptions. 2. Open source product/page with granted access. 3. Correct metadata. 4. Publish where needed. 5. Review again. Requiredness follows owning module. No independent SEO lifecycle or bulk rewrite specified.

### Tracking: tag management and pixels — detailed screen Proposed (to confirm)

| Field, column or control | Meaning | Required |
|---|---|---|
| Provider / Name / Measurement Reference | Approved measurement configuration | Conditional to configure |
| Enabled / Consent Conditions / Scope | Controls active use and allowed context | Conditional |
| Status / Updated | Current configuration state | Display |
| Search / Provider / Status | Filters | Optional |
| Create / Edit / Save / Test / Enable / Disable | Measurement work | Conditional |

A tracking pixel is a configured measurement tool that reports allowed website events to a service. Tag management controls approved measurement tools. Documented capability includes GTM/pixels and tracking settings; GTM means Google Tag Manager, a service for managing website measurement tags.

Proposed fields Provider, Name, Measurement Reference, Enabled, Consent Conditions, Scope; name/provider/reference conditional to configure. Grid Provider/Name/Status/Updated; filters Provider/Status/Search. Buttons Create/Edit/Save/Test/Enable/Disable. No code entry is specified or needed in this business document.

**Workflow:** 1. Select approved provider. 2. Enter authorized reference. 3. Set agreed consent/scope. 4. Test permitted event. 5. Enable with tracking authority. 6. Monitor attribution report. Tracking must reflect agreed privacy/consent governance; exact event/data policy [To be agreed]. No tracking bulk action specified.

| Access | Allows |
|---|---|
| Campaign management | Prepare communication |
| Campaign sending | Tests/scheduling/live sending as granted; detailed test grant [To be agreed] |
| Subscriber read | View allowed subscriber information |
| Tracking management | Measurement configuration |
| Popups/templates/SEO | Detailed grants [To be agreed]; no implied publish authority |

**Business:** consent-aware communication depends on Customers/Segments, approved providers/templates and queue work. Popup/tracking/search metadata affects future website behaviour. Consent granularity, launch channels, audience rules and measurement policy [To be agreed].

## B12. Support

**Purpose/users:** support agents handle tickets, returns and warranty context; managers assign work and track service targets. **Release — Proposed (to confirm):** tickets/assignment/replies/warranty in MVP; richer response-library/queue tuning Phase 2. **Menu/screens:** Support > Tickets/list/detail, Canned Responses, Warranty Claims/Validation, Returns workspace; links proposed.

Tickets unify admin-created cases, email/chat/WhatsApp references, return requests and warranty claims. A reference to a channel is not a promise that every conversation is automatically synchronized.

### Tickets list and detail

Proposed layout: queue/filter panel left, ticket grid or conversation centre, customer/order/warranty context right.

| Field/control | Meaning | Required |
|---|---|---|
| Ticket Number | Unique readable case reference | Display |
| Customer / Order | Related context if present | Optional |
| Channel | Source of issue | Requiredness not stated |
| Status | Open, Pending, Resolved, Closed | Yes |
| Priority | Urgency under agreed rules | Yes; choices [To be agreed] |
| Assignee | Responsible agent | Optional |
| Service Due At | Agreed response/resolution target | Optional/display |
| Subject/Message | Suggested case entry | Proposed required to create meaningful case |
| Queue | Context used by service timing | Supported; editor/filter placement proposed |
| Assign / Reply / Resolve | Documented actions; labels proposed | Conditional |
| Search/Status/Priority/Assignee/Queue/Date | Suggested filters | Proposed optional |

Open means needs attention; Pending means awaiting action/information; Resolved means solution recorded; Closed means finished under closure policy. Due timers are aware of queue and priority, not one fixed deadline for every case.

**Workflow:** 1. Open washer-install issue. 2. Verify customer/order. 3. Assign authorized agent. 4. Check priority/timer. 5. Reply or add relevant context. 6. Route return/warranty work through permitted screens. 7. Resolve/close under agreed policy. Proposed rows Open/Assign/Reply/Resolve; bulk assignment/status changes are not documented and must be confirmed. Replies are documented actions, but actual external send routing and channel integration are [To be agreed].

### Canned Responses — detailed screen Proposed (to confirm)

| Field, column or control | Meaning | Required |
|---|---|---|
| Title / Text | Reusable response identity/wording | Proposed yes |
| Topic / Language / Channel / Status | Appropriate context | Optional/conditional |
| Search / Topic | Filters | Optional |
| Create / Edit / Save / Insert into Reply | Response-library work | Conditional |

A canned response is reusable approved wording that an agent adapts before replying. Documented maintenance capability; proposed grid Title, Topic, Language, Status; Search/Topic filters. Form Title/Text required, channel/language optional. Buttons Create/Edit/Save/Insert into Reply; suggested Active/Retired states.

**Workflow:** 1. Select warranty wording. 2. Insert into reply. 3. Adapt order-specific facts. 4. Review before sending. 5. Update library through authorized process. No canned-response bulk action specified.

### Warranty Claims and validation

| Field/control | Meaning | Required |
|---|---|---|
| Product/Serial | Identifies claimed physical item | Required for stated validation |
| Purchase/Order | Confirms sale | Required validation evidence |
| Coverage Policy | Applicable warranty | Required validation context |
| Prior Claims | Previous claims affecting assessment | Display |
| Claim Description/Evidence | Suggested issue details | Proposed required/conditional |
| Validate Warranty / Record Decision | Documented validation/management, labels proposed | Conditional |
| Search/Product/Serial/Order/Claim Status | Suggested filters | Proposed optional |

**Workflow:** 1. Find combo's washer component. 2. Verify its serial/product/order. 3. Check coverage policy/purchase. 4. Inspect prior claims. 5. Validate. 6. Record outcome and next action. Proposed Submitted, Needs Evidence, Eligible, Not Eligible, In Service, Resolved; exact lifecycle/rejection policy [To be agreed]. Parent combo identity alone is not enough for component warranty. No warranty bulk action specified.

### Returns workspace

| Queue control — Proposed (to confirm) | Meaning | Required |
|---|---|---|
| Return / Order / Customer / Item or Component / Status / Assignee | Linked authoritative return | Display |
| Status / Assignee / Date | Filters | Optional |
| Open Return / Add Context / Route for Inspection | Support coordination | Conditional |
| Return-entry fields | Use B5's return form | As stated in B5 |

Support return-management capability is documented. Proposed queue columns Return Reference, Order, Customer, Item/Component, Status, Assigned Agent; Status/Assignee/Date filters; Open Return, Add Context, Route for Inspection. The authoritative goods/refund workflow is B5, not a second unrelated return.

**Workflow:** 1. Review return ticket. 2. Verify order/component. 3. Create/link the return with permitted grant. 4. Coordinate inspection. 5. Refer financial refund to separately authorized staff. 6. Resolve case after outcome. No support-specific mass return/refund action specified.

| Access | Allows |
|---|---|
| Ticket read/reply/assign | Separate viewing, response and allocation |
| Warranty management | Claims/eligibility validation |
| Support returns management | Return work |
| Order/customer/finance access | Separately needed for detailed context or financial action |

**Business:** future customer support/claim forms create controlled records. Depends on customers, orders, serials, warranties and returns. Queue priorities, service targets, closure rules, warranty coverage and reply-channel contracts [To be agreed].

## B13. Reports

**Purpose/users:** leaders/finance/inventory/customer/marketing analysts obtain detailed reporting under their grants. **Release — Proposed (to confirm):** sales/tax/inventory/store and export runs MVP; customer/attribution/schedules Phase 2. **Menu/screens:** Reports > Report Catalog, Report Parameters/Preview, Report Runs/Results, Scheduled Reports; links proposed.

### Report Catalog and parameters

| Report family | Business question | Proposed fields/filters and requiredness |
|---|---|---|
| Sales | What sold, for how much, and when? | Period required; Branch, Channel, Product, Brand, Category optional; order/sales/units/value columns proposed |
| VAT / eTIMS | What tax was recorded and what fiscal submissions need attention? | Period required; Branch, Tax Class, eTIMS State optional; invoice/net/tax/gross/exception columns proposed |
| Inventory | What is held, reserved, available and low? | Location/Product/Stock State optional; on-hand/reserved/salable/threshold columns from stock model |
| Customer | How are permitted customer relationships performing? | Period, Group, Segment optional; counts/value/consent context proposed and access restricted |
| Marketing Attribution | Which activities receive credit for outcomes? | Period/Channel/Campaign optional; attribution model/columns [To be agreed] |
| Per-store Performance | How do showrooms compare? | Period required; allowed stores optional; sales/orders/service columns proposed |

All report families are documented; precise report definitions and filters are report-specific, not a universal invented set. Proposed catalog columns Name, Description, Available Formats, Required Access; Search/Family filters; Open Report row action.


**Workflow:** 1. Choose VAT/eTIMS report. 2. Enter supported period/branch parameters. 3. Review permitted scope. 4. Choose CSV, XLSX or PDF. 5. Run. Large reports use prepared reporting information so they do not block order work. Unknown filters return a clear error. No catalog bulk action specified.

### Report Runs and results

| Field/control | Meaning | Required |
|---|---|---|
| Report / Parameters | Selected report and scope | Yes |
| Format | CSV, XLSX, PDF | Yes |
| Requested By | User responsible | Display |
| Status | Background work state | Display; labels proposed as A10 |
| Expires At | When output becomes unavailable, if set | Optional/display |
| Run Report / View Progress / Download | Documented capabilities; labels proposed | Conditional |
| Search/Report/Requester/Status/Date | Suggested run-history filters | Proposed optional |

**Workflow:** 1. Submit permitted parameters. 2. Leave while work runs. 3. Reopen run. 4. Inspect completion/error. 5. Download before expiry. 6. Rerun if expired, with current permission checks (proposed expiry behaviour). No bulk-run/delete action specified. Export respects field/branch access.

### Scheduled Reports

| Field/control | Meaning | Required |
|---|---|---|
| Report / Parameters / Format | Report definition | Yes for usable schedule |
| Recurrence / Time | When to run | Proposed required; exact frequency choices unspecified |
| Recipients by User or Role | Approved internal recipients | Required to deliver schedule |
| Status | Suggested Active/Paused | Proposed |
| Create/Edit/Save/Pause | Maintenance labels proposed | Conditional |

Schedules store recipients by user/role, avoiding arbitrary destinations for sensitive data. **Workflow:** 1. Choose weekly showroom sales report (frequency proposed). 2. Set permitted store scope. 3. Select authorized recipients. 4. Save schedule. 5. Review run outcomes. Proposed: recheck recipients' access at execution/delivery; exact recipient semantics [To be agreed]. No schedule bulk action specified.

| Access | Allows |
|---|---|
| Sales/tax/inventory/customer/marketing report read | Independent report-family access |
| Report export | Download permitted output |
| Report scheduling | Recurring runs |
| Store reporting | Grant mapping [To be agreed], never bypass branch restrictions |

**Business:** no direct customer-website effect. Report definitions, attribution model, freshness, downloads' retention/expiry and schedule delivery [To be agreed].

## B14. Users & Roles

**Purpose/users:** authorized identity administrators control who can read and act, where, and with which security measures. **Release — Proposed (to confirm):** users/roles/branch scope/two-factor/sessions/audit MVP; enforced company sign-in Phase 2 subject to identity provider. **Menu/screens:** Users & Roles > Admin Users/editor, Roles/editor, Permission Review, Sessions, Audit Trail; links proposed; contextual Reset Two-factor Authentication.

Role-based access means a user's role determines allowed actions. Two-factor authentication requires a second identity check beyond a password. Single sign-on lets staff use an approved company identity instead of a separate dashboard sign-in.

### Admin Users

| Field/control | Meaning | Required |
|---|---|---|
| Email | Unique administrator identity | Yes |
| Status | Active / Disabled | Yes |
| Roles | Assigned access packages | Requiredness not stated; proposed required for useful access |
| Two-factor Enabled | Whether second-factor setup exists | Display |
| Create/Edit/Save/Disable | Documented maintenance, labels proposed | Conditional |
| Reset Two-factor | Authorized recovery action | Documented |
| Search/Status/Role/Two-factor | Suggested filters | Proposed optional |

**Workflow:** 1. Create staff identity. 2. Assign minimum agreed roles/branches. 3. Require second factor if privileged. 4. Activate. 5. Review access. 6. Disable/revoke sessions when appropriate. Active means eligible identity; Disabled means prohibited administrative use. Bulk user changes are not documented. Recovery identity proof and approval [To be agreed].

### Roles and Permission Review

| Field/control | Meaning | Required |
|---|---|---|
| Role Name | Unique access-package name | Yes |
| Module / Action choices | Human-readable Read, Create, Edit, Delete, Bulk, Import, Export, Manage, Publish or relevant action | Yes for usable role |
| Branch Scope | All or Selected branches | Yes |
| Selected Branches | Allowed branch list | Conditional |
| Two-factor Requirement / Company Sign-in Enforcement | Role security policy | Privileged second factor mandatory; sign-in enforcement supported |
| Save Role / Review Effective Access | Save and suggested consolidated review | Conditional; review label proposed |
| Affected Users | Suggested impact preview | Proposed display |

Proposed layout: module tree left, action checkboxes centre, branch/security panel right. Separate categories/brands/content/location grants remain visible. Product management never implies attribute management.

**Workflow:** 1. Open Showroom Manager role. 2. Grant agreed order/stock actions. 3. Select allowed branches. 4. Review financial/customer restrictions. 5. Set required security. 6. Save. 7. Audit effects on current users. Actual data queries enforce branch scope; hiding a menu/filter is not enough. Combined-role scope and conflicts [To be agreed]; no undocumented permission codes appear in this document.

Permission Review is a proposed presentation of documented permission resources. Suggested columns Module, Resource, Action, Granted Through Role, Branch Scope; User/Role/Module filters. Read-only review cannot grant itself new rights. No role/permission bulk action specified.

### Sessions

| Session control — presentation Proposed (to confirm) | Meaning | Required |
|---|---|---|
| User / Start / Last Activity / Device / Network Origin / Status | Sign-in context | Display |
| User / Date / Active | Filters | Optional |
| Revoke Session | Stops selected session immediately | Selected session required |
| Revoke All | Suggested wider action | Proposed; not established |

Documented session listing/revocation. Proposed fields User, Started, Last Activity, Device/Browser, Network Origin, Status; filters User/Date/Active; row Revoke Session. Required session selection for revocation. Revocation is immediate. Mass Revoke All is proposed, not a supplied capability.

**Workflow:** 1. Find compromised or departed user's session. 2. Check identity/context. 3. Revoke. 4. Verify access stops. 5. Audit relevant action. Proposed Active/Revoked/Expired labels; expiry policy [To be agreed].

### Audit Trail — screen presentation Proposed (to confirm)

| Field or control | Meaning | Required |
|---|---|---|
| Actor / Time / Branch / Record Type and Name / Action | Event identity/context | Display |
| Before / After / Investigation Reference | Permanent change evidence | Display; secrets excluded |
| Actor / Time / Branch / Action / Record filters | Narrows investigation | Optional |
| View Details | Inspect evidence | Optional |
| Export | Suggested restricted output | Grant/policy [To be agreed] |

Permanent audit events are documented; a searchable workspace is proposed. Columns Actor, Time, Branch, Record Type, Record Name, Action, Before/After Summary, Investigation Reference; filters Actor/Time/Branch/Action/Record. Row View Details; export only if separately agreed. Events are not editable/deletable. Secret values remain excluded.

**Workflow:** 1. Investigate a price or refund change. 2. Filter record/time/branch. 3. Inspect actor and before/after. 4. Use request/device references through authorized investigation. 5. Follow linked evidence. Viewing access and retention [To be agreed]; ordinary dashboard reads are not treated as mutation events.

| Access boundary | Rule |
|---|---|
| Identity/roles/sessions | Only separately authorized administrators; detailed grants not supplied |
| Privileged roles | Second-factor authentication required |
| Company sign-in | Can be enforced by role |
| Branch scope | Applies to actual records, exports and direct screens |
| Product vs attributes | Independent |
| Categories, brands, CMS, Store Locator | Independent |
| Role change | Effects on current users audited |

**Business:** no direct customer-website effect. Least-needed role packages, security recovery, combined roles, cross-branch visibility, sign-in provider and audit-view access [To be agreed].

## B15. Settings

**Purpose/users:** authorized administrators and specialist owners configure operation; access is separately granted for each section and separately for read versus manage. **Release — Proposed (to confirm):** launch-critical general/payment/shipping/tax/security/localization/integration configuration plus monitoring in MVP; refined tooling/maintenance operations Phase 2; advanced developer utilities Phase 3. **Menu/screens:** all section names in the table below, plus API Key Editor, Integration Detail, Webhook Subscription/Delivery Logs, Queue Job Detail. Screen links and detailed field arrangements are proposed where source only lists sections.

### Shared settings layout

| Left | Main | Right |
|---|---|---|
| Section navigation | Labelled settings/current values | Proposed impact/help panel |
| Current section | Configured indicators, masked credentials | Warning/validation |
| Available sections | Save/Cancel (proposed labels) | Change/audit summary proposed |

Ordinary configured secrets show only Configured, Masked Value and Updated At. Secrets are encrypted when stored, cannot be revealed later, and never enter audit/log summaries. Rotation supports an overlap period only where the provider permits it. API access credentials are an exception only at creation: the new key is shown once and cannot later be retrieved.

### Every settings section

Detailed entry labels below are **Proposed (to confirm)** unless explicitly identified as documented. Each section offers read-only inspection to its read role and Save to its manage role. Common conflict/validation/audit rules apply. List-based sections use A5; singleton forms do not need row selection or pagination.

| Screen | What staff see / fields | Requiredness and actions | Workflow example |
|---|---|---|---|
| General | Proposed retailer identity/contact, KES default, VAT-inclusive default, Nairobi time | Core currency/tax/time documented; exact editable fields [To be agreed]; Save proposed | 1. Review identity. 2. Confirm business defaults. 3. Save allowed change. |
| Payment Gateways | Enabled M-Pesa/cards/bank/POD/approved optional methods; masked configured credentials; proposed provider/environment/status | Credentials conditional for configured provider; Save/Enable/Disable/Test Connection proposed | 1. Select M-Pesa. 2. Enter authorized new credentials. 3. Test. 4. Enable under agreed provider contract. |
| Shipping Providers | Provider configuration/enabled status; masked credentials where relevant | Conditional credentials; Save/Test proposed | 1. Select carrier. 2. Configure agreed connection. 3. Test. 4. Verify Logistics availability. |
| Tax / eTIMS | Supported tax classes and integration state; proposed fiscal-business/provider settings | Tax reasons conditional; provider-required values [To be agreed]; Save/Test proposed | 1. Confirm approved provider/mode. 2. Configure. 3. Test compliance submission. 4. Check exceptions in Finance. |
| Email / SMS | Message-provider configuration and masked credentials; proposed approved sender identities | Conditional provider values; Save/Test proposed | 1. Select provider. 2. Set approved identity. 3. Test. 4. Verify Marketing readiness. |
| API Keys | Documented Name, Allowed Functions, Allowed Network Addresses, Last Used, Expiry; credential shown once on creation | Name/allowed functions needed; network restrictions supported, mandatory policy [To be agreed]; expiry optional; Create/View/Revoke proposed labels | 1. Define approved integration need. 2. Restrict access/network/expiry. 3. Create. 4. Store once-shown key securely. 5. Review usage. |
| Integrations | Connection records, configured credentials, operational outcomes; detailed columns proposed | Conditional connection configuration; Save/Test proposed | 1. Select approved external system. 2. Agree authority. 3. Configure/test. 4. Review failure state. |
| Webhooks | Documented event subscriptions, destination, signing secret, status, retry policy; delivery history | Event/destination/signing configuration needed; policy values [To be agreed]; Test/Replay documented | 1. Subscribe approved receiver. 2. Sign/test notification. 3. Review attempts. 4. Replay eligible failure. |
| Storage / Media Delivery | Asset-storage/delivery configuration, masked credentials; proposed readiness view | Conditional connection settings; Save/Test proposed | 1. Configure approved service. 2. Test media delivery. 3. Review renditions in Media Library. |
| Search | Search configuration, product/attribute update readiness; proposed indexing health/rebuild controls | Fields/actions not specified; Save proposed; Rebuild requires confirmation as proposed high-impact action | 1. Review stale-search signal. 2. Investigate catalog changes. 3. Run agreed recovery. 4. Verify results. |
| Cache | Temporary saved-information configuration/health; proposed selective refresh | Exact settings not specified; Save/Refresh proposed | 1. Review stale-form information. 2. Check attribute change. 3. Use agreed refresh. 4. Verify current form. |
| Queue Monitoring | Background work, delays/failures/provider failures; detailed job panel below | Display; Retry only supported safe tasks; exact manual controls [To be agreed] | 1. Inspect stuck import/eTIMS delivery. 2. Open detail. 3. Resolve cause. 4. Retry safely. |
| Security | Privileged second-factor/company sign-in policies; proposed session/security configuration | Documented security mandates apply; exact fields [To be agreed] | 1. Review privileged role protection. 2. Configure permitted enforcement. 3. Verify sign-in/revocation behaviour. |
| Notifications | Proposed event subscriptions/recipients/channel preferences | Fields/routing not specified; Save proposed | 1. Select low-stock/job/compliance event. 2. Set approved recipients. 3. Test agreed delivery. |
| Localization | English-first/Swahili-ready labels, currencies/time presentation | KES/Nairobi baseline documented; exact translation controls proposed | 1. Review English labels. 2. Add approved Swahili labels where supported. 3. Check forms/options. |

| Feature Flags | Documented selectable feature enablement; proposed Name/Scope/Enabled/Version | Flag/scope conditional; Save/Enable/Disable proposed | 1. Choose approved feature. 2. Review affected users/storefront. 3. Change agreed scope. 4. Verify. |
| Backup / Maintenance | Backup/maintenance configuration; proposed last result/restore context/maintenance status | Schedules and recovery authority [To be agreed]; Save/Test Restore/Enter Maintenance proposed high-impact actions | 1. Review backup outcome. 2. Investigate failure. 3. Follow agreed recovery. 4. Communicate maintenance. |
| Theme | Appearance configuration with light/dark contrast | Exact business-theme fields not specified; Preview/Save proposed | 1. Review colours/contrast. 2. Preview both modes. 3. Save approved configuration. |
| Developer Tools | Specialist operational diagnostics; safe request references, response/error/queue signals | Exact functions not specified; authorized read/manage only | 1. Select diagnostic context. 2. Follow safe investigation reference. 3. Resolve issue without exposing secrets. |

A webhook is an automatic signed notice sent to an approved external receiver when a business event happens. Signing lets the receiver check the notice's authenticity. A content-delivery network is a service that delivers media efficiently; “Media Delivery” is the business-facing label here. Cache means temporary saved information used for faster display; clearing it does not delete the authoritative product/order record.

### API Keys

| List/editor control — presentation Proposed (to confirm) | Meaning | Required |
|---|---|---|
| Name / Allowed Functions | Integration identity and permitted work | Needed for usable key |
| Allowed Network Addresses | Network restrictions | Supported; mandatory policy [To be agreed] |
| Expiry / Last Used / Status | Lifetime/usage | Optional/display |
| Search / Status / Expiry | Filters | Optional |
| Create / View Metadata / Revoke | Credential lifecycle | Conditional |
| Once-shown New Key | Usable credential only at creation | Display once; no later reveal |

Documented fields are shown in the settings table. Proposed list columns Name, Allowed Functions, Network Restrictions, Expiry, Last Used, Status; filters Search/Status/Expiry. Proposed Active/Expired/Revoked states; exact status vocabulary not supplied. Create returns the usable credential once; later detail shows metadata only. No Reveal Secret button exists. Rotation uses a new credential with provider-permitted overlap, not retrieval of the old secret.

**Workflow:** 1. Name the approved connection. 2. Limit functions. 3. Restrict approved network addresses. 4. Set expiry if agreed. 5. Create. 6. Capture credential once using agreed secure process. 7. Review Last Used. 8. Revoke if no longer needed. No bulk credential creation/reveal specified.

### Integrations

| Detailed control — Proposed (to confirm) | Meaning | Required |
|---|---|---|
| Name / Provider or System / Configured / Status | Connection readiness | Display/conditional configuration |
| Last Success / Last Failure / Masked Credential Indicators | Operational health | Display |
| Search / System / Status | Filters | Optional |
| Open / Edit / Test / Save | Connection work | Conditional |

Documented integration maintenance; detailed screen Proposed (to confirm): Name, Provider/System, Configured, Status, Last Success/Failure, Masked Credential indicators; Search/System/Status filters; Open/Edit/Test/Save. Proposed Not Configured, Ready, Degraded, Disabled states.

**Workflow:** 1. Decide what accounting system owns stock/cost/invoices. 2. Configure only after authority is agreed. 3. Test permitted data exchange. 4. Review failures. 5. Rotate secrets safely. Integration connection alone must not establish business authority that the sources leave unresolved. No integration bulk action specified.

### Webhook subscriptions and delivery logs

| Field/control | Meaning | Required |
|---|---|---|
| Event | Business trigger | Yes |
| Destination | Approved receiver address | Yes |
| Signing Secret | Credential used to authenticate notices | Conditional configuration; later masked |
| Status | Subscription state | Yes; exact labels unspecified |
| Retry Policy | Agreed repeat-attempt rules | Required configuration |
| Delivery Status | Outcome of an attempt | Display |
| Delivery Time Taken | Speed of attempt | Display |
| Attempt Count | Number of tries | Display |
| Test | Checks receiver | Documented |
| Replay | Creates a new signed attempt | Documented |
| Search/Event/Status/Date | Suggested filters | Proposed optional |

**Workflow:** 1. Create approved subscription. 2. Choose event/destination. 3. Configure signing/retries. 4. Test. 5. View failed delivery. 6. Resolve receiver problem. 7. Replay; new signed attempt is recorded. Replays must not silently duplicate downstream business effects. Proposed Pending/Delivered/Failed/Retrying delivery labels; retry limits [To be agreed]. Bulk replay is not documented.

### Queue Monitoring and job detail

| Detailed control — Proposed (to confirm) | Meaning | Required |
|---|---|---|
| Job / Type / Submitted / State / Processed | Work identity/progress | Display |
| Queue Delay / Attempts / Provider Reference / Error | Delay and failure context | Display; safe references only |
| Type / State / Date | Filters | Optional |
| View Details / Eligible Retry | Investigation/recovery | Conditional |
| Results / Download | Job-specific permitted output | Conditional; A10 rules |

Proposed columns Job/Type/Submitted/State/Processed/Queue Delay/Attempts/Provider Reference/Error; filters Type/State/Date. Progress/results use A10. Permitted actions View Details and eligible Retry; Pause Queue/Cancel/Requeue All are not established and require explicit operational agreement.

**Workflow:** 1. Filter failed catalog imports or fiscal retries. 2. Open safe error explanation. 3. Check provider/queue condition. 4. Resolve cause. 5. Retry only with duplicate-safe handling. 6. Confirm result. Retrying delivery does not change immutable invoice evidence or undo successful publication.

### Settings states, errors and role access

Documented configured/unconfigured indicators and masked values describe credential readiness. Other section/provider states are as supplied or explicitly proposed. Validation rejects invalid configuration; errors contain safe references. High-impact changes use explicit confirmation. No universal settings bulk action is documented.

| Access | Meaning |
|---|---|
| Read a settings section | Inspect that section's allowed values/status |
| Manage a settings section | Change that section, not all Settings |
| Credentials | Configure/rotate when granted; never reveal stored secret |
| API key creation | One-time display only |
| Monitoring | Detailed action grants [To be agreed] |
| Identity/security | Must align with separate Users & Roles responsibilities |

**Business:** payment/shipping enablement, language, search, feature flags and approved public integration settings can change the future website through controlled configuration revisions. Secret storage uses centrally managed encryption. Backup/recovery schedules, providers, maintenance communication, monitoring recipients and operational powers [To be agreed].

# Part C — Connected business journeys and decisions

## C1. Selling and servicing a TV + soundbar + washer combo

1. The catalog manager creates one Simple combo with linked component quantities and price allocation. No third product type is introduced.
2. Inventory verifies each component at the intended showroom/warehouse. Any required component shortage makes the combo unavailable unless explicit split handling is enabled.
3. An authorized showroom user creates a draft order or converts an accepted quote. Original item prices, tax classes, applied discounts, components and allocations are preserved at order creation.
4. Logistics rules determine free or charged delivery and installation. A specialist paid requirement remains visible even if a broad free-installation offer exists.
5. M-Pesa prompt creates Pending payment. Verified confirmation resolves it. A timeout prompts querying/reconciliation, not an automatic failed-payment conclusion.
6. Fulfilment reserves/deducts each component, captures relevant serials and records partial shipment if only part is sent.
7. Delivery/installation appointments are recorded. POD collection remains a separate state from physical handover.
8. eTIMS submission records immutable fiscal evidence. A failure creates a visible retryable compliance exception while the order remains valid.
9. If only the soundbar returns, staff inspect that component and calculate its refund ceiling from the original allocation and actual captured funds.
10. Support assesses any washer warranty claim against that washer's serial/product/order/coverage/prior claims.
11. Reports and dashboard revise affected periods when delayed payments or refunds arrive. Nothing rewrites the historical commercial agreement.

## C2. Publishing a free-delivery campaign

1. Logistics defines the actual eligible delivery/install conditions and tests them in the simulator.
2. Promotion staff define offer conditions, exclusions, priority, stacking and schedule, then simulate competing offers.
3. Content staff build approved banner/homepage/menu content and review every placement affected by reusable-block changes.
4. Publishers release the promotion/content under their separate authority. Background activation and website refresh are tracked.
5. Marketing selects a segment but excludes unsubscribed contacts, uses provider-approved WhatsApp templates where required, tests and schedules communication.
6. Marketing attribution and store reports measure outcomes under agreed definitions; no invented performance statistics appear.
7. If page refresh or message delivery fails, authorized staff investigate and retry the relevant background task without duplicating charges, orders or successful publication.

## C3. Handling an access/export or deletion request

1. Privacy staff record the customer's request and verify identity.
2. They inspect necessary retained commercial/tax/fraud records, under the Kenya Data Protection Act 2019 workflow and the agreed retention policy.
3. Access/export returns permitted customer information through the controlled process.
4. Deletion removes or anonymizes eligible identifying information while legally necessary records remain.
5. Consent changes include source/time; marketing suppresses opt-outs.
6. The request reaches Completed or Rejected with a documented outcome. Identity is not auto-merged based only on phone number.

## C4. Proposed release discussion, not an approved schedule

| Phase | Proposed business emphasis | Estimate |
|---|---|---|
| MVP | Core catalog/templates, showroom stock, order/payment/service handling, privacy, fiscal exceptions, launch logistics/offers/content, essential marketing/support/reports, secure access/settings | Dates/cost/effort [To be estimated] |
| Phase 2 | Richer purchasing/supplier operations, segmentation/recovery, additional agreed payments, advanced reporting/schedules and content/marketing refinements | [To be estimated] |
| Phase 3 | Agreed loyalty/referral expansion, optional later channels and specialist operational tooling | [To be estimated] |

Modules contain all documented capabilities regardless of proposed phase. Phasing does not remove requirements. Provider contracts, policy readiness and business priority determine the final sequence.

## C5. Decisions to confirm

| Decision | Why it matters | Current position |
|---|---|---|
| Accounting/enterprise platform and authority for stock, cost, invoices | Prevent competing truth between systems | Source open question; [To be agreed] |
| KRA eTIMS mode/provider | Determines fiscal configuration/evidence/retries | Source open question; [To be agreed] |
| Cross-branch manager access to customers/orders | Determines scope throughout lists/search/export | Source open question; [To be agreed] |
| Card aggregator and BNPL providers | Determines actual launch payment methods | Source open question; [To be agreed] |
| Recommended commercial combo vs promotion-only assembly | Determines combined identity/returns/warranty handling | Commercial Simple combo recommended by source; approval [To be agreed] |
| Net/gross sales, conversion, average order value and combo unit definitions | Makes leadership reports interpretable | [To be agreed] |
| Required catalog content, measured units and activation criteria | Prevent incomplete offers | [To be agreed] |
| Special-price exceptions and tier/special interaction | Prevent conflicting prices | [To be agreed] |
| Returns, cancellation, exchange and stock disposition policies | Determines allowed transitions/refunds | [To be agreed] |
| Installation/delivery rules and targets, specialist work, pickup capacity | Defines operational promises | [To be agreed] |
| Privacy retention and verification policy | Preserves rights and lawful records | [To be agreed] |
| Role packages, privileged recovery and approvals | Makes access boundaries practical | [To be agreed] |
| Job commitment, retry/cancellation, output retention and expiry | Determines safe large operations | [To be agreed] |
| Gift-card/loyalty/referral rules | Defines balances, rewards, liability and anti-abuse | [To be agreed] |
| Marketing channel contracts, consent detail, tracking/attribution rules | Determines allowed sending/measurement | [To be agreed] |
| Backup/recovery/maintenance procedures | Determines continuity and safe recovery | [To be agreed] |
| Release sequencing, delivery effort and budget | Converts proposed scope into plan | [To be estimated] |

## C6. Source coverage record

This table is a traceability aid, not a reproduction of the source structure.

| Source | Business coverage here |
|---|---|
| README | Reader's guide/foundations; scope; KES/tax/time; audit/history; growth assumptions; open decisions |
| Navigation and Admin UX | Part A; complete menu; builders/layouts; accessibility; standard list/forms; scope and dark mode |
| Dashboard | A4 and B1; every metric/filter/screen; widget order; background export; late-event reporting |
| Product module | B2; types, pricing/badges, variations, combos, categories/brands, moderation, files, clone/history |
| Attribute engine | B3; all input types/flags/options/groups/sets/Base, membership, changes, retention/retirement and variation validation |
| Inventory | B4; stock, movements, transfers, buying/suppliers, serials, holds/alerts and combo availability |
| Orders | B5; all lifecycle states, drafts/quotes, partial shipments, returns/refunds/exchanges, scheduling/pickup/POD/warranty/invoices |
| Customers | B6; identity/contact/address, relationship summaries, grouping/segments/carts, consent/privacy/merge |
| Payments and Finance | B7; all payment methods, transaction types, evidence, reconciliation/settlements/refunds/disputes/tax/eTIMS |
| Shipping | B8; zones/rates/simulator, fleet/carriers/labels, pickup/install/tracking, free/paid and fallback rules |
| Promotions | B9; coupons/cart/catalog/campaign/combo/gift-card/loyalty/referral; stacking/exclusions/allocations/activation |

| CMS | B10; every sidebar destination; preview/version/publish/delivery; menu/block/media/redirect safeguards |
| Marketing | B11; all channels, subscribers/templates/popups/SEO/tracking; consent, throttling and provider approval |
| Support | B12; tickets/replies/assignment/timers, canned responses, component warranty checks/returns |
| Reports | B13; every family, formats, runs/expiry/schedules, role recipients and restricted output |
| Users and Roles | B14; actions/scope/separation, privileged two-factor/company sign-in/sessions/audit |
| Settings | B15; every section, configured secrets, one-time keys, subscriptions/delivery/replay and monitoring |
| Consistency check | Foundations/Part A and all module rules: only Simple/Variable, central specifications, snapshots, scope, component stock, separated money/goods, retryable compliance and versioned content |


