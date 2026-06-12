import { Container } from '@mantine/core';
import { authenticatedLayout } from '@/layouts/authenticatedLayout';

function Dashboard() {
    return (
        <Container>Painel principal</Container>
    );
}

Dashboard.layout = authenticatedLayout;

export default Dashboard;
