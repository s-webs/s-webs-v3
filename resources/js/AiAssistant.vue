<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { PhArrowClockwise, PhCheck, PhSparkle, PhWarningCircle } from '@phosphor-icons/vue';

const props = defineProps({
  resource: { type: String, required: true },
  itemId: { type: [String, Number], default: null },
  current: { type: Object, required: true },
  fields: { type: Array, required: true },
  enabled: { type: Boolean, default: false },
  dirty: { type: Boolean, default: false },
});
const emit = defineEmits(['applied']);
const instruction = ref('Улучши текст и SEO-поля, сохрани все факты и язык страницы.');
const draft = ref(null);
const selected = ref([]);
const issues = ref([]);
const busy = ref(false);
const error = ref('');
const notice = ref('');
const fieldLabels = computed(() => Object.fromEntries(props.fields.map(([name, label]) => [name, label])));
const suggestions = computed(() => draft.value?.suggestions || []);
const isNew = computed(() => !props.itemId);

async function request(url, options = {}) {
  const response = await fetch(url, {
    ...options,
    credentials: 'same-origin',
    headers: { ...options.headers, Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
  });
  const data = await response.json().catch(() => ({}));
  if (!response.ok) throw new Error(Object.values(data.errors || {})[0]?.[0] || data.message || `Ошибка ${response.status}`);
  return data;
}
async function audit() {
  if (!props.itemId) { issues.value = []; return; }
  try { issues.value = (await request(`/admin/ai/audit/${props.resource}/${props.itemId}`)).issues || []; }
  catch (e) { error.value = e.message; }
}
async function generate() {
  busy.value = true; error.value = ''; notice.value = ''; draft.value = null;
  try {
    const data = await request('/admin/ai/drafts', {
      method: 'POST', body: JSON.stringify({ resource: props.resource, item_id: props.itemId || null, instruction: instruction.value, current: props.current }),
    });
    draft.value = data.draft;
    selected.value = suggestions.value.map(row => row.field);
    if (!suggestions.value.length) notice.value = 'Модель не предложила изменений для этой записи.';
  } catch (e) { error.value = e.message; }
  finally { busy.value = false; }
}
function htmlValue(field, value) {
  if (!((['projects', 'prices'].includes(props.resource) && field === 'description') || (props.resource === 'teams' && field === 'portfolio') || (props.resource === 'seo' && field === 'text'))) return value;
  const escaped = value.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  return escaped.split(/\n\s*\n/).map(part => `<p>${part.replace(/\n/g, '<br>')}</p>`).join('');
}
async function apply() {
  if (!selected.value.length || !draft.value) return;
  busy.value = true; error.value = '';
  try {
    if (isNew.value) {
      const values = Object.fromEntries(suggestions.value.filter(row => selected.value.includes(row.field)).map(row => [row.field, htmlValue(row.field, row.value)]));
      emit('applied', values);
      notice.value = 'Предложения перенесены в форму. Проверьте и сохраните запись.';
    } else {
      const data = await request(`/admin/ai/drafts/${draft.value.id}/apply`, { method: 'POST', body: JSON.stringify({ fields: selected.value }) });
      emit('applied', data.item);
      notice.value = data.message;
      await audit();
    }
    draft.value = null;
  } catch (e) { error.value = e.message; }
  finally { busy.value = false; }
}
async function discard() {
  if (!draft.value) return;
  try { await request(`/admin/ai/drafts/${draft.value.id}/discard`, { method: 'POST' }); draft.value = null; selected.value = []; }
  catch (e) { error.value = e.message; }
}
watch(() => [props.resource, props.itemId], () => { draft.value = null; audit(); });
onMounted(audit);
</script>

<template>
  <div class="ai-assistant">
    <div class="section-heading"><h2>ИИ-помощник</h2><p>Подготовит черновик текста и SEO-полей. Перед применением проверьте факты, имена, цены и ссылки.</p></div>
    <div v-if="!enabled" class="notice">Для генерации <a href="/admin/settings">укажите ключ и модель в настройках OpenAI</a> и включите функцию.</div>
    <div v-if="dirty && !isNew" class="notice">Сначала сохраните изменения формы. ИИ работает с последней сохранённой версией записи.</div>
    <div class="field"><label for="ai-instruction">Что нужно улучшить</label><textarea id="ai-instruction" v-model="instruction" rows="3" maxlength="1000" placeholder="Например: сделай описание проекта яснее и предложи SEO title"></textarea></div>
    <button class="button primary" type="button" :disabled="!enabled || busy || (dirty && !isNew) || !instruction.trim()" @click="generate"><PhSparkle :size="17" /> {{ busy ? 'Подготавливаем…' : 'Создать предложения' }}</button>
    <p v-if="error" class="notice error" role="alert">{{ error }}</p>
    <p v-if="notice" class="notice success" role="status">{{ notice }}</p>

    <section v-if="draft" class="ai-results"><div class="ai-results-head"><h3>Предложения</h3><small>Черновик #{{ draft.id }} · {{ draft.model }}</small></div>
      <div v-for="row in suggestions" :key="row.field" class="ai-suggestion">
        <label><input v-model="selected" type="checkbox" :value="row.field"><strong>{{ fieldLabels[row.field] || row.field }}</strong></label>
        <p class="ai-reason">{{ row.reason }}</p>
        <div class="ai-compare"><div><small>Сейчас</small><p>{{ String(current[row.field] || '').replace(/<[^>]*>/g, ' ').slice(0, 1000) || '—' }}</p></div><div><small>Предложение</small><p>{{ row.value }}</p></div></div>
      </div>
      <div class="ai-actions"><button class="button primary" type="button" :disabled="busy || !selected.length || (dirty && !isNew)" @click="apply"><PhCheck :size="17" /> {{ isNew ? 'Перенести в форму' : 'Применить выбранное' }}</button><button class="button" type="button" :disabled="busy" @click="discard">Отклонить</button></div>
    </section>

    <section class="ai-audit"><div class="ai-results-head"><h3>Проверка SEO</h3><button v-if="itemId" class="ai-refresh" type="button" @click="audit"><PhArrowClockwise :size="16" /> Обновить</button></div>
      <p v-if="issues.length" class="muted">Страница: <a :href="issues[0].url" target="_blank" rel="noopener noreferrer">{{ issues[0].url }}</a></p>
      <p v-if="!itemId" class="muted">Проверка доступна после сохранения записи.</p>
      <p v-else-if="!issues.length" class="muted">Замечаний по базовым полям не найдено.</p>
      <div v-for="(issue, index) in issues" :key="index" class="ai-issue" :class="issue.severity"><PhWarningCircle :size="17" /><span><strong>{{ fieldLabels[issue.field] || issue.field }}</strong> — {{ issue.message }}</span></div>
    </section>
  </div>
</template>
