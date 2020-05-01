let mix = require('laravel-mix');

mix.options({ processCssUrls: false })
.sass('resources/assets/sass/shoppe.scss', 'publishable/assets/css')
.js('resources/assets/js/shoppe.js', 'publishable/assets/js').sourceMaps();
