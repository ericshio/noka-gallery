const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const DependencyExtractionWebpackPlugin = require('@wordpress/dependency-extraction-webpack-plugin'); 

module.exports = {
  mode: 'production', 
  
  entry: {
    'noka-gallery-module': './src/index.jsx',
  },

  externals: {
    react: 'React',
  },

  plugins: [
    // Automatically handles dependency externals and creates the .asset.php file
    new DependencyExtractionWebpackPlugin({
        injectPolyfill: true,
        requestToExternal: (request) => {
            // Treat Divi's module library as an external dependency
            if (request.startsWith('@divi/module-library')) {
                return ['window', 'divi', 'moduleLibrary'];
            }
        },
    }),
    
    // Extracts CSS to a separate file
    new MiniCssExtractPlugin({
      filename: 'noka-gallery-module.css',
    }),
  ],

  module: {
    rules: [
      // 1. Handle JS/JSX files
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

      // 2. Handle CSS files
      {
        test: /\.css$/,
        use: [
          MiniCssExtractPlugin.loader,
          {
            loader: 'css-loader',
            options: {
              importLoaders: 1,
            },
          },
        ],
      },
    ]
  },

  resolve: {
    extensions: ['.js', '.jsx'],
  },

  // Determine where the created bundles will be outputted.
  output: {
    // Ensure the module is available to the global context 
    libraryTarget: 'this', 
    
    filename: '[name].js', 
    path: path.resolve(__dirname, 'build'),
  },
};