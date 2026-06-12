import '@mantine/core/styles.css';
import '@mantine/dates/styles.css';
import './App.css';

import { createInertiaApp } from '@inertiajs/react';
import { ModalsProvider } from '@mantine/modals';
import { MantineProvider } from '@mantine/core';
import { createRoot } from 'react-dom/client';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

import { GlobalAlert } from '@/components/GlobalAlert';
import { LoadingModal } from '@/components/LoadingModal';
import { theme } from './theme';

const modals = {
    globalAlert: GlobalAlert,
    loadingModal: LoadingModal,
};

createInertiaApp({
    title: (title) => (title ? `${title} - Gymnamite` : 'Gymnamite'),
    resolve: async (name) => {
        const page = await resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        );

        return (page as { default: never }).default;
    },
    setup({ el, App, props }) {
        createRoot(el).render(
            <MantineProvider theme={theme} defaultColorScheme="dark">
                <ModalsProvider modals={modals}>
                    <App {...props} />
                </ModalsProvider>
            </MantineProvider>,
        );
    },
});
