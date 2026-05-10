import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const fromEnv = process.env.API_PROXY_TARGET ?? env.API_PROXY_TARGET ?? ''
  const proxyTarget =
    String(fromEnv).trim() || 'http://127.0.0.1:8080'

  return {
    plugins: [vue()],
    server: {
      host: true,
      proxy: {
        '/api': {
          target: proxyTarget,
          changeOrigin: true,
        },
      },
    },
  }
})
