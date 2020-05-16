module.exports = {
  purge: {
    enabled: true,
    content: [
        './src/templates/**/*.twig',
        './src/assetbundles/ohdear/src/js/**/*.vue'
    ],
  },
  prefix: 'oh-',
  theme: {
    extend: {
      padding: {
        '0.5': '0.125rem',
        '2.5': '0.625rem',
      },
      margin: {
        '0.5': '0.125rem',
        '1.5': '0.375rem',
      },
      boxShadow: {
        craft: '0 0 0 1px rgba(205, 216, 228, 0.25), 0 2px 12px rgba(205, 216, 228, 0.5)'
      }
    },
  },
  variants: {},
  plugins: [],
}
