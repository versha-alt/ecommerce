import 'reflect-metadata';
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { ServiceUnavailableException } from '@nestjs/common';
import { HealthController } from '../src/health/health.controller';
import { DatabaseService } from '../src/database/database.service';

test('readiness rejects unavailable storage instead of claiming success', async () => {
  const database = { isReady: async () => false } as DatabaseService;
  await assert.rejects(new HealthController(database).ready(), ServiceUnavailableException);
});
test('readiness reports a confirmed connection', async () => {
  const database = { isReady: async () => true } as DatabaseService;
  assert.deepEqual(await new HealthController(database).ready(), { status: 'ok', database: 'connected' });
});
