const defaultTheme = require('tailwindcss/defaultTheme');

module.exports = {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './resources/js/**/*.vue',
    ],
    
    theme: {
        extend: {
            colors: {
                // Palette verte éco-énergétique principale avec #4acf7b
                'eco-green': {
                    50: '#f0fdf4',   // Vert très clair - fonds subtils
                    100: '#dcfce7',  // Vert clair - accents légers
                    200: '#bbf7d0',  // Vert doux - bordures
                    300: '#86efac',  // Vert moyen - éléments interactifs
                    400: '#4ade80',  // Vert vif - boutons secondaires
                    500: '#4acf7b',  // Vert principal EVON - couleur de marque
                    600: '#3bb86b',  // Vert foncé - boutons primaires
                    700: '#2d8f56',  // Vert profond - texte important
                    800: '#1f6b42',  // Vert très foncé - titres
                    900: '#14532d',  // Vert sombre - texte principal
                },
                // Palette verte énergétique basée sur #4acf7b
                'energy-green': {
                    50: '#f0fdf4',
                    100: '#dcfce7',
                    200: '#bbf7d0',
                    300: '#86efac',
                    400: '#4ade80',
                    500: '#4acf7b',  // Vert énergétique principal EVON
                    600: '#3bb86b',
                    700: '#2d8f56',
                    800: '#1f6b42',
                    900: '#14532d',
                },
                // Palette verte naturelle (plus organique)
                'nature-green': {
                    50: '#f7fee7',
                    100: '#ecfccb',
                    200: '#d9f99d',
                    300: '#bef264',
                    400: '#a3e635',
                    500: '#84cc16',  // Vert nature principal
                    600: '#65a30d',
                    700: '#4d7c0f',
                    800: '#3f6212',
                    900: '#365314',
                },
                // Couleurs d'accent éco-énergétiques basées sur #4acf7b
                'eco-accent': {
                    'mint': '#6ee7b7',
                    'sage': '#a7f3d0',
                    'forest': '#2d8f56',
                    'lime': '#84cc16',
                    'emerald': '#4acf7b',
                    'primary': '#4acf7b',  // Couleur principale EVON
                },
                // Couleurs neutres avec teinte verte
                'eco-gray': {
                    50: '#f9fafb',
                    100: '#f3f4f6',
                    200: '#e5e7eb',
                    300: '#d1d5db',
                    400: '#9ca3af',
                    500: '#6b7280',
                    600: '#4b5563',
                    700: '#374151',
                    800: '#1f2937',
                    900: '#111827',
                },
            },
            fontFamily: {
                sans: ['Inter var', 'Inter', ...defaultTheme.fontFamily.sans],
                'eco': ['Inter var', 'Inter', 'system-ui', 'sans-serif'],
            },
            boxShadow: {
                'eco': '0 4px 6px -1px rgba(74, 207, 123, 0.1), 0 2px 4px -1px rgba(74, 207, 123, 0.06)',
                'eco-lg': '0 10px 15px -3px rgba(74, 207, 123, 0.1), 0 4px 6px -2px rgba(74, 207, 123, 0.05)',
                'eco-xl': '0 20px 25px -5px rgba(74, 207, 123, 0.1), 0 10px 10px -5px rgba(74, 207, 123, 0.04)',
                'energy': '0 4px 6px -1px rgba(74, 207, 123, 0.1), 0 2px 4px -1px rgba(74, 207, 123, 0.06)',
                'energy-lg': '0 10px 15px -3px rgba(74, 207, 123, 0.1), 0 4px 6px -2px rgba(74, 207, 123, 0.05)',
                'nature': '0 4px 6px -1px rgba(132, 204, 22, 0.1), 0 2px 4px -1px rgba(132, 204, 22, 0.06)',
                'glow-green': '0 0 20px rgba(74, 207, 123, 0.3)',
                'glow-energy': '0 0 20px rgba(74, 207, 123, 0.3)',
                'glow-evon': '0 0 25px rgba(74, 207, 123, 0.4)',
            },
            animation: {
                // Animations éco-énergétiques
                'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                'pulse-energy': 'pulse-energy 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                'bounce-energy': 'bounce-energy 1s infinite',
                'glow-green': 'glow-green 2s ease-in-out infinite alternate',
                'glow-energy': 'glow-energy 2s ease-in-out infinite alternate',
                'float': 'float 3s ease-in-out infinite',
                'float-delayed': 'float 3s ease-in-out infinite 1.5s',
                'slide-up': 'slide-up 0.5s ease-out',
                'slide-down': 'slide-down 0.5s ease-out',
                'fade-in': 'fade-in 0.6s ease-out',
                'fade-in-delayed': 'fade-in 0.6s ease-out 0.2s both',
                'scale-in': 'scale-in 0.4s ease-out',
                'rotate-slow': 'rotate-slow 8s linear infinite',
                'wiggle': 'wiggle 1s ease-in-out infinite',
                'shimmer': 'shimmer 2s linear infinite',
                'wave': 'wave 2s ease-in-out infinite',
                'leaf': 'leaf 4s ease-in-out infinite',
                'energy-flow': 'energy-flow 3s ease-in-out infinite',
            },
            keyframes: {
                'pulse-energy': {
                    '0%, 100%': { 
                        opacity: '1',
                        transform: 'scale(1)',
                        boxShadow: '0 0 0 0 rgba(74, 207, 123, 0.7)'
                    },
                    '50%': { 
                        opacity: '0.8',
                        transform: 'scale(1.05)',
                        boxShadow: '0 0 0 10px rgba(74, 207, 123, 0)'
                    },
                },
                'bounce-energy': {
                    '0%, 100%': {
                        transform: 'translateY(-25%)',
                        animationTimingFunction: 'cubic-bezier(0.8, 0, 1, 1)',
                    },
                    '50%': {
                        transform: 'translateY(0)',
                        animationTimingFunction: 'cubic-bezier(0, 0, 0.2, 1)',
                    },
                },
                'glow-green': {
                    '0%': { 
                        boxShadow: '0 0 5px rgba(74, 207, 123, 0.5), 0 0 10px rgba(74, 207, 123, 0.3), 0 0 15px rgba(74, 207, 123, 0.1)' 
                    },
                    '100%': { 
                        boxShadow: '0 0 10px rgba(74, 207, 123, 0.8), 0 0 20px rgba(74, 207, 123, 0.5), 0 0 30px rgba(74, 207, 123, 0.3)' 
                    },
                },
                'glow-energy': {
                    '0%': { 
                        boxShadow: '0 0 5px rgba(74, 207, 123, 0.5), 0 0 10px rgba(74, 207, 123, 0.3), 0 0 15px rgba(74, 207, 123, 0.1)' 
                    },
                    '100%': { 
                        boxShadow: '0 0 10px rgba(74, 207, 123, 0.8), 0 0 20px rgba(74, 207, 123, 0.5), 0 0 30px rgba(74, 207, 123, 0.3)' 
                    },
                },
                'float': {
                    '0%, 100%': { transform: 'translateY(0px)' },
                    '50%': { transform: 'translateY(-10px)' },
                },
                'slide-up': {
                    '0%': { transform: 'translateY(100%)', opacity: '0' },
                    '100%': { transform: 'translateY(0)', opacity: '1' },
                },
                'slide-down': {
                    '0%': { transform: 'translateY(-100%)', opacity: '0' },
                    '100%': { transform: 'translateY(0)', opacity: '1' },
                },
                'fade-in': {
                    '0%': { opacity: '0', transform: 'translateY(20px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'scale-in': {
                    '0%': { opacity: '0', transform: 'scale(0.9)' },
                    '100%': { opacity: '1', transform: 'scale(1)' },
                },
                'rotate-slow': {
                    '0%': { transform: 'rotate(0deg)' },
                    '100%': { transform: 'rotate(360deg)' },
                },
                'wiggle': {
                    '0%, 100%': { transform: 'rotate(-3deg)' },
                    '50%': { transform: 'rotate(3deg)' },
                },
                'shimmer': {
                    '0%': { backgroundPosition: '-200% 0' },
                    '100%': { backgroundPosition: '200% 0' },
                },
                'wave': {
                    '0%, 100%': { transform: 'rotate(0deg)' },
                    '25%': { transform: 'rotate(5deg)' },
                    '75%': { transform: 'rotate(-5deg)' },
                },
                'leaf': {
                    '0%, 100%': { transform: 'rotate(0deg) translateY(0px)' },
                    '25%': { transform: 'rotate(2deg) translateY(-5px)' },
                    '50%': { transform: 'rotate(0deg) translateY(-10px)' },
                    '75%': { transform: 'rotate(-2deg) translateY(-5px)' },
                },
                'energy-flow': {
                    '0%': { 
                        backgroundPosition: '0% 50%',
                        opacity: '0.7'
                    },
                    '50%': { 
                        backgroundPosition: '100% 50%',
                        opacity: '1'
                    },
                    '100%': { 
                        backgroundPosition: '0% 50%',
                        opacity: '0.7'
                    },
                },
            },
            backgroundImage: {
                'gradient-eco': 'linear-gradient(135deg, #f0fdf4 0%, #dcfce7 50%, #bbf7d0 100%)',
                'gradient-energy': 'linear-gradient(135deg, #f0fdf4 0%, #dcfce7 50%, #bbf7d0 100%)',
                'gradient-nature': 'linear-gradient(135deg, #f7fee7 0%, #ecfccb 50%, #d9f99d 100%)',
                'gradient-radial-eco': 'radial-gradient(circle, #f0fdf4 0%, #dcfce7 50%, #bbf7d0 100%)',
                'gradient-evon': 'linear-gradient(135deg, #f0fdf4 0%, #dcfce7 30%, rgba(74, 207, 123, 0.1) 70%, #bbf7d0 100%)',
                'shimmer': 'linear-gradient(90deg, transparent, rgba(74, 207, 123, 0.4), transparent)',
                'energy-flow': 'linear-gradient(45deg, rgba(74, 207, 123, 0.1), rgba(74, 207, 123, 0.2), rgba(74, 207, 123, 0.1))',
            },
            gridTemplateColumns: {
                'auto-fit': 'repeat(auto-fit, minmax(250px, 1fr))',
                'eco-grid': 'repeat(auto-fit, minmax(280px, 1fr))',
            },
            spacing: {
                '18': '4.5rem',
                '88': '22rem',
                '128': '32rem',
            },
            borderRadius: {
                'eco': '1rem',
                'eco-lg': '1.5rem',
                'eco-xl': '2rem',
            },
        },
    },
    variants: {
        extend: {
            backgroundColor: ['dark'],
            textColor: ['dark'],
            borderColor: ['dark'],
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
        require('@tailwindcss/aspect-ratio'),
        require('tailwind-scrollbar'),
    ],
};