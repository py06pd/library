var path = require('path');
var webpack = require('webpack');
var ManifestPlugin = require('webpack-manifest-plugin');

const devBuild = process.env.NODE_ENV !== 'production';

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
        },
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
                options: {
                    presets: ['es2015'],
                },
                exclude: /node_modules/,
            },
            {
                test: /\.css$/,
                loader: 'style-loader!css-loader',
            },
            {
                test: /\.(eot|svg|ttf|woff|woff2)(\?\S*)?$/,
                loader: 'file-loader',
            },
            {
                test: /\.(png|jpe?g|gif|svg)(\?\S*)?$/,
                loader: 'file-loader',
                query: {
                    name: '[name].[ext]?[hash]',
                },
            },
            {
                test: /\.html$/,
                use: 'html-loader',
            },
            {
                test: /\.(js|vue)$/,
                enforce: 'pre',
                loader: 'eslint-loader',
                options: {
                    emitWarning: true,
                },
            },
        ],
    },
    plugins: [
        new ManifestPlugin({ basePath : 'js/' })
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
