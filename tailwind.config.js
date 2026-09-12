/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './wordpress/mu-plugins/hs-manacost-reader/*.php',
    './wordpress/mu-plugins/hs-manacost-reader/*.js',
  ],
  prefix: 'mc-tw-',
  corePlugins: { preflight: false },
  theme: { extend: {} },
  plugins: [],
};
