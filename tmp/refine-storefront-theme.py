from pathlib import Path
import re
p=Path('apps/storefront/app/globals.css');s=p.read_text(encoding='utf-8-sig')
s=re.sub(r"@import url\([^;]+;\s*",'',s,count=1)
s=s.replace("--olive:#58633e;--dark:#273124;--ink:#263028;--muted:#788075;--line:#e3e7dd;--cream:#f7f7f1;--sage:#edf0e6", "--olive:#465b32;--dark:#233523;--ink:#253126;--muted:#5e6a5d;--line:#dfe5d8;--cream:#f8f9f4;--sage:#edf2e5;--gold:#80591f;--gold-soft:#f6ecd5;--font-body:'Inter Variable',Inter,Arial,sans-serif")
s=s.replace("font-family:'DM Sans',Arial,sans-serif",'font-family:var(--font-body)').replace('font-family:Manrope,Arial,sans-serif','font-family:var(--font-body)').replace('font-family:Manrope,sans-serif','font-family:var(--font-body)')
size_map={7:11,8:11,9:12,10:12,11:13,12:14,13:15,14:16}
s=re.sub(r'font-size:(\d+)px',lambda m:'font-size:'+str(size_map.get(int(m[1]),int(m[1])))+'px',s)
# Replace weak grey copy and scattered highlight colours with the shared palette.
for colour in ['#7a8071','#929888','#8b9182','#7c856a','#7d886c','#7d846e','#7c876a','#7e8576','#8a9081','#8a9280','#9c9f94','#8e987e']:
 s=s.replace(colour,'var(--muted)')
s=s.replace('background:#414d2d','background:#354825').replace('background:#f5f5ed','background:#f6f7ef').replace('background:#e7ebdd','background:#e6eddb').replace('background:#d6ddc7','background:#d3dfc2').replace('background:#f8f9f5','background:#f7f9f3')
s+='''
/* Storefront typography and emphasis */
body{font-optical-sizing:auto;-webkit-font-smoothing:antialiased;line-height:1.65}
h1,h2,h3{font-weight:650;letter-spacing:-.035em}
h1{font-size:clamp(2.2rem,3.5vw,3rem)}
h2{font-size:clamp(1.7rem,2.5vw,2.125rem)}
h3{font-weight:600}
label{font-weight:550;color:var(--ink)}
input,select,textarea{font-size:15px;line-height:1.5;min-height:44px}
input::placeholder,textarea::placeholder{color:#707b6c;opacity:1}
.button{font-weight:600;min-height:48px;box-shadow:0 2px 3px #2335230a;transition:background .18s,box-shadow .18s,transform .18s}
.button:hover{box-shadow:0 4px 12px #23352320}
.button:active{transform:translateY(1px)}
.button.secondary{border-color:#c4cfb9;color:var(--olive);box-shadow:none}
.button.secondary:hover{background:var(--sage);border-color:var(--olive)}
.text-link,.section-heading>a,.clear-filters{font-weight:550}
.eyebrow{font-weight:650;letter-spacing:.14em}
.store-nav{font-weight:550}
.store-search{height:48px;border-color:#e1e7d8;background:#f6f8f1}
.store-search input{min-height:0;font-size:14px}
.header-tools small{font-weight:550}
.header-tools b{font-size:11px;min-width:18px;line-height:16px;text-align:center}
.hero h1{font-size:clamp(2.6rem,4.2vw,3.6rem);font-weight:650;letter-spacing:-.045em;line-height:1.12}
.hero-copy>p{max-width:410px;line-height:1.8;font-size:15px}
.hero-brand-note>span{font-size:11px;font-weight:550}
.hero-note{color:var(--olive);box-shadow:0 5px 20px #23352308}
.benefits strong{font-size:15px;font-weight:600}
.benefits small{font-size:12px}
.section-heading h2{font-size:clamp(1.7rem,2.4vw,2rem)}
.section-heading .eyebrow{font-size:11px}
.category-card>span{font-size:15px;font-weight:600}
.category-card small{font-size:12px;font-weight:500}
.product-card{background:white;box-shadow:0 2px 5px #23352303}
.product-card h3{font-size:15px;line-height:1.5;min-height:3em;max-height:3em;height:auto;font-weight:550;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.product-brand{font-size:11px;font-weight:600;letter-spacing:.1em}
.product-price{font-size:18px;line-height:1.4;flex-wrap:wrap;gap:7px}
.product-price strong,.detail-price strong{font-weight:700;color:#364e29;font-variant-numeric:tabular-nums}
.product-price del{font-size:12px;color:var(--muted);font-weight:400}
.offer-badge{background:var(--gold-soft);color:var(--gold);font-size:12px;font-weight:650;padding:4px 9px;border:1px solid #e9d9b5}
.in-stock{color:#456233;font-size:12px;font-weight:500}
.out-stock{color:#785b44;font-size:12px;font-weight:500}
.add-button{font-size:13px;font-weight:600;background:var(--olive);color:white;min-height:32px;padding:5px 12px}
.add-button:hover:not(:disabled){background:#354825}
.add-button:disabled{background:var(--sage);color:#59654e;opacity:.7}
.favorite-button{color:#647358;border-color:#dce4d4;min-width:34px;min-height:34px;align-items:center;justify-content:center}
.favorite-button.selected{background:var(--sage);color:var(--olive);border-color:#bdcdaa}
.editorial{background:#e9efdf;border:1px solid #dbe4ce}
.editorial p{font-size:15px;line-height:1.8;color:var(--muted)}
.editorial h2{font-size:clamp(1.9rem,3vw,2.5rem)}
.newsletter h2{font-size:30px}
.newsletter p,.newsletter input{font-size:14px}
.newsletter form small{font-size:12px;line-height:1.6}
.newsletter .eyebrow{font-size:11px}
.store-footer{background:#f0f4e9;border-top:1px solid #e1e8d7}
.footer-grid{font-size:13px;color:var(--muted);line-height:1.7}
.footer-grid h3{font-size:14px;font-weight:600}
.footer-location{font-size:12px;color:var(--ink);border-color:#ccd7bc}
.footer-bottom{font-size:12px;line-height:1.6;color:var(--muted)}
.catalog-heading p,.information-heading p{font-size:16px;line-height:1.8}
.catalog-filters{font-size:14px}
.catalog-filters>h3{font-size:15px;font-weight:600}
.catalog-filters label,.catalog-filters select,.catalog-filters input:not([type=checkbox]){font-size:13px}
.catalog-filters label.check-label{font-size:13px}
.catalog-filters input[type=checkbox]{min-height:0;width:16px;height:16px;flex-shrink:0}
.catalog-toolbar,.catalog-toolbar label{font-size:13px}
.catalog-toolbar select{font-size:13px}
.filter-help p,.filter-help a{font-size:13px}
.brand-banner{background:linear-gradient(115deg,#f2f5e9,#e2ebd5);border-block:1px solid #dbe4ce}
.brand-banner .eyebrow{color:var(--olive)}
.brand-banner p{color:var(--muted);font-size:16px}
.product-detail-copy h1{font-size:clamp(1.9rem,3vw,2.5rem);line-height:1.25}
.product-detail-copy>p{font-size:15px;line-height:1.8}
.detail-price{font-size:30px;line-height:1.4}
.detail-price del{font-size:17px;color:var(--muted)}
.detail-assurances,.detail-link{font-size:14px;line-height:1.65}
.product-information p,.product-information table{font-size:15px;line-height:1.8}
.product-information th{font-weight:600;color:var(--ink)}
.product-information h2,.product-reviews h2{font-size:26px}
.review-card h3{font-size:17px}
.review-card p{font-size:15px;line-height:1.8}
.review-card small{font-size:13px}
.review-stars{color:var(--gold)}
.status-pill{font-size:13px;font-weight:600;background:var(--sage);color:var(--olive);border:1px solid #dae4ce}
.form-feedback{font-size:14px;line-height:1.7}
.form-feedback.error{color:#923b2a;background:#fff1eb;border:1px solid #f0d4ca}
.checkout-line small,.order-summary>small,.checkout-assurance{font-size:12px}
.order-summary>div,.order-summary p{font-size:14px;line-height:1.6}
.order-summary strong,.cart-line strong,.account-order>strong{color:#364e29;font-variant-numeric:tabular-nums}
.summary-total{font-size:20px!important}
.auth-form>p,.auth-story p{font-size:15px}
.account-order{font-size:14px}
.account-order small{font-size:12px}
.order-receipt section:first-child>div{font-size:15px}
.order-receipt p,.order-statuses,.order-reference{font-size:14px}
.information-body,.faq-list p{font-size:15px;line-height:1.85}
.contact-details p,.contact-details>a{font-size:14px}
@media(max-width:800px){.header-main{gap:24px}.hero-copy{padding:36px 27px}.hero h1{font-size:40px}.hero-actions .button{padding-inline:17px;font-size:14px}.hero-copy>p{font-size:14px}.benefits strong{font-size:13px}.benefits small{font-size:12px}.product-card-info{padding:16px 14px}.catalog-products .product-card-info{padding:15px 12px}.product-price{font-size:17px}.section-heading>a{font-size:13px}.detail-assurances span{align-items:flex-start}}
@media(max-width:560px){body{font-size:15px}.announcement{font-size:11px;padding:9px 0;letter-spacing:0}.hero-copy{padding:31px 24px}.hero h1{font-size:42px}.hero-copy>p{font-size:15px}.hero .eyebrow{font-size:11px}.hero-brand-note>div{gap:24px}.hero-visual-caption{font-size:10px}.hero-note{font-size:11px}.benefits strong{font-size:15px}.benefits small{font-size:13px}.section-heading h2{font-size:26px;line-height:1.25}.category-card>span{font-size:14px}.category-card small{font-size:12px}.product-card-image,.catalog-layout .product-card-image{height:175px}.product-card-info,.catalog-products .product-card-info{padding:14px 12px}.product-card h3{font-size:14px;min-height:3em;max-height:3em}.product-brand{font-size:10px}.product-price{font-size:17px;gap:5px}.product-price del{font-size:11px}.offer-badge{font-size:11px;padding:3px 7px}.in-stock,.out-stock{font-size:11px}.add-button{font-size:12px;padding:5px 8px;gap:3px}.product-card-bottom{gap:5px;flex-wrap:wrap}.category-grid{gap:12px}.editorial h2{font-size:29px}.newsletter h2{font-size:28px}.footer-grid{font-size:14px}.footer-bottom{font-size:12px}.catalog-toolbar{align-items:flex-start;flex-wrap:wrap}.catalog-toolbar select{font-size:13px;width:auto;max-width:180px}.catalog-filters{gap:16px}.catalog-filters input:not([type=checkbox]),.catalog-filters select{font-size:14px}.catalog-heading h1{font-size:35px}.product-detail-copy h1{font-size:31px}.detail-price{font-size:29px}.purchase-actions{gap:10px}.purchase-actions .button{padding-inline:15px;font-size:14px}.quantity{min-width:95px}.quantity button{padding:0 11px}.detail-assurances{font-size:14px}.product-information table{font-size:14px}.order-statuses{font-size:13px;gap:12px}.cart-line h3{font-size:15px}.account-tabs button{font-size:13px}.account-tabs button:last-child{font-size:0}.footer-grid>div:last-child{padding-top:10px}}
'''
p.write_text(s,encoding='utf-8')
p=Path('apps/storefront/app/layout.tsx');s=p.read_text();s="import '@fontsource-variable/inter/latin.css';\n"+s;p.write_text(s)
