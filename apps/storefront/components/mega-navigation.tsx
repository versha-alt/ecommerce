"use client";
import CategorySymbol from '@/components/category-symbol';
import {useEffect, useMemo, useRef, useState} from 'react';
import type {KeyboardEvent as ReactKeyboardEvent, ReactNode} from 'react';
import {createPortal} from 'react-dom';
import Link from 'next/link';
import Image from 'next/image';
import {usePathname} from 'next/navigation';
import {ArrowRight, ChevronDown, ImageOff, Truck, X} from 'lucide-react';
import {Catalog, slugify} from '@/lib/store';
import {brandLogoFallbacks, featureHref, menuCategories, menuCategoryHref, menuBrandHref, type MenuCategory} from '@/lib/mega-menu';

/* Menu content lives in lib/mega-menu.ts. This file only handles layout and behaviour. */

type ResolvedBrand = {id: string; name: string; slug: string; logo?: string; href?: string};
type ResolvedCategory = Omit<MenuCategory, 'subcategories'> & {label: string; href: string; subcategories: (MenuCategory['subcategories'][number] & {href: string})[]; brandLinks: ResolvedBrand[]};

const DESKTOP_QUERY = '(min-width: 1181px)';
const OPEN_DELAY = 110; // hover intent before the first panel opens
const SWITCH_DELAY = 90; // moving between categories while a panel is open
const CLOSE_DELAY = 150; // grace period after the pointer leaves the menu

const quickLinks = [
  {label: 'All products', href: '/products'},
  {label: 'New arrivals', href: '/products?sort=newest'},
  {label: 'Special offers', href: '/products?sale=1'},
];

const triggerId = (id: string) => `site-nav-trigger-${id}`;
const panelId = (id: string) => `site-nav-panel-${id}`;

function useResolvedCategories(data: Catalog): ResolvedCategory[] {
  return useMemo(() => {
    const brands: ResolvedBrand[] = (data.brands ?? []).map((brand: any) => {
      const slug = slugify(String(brand.name ?? ''));
      return {id: String(brand.id), name: String(brand.name ?? ''), slug, logo: brand.image || brandLogoFallbacks[slug]};
    });
    return menuCategories.map(category => ({
      ...category,
      label: category.navLabel ?? category.name,
      href: menuCategoryHref(category, data),
      subcategories: category.subcategories.map(sub => ({...sub, href: menuCategoryHref(category, data, sub.label)})),
      featured: category.featured.map(feature => {
        const destination = featureHref(feature, category, data);
        const selected = data.categories.find(item => destination === `/categories/${item.slug}`);
        const product = data.products.find(item => selected && item.category_ids.includes(selected.id) && item.image);
        return {...feature, href: destination, image: product?.image ?? feature.image, imageAlt: product?.name ?? feature.imageAlt};
      }),
      brandLinks: category.brands
        .map(slug => brands.find(brand => brand.slug === slug))
        .filter((brand): brand is ResolvedBrand => Boolean(brand) && data.products.some(product => product.brand_id === brand?.id && product.category_ids.includes(data.categories.find(item => item.slug === category.id)?.id ?? '')))
        .map(brand => ({...brand, href: menuBrandHref(category, data, brand.id)})),
    }));
  }, [data.brands, data.categories, data.products]);
}

/** next/image with a graceful fallback when the source is missing or fails to load. */
function MenuImage({src, alt, sizes, fallback}: {src?: string; alt: string; sizes: string; fallback: ReactNode}) {
  const [failed, setFailed] = useState(false);
  useEffect(() => setFailed(false), [src]);
  if (!src || failed) return <>{fallback}</>;
  return <Image src={src} alt={alt} fill sizes={sizes} unoptimized={src.endsWith('.svg')} onError={() => setFailed(true)} />;
}

const imageFallback = (
  <span className="mm-img-fallback" aria-hidden="true">
    <ImageOff size={22} strokeWidth={1.5} />
  </span>
);

function BrandTile({brand, category, onSelect}: {brand: ResolvedBrand; category: ResolvedCategory; onSelect: () => void}) {
  return (
    <Link className="mm-brand" href={brand.href ?? '/brands/'+brand.slug} onClick={onSelect} aria-label={`Shop ${brand.name} ${category.name}`}>
      <span className="mm-brand-logo">
        <MenuImage src={brand.logo} alt={`${brand.name} logo`} sizes="140px" fallback={<span className="mm-brand-name">{brand.name}</span>} />
      </span>
    </Link>
  );
}

export default function MegaNavigation({data, mobileOpen, onNavigate}: {data: Catalog; mobileOpen: boolean; onNavigate: () => void}) {
  const categories = useResolvedCategories(data);
  const [openId, setOpenId] = useState<string | null>(null);
  const [switching, setSwitching] = useState(false);
  const navRef = useRef<HTMLElement>(null);
  const openRef = useRef<string | null>(null);
  const openedAt = useRef(0);
  const timer = useRef<number | undefined>(undefined);
  const suppressFocusOpen = useRef(false);
  const pendingFocus = useRef<'first' | 'last' | null>(null);
  const onNavigateRef = useRef(onNavigate);
  onNavigateRef.current = onNavigate;
  const pathname = usePathname();

  const clearTimer = () => {
    if (timer.current !== undefined) window.clearTimeout(timer.current);
    timer.current = undefined;
  };
  const schedule = (action: () => void, delay: number) => {
    clearTimer();
    timer.current = window.setTimeout(action, delay);
  };
  const show = (id: string | null) => {
    clearTimer();
    const previous = openRef.current;
    if (previous === id) return;
    setSwitching(false); // Keep the same fade/slide when switching categories.
    openRef.current = id;
    if (id) openedAt.current = Date.now();
    setOpenId(id);
  };
  const focusTrigger = (id: string) => {
    suppressFocusOpen.current = true;
    document.getElementById(triggerId(id))?.focus();
    suppressFocusOpen.current = false;
  };
  const panelLinks = (id: string) => Array.from(document.getElementById(panelId(id))?.querySelectorAll<HTMLElement>('a[href]') ?? []);
  const focusPanelLink = (id: string, where: 'first' | 'last') => {
    const links = panelLinks(id);
    (where === 'first' ? links[0] : links[links.length - 1])?.focus();
  };
  const triggers = () => Array.from(navRef.current?.querySelectorAll<HTMLButtonElement>('.site-nav-trigger') ?? []);
  const selectLink = () => {
    show(null);
    onNavigateRef.current();
  };

  // Close everything when the route changes.
  useEffect(() => {
    show(null);
    onNavigateRef.current();
  }, [pathname]);

  // Move focus into a panel once it has rendered (keyboard ArrowDown / ArrowUp).
  useEffect(() => {
    if (!openId || !pendingFocus.current) return;
    const where = pendingFocus.current;
    pendingFocus.current = null;
    focusPanelLink(openId, where);
  }, [openId]);

  // Click outside and Escape close the open panel.
  useEffect(() => {
    if (!openId) return;
    const onPointerDown = (event: PointerEvent) => {
      if (!navRef.current?.contains(event.target as Node)) show(null);
    };
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key !== 'Escape') return;
      const id = openRef.current;
      const focusInside = navRef.current?.contains(document.activeElement);
      show(null);
      if (id && focusInside) focusTrigger(id);
    };
    document.addEventListener('pointerdown', onPointerDown);
    document.addEventListener('keydown', onKeyDown);
    return () => {
      document.removeEventListener('pointerdown', onPointerDown);
      document.removeEventListener('keydown', onKeyDown);
    };
  }, [openId]);

  // Switching between desktop and drawer layouts resets whichever one is hidden.
  useEffect(() => {
    const media = window.matchMedia(DESKTOP_QUERY);
    const sync = () => {
      if (media.matches) onNavigateRef.current();
      else show(null);
    };
    media.addEventListener('change', sync);
    return () => {
      media.removeEventListener('change', sync);
      clearTimer();
    };
  }, []);

  const onTriggerKeyDown = (event: ReactKeyboardEvent<HTMLButtonElement>, id: string) => {
    const list = triggers();
    const index = list.indexOf(event.currentTarget);
    switch (event.key) {
      case 'Enter':
      case ' ':
        event.preventDefault();
        show(id);
        break;
      case 'ArrowRight':
      case 'ArrowLeft': {
        event.preventDefault();
        const step = event.key === 'ArrowRight' ? 1 : -1;
        list[(index + step + list.length) % list.length]?.focus();
        break;
      }
      case 'Home':
        event.preventDefault();
        list[0]?.focus();
        break;
      case 'End':
        event.preventDefault();
        list[list.length - 1]?.focus();
        break;
      case 'ArrowDown':
      case 'ArrowUp': {
        event.preventDefault();
        const where = event.key === 'ArrowDown' ? 'first' : 'last';
        if (openRef.current === id) focusPanelLink(id, where);
        else {
          pendingFocus.current = where;
          show(id);
        }
        break;
      }
    }
  };

  const onPanelKeyDown = (event: ReactKeyboardEvent<HTMLDivElement>, id: string) => {
    const links = panelLinks(id);
    const index = links.indexOf(document.activeElement as HTMLElement);
    if (index < 0) return;
    let next: HTMLElement | undefined;
    if (event.key === 'ArrowDown') next = links[(index + 1) % links.length];
    else if (event.key === 'ArrowUp') next = links[(index - 1 + links.length) % links.length];
    else if (event.key === 'Home') next = links[0];
    else if (event.key === 'End') next = links[links.length - 1];
    else if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
      // Jump to the first link of the neighbouring column.
      const columns = Array.from(event.currentTarget.querySelectorAll<HTMLElement>('[data-menu-col]')).filter(column => column.querySelector('a[href]'));
      const current = columns.findIndex(column => column.contains(document.activeElement));
      if (current < 0) return;
      const target = columns[current + (event.key === 'ArrowRight' ? 1 : -1)];
      next = target?.querySelector<HTMLElement>('a[href]') ?? undefined;
      if (!next) return;
    } else return;
    event.preventDefault();
    next?.focus();
  };

  return (
    <>
      <nav
        ref={navRef}
        aria-label="Main navigation"
        className={'container site-nav' + (switching ? ' is-switching' : '')}
        onPointerEnter={clearTimer}
        onPointerLeave={event => {
          if (event.pointerType === 'mouse') schedule(() => show(null), CLOSE_DELAY);
        }}
        onBlur={event => {
          const next = event.relatedTarget as Node | null;
          if (next && !event.currentTarget.contains(next)) show(null);
        }}
      >
        <ul className="site-nav-list">
          {categories.map(category => {
            const open = openId === category.id;
            return (
              <li className="site-nav-item" key={category.id} onPointerLeave={event => {if(event.pointerType==='mouse') schedule(()=>show(null),CLOSE_DELAY);}}>
                <button
                  type="button"
                  id={triggerId(category.id)}
                  className="site-nav-trigger"
                  aria-expanded={open}
                  aria-haspopup="true"
                  aria-controls={panelId(category.id)}
                  onPointerEnter={event => {
                    if (event.pointerType !== 'mouse') return;
                    if (openRef.current === category.id) return clearTimer();
                    schedule(() => show(category.id), openRef.current ? SWITCH_DELAY : OPEN_DELAY);
                  }}
                  onFocus={() => {
                    if (!suppressFocusOpen.current && openRef.current !== category.id) show(category.id);
                  }}
                  onClick={() => {
                    // A click right after hover/focus opened the panel keeps it open; otherwise it toggles.
                    if (openRef.current === category.id && Date.now() - openedAt.current > 400) show(null);
                    else show(category.id);
                  }}
                  onKeyDown={event => onTriggerKeyDown(event, category.id)}
                >
                  <CategorySymbol id={category.id} size={16}/><span>{category.label}</span>
                  <ChevronDown size={14} strokeWidth={2.2} aria-hidden="true" />
                </button>

                <div
                  id={panelId(category.id)}
                  className={'site-nav-panel' + (open ? ' is-open' : '')}
                  role="region"
                  aria-labelledby={triggerId(category.id)}
                  inert={!open}
                  onPointerEnter={clearTimer}
                  onPointerLeave={event=>{if(event.pointerType==='mouse')schedule(()=>show(null),CLOSE_DELAY);}}
                  onKeyDown={event => onPanelKeyDown(event, category.id)}
                >
                  <div className="mm-grid">
                    <div className="mm-col" data-menu-col>
                      <p className="mm-col-title" id={`mm-sub-${category.id}`}>{category.name}</p>
                      <ul className="mm-sublinks" aria-labelledby={`mm-sub-${category.id}`}>
                        {category.subcategories.map(sub => (
                          <li key={sub.label}>
                            <Link href={sub.href} onClick={selectLink}>
                              <span>{sub.label}</span>
                              <ArrowRight size={14} aria-hidden="true" />
                            </Link>
                          </li>
                        ))}
                      </ul>
                      <Link className="mm-view-all" href={category.href} onClick={selectLink}>
                        View all {category.label} <ArrowRight size={14} aria-hidden="true" />
                      </Link>
                    </div>

                    <div className="mm-col" data-menu-col>
                      <p className="mm-col-title" id={`mm-feat-${category.id}`}>In the spotlight</p>
                      <ul className="mm-features" aria-labelledby={`mm-feat-${category.id}`}>
                        {category.featured.slice(0, 2).map(feature => (
                          <li key={feature.headline}>
                            <article className="mm-feature">
                              <span className={'mm-feature-media' + (feature.image.endsWith('.svg') ? '' : ' is-photo')}>
                                <MenuImage src={feature.image} alt={feature.imageAlt ?? feature.headline} sizes="96px" fallback={imageFallback} />
                              </span>
                              <span className="mm-feature-copy">
                                <span className="mm-feature-label">{feature.label}</span>
                                <span className="mm-feature-headline">{feature.headline}</span>
                                <span className="mm-feature-desc">{feature.description}</span>
                                <Link className="mm-feature-link" href={feature.href ?? category.href} onClick={selectLink} aria-label={`Explore: ${feature.headline}`}>
                                  Explore <ArrowRight size={14} aria-hidden="true" />
                                </Link>
                              </span>
                            </article>
                          </li>
                        ))}
                      </ul>
                    </div>

                    <div className="mm-col" data-menu-col>
                      <p className="mm-col-title" id={`mm-brand-${category.id}`}>Shop by brand</p>
                      {category.brandLinks.length ? (
                        <ul className="mm-brands" aria-labelledby={`mm-brand-${category.id}`}>
                          {category.brandLinks.map(brand => (
                            <li key={brand.id}>
                              <BrandTile brand={brand} category={category} onSelect={selectLink} />
                            </li>
                          ))}
                        </ul>
                      ) : (
                        <p className="mm-empty">More brands arriving soon.</p>
                      )}
                    </div>
                  </div>

                  <div className="mm-panel-footer" data-menu-col>
                    {quickLinks.map(link => (
                      <Link key={link.href} href={link.href} onClick={selectLink}>{link.label}</Link>
                    ))}
                    <span className="mm-footer-note"><Truck size={14} aria-hidden="true" />Delivery across selected Kenyan counties</span>
                  </div>
                </div>
              </li>
            );
          })}
        </ul>
      </nav>
      <MobileDrawer categories={categories} open={mobileOpen} onClose={() => onNavigateRef.current()} />
    </>
  );
}

function MobileDrawer({categories, open, onClose}: {categories: ResolvedCategory[]; open: boolean; onClose: () => void}) {
  const [mounted, setMounted] = useState(false);
  const [expanded, setExpanded] = useState<string | null>(null);
  const drawerRef = useRef<HTMLDivElement>(null);
  const closeRef = useRef<HTMLButtonElement>(null);

  useEffect(() => setMounted(true), []);

  // Lock page scroll, move focus in, and give focus back to the menu button on close.
  useEffect(() => {
    if (!open) return;
    const opener = document.activeElement as HTMLElement | null;
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    const frame = requestAnimationFrame(() => closeRef.current?.focus());
    return () => {
      cancelAnimationFrame(frame);
      document.body.style.overflow = previousOverflow;
      const active = document.activeElement;
      if (!active || active === document.body || drawerRef.current?.contains(active)) {
        (opener && opener.isConnected ? opener : document.querySelector<HTMLElement>('.mobile-menu'))?.focus();
      }
    };
  }, [open]);

  if (!mounted) return null;

  const onKeyDown = (event: ReactKeyboardEvent<HTMLDivElement>) => {
    if (event.key === 'Escape') {
      event.preventDefault();
      onClose();
      return;
    }
    if (event.key !== 'Tab') return;
    // Keep keyboard focus inside the open drawer.
    const focusable = Array.from(drawerRef.current?.querySelectorAll<HTMLElement>('a[href],button:not([disabled])') ?? []).filter(element => !element.closest('[inert]'));
    if (!focusable.length) return;
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  };

  return createPortal(
    <div className={'mm-drawer-root' + (open ? ' is-open' : '')} inert={!open}>
      <div className="mm-drawer-backdrop" aria-hidden="true" onClick={onClose} />
      <div id="site-nav-drawer" ref={drawerRef} className="mm-drawer" role="dialog" aria-modal="true" aria-labelledby="mm-drawer-title" onKeyDown={onKeyDown}>
        <div className="mm-drawer-head">
          <span className="mm-drawer-title" id="mm-drawer-title">Shop by category</span>
          <button ref={closeRef} type="button" className="mm-drawer-close" aria-label="Close menu" onClick={onClose}>
            <X size={20} aria-hidden="true" />
          </button>
        </div>
        <nav className="mm-drawer-body" aria-label="Mobile navigation">
          <ul className="mm-accordion">
            {categories.map(category => {
              const isOpen = expanded === category.id;
              return (
                <li key={category.id} className={isOpen ? 'is-expanded' : undefined}>
                  <h3 className="mm-acc-heading">
                    <button
                      type="button"
                      id={`mm-acc-btn-${category.id}`}
                      aria-expanded={isOpen}
                      aria-controls={`mm-acc-panel-${category.id}`}
                      onClick={() => setExpanded(isOpen ? null : category.id)}
                    >
                      {category.label}
                      <ChevronDown size={18} aria-hidden="true" />
                    </button>
                  </h3>
                  <div id={`mm-acc-panel-${category.id}`} className="mm-acc-panel" role="region" aria-labelledby={`mm-acc-btn-${category.id}`} inert={!isOpen}>
                    <div className="mm-acc-inner">
                      <ul className="mm-acc-links">
                        {category.subcategories.map(sub => (
                          <li key={sub.label}>
                            <Link href={sub.href} onClick={onClose}>{sub.label}</Link>
                          </li>
                        ))}
                        <li>
                          <Link className="mm-acc-all" href={category.href} onClick={onClose}>
                            View all {category.label} <ArrowRight size={14} aria-hidden="true" />
                          </Link>
                        </li>
                      </ul>
                      {category.brandLinks.length > 0 && (
                        <>
                          <p className="mm-acc-subtitle" id={`mm-acc-brands-${category.id}`}>Shop by brand</p>
                          <ul className="mm-brands mm-acc-brands" aria-labelledby={`mm-acc-brands-${category.id}`}>
                            {category.brandLinks.map(brand => (
                              <li key={brand.id}>
                                <BrandTile brand={brand} category={category} onSelect={onClose} />
                              </li>
                            ))}
                          </ul>
                        </>
                      )}
                    </div>
                  </div>
                </li>
              );
            })}
          </ul>
          <ul className="mm-drawer-quick">
            {quickLinks.map(link => (
              <li key={link.href}>
                <Link href={link.href} onClick={onClose}>
                  {link.label} <ArrowRight size={15} aria-hidden="true" />
                </Link>
              </li>
            ))}
          </ul>
        </nav>
      </div>
    </div>,
    document.body,
  );
}
