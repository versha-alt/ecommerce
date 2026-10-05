import { Module } from '@nestjs/common';
import { HealthController } from './health/health.controller';
import { DatabaseService } from './database/database.service';
import { CategoryModule } from './modules/category/category.module';
import { ProductModule } from './modules/product/product.module';
import { OrderModule } from './modules/order/order.module';
import { CustomerModule } from './modules/customer/customer.module';
import { SettingsModule } from './modules/settings/settings.module';

@Module({
  imports: [CategoryModule, ProductModule, OrderModule, CustomerModule, SettingsModule],
  controllers: [HealthController], providers: [DatabaseService],
})
export class AppModule {}
