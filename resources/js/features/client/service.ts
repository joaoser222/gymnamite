import {CrudService} from '@/services/CrudService';
import {Client} from './types';

class ClientService extends CrudService<Client> {
  protected resource = 'clients';
}

export default new ClientService();