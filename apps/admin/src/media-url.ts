const storefrontOrigin = (import.meta.env.VITE_STOREFRONT_URL || (import.meta.env.DEV ? `${window.location.protocol}//${window.location.hostname}:3000` : window.location.origin)).replace(/\/$/, '');

export function mediaUrl(value: string): string {
  if (/^\/(?:assets|images|brand)\//.test(value)) {
    return storefrontOrigin + value;
  }

  return value;
}
