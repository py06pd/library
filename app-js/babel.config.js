const presets = ['@babel/preset-env'];
const plugins = [['component',
    {
        'libraryName': 'element-ui',
        'styleLibraryName': 'theme-default',
    },
]];

module.exports = { presets, plugins };
