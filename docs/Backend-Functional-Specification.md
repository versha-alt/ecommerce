# E-commerce Platform: Backend Functional Specification
## Business Edition - Global Requirements for an AI Builder

# Page 1 - Purpose and platform-wide requirements

## What must be created

Create a global e-commerce platform for selling appliances and other merchandise online and through stores, showrooms and warehouses. This document is the business brief for the AI creating the site. It specifies required information, behaviour, relationships and acceptance conditions, without prescribing visual layouts or implementation code.

The scope has five modules: **Category, Product, Order, Customer and Settings**. Category is independently managed; Product references its hierarchy. Product includes Brand and Attribute management. Attribute management includes Attributes, Attribute Groups and Attribute Sets. Inventory, payments, delivery and other supporting capabilities must serve these five modules rather than introduce competing sources of business information.

**R01 - Global operation.** Do not hard-code a country, currency, tax rate, language, time zone, payment provider or shipping provider. Administrators must establish the operating markets through Settings. A country-specific service can apply only to its configured markets. Examples are illustrative business examples, not mandatory operating countries or providers.

| Module | Business responsibility |
|---|---|
| Category | Category hierarchy, category information, membership rules and navigation organisation |
| Product | Brands, attribute definitions and templates, products, variants, prices, merchandise availability and stock |
| Order | Quotes and orders, transaction snapshots, stock reservations, payment records, invoices, fulfilment, returns and refunds |
| Customer | People and business accounts, addresses, groups, preferences, consent and customer history |
| Settings | All platform configuration, applicability rules, enabled services, integrations, permissions and operational policies |

**R02 - Single control center.** Settings governs how the platform operates. Administrators enable or disable options, configure them, and select applicable countries, currencies, stores and customer groups. Category, Product, Order and Customer remain the places where their business records are maintained. Configuration must not be duplicated inside those records as separate platform policies.

## Shared integrity and access

**R03 - Protect business history.** Give every record a permanent reference. Record who changed it, when, what changed, and the previous and new values. Detect concurrent changes and explain conflicts before accepting an overwrite. Retire records referenced by orders instead of deleting their history. Preserve the currency, prices, tax treatment, address, product description and applicable configuration captured when an order was placed. Changes to Settings must not rewrite past transactions.

**R04 - Controlled operation.** Roles determine which records a person may see and which actions they may perform. Store or showroom restrictions must also apply to searches, reports, exports and background work. Give catalog staff product authority, store staff relevant stock and fulfilment authority, finance staff financial authority, and support staff customer-service authority. Separately grant Settings administration, sensitive-data access and refund approval.

Repeated submissions or repeated external confirmations must not create duplicate orders, payments, refunds or stock movements. Background work must show progress, completed results and understandable failures. Missing configuration must produce a clear explanation rather than silently substitute a country or provider.

**Build acceptance:** demonstrate a second country, currency, language and time zone being configured without changing the site implementation.

# Page 2 - Category module and Product structure

## Organising the merchandise

**R05 - Separate Category module.** Manage a two- or three-level hierarchy: parent, child and optional sub-child, such as Appliances > Laundry > Washing Machines. Each non-root category has one parent. Prevent cycles and moves that exceed three levels. Hold Name, Description, Parent Category, Image, Active Status, Display Order, Search Title, Search Description, Public Address and Redirect History. Product uses category references maintained here. Manual assignments require explicit admin selection. Optional rule-based membership must be separately enabled and identified; it must not silently pre-select product checkboxes. Allow category-specific sorting, customer filters and navigation inclusion. Changing a public address must preserve an appropriate redirect from the previous address.

**R06 - Product category selection and Brand.** Show all categories as checkboxes in their parent-child-sub-child hierarchy. No category is pre-selected when creating a product. The admin explicitly selects every relevant category; checking a child must not automatically check its parent or siblings. Saved selections appear when editing. **Proposed (to confirm):** drafts may have no category, but publication requires at least one.

**Brand.** A Brand identifies the manufacturer or commercial brand. Hold Name, Description, Logo, Website, Active Status, Display Order, Search Title, Search Description and Public Address. Associate products with a Brand; retiring a brand must preserve existing product and order references. Translated descriptions and labels must follow enabled languages.

## How Attributes, Groups and Sets relate

**R07 - Three distinct responsibilities.** An **Attribute** defines one piece of product information, such as Screen Size. An **Attribute Group** organises related attributes under a meaningful heading, such as Display Specifications. An **Attribute Set** is a complete product-information template, such as Television.

| Concept | Example and relationship |
|---|---|
| Attribute | Screen Size defines the value type, unit and rules; individual products supply values such as 55 inches |
| Attribute Group | Display Specifications places Screen Size, Resolution and Panel Type together in a useful order |
| Attribute Set | Television combines relevant groups and their attributes into the template used by television products |
| Product | Selects one Attribute Set; that set determines which additional details must be supplied |

A set contains ordered groups and their assigned attributes. The same attribute definition can be reused across sets without being recreated. Group placement and ordering belong to the set's arrangement; moving an attribute between groups must not change its stored product values.

Create Screen Size once, choose inches as its unit, and assign it to Television under Display Specifications. Create Capacity once with kg as its unit and assign it to Washing Machine under Laundry Specifications. Neither attribute should automatically appear in unrelated sets. Support Television, Washing Machine, Refrigerator, Air Conditioner, Small Appliance and Default templates, and allow administrators to manage further templates.

**R08 - Attribute information.** Maintain Label, Permanent Code, Input Type, Unit, Default Value, Validation Rules, Required, Unique, Scope, Searchable, Filterable, Comparable, Show on Product Page, Use in Promotion Rules, Use for Sorting and Use for Variations. Maintain System or User-defined designation and Translated Labels. Choice options require their own labels, order and optional colour or image swatch.

Support short text, long text, whole number, decimal number, yes/no, single choice, multiple choices, date, price, file or image, and visual swatches. Use choices for consistent comparisons and customer filters; use numbers where range filtering is needed. Required values must be supplied; unique values must not duplicate another relevant record.

Protect system-required attributes from deletion. Allow permitted reordering without removing required information. Used attributes must be retired or deliberately migrated. **Proposed (to confirm):** when changing a product's set, retain unmatched values privately for recovery, map compatible values, and require missing new values before publication. Attribute-definition changes must refresh affected product validation and search filters.

# Page 3 - Product: types, variants, pricing and stock

## Exactly two product types

**R09 - Product information.** Support **Simple** and **Variable** products only. A Simple product is a directly sellable item. A Variable product is a parent containing child products called variants, such as one washer model offered in different capacities and colours. The parent organises the family; the selected child is what the customer buys.

Maintain Product Name, Stock Code, Type, Attribute Set, Brand, Categories, Short Description, Full Description, Images, Videos, Active Status, Publication Status and Visibility. Include Regular Price, Special Price, Special Price Start and End, Cost, Quantity-based Prices, Tax Class, Weight, Dimensions, Shipping Class and Customs Classification where relevant. Include Warranty Duration, Warranty Terms, Serial-number Requirement, Search Title, Search Description, Public Address and related, alternative and complementary product links. Attribute-set values complete the product information.

**R10 - Variant behaviour.** Only suitable single-choice attributes marked Use for Variations may define variants. Require at least one variation attribute. Generate candidate combinations, allow unwanted combinations to be excluded, and prevent duplicate combinations.

| Information | Parent and child ownership |
|---|---|
| Shared identity | Children normally inherit name context, descriptions, Brand, Categories and Attribute Set |
| Variation values | Each child owns its selected combination, such as 8 kg and White |
| Commercial details | Each child owns a unique Stock Code, applicable prices, stock, availability and serial records |
| Optional differences | Allow child-specific imagery, weight, dimensions and other explicitly permitted overrides |

Show the parent price range or lowest eligible “from” price using its available children. Calculate parent stock totals from children without creating extra parent stock. A child must not sell when its own available quantity is insufficient. Changes to shared information must not overwrite explicit child overrides.

## Prices and merchandise operations

**R11 - Configured pricing.** Support price records for the enabled currencies and applicable stores or customer groups. Never assume one universal price converted automatically. Settings defines currency precision, rounding, exchange-rate sources, tax treatment and whether prices include tax.

Scheduled special prices apply only within their configured period and relevant time zone. The active selling price must follow a documented precedence between special prices, group prices, quantity tiers and discounts. **Proposed (to confirm):** reject conflicting equal-priority rules until an administrator resolves them.

Illustrative example: a Regular Price of 1,000 and Special Price of 900 saves 100, or 10%, in the selected currency. An illustrative configured tax rate of 10% gives tax of 10 on a tax-exclusive price of 100, making a total of 110. These are sample numbers, not a country's prescribed tax rate.

**R12 - Stock, offers and bulk changes.** Track physical, reserved and available quantities by location; available stock excludes reservations. Record receipts, adjustments, transfers and deductions with reasons and history. Maintain stock alerts and serial-number tracking where required.

A TV + soundbar + washer offer must not introduce a third product type. **Proposed (to confirm):** represent it as a Simple offer with linked sellable components. Prices of 500, 100 and 400 total 1,000; an offer price of 900 saves 100, or 10%. Reserve and deduct each component at its fulfilment location, retaining component serials and warranties.

Support draft, active and retired merchandise states, version history, cloning, manual or scheduled badges, and imports/exports. Imports require validation, preview, update-by-stock-code rules, row-level errors and job history. Invalid rows must not corrupt accepted product records.

# Page 4 - Order: commercial lifecycle and fulfilment

## What an order holds

**R13 - Order information.** Hold Order Reference, Customer, Guest Details where allowed, Country, Store, Currency, Language, Business Time Zone, Billing Address, Delivery Address, Contact Details, Customer Group and Order Source. Hold order lines with Product or Variant, Stock Code, Description, Quantity, Unit Price, Discounts, Tax Class, Tax Rate, Tax Amount and Line Total. Capture subtotal, delivery and installation charges, order discount, tax total, final total, payment method, delivery method, notes and relevant timestamps.

Snapshot the agreed commercial information. Later product edits, exchange-rate changes or tax-setting changes must not alter an accepted order. Link payments, invoices, reservations, shipments, serial numbers, returns and refunds to the order. Keep payment status separate from order and fulfilment status.

**R14 - Independent status models.** Every transition must be authorised and recorded. The following lifecycle labels are **Proposed (to confirm)** where the original rules do not establish a final naming convention.

| Lifecycle | Statuses and meaning |
|---|---|
| Order | Draft: incomplete; Pending: awaiting required action; Confirmed: accepted; Processing: preparation underway; Completed: obligations satisfied; Cancelled: stopped |
| Payment | Unpaid; Pending; Partially Paid; Paid; Failed; Partially Refunded; Refunded, based on verified financial evidence |
| Fulfilment | Unfulfilled; Reserved; Picking; Packed; Dispatched; Partially Delivered; Delivered; Installation Pending; Installed; Returned, based on actual operations |

An order may be Paid while still Unfulfilled. One delivered line must not mark the whole order Delivered. A pending or failed payment must not be treated as paid. Cancellation must follow the configured policy and reverse reservations without duplicating reversals.

## Main order process

**R15 - When an order is submitted**, the system must validate product availability and quantities, establish the applicable market and Settings rules, calculate prices and tax, snapshot the agreement, reserve stock according to policy and start the selected payment process. Hold a reservation expiry where required. Release expired or cancelled reservations safely; convert reservations to deductions at the configured fulfilment event.

An order containing an offer must reserve its underlying components. If one component is unavailable, explain the unavailable item and follow the configured partial-availability policy. Do not silently replace merchandise. Transfers between locations need dispatch and receipt confirmation so stock is not counted twice.

**R16 - Delivery, returns and quotes.** Support separate shipments, delivery appointments, collection, installation bookings, proof of delivery and actual completion times. Free delivery or installation must be an applicable configured rule, not a universal promise.

Quotes must hold agreed items, quantities, prices, expiry, customer and approval status. A hotel quote becoming an order must recheck availability and applicable rules and clearly identify any changed commercial terms.

Returns require original order lines, quantities, reason, condition, approval, received location and refund outcome. Returned stock must be inspected before becoming sellable. Component returns must use the allocated component value rather than refunding the whole offer automatically. Serial numbers link delivered items to warranty history.

## Financial and integration safeguards

**R26 - Verified payment flow.** When payment starts, record the amount, currency, order, selected provider and unique attempt reference. Send the request, guide the customer through the provider's required confirmation, then await verified evidence. A successful customer interaction alone must not mark an order Paid.

For a delayed response, retain Pending and check according to configured policy. Timeouts and failures must be understandable and safely retryable. Repeated confirmations must not credit the order again. Manual confirmation requires evidence and authorised approval. Refunds need an eligible original payment, reason, amount, approval and verified outcome. Reconciliation compares provider evidence with platform records and exposes discrepancies.

Issue fiscal invoices through the configured applicable service, preserve invoice references and track failures. Never lose a paid order because an invoicing integration is temporarily unavailable. Retry without issuing duplicates.

# Page 5 - Customer: accounts, groups and protection

## Customer information and relationships

**R17 - Customer records.** Support individual customers, guest purchasers where enabled, and business customers. Hold Customer Reference, Name, Email, Telephone, Account Status, Account Type, Company Details where applicable, Customer Group, Preferred Language, Preferred Currency, Preferred Time Zone, Marketing Preferences and Notes. Addresses need recipient, contact, address lines, locality, region where applicable, postal code where applicable, and Country. Address requirements must vary through Settings rather than assume one country's format.

A customer can have multiple addresses, orders and enquiries. Support primary billing and delivery addresses. Link business contacts to their organisation without mixing separate people's consent. Keep commercial history available to authorised staff. Customer preferences guide presentation; they must not override destination tax, payment eligibility or other applicable commercial rules.

**R18 - Groups and access.** Customer Groups support business distinctions such as Retail, Trade or Hotel Accounts. Administrators manage group definitions, assignment policy and eligibility from Settings; Customer stores actual membership. Product prices and Order policies use that membership only where a matching rule applies.

| Role | Customer authority |
|---|---|
| Support | Permitted contact and order information, enquiries and service history; sensitive changes separately granted |
| Store manager | Customers and orders permitted by store policy; cross-store visibility must be an explicit choice |
| Finance | Billing evidence and financial history required for authorised work |
| Administrator | Account controls, access policy and approved privacy handling; access remains logged |

**R19 - Account and consent behaviour.** Support active, inactive and blocked accounts with clear reasons and authorisation. Verification, password recovery and suspicious-access handling must follow Settings security policy. **Proposed (to confirm):** use separate Pending Verification and Deletion Requested states where required.

Maintain channel-specific consent, purpose, language, capture time, source and withdrawal. Transactional order notices and optional marketing messages must follow separately configured rules. A customer opting out of advertising must still receive necessary order-service communication where the configured applicable policy permits it.

## Service, warranty and privacy

**R20 - Service records.** Record enquiry or case reference, customer, relevant order, product or component, serial number, issue, assigned owner, status, communications and resolution. Warranty eligibility must use the sold item's warranty terms, purchase evidence and serial number, not the product's current warranty settings. Installation completion may trigger warranty registration only where the applicable policy requires it.

**R21 - Privacy requirements.** Configure applicable privacy and retention policies by market. Support access requests, correction, consent withdrawal and deletion requests. Validate identity before releasing personal information. Remove or anonymise eligible personal information while preserving legally required financial and commercial evidence. Record the decision, exclusions, approver and completion.

The Kenya Data Protection Act 2019 is one example of a jurisdiction-specific requirement; it must apply only where relevant, alongside configured requirements for other markets. This document does not prescribe legal retention periods. Leadership must approve applicable policies **[To be agreed]**.

Prevent accidental duplicate customer records; flag matching contact details for review rather than automatically combining unrelated customers. Explain denied access, invalid addresses and account conflicts without revealing protected information.

# Page 6 - Settings: international business control

## Administrator-managed international configuration

**R22 - Market foundations.** Settings must provide manageable records rather than fixed lists embedded in platform behaviour. Every setting needs a clear name, enabled state, owner or authorised editor, relevant applicability, and change history. Publish only configuration that passes validation.

| Settings group | Required information and behaviour |
|---|---|
| Countries and regions | Country names and codes, enabled selling destinations, regions, address requirements, restrictions and available services |
| Currencies | Code, name, display symbol, decimal precision, rounding, enabled status, store defaults and supported payment compatibility |
| Languages | Enabled languages, default and fallback, translated business labels and content, translation-completeness rules |
| Time zones | Store and business time zones, customer display preferences and the zone governing each scheduled rule |
| Stores and locations | Name, address, country, enabled currencies and languages, time zone, fulfilment locations and operational scope |
| Taxes | Tax names/classes, jurisdictions, rates, effective periods, inclusive/exclusive treatment, exemptions, calculation basis and rounding |
| Currency conversion | Approved rate source or manually entered rates, effective time, update policy, failures and reporting conversion rules |

Tax means a government-imposed amount collected under applicable rules. A tax class groups products sharing a tax treatment. Neither tax names nor rates may be assumed from the platform's original market.

**R23 - Applicability and precedence.** Administrators select countries, currencies, stores and customer groups where an option applies. An explicit “All” scope may be supported; a blank required scope must not imply universal availability. Multiple selected conditions must all match. Hold priority and effective dates where rules can overlap.

Before activation, show the business effect of the configured rule and identify conflicts, unsupported combinations and missing dependencies. **Proposed (to confirm):** the most specific applicable rule wins, followed by explicit priority; unresolved ties block activation. The final precedence policy must be documented consistently for all five modules.

## Money, scheduling and localisation

Orders must keep their transaction currency. Never combine money in different currencies without an approved conversion rule. Reports converted into a reporting currency must show the rate and retain original amounts. Product pricing can be explicitly maintained for each currency; automatic conversion is optional and must be enabled and explained.

Support currencies with different decimal precision. Calculate using the configured precision and rounding policy, showing line and order totals that reconcile. Explain how discounts affect the taxable amount and how shipping or installation is taxed. Preserve applicable tax calculations and exemption evidence in the order.

**R24 - Safe international changes.** Interpret scheduled prices, offers, taxes and notifications using their assigned time zone, including clock changes where relevant. Retain an unambiguous event time for auditing. Changing a store's time zone must not silently move existing scheduled commitments.

Language fallback must be deliberate. Missing required translations must prevent publication where policy requires completeness; otherwise identify the configured fallback. Disabling a currency, country or language stops new eligible use while preserving past orders and existing records. No administrator should have to edit implementation code to launch another configured market.

# Page 7 - Settings: services, integrations and security

## All operational options are configured here

**R25 - Service configuration.** Each service must have an enabled state, display name, supported connector, credentials where needed, applicable countries/currencies/stores/customer groups, business rules, readiness status and failure policy. A connector is the supported link between the platform and an external service.

| Settings group | What the administrator must control |
|---|---|
| Payments | Methods and providers, eligibility, transaction limits, customer instructions, confirmation rules, retries, timeouts, refunds and reconciliation |
| Shipping and installation | Providers, zones, methods, weight/price/location rules, free-shipping thresholds, collection and installation appointments |
| Notifications and email templates | Editable event templates, languages, triggers, recipients, sender details, consent, retries and delivery history |
| Integrations | Tax-invoice services, accounting, stock systems, payment partners, instalment services, search, media storage and delivery services |
| Security | Users, roles, permissions, store boundaries, verification, sign-in policies, sensitive-action approvals and session controls |
| Operational policies | Reservation expiry, order acceptance, cancellation, returns, warranty, imports, retention, audit access and report visibility |

M-Pesa and KRA eTIMS are optional market-scoped services. Configure supported providers through Settings; a new unsupported service requires a connector, with scope **[To be agreed]**.

## Shipping rules

Configure weight bands, order-value bands, destination zones and free-shipping thresholds. Hold Rule Name, Enabled, Conditions, Weight Unit, Minimum/Maximum, Charge, Currency, Threshold, Priority and Effective Dates, scoped by country, store and customer group. Define inclusive boundaries, surcharge exceptions and whether the threshold uses totals before or after discounts/tax. **Proposed (to confirm):** use discounted merchandise subtotal before tax and shipping. At a threshold of 100, 99 pays the applicable charge and 100 qualifies, in the configured currency. Test overlaps and missing eligible methods; never silently treat an unsupported location as free delivery.

## Editable event email templates

Provide a separate editable template for each event: Order Placed, Order Confirmed, Payment Successful, Payment Failed, Order Shipped, Order Delivered, Order Cancelled, Return Initiated, Return Completed, Refund Initiated, Refund Completed, Welcome/Registration and Password Reset. **Proposed (to confirm):** also include verification, payment pending, collection ready, installation booking, abandoned cart and warranty updates.

For each template maintain Name, Event, Enabled, Subject, Body, Sender Name, Sender Address, Reply-to Address, Recipients, Language and country/store/group scope. Allow approved personalised values such as customer name, order reference, items, totals and tracking details. Provide preview and test sending before activation. Translate templates with deliberate fallback. Send only after the relevant event is accepted; retries must not duplicate messages. Password-reset messages must use secure expiring links and never contain passwords. Log delivery outcomes without secrets.

**R27 - Reliability and protection.** Encrypt credentials and never reveal saved secrets. Support controlled replacement and connection testing. Audit configuration changes without recording secret values. Restrict production activation, refund authority and export of personal information.

Queue long operations and external exchanges with progress, outcome, retry history and responsible owner. Do not report success before confirmation. Failed notices must not reverse successful payments; failed delivery updates must not lose shipment history. Service outages must leave recoverable records and an actionable alert. Capacity targets and peak-volume acceptance measures are **[To be agreed]**; implementation effort is **[To be estimated]**.

# Page 8 - AI creation requirements and acceptance

## Required end-to-end behaviour

**R28 - Build functioning requirements, not static examples.** Support authorised record creation, viewing, updating, retirement, searching, filtering, sorting and relevant exports. Validate required information, explain errors and preserve activity history. Bulk work needs preview and row-level results. Never invent production credentials or show an unconfirmed integration as connected.

1. **Product structure:** verify the separate Category module, all hierarchy levels and unchecked initial category choices. Save explicit selections. Then create Colour and Capacity attributes, organise them in groups, and assign them to a Washing Machine set. Create a Variable parent and selected child combinations with distinct stock codes, prices and stock. Buying one child must affect that child's stock.
2. **Global checkout:** test weight, value and location rules and orders below/at the free-shipping threshold. Preview event templates and verify the correct emails. Then configure two countries, currencies, languages and business time zones. Apply different payment, tax and delivery rules. Complete orders in both markets without implementation changes; show only eligible options.
3. **Offer fulfilment:** if the proposed component-offer design is approved, purchase a TV + soundbar + washer offer. Reserve components, verify payment, issue the applicable invoice, deliver, record serials and complete installation.
4. **Return and refund:** inspect a returned item, restore stock only when sellable, refund its original agreed allocation and prevent duplicate financial effects.
5. **Configuration change:** disable a provider or currency. Restrict new use while preserving existing orders and evidence; handle outstanding transactions through an explicit continuation policy.
6. **Privacy:** withdraw marketing consent and process a verified privacy request without deleting required commercial evidence or exposing protected information.

## Acceptance evidence

| Area | Required proof |
|---|---|
| Product | Reusable attributes, ordered groups, valid templates and unique child combinations |
| Money | Reconciled totals, currency precision, tax snapshots and verified payments/refunds |
| Settings | Central configuration, explicit applicability, conflicts resolved and changes logged |
| Access and recovery | Role/store restrictions, protected secrets and recoverable failed work |

**Proposed (to confirm) phasing:** MVP delivers the five modules, both product types, attribute structures, international configuration, stock, orders and confirmed connectors. Phase 2 expands connectors, advanced pricing, component offers and service automation. Phase 3 adds deeper automation and reporting. Final scope is **[To be agreed]**; costs and durations are **[To be estimated]**.

Leadership must confirm launch markets, tax/privacy policies, stock and accounting source of truth, fiscal-invoice arrangements, payment aggregators, instalment partners, refund limits and cross-branch customer/order access.

The AI must flag open decisions rather than silently fixing assumptions into policy. Acceptance requires the above test workflows, clear failure outcomes and administrator setup instructions.

