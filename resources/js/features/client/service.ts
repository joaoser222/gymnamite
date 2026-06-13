import { CrudService } from '@/services/CrudService';
import type { Client } from './types';

class ClientService extends CrudService<Client> {
    protected resource = 'clients';
}

export default new ClientService();
