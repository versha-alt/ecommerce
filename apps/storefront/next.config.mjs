const api = process.env.LARAVEL_API_URL || 'http://127.0.0.1:8000';

export default {
  allowedDevOrigins: ['http://10.0.0.17:3000', 'http://192.168.1.26:3000'],
  images: { remotePatterns: [{ protocol: 'https', hostname: '**' }] },
  async rewrites() {
    return [{ source: '/api/v1/media/:path*', destination: `${api}/api/v1/media/:path*` }];
  },
};
