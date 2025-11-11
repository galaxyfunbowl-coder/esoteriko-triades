import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import { resolve } from 'path'
export default defineConfig({
  plugins:[react()],
  root: resolve(__dirname),
  build:{
    outDir:'dist',
    emptyOutDir:false,
    rollupOptions:{
      input: resolve(__dirname,'admin.setup.jsx'),
      output:{ entryFileNames:'assets/admin-setup.js', assetFileNames:'assets/[name][extname]' }
    }
  }
})

