// components/FormModal.tsx
import { Modal, Button, Group, Stack } from '@mantine/core';

interface FormModalWrapperProps {
  opened: boolean;
  onClose: () => void;
  title: string;
  onSubmit: () => void;
  loading?: boolean;
  children: React.ReactNode;
}

export function FormModal({ opened, onClose, title, onSubmit, loading, children }: FormModalWrapperProps) {
  return (
    <Modal opened={opened} onClose={onClose} title={title} size="lg">
      <form onSubmit={onSubmit}>
        <Stack>
          {children}
          <Group justify="right">
            <Button variant="default" onClick={onClose}>Cancelar</Button>
            <Button type="submit" loading={loading}>Salvar</Button>
          </Group>
        </Stack>
      </form>
    </Modal>
  );
}