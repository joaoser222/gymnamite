---
name: vuetify-development
description: "Use for this project's Vue UI work with Vuetify: pages, layouts, forms, tables, dialogs, cards, buttons, navigation, icons, spacing, responsive UI, and visual components. This Laravel/Inertia project uses Vuetify, vite-plugin-vuetify auto-imports, Tabler icons, and SCSS theme styles; prefer Vuetify components and utility classes over Tailwind or raw CSS."
license: MIT
metadata:
  author: project
---

# Vuetify Development

## Project Stack

This project uses Laravel + Inertia v3 + Vue 3 with Vuetify for UI.

- Vuetify is configured in `resources/js/plugins/vuetify.ts`.
- Vite enables Vuetify through `vite-plugin-vuetify` in `vite.config.ts`.
- Vuetify components are auto-imported; use `<v-btn>`, `<v-card>`, `<v-form>`, `<v-text-field>`, `<v-data-table>`, `<v-dialog>`, and related components directly.
- Icons use Tabler classes through the configured `tabler` icon set, for example `icon="ti ti-plus"` or `prepend-icon="ti ti-search"`.
- Shared app layout lives in `resources/js/layouts/AuthenticatedLayout.vue`.
- Reusable Vue components live in `resources/js/components`.
- Inertia pages live in `resources/js/pages`.

## When to Apply

Activate this skill when creating or modifying Vue UI in this project, including:

- Inertia page layout and component composition.
- Forms, detail pages, cards, tables, dialogs, actions, navigation, and filters.
- Visual styling, spacing, density, responsive behavior, and icons.

Do not use Tailwind utilities unless the user explicitly asks for Tailwind and the project is changed to include it.

## Conventions

- Prefer Vuetify props, variants, density, layout helpers, and defaults already configured in `resources/js/plugins/vuetify.ts`.
- Follow existing component style from `resources/js/components/TablePage.vue` and auth pages before introducing new patterns.
- Use Vuetify layout helpers such as `d-flex`, `align-center`, `justify-space-between`, `ga-*`, `pa-*`, `mb-*`, and grid components instead of Tailwind classes.
- Prefer Tabler icon names for buttons and list items.
- Keep generic components data-driven and slot-friendly; let feature pages own their specific forms and field definitions.
- For Inertia forms, combine Vuetify inputs with `useForm` or the Inertia `<Form>` component as appropriate for the existing page pattern.
