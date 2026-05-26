import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import cssInjectedByJsPlugin from 'vite-plugin-css-injected-by-js';
import { resolve } from 'path';

export default defineConfig({
  plugins: [
    react(),
    cssInjectedByJsPlugin(),
  ],
  build: {
    lib: {
      entry: resolve(__dirname, 'src/main.jsx'),
      name: 'SileoToasterGlobal',
      fileName: () => 'sileo-toaster.bundle.js',
      formats: ['iife'],
    },
    outDir: resolve(__dirname, '../assets/js'),
    emptyOutDir: false,
    rollupOptions: {
      // By not specifying external, everything (React, ReactDOM, Sileo) will be bundled together
    },
  },
  define: {
    'process.env.NODE_ENV': '"production"',
  },
});
