/**
 * External Dependencies.
 */
const path = require( 'path' );

// Inspo from Sage Tubiz :).
const WooCommerceDependencyExtractionWebpackPlugin = require( '@woocommerce/dependency-extraction-webpack-plugin' );

const wcDepMap = {
	'@woocommerce/blocks-registry': ['wc', 'wcBlocksRegistry'],
	'@woocommerce/settings'       : ['wc', 'wcSettings']
};

const wcHandleMap = {
	'@woocommerce/blocks-registry': 'wc-blocks-registry',
	'@woocommerce/settings'       : 'wc-settings'
};

const requestToExternal = (request) => {
	if ( wcDepMap[ request ] ) {
		return wcDepMap[ request ];
	}
};

const requestToHandle = ( request ) => {
	if ( wcHandleMap[ request ]) {
		return wcHandleMap[ request ];
	}
};
// Be like say Inspo ends here. 👊

/**
 * WordPress Dependencies
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config.js' );

module.exports = {
	...defaultConfig,
	mode: 'production',
	entry: {
		frontend: path.resolve( __dirname, 'assets/js/src', 'frontend.js' ),
		'blocks/frontend': path.resolve( __dirname, 'assets/js/src/blocks', 'frontend.js' ),
		//admin: path.resolve( __dirname, 'assets/js/src', 'admin.js' ),
	},
	output: {
		path: path.resolve( __dirname, 'assets/js/build' ),
		filename: '[name].js',
	},
	plugins: [
		...defaultConfig.plugins.filter(
			(plugin) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new WooCommerceDependencyExtractionWebpackPlugin({
			requestToExternal,
			requestToHandle
		})
	],
};
