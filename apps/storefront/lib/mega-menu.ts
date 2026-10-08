/**
 * Main navigation / mega menu content.
 *
 * This is the single place to edit the store's top-level categories.
 * The homepage "Shop by category" grid and the header mega menu both read from it.
 *
 * Links work exactly like the existing homepage category links: they open the
 * products page with a search query (`/products?q=...`). Brand links add the
 * brand filter (`&brand=<brand id>`), resolved at runtime from the catalog.
 *
 * Field guide
 * - id:            unique key, also used for element ids (lowercase, dashes)
 * - name:          full name (shown on the homepage card and as the panel title)
 * - navLabel:      optional shorter label for the top nav bar
 * - image:         homepage category card image
 * - query:         search term for "View all" / homepage card link
 * - subcategories: list of { label, query }
 * - featured:      1–2 promo items { image, imageAlt?, label, headline, description, query? , href? }
 *                  `href` overrides `query` when you need a custom link.
 * - brands:        brand slugs as they appear in the catalog (e.g. 'lg', 'midea', 'karcher').
 *                  Brands not found in the catalog are skipped automatically.
 */

export type MenuLink = {label: string; query: string};

export type MenuFeature = {
  image: string;
  imageAlt?: string;
  label: string;
  headline: string;
  description: string;
  query?: string;
  href?: string;
};

export type MenuCategory = {
  id: string;
  name: string;
  navLabel?: string;
  image: string;
  query: string;
  subcategories: MenuLink[];
  featured: MenuFeature[];
  brands: string[];
};

/** Fallback brand logos, used when a brand in the catalog has no image of its own. */
export const brandLogoFallbacks: Record<string, string> = {
  lg: '/assets/brands/lg.png',
  midea: '/assets/brands/midea.png',
  karcher: '/assets/brands/karcher.png',
};

export const menuCategories: MenuCategory[] = [
  {
    id: 'tvs-audio',
    name: 'TVs & Audio',
    image: '/assets/categories/tvs-audio.jpg',
    query: 'tv',
    subcategories: [
      {label: 'Smart TVs', query: 'smart tv'},
      {label: '4K UHD TVs', query: 'uhd'},
      {label: 'OLED TVs', query: 'oled'},
      {label: 'Soundbars', query: 'soundbar'},
      {label: 'Home Theatre', query: 'home theatre'},
    ],
    featured: [
      {image: '/assets/tv.svg', imageAlt: 'Smart TV', label: 'Movie nights', headline: 'Your sofa, now a cinema', description: 'Sharp 4K pictures with streaming apps built right in.', query: 'smart tv'},
      {image: '/assets/banners/lg-living.jpg', imageAlt: 'Living room with a TV setup', label: 'Better sound', headline: 'Fill the room with sound', description: 'Pair your screen with a soundbar for richer, clearer audio.', query: 'soundbar'},
    ],
    brands: ['lg', 'midea'],
  },
  {
    id: 'fridges-freezers',
    name: 'Fridges & Freezers',
    image: '/assets/categories/fridges-freezers.jpg',
    query: 'fridge',
    subcategories: [
      {label: 'Side by Side', query: 'side by side'},
      {label: 'Top Freezer', query: 'top freezer'},
      {label: 'Bottom Freezer', query: 'bottom freezer'},
      {label: 'Single Door', query: 'single door'},
      {label: 'Chest Freezers', query: 'chest freezer'},
    ],
    featured: [
      {image: '/assets/fridge.svg', imageAlt: 'Refrigerator', label: 'Family size', headline: 'Room for the weekly shop', description: 'Generous shelves and crisper drawers that keep food fresher.', query: 'refrigerator'},
      {image: '/assets/categories/fridges-freezers.jpg', imageAlt: 'Fridges and freezers', label: 'Stock up', headline: 'Freeze more, waste less', description: 'Chest freezers sized for bulk buys and busy households.', query: 'freezer'},
    ],
    brands: ['lg', 'midea'],
  },
  {
    id: 'washers-dryers',
    name: 'Washers & Dryers',
    image: '/assets/categories/washers-dryers.jpg',
    query: 'washer',
    subcategories: [
      {label: 'Front Load Washers', query: 'front load'},
      {label: 'Top Load Washers', query: 'top load'},
      {label: 'Washer Dryers', query: 'washer dryer'},
      {label: 'Tumble Dryers', query: 'dryer'},
      {label: 'Twin Tub', query: 'twin tub'},
    ],
    featured: [
      {image: '/assets/washer.svg', imageAlt: 'Front load washing machine', label: 'Laundry day', headline: 'Gentle on fabrics, easy on you', description: 'Quiet front loaders with quick cycles for busy weeks.', query: 'front load'},
    ],
    brands: ['lg', 'midea'],
  },
  {
    id: 'cookers-microwaves',
    name: 'Cookers & Microwaves',
    image: '/assets/categories/cookers-microwaves.jpg',
    query: 'cooker',
    subcategories: [
      {label: 'Standing Cookers', query: 'cooker'},
      {label: 'Gas Cookers', query: 'gas cooker'},
      {label: 'Microwaves', query: 'microwave'},
      {label: 'Ovens', query: 'oven'},
      {label: 'Cooker Hoods', query: 'hood'},
    ],
    featured: [
      {image: '/assets/microwave.svg', imageAlt: 'Microwave oven', label: 'Quick meals', headline: 'Dinner in minutes', description: 'Even reheating and defrosting at the touch of a button.', query: 'microwave'},
      {image: '/assets/cooker.svg', imageAlt: 'Standing cooker', label: 'Home cooking', headline: 'Cook for the whole family', description: 'Spacious ovens and reliable burners for everyday meals.', query: 'cooker'},
    ],
    brands: ['lg', 'midea'],
  },
  {
    id: 'small-appliances',
    name: 'Kitchen & Home Small Appliances',
    navLabel: 'Small Appliances',
    image: '/assets/categories/kitchen-small-appliances.jpg',
    query: 'kettle',
    subcategories: [
      {label: 'Kettles', query: 'kettle'},
      {label: 'Blenders', query: 'blender'},
      {label: 'Air Fryers', query: 'air fryer'},
      {label: 'Irons', query: 'iron'},
      {label: 'Vacuum Cleaners', query: 'vacuum'},
    ],
    featured: [
      {image: '/assets/kettle.svg', imageAlt: 'Electric kettle', label: 'Morning ritual', headline: 'Tea time, sooner', description: 'Fast-boil kettles with auto shut-off for peace of mind.', query: 'kettle'},
      {image: '/assets/vacuum.svg', imageAlt: 'Vacuum cleaner', label: 'Clean home', headline: 'Less dust, more weekend', description: 'Powerful vacuums that handle floors, rugs and corners.', query: 'vacuum'},
    ],
    brands: ['midea', 'karcher'],
  },
  {
    id: 'built-in-appliances',
    name: 'Built in Appliances',
    image: '/assets/categories/built-in-appliances.jpg',
    query: 'microwave',
    subcategories: [
      {label: 'Built-in Ovens', query: 'built-in oven'},
      {label: 'Hobs', query: 'hob'},
      {label: 'Built-in Microwaves', query: 'built-in microwave'},
      {label: 'Dishwashers', query: 'dishwasher'},
      {label: 'Extractor Hoods', query: 'hood'},
    ],
    featured: [
      {image: '/assets/categories/built-in-appliances.jpg', imageAlt: 'Fitted kitchen with built-in appliances', label: 'Fitted kitchens', headline: 'A seamless kitchen look', description: 'Appliances that sit flush with your cabinets.', query: 'built-in'},
    ],
    brands: ['lg', 'midea'],
  },
  {
    id: 'acs-fans-heaters',
    name: 'ACs, Fans & Heaters',
    image: '/assets/banners/everyday-home.jpg',
    query: 'ac',
    subcategories: [
      {label: 'Split ACs', query: 'split'},
      {label: 'Portable ACs', query: 'portable'},
      {label: 'Fans', query: 'fan'},
      {label: 'Heaters', query: 'heater'},
      {label: 'Dehumidifiers', query: 'dehumidifier'},
    ],
    featured: [
      {image: '/assets/ac.svg', imageAlt: 'Air conditioner', label: 'Stay comfortable', headline: 'Cool air, quiet nights', description: 'Energy-saving air conditioners for every room size.', query: 'ac'},
    ],
    brands: ['lg', 'midea'],
  },
  {
    id: 'health-personal-care',
    name: 'Health & Personal Care',
    image: '/assets/banners/lg-living.jpg',
    query: 'health',
    subcategories: [
      {label: 'Air Purifiers', query: 'purifier'},
      {label: 'Water Dispensers', query: 'dispenser'},
      {label: 'Hair Dryers', query: 'hair dryer'},
      {label: 'Shavers & Trimmers', query: 'trimmer'},
    ],
    featured: [
      {image: '/assets/banners/everyday-home.jpg', imageAlt: 'Bright, airy living space', label: 'Wellbeing', headline: 'Breathe easier at home', description: 'Air purifiers that quietly clear dust and allergens.', query: 'purifier'},
    ],
    brands: ['lg', 'midea'],
  },
];

/** Builds a products-page link, the same way the homepage category links work. */
export function productsHref(filters: {q?: string; brand?: string}) {
  const params = new URLSearchParams();
  if (filters.q) params.set('q', filters.q);
  if (filters.brand) params.set('brand', filters.brand);
  const query = params.toString();
  return query ? `/products?${query}` : '/products';
}

export const featureHref = (feature: MenuFeature, category: MenuCategory) =>
  feature.href ?? productsHref({q: feature.query ?? category.query});
