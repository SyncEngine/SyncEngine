const Encore = require( '@symfony/webpack-encore' );
const path = require('path');
const dotenv = require('dotenv');

const _env = dotenv.config( { path: '.env.local', override: true } ).parsed;

const buildPath = ( _env && _env.APP_BUILD_PATH ) || 'build';

const isDeploy = process.argv.includes( 'deploy' );
const isDebug = process.argv.includes( 'debug' );

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if ( !Encore.isRuntimeEnvironmentConfigured() ) {
	Encore.configureRuntimeEnvironment(  process.env.NODE_ENV || 'dev' );
}

if ( ! Encore.isDevServer() ) {
	Encore.cleanupOutputBeforeBuild();
}

Encore
	.configureWatchOptions( options => {
		options.poll = 250;
	} )

	// directory where compiled assets will be stored
	.setOutputPath( 'public/' + buildPath + '/' )
	// public path used by the web server to access the output path
	.setPublicPath( '/' + buildPath )
	// only needed for CDN's or subdirectory deploy
	//.setManifestKeyPrefix('build/')

	/*
	 * ENTRY CONFIG
	 *
	 * Each entry will result in one JavaScript file (e.g. app.js)
	 * and one CSS file (e.g. app.scss) if your JavaScript imports CSS.
	 */
	.addEntry( 'app', './assets/app.js' )

	// enables the Symfony UX Stimulus bridge (used in assets/bootstrap.js)
	.enableStimulusBridge( './assets/controllers.json' )

	// When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
	.splitEntryChunks()

	// will require an extra script tag for runtime.js
	// but, you probably want this, unless you're building a single-page app
	.enableSingleRuntimeChunk()

	/*
	 * FEATURE CONFIG
	 *
	 * Enable & configure other features below. For a full
	 * list of features, see:
	 * https://symfony.com/doc/current/frontend.html#adding-more-features
	 */
	.enableBuildNotifications()
	.enableSourceMaps( ! Encore.isProduction() )
	// enables hashed filenames (e.g. app.abc123.css)
	.enableVersioning( Encore.isProduction() && isDeploy )

	// configure Babel
	// .configureBabel((config) => {
	//     config.plugins.push('@babel/a-babel-plugin');
	// })

	// enables and configure @babel/preset-env polyfills
	.configureBabelPresetEnv( ( config ) => {
		config.useBuiltIns = 'usage';
		config.corejs = '3.23';
	} )

	// enables Sass/SCSS support
	.enableSassLoader( ( options ) => {
		/* TMP fix for Bootstrap 5.x: https://github.com/twbs/bootstrap/issues/40962 */
		//options.api = 'modern';
		options.sassOptions.silenceDeprecations = ['mixed-decls', 'color-functions', 'global-builtin', 'import', 'legacy-js-api'];
	} )

	// enabled PostCSS loader.
	.enablePostCssLoader()

	// uncomment if you use TypeScript
	//.enableTypeScriptLoader()

	// uncomment if you use React
	.enableReactPreset()

	// uncomment to get integrity="..." attributes on your script & link tags
	// requires WebpackEncoreBundle 1.4 or higher
	.enableIntegrityHashes( Encore.isProduction() )

	// uncomment if you're having problems with a jQuery plugin
	//.autoProvidejQuery()

	// Auto builds.
	.configureDevServerOptions( options => {
		options.server = {
			type: 'https',
			options: {
				pfx: path.join( process.env.HOME, '.symfony5/certs/default.p12' ),
			},
		};
		options.liveReload = true;
		options.static = {
			watch: false
		};
		options.watchFiles = {
			paths: [
				//'src/**/*.php',
				'templates/**/*'
			],
		};
	} )
;

/*if ( Encore.isDevServer() ) {
	Encore
		// directory where compiled assets will be stored
		.setOutputPath( 'public/.local/' + buildPath + '/' )
		// public path used by the web server to access the output path
		.setPublicPath( '/.local/' + buildPath )
}*/

if ( isDebug ) {
	Encore.addCacheGroup( 'vendor', {
		chunks: 'all',
		test: /[\\/]node_modules[\\/]/,
		name( module ) {
			// get the name. E.g. node_modules/packageName/not/this/part.js
			// or node_modules/packageName
			const packageName = module.context.match(/[\\/]node_modules[\\/](.*?)([\\/]|$)/)[1];

			// npm package names are URL-safe, but some servers don't like @ symbols
			return `npm.${packageName.replace('@', '')}`;
		},
	} );
} else {
	Encore.addCacheGroup( 'vendors', {
		chunks: 'all',
		test: /[\\/]node_modules[\\/]/,
		name: 'vendors',
	} );
}

// Load configuration.
const config = Encore.getWebpackConfig();

config.optimization = { ...config.optimization,
	usedExports: true,
	concatenateModules: true,
}

// export the final configuration
module.exports = config;
