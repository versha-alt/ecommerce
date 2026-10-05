from pathlib import Path
p=Path('docs/Backend-Functional-Specification.md')
t=p.read_text(encoding='utf-8-sig')
t=t.replace('The scope has four modules: **Product, Order, Customer and Settings**. Product includes Category, Brand and Attribute management.', 'The scope has five modules: **Category, Product, Order, Customer and Settings**. Category is independently managed; Product references its hierarchy. Product includes Brand and Attribute management.')
t=t.replace('these four modules','these five modules').replace('all four modules','all five modules').replace('the four modules','the five modules')
t=t.replace('| Product | Categories, brands,', '| Category | Category hierarchy, category information, membership rules and navigation organisation |\n| Product | Brands,')
t=t.replace('Product, Order and Customer remain','Category, Product, Order and Customer remain')
t=t.replace('# Page 2 - Product: Category, Brand and Attribute structure','# Page 2 - Category module and Product structure')
t=t.replace('**R05 - Category.** A Category organises products into a hierarchy, such as Appliances, Laundry and Washing Machines.', '**R05 - Separate Category module.** Manage a two- or three-level hierarchy: parent, child and optional sub-child, such as Appliances > Laundry > Washing Machines. Each non-root category has one parent. Prevent cycles and moves that exceed three levels.')
t=t.replace('Prevent circular parent relationships. Support manual product membership and rule-based membership, with clear precedence.', 'Product uses category references maintained here. Manual assignments require explicit admin selection. Optional rule-based membership must be separately enabled and identified; it must not silently pre-select product checkboxes.')
t=t.replace('**R06 - Brand.**','**R06 - Product category selection and Brand.** Show all categories as checkboxes in their parent-child-sub-child hierarchy. No category is pre-selected when creating a product. The admin explicitly selects every relevant category; checking a child must not automatically check its parent or siblings. Saved selections appear when editing. **Proposed (to confirm):** drafts may have no category, but publication requires at least one.\n\n**Brand.**')
# Move verified payment information to Order to keep Settings expansion within eight pages.
start=t.index('## Financial and integration safeguards')
end=t.index('**R27 - Reliability and protection.')
block=t[start:end]
t=t[:start]+t[end:]
t=t.replace('# Page 5 - Customer: accounts, groups and protection',block+'\n# Page 5 - Customer: accounts, groups and protection')
# Replace table labels and condense provider explanation to make space for explicit new requirements.
t=t.replace('| Shipping and installation | Providers, zones, methods, rates, free-service conditions, collection locations, appointment capacity and delivery promises |','| Shipping and installation | Providers, zones, methods, weight/price/location rules, free-shipping thresholds, collection and installation appointments |')
t=t.replace('| Notifications | Email, SMS and messaging providers; templates, translations, triggers, recipients, consent requirements, retry and escalation rules |','| Notifications and email templates | Editable event templates, languages, triggers, recipients, sender details, consent, retries and delivery history |')
old="M-Pesa and KRA eTIMS may be available as configured services for eligible markets; they must not be mandatory platform-wide services. Other supported providers must follow the same admin-controlled model. Configure provider-specific prerequisites explicitly. Connecting a supported service must require configuration rather than country-specific implementation changes. A genuinely new unsupported service needs an additional connector, with scope **[To be agreed]**."
new="M-Pesa and KRA eTIMS are optional market-scoped services. Configure supported providers through Settings; a new unsupported service requires a connector, with scope **[To be agreed]**."
t=t.replace(old,new)
pos=t.index('**R27 - Reliability and protection.')
extra="""## Shipping rules

Configure weight bands, order-value bands, destination zones and free-shipping thresholds. Hold Rule Name, Enabled, Conditions, Weight Unit, Minimum/Maximum, Charge, Currency, Threshold, Priority and Effective Dates, scoped by country, store and customer group. Define inclusive boundaries, surcharge exceptions and whether the threshold uses totals before or after discounts/tax. **Proposed (to confirm):** use discounted merchandise subtotal before tax and shipping. At a threshold of 100, 99 pays the applicable charge and 100 qualifies, in the configured currency. Test overlaps and missing eligible methods; never silently treat an unsupported location as free delivery.

## Editable event email templates

Provide a separate editable template for each event: Order Placed, Order Confirmed, Payment Successful, Payment Failed, Order Shipped, Order Delivered, Order Cancelled, Return Initiated, Return Completed, Refund Initiated, Refund Completed, Welcome/Registration and Password Reset. **Proposed (to confirm):** also include verification, payment pending, collection ready, installation booking, abandoned cart and warranty updates.

For each template maintain Name, Event, Enabled, Subject, Body, Sender Name, Sender Address, Reply-to Address, Recipients, Language and country/store/group scope. Allow approved personalised values such as customer name, order reference, items, totals and tracking details. Provide preview and test sending before activation. Translate templates with deliberate fallback. Send only after the relevant event is accepted; retries must not duplicate messages. Password-reset messages must use secure expiring links and never contain passwords. Log delivery outcomes without secrets.

"""
t=t[:pos]+extra+t[pos:]
t=t.replace('## Required end-to-end behaviour','## Required end-to-end behaviour')
t=t.replace('1. **Product structure:**','1. **Product structure:** verify the separate Category module, all hierarchy levels and unchecked initial category choices. Save explicit selections. Then')
t=t.replace('Then create Colour','Then create Colour')
t=t.replace('2. **Global checkout:**','2. **Global checkout:** test weight, value and location rules and orders below/at the free-shipping threshold. Preview event templates and verify the correct emails. Then')
p.write_text(t,encoding='utf-8')

