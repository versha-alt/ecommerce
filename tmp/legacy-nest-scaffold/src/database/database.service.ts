import { Injectable, OnModuleDestroy } from '@nestjs/common';
import { Pool } from 'pg';

@Injectable()
export class DatabaseService implements OnModuleDestroy {
  private readonly pool = process.env.DATABASE_URL
    ? new Pool({ connectionString: process.env.DATABASE_URL, connectionTimeoutMillis: 3000 })
    : undefined;
  constructor() { this.pool?.on('error', () => console.error('PostgreSQL connection error')); }
  async isReady(): Promise<boolean> {
    if (!this.pool) return false;
    try { await this.pool.query('SELECT 1'); return true; }
    catch { return false; }
  }
  async onModuleDestroy() { await this.pool?.end(); }
}
