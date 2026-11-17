import { env } from 'node:process'
import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'

export default defineConfig({
  plugins: [react()],
  server: {
    proxy: {
      '^/src/(recipes|recipe|add_recipe|login|register|logout|session|profile|recipe_delete|reviewboard)\\.php(?:\\?.*)?$': {
        target: env.PHP_BACKEND_URL || 'http://127.0.0.1:8000',
      },
    },
  },
})
