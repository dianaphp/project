module.exports = {
    root: true,
    env: { browser: true, es2022: true },
    extends: [
        'eslint:recommended',
        'plugin:react/recommended',
        'plugin:react/jsx-runtime',
        'plugin:react-hooks/recommended',
    ],
    parserOptions: {
        ecmaVersion: 'latest',
        sourceType: 'module',
        ecmaFeatures: { jsx: true },
    },
    settings: { react: { version: 'detect' } },
    ignorePatterns: ['node_modules/', 'vendor/', 'dist/', 'tmp/', 'logs/', 'coverage/'],
    overrides: [
        {
            files: ['res/**/*.{js,jsx}'],
            plugins: ['react-refresh'],
            rules: { 'react-refresh/only-export-components': ['warn', { allowConstantExport: true }] },
        },
        {
            files: ['vite.config.js'],
            env: { node: true, browser: false },
        },
    ],
}
