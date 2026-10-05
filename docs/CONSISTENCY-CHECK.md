# Consistency Check

-   API prefix: `/api/v1` across all modules.
-   Product types: only `SIMPLE` and `VARIABLE`; combos use component
    relations.
-   Attribute dependency: consumers depend on Attribute Engine; reverse
    dependency prohibited.
-   IDs: UUID; time persisted UTC and rendered Africa/Nairobi.
-   Currency/tax: KES, VAT-inclusive default; STANDARD_16 / ZERO_RATED /
    EXEMPT.
-   Permissions: dot-separated module/resource/action codes and backend
    enforcement.
-   Dynamic product forms: Attribute Set form-schema API.
-   Search: OpenSearch projections; PostgreSQL remains system of record.
-   Cache/queues: Redis; schema cache invalidated on attribute/set
    mutations.
-   Audit: mutations produce immutable audit events; secrets excluded.
-   CMS: versioned publishing plus content-delivery and ISR invalidation
    contract.
-   Inventory: location-level reservations and component-level combo
    deduction.
-   Finance: payment/fulfilment state separated; eTIMS exceptions
    retryable.
-   Cross-references: all module files link back to README, Navigation,
    RBAC and Settings.

No naming/API-prefix/product-type conflicts were found in the generated
suite.
