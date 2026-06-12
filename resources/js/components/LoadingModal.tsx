// components/LoadingModal.tsx
import { ContextModalProps } from '@mantine/modals';
import { Stack, Text, Loader, Progress, Center } from '@mantine/core';
import { IconCheck,IconX } from '@tabler/icons-react';

interface LoadingModalProps {
  message?: string;
  subMessage?: string;
  progress?: number;
  showProgress?: boolean;
  status?: 'loading' | 'success' | 'error';
}

export function LoadingModal({ context, id, innerProps }: ContextModalProps<LoadingModalProps>) {
  const {
    message = 'Carregando...',
    subMessage,
    progress,
    showProgress = false,
    status = 'loading',
  } = innerProps;

  const getStatusDisplay = () => {
    switch (status) {
      case 'success':
        return (
          <Center>
            <IconCheck size={48} color="green" stroke={1.5} />
          </Center>
        );
      case 'error':
        return (
          <Center>
            <IconX size={48} color="red" stroke={1.5} />
          </Center>
        );
      default:
        return <Loader size="lg" variant="dots" />;
    }
  };

  return (
    <Stack align="center" gap="md" py="xl">
      {getStatusDisplay()}
      
      <Text fw={700} size="lg" ta="center">
        {message}
      </Text>
      
      {subMessage && (
        <Text size="sm" c="dimmed" ta="center">
          {subMessage}
        </Text>
      )}
      
      {showProgress && progress !== undefined && (
        <Progress 
          value={progress} 
          size="lg" 
          w="100%" 
          radius="xl"
          animated={status === 'loading'}
        />
      )}
    </Stack>
  );
}