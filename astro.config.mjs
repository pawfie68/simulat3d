import { defineConfig } from 'astro/config';
export default defineConfig({
  site: 'https://simulat3d.eu',
  devToolbar: { enabled: false },
  vite: { server: { proxy: { '/api/': 'http://127.0.0.1:4322' } } }
});
