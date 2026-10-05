import 'reflect-metadata';
import { config } from 'dotenv';
import { resolve } from 'node:path';
import { NestFactory } from '@nestjs/core';
import { DocumentBuilder, SwaggerModule } from '@nestjs/swagger';
import { AppModule } from './app.module';

config({ path: resolve(__dirname, '../.env'), quiet: true });

async function bootstrap() {
  const app = await NestFactory.create(AppModule);
  app.setGlobalPrefix('api/v1');
  app.enableCors({ origin: process.env.ADMIN_ORIGIN ?? 'http://localhost:5173' });
  const options = new DocumentBuilder().setTitle('E-commerce Admin API')
    .setDescription('Development foundation. Business modules are not implemented yet.')
    .setVersion('0.1.0').build();
  SwaggerModule.setup('api/docs', app, SwaggerModule.createDocument(app, options));
  app.enableShutdownHooks();
  await app.listen(Number(process.env.PORT ?? 3000), process.env.HOST ?? '127.0.0.1');
}
bootstrap().catch((error: unknown) => { console.error(error); process.exitCode = 1; });
