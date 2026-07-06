<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';

defineOptions({ layout: null });

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post('/forgot-password');
};
</script>

<template>
    <v-main
        theme="dark"
        class="d-flex align-center justify-center"
        style="min-height: 100vh"
    >
        <v-container
            fluid
            class="fill-height d-flex align-center justify-center pa-4"
        >
            <v-card
                width="420"
                color="secondary"
            >
                <v-card-text class="pa-8">
                    <h1 class="text-h6 font-weight-medium text-center mb-1">
                        Recuperar senha
                    </h1>
                    <p
                        class="text-body-2 text-medium-emphasis text-center mb-6"
                    >
                        Informe seu e-mail para receber o link de redefinição.
                    </p>

                    <v-alert
                        v-if="status"
                        type="success"
                        variant="tonal"
                        class="mb-4"
                    >
                        {{ status }}
                    </v-alert>

                    <v-form @submit.prevent="submit">
                        <v-text-field
                            v-model="form.email"
                            label="E-mail"
                            type="email"
                            autocomplete="email"
                            :error-messages="form.errors.email"
                            class="mb-4"
                        />
                        <v-btn
                            type="submit"
                            color="primary"
                            size="large"
                            block
                            :loading="form.processing"
                            :disabled="form.processing"
                        >
                            Enviar link
                        </v-btn>
                    </v-form>

                    <v-btn
                        color="primary"
                        block
                        class="mt-4"
                        @click="router.visit('/login')"
                    >
                        Voltar para login
                    </v-btn>
                </v-card-text>
            </v-card>
        </v-container>
    </v-main>
</template>
