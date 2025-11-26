const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const DependencyExtractionWebpackPlugin = require('@wordpress/dependency-extraction-webpack-plugin'); 

module.exports = {
  mode: 'production', 
  entry: {
    'noka-gallery-module': './src/index.jsx',
  },
  
  // FIX: Tell Webpack NOT to bundle these. Use WordPress's global versions instead.
  externals: {
    react: 'React',
    'react-dom': 'ReactDOM',
    jquery: 'jQuery',
    'lodash': 'lodash'
  },

  plugins: [
    new DependencyExtractionWebpackPlugin({
        injectPolyfill: true,
        requestToExternal: (request) => {
            if (request.startsWith('@divi/module-library')) return ['window', 'divi', 'moduleLibrary'];
            if (request.startsWith('@divi/')) return ['window', 'divi', request.replace('@divi/', '')];
        },
    }),
    new MiniCssExtractPlugin({
      filename: 'noka-gallery-module.css',
    }),
  ],

  module: {
    rules: [
      {
        test: /\.jsx?$/,
        exclude: /node_modules/,
        use: [
          {
            loader: 'babel-loader',
            options: {
              presets: ['@babel/preset-env', '@babel/preset-react'],
              cacheDirectory: true,
            },
          }
        ]
      },
      {
        test: /\.css$/,
        use: [ MiniCssExtractPlugin.loader, 'css-loader' ],
      },
    ]
  },
  resolve: {
    extensions: ['.js', '.jsx'],
  },
  output: {
    libraryTarget: 'window', // Expose to window instead of 'this'
    filename: '[name].js', 
    path: path.resolve(__dirname, 'build'),
  },
};