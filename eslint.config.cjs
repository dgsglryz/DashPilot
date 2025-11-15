const pluginVue = require('eslint-plugin-vue');
const { defineConfigWithVueTs, vueTsConfigs } = require('@vue/eslint-config-typescript');

module.exports = defineConfigWithVueTs(
    {
        ignores: [
            '**/node_modules/**',
            'vendor/**',
            'public/build/**',
            'storage/**',
            'bootstrap/cache/**',
        ],
    },
    pluginVue.configs['flat/recommended'],
    vueTsConfigs.recommended,
    {
        files: ['resources/js/**/*.{ts,vue}'],
        rules: {
            'vue/multi-word-component-names': 'off',
            'vue/html-indent': 'off',
            'vue/max-attributes-per-line': 'off',
            'vue/singleline-html-element-content-newline': 'off',
            'vue/html-self-closing': 'off',
            'vue/attributes-order': 'off',
            'vue/html-closing-bracket-newline': 'off',
            'vue/multiline-html-element-content-newline': 'off',
            '@typescript-eslint/ban-ts-comment': 'off',
        },
    },
);

