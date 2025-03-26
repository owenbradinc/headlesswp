const config = require("@wordpress/scripts/config/webpack.config");
const WooCommerceDependencyExtractionWebpackPlugin = require("@woocommerce/dependency-extraction-webpack-plugin");

module.exports = {
  ...config,
  entry: {
    "openapi": "./includes/openapi/assets/js/openapi.js",
  },
  plugins: [
    ...config.plugins.filter(
      (plugin) =>
        plugin.constructor.name !== "DependencyExtractionWebpackPlugin"
    ),
    new WooCommerceDependencyExtractionWebpackPlugin(),
  ],
};
