const path = require("path");
const webpack = require("webpack");
const WebpackMessages = require('webpack-messages');
const WebpackBar = require('webpackbar');
const ESLintPlugin = require('eslint-webpack-plugin');
const HtmlWebpackPlugin = require('html-webpack-plugin')

module.exports = {
    mode: process.env.NODE_ENV,
    watchOptions: {
        aggregateTimeout: 200,
        poll: 1000,
    },
    entry: path.resolve(__dirname, './src/index.js'),
    output: {
        path: path.join(__dirname, "build"),
        filename: 'index.js',
        publicPath: 'build/',
    },
    module: {
        rules: [
            {
                test: /\.(js|jsx)$/,
                exclude: [
                    path.resolve(__dirname, "node_modules"),
                    path.resolve(__dirname, "build"),
                ],
                use: {
                    loader: "babel-loader",
                    options: {
                        presets: [
                            "@babel/preset-env",
                            "@babel/preset-react"
                        ],
                    },
                },
            },
            {
                test: /\.s[ac]ss$/i,
                use: [
                    // Creates `style` nodes from JS strings
                    "style-loader",
                    // Translates CSS into CommonJS
                    "css-loader",
                    // Compiles Sass to CSS
                    "sass-loader",
                ],
            },
            {
                test: /\.svg$/,
                use: ['@svgr/webpack'],
            },
            {
                test: /\.(png|jpe?g|gif)$/i,
                use: [
                    {
                        loader: 'file-loader',
                    },
                ],
            },
        ],
    },
    resolve: {
        extensions: ['*', '.js', '.jsx'],
    },
    devtool: "source-map",
    plugins: [
        new webpack.BannerPlugin(`Copyright 2022 NiWee Productions.`),
        new WebpackMessages({
            name: 'client',
            logger: str => console.log(`>> ${str}`)
        }),
        new WebpackBar({
            name: "NiwHiboutik",
            color: "#412f97",
            basic: false,
            profile: true,
            fancy: true,
            reporters: [
                'fancy',
            ],
        }),
        new ESLintPlugin(),
    ],
};