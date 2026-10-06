<script setup>
import { onMounted, reactive, ref } from 'vue';
import { PhFloppyDisk, PhKey, PhTrash } from '@phosphor-icons/vue';

const form = reactive({ api_key: '', model: '', enabled: false, max_output_tokens: 1600, daily_request_limit: 30, daily_token_limit: 100000 });
const hasKey = ref(false);
const busy = ref(false);
const error = ref('');
const notice = ref('');
const csrf = document.querySelector('meta[name="csrf-token"]').content;
async function api(method, url, body) {
  const response = await fetch(url, { method, credentials: 'same-origin', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }, body: body ? JSON.stringify(body) : undefined });
  const data = await response.json().catch(() => ({}));
  if (!response.ok) throw new Error(Object.values(data.errors || {})[0]?.[0] || data.message || `Ошибка ${response.status}`);
  return data;
}
async function load() {
  try { const data = await api('GET', '/admin/settings'); Object.assign(form, data, { api_key: '' }); hasKey.value = data.has_key; }
  catch (e) { error.value = e.message; }
}
async function save() {
  busy.value = true; error.value = ''; notice.value = '';
  try { const data = await api('PUT', '/admin/settings', form); hasKey.value = data.has_key; form.api_key = ''; notice.value = data.message; }
  catch (e) { error.value = e.message; }
  finally { busy.value = false; }
}
async function removeKey() {
  if (!window.confirm('Удалить API-ключ и выключить ИИ-помощника?')) return;
  busy.value = true; error.value = ''; notice.value = '';
  try { const data = await api('DELETE', '/admin/settings/api-key'); hasKey.value = false; form.api_key = ''; form.enabled = false; notice.value = data.message; }
  catch (e) { error.value = e.message; }
  finally { busy.value = false; }
}
onMounted(load);
</script>

<template>
  <div class="panel form-panel settings-panel">
    <div class="section-heading"><h2>OpenAI API</h2><p>Ключ хранится на сервере в зашифрованном виде. Его значение не показывается после сохранения.</p></div>
    <p v-if="error" class="notice error" role="alert">{{ error }}</p><p v-if="notice" class="notice success" role="status">{{ notice }}</p>
    <div class="field"><label for="openai-key"><PhKey :size="15" /> API-ключ</label><input id="openai-key" v-model="form.api_key" type="password" autocomplete="new-password" :placeholder="hasKey ? 'Ключ сохранён · введите новый для замены' : 'Вставьте API-ключ'"><small>{{ hasKey ? 'Ключ настроен. Пустое поле оставит его без изменений.' : 'Ключ пока не настроен.' }}</small></div>
    <div class="field"><label for="openai-model">Модель</label><input id="openai-model" v-model.trim="form.model" type="text" placeholder="Укажите модель OpenAI"></div>
    <div class="field inline"><label for="openai-enabled">Включить ИИ-помощника</label><input id="openai-enabled" v-model="form.enabled" type="checkbox"></div>
    <div class="settings-grid"><div class="field"><label for="ai-output">Максимум токенов ответа</label><input id="ai-output" v-model.number="form.max_output_tokens" type="number" min="256" max="8000"></div><div class="field"><label for="ai-daily">Запросов в день на администратора</label><input id="ai-daily" v-model.number="form.daily_request_limit" type="number" min="1" max="1000"></div><div class="field"><label for="ai-tokens">Токенов в день на администратора</label><input id="ai-tokens" v-model.number="form.daily_token_limit" type="number" min="1000" max="10000000"></div></div>
    <div class="settings-actions"><button class="button primary" type="button" :disabled="busy" @click="save"><PhFloppyDisk :size="17" /> Сохранить</button><button v-if="hasKey" class="button danger" type="button" :disabled="busy" @click="removeKey"><PhTrash :size="17" /> Удалить ключ</button></div>
  </div>
</template>
