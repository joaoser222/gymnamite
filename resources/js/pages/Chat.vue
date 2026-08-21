<script setup lang="ts">
import { onMounted, ref } from 'vue';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

type ChatMessage = {
    id: number;
    role: 'user' | 'assistant';
    text: string;
};

type ChatPrompt = {
    name: string;
    label: string;
    description: string;
    text: string;
};

const messages = ref<ChatMessage[]>([]);
const draft = ref('');
const loading = ref(false);
const conversationId = ref<number | null>(null);
const prompts = ref<ChatPrompt[]>([]);

const xsrfToken = decodeURIComponent(
    document.cookie.match(/(^|; )XSRF-TOKEN=([^;]*)/)?.[1] ?? '',
);

async function loadPrompts(): Promise<void> {
    try {
        const response = await fetch('/chat/prompts', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken,
            },
        });

        if (! response.ok) {
            return;
        }

        const data = await response.json();
        prompts.value = data.prompts ?? [];
    } catch {
        prompts.value = [];
    }
}

function applyPrompt(prompt: ChatPrompt): void {
    if (loading.value) {
        return;
    }

    draft.value = prompt.label;
    void send(prompt.name);
}

onMounted(loadPrompts);

function updateMessage(id: number, text: string): void {
    const message = messages.value.find((entry) => entry.id === id);

    if (message) {
        message.text = text;
    }
}

async function send(promptName: string | null = null): Promise<void> {
    const text = draft.value.trim();

    if (text === '' || loading.value) {
        return;
    }

    messages.value.push({ id: Date.now(), role: 'user', text });
    draft.value = '';
    loading.value = true;

    const assistantId = Date.now() + 1;
    messages.value.push({ id: assistantId, role: 'assistant', text: '' });
    let accumulated = '';

    try {
        const response = await fetch('/chat/message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'text/event-stream',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken,
            },
            body: JSON.stringify({
                message: text,
                conversation_id: conversationId.value,
                stream: true,
                prompt: promptName,
            }),
        });

        if (!response.ok || !response.body) {
            throw new Error('Resposta inválida do servidor.');
        }

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';

        while (true) {
            const { value, done } = await reader.read();

            if (done) {
                break;
            }

            buffer += decoder.decode(value, { stream: true });

            let separator: number;
            while ((separator = buffer.indexOf('\n\n')) !== -1) {
                const rawEvent = buffer.slice(0, separator);
                buffer = buffer.slice(separator + 2);

                for (const line of rawEvent.split('\n')) {
                    if (!line.startsWith('data:')) {
                        continue;
                    }

                    const data = line.slice(5).trim();

                    if (data === '' || data === '[DONE]') {
                        continue;
                    }

                    try {
                        const payload = JSON.parse(data);

                        if (payload.type === 'meta') {
                            conversationId.value = payload.conversation_id ?? conversationId.value;
                        } else if (payload.type === 'token') {
                            accumulated += payload.content;
                            updateMessage(assistantId, accumulated);
                        } else if (payload.type === 'done') {
                            accumulated = payload.content ?? accumulated;
                            updateMessage(assistantId, accumulated);
                        }
                    } catch {
                        // Ignore malformed SSE payloads.
                    }
                }
            }
        }

        if (accumulated === '') {
            updateMessage(assistantId, 'Sem resposta.');
        }
    } catch {
        updateMessage(assistantId, 'Erro ao obter resposta do assistente.');
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

        <div v-if="prompts.length > 0" class="mt-4">
            <div class="text-subtitle-2 text-medium-emphasis mb-2">
                Prompts úteis
            </div>
            <div class="d-flex flex-wrap ga-2">
                <v-chip
                    v-for="prompt in prompts"
                    :key="prompt.name"
                    variant="outlined"
                    color="primary"
                    size="small"
                    :disabled="loading"
                    :title="prompt.description"
                    @click="applyPrompt(prompt)"
                >
                    {{ prompt.label }}
                </v-chip>
            </div>
        </div>
    </div>
</template>
