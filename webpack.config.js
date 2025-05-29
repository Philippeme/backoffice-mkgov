// webpack.config.js
const Encore = require('@symfony/webpack-encore');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // public path used by the web server to access the output path
    .setPublicPath('/build')

    // Entry points for the admin interface
    .addEntry('admin', './assets/js/admin/app.js')
    .addStyleEntry('admin-styles', './assets/styles/admin/app.scss')

    // Entry point for dashboard specific functionality
    .addEntry('dashboard', './assets/js/admin/dashboard.js')

    // Entry points for other admin sections
    .addEntry('requests', './assets/js/admin/requests.js')
    .addEntry('procedures', './assets/js/admin/procedures.js')
    .addEntry('documents', './assets/js/admin/documents.js')
    .addEntry('users', './assets/js/admin/users.js')
    .addEntry('entities', './assets/js/admin/entities.js')
    .addEntry('system', './assets/js/admin/system.js')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // will require an extra script tag for runtime.js
    .enableSingleRuntimeChunk()

    // clean the output folder before each build
    .cleanupOutputBeforeBuild()

    // enable source maps during development
    .enableSourceMaps(!Encore.isProduction())

    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    // configure Babel
    .configureBabel((config) => {
        config.plugins.push('@babel/plugin-proposal-class-properties');
    })

    // enables @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
    })

    // enable Sass/SCSS support
    .enableSassLoader()

    // enable PostCSS support
    .enablePostCssLoader()

    // uncomment if you use TypeScript
    // .enableTypeScriptLoader()

    // uncomment if you use React
    // .enableReactPreset()

    // uncomment to get integrity="..." attributes on your script & link tags
    // requires WebpackEncoreBundle 1.4 or higher
    .enableIntegrityHashes(Encore.isProduction())

    // uncomment if you're having problems with a jQuery plugin
    .autoProvidejQuery()

    // Copy static files
    .copyFiles({
        from: './assets/images',
        to: 'images/[path][name].[hash:8].[ext]',
        pattern: /\.(png|jpg|jpeg|gif|ico|svg|webp)$/
    })

    // Configure optimization for production
    .configureOptimization((optimization) => {
        if (Encore.isProduction()) {
            optimization.minimizer = [
                '...',
                new (require('css-minimizer-webpack-plugin'))()
            ];
        }
    });

module.exports = Encore.getWebpackConfig();