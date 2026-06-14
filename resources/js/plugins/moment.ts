import moment, { type Moment, type MomentInput } from 'moment';
import 'moment/dist/locale/pt-br';

const instance: typeof moment = moment;
instance.locale('pt-br');

export default instance;