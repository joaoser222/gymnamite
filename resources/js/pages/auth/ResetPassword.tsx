import { Link, router } from '@inertiajs/react';
import {
    TextInput,
    PasswordInput,
    Button,
    Paper,
    Title,
    Center,
} from '@mantine/core';
import { useState } from 'react';
import { useAlert } from '@/hooks/useAlert';
import { useLoading } from '@/hooks/useLoading';
import { login } from '@/routes';
import { update as passwordUpdate } from '@/routes/password';

interface ResetPasswordProps {
    email?: string;
    token: string;
}

function ResetPassword({
    email: initialEmail = '',
    token,
}: ResetPasswordProps) {
    const { showError } = useAlert();
    const { withLoading } = useLoading();
    const [email, setEmail] = useState(initialEmail);
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');

    async function handleSubmit() {
        try {
            await withLoading(
                new Promise<void>((resolve, reject) => {
                    router.post(
                        passwordUpdate.url(),
                        {
                            token,
                            email,
                            password,
                            password_confirmation: passwordConfirmation,
                        },
                        {
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
                    subMessage: 'Atualizando senha...',
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
                    Nova Senha
                </Title>

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
                    mb="sm"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                />
                <PasswordInput
                    label="Confirmar senha"
                    placeholder="••••••••"
                    mb="lg"
                    value={passwordConfirmation}
                    onChange={(e) => setPasswordConfirmation(e.target.value)}
                    onKeyDown={(e) => e.key === 'Enter' && handleSubmit()}
                />

                <Button fullWidth onClick={handleSubmit} my={15}>
                    Atualizar senha
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

ResetPassword.layout = (page: React.ReactNode) => page;

export default ResetPassword;
