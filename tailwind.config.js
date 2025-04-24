/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
      './**/*.php',  // Tous les fichiers PHP
      './assets/js/**/*.js',  // Tous les fichiers JS
    ],
    theme: {
      extend: {
        colors: {
          'primary': '#000000',  // Fond global
          'secondary': '#111111',  // Sections alternées
          'accent': {
            DEFAULT: '#0ed0ff',  // Accent & icônes
            'dark': '#00b5e2',   // Hover
          },
        },
        fontFamily: {
          'sans': ['Inter', 'sans-serif'],
          'display': ['Montserrat', 'sans-serif'],
        },
        fontSize: {
          'title': ['42px', '1.2'],
          'subtitle': ['24px', '1.4'],
          'body': ['18px', '1.6'],
        },
        spacing: {
          'header': '80px',
        },
        animation: {
          'fade-in': 'fadeIn 1.2s ease-out forwards',
          'pulse': 'pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
        },
        keyframes: {
          fadeIn: {
            '0%': { opacity: '0', transform: 'translateY(20px)' },
            '100%': { opacity: '1', transform: 'translateY(0)' },
          },
          pulse: {
            '0%, 100%': { opacity: '1' },
            '50%': { opacity: '.5' },
          },
        },
        boxShadow: {
          'accent': '0 0 15px rgba(14, 208, 255, 0.5)',
        },
        borderRadius: {
          'xl': '12px',
        },
        height: {
          'hero': 'calc(100vh - 80px)',
        },
        backgroundImage: {
          'hero-gradient': 'radial-gradient(circle at 70% 50%, rgba(14, 208, 255, 0.15) 0%, rgba(0, 0, 0, 0) 70%)',
        },
      },
    },
    plugins: [
      // Plugin pour gérer les forms
      require('@tailwindcss/forms')({
        strategy: 'class',
      }),
    ],
  }