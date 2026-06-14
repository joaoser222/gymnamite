<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

defineOptions({ layout: null });

const props = defineProps<{
    email: string;
    token: string;
}>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/reset-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <v-app theme="dark">
        <v-main style="background: #0f0f13">
            <v-container
                fluid
                class="fill-height d-flex align-center justify-center"
            >
                <v-card
                    width="420"
                    style="
                        background: #1c1c23;
                        border: 0.5px solid rgba(255, 255, 255, 0.08);
                    "
                >
                    <v-card-text class="pa-8">
                        <h1 class="text-h6 font-weight-medium text-center mb-1">
                            Nova senha
                        </h1>
                        <p
                            class="text-body-2 text-medium-emphasis text-center mb-6"
                        >
                            Escolha uma nova senha para acessar sua conta.
                        </p>

                        <v-form @submit.prevent="submit">
                            <v-text-field
                                v-model="form.email"
                                label="E-mail"
                                type="email"
                                autocomplete="email"
                                :error-messages="form.errors.email"
                                class="mb-3"
                            />

                            <v-text-field
                                v-model="form.password"
                                label="Senha"
                                type="password"
                                autocomplete="new-password"
                                :error-messages="form.errors.password"
                                class="mb-3"
                            />

                            <v-text-field
                                v-model="form.password_confirmation"
                                label="Confirmar senha"
                                type="password"
                                autocomplete="new-password"
                                :error-messages="
                                    form.errors.password_confirmation
                                "
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
                                Redefinir senha
                            </v-btn>
                        </v-form>
                    </v-card-text>
                </v-card>
            </v-container>
        </v-main>
    </v-app>
</template>
