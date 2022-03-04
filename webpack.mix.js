let mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for your application, as well as bundling up your JS files.
 |
 */

const BROWSER_SYNC_PROXY = 'localhost';
const EXTRACT_VENDORS = ['vue'];

mix.setPublicPath('src/assetbundles/');

/*
|--------------------------------------------------------------------------
| JavaScript
|--------------------------------------------------------------------------
|
| There is a lot going on here: ES2017+ modules compilation, build and
| compile .vue components, hot module replacement and tree-shaking.
| You can even bundle multiple files into one or have multiple
| entry/output points.
|
| Mix Docs: https://laravel-mix.com/docs/2.1/mixjs
|
*/

mix.js('src/assetbundles/ohdear/src/js/OhDear.js', 'ohdear/dist/js/').vue({version: 2});
mix.js('src/assetbundles/ohdear/src/js/OhDearWidget.js', 'ohdear/dist/js/').vue({version: 2});
mix.sourceMaps();

/*
|--------------------------------------------------------------------------
| Library Code Splitting
|--------------------------------------------------------------------------
|
| Extract vendor libraries into their own file.
|	- Application code:				app.js
|	- Vendor Libraries:				vendor.js
|	- Manifest (webpack Runtime):	manifest.js
|
| Do not forget to embed all files in your template.
|
| Mix Docs: https://laravel-mix.com/docs/2.1/extract
|
*/

// mix.extract(EXTRACT_VENDORS);

/*
|--------------------------------------------------------------------------
| SCSS + TailwindCSS + Purgecss
|--------------------------------------------------------------------------
|
| Mix Docs: https://laravel-mix.com/docs/2.1/css-preprocessors
| Tailwind: https://tailwindcss.com/docs/installation#laravel-mix
| Purgecss: https://www.purgecss.com/with-webpack#options
| Purgecss Mix Extension: https://github.com/spatie/laravel-mix-purgecss
|
*/

mix.postCss('src/assetbundles/ohdear/src/css/OhDear.css', 'ohdear/dist/css/', [
    require('tailwindcss'),
]);
mix.postCss('src/assetbundles/ohdear/src/css/OhDearWidget.css', 'ohdear/dist/css/', [
    require('tailwindcss'),
]);

/*
|--------------------------------------------------------------------------
| Browser Sync
|--------------------------------------------------------------------------
|
| Heads up: All files in templates/_layouts are not watched because they
| are changed each time an asset versioning happens. If they would be
| watched browser sync would fire multiple times. That's not cool.
|
| Mix Docs: https://laravel-mix.com/docs/2.1/browsersync
| Settings: https://browsersync.io/docs/options/
|
*/

mix.browserSync({
    proxy: BROWSER_SYNC_PROXY,
    host: BROWSER_SYNC_PROXY,
    open: 'external',
    files: [
        'src/templates/**/*.twig',
        'src/assetbundles/**/src/**/*.{js,vue,css}',
        'tailwind.config.js'
    ],
});

/*
|--------------------------------------------------------------------------
| Versioning
|--------------------------------------------------------------------------
|
| Only applied in production mode. The mode is being set directly in
| the npm script via the NODE_ENV constant.
|
| Mix Docs: https://laravel-mix.com/docs/2.1/versioning
|
*/

if (mix.inProduction()) {
    mix.version();
}
