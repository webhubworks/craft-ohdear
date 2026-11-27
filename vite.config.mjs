import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import tailwindcss from 'tailwindcss';
import autoprefixer from 'autoprefixer';

// One IIFE build per asset bundle, because Craft registers them as classic scripts.
// IIFE can't split code, so `npm run build` runs this once per entry.
const entry = process.env.OHDEAR_ENTRY ?? 'OhDear';

export default defineConfig({
    plugins: [vue()],
    css: {
        postcss: { plugins: [tailwindcss, autoprefixer] },
    },
    build: {
        outDir: 'src/assetbundles/ohdear/dist',
        emptyOutDir: false,
        sourcemap: true,
        // Emit the CSS as its own file instead of injecting it from the JS.
        cssCodeSplit: false,
        rolldownOptions: {
            input: { [entry]: `src/assetbundles/ohdear/src/js/${entry}.js` },
            output: {
                format: 'iife',
                entryFileNames: 'js/[name].js',
                assetFileNames: `css/${entry}[extname]`,
            },
        },
    },
});
