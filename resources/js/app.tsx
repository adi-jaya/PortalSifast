import { createInertiaApp } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import AOS from 'aos';
import '../css/app.css';
import { initializeTheme } from './hooks/use-appearance';
import { initializeColorTheme } from './hooks/use-color-theme';
import './echo.js';
import { configureEcho } from '@laravel/echo-react';

// Font is loaded via Blade template head (Geist — see resources/views/app.blade.php)

configureEcho({
    broadcaster: 'reverb',
});

// Add Echo and Reverb config to global window type
declare global {
  interface Window {
    Echo: any;
    REVERB_CONFIG?: { key: string; host: string; port: number; scheme: string } | null;
  }
}

const appName = import.meta.env.VITE_APP_NAME || 'Portal RS Aisyiyah Siti Fatimah';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <StrictMode>
                <App {...props} />
            </StrictMode>,
        );
    },
    progress: {
        color: '#0A9E8F',
    },
});

// This will set light / dark mode on load...
initializeTheme();
// Initialize color theme customizer...
initializeColorTheme();

// ─── AOS — Animate On Scroll ──────────────────────────────────────────────────
AOS.init({
    duration: 400,
    easing: 'ease-out-cubic',
    once: true,
    offset: 40,
    delay: 0,
    disable: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
});

// Re-run AOS after each Inertia page navigation
router.on('finish', () => {
    AOS.refresh();
});