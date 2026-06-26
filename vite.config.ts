import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { google } from 'laravel-vite-plugin/fonts';
import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import vuetify from 'vite-plugin-vuetify';
import Components from 'unplugin-vue-components/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            refresh: true,
            fonts: [
                google('Barlow', {
                    weights: [400, 500, 600],
                }),
                google('Barlow Condensed', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        vue(),
        vuetify({
            autoImport: true,
            styles: {
                configFile: 'resources/js/styles/app.scss',
            },
        }),
        Components({
            dirs: ['resources/js/components'],
            dts: 'resources/js/components.d.ts',
        }),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    build: {
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
            },
        },
    },
});
