export interface Client {
  id?: number;
  name: string;
  email: string;
  phone: string;
  document: string;
  gender: string;
  birth_date: string;
  status?: 'active' | 'inactive';
  legal_representative: boolean;
  legal_representative_name?: string | null;
  legal_representative_document?: string | null;
  legal_representative_birth_date?: string | null;
  address_postal_code: string;
  address: string;
  address_number: string;
  address_complement: string;
  address_district: string;
  address_state: string;
  address_city: string;
  created_at?: string;
  updated_at?: string;
}