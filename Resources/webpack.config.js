let merge = require('webpack-merge');
let path = require('path');
let baseConfig = require(
    path.join(process.env.WEBPACK_BASE_PATH, 'webpack.config.base.js')
);

module.exports = merge(baseConfig.get(__dirname), {
    entry: {
        'js/results': './Resources/assets/js/results.js',
    },
});
