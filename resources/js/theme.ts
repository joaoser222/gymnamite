import { createTheme } from '@mantine/core';

export const theme = createTheme({
    fontFamily: 'Inter, sans-serif',
    headings: {
        fontFamily: 'Inter, sans-serif',
    },
    components: {
        NavLink: {
            styles: {
                root: {
                    borderRadius: '8px',
                },
            },
        },
    },
});
