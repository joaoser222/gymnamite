import { h } from 'vue'
import type { IconSet, IconProps } from 'vuetify'

const tabler: IconSet = {
  component: (props: IconProps) =>
    h('i', { class: `ti ti-${props.icon}` }),
}

export { tabler }