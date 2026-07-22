<template>
  <v-app class="app-transparent-shell">
    <slot />
    <GlobalDialog />
    <GlobalToast />
  </v-app>
</template>

<script setup lang="ts">
import GlobalDialog from '@/components/GlobalDialog.vue';
import GlobalToast from '@/components/GlobalToast.vue';
</script>
