<?php

namespace Database\Seeders;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductUnity;

class ProductUnitySeeder extends Seeder
{
    use WithoutModelEvents;
    
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProductUnity::upsert([
            ['code' => 'AMPOLA', 'name' => 'Ampola'],
            ['code' => 'BALDE', 'name' => 'Balde'],
            ['code' => 'BANDEJ', 'name' => 'Bandeja'],
            ['code' => 'BARRA', 'name' => 'Barra'],
            ['code' => 'BISNAG', 'name' => 'Bisnaga'],
            ['code' => 'BLOCO', 'name' => 'Bloco'],
            ['code' => 'BOBINA', 'name' => 'Bobina'],
            ['code' => 'BOMB', 'name' => 'Bombona'],
            ['code' => 'CAPS', 'name' => 'Cápsula'],
            ['code' => 'CART', 'name' => 'Cartela'],
            ['code' => 'CENTO', 'name' => 'Cento'],
            ['code' => 'CJ', 'name' => 'Conjunto'],
            ['code' => 'CM', 'name' => 'Centímetro'],
            ['code' => 'CX', 'name' => 'Caixa'],
            ['code' => 'DISP', 'name' => 'Display'],
            ['code' => 'DUZIA', 'name' => 'Duzia'],
            ['code' => 'EMBAL', 'name' => 'Embalagem'],
            ['code' => 'FARDO', 'name' => 'Fardo'],
            ['code' => 'FOLHA', 'name' => 'Folha'],
            ['code' => 'FRASCO', 'name' => 'Frasco'],
            ['code' => 'GALAO', 'name' => 'Galão'],
            ['code' => 'GF', 'name' => 'Garrafa'],
            ['code' => 'GRAMAS', 'name' => 'Gramas'],
            ['code' => 'JOGO', 'name' => 'Jogo'],
            ['code' => 'KG', 'name' => 'Quilograma'],
            ['code' => 'KIT', 'name' => 'Kit'],
            ['code' => 'LATA', 'name' => 'Lata'],
            ['code' => 'LITRO', 'name' => 'Litro'],
            ['code' => 'M', 'name' => 'Metro'],
            ['code' => 'M2', 'name' => 'Metro Quadrado'],
            ['code' => 'M3', 'name' => 'Metro Cúbico'],
            ['code' => 'MILHEI', 'name' => 'Milheiro'],
            ['code' => 'ML', 'name' => 'Mililitro'],
            ['code' => 'MWH', 'name' => 'Megawatt Hora'],
            ['code' => 'PACOTE', 'name' => 'Pacote'],
            ['code' => 'PALETE', 'name' => 'Palete'],
            ['code' => 'PARES', 'name' => 'Pares'],
            ['code' => 'PC', 'name' => 'Peça'],
            ['code' => 'POTE', 'name' => 'Pote'],
            ['code' => 'K', 'name' => 'Quilate'],
            ['code' => 'RESMA', 'name' => 'Resma'],
            ['code' => 'ROLO', 'name' => 'Rolo'],
            ['code' => 'SACO', 'name' => 'Saco'],
            ['code' => 'SACOLA', 'name' => 'Sacola'],
            ['code' => 'TAMBOR', 'name' => 'Tambor'],
            ['code' => 'TANQUE', 'name' => 'Tanque'],
            ['code' => 'TON', 'name' => 'Tonelada'],
            ['code' => 'TUBO', 'name' => 'Tubo'],
            ['code' => 'UN', 'name' => 'Unidade'],
            ['code' => 'VASIL', 'name' => 'Vasilhame'],
            ['code' => 'VIDRO', 'name' => 'Vidro']
        ],['code'],['name']);
    }
}
