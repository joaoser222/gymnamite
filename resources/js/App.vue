<template>
  <v-app class="app-transparent-shell">
    <slot />
    <GlobalToast />
  </v-app>
</template>

<script setup lang="ts">
import GlobalToast from '@/components/GlobalToast.vue';
</script>
