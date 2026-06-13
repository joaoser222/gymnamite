import { Link, router } from '@inertiajs/react';
import { TextInput, Button, Paper, Title, Center } from '@mantine/core';
import { useState } from 'react';
import { useAlert } from '@/hooks/useAlert';
import { useLoading } from '@/hooks/useLoading';
import { login } from '@/routes';
import { email as passwordEmail } from '@/routes/password';

interface ForgotPasswordProps {
    status?: string;
}

function ForgotPassword({ status }: ForgotPasswordProps) {
    const { showError } = useAlert();
    const { withLoading } = useLoading();
    const [email, setEmail] = useState('');

    async function handleSubmit() {
        try {
            await withLoading(
                new Promise<void>((resolve, reject) => {
                    router.post(
                        passwordEmail.url(),
                        { email },
                        {
                            preserveScroll: true,
                            onSuccess: () => resolve(),
                            onError: (errors) => {
                                const firstError = Object.values(errors)[0];
                                showError(
                                    Array.isArray(firstError)
                                        ? firstError[0]
                                        : String(firstError),
                                );
                                reject(errors);
                            },
                        },
                    );
                }),
                {
                    message: 'Enviando dados',
                    subMessage: 'Solicitando recuperação de senha...',
                },
            );
        } catch {
            // errors already handled in onError
        }
    }

    return (
        <Center h="100vh" bg="dark.9">
            <Paper withBorder shadow="0" p={30} w={420} radius="md" bg="dark.7">
                <Title order={2} fw={600} mb={15} ta="center">
                    Recuperar Senha
                </Title>

                {status && (
                    <Title order={6} c="green" ta="center" mb="sm">
                        {status}
                    </Title>
                )}

                <TextInput
                    label="E-mail"
                    placeholder="seu@email.com"
                    mb="lg"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    onKeyDown={(e) => e.key === 'Enter' && handleSubmit()}
                />

                <Button fullWidth onClick={handleSubmit} my={15}>
                    Enviar link de recuperação
                </Button>

                <Button
                    variant="subtle"
                    fullWidth
                    component={Link}
                    href={login.url()}
                >
                    Voltar para login
                </Button>
            </Paper>
        </Center>
    );
}

ForgotPassword.layout = (page: React.ReactNode) => page;

export default ForgotPassword;
