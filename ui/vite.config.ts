import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  server: {
    host: '0.0.0.0',
    port: 3000,
    allowedHosts: ['ui-eventdrivenmicroservice.up.railway.app'],
    proxy: {
      '/svc/sales': {
        target: 'http://sales-service:8000',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/svc\/sales/, ''),
      },
      '/svc/invoice': {
        target: 'http://invoice-service:8000',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/svc\/invoice/, ''),
      },
      '/svc/payment': {
        target: 'http://payment-service:8000',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/svc\/payment/, ''),
      },
      '/svc/inventory': {
        target: 'http://inventory-service:8000',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/svc\/inventory/, ''),
      },
    },
  },
});
