import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
  plugins: [
    react(),
    laravel({
      input: [
        'resources/css/admin/template/edit.css',
        'resources/css/admin/template/image.css',
        'resources/css/admin/template/template.css',
        'resources/css/app.css',
        'resources/css/customer/customer.css',
        // Studio assets
        'resources/css/customer/studio.css',
        'resources/css/customer/editing.css',
        'resources/js/app.js',
        'resources/js/admin/template/editor.js',
        'resources/js/admin/template/template.js',
        'resources/js/customer/editing.js',
        'resources/js/customer/customer.jsx',
        // Studio entry
        'resources/js/customer/studio/main.jsx',
        'resources/js/customer/studio/svg-template-editor.jsx',
        'resources/js/staff/template-editor/main.jsx',
      ],
      refresh: true,
    }),
  ],
})
