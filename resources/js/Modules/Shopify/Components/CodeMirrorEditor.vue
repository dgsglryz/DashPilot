<template>
  <textarea
    class="h-full w-full resize-none bg-gray-900 text-gray-100 font-mono text-sm p-4 focus:outline-none"
    :value="modelValue"
    @input="handleInput"
  ></textarea>
</template>

<script setup lang="ts">
// @ts-nocheck
defineProps<{
  modelValue: string
}>()

const emit = defineEmits(['update:modelValue', 'change'])

const handleInput = (event) => {
  emit('update:modelValue', event.target.value)
  emit('change', event.target.value)
}
</script>
