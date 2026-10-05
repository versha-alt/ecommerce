import { DatabaseSync } from 'node:sqlite';
import { mkdirSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { randomUUID, randomBytes, scryptSync, timingSafeEqual } from 'node:crypto';
import { BadRequestException, ConflictException, NotFoundException, UnauthorizedException } from '@nestjs/common';

export type RecordData = { id: string; version: number; createdAt: string; updatedAt: string; [key: string]: any };
export const resources = ['categories', 'products', 'brands', 'attributes', 'attribute-groups', 'attribute-sets', 'customers', 'markets', 'shipping-rules', 'email-templates', 'services'] as const;
const fail = (message: string): never => { throw new BadRequestException(message); };
const nonempty = (value: unknown, label: string): string => typeof value === 'string' && value.trim() ? value.trim() : fail(`${label} is required.`);
const amount = (value: unknown, label: string): number => typeof value === 'number' && Number.isFinite(value) && value >= 0 ? value : fail(`${label} must be a non-negative number.`);
const integer = (value: unknown, label: string): number => Number.isSafeInteger(value) && Number(value) >= 0 ? Number(value) : fail(`${label} must be a non-negative whole number.`);

export class CommerceStore {
  readonly db: DatabaseSync;
  constructor(path = process.env.LOCAL_DATABASE_PATH ?? resolve(__dirname, '../../data/commerce.sqlite')) {
    if (path !== ':memory:') mkdirSync(dirname(path), { recursive: true });
    this.db = new DatabaseSync(path);
    this.db.exec(`PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON; PRAGMA busy_timeout=5000;
      CREATE TABLE IF NOT EXISTS records (resource TEXT NOT NULL, id TEXT NOT NULL, data TEXT NOT NULL, PRIMARY KEY(resource,id));
      CREATE TABLE IF NOT EXISTS audit (id TEXT PRIMARY KEY, data TEXT NOT NULL);
      CREATE TABLE IF NOT EXISTS users (id TEXT PRIMARY KEY, email TEXT UNIQUE, salt TEXT, hash TEXT);
      CREATE TABLE IF NOT EXISTS sessions (token TEXT PRIMARY KEY, user_id TEXT NOT NULL, expires TEXT NOT NULL);
      CREATE TABLE IF NOT EXISTS idempotency (key TEXT PRIMARY KEY, fingerprint TEXT NOT NULL, result TEXT NOT NULL);`);
    if (!this.db.prepare('SELECT id FROM users LIMIT 1').get()) {
      const password = process.env.ADMIN_PASSWORD;
      if (!password || password.length < 12) throw new Error('Set ADMIN_PASSWORD to at least 12 characters in apps/api/.env.');
      const salt = randomBytes(16).toString('hex');
      this.db.prepare('INSERT INTO users VALUES (?, ?, ?, ?)').run(randomUUID(), (process.env.ADMIN_EMAIL ?? 'admin@olive.local').toLowerCase(), salt, scryptSync(password, salt, 64).toString('hex'));
    }
  }
  close() { this.db.close(); }
  onModuleDestroy() { this.close(); }
  transaction<T>(fn: () => T): T {
    this.db.exec('BEGIN IMMEDIATE');
    try { const value = fn(); this.db.exec('COMMIT'); return value; }
    catch (error) { this.db.exec('ROLLBACK'); throw error; }
  }
  login(email: string, password: string) {
    const user = this.db.prepare('SELECT * FROM users WHERE email = ?').get(email?.trim().toLowerCase()) as any;
    if (!user || typeof password !== 'string' || !timingSafeEqual(Buffer.from(user.hash, 'hex'), scryptSync(password, user.salt, 64))) throw new UnauthorizedException('Email or password is incorrect.');
    const token = randomBytes(32).toString('hex');
    this.db.prepare('DELETE FROM sessions WHERE expires < ?').run(new Date().toISOString());
    this.db.prepare('INSERT INTO sessions VALUES (?, ?, ?)').run(token, user.id, new Date(Date.now() + 8 * 3600_000).toISOString());
    return { token, user: { id: user.id, email: user.email, name: 'Administrator', role: 'Administrator' } };
  }
  actor(header?: string) {
    const token = header?.startsWith('Bearer ') ? header.slice(7) : '';
    const user = this.db.prepare('SELECT u.id,u.email FROM sessions s JOIN users u ON u.id = s.user_id WHERE s.token = ? AND s.expires > ?').get(token, new Date().toISOString()) as any;
    if (!user) throw new UnauthorizedException('Your session has expired. Please sign in.');
    return user.email as string;
  }
  logout(header?: string) { this.db.prepare('DELETE FROM sessions WHERE token = ?').run(header?.slice(7) ?? ''); }
  list(resource: string): RecordData[] {
    return (this.db.prepare('SELECT data FROM records WHERE resource = ? ORDER BY rowid DESC').all(resource) as any[]).map(r => JSON.parse(r.data));
  }
  get(resource: string, id: string): RecordData {
    const row = this.db.prepare('SELECT data FROM records WHERE resource = ? AND id = ?').get(resource, id) as any;
    if (!row) throw new NotFoundException('Record not found.');
    return JSON.parse(row.data);
  }
  put(resource: string, record: RecordData) {
    this.db.prepare('INSERT INTO records VALUES (?, ?, ?) ON CONFLICT(resource,id) DO UPDATE SET data = excluded.data').run(resource, record.id, JSON.stringify(record));
  }
  audit(action: string, resource: string, before: any, after: any, actor: string) {
    const event = { id: randomUUID(), actor, action, resource, entityId: after?.id ?? before?.id, name: after?.name ?? after?.reference ?? before?.name, before: before ?? null, after: after ?? null, createdAt: new Date().toISOString() };
    this.db.prepare('INSERT INTO audit VALUES (?, ?)').run(event.id, JSON.stringify(event));
  }
  activity() { return (this.db.prepare('SELECT data FROM audit ORDER BY rowid DESC LIMIT 100').all() as any[]).map(row => JSON.parse(row.data)); }
  ref(resource: string, id: string | undefined, label: string) {
    if (!id) return undefined;
    try { const record = this.get(resource, id); if (['Retired','Inactive'].includes(record.status) || record.enabled === false) fail(`${label} is inactive.`); return record; }
    catch (error) { if (error instanceof NotFoundException) fail(`${label} does not exist.`); throw error; }
  }
  checkResource(resource: string) { if (!(resources as readonly string[]).includes(resource)) throw new NotFoundException('Unknown resource.'); }
  validate(resource: string, data: any, id?: string): any {
    this.checkResource(resource);
    if (!data || typeof data !== 'object' || Array.isArray(data)) fail('A record object is required.');
    const clean: any = { ...data, name: nonempty(data.name, 'Name') };
    delete clean.id; delete clean.version; delete clean.createdAt; delete clean.updatedAt;
    if (clean.name.length > 180) fail('Name must be 180 characters or less.');
    const statuses = resource === 'products' ? ['Draft', 'Active', 'Retired'] : resource === 'customers' ? ['Active', 'Inactive', 'Blocked', 'Retired'] : ['Active', 'Inactive', 'Retired'];
    if (data.status && !statuses.includes(data.status)) fail('Invalid record status.');
    clean.status = data.status ?? (resource === 'products' ? 'Draft' : 'Active');
    if (resource === 'categories') {
      clean.parentId = data.parentId || null;
      clean.slug = nonempty(data.slug, 'Public address');
      if (!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(clean.slug)) fail('Public address must use lowercase letters, numbers and hyphens.');
      if (this.list(resource).some(r => r.slug === clean.slug && r.id !== id)) fail('Public address is already used.');
      const all = this.list(resource).filter(r => r.id !== id).concat([{ ...clean, id: id ?? '__new' }] as any);
      for (const category of all) {
        let current: any = category; const seen = new Set<string>(); let depth = 0;
        while (current) {
          if (seen.has(current.id)) fail('Category hierarchy cannot contain a cycle.');
          seen.add(current.id); depth++;
          if (depth > 3) fail('Category hierarchy supports a maximum of three levels.');
          if (!current.parentId) break;
          const parent = all.find(r => r.id === current.parentId);
          if (!parent || parent.status === 'Retired') fail('Select an existing, active parent category.');
          current = parent;
        }
      }
      if (id) { const old = this.get(resource, id); clean.redirects = [...(old.redirects ?? []), ...(old.slug !== clean.slug ? [old.slug] : [])]; }
    }
    if (resource === 'markets') {
      clean.countryCode = nonempty(data.countryCode, 'Country code').toUpperCase();
      clean.currency = nonempty(data.currency, 'Currency code').toUpperCase();
      if (!/^[A-Z]{2}$/.test(clean.countryCode) || !/^[A-Z]{3}$/.test(clean.currency)) fail('Use a two-letter country and three-letter currency code.');
      clean.language = nonempty(data.language, 'Language');
      try { new Intl.Locale(clean.language); } catch { fail('Enter a valid language tag.'); }
      clean.timeZone = nonempty(data.timeZone, 'Time zone');
      try { new Intl.DateTimeFormat('en', { timeZone: clean.timeZone }); } catch { fail('Enter a valid IANA time zone.'); }
      clean.precision = integer(data.precision ?? 2, 'Currency precision');
      if (clean.precision > 4) fail('Currency precision must be between 0 and 4.');
      clean.taxRate = amount(data.taxRate ?? 0, 'Tax rate');
      if (clean.taxRate > 100) fail('Tax rate cannot exceed 100%.');
      clean.taxInclusive = Boolean(data.taxInclusive); clean.enabled = data.enabled !== false;
      if(this.list(resource).some(m=>m.id!==id && m.currency===clean.currency && m.precision!==clean.precision)) fail('Markets sharing a currency must use the same precision.');
    }
    if (resource === 'attributes') {
      clean.code = nonempty(data.code, 'Attribute code');
      if (!/^[a-z][a-z0-9_]*$/.test(clean.code)) fail('Attribute codes use lowercase letters, numbers and underscores.');
      if (this.list(resource).some(r => r.code === clean.code && r.id !== id)) fail('Attribute code already exists.');
      if (id && this.get(resource, id).code !== clean.code) fail('Permanent attribute codes cannot change.');
      if (!['Text', 'Long text', 'Integer', 'Decimal', 'Yes/No', 'Single choice', 'Multiple choice', 'Date', 'Price', 'Image', 'Swatch'].includes(data.inputType)) fail('Select a supported input type.');
      clean.options = Array.isArray(data.options) ? [...new Set(data.options.map((o: any) => nonempty(o, 'Option')))] : [];
      if (['Single choice','Multiple choice','Swatch'].includes(clean.inputType) && !clean.options.length) fail('Choice attributes need at least one option.');
      if (data.variation && data.inputType !== 'Single choice') fail('Only single-choice attributes can define variants.');
    }
    if (resource === 'attribute-groups') {
      clean.attributeIds = [...new Set(data.attributeIds ?? [])];
      clean.attributeIds.forEach((aid: string) => this.ref('attributes', aid, 'Attribute'));
    }
    if (resource === 'attribute-sets') {
      clean.groupIds = [...new Set(data.groupIds ?? [])];
      clean.groupIds.forEach((gid: string) => this.ref('attribute-groups', gid, 'Attribute group'));
      const ids = clean.groupIds.flatMap((gid: string) => this.get('attribute-groups', gid).attributeIds ?? []);
      if (new Set(ids).size !== ids.length) fail('An attribute may appear only once within a set.');
    }
    if (resource === 'customers') {
      clean.email = nonempty(data.email, 'Email').toLowerCase();
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(clean.email)) fail('Enter a valid email address.');
      if (this.list(resource).some(r => r.email === clean.email && r.id !== id)) fail('A customer with this email already exists. Review the existing record.');
      if (!['Individual','Business'].includes(data.accountType ?? 'Individual')) fail('Invalid account type.');
      clean.accountType = data.accountType ?? 'Individual'; clean.addresses = Array.isArray(data.addresses) ? data.addresses : [];
      const old = id ? this.get(resource, id) : undefined; clean.consentHistory = old?.consentHistory ?? [];
      if (!old || Boolean(old.marketingConsent) !== Boolean(data.marketingConsent)) clean.consentHistory = [...clean.consentHistory, { channel: 'email', purpose: 'marketing', consent: Boolean(data.marketingConsent), source: 'Admin', at: new Date().toISOString() }];
    }
    if (resource === 'products') {
      clean.sku = nonempty(data.sku, 'Stock code');
      if (this.list(resource).some(r => r.sku.toLowerCase() === clean.sku.toLowerCase() && r.id !== id)) fail('Stock code must be unique.');
      if (!['Simple','Variable'].includes(data.type)) fail('Products must be Simple or Variable.');
      clean.parentId = data.parentId || null;
      if (clean.parentId) {
        const parent = this.ref('products', clean.parentId, 'Variable parent')!;
        if (!parent || parent.type !== 'Variable' || parent.parentId || parent.id === id || clean.type !== 'Simple') fail('Variants must be Simple children of a Variable parent.');
        clean.categoryIds = parent.categoryIds; clean.brandId = parent.brandId; clean.attributeSetId = parent.attributeSetId;
      } else { clean.categoryIds = [...new Set(data.categoryIds ?? [])]; }
      clean.categoryIds.forEach((cid: string) => this.ref('categories', cid, 'Category'));
      this.ref('brands', clean.brandId, 'Brand'); this.ref('attribute-sets', clean.attributeSetId, 'Attribute set');
      clean.stock = integer(data.stock ?? 0, 'Physical stock'); clean.reserved = id ? this.get(resource, id).reserved ?? 0 : 0;
      if (clean.stock < clean.reserved) fail('Physical stock cannot be lower than reserved stock.');
      clean.prices = Array.isArray(data.prices) ? data.prices : [];
      const currencies = new Set<string>();
      clean.prices = clean.prices.map((price: any) => {
        if (!this.list('markets').some(m => m.currency === price.currency && m.enabled && m.status === 'Active')) fail(`Configure an enabled market for ${price.currency} first.`);
        if (currencies.has(price.currency)) fail('Only one base price per currency is supported.'); currencies.add(price.currency);
        const precision = this.list('markets').find(m => m.currency === price.currency)!.precision; const value = amount(price.amount, 'Price');
        if (Math.abs(value * 10 ** precision - Math.round(value * 10 ** precision)) > 0.000001) fail('Price exceeds configured currency precision.');
        return { currency: price.currency, amount: value };
      });
      clean.values = data.values ?? {};
      if (clean.attributeSetId) {
        const set = this.get('attribute-sets', clean.attributeSetId);
        const attributes = (set.groupIds ?? []).flatMap((gid: string) => this.get('attribute-groups', gid).attributeIds ?? []).map((aid: string) => this.get('attributes', aid));
        for (const attr of attributes) {
          const value = clean.values[attr.code];
          if (clean.status === 'Active' && attr.required && (value === undefined || value === '')) fail(`${attr.name} is required before activation.`);
          if (value !== undefined && value !== '') {
            if (['Single choice','Swatch'].includes(attr.inputType) && !attr.options.includes(value)) fail(`Invalid ${attr.name} option.`);
            if (['Integer','Decimal','Price'].includes(attr.inputType) && (!Number.isFinite(Number(value)) || (attr.inputType === 'Integer' && !Number.isInteger(Number(value))))) fail(`${attr.name} must be a valid number.`);
            if (attr.unique && this.list(resource).some(p => p.id !== id && p.status !== 'Retired' && p.values?.[attr.code] === value)) fail(`${attr.name} must be unique.`);
          }
        }
      }
      if (clean.parentId) {
        const parent=this.get('products',clean.parentId);
        const attrs = (parent.variationAttributeIds ?? []).map((aid: string) => this.get('attributes', aid));
        if (!attrs.length || attrs.some((attr: RecordData) => !attr.options.includes(clean.values[attr.code]))) fail('Every variant must choose a valid value for each variation attribute.');
        if (this.list(resource).some(p => p.id !== id && p.parentId === clean.parentId && attrs.every((attr: RecordData) => p.values?.[attr.code] === clean.values[attr.code]))) fail('This variant combination already exists.');
      }
      if (clean.type === 'Variable') {
        clean.stock = 0; clean.reserved = 0; clean.variationAttributeIds = [...new Set(data.variationAttributeIds ?? [])];
        if (!clean.variationAttributeIds.length) fail('Select at least one variation attribute.');
        clean.variationAttributeIds.forEach((aid: string) => { const attr = this.ref('attributes', aid, 'Variation attribute')!; if (!attr.variation || attr.inputType !== 'Single choice') fail('Select single-choice attributes enabled for variations.'); });
        if (id && this.list(resource).some(p => p.parentId === id) && JSON.stringify(clean.variationAttributeIds) !== JSON.stringify(this.get(resource,id).variationAttributeIds)) fail('Variation axes cannot change while variants exist.');
      }
      if (id && this.list(resource).some(p => p.parentId === id) && clean.type !== this.get(resource,id).type) fail('Retire variants before changing the parent type.');
    }
    if (resource === 'shipping-rules') {
      this.ref('markets', data.marketId, 'Market') ?? fail('Select a market.');
      clean.charge = amount(data.charge, 'Shipping charge'); clean.freeThreshold = data.freeThreshold === '' || data.freeThreshold == null ? null : amount(data.freeThreshold, 'Free-shipping threshold');
      if (this.list(resource).some(r => r.id !== id && r.marketId === data.marketId && r.status === 'Active' && clean.status === 'Active')) fail('Only one active delivery rule per market is supported; edit or deactivate the existing rule.');
    }
    if (resource === 'email-templates') {
      clean.subject = nonempty(data.subject, 'Subject'); clean.body = nonempty(data.body, 'Body');
      clean.event = nonempty(data.event, 'Event'); clean.language = nonempty(data.language, 'Language');
    }
    if (resource === 'services') {
      if ('credentials' in data || 'secret' in data || 'apiKey' in data) fail('Live credentials are not supported by this local edition.');
      clean.readiness = 'Not connected'; clean.enabled = false;
    }
    return clean;
  }
  save(resource: string, data: any, actor: string, id?: string, version?: number) {
    return this.transaction(() => {
      const before = id ? this.get(resource, id) : undefined;
      if (before && before.version !== version) throw new ConflictException('This record changed. Reload it before saving.');
      const clean = this.validate(resource, data, id); const now = new Date().toISOString();
      const after = { ...clean, id: id ?? randomUUID(), version: (before?.version ?? 0) + 1, createdAt: before?.createdAt ?? now, updatedAt: now };
      this.put(resource, after); this.audit(before ? 'Updated' : 'Created', resource, before, after, actor); return after;
    });
  }
  retire(resource: string, id: string, version: number, actor: string) {
    return this.transaction(() => {
      this.checkResource(resource); const before = this.get(resource,id);
      if (before.version !== version) throw new ConflictException('This record changed. Reload it before retiring.');
      if (resource === 'categories' && this.list(resource).some(r => r.parentId === id && r.status !== 'Retired')) fail('Retire child categories first.');
      if (resource === 'products' && (before.reserved > 0 || this.list(resource).some(r => r.parentId === id && r.status !== 'Retired'))) fail('Release reservations and retire variants first.');
      if (resource === 'attributes' && this.list('attribute-groups').some(g => g.attributeIds.includes(id) && g.status !== 'Retired')) fail('Remove this attribute from active groups before retiring.');
      if (resource === 'attribute-groups' && this.list('attribute-sets').some(s => s.groupIds.includes(id) && s.status !== 'Retired')) fail('Remove this group from active sets first.');
      const after = { ...before, status: 'Retired', enabled: false, version: before.version + 1, updatedAt: new Date().toISOString() };
      this.put(resource,after); this.audit('Retired',resource,before,after,actor); return after;
    });
  }
  createOrder(data: any, key: string, actor: string) {
    if (!key || key.length > 200) fail('A valid Idempotency-Key is required.');
    const fingerprint = JSON.stringify(data);
    return this.transaction(() => {
      const previous = this.db.prepare('SELECT * FROM idempotency WHERE key = ?').get('order:'+key) as any;
      if (previous) { if (previous.fingerprint !== fingerprint) throw new ConflictException('Idempotency key was used for different order details.'); return JSON.parse(previous.result); }
      const market = this.ref('markets',data.marketId,'Market') ?? fail('Select an enabled market.');
      const customer = this.ref('customers',data.customerId,'Customer') ?? fail('Select a customer.');
      if (customer.status !== 'Active') fail('Customer account is not active.');
      const deliveryAddress = nonempty(data.deliveryAddress, 'Delivery address');
      const shipping = this.list('shipping-rules').find(r => r.marketId === market.id && r.status === 'Active') ?? fail('Configure an active delivery rule for this market.');
      if (!Array.isArray(data.lines) || !data.lines.length || data.lines.length > 100) fail('Choose between 1 and 100 order lines.');
      const scale = 10 ** market.precision; let subtotal = 0; let taxTotal = 0; const seen = new Set<string>();
      const lines = data.lines.map((line: any) => {
        if (seen.has(line.productId)) fail('Combine duplicate products in one order line.'); seen.add(line.productId);
        const product = this.ref('products',line.productId,'Product');
        if (!product || product.status !== 'Active' || product.type === 'Variable') fail('Select an active Simple product or variant.');
        if (product.parentId && this.get('products',product.parentId).status !== 'Active') fail('The variable parent is not active.');
        const quantity = integer(line.quantity,'Quantity'); if (!quantity) fail('Quantity must be greater than zero.');
        if (product.stock - product.reserved < quantity) fail(`Insufficient available stock for ${product.name}.`);
        const price = product.prices.find((p: any) => p.currency === market.currency) ?? fail(`No ${market.currency} price for ${product.name}.`);
        const baseMinor = Math.round(price.amount * scale) * quantity;
        const taxMinor = Math.round(market.taxInclusive ? baseMinor - baseMinor / (1 + market.taxRate / 100) : baseMinor * market.taxRate / 100);
        const totalMinor = baseMinor + (market.taxInclusive ? 0 : taxMinor); subtotal += baseMinor; taxTotal += taxMinor;
        const after = { ...product, reserved: product.reserved + quantity, version: product.version + 1, updatedAt: new Date().toISOString() };
        this.put('products',after); this.audit('Stock reserved','products',product,after,actor);
        return { productId: product.id, name: product.name, sku: product.sku, quantity, unitPrice: price.amount, taxRate: market.taxRate, taxAmount: taxMinor/scale, total: totalMinor/scale, warranty: product.warranty ?? '' };
      });
      const charge = shipping.freeThreshold !== null && subtotal >= Math.round(shipping.freeThreshold * scale) ? 0 : Math.round(shipping.charge * scale);
      const now = new Date().toISOString(); const id = randomUUID();
      const order: RecordData = { id, version:1, reference:'ORD-'+id.slice(0,8).toUpperCase(), name:customer.name, customerId:customer.id, customer: { name:customer.name,email:customer.email,phone:customer.phone }, marketId:market.id, marketSnapshot:market, deliverySnapshot:shipping, deliveryAddress, currency:market.currency, precision:market.precision, lines, subtotal:subtotal/scale, taxTotal:taxTotal/scale, shippingTotal:charge/scale, total:(subtotal+(market.taxInclusive?0:taxTotal)+charge)/scale, status:'Confirmed', paymentStatus:'Unpaid', fulfilmentStatus:'Reserved', notes:data.notes??'', payments:[], createdAt:now,updatedAt:now };
      this.put('orders',order); this.audit('Order placed','orders',null,order,actor);
      this.db.prepare('INSERT INTO idempotency VALUES (?, ?, ?)').run('order:'+key,fingerprint,JSON.stringify(order)); return order;
    });
  }
