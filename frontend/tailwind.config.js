export default {
    content: [
        "./index.html",
        "./src/**/*.{vue,js,ts,jsx,tsx}",
    ],
    theme: {
        extend: {
            colors: {
                sales: {
                    primary: '#0072BC',
                    secondary: '#00AEEF',
                    tertiary: '#212529',
                    neutral: '#F4F4F4',
                    surface: '#FFFFFF',
                    muted: '#E9EEF3',
                    border: '#D7DEE8',
                    ink: '#4B5563',
                },
            },
        },
    },
    plugins: [],
};