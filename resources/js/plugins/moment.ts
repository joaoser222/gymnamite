import moment, { type Moment, type MomentInput } from 'moment';
import 'moment/dist/locale/pt-br';

/**
 * Instância compartilhada do Moment já configurada para pt-BR.
 *
 * Mantém um único ponto de configuração para componentes que ainda dependem
 * de parsing/formatos baseados em Moment.
 */

const instance: typeof moment = moment;
instance.locale('pt-br');

export default instance;
