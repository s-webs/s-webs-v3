<script setup>
import { nextTick, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import {
  PhArrowUpRight, PhCheck, PhImageSquare, PhMagnifyingGlass,
  PhPaperPlaneRight, PhPaperclip, PhSparkle, PhX,
} from '@phosphor-icons/vue';

const props = defineProps({ editorContext: { type: Object, default: null } });
const emit = defineEmits(['content-applied']);

const open = ref(false);
const busy = ref(false);
const reacting = ref(false);
let reactionTimer;
const error = ref('');
const input = ref('');
const uploads = ref([]);
const action = ref(null);
const enabled = ref(false);
const model = ref('');
const categories = ref({});
const pendingSeoInstruction = ref('');
const pendingContentInstruction = ref('');
const conversationEl = ref(null);
const composerInput = ref(null);
const messages = ref([{ role: 'assistant', text: 'Привет! Я помощник S-WEBS. Проверю SEO, отредактирую текст страницы или оформлю проект по скриншотам. Что сделаем?' }]);
watch(() => messages.value.length, () => {
  reacting.value = true;
  clearTimeout(reactionTimer);
  reactionTimer = setTimeout(() => { reacting.value = false; }, 1400);
});
onUnmounted(() => clearTimeout(reactionTimer));
const project = reactive({ category_id: '', name: '', description: '', year: '', client: '', link: '', seo_h1: '', seo_title: '', seo_description: '', seo_image_alt: '', publish: false });
const fields = [
  ['name', 'Название'], ['description', 'Описание'], ['year', 'Год'], ['client', 'Клиент'],
  ['link', 'Ссылка на сайт'], ['seo_h1', 'H1'], ['seo_title', 'SEO title'],
  ['seo_description', 'SEO description'], ['seo_image_alt', 'Alt изображения'],
];

async function api(url, options = {}) {
  const tokenMeta = document.querySelector('meta[name="csrf-token"]');
  const send = () => fetch(url, {
    ...options,
    credentials: 'same-origin',
    headers: { ...options.headers, Accept: 'application/json', 'X-CSRF-TOKEN': tokenMeta.content },
  });
  let response = await send();
  if (response.status === 419) {
    const refreshed = await fetch('/admin/assistant/state', { credentials: 'same-origin', headers: { Accept: 'application/json' }, cache: 'no-store' });
    if (refreshed.redirected && refreshed.url.includes('/admin/login')) {
      window.location.href = refreshed.url;
      throw new Error('Сессия истекла. Войдите снова.');
    }
    const state = refreshed.ok ? await refreshed.json() : {};
    if (state.csrf_token) { tokenMeta.content = state.csrf_token; response = await send(); }
  }
  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    const failure = new Error(Object.values(data.errors || {})[0]?.[0] || data.message || `Ошибка ${response.status}`);
    failure.choices = data.choices;
    throw failure;
  }
  return data;
}

function setAction(next) {
  action.value = next;
  if (next?.kind === 'project') Object.assign(project, { category_id: '', ...next.payload.fields, publish: false });
}

onMounted(async () => {
  try {
    const data = await api('/admin/assistant/state');
    enabled.value = data.enabled;
    model.value = data.model;
    categories.value = data.categories;
    if (data.action) {
      setAction(data.action);
      messages.value.push({ role: 'assistant', text: 'У вас есть незавершённое предложение. Проверьте его ниже.' });
    }
  } catch (e) { error.value = e.message; }
});

function addImages(event) {
  const files = Array.from(event.target.files || []).filter(file => file.type.startsWith('image/'));
  uploads.value = [...uploads.value, ...files].slice(0, 3);
  event.target.value = '';
  open.value = true;
}

function isApply(text) { return /сохрани|примени|выполни|опубликуй|подтверждаю/i.test(text); }
function isSeo(text) { return /seo|сео|поиск|мета|аудит/i.test(text); }
function isOptimize(text) { return /оптим|улучш|исправ|предлож|выполни/i.test(text); }
function isContentRequest(text) { return /текст|контент|описан|портфолио|редакт|перепиш|перефраз|сократ|расшир|абзац|формат/i.test(text); }
function seoTarget(text) {
  const quoted = text.match(/[«"“]([^»"”]+)[»"”]/);
  if (quoted) return quoted[1].trim();
  return text.match(/страниц[уыае]\s+(.+)$/i)?.[1]?.replace(/[.!?]+$/, '').trim() || '';
}

async function audit() {
  const data = await api('/admin/assistant/audit');
  const issues = data.items.reduce((sum, item) => sum + item.issues.length, 0);
  messages.value.push({ role: 'assistant', text: `Проверил контентные страницы: ${data.items.length} страниц с замечаниями, ${issues} замечаний. Подробности ниже. Напишите «предложи SEO-правки», чтобы подготовить первые страницы.`, items: data.items });
}

async function planSeo(text, target = seoTarget(text)) {
  pendingSeoInstruction.value = text;
  const data = await api('/admin/assistant/seo/plan', {
    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ instruction: text, target }),
  });
  pendingSeoInstruction.value = '';
  setAction(data.action);
  messages.value.push({ role: 'assistant', text: target
    ? `Подготовил SEO-правки для страницы «${data.action.payload.proposals[0].name}». Проверьте предложение и нажмите «Применить», если всё верно.`
    : `Подготовил правки для ${data.action.payload.proposals.length} страниц из ${data.action.payload.total_candidates} с важными замечаниями. Проверьте предложения. Напишите «выполни оптимизацию» или нажмите «Применить». Затем можно попросить следующую порцию.` });
}

async function scrollToLatest() {
  await nextTick();
  if (conversationEl.value) conversationEl.value.scrollTop = conversationEl.value.scrollHeight;
}

async function requestPageSeo(choice, instruction) {
  if (busy.value) return;
  if (action.value) {
    messages.value.push({ role: 'assistant', text: 'Сначала примените или отклоните текущее предложение.' });
    await scrollToLatest();
    return;
  }
  busy.value = true; error.value = '';
  messages.value.push({ role: 'user', text: `Оптимизируй ${choice.name}: ${choice.url}` });
  await scrollToLatest();
  try { await planSeo(instruction, choice.target); }
  catch (e) { error.value = e.message; messages.value.push({ role: 'assistant', text: `Не получилось выполнить команду: ${e.message}` }); }
  finally { busy.value = false; await scrollToLatest(); }
}

function chooseSeoPage(choice) {
  return requestPageSeo(choice, pendingSeoInstruction.value || `Улучши SEO страницы ${choice.name}`);
}

function improveAuditItem(item) {
  return requestPageSeo({ target: `${item.resource}:${item.id}`, name: item.name, url: item.url }, `Улучши SEO страницы ${item.name}`);
}

async function startContentEdit() {
  const context = props.editorContext;
  if (!context) return;
  open.value = true;
  if (context.currentHtml !== context.savedHtml) {
    messages.value.push({ role: 'assistant', text: 'В HTML-редакторе есть несохранённые изменения. Сначала сохраните страницу, затем попросите меня изменить текст.' });
  } else {
    input.value = `Улучши текст страницы «${context.name}»: сделай его яснее и структурнее, сохрани факты, ссылки и изображения.`;
  }
  await nextTick();
  composerInput.value?.focus();
  await scrollToLatest();
}
defineExpose({ startContentEdit });

async function planContent(instruction, target = null) {
  const current = props.editorContext;
  const query = seoTarget(instruction);
  let context = target;
  if (!context && current && (!query || query.toLocaleLowerCase() === current.name.toLocaleLowerCase())) context = current;
  if (!context) {
    if (!query) throw new Error('Укажите название страницы в кавычках или откройте её HTML-редактор.');
    const data = await api('/admin/assistant/content/targets');
    const normalize = value => String(value).toLocaleLowerCase().trim().replace(/\/$/, '');
    const exact = data.targets.filter(item => normalize(item.name) === normalize(query) || normalize(item.url) === normalize(query));
    const matches = exact.length ? exact : data.targets.filter(item => normalize(item.name).includes(normalize(query)));
    if (!matches.length) throw new Error('Не нашёл страницу с HTML-редактором по этому названию.');
    if (matches.length > 1) {
      pendingContentInstruction.value = instruction;
      messages.value.push({ role: 'assistant', text: 'Нашёл несколько страниц. Выберите текст, который нужно изменить.', contentChoices: matches });
      await scrollToLatest();
      return;
    }
    context = matches[0];
  }
  const editingCurrent = current?.resource === context.resource && current?.id === Number(context.id) && current?.field === context.field;
  if (editingCurrent && current.currentHtml !== current.savedHtml) throw new Error('Сначала сохраните текущие изменения в HTML-редакторе.');
  pendingContentInstruction.value = '';
  const data = await api('/admin/assistant/content/plan', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ resource: context.resource, item_id: context.id, field: context.field, ...(editingCurrent ? { current_html: current.currentHtml } : {}), instruction }),
  });
  setAction(data.action);
  messages.value.push({ role: 'assistant', text: `Подготовил новую версию текста для «${context.name}». Сравните её с исходной и примените, если всё верно.` });
  await scrollToLatest();
}

async function chooseContentPage(choice) {
  if (busy.value) return;
  if (action.value) {
    messages.value.push({ role: 'assistant', text: 'Сначала примените или отклоните текущее предложение.' });
    await scrollToLatest();
    return;
  }
  busy.value = true; error.value = '';
  messages.value.push({ role: 'user', text: `Отредактируй текст: ${choice.name} · ${choice.url}` });
  try { await planContent(pendingContentInstruction.value || `Улучши текст страницы «${choice.name}»`, choice); }
  catch (e) { error.value = e.message; messages.value.push({ role: 'assistant', text: `Не получилось выполнить команду: ${e.message}` }); }
  finally { busy.value = false; await scrollToLatest(); }
}

async function planProject(text) {
  const body = new FormData();
  body.append('instruction', text);
  uploads.value.forEach(file => body.append('images[]', file));
  const data = await api('/admin/assistant/project/plan', { method: 'POST', body });
  uploads.value = [];
  setAction(data.action);
  messages.value.push({ role: 'assistant', text: 'Оформил карточку по скриншотам. Проверьте название, клиента, год и категорию. Неизвестные факты оставлены пустыми. После проверки напишите «сохрани».' });
}

async function apply() {
  if (!action.value) return;
  if (action.value.kind === 'content' && props.editorContext?.resource === action.value.payload.resource
    && props.editorContext?.id === Number(action.value.payload.id)
    && props.editorContext?.currentHtml !== action.value.payload.original_html) {
    throw new Error('Текст в редакторе изменился после создания предложения. Сохраните его и создайте новое предложение.');
  }
  const body = action.value.kind === 'project' ? { ...project, publish: !!project.publish } : {};
  const data = await api(`/admin/assistant/actions/${action.value.id}/apply`, {
    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body),
  });
  messages.value.push({ role: 'assistant', text: data.message, link: data.project?.url });
  if (data.content) emit('content-applied', data.content);
  action.value = null;
}

async function applyFromButton() {
  if (busy.value) return;
  busy.value = true; error.value = '';
  try { await apply(); }
  catch (e) { error.value = e.message; messages.value.push({ role: 'assistant', text: `Не удалось применить предложение: ${e.message}` }); }
  finally { busy.value = false; await scrollToLatest(); }
}

async function discard() {
  if (!action.value) return;
  await api(`/admin/assistant/actions/${action.value.id}`, { method: 'DELETE' });
  action.value = null;
  messages.value.push({ role: 'assistant', text: 'Предложение отклонено.' });
}

async function send(quick = '') {
  const text = (quick || input.value).trim();
  if (!text && !uploads.value.length) return;
  if (busy.value) return;
  busy.value = true; error.value = ''; input.value = '';
  messages.value.push({ role: 'user', text: text || `${uploads.value.length} скриншот(а)` });
  try {
    if (action.value && isApply(text)) await apply();
    else if (action.value && (uploads.value.length || isOptimize(text) || isContentRequest(text))) messages.value.push({ role: 'assistant', text: 'Сначала примените или отклоните текущее предложение.' });
    else if (uploads.value.length) await planProject(text);
    else if ((isSeo(text) && isOptimize(text)) || /оптимизац/i.test(text)) await planSeo(text);
    else if (isSeo(text)) await audit();
    else if (isContentRequest(text)) await planContent(text);
    else messages.value.push({ role: 'assistant', text: 'Могу провести SEO-аудит, предложить правки или оформить проект по скриншотам. Для проекта прикрепите 1–3 изображения и напишите, что известно о нём.' });
  } catch (e) {
    if (e.choices?.length) messages.value.push({ role: 'assistant', text: e.message, choices: e.choices });
    else { error.value = e.message; messages.value.push({ role: 'assistant', text: `Не получилось выполнить команду: ${e.message}` }); }
  }
  finally { busy.value = false; }
}
</script>

<template>
  <div class="assistant-widget" :class="{ expanded: open }">
    <section v-if="open" class="assistant-window" aria-label="ИИ-помощник S-WEBS">
      <header class="assistant-header"><div class="assistant-header-icon"><PhSparkle :size="20" /></div><div><strong>Ассистент S-WEBS</strong><small>{{ enabled ? `Готов · ${model}` : 'SEO-аудит доступен без ключа' }}</small></div><button type="button" aria-label="Закрыть помощника" @click="open = false"><PhX :size="17" /></button></header>
      <div ref="conversationEl" class="assistant-conversation" aria-live="polite">
        <div v-for="(message, index) in messages" :key="index" class="assistant-message" :class="message.role">
          <p>{{ message.text }}</p>
          <a v-if="message.link" :href="message.link">Открыть проект <PhArrowUpRight :size="13" /></a>
          <div v-if="message.choices?.length" class="assistant-choices"><button v-for="choice in message.choices" :key="choice.target" type="button" :disabled="busy" @click="chooseSeoPage(choice)"><strong>{{ choice.name }}</strong><small>{{ choice.resource === 'categories' ? 'Категория' : choice.resource === 'prices' ? 'Услуга' : choice.resource === 'projects' ? 'Проект' : 'SEO-страница' }} · {{ choice.url }}</small></button></div>
          <div v-if="message.contentChoices?.length" class="assistant-choices"><button v-for="choice in message.contentChoices" :key="`${choice.resource}:${choice.id}`" type="button" :disabled="busy || !!action" @click="chooseContentPage(choice)"><strong>{{ choice.name }}</strong><small>{{ choice.resource === 'prices' ? 'Услуга' : choice.resource === 'projects' ? 'Проект' : choice.resource === 'teams' ? 'Команда' : 'SEO-страница' }} · {{ choice.url }}</small></button></div>
          <details v-if="message.items?.length" class="assistant-audit-details"><summary>Страницы с замечаниями ({{ message.items.length }})</summary><div v-for="item in message.items" :key="`${item.resource}:${item.id}`" class="assistant-audit-item"><a :href="item.url" target="_blank" rel="noopener noreferrer">{{ item.name }} <PhArrowUpRight :size="12" /></a><small>{{ item.url }}</small><ul><li v-for="(issue, i) in item.issues" :key="i">{{ issue.message }}</li></ul><button type="button" class="assistant-audit-improve" :disabled="busy || !!action" :aria-label="`Улучшить SEO страницы ${item.name}`" @click="improveAuditItem(item)"><PhSparkle :size="13" /> Улучшить SEO</button></div></details>
        </div>
        <div v-if="busy" class="assistant-message assistant thinking"><span class="assistant-dots"><i></i><i></i><i></i></span> Работаю над задачей…</div>
      </div>
      <div v-if="action?.kind === 'seo'" class="assistant-action"><div class="assistant-action-heading"><strong>SEO-правки</strong><small>{{ action.payload.proposals.length }} {{ action.payload.proposals.length === 1 ? 'страница' : 'страницы' }}</small></div><details v-for="proposal in action.payload.proposals" :key="`${proposal.resource}:${proposal.id}`"><summary>{{ proposal.name }}</summary><small>{{ proposal.url }}</small><div v-for="suggestion in proposal.suggestions" :key="suggestion.field" class="assistant-suggestion"><strong>{{ suggestion.field }}</strong><p>{{ suggestion.value }}</p><small>{{ suggestion.reason }}</small></div></details><div class="assistant-action-buttons"><button class="button primary" :disabled="busy" @click="applyFromButton"><PhCheck :size="15" /> Применить</button><button class="button" :disabled="busy" @click="discard">Отклонить</button></div></div>
      <div v-if="action?.kind === 'content'" class="assistant-action assistant-content"><div class="assistant-action-heading"><strong>HTML-текст · {{ action.payload.name }}</strong></div><p v-if="action.payload.summary" class="assistant-content-summary">{{ action.payload.summary }}</p><details><summary>Исходный HTML</summary><pre>{{ action.payload.original_html || 'Поле пустое' }}</pre></details><details open><summary>Предложение</summary><div class="assistant-content-preview" v-html="action.payload.proposed_html"></div></details><div class="assistant-action-buttons"><button class="button primary" :disabled="busy" @click="applyFromButton"><PhCheck :size="15" /> Применить текст</button><button class="button" :disabled="busy" @click="discard">Отклонить</button></div></div>
      <div v-if="action?.kind === 'project'" class="assistant-action assistant-project"><div class="assistant-action-heading"><strong>Карточка проекта</strong><small>{{ action.payload.image_count }} скриншот(а)</small></div><label>Категория<select v-model="project.category_id"><option value="">Выберите категорию</option><option v-for="(name, id) in categories" :key="id" :value="id">{{ name }}</option></select></label><label v-for="[key, label] in fields" :key="key">{{ label }}<textarea v-if="key === 'description' || key === 'seo_description'" v-model="project[key]" rows="3"></textarea><input v-else v-model="project[key]" :type="key === 'year' ? 'number' : 'text'"></label><label class="assistant-publish"><input v-model="project.publish" type="checkbox"> Опубликовать на сайте после сохранения</label><div class="assistant-action-buttons"><button class="button primary" :disabled="busy" @click="applyFromButton"><PhCheck :size="15" /> Сохранить проект</button><button class="button" :disabled="busy" @click="discard">Отклонить</button></div></div>
      <div class="assistant-composer"><div v-if="uploads.length" class="assistant-files"><span v-for="(file, i) in uploads" :key="i"><PhImageSquare :size="14" /> {{ file.name }} <button :aria-label="`Убрать ${file.name}`" @click="uploads.splice(i, 1)"><PhX :size="12" /></button></span></div><div class="assistant-input-row"><label class="assistant-attach" title="Прикрепить скриншоты"><PhPaperclip :size="19" /><input type="file" accept="image/png,image/jpeg,image/webp" multiple @change="addImages"></label><input ref="composerInput" v-model="input" type="text" aria-label="Сообщение помощнику" placeholder="Попросите проверить SEO или приложите скрины" @keydown.enter="send()"><button class="assistant-send" :disabled="busy || (!input.trim() && !uploads.length)" aria-label="Отправить" @click="send()"><PhPaperPlaneRight :size="19" /></button></div><div class="assistant-quick"><button v-if="editorContext" @click="send('Улучши текст в HTML-редакторе, сохрани факты и ссылки')"><PhSparkle :size="13" /> Улучшить текст</button><button @click="send('Проведи SEO-аудит')"><PhMagnifyingGlass :size="13" /> SEO-аудит</button><button @click="send('Предложи SEO-правки')"><PhSparkle :size="13" /> SEO-правки</button></div><p v-if="!enabled">Для генерации текста и анализа скриншотов <a href="/admin/settings">настройте OpenAI API</a>.</p><p v-if="error" class="assistant-error" role="alert">{{ error }}</p></div>
    </section>
    <button class="assistant-robot" :class="{ thinking: busy, reacting }" type="button" :aria-label="open ? 'Скрыть помощника' : 'Открыть помощника'" :aria-expanded="open" @click="open = !open">
      <span class="robot-aura"></span>
      <span class="robot-float"><img class="robot-figure" :src="'/assets/img/assistant-robot.webp'" alt="" width="112" height="112"><span class="robot-eye left"></span><span class="robot-eye right"></span><span class="robot-sparkle one"></span><span class="robot-sparkle two"></span></span>
      <span class="robot-shadow"></span>
      <span class="robot-hint">{{ busy ? 'Думаю…' : 'Спросите меня' }}</span>
    </button>
  </div>
</template>
