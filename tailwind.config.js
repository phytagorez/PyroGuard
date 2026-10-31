/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './node_modules/preline/dist/*.js',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                // §5.1 Netral
                paper: 'var(--paper)',
                'paper-2': 'var(--paper-2)',
                'paper-3': 'var(--paper-3)',
                line: 'var(--line)',
                ink: 'var(--ink)',
                'ink-muted': 'var(--ink-muted)',
                forest: 'var(--forest)',
                moss: 'var(--moss)',

                // §5.1 Status Risiko
                'safe-fill': 'var(--safe-fill)',
                safe: 'var(--safe)',
                'warn-fill': 'var(--warn-fill)',
                warn: 'var(--warn)',
                'warn-tag': 'var(--warn-tag)',
                'danger-fill': 'var(--danger-fill)',
                danger: 'var(--danger)',
                siaga: 'var(--siaga)',
                ember: 'var(--ember)',

                // §5.1 Fungsional Non-Risiko
                water: 'var(--water)',
                evac: 'var(--evac)',
                hotspot: 'var(--hotspot)',
                report: 'var(--report)',
                focus: 'var(--focus)',
            },
            fontFamily: {
                display: ['"Space Grotesk"', 'system-ui', 'sans-serif'],
                sans: ['Inter', 'system-ui', 'sans-serif'],
                mono: ['"JetBrains Mono"', 'ui-monospace', 'monospace'],
            },
            boxShadow: {
                sheet: '0 -12px 40px rgba(20, 32, 26, 0.14)',
                card: '0 2px 0 rgba(20, 32, 26, 0.04), 0 8px 24px rgba(20, 32, 26, 0.06)',
            },
            transitionDuration: {
                fast: '120ms',
                base: '220ms',
                slow: '420ms',
            },
        },
    },
    plugins: [],
};
