<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import logo from '@/assets/logo.webp';

defineOptions({ layout: null });

defineProps<{
    canResetPassword: boolean;
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
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
                    <!-- Logo -->
                    <div class="d-flex justify-center mb-6">
                        <v-img :src="logo" height="48" />
                    </div>

                    <!-- Título -->
                    <h1 class="text-h6 font-weight-medium text-center mb-1">
                        Bem-vindo de volta
                    </h1>
                    <p
                        class="text-body-2 text-medium-emphasis text-center mb-6"
                    >
                        Entre com sua conta para continuar
                    </p>

                    <v-alert
                        v-if="status"
                        type="success"
                        variant="tonal"
                        class="mb-4"
                    >
                        {{ status }}
                    </v-alert>

                    <!-- Form -->
                    <v-form @submit.prevent="submit">
                        <v-text-field
                            v-model="form.email"
                            label="E-mail"
                            type="email"
                            autocomplete="email"
                            :error-messages="form.errors.email"
                            class="mb-3"
                        />
                        <password-field
                            v-model="form.password"
                            label="Senha"
                            autocomplete="current-password"
                            :error-messages="form.errors.password"
                            class="mb-2"
                        />

                        <!-- Lembrar / Esqueci -->
                        <div
                            class="d-flex align-center justify-space-between mb-4"
                        >
                            <v-checkbox
                                v-model="form.remember"
                                label="Lembrar de mim"
                                density="compact"
                                hide-details
                                color="primary"
                            />
                            <v-btn
                                v-if="canResetPassword"
                                variant="text"
                                size="small"
                                color="primary"
                                class="text-caption"
                                @click="router.visit('/forgot-password')"
                            >
                                Esqueci a senha
                            </v-btn>
                        </div>

                        <v-btn
                            type="submit"
                            color="primary"
                            size="large"
                            block
                            :loading="form.processing"
                            :disabled="form.processing"
                        >
                            Entrar
                        </v-btn>
                    </v-form>
                </v-card-text>
            </v-card>
        </v-container>
    </v-main>
</template>
