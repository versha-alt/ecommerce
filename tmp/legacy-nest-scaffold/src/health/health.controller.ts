import { Controller, Get, Inject, ServiceUnavailableException } from '@nestjs/common';
import { ApiOperation, ApiTags } from '@nestjs/swagger';
import { DatabaseService } from '../database/database.service';

@ApiTags('health')
@Controller('health')
export class HealthController {
  constructor(@Inject(DatabaseService) private readonly database: DatabaseService) {}
  @Get()
  @ApiOperation({ summary: 'API process liveness' })
  live() { return { status: 'ok', service: 'ecomm-api', version: '0.1.0' }; }
  @Get('ready')
  @ApiOperation({ summary: 'PostgreSQL readiness; Redis and search are not wired yet' })
  async ready() {
    if (!(await this.database.isReady())) {
      throw new ServiceUnavailableException({ status: 'unavailable', database: 'unavailable' });
    }
    return { status: 'ok', database: 'connected' };
  }
}
