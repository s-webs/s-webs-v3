<script setup>
import { computed, defineAsyncComponent, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import ImageUpload from './ImageUpload.vue';
import GlobalAssistant from './GlobalAssistant.vue';
import AdminSettings from './AdminSettings.vue';
import AdminUsers from './AdminUsers.vue';
const HtmlEditor = defineAsyncComponent(() => import('./HtmlEditor.vue'));
import {
  PhArrowLeft, PhArrowRight, PhArrowUpRight, PhCheck, PhCircle,
  PhCurrencyCircleDollar, PhFiles, PhFloppyDisk, PhFolders,
  PhGear, PhGlobe, PhHouse, PhMagnifyingGlass, PhNotePencil, PhShieldCheck,
  PhPlus, PhSignOut, PhTrash, PhUsersThree,
  PhSparkle,
} from '@phosphor-icons/vue';

const sections = [
  ['categories', 'Категории', PhFolders], ['projects', 'Проекты', PhFiles],
  ['prices', 'Услуги и цены', PhCurrencyCircleDollar],
  ['teams', 'Команда', PhUsersThree], ['seo', 'SEO страниц', PhMagnifyingGlass],
];
const utilitySections = [['admins', 'Администраторы', PhShieldCheck], ['settings', 'Настройки', PhGear]];
const state = reactive({ resource: '', mode: 'dashboard', id: null, loading: false, saving: false, error: '', notice: '' });
const counts = ref({});
const rows = ref([]);
const pagination = ref({});
const search = ref('');
const status = ref('all');
const fields = ref([]);
const form = reactive({});
const errors = ref({});
const categories = ref({});
const uploads = reactive({});
const previewUrls = reactive({});
const savedHtml = reactive({});
const globalAssistant = ref(null);
const tab = ref('content');
const csrf = document.querySelector('meta[name="csrf-token"]').content;
let searchTimer;

const title = computed(() => [...sections, ...utilitySections].find(([key]) => key === state.resource)?.[1] || 'Панель управления');
const contentFields = computed(() => fields.value.filter(([name]) => !isSeo(name) && !isMedia(name)));
const seoFields = computed(() => fields.value.filter(([name]) => isSeo(name)));
const mediaFields = computed(() => fields.value.filter(([name]) => isMedia(name) && !isSeo(name)));
const editorContext = computed(() => {
  if (state.mode !== 'edit' || !state.id) return null;
  const htmlField = contentFields.value.find(([, , type]) => type === 'html');
  if (!htmlField) return null;
  const field = htmlField[0];
  return {
    resource: state.resource, id: Number(state.id), field,
    name: String(form.name || form.url || form.title || 'страница'),
    currentHtml: String(form[field] || ''), savedHtml: String(savedHtml[field] || ''),
  };
});
const seoPrefix = computed(() => state.resource === 'seo' ? '' : 'seo_');
const metaTitle = computed(() => String(form[seoPrefix.value + (state.resource === 'seo' ? 'title' : 'title')] || form.name || form.title || 'Название страницы'));
const metaDescription = computed(() => String(form[seoPrefix.value + 'description'] || form.short_description || 'Добавьте описание страницы — оно появится здесь.'));
const pageUrl = computed(() => {
  if (state.resource === 'seo') return form.url || '/';
  const slug = form.slug || 'slug';
  return ({ categories: `/portfolio-${form.id || 'id'}`, projects: `/portfolio/${slug}`, prices: `/pricing/${slug}`, teams: `/about/${slug}` })[state.resource] || '/';
});
const canonical = computed(() => form[seoPrefix.value + 'canonical'] || window.location.origin + pageUrl.value);
const seoChecks = computed(() => [
  ['Title', !!form[seoPrefix.value + 'title'], metaTitle.value.length, '30–60 символов'],
  ['Description', !!form[seoPrefix.value + 'description'], metaDescription.value.length, '70–160 символов'],
  ['H1', state.resource === 'seo' || !!form.seo_h1, null, 'Заголовок страницы'],
  ['Alt изображения', !!form[seoPrefix.value + 'image_alt'], null, 'Описание для изображения'],
]);

function isSeo(name) { return name.startsWith('seo_') || (state.resource === 'seo' && name !== 'url' && name !== 'text'); }
function isMedia(name) { return fields.value.find(([key]) => key === name)?.[2] === 'file'; }
function activePath() { return window.location.pathname.replace(/\/$/, '') || '/admin'; }
function parsePath() {
  const parts = activePath().split('/').filter(Boolean);
  if (utilitySections.some(([key]) => key === parts[1])) {
    state.resource = parts[1]; state.mode = parts[1]; state.id = null; return;
  }
  state.resource = sections.some(([key]) => key === parts[1]) ? parts[1] : '';
  state.mode = !state.resource ? 'dashboard' : parts[2] === 'create' ? 'create' : parts[3] === 'edit' ? 'edit' : 'list';
  state.id = state.mode === 'edit' ? parts[2] : null;
}
async function api(url, options = {}) {
  const response = await fetch(url, {
    ...options,
    credentials: 'same-origin',
    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, ...options.headers },
  });
  if (response.redirected && response.url.includes('/admin/login')) { window.location.href = response.url; return; }
  const data = await response.json().catch(() => ({}));
  if (!response.ok) { const error = new Error(data.message || `Ошибка ${response.status}`); error.validation = data.errors || {}; throw error; }
  return data;
}
function goto(path) { history.pushState({}, '', path); search.value = ''; status.value = 'all'; load(); }
async function load(page = 1) {
  parsePath(); state.loading = true; state.error = ''; state.notice = ''; errors.value = {}; tab.value = 'content';
  try {
    if (state.mode === 'dashboard') counts.value = (await api('/admin')).counts;
    else if (state.mode === 'settings' || state.mode === 'admins') { /* Components load their own data. */ }
    else if (state.mode === 'list') await loadList(page);
    else {
      const data = await api(activePath());
      fields.value = data.fields; categories.value = data.categories || {};
      Object.keys(form).forEach(key => delete form[key]);
      Object.assign(form, data.item || {});
      Object.keys(savedHtml).forEach(key => delete savedHtml[key]);
      for (const [name, , type] of fields.value) if (type === 'html') savedHtml[name] = String(data.item?.[name] || '');
      for (const [name, , type] of fields.value) {
        if (form[name] == null) form[name] = type === 'checkbox' ? false : type === 'robots' ? 'index,follow' : '';
        if ((name === 'options' || name === 'social') && typeof form[name] === 'object') form[name] = JSON.stringify(form[name], null, 2);
      }
      for (const key of Object.keys(uploads)) delete uploads[key];
      for (const key of Object.keys(previewUrls)) delete previewUrls[key];
    }
  } catch (e) { state.error = e.message; }
  finally { state.loading = false; }
}
async function loadList(page = 1) {
  const params = new URLSearchParams({ page: String(page) });
  if (search.value) params.set('search', search.value);
  if (status.value !== 'all') params.set('status', status.value);
  const data = await api(`/admin/${state.resource}?${params}`);
  rows.value = data.data; pagination.value = data;
}
watch([search, status], () => {
  if (state.mode !== 'list') return;
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => loadList().catch(e => state.error = e.message), 300);
});
function setFile(name, file) {
  if (!file) return;
  uploads[name] = file;
  if (previewUrls[name]) URL.revokeObjectURL(previewUrls[name]);
  previewUrls[name] = URL.createObjectURL(file);
}
function imageUrl(value) { return value ? (String(value).startsWith('http') ? value : '/' + String(value).replace(/^\//, '')) : ''; }
function slugify(value) { return String(value).toLowerCase().normalize('NFKD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9а-яё]+/gi, '-').replace(/^-|-$/g, ''); }
function suggestSlug() { if (!form.slug) form.slug = slugify(form.name); }
async function save() {
  state.saving = true; state.error = ''; errors.value = {};
  const body = new FormData();
  for (const [name, , type] of fields.value) {
    if (type === 'file') { if (uploads[name]) body.append(name + '_upload', uploads[name]); continue; }
    if (type === 'checkbox') body.append(name, form[name] ? '1' : '0');
    else body.append(name, form[name] ?? '');
  }
  if (state.mode === 'edit') body.append('_method', 'PUT');
  try {
    const data = await api(state.mode === 'create' ? `/admin/${state.resource}` : `/admin/${state.resource}/${state.id}`, { method: 'POST', body });
    state.notice = data.message;
    goto(`/admin/${state.resource}/${data.item.id}/edit`);
    state.notice = data.message;
  } catch (e) {
    state.error = e.message; errors.value = e.validation;
    const failedFields = Object.keys(errors.value);
    if (failedFields.some(key => isSeo(key))) tab.value = 'seo';
    else if (failedFields.some(key => key.endsWith('_upload'))) tab.value = 'media';
  }
  finally { state.saving = false; }
}
async function remove(row) {
  if (!window.confirm(`Удалить запись «${row.name || row.url}»?`)) return;
  try { await api(`/admin/${state.resource}/${row.id}`, { method: 'DELETE' }); await loadList(pagination.value.current_page); state.notice = 'Запись удалена.'; }
  catch (e) { state.error = e.message; }
}
function onPop() { load(); }
function onAssistantContentApplied(content) {
  if (state.mode !== 'edit' || state.resource !== content.resource || Number(state.id) !== Number(content.id)) return;
  form[content.field] = content.html;
  savedHtml[content.field] = content.html;
  state.notice = 'Текст обновлён помощником.';
}
onMounted(() => { window.addEventListener('popstate', onPop); load(); });
onUnmounted(() => { window.removeEventListener('popstate', onPop); clearTimeout(searchTimer); Object.values(previewUrls).forEach(URL.revokeObjectURL); });
</script>

<template>
  <div class="admin-layout">
    <aside class="sidebar">
      <a class="brand" href="/admin" @click.prevent="goto('/admin')"><span class="brand-mark">S</span><span class="brand-name">S-WEBS<small>Управление сайтом</small></span></a>
      <nav aria-label="Разделы админки">
        <a href="/admin" :class="{ active: !state.resource }" @click.prevent="goto('/admin')"><PhHouse :size="19" weight="regular" /> <span>Обзор</span></a>
        <a v-for="[key, label, icon] in sections" :key="key" :href="`/admin/${key}`" :class="{ active: state.resource === key }" @click.prevent="goto(`/admin/${key}`)"><component :is="icon" :size="19" weight="regular" /> <span>{{ label }}</span></a>
      </nav>
      <nav class="sidebar-utilities" aria-label="Настройки админки"><a v-for="[key, label, icon] in utilitySections" :key="key" :href="`/admin/${key}`" :class="{ active: state.resource === key }" @click.prevent="goto(`/admin/${key}`)"><component :is="icon" :size="19" weight="regular" /> <span>{{ label }}</span></a></nav>
      <div class="sidebar-bottom"><a href="/" target="_blank" rel="noopener"><PhGlobe :size="18" /> <span>Открыть сайт</span><PhArrowUpRight :size="14" class="trailing-icon" /></a><form method="post" action="/admin/logout"><input type="hidden" name="_token" :value="csrf"><button type="submit"><PhSignOut :size="18" /> <span>Выйти</span></button></form></div>
    </aside>
    <main class="workspace">
      <header class="topbar"><div><span class="eyebrow">{{ state.mode === 'dashboard' ? 'Обзор' : title }}</span><h1>{{ state.mode === 'dashboard' ? 'Панель управления' : ['list', 'settings', 'admins'].includes(state.mode) ? title : state.mode === 'create' ? `Новая запись · ${title}` : `Редактирование · ${title}` }}</h1></div></header>
      <div v-if="state.notice" class="notice success" role="status">{{ state.notice }}</div>
      <div v-if="state.error" class="notice error" role="alert">{{ state.error }}</div>
      <div v-if="state.loading" class="panel loading-state">Загружаем данные…</div>
      <AdminSettings v-else-if="state.mode === 'settings'" />
      <AdminUsers v-else-if="state.mode === 'admins'" />
      <template v-else-if="state.mode === 'dashboard'">
        <div class="intro"><h2>Содержание сайта</h2><p>Разделы и SEO-настройки страниц.</p></div>
        <div class="stats"><a v-for="[key, label, icon] in sections" :key="key" class="stat panel" :href="`/admin/${key}`" @click.prevent="goto(`/admin/${key}`)"><component :is="icon" :size="22" weight="regular" /><PhArrowUpRight :size="17" class="stat-arrow" /><strong>{{ counts[key] ?? '—' }}</strong><span>{{ label }}</span></a></div>
      </template>
      <template v-else-if="state.mode === 'list'">
        <div class="list-toolbar"><p>{{ pagination.total || 0 }} записей</p><button class="button primary" @click="goto(`/admin/${state.resource}/create`)"><PhPlus :size="17" weight="bold" /> Добавить</button></div>
        <div class="filters"><label class="search-field"><PhMagnifyingGlass :size="18" /><input v-model="search" type="search" :placeholder="state.resource === 'seo' ? 'Поиск по URL' : 'Поиск по названию'" aria-label="Поиск"></label><select v-if="state.resource !== 'seo'" v-model="status" aria-label="Статус"><option value="all">Все статусы</option><option value="active">Активные</option><option value="inactive">Неактивные</option></select></div>
        <div class="panel table-wrap"><table><thead><tr><th>Название / URL</th><th v-if="state.resource !== 'seo'">Статус</th><th>SEO title</th><th class="actions-col">Действия</th></tr></thead><tbody><tr v-for="row in rows" :key="row.id"><td><strong>{{ row.name || row.url }}</strong><small>{{ row.slug || (state.resource === 'categories' ? `ID ${row.id}` : '') }}</small></td><td v-if="state.resource !== 'seo'"><span class="badge" :class="(row.active ?? row.is_active) ? 'on' : 'off'">{{ (row.active ?? row.is_active) ? 'Активно' : 'Скрыто' }}</span></td><td class="muted">{{ row.seo_title || row.title || '—' }}</td><td><div class="row-actions"><button :aria-label="`Изменить ${row.name || row.url}`" title="Изменить" @click="goto(`/admin/${state.resource}/${row.id}/edit`)"><PhNotePencil :size="18" /></button><button class="delete" :aria-label="`Удалить ${row.name || row.url}`" title="Удалить" @click="remove(row)"><PhTrash :size="18" /></button></div></td></tr><tr v-if="!rows.length"><td :colspan="state.resource === 'seo' ? 3 : 4" class="empty">Записей пока нет</td></tr></tbody></table></div>
        <div v-if="pagination.last_page > 1" class="pager"><button :disabled="pagination.current_page <= 1" @click="loadList(pagination.current_page - 1)"><PhArrowLeft :size="16" /> Назад</button><span>{{ pagination.current_page }} / {{ pagination.last_page }}</span><button :disabled="pagination.current_page >= pagination.last_page" @click="loadList(pagination.current_page + 1)">Вперёд <PhArrowRight :size="16" /></button></div>
      </template>
      <template v-else>
        <div class="form-toolbar"><button class="back" @click="goto(`/admin/${state.resource}`)"><PhArrowLeft :size="17" /> К списку</button><button class="button primary" :disabled="state.saving" @click="save"><PhFloppyDisk :size="17" /> {{ state.saving ? 'Сохраняем…' : 'Сохранить' }}</button></div>
        <div class="editor-layout" :class="{ 'with-seo': tab === 'seo' }"><section class="editor-main"><div class="tabs"><button v-for="[key,label] in [['content','Содержание'],['seo','SEO и соцсети'],['media','Медиа']]" :key="key" :class="{selected: tab === key}" @click="tab = key">{{ label }}</button></div>
          <div class="panel form-panel">
            <template v-if="tab === 'content'">
              <div class="section-heading"><h2>Основная информация</h2><p>Данные, которые увидят посетители сайта.</p></div>
              <div v-for="[name,label,type] in contentFields" :key="name" class="field" :class="{ inline: type === 'checkbox' }">
                <div v-if="type === 'html'" class="editor-field-heading"><label :for="name">{{ label }}</label><button v-if="state.mode === 'edit'" type="button" class="editor-ai-button" @click="globalAssistant?.startContentEdit()"><PhSparkle :size="15" /> Редактировать с ИИ</button></div>
                <label v-else :for="name">{{ label }}</label>
                <input v-if="type === 'checkbox'" :id="name" v-model="form[name]" type="checkbox">
                <select v-else-if="type === 'select'" :id="name" v-model="form[name]"><option value="">Выберите категорию</option><option v-for="(value,key) in categories" :key="key" :value="key">{{ value }}</option></select>
                <HtmlEditor v-else-if="type === 'html'" :key="`${state.resource}:${state.id || 'new'}:${name}`" :id="name" v-model="form[name]" />
                <textarea v-else-if="type === 'textarea'" :id="name" v-model="form[name]" rows="7"></textarea>
                <div v-else class="input-row"><input :id="name" v-model="form[name]" :type="type === 'number' ? 'number' : type === 'url' ? 'url' : 'text'" :step="type === 'number' ? 1 : undefined" @blur="name === 'name' && suggestSlug()"><button v-if="name === 'slug'" type="button" @click="form.slug = slugify(form.name)">Создать slug</button></div>
                <small v-if="errors[name]" class="field-error">{{ errors[name][0] }}</small>
              </div>
            </template>
            <template v-if="tab === 'seo'">
              <div class="section-heading"><h2>Поисковая оптимизация</h2><p>Заголовки и описания обновляются в предпросмотре сразу после ввода.</p></div>
              <div v-for="[name,label,type] in seoFields" :key="name" class="field">
                <ImageUpload v-if="type === 'file'" :id="name" :label="label" :src="previewUrls[name] || imageUrl(form[name])" :file-name="uploads[name]?.name || ''" @select="setFile(name, $event)" />
                <template v-else><label :for="name">{{ label }}</label><select v-if="type === 'robots'" :id="name" v-model="form[name]"><option value="index,follow">Индексировать страницу и ссылки</option><option value="noindex,follow">Не индексировать страницу</option><option value="noindex,nofollow">Не индексировать страницу и ссылки</option></select><textarea v-else-if="type === 'textarea'" :id="name" v-model="form[name]" rows="4"></textarea><input v-else :id="name" v-model="form[name]" :type="type === 'url' ? 'url' : 'text'"><small v-if="name.endsWith('title') || name.endsWith('description')">{{ String(form[name] || '').length }} символов</small></template>
                <small v-if="errors[name] || errors[name+'_upload']" class="field-error">{{ (errors[name] || errors[name+'_upload'])[0] }}</small>
              </div>
            </template>
            <template v-if="tab === 'media'">
              <div class="section-heading"><h2>Изображения</h2><p>Файлы для страниц и карточек.</p></div>
              <div v-if="!mediaFields.length" class="empty">У этой записи нет основных изображений.</div>
              <div v-for="[name,label] in mediaFields" :key="name" class="field">
                <ImageUpload :id="name" :label="label" :src="previewUrls[name] || imageUrl(form[name])" :file-name="uploads[name]?.name || ''" @select="setFile(name, $event)" />
                <small v-if="errors[name+'_upload']" class="field-error">{{ errors[name+'_upload'][0] }}</small>
              </div>
            </template>
          </div></section>
          <aside v-if="tab === 'seo'" class="seo-side"><div class="panel preview-panel"><span class="eyebrow">Предпросмотр в поиске</span><div class="serp"><span class="serp-site">S-WEBS · {{ canonical }}</span><strong>{{ metaTitle }}</strong><p>{{ metaDescription }}</p></div><div class="lengths"><span>Title: {{ metaTitle.length }} / 60</span><span>Description: {{ metaDescription.length }} / 160</span></div></div><div class="panel checklist"><span class="eyebrow">SEO-поля</span><div v-for="[label,done,count,hint] in seoChecks" :key="label" class="check"><PhCheck v-if="done" :size="16" weight="bold" class="done" /><PhCircle v-else :size="16" class="missing" /><span>{{ label }}<small>{{ count === null ? hint : `${count} · ${hint}` }}</small></span></div></div><div class="panel url-panel"><span class="eyebrow">Адрес страницы</span><code>{{ pageUrl }}</code></div></aside>
        </div>
      </template>
    </main>
    <GlobalAssistant ref="globalAssistant" :editor-context="editorContext" @content-applied="onAssistantContentApplied" />
  </div>
</template>
