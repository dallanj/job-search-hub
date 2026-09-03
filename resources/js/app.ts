import { watchInertiaHydration } from '@dallanj/pinia-hydrate';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { nextTick } from 'vue';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';
import { hydratePinia, pinia } from '@/lib/pinia';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
    withApp(app, { page, ssr }) {
        app.use(pinia);

        hydratePinia(page.props, { pinia, resetMissing: true });

        if (!ssr) {
            let stopHydrationWatch: (() => void) | undefined;

            app.onUnmount(() => stopHydrationWatch?.());

            void nextTick(() => {
                stopHydrationWatch = watchInertiaHydration(hydratePinia, {
                    router,
                    pinia,
                    getProps: () => app.config.globalProperties.$page.props,
                });
            });
        }
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
