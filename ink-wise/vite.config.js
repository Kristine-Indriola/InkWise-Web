import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
  plugins: [
    react(),
    laravel({
      input: [
        'resources/css/app.css',
        'resources/css/customer/customer.css',
        'resources/js/app.js',
        'resources/js/customer/customer.js',
        'resources/js/staff/template-editor/main.jsx',
      ],
      refresh: true,
    }),
  ],
})
