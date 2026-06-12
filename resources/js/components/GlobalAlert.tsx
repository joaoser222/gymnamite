// components/GlobalAlert.tsx
import { ContextModalProps } from '@mantine/modals';
import { Text, Button, Group, Stack, Box } from '@mantine/core';
import { 
  IconAlertCircle, 
  IconCheck, 
  IconInfoCircle, 
  IconAlertTriangle 
} from '@tabler/icons-react';

interface GlobalAlertProps {
  message: string;
  type?: 'info' | 'success' | 'error' | 'warning';
  confirmLabel?: string;
  cancelLabel?: string;
  onConfirm?: () => void;
  onCancel?: () => void;
  showCancel?: boolean;
}

export function GlobalAlert({ context, id, innerProps }: ContextModalProps<GlobalAlertProps>) {
  const {
    message,
    type = 'info',
    confirmLabel = 'OK',
    cancelLabel = 'Cancelar',
    onConfirm,
    onCancel,
    showCancel = false,
  } = innerProps;

  const config = {
    info: { icon: IconInfoCircle, color: 'blue' },
    success: { icon: IconCheck, color: 'green' },
    error: { icon: IconAlertCircle, color: 'red' },
    warning: { icon: IconAlertTriangle, color: 'orange' },
  };

  const Icon = config[type].icon;
  const color = config[type].color;

  const handleConfirm = () => {
    if (onConfirm) onConfirm();
    context.closeModal(id);
  };

  const handleCancel = () => {
    if (onCancel) onCancel();
    context.closeModal(id);
  };

  return (
    <Stack>
      <Group wrap="nowrap" gap="md">
        <Icon size={32} color={color} stroke={1.5} />
        <Text size="md" style={{ flex: 1 }}>
          {message}
        </Text>
      </Group>
      
      <Group justify="flex-end" mt="md">
        {showCancel && (
          <Button variant="default" onClick={handleCancel}>
            {cancelLabel}
          </Button>
        )}
        <Button color={color} onClick={handleConfirm}>
          {confirmLabel}
        </Button>
      </Group>
    </Stack>
  );
}