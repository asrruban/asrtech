import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';
import AdminLayout from '@/modules/admin/layouts/AdminLayout.vue';
import ClientLayout from '@/modules/client/layouts/ClientLayout.vue';
const appName = 'ASR Tech';
createInertiaApp({
    title: (title) =>
        !title
            ? appName
            : /ASR\s?Tech/i.test(title)
              ? title
              : `${title} - ${appName}`,
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name.startsWith('Admin/Auth/'):
                return null;
            case name.startsWith('Admin/'):
                return AdminLayout;
            case name === 'Dashboard':
            case name === 'settings/Appearance':
            case name.startsWith('Client/'):
                return ClientLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#087f75',
    },
});

// PWA: register the offline shell in production builds only.
if (import.meta.env.PROD && 'serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Offline support is progressive enhancement — never break the app.
        });
    });
}

// This will set light / dark mode on page load...
initializeTheme();
// This will listen for flash toast data from the server...
initializeFlashToast();
