<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

type ChatMessage = {
    id: number;
    role: 'user' | 'assistant';
    text: string;
};

const messages = ref<ChatMessage[]>([]);
const draft = ref('');
const loading = ref(false);
const conversationId = ref<number | null>(null);

const xsrfToken = decodeURIComponent(
    document.cookie.match(/(^|; )XSRF-TOKEN=([^;]*)/)?.[1] ?? '',
);

async function send(): Promise<void> {
    const text = draft.value.trim();

    if (text === '' || loading.value) {
        return;
    }

    messages.value.push({ id: Date.now(), role: 'user', text });
    draft.value = '';
    loading.value = true;

    try {
        const { data } = await axios.post(
            '/chat/message',
            { message: text, conversation_id: conversationId.value },
            { headers: { 'X-XSRF-TOKEN': xsrfToken } },
        );

        conversationId.value = data.conversation_id ?? null;

        messages.value.push({
            id: Date.now() + 1,
            role: 'assistant',
            text: data.reply ?? 'Sem resposta.',
        });
    } catch {
        messages.value.push({
            id: Date.now() + 1,
            role: 'assistant',
            text: 'Erro ao obter resposta do assistente.',
        });
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="mx-auto" style="max-width: 900px">
        <div class="mb-6">
            <h1 class="text-h4">Chat</h1>
        <p class="text-medium-emphasis mt-1">
            Assistente que lê dados e executa ações permitidas à sua conta
            (clientes, vendas, recebimentos, entre outros), conforme as
            ferramentas do servidor MCP.
        </p>
        </div>

        <v-card
            class="d-flex flex-column"
            color="surface"
            elevation="3"
            style="height: 60vh"
        >
            <v-card-text class="flex-grow-1 overflow-y-auto">
                <v-empty-state
                    v-if="messages.length === 0"
                    icon="ti ti-messages"
                    title="Nenhuma conversa ainda"
                    text="As mensagens aparecerão aqui quando o assistente estiver disponível."
                />
                <div
                    v-for="message in messages"
                    :key="message.id"
                    class="mb-3"
                >
                    <div
                        class="pa-3 rounded"
                        :class="
                            message.role === 'user'
                                ? 'bg-primary text-on-primary'
                                : 'bg-grey-lighten-4'
                        "
                    >
                        {{ message.text }}
                    </div>
                </div>
            </v-card-text>

            <v-divider />

            <v-card-actions class="pa-3">
                <v-text-field
                    v-model="draft"
                    label="Escreva uma mensagem"
                    variant="outlined"
                    hide-details
                    density="comfortable"
                    @keyup.enter="send"
                />
                <v-btn
                    color="primary"
                    class="ml-3"
                    :disabled="draft.trim() === '' || loading"
                    :loading="loading"
                    @click="send"
                >
                    Enviar
                </v-btn>
            </v-card-actions>
        </v-card>
    </div>
</template>
