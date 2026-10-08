import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

const lanHosts = ['localhost', '127.0.0.1', '10.0.0.17', '192.168.1.26'];

export default defineConfig({ plugins: [react()], server: {
  host: '0.0.0.0',
  port: 5173,
  strictPort: true,
  allowedHosts: lanHosts,
  hmr: { host: '10.0.0.17', clientPort: 5173 },
  proxy: { '/api': 'http://127.0.0.1:8000' },
} });

