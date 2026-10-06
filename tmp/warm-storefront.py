from pathlib import Path
p=Path('apps/storefront/components/store.tsx');s=p.read_text();s=s.replace('Check,Star}', 'Check,Star,Tag}');s=s.replace('<span className="offer-badge">Save {discount}%</span>','<span className="offer-badge"><Tag size={12} aria-hidden="true"/>Save {discount}%</span>');p.write_text(s)
