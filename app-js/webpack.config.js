const path = require('path');
const webpack = require('webpack');
const CleanWebpackPlugin = require('clean-webpack-plugin');
const ExtractTextPlugin = require('extract-text-webpack-plugin');
const ManifestPlugin = require('webpack-manifest-plugin');
const CircularDependencyPlugin = require('circular-dependency-plugin');

/*
To get a visualisation of what makes up the JS bundle size:
    1. npm install webpack-bundle-analyzer
    2. add above: const BundleAnalyzerPlugin = require('webpack-bundle-analyzer').BundleAnalyzerPlugin;
    3. add new BundleAnalyzerPlugin() to the plugins array
*/

const devBuild = process.env.NODE_ENV !== 'production';

const cssLoader = {
    loader: 'css-loader',
    options: { minimize: true, sourceMap: true },
};

const config = {
    entry: {
        main: './app.js',
    },
    output: {
        filename: '[name].[chunkhash].js',
        path: path.resolve(__dirname, '../web/js'),
        publicPath: 'js/',
    },
    devtool: 'source-map',
    resolve: {
        alias: {
            'vue$': 'vue/dist/vue.esm.js',
            '@': path.resolve(__dirname, '.'),
        },
        extensions: ['*', '.js', '.vue', '.json'],
    },
    module: {
        rules: [
            {
                test: /\.vue$/,
                loader: 'vue-loader',
            },
            {
                test: /\.js$/,
                loader: 'babel-loader',
                exclude: /node_modules/,
            },

            // Extract the CSS from all our modules and output them as a separate minimized file
            {
                test: /\.css$/,
                loader: ExtractTextPlugin.extract({
                    use: [cssLoader],
                }),
            },

            {
                test: /\.scss$/,
                use: ExtractTextPlugin.extract({
                    fallback: 'style-loader',
                    use: [cssLoader, 'sass-loader'],
                }),
            },

            {
                test: /\.(eot|svg|ttf|woff|woff2)(\?\S*)?$/,
                loader: 'file-loader',
                options: {
                    name: 'css/images/[name].[hash].[ext]',
                },
            },

            // Images under 9000 bytes get converted to base64 and in-lined in the CSS file
            {
                test: /\.(png|jpe?g|gif)(\?\S*)?$/,
                use: [
                    {
                        loader: 'url-loader',
                        options: {
                            name: 'css/images/[name].[hash].[ext]',
                            limit: 9000,
                        },
                    },
                ],
            },
            {
                test: /\.html$/,
                use: 'html-loader',
            },
            {
                test: /\.(js|vue)$/,
                enforce: 'pre',
                loader: 'eslint-loader',
                exclude: /node_modules/,
                options: {
                    emitWarning: true,
                },
            },
        ],
    },
    plugins: [
        new CleanWebpackPlugin(['../web/js', '../web/*.css', '../web/*.css.map'], {
            // allow folders outside of the project root (app-js) to be purged
            allowExternal: true,
            watch: true,
        }),
        new ManifestPlugin(),
        new ExtractTextPlugin({
            // TODO: Moving this to a css folder breaks the references to our images
            filename: '[name].[chunkhash].css',
        }),
        new CircularDependencyPlugin({
            // Exclude detection of files based on a RegExp
            exclude: /node_modules/,
            // Add warnings instead of errors
            failOnError: false,
            // Disallow cycles that include an asynchronous import since we're not using them
            allowAsyncCycles: false,
            // Set the current working directory for displaying module paths
            cwd: process.cwd(),
        }),
    ],
};

if(devBuild) {
    console.log('Webpack dev build');
} else {
    console.log('Webpack production build');
    config.plugins.push(
        new webpack.optimize.UglifyJsPlugin({
            minimize: true,
            sourceMap: true,
        })
    );

    // Set the NODE_ENV variable so that vue.js also performs a production build
    config.plugins.push(
        new webpack.DefinePlugin({
            'process.env': {
                NODE_ENV: '"production"',
            },
        })
    );
}

module.exports = config;
