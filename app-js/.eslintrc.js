module.exports = {
    "env": {
        "browser": true,
        "commonjs": true,
        "es6": true,
        "node": true,
        "jest/globals": true
    },
    "extends": [
        "eslint:recommended",
        "plugin:vue/recommended",
    ],
    "parserOptions": {
        "sourceType": "module",
    },
    "rules": {
        "comma-dangle": ["error", "always-multiline"],
        "indent": ["error", 4, {"SwitchCase": 1}],
        "linebreak-style": ["error", "windows"],
        "quotes": ["error", "single"],
        "semi": ["error", "always"],
        "no-unused-vars": ["warn"],
        "no-console": 0,
        "no-trailing-spaces": ["error", { "skipBlankLines": true }],
        "keyword-spacing": ["error", { "after": true}],
        "space-before-blocks": ["error", "always"],
        "brace-style": ["error", "1tbs"],
        "space-before-function-paren": ["error", "always"],

        // Vue linter exceptions

        // It's not always useful to add a key to a v-for (https://vuejs.org/v2/guide/list.html#key)
        // e.g. if you don't have a unique id available and the DOM content is simple. Otherwise,
        // it's usually best to use a key.
        "vue/require-v-for-key": ["off"],
        "vue/valid-v-for": ["off"],

        "vue/html-indent": ["error", 4],
        "vue/max-attributes-per-line": [2, { "singleline": 2 }],

        // Only require self closing tags for Vue components e.g. <cic-data-table/>,
        // not standard HTML tags
        "vue/html-self-closing": ["error", {
            "html": { "void": "always", "normal": "never" }
        }]
    },
    "plugins": ["jest"]
};
