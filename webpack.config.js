const config = require("@wordpress/scripts/config/webpack.config");
const WooCommerceDependencyExtractionWebpackPlugin = require("@woocommerce/dependency-extraction-webpack-plugin");
module.exports = {
  ...config,
  externals: {
    "react": "React",
    "react-dom": "ReactDOM",
    "@wordpress/element": "wp.element",
  },
  entry: {
    "js/openapi": "./assets/js/openapi.js",
    "css/openapi": "./assets/css/openapi.scss",
  },
  plugins: [
    ...config.plugins.filter(
      (plugin) =>
        plugin.constructor.name !== "DependencyExtractionWebpackPlugin"
    ),
    new WooCommerceDependencyExtractionWebpackPlugin(),
  ],
};
