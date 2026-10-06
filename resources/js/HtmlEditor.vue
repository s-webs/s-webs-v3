<script setup>
import { ref, watch } from 'vue';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import {
  PhArrowCounterClockwise, PhArrowClockwise, PhBracketsAngle, PhCode,
  PhImage, PhLink, PhListBullets, PhListNumbers, PhQuotes, PhUploadSimple,
  PhTextB, PhTextItalic, PhTextStrikethrough, PhTextUnderline,
} from '@phosphor-icons/vue';

const props = defineProps({ modelValue: { type: String, default: '' }, id: { type: String, required: true } });
const emit = defineEmits(['update:modelValue']);
const source = ref(props.modelValue || '');
const isComplex = value => /<(div|table|iframe|section|figure|video|audio|svg|script|style)\b|\s(class|style|width|height|data-[\w-]+)=/i.test(value);
const complexMarkup = ref(isComplex(source.value));
const mode = ref(complexMarkup ? 'source' : 'visual');
const linkOpen = ref(false);
const imageOpen = ref(false);
const linkUrl = ref('');
const imageUrl = ref('');
const imageInput = ref(null);
const imageUploading = ref(false);
const imageError = ref('');
const heading = ref('paragraph');
function updateHeading(editor) {
  heading.value = [2, 3, 4].find(level => editor.isActive('heading', { level }))?.toString() || 'paragraph';
}
const editor = useEditor({
  content: source.value,
  extensions: [
    StarterKit.configure({ link: { openOnClick: false } }),
    Image.configure({ allowBase64: false }),
  ],
  editorProps: { attributes: { class: 'html-editor-content', 'aria-label': 'Визуальный HTML-редактор' } },
  onUpdate: ({ editor }) => {
    updateHeading(editor);
    if (mode.value !== 'visual') return;
    source.value = editor.isEmpty ? '' : editor.getHTML();
    emit('update:modelValue', source.value);
  },
  onSelectionUpdate: ({ editor }) => updateHeading(editor),
});
watch(() => props.modelValue, value => {
  const next = value || '';
  if (next === source.value) return;
  source.value = next;
  complexMarkup.value = isComplex(next);
  if (complexMarkup.value) mode.value = 'source';
  editor.value?.commands.setContent(next, { emitUpdate: false });
});
function switchMode(next) {
  if (next === mode.value) return;
  if (next === 'source') {
    source.value = editor.value?.isEmpty ? '' : editor.value?.getHTML() || source.value;
    emit('update:modelValue', source.value);
  } else {
    editor.value?.commands.setContent(source.value || '', { emitUpdate: false });
  }
  mode.value = next;
  linkOpen.value = false;
  imageOpen.value = false;
}
function updateSource(event) {
  source.value = event.target.value;
  emit('update:modelValue', source.value);
}
function setHeading(event) {
  const value = event.target.value;
  heading.value = value;
  if (value === 'paragraph') editor.value?.chain().focus().setParagraph().run();
  else editor.value?.chain().focus().toggleHeading({ level: Number(value) }).run();
}
function validUrl(value, image = false) {
  const url = value.trim();
  if (!url) return false;
  if (url.startsWith('/') && !url.startsWith('//')) return true;
  try {
    const protocol = new URL(url).protocol;
    return ['http:', 'https:', ...(image ? [] : ['mailto:', 'tel:'])].includes(protocol);
  } catch { return false; }
}
function applyLink() {
  if (!validUrl(linkUrl.value)) return;
  editor.value?.chain().focus().extendMarkRange('link').setLink({ href: linkUrl.value.trim() }).run();
  linkOpen.value = false;
  linkUrl.value = '';
}
function applyImage() {
  if (!validUrl(imageUrl.value, true)) return;
  editor.value?.chain().focus().setImage({ src: imageUrl.value.trim() }).run();
  imageOpen.value = false;
  imageUrl.value = '';
}
async function uploadImage(event) {
  const file = event.target.files?.[0];
  if (!file) return;
  imageUploading.value = true;
  imageError.value = '';
  const body = new FormData();
  body.append('image', file);
  try {
    const response = await fetch('/admin/images', {
      method: 'POST', credentials: 'same-origin', body,
      headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
    });
    const data = await response.json();
    if (!response.ok) throw new Error(data.errors?.image?.[0] || data.message || 'Не удалось загрузить изображение.');
    editor.value?.chain().focus().setImage({ src: data.url, alt: file.name.replace(/\.[^.]+$/, '') }).run();
  } catch (error) {
    imageError.value = error.message;
  } finally {
    imageUploading.value = false;
    event.target.value = '';
  }
}
function openLink() {
  linkUrl.value = editor.value?.getAttributes('link').href || '';
  linkOpen.value = !linkOpen.value;
  imageOpen.value = false;
}
</script>

<template>
  <div class="html-editor">
    <div class="html-editor-toolbar">
      <div class="html-editor-tools" v-if="mode === 'visual' && editor">
        <select :value="heading" aria-label="Стиль абзаца" @change="setHeading"><option value="paragraph">Обычный текст</option><option value="2">Заголовок H2</option><option value="3">Заголовок H3</option><option value="4">Заголовок H4</option></select>
        <span class="tool-divider"></span>
        <button type="button" title="Жирный" aria-label="Жирный" :class="{active: editor.isActive('bold')}" @click="editor.chain().focus().toggleBold().run()"><PhTextB :size="17" /></button>
        <button type="button" title="Курсив" aria-label="Курсив" :class="{active: editor.isActive('italic')}" @click="editor.chain().focus().toggleItalic().run()"><PhTextItalic :size="17" /></button>
        <button type="button" title="Подчёркнутый" aria-label="Подчёркнутый" :class="{active: editor.isActive('underline')}" @click="editor.chain().focus().toggleUnderline().run()"><PhTextUnderline :size="17" /></button>
        <button type="button" title="Зачёркнутый" aria-label="Зачёркнутый" :class="{active: editor.isActive('strike')}" @click="editor.chain().focus().toggleStrike().run()"><PhTextStrikethrough :size="17" /></button>
        <span class="tool-divider"></span>
        <button type="button" title="Маркированный список" aria-label="Маркированный список" :class="{active: editor.isActive('bulletList')}" @click="editor.chain().focus().toggleBulletList().run()"><PhListBullets :size="17" /></button>
        <button type="button" title="Нумерованный список" aria-label="Нумерованный список" :class="{active: editor.isActive('orderedList')}" @click="editor.chain().focus().toggleOrderedList().run()"><PhListNumbers :size="17" /></button>
        <button type="button" title="Цитата" aria-label="Цитата" :class="{active: editor.isActive('blockquote')}" @click="editor.chain().focus().toggleBlockquote().run()"><PhQuotes :size="17" /></button>
        <button type="button" title="Код" aria-label="Код" :class="{active: editor.isActive('code')}" @click="editor.chain().focus().toggleCode().run()"><PhCode :size="17" /></button>
        <span class="tool-divider"></span>
        <button type="button" title="Ссылка" aria-label="Ссылка" :class="{active: editor.isActive('link')}" @click="openLink"><PhLink :size="17" /></button>
        <button type="button" title="Изображение по URL" aria-label="Изображение по URL" @click="imageOpen = !imageOpen; linkOpen = false"><PhImage :size="17" /></button>
        <button type="button" title="Загрузить изображение" aria-label="Загрузить изображение" :disabled="imageUploading" @click="imageInput?.click()"><PhUploadSimple :size="17" /></button>
        <input ref="imageInput" class="image-upload-input" type="file" accept="image/jpeg,image/png,image/webp" aria-label="Файл изображения для HTML" @change="uploadImage">
        <span class="tool-divider"></span>
        <button type="button" title="Отменить" aria-label="Отменить" :disabled="!editor.can().undo()" @click="editor.chain().focus().undo().run()"><PhArrowCounterClockwise :size="17" /></button>
        <button type="button" title="Повторить" aria-label="Повторить" :disabled="!editor.can().redo()" @click="editor.chain().focus().redo().run()"><PhArrowClockwise :size="17" /></button>
      </div>
      <div class="html-editor-mode"><button type="button" :class="{active: mode === 'visual'}" @click="switchMode('visual')">Визуально</button><button type="button" :class="{active: mode === 'source'}" @click="switchMode('source')"><PhBracketsAngle :size="16" /> HTML</button></div>
    </div>
    <div v-if="mode === 'visual' && linkOpen" class="html-editor-insert"><input v-model="linkUrl" type="url" placeholder="https://example.com или /page" aria-label="Адрес ссылки" @keydown.enter.prevent="applyLink"><button type="button" :disabled="!validUrl(linkUrl)" @click="applyLink">Применить</button><button type="button" @click="editor.chain().focus().unsetLink().run(); linkOpen = false">Убрать ссылку</button></div>
    <div v-if="mode === 'visual' && imageOpen" class="html-editor-insert"><input v-model="imageUrl" type="url" placeholder="https://example.com/image.jpg или /storage/..." aria-label="Адрес изображения" @keydown.enter.prevent="applyImage"><button type="button" :disabled="!validUrl(imageUrl, true)" @click="applyImage">Вставить</button></div>
    <p v-if="imageError" class="html-editor-hint" role="alert">{{ imageError }}</p>
    <EditorContent v-show="mode === 'visual'" :editor="editor" />
    <textarea v-if="mode === 'source'" :id="id" class="html-editor-source" :value="source" rows="12" spellcheck="false" aria-label="Исходный HTML" @input="updateSource"></textarea>
    <p v-if="complexMarkup && mode === 'source'" class="html-editor-hint">Запись содержит нестандартную разметку. Режим HTML сохраняет её без изменений.</p>
  </div>
</template>
