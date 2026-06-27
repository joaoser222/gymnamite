import { h } from 'vue'
import type { IconSet, IconProps } from 'vuetify'

/**
 * Adaptador do iconset Tabler para o formato esperado pelo Vuetify.
 *
 * Permite usar nomes curtos de ícone na aplicação mantendo renderização via
 * classes CSS do pacote webfont.
 */

const tabler: IconSet = {
  component: (props: IconProps) =>
    h('i', { class: `ti ti-${props.icon}` }),
}

export { tabler }
