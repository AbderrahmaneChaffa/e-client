import defaultTheme from "tailwindcss/defaultTheme";
import colors from "tailwindcss/colors";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: "class",
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
    ],

    theme: {
        extend: {
            colors: {
                primary: {
                    DEFAULT: "#4F46E5",
                    50: "#EEF2FF",
                    100: "#E0E7FF",
                    500: "#6366F1",
                    600: "#4F46E5",
                    700: "#4338CA",
                    800: "#3730A3",
                    900: "#312E81",
                },
                secondary: colors.slate,
                success: colors.emerald,
                warning: colors.amber,
                danger: colors.red,
                info: colors.sky,
            },
            fontFamily: {
                sans: ["Figtree", ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                soft: "0 10px 30px rgba(15, 23, 42, 0.08)",
            },
        },
    },

    plugins: [forms],
};
