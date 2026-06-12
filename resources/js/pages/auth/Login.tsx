import { TextInput, PasswordInput, Button, Paper, Title, Center } from '@mantine/core';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import { useAlert } from '@/hooks/useAlert';
import { useLoading } from '@/hooks/useLoading';
import { store as loginStore } from '@/routes/login';

interface LoginProps {
    canResetPassword: boolean;
    status?: string;
}

function Login({ canResetPassword, status }: LoginProps) {
    const { showError } = useAlert();
    const { withLoading } = useLoading();
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');

    async function handleSubmit() {
        try {
            await withLoading(
                new Promise<void>((resolve, reject) => {
                    router.post(loginStore.url(), { email, password }, {
                        onSuccess: () => resolve(),
                        onError: (errors) => {
                            const firstError = Object.values(errors)[0];
                            showError(Array.isArray(firstError) ? firstError[0] : String(firstError));
                            reject(errors);
                        },
                    });
                }),
                {
                    message: 'Enviando dados',
                    subMessage: 'Autenticando usuário...',
                },
            );
        } catch {
            // errors already handled in onError
        }
    }

    return (
        <Center h="100vh" bg="dark.9">
            <Paper withBorder shadow="0" p={30} w={420} radius="md" bg="dark.7">
                <Title order={2} fw={600} mb={15} ta="center">Entrar na Conta</Title>

                {status && (
                    <Title order={6} c="green" ta="center" mb="sm">{status}</Title>
                )}

                <TextInput
                    label="E-mail"
                    placeholder="seu@email.com"
                    mb="sm"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                />
                <PasswordInput
                    label="Senha"
                    placeholder="••••••••"
                    mb="lg"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    onKeyDown={(e) => e.key === 'Enter' && handleSubmit()}
                />

                <Button fullWidth onClick={handleSubmit} my={15}>Entrar</Button>

                {canResetPassword && (
                    <Button variant="subtle" fullWidth component="a" href="/forgot-password">
                        Esqueceu a senha?
                    </Button>
                )}
            </Paper>
        </Center>
    );
}

Login.layout = (page: React.ReactNode) => page;

export default Login;
