const api=process.env.LARAVEL_API_URL||'http://127.0.0.1:8000';
export default {images:{remotePatterns:[{protocol:'https',hostname:'**'}]},async rewrites(){return [{source:'/api/v1/media/:path*',destination:`${api}/api/v1/media/:path*`}]}};
