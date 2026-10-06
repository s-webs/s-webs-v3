<script setup>
import { PhImage, PhUploadSimple } from '@phosphor-icons/vue';

defineProps({ id: { type: String, required: true }, label: { type: String, required: true }, src: { type: String, default: '' }, fileName: { type: String, default: '' } });
const emit = defineEmits(['select']);
function choose(event) {
  const file = event.target.files?.[0];
  if (file) emit('select', file);
}
</script>

<template>
  <div class="image-upload">
    <div class="image-upload-thumb"><img v-if="src" :src="src" :alt="label"><PhImage v-else :size="22" /></div>
    <div class="image-upload-info"><strong>{{ label }}</strong><small>{{ fileName || (src ? 'Изображение загружено' : 'Файл не выбран') }}</small></div>
    <label class="image-upload-button" :for="id"><PhUploadSimple :size="16" /><span>Выбрать</span></label>
    <input :id="id" class="image-upload-input" type="file" accept="image/*" @change="choose">
  </div>
</template>
